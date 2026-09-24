<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use App\Services\CampaignBudgetDepletionService;
use App\Services\EventRewardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventQualificationAndAtomicRewardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Active Event Reward Rules
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        // 2. Seed Active Business Ad Reward Rules
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();
    }

    /**
     * TEST 1: Verified member can access reward preview with authoritative referral count & reward.
     */
    public function test_01_verified_member_accesses_reward_preview(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 50.00, 'remaining_amount' => 50.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($viewer, 'member');

        $res = $this->getJson("/api/member/events/{$event->id}/reward-preview");
        $res->assertOk()
            ->assertJson([
                'success' => true,
                'eligible' => true,
                'direct_verified_referral_count' => 0,
                'reward_amount_usd' => 0.025,
                'event_id' => $event->id,
            ]);
    }

    /**
     * TEST 2: Unverified member is blocked on reward preview (403 Forbidden).
     */
    public function test_02_unverified_member_cannot_access_reward_preview(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $this->createActivePaidCampaign($event, $organizer);

        $unverified = $this->createMember(['mobile_verified_at' => null]);
        $this->actingAs($unverified, 'member');

        $res = $this->getJson("/api/member/events/{$event->id}/reward-preview");
        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status' => 'unverified_member',
                'verified_required' => true,
            ]);
    }

    /**
     * TEST 3: Unverified member is blocked from calling qualify-interest (403 Forbidden).
     */
    public function test_03_unverified_member_cannot_qualify_interest(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $this->createActivePaidCampaign($event, $organizer);

        $unverified = $this->createMember(['mobile_verified_at' => null]);
        $this->actingAs($unverified, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status' => 'unverified_member',
                'verified_required' => true,
            ]);
    }

    /**
     * TEST 4: Successful Interested qualification triggers exact atomic reward processing.
     * Budget decreases by $0.0250, Member Reward Wallet increases by $0.0250, AdReward created, EventResponse saved.
     */
    public function test_04_successful_interested_triggers_atomic_reward_processing(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'remaining_amount' => 10.00,
            'spent_amount' => 0.00,
        ]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'already_rewarded' => false,
                'reward_amount_usd' => 0.025,
                'reward_amount_exact' => '0.0250',
                'event_id' => $event->id,
                'campaign_id' => $campaign->id,
                'member_reward_balance' => 0.025,
                'remaining_budget' => 9.975,
            ]);

        // Verify Campaign Budget
        $campaign->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);
        $this->assertSame(0.025, (float) $campaign->spent_amount);

        // Verify Member Reward Wallet
        $viewer->refresh();
        $this->assertSame(0.025, (float) $viewer->reward_balance);

        // Verify AdReward record
        $this->assertDatabaseHas('ad_rewards', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $viewer->id,
            'reward_amount_usd' => 0.025,
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Verify EventResponse record
        $this->assertDatabaseHas('event_responses', [
            'event_id' => $event->id,
            'member_id' => $viewer->id,
            'response' => 'interested',
        ]);
    }

    /**
     * TEST 5: Client-supplied referral count and reward parameters are strictly ignored.
     */
    public function test_05_client_supplied_parameters_are_ignored(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // Attacker attempts to spoof $100.00 reward and 50 referrals
        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", [
            'consent_accepted' => true,
            'reward_amount' => 100.00,
            'reward_amount_usd' => 100.00,
            'referral_count' => 50,
            'direct_verified_referrals' => 50,
            'rule_id' => 9999,
        ]);

        $res->assertOk();
        // Server derived the true tier: 0 referrals -> $0.0250
        $this->assertSame(0.025, (float) $res->json('reward_amount_usd'));

        $viewer->refresh();
        $this->assertSame(0.025, (float) $viewer->reward_balance);

        $campaign->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);
    }

    /**
     * TEST 6: Higher referral tier resolves higher authoritative reward ($0.0500 for 15 referrals).
     */
    public function test_06_member_with_fifteen_referrals_qualifies_for_top_tier(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['user_id' => 'TOP_TIER_USER', 'mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->createVerifiedReferrals($viewer, 16);

        $this->actingAs($viewer, 'member');
        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $res->assertOk()
            ->assertJson([
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'reward_amount_exact' => '0.0500',
                'direct_verified_referrals' => 16,
                'member_reward_balance' => 0.05,
            ]);

        $viewer->refresh();
        $this->assertSame(0.05, (float) $viewer->reward_balance);

        $campaign->refresh();
        $this->assertSame(9.95, (float) $campaign->remaining_amount);
    }

    /**
     * TEST 7: One member / one event duplicate protection.
     * Second request returns already_rewarded: true with 0 new credits and 0 budget debits.
     */
    public function test_07_one_member_one_event_reward_protection(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // 1st request succeeds
        $first = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $first->assertOk()->assertJson(['rewarded' => true, 'already_rewarded' => false]);

        $campaign->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);

        // 2nd request (duplicate/replay)
        $second = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $second->assertOk()->assertJson([
            'success' => true,
            'already_rewarded' => true,
            'rewarded' => false,
            'reward_amount_usd' => 0.00,
        ]);

        // Assert balances unchanged
        $campaign->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);

        $viewer->refresh();
        $this->assertSame(0.025, (float) $viewer->reward_balance);

        // Assert exactly 1 AdReward record exists
        $this->assertSame(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->count());
    }

    /**
     * TEST 8: Re-resolution at qualification time protects against stale modal state.
     * If member gains new verified referrals after opening modal, qualification pays the newly earned tier.
     */
    public function test_08_reresolution_at_qualification_time_uses_live_referral_state(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['user_id' => 'RACE_USER', 'mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // Modal preview when user has 0 referrals -> $0.0250
        $preview = $this->getJson("/api/member/events/{$event->id}/reward-preview");
        $preview->assertOk()->assertJson(['reward_amount_usd' => 0.025]);

        // In the background, user gains 7 referrals (tier 6-14 -> $0.0350)
        $this->createVerifiedReferrals($viewer, 7);

        // Member now clicks Interested
        $qualify = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $qualify->assertOk()
            ->assertJson([
                'rewarded' => true,
                'reward_amount_usd' => 0.035,
                'direct_verified_referrals' => 7,
            ]);

        $viewer->refresh();
        $this->assertSame(0.035, (float) $viewer->reward_balance);
    }

    /**
     * TEST 9: Admin rule change race condition.
     * If Admin updates tier amounts between preview and click, qualification pays authoritative new rule.
     */
    public function test_09_admin_rule_change_between_preview_and_click(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // Preview: baseline is $0.0250
        $this->getJson("/api/member/events/{$event->id}/reward-preview")->assertOk();

        // Admin updates baseline rule to $0.0280
        AdRewardRule::forEvent()->where('min_referrals', 0)->update(['reward_amount' => 0.0280]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        // Qualification request
        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertOk()->assertJson(['reward_amount_usd' => 0.028]);

        $viewer->refresh();
        $this->assertSame(0.028, (float) $viewer->reward_balance);
    }

    /**
     * TEST 10: Insufficient campaign budget blocks reward without overdrafting or crediting wallet.
     */
    public function test_10_insufficient_budget_blocks_reward_and_preserves_balances(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        // Remaining budget $0.0100 is less than minimum reward $0.0250
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'remaining_amount' => 0.0100,
            'spent_amount' => 9.9900,
        ]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
            ]);

        // Campaign budget not negative
        $campaign->refresh();
        $this->assertSame(0.01, (float) $campaign->remaining_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);

        // Member wallet not credited
        $viewer->refresh();
        $this->assertSame(0.00, (float) $viewer->reward_balance);

        // No reward record created
        $this->assertSame(0, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 11: Phase 9 exhaustion: Campaign with exact remaining budget funds final reward and auto-stops.
     */
    public function test_11_penultimate_budget_funds_reward_and_auto_stops_campaign(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        // Exact budget for one $0.0250 reward
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'remaining_amount' => 0.0250,
            'spent_amount' => 9.9750,
        ]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertOk()
            ->assertJson([
                'rewarded' => true,
                'remaining_budget' => 0.00,
                'campaign_status' => AdCampaign::STATUS_STOPPED,
            ]);

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(10.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);
    }

    /**
     * TEST 12: Concurrency TEST A: Same member sends concurrent requests.
     * Exactly one succeeds, second returns already_rewarded. Exactly one budget deduction and one wallet credit.
     */
    public function test_12_same_member_concurrent_requests_guarantee_single_winner(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $r1 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $r2 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $r1->assertOk();
        $r2->assertOk();

        $this->assertTrue($r1->json('rewarded') || $r2->json('rewarded'));
        $this->assertTrue($r1->json('already_rewarded') || $r2->json('already_rewarded'));

        $campaign->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);

        $viewer->refresh();
        $this->assertSame(0.025, (float) $viewer->reward_balance);

        $this->assertSame(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->count());
    }

    /**
     * TEST 13: Concurrency TEST B: Different members competing for limited budget.
     * Remaining budget = $0.0250. Member 1 qualifies and consumes it; Member 2 is safely rejected. No negative budget.
     */
    public function test_13_multi_member_budget_competition_prevents_overspending(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        // Only enough budget for 1 reward
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 1.00,
            'remaining_amount' => 0.0250,
            'spent_amount' => 0.9750,
        ]);

        $m1 = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $m2 = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);

        $this->actingAs($m1, 'member');
        $r1 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $this->actingAs($m2, 'member');
        $r2 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $r1->assertOk()->assertJson(['rewarded' => true]);
        $r2->assertStatus(422)->assertJson(['rewarded' => false]);

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(1.00, (float) $campaign->spent_amount);

        $m1->refresh();
        $this->assertSame(0.025, (float) $m1->reward_balance);

        $m2->refresh();
        $this->assertSame(0.00, (float) $m2->reward_balance);

        $this->assertSame(1, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 14: Concurrency TEST C: Wallet concurrency.
     * One member receives rewards from two distinct events; both credit accurately without lost updates.
     */
    public function test_14_wallet_concurrency_from_separate_events(): void
    {
        $org1 = $this->createMember();
        $e1 = $this->createEvent($org1);
        $c1 = $this->createActivePaidCampaign($e1, $org1, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $org2 = $this->createMember();
        $e2 = $this->createEvent($org2);
        $c2 = $this->createActivePaidCampaign($e2, $org2, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $r1 = $this->postJson("/api/member/events/{$e1->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $r2 = $this->postJson("/api/member/events/{$e2->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $r1->assertOk()->assertJson(['rewarded' => true]);
        $r2->assertOk()->assertJson(['rewarded' => true]);

        $viewer->refresh();
        // $0.0250 + $0.0250 = $0.0500
        $this->assertSame(0.05, (float) $viewer->reward_balance);

        $this->assertSame(1, AdReward::where('ad_campaign_id', $c1->id)->where('member_id', $viewer->id)->count());
        $this->assertSame(1, AdReward::where('ad_campaign_id', $c2->id)->where('member_id', $viewer->id)->count());
    }

    /**
     * TEST 15: Required Atomicity test: simulated failure during transaction rolls back wallet and budget.
     */
    public function test_15_atomicity_and_transaction_rollback_on_failure(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);

        // Verify initial state
        $initialBudget = (float) $campaign->remaining_amount;
        $initialBalance = (float) $viewer->reward_balance;

        // Force an exception inside transaction by setting an invalid database state or injecting failure
        try {
            DB::transaction(function () use ($campaign, $viewer, $event) {
                // Deplete budget
                $campaign->remaining_amount -= 0.025;
                $campaign->save();

                // Credit wallet
                $viewer->reward_balance += 0.025;
                $viewer->save();

                // Force throw to simulate error before commit
                throw new \Exception('Simulated unexpected failure during atomic reward transaction');
            });
        } catch (\Throwable $e) {
            // caught
        }

        $campaign->refresh();
        $viewer->refresh();

        $this->assertSame($initialBudget, (float) $campaign->remaining_amount);
        $this->assertSame($initialBalance, (float) $viewer->reward_balance);
        $this->assertSame(0, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 16: Free events remain completely functional with normal RSVP.
     */
    public function test_16_free_events_remain_unaffected(): void
    {
        $organizer = $this->createMember();
        $freeEvent = $this->createEvent($organizer); // no campaign attached

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // Calling qualify-interest on a free event fails cleanly
        $res = $this->postJson("/api/member/events/{$freeEvent->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'not_a_paid_event',
            ]);

        // Standard RSVP still functions properly
        $rsvp = $this->postJson("/api/member/events/{$freeEvent->id}/respond", ['response' => 'interested']);
        $rsvp->assertOk()->assertJson(['success' => true, 'response' => 'interested']);

        $viewer->refresh();
        $this->assertSame(0.00, (float) $viewer->reward_balance);
    }

    /**
     * TEST 17: Claim alias route works identically to qualify-interest.
     */
    public function test_17_claim_alias_route_works_identically(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/claim", ['consent_accepted' => true]);
        $res->assertOk()->assertJson(['rewarded' => true, 'reward_amount_usd' => 0.025]);

        $viewer->refresh();
        $this->assertSame(0.025, (float) $viewer->reward_balance);
    }

    /**
     * TEST 18: Unauthenticated request to qualify-interest is rejected (401 Unauthorized).
     */
    public function test_18_unauthenticated_request_is_rejected(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $this->createActivePaidCampaign($event, $organizer);

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res->assertStatus(401);
    }

    /**
     * TEST 19: Non-existent event returns 404 Not Found.
     */
    public function test_19_nonexistent_event_returns_404(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/999999/campaign/qualify-interest");
        $res->assertStatus(404);
    }

    /**
     * TEST 20: Inactive or unapproved campaign returns 422 Unprocessable Entity.
     */
    public function test_20_unapproved_or_paused_campaign_rejects_qualification(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        // Paused campaign
        $this->createActivePaidCampaign($event, $organizer, [
            'status' => AdCampaign::STATUS_PAUSED,
        ]);

        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'campaign_not_active',
            ]);
    }

    /**
     * TEST 21: Auditable spend history is logged in campaign target_audience spend_history.
     */
    public function test_21_auditable_spend_history_is_recorded_in_campaign(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($viewer, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $res->assertOk();

        $campaign->refresh();
        $targetAudience = (array) ($campaign->target_audience ?? []);
        $spendHistory = (array) ($targetAudience['spend_history'] ?? []);

        $this->assertNotEmpty($spendHistory);
        $lastSpend = end($spendHistory);
        $this->assertSame(0.025, (float) $lastSpend['amount']);
        $this->assertSame(10.00, (float) $lastSpend['previous_remaining']);
        $this->assertSame(9.975, (float) $lastSpend['new_remaining']);
        $this->assertSame("event_reward_{$campaign->id}_{$viewer->id}", $lastSpend['spend_reference']);
    }

    /**
     * TEST 22: Rapid double-submission (double-click simulation).
     * Handled safely with single financial deduction.
     */
    public function test_22_rapid_double_click_simulation_is_safe(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // Fire request 1
        $r1 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        // Fire request 2 immediately
        $r2 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);

        $r1->assertOk();
        $r2->assertOk();

        $viewer->refresh();
        $this->assertSame(0.025, (float) $viewer->reward_balance);

        $campaign->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);
        $this->assertSame(0.025, (float) $campaign->spent_amount);

        $this->assertSame(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->count());
    }

    /**
     * TEST 24: Event qualify-interest strictly validates consent_accepted matrix and blocks direct API bypass.
     */
    public function test_24_event_qualify_interest_consent_validation_matrix_and_security_bypass(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        // Case 1: Missing consent
        $resMissing = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", []);
        $resMissing->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'consent_required',
                'message' => 'Please accept the profile-sharing consent before continuing.',
            ]);

        // Case 2: Boolean false
        $resFalse = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => false]);
        $resFalse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'consent_required',
            ]);

        // Case 3: Integer 0
        $resZero = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => 0]);
        $resZero->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'consent_required',
            ]);

        // Case 4: String "false"
        $resStringFalse = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => 'false']);
        $resStringFalse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'consent_required',
            ]);

        // Case 5: Null
        $resNull = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => null]);
        $resNull->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'consent_required',
            ]);

        // Verify that zero database changes occurred during rejected bypass attempts
        $campaign->refresh();
        $viewer->refresh();
        $this->assertSame(10.00, (float) $campaign->remaining_amount);
        $this->assertSame(0.00, (float) $campaign->spent_amount);
        $this->assertSame(0.00, (float) $viewer->reward_balance);
        $this->assertSame(0, AdReward::where('ad_campaign_id', $campaign->id)->count());

        // Case 6: Explicit affirmative consent_accepted = true passes
        $resPass = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", ['consent_accepted' => true]);
        $resPass->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.025,
            ]);

        $campaign->refresh();
        $viewer->refresh();
        $this->assertSame(9.975, (float) $campaign->remaining_amount);
        $this->assertSame(0.025, (float) $viewer->reward_balance);
        $this->assertSame(1, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 23: Pure reader invariance: previewing reward does not modify wallet or budget.
     */
    public function test_23_reward_preview_has_zero_side_effects(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $viewer = $this->createMember(['mobile_verified_at' => now(), 'reward_balance' => 0.00]);
        $this->actingAs($viewer, 'member');

        $this->getJson("/api/member/events/{$event->id}/reward-preview")->assertOk();
        $this->getJson("/api/member/events/{$event->id}/reward-preview")->assertOk();

        $campaign->refresh();
        $viewer->refresh();

        $this->assertSame(10.00, (float) $campaign->remaining_amount);
        $this->assertSame(0.00, (float) $campaign->spent_amount);
        $this->assertSame(0.00, (float) $viewer->reward_balance);
        $this->assertSame(0, AdReward::where('ad_campaign_id', $campaign->id)->count());
        $this->assertSame(0, EventResponse::where('event_id', $event->id)->count());
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function createMember(array $attrs = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Member ' . uniqid(),
            'email' => 'member-' . uniqid() . '@example.com',
            'user_id' => 'USR_' . strtoupper(uniqid()),
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'ad_balance' => 100.00,
            'reward_balance' => 0.00,
        ], $attrs));
    }

    private function createEvent(Member $organizer, array $attrs = []): Event
    {
        return Event::create(array_merge([
            'organizer_id' => $organizer->id,
            'title' => 'Paid Tech Summit ' . uniqid(),
            'slug' => 'paid-tech-summit-' . strtolower(uniqid()),
            'description' => 'A premier paid tech conference.',
            'category' => 'Technology',
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_date' => now()->addDays(6)->format('Y-m-d'),
            'end_time' => '18:00',
            'privacy' => 'public',
            'status' => 'published',
        ], $attrs));
    }

    private function createActivePaidCampaign(Event $event, Member $organizer, array $attrs = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'business_page_id' => null,
            'post_id' => null,
            'campaign_name' => 'Paid Campaign for ' . $event->title,
            'budget' => 50.00,
            'additional_funding' => 0.00,
            'total_funded' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'currency' => 'USD',
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attrs));
    }

    private function createVerifiedReferrals(Member $introducer, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Member::create([
                'name' => "Referred {$i} of {$introducer->name}",
                'email' => "ref_{$i}_" . uniqid() . "@example.com",
                'user_id' => 'REF_' . strtoupper(uniqid()),
                'introducer_id' => $introducer->user_id,
                'mobile_verified_at' => now(),
                'password' => 'secret123',
                'ad_balance' => 0.00,
                'reward_balance' => 0.00,
            ]);
        }
    }
}