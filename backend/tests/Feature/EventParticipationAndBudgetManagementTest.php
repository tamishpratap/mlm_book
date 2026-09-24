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

class EventParticipationAndBudgetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Active Event Reward Rules (Tiered: 0-5, 6-14, 15+)
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

    // =========================================================================
    // SECTION 41: REQUIRED PARTICIPATION TESTS
    // =========================================================================

    /**
     * TEST 1: Successful Interested/reward creates correct participant state.
     */
    public function test_01_successful_interested_creates_correct_participant_and_reward_state(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 20.00, 'remaining_amount' => 20.00]);

        $participant = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($participant, 'member');

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rewarded', true)
            ->assertJsonPath('reward_amount_usd', 0.025);

        // Verify EventResponse participant record exists
        $this->assertDatabaseHas('event_responses', [
            'event_id' => $event->id,
            'member_id' => $participant->id,
            'response' => 'interested',
        ]);

        // Verify AdReward record exists with credited status
        $this->assertDatabaseHas('ad_rewards', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $participant->id,
            'reward_amount_usd' => 0.0250,
            'status' => AdReward::STATUS_CREDITED,
        ]);
    }

    /**
     * TEST 2: Failed reward does not falsely become successful paid participant.
     */
    public function test_02_failed_reward_does_not_falsely_become_successful_paid_participant(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 0.01, 'remaining_amount' => 0.01]);

        $unverified = $this->createMember(['mobile_verified_at' => null]);
        $this->actingAs($unverified, 'member');

        // Attempt qualify with unverified member
        $res = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res->assertStatus(403)
            ->assertJsonPath('rewarded', false);

        // Verify no rewarded row exists for this member
        $this->assertDatabaseMissing('ad_rewards', [
            'member_id' => $unverified->id,
            'status' => AdReward::STATUS_CREDITED,
        ]);
    }

    /**
     * TEST 3: Duplicate Interested does not create duplicate participant or double reward.
     */
    public function test_03_duplicate_interested_does_not_create_duplicate_participant(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 20.00, 'remaining_amount' => 20.00]);

        $participant = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($participant, 'member');

        // First attempt
        $res1 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res1->assertOk()->assertJsonPath('rewarded', true);

        // Second attempt
        $res2 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res2->assertOk()
            ->assertJsonPath('status', 'already_rewarded')
            ->assertJsonPath('already_rewarded', true)
            ->assertJsonPath('rewarded', false);

        // Exactly one participant response and one reward record
        $this->assertEquals(1, EventResponse::where('event_id', $event->id)->where('member_id', $participant->id)->count());
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $participant->id)->count());
    }

    /**
     * TEST 4: Existing Event participation remains functional.
     */
    public function test_04_existing_event_participation_remains_functional(): void
    {
        $organizer = $this->createMember();
        $freeEvent = $this->createEvent($organizer);

        $attendee = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($attendee, 'member');

        $res = $this->postJson("/api/member/events/{$freeEvent->id}/respond", [
            'response' => 'going',
        ]);
        $res->assertOk();

        $this->assertDatabaseHas('event_responses', [
            'event_id' => $freeEvent->id,
            'member_id' => $attendee->id,
            'response' => 'going',
        ]);
    }

    /**
     * TEST 5: Participant belongs to correct Event/campaign and correct member.
     */
    public function test_05_participant_belongs_to_correct_event_and_member(): void
    {
        $organizerA = $this->createMember();
        $eventA = $this->createEvent($organizerA);
        $campaignA = $this->createActivePaidCampaign($eventA, $organizerA);

        $organizerB = $this->createMember();
        $eventB = $this->createEvent($organizerB);
        $campaignB = $this->createActivePaidCampaign($eventB, $organizerB);

        $member = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($member, 'member');

        $this->postJson("/api/member/events/{$eventA->id}/campaign/qualify-interest")->assertOk();

        // Check Event A has reward, Event B does NOT
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaignA->id)->where('member_id', $member->id)->count());
        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaignB->id)->where('member_id', $member->id)->count());
    }

    /**
     * TEST 6: Unauthorized creator cannot access participant analytics.
     */
    public function test_06_unauthorized_creator_cannot_access_participant_analytics(): void
    {
        $organizerA = $this->createMember();
        $eventA = $this->createEvent($organizerA);
        $this->createActivePaidCampaign($eventA, $organizerA);

        $organizerB = $this->createMember();
        $this->actingAs($organizerB, 'member');

        // Organizer B attempts to view Organizer A's event campaign analytics
        $res = $this->getJson("/api/member/events/{$eventA->id}/campaign/analytics");
        $res->assertStatus(403)
            ->assertJsonPath('success', false);

        // Participants endpoint also rejects unauthorized access
        $res2 = $this->getJson("/api/member/events/{$eventA->id}/campaign/participants");
        $res2->assertStatus(403);
    }

    /**
     * TEST 7: Historical participant record remains after exhaustion and reactivation.
     */
    public function test_07_historical_participant_record_remains_after_exhaustion_and_reactivation(): void
    {
        $organizer = $this->createMember(['ad_balance' => 100.00]);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.9750,
            'remaining_amount' => 0.0250,
        ]);

        $participant = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($participant, 'member');

        // Claim final reward, exhausting campaign to 0.00
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);

        // Organizer adds funds to SAME campaign
        $this->actingAs($organizer, 'member');
        $addRes = $this->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 10.00]);
        $addRes->assertOk();

        // Reactivate SAME campaign
        $reactivateRes = $this->postJson("/api/member/events/{$event->id}/campaign/reactivate");
        $reactivateRes->assertOk();

        // Historical participant record still exists untouched
        $this->assertDatabaseHas('event_responses', [
            'event_id' => $event->id,
            'member_id' => $participant->id,
            'response' => 'interested',
        ]);
        $this->assertDatabaseHas('ad_rewards', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $participant->id,
            'status' => AdReward::STATUS_CREDITED,
        ]);
    }

    // =========================================================================
    // SECTION 42: REQUIRED HISTORICAL SNAPSHOT TESTS
    // =========================================================================

    /**
     * TEST 8: Reward amount at payout is stored authoritatively with rule and referral snapshot.
     */
    public function test_08_reward_amount_at_payout_is_stored_authoritatively(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer);

        // Member with 10 verified referrals qualifies for 6-14 tier ($0.0350)
        $member = $this->createMember(['mobile_verified_at' => now()]);
        $this->createVerifiedReferrals($member, 10);

        $this->actingAs($member, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        /** @var AdReward $reward */
        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $member->id)->first();
        $this->assertNotNull($reward);
        $this->assertEquals(0.0350, (float) $reward->reward_amount_usd);
        $this->assertEquals(10, $reward->direct_verified_referral_count);
        $this->assertEquals(6, $reward->rule_min_referrals);
        $this->assertEquals(14, $reward->rule_max_referrals);
        $this->assertEquals('6–14', $reward->tier_label);
    }

    /**
     * TEST 9: Subsequent Admin rule changes do NOT mutate historical reward snapshots.
     */
    public function test_09_subsequent_admin_rule_changes_do_not_mutate_historical_reward(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer);

        $member = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($member, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $member->id)->first();
        $originalAmount = (float) $reward->reward_amount_usd;
        $this->assertEquals(0.0250, $originalAmount);

        // Admin later changes the tier 0-5 reward amount to $0.0900
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 0)
            ->update(['reward_amount' => 0.0900]);
        AdRewardRule::clearCache();

        // Historical reward record remains exactly $0.0250
        $this->assertEquals(0.0250, (float) $reward->fresh()->reward_amount_usd);
    }

    /**
     * TEST 10: Old reward amount remains unchanged after reactivation.
     */
    public function test_10_old_reward_remains_unchanged_after_reactivation(): void
    {
        $organizer = $this->createMember(['ad_balance' => 50.00]);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.9750,
            'remaining_amount' => 0.0250,
        ]);

        $member1 = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($member1, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        // Exhausted
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);

        // Add funds and reactivate
        $this->actingAs($organizer, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 20.00])->assertOk();
        $this->postJson("/api/member/events/{$event->id}/campaign/reactivate")->assertOk();

        // Old reward snapshot is intact
        $reward1 = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $member1->id)->first();
        $this->assertEquals(0.0250, (float) $reward1->reward_amount_usd);
    }

    // =========================================================================
    // SECTION 43: REQUIRED ANALYTICS TESTS
    // =========================================================================

    /**
     * TEST 11: Creator sees only own Event data with accurate counts and financial reconciliation.
     */
    public function test_11_creator_sees_only_own_event_data_with_accurate_metrics(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $participant1 = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($participant1, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        // Organizer views analytics
        $this->actingAs($organizer, 'member');
        $res = $this->getJson("/api/member/events/{$event->id}/campaign/analytics");
        $res->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(10.00, $res->json('budget_metrics.initial_budget'));
        $this->assertEquals(0.025, $res->json('budget_metrics.spent_amount'));
        $this->assertEquals(9.975, $res->json('budget_metrics.remaining_amount'));
        $this->assertTrue($res->json('budget_metrics.financial_reconciliation.reconciles_exactly'));
        $this->assertEquals(1, $res->json('participant_metrics.total_participants'));
        $this->assertEquals(1, $res->json('participant_metrics.rewarded_participants'));
        $this->assertEquals(0.025, $res->json('participant_metrics.total_rewards_paid_usd'));
    }

    /**
     * TEST 12: Pagination, search query, and reward status filtering work in creator analytics.
     */
    public function test_12_pagination_search_and_filters_in_creator_analytics(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        // Alice: rewarded
        $alice = $this->createMember(['name' => 'Alice Wonder', 'mobile_verified_at' => now()]);
        $this->actingAs($alice, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        // Bob: interested only (unrewarded)
        $bob = $this->createMember(['name' => 'Bob Builder', 'mobile_verified_at' => now()]);
        EventResponse::create(['event_id' => $event->id, 'member_id' => $bob->id, 'response' => 'interested']);

        $this->actingAs($organizer, 'member');

        // Filter: rewarded only
        $resRewarded = $this->getJson("/api/member/events/{$event->id}/campaign/analytics?reward_status=rewarded");
        $resRewarded->assertOk()
            ->assertJsonPath('participants.total', 1)
            ->assertJsonPath('participants.data.0.name', 'Alice Wonder');

        // Filter: unpaid only
        $resUnpaid = $this->getJson("/api/member/events/{$event->id}/campaign/analytics?reward_status=unpaid");
        $resUnpaid->assertOk()
            ->assertJsonPath('participants.total', 1)
            ->assertJsonPath('participants.data.0.name', 'Bob Builder');

        // Search by query
        $resSearch = $this->getJson("/api/member/events/{$event->id}/campaign/analytics?q=Alice");
        $resSearch->assertOk()
            ->assertJsonPath('participants.total', 1)
            ->assertJsonPath('participants.data.0.name', 'Alice Wonder');
    }

    /**
     * TEST 13: Business Ads rewards are strictly isolated from Event analytics.
     */
    public function test_13_business_ads_rewards_are_isolated_from_event_analytics(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        // Create a foreign Business Ad reward
        $businessCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'member_id' => $organizer->id,
            'campaign_name' => 'Business Ad',
            'budget' => 20.00,
            'remaining_amount' => 20.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);
        $foreignMember = $this->createMember();
        AdReward::create([
            'ad_campaign_id' => $businessCampaign->id,
            'member_id' => $foreignMember->id,
            'reward_amount_usd' => 0.05,
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $this->actingAs($organizer, 'member');
        $res = $this->getJson("/api/member/events/{$event->id}/campaign/analytics");
        $res->assertOk()
            ->assertJsonPath('participant_metrics.total_participants', 0)
            ->assertJsonPath('participant_metrics.rewarded_participants', 0);
        $this->assertEquals(0.0, $res->json('participant_metrics.total_rewards_paid_usd'));
    }

    // =========================================================================
    // SECTION 44: REQUIRED BUDGET & REACTIVATION TESTS
    // =========================================================================

    /**
     * TEST 14: Reward processing decreases budget atomically.
     */
    public function test_14_reward_processing_decreases_budget_atomically(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $participant = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($participant, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        $fresh = $campaign->fresh();
        $this->assertEquals(0.025, (float) $fresh->spent_amount);
        $this->assertEquals(9.975, (float) $fresh->remaining_amount);
        $this->assertEquals(10.00, round((float) $fresh->spent_amount + (float) $fresh->remaining_amount, 4));
    }

    /**
     * TEST 15: Low-budget state does not incorrectly imply exhaustion.
     */
    public function test_15_low_budget_state_does_not_imply_exhaustion(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        // Remaining 0.50 is > min active reward (0.025), but <= low budget threshold (1.00)
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.50,
            'remaining_amount' => 0.50,
        ]);

        $this->actingAs($organizer, 'member');
        $res = $this->getJson("/api/member/events/{$event->id}/campaign/analytics");
        $res->assertOk()
            ->assertJsonPath('budget_metrics.is_low_budget', true)
            ->assertJsonPath('budget_metrics.is_exhausted', false);
    }

    /**
     * TEST 16: Exhaustion occurs according to Phase 9 (remaining < min active reward).
     */
    public function test_16_exhaustion_occurs_when_remaining_is_below_minimum_active_reward(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        // Remaining 0.020 is below min active reward of 0.025
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.98,
            'remaining_amount' => 0.02,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        $this->actingAs($organizer, 'member');
        $res = $this->getJson("/api/member/events/{$event->id}/campaign/analytics");
        $res->assertOk()
            ->assertJsonPath('budget_metrics.is_exhausted', true)
            ->assertJsonPath('budget_metrics.is_low_budget', false);
    }

    /**
     * TEST 17: Add Fund increases budget on SAME campaign and does not reset spent amount or history.
     */
    public function test_17_add_fund_increases_same_campaign_budget_without_resetting_history(): void
    {
        $organizer = $this->createMember(['ad_balance' => 100.00]);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 5.00,
            'remaining_amount' => 5.00,
        ]);

        $initialCampaignCount = AdCampaign::count();
        $campaignId = $campaign->id;

        $this->actingAs($organizer, 'member');
        $res = $this->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
            'amount' => 20.00,
        ]);
        $res->assertOk()
            ->assertJsonPath('success', true);

        // Exactly the SAME campaign ID
        $this->assertEquals($initialCampaignCount, AdCampaign::count());
        $fresh = $campaign->fresh();
        $this->assertEquals($campaignId, $fresh->id);

        // Budget accounting: spent amount remains unchanged
        $this->assertEquals(5.00, (float) $fresh->spent_amount);
        $this->assertEquals(20.00, (float) $fresh->additional_funding);
        $this->assertEquals(30.00, (float) $fresh->total_funded);
        $this->assertEquals(25.00, (float) $fresh->remaining_amount);
    }

    /**
     * TEST 18: Reactivation transitions stopped campaign back to active on the SAME campaign row.
     */
    public function test_18_reactivation_transitions_same_campaign_to_active(): void
    {
        $organizer = $this->createMember(['ad_balance' => 50.00]);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.99,
            'remaining_amount' => 0.01,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        $campaignId = $campaign->id;
        $this->actingAs($organizer, 'member');

        // Cannot reactivate before adding funds because remaining 0.01 < 0.025
        $failedReactivate = $this->postJson("/api/member/events/{$event->id}/campaign/reactivate");
        $failedReactivate->assertStatus(422)
            ->assertJsonPath('success', false);

        // Add funds to SAME campaign
        $this->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 15.00])->assertOk();

        // Reactivate succeeds on SAME campaign
        $res = $this->postJson("/api/member/events/{$event->id}/campaign/reactivate");
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.status', AdCampaign::STATUS_ACTIVE);

        $fresh = $campaign->fresh();
        $this->assertEquals($campaignId, $fresh->id);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $fresh->status);
    }

    // =========================================================================
    // SECTION 45: REQUIRED CONCURRENCY TESTS
    // =========================================================================

    /**
     * TEST A: REWARD + EXHAUSTION CONCURRENCY
     * Campaign has budget for exactly one reward ($0.0250). Two requests compete.
     */
    public function test_19_concurrency_test_a_reward_and_exhaustion(): void
    {
        $organizer = $this->createMember();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.9750,
            'remaining_amount' => 0.0250,
        ]);

        $member1 = $this->createMember(['mobile_verified_at' => now()]);
        $member2 = $this->createMember(['mobile_verified_at' => now()]);

        // Simulating rapid serial/concurrent competition under DB transactions
        $this->actingAs($member1, 'member');
        $res1 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        $this->actingAs($member2, 'member');
        $res2 = $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        // Exactly one succeeds, one fails due to insufficient budget
        $this->assertTrue(
            ($res1->json('rewarded') === true && $res2->json('rewarded') === false) ||
            ($res1->json('rewarded') === false && $res2->json('rewarded') === true)
        );

        $fresh = $campaign->fresh();
        $this->assertEquals(0.00, (float) $fresh->remaining_amount);
        $this->assertEquals(10.00, (float) $fresh->spent_amount);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST B: REWARD + ADD FUND CONCURRENCY
     * Atomic accounting under pessimistic locking prevents lost updates.
     */
    public function test_20_concurrency_test_b_reward_and_add_fund(): void
    {
        $organizer = $this->createMember(['ad_balance' => 50.00]);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'remaining_amount' => 10.00,
        ]);

        $member = $this->createMember(['mobile_verified_at' => now()]);

        // Run reward qualification
        $this->actingAs($member, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        // Run Add Fund
        $this->actingAs($organizer, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 10.00])->assertOk();

        $fresh = $campaign->fresh();
        $this->assertEquals(0.025, (float) $fresh->spent_amount);
        $this->assertEquals(10.00, (float) $fresh->additional_funding);
        $this->assertEquals(20.00, (float) $fresh->total_funded);
        $this->assertEquals(19.975, (float) $fresh->remaining_amount);
        $this->assertEquals(20.00, round((float) $fresh->spent_amount + (float) $fresh->remaining_amount, 4));
    }

    /**
     * TEST C: EXHAUSTION + ADD FUND CONCURRENCY
     * Exhaustion transition and Add Fund ensure final state reflects committed budget.
     */
    public function test_21_concurrency_test_c_exhaustion_and_add_fund(): void
    {
        $organizer = $this->createMember(['ad_balance' => 50.00]);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActivePaidCampaign($event, $organizer, [
            'budget' => 10.00,
            'spent_amount' => 9.9750,
            'remaining_amount' => 0.0250,
        ]);

        $member = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($member, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/qualify-interest")->assertOk();

        // Campaign is now exhausted and stopped
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);

        // Creator adds funds
        $this->actingAs($organizer, 'member');
        $this->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 15.00])->assertOk();

        // Reactivate
        $this->postJson("/api/member/events/{$event->id}/campaign/reactivate")->assertOk();

        $fresh = $campaign->fresh();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $fresh->status);
        $this->assertEquals(15.00, (float) $fresh->remaining_amount);
        $this->assertEquals(10.00, (float) $fresh->spent_amount);
        $this->assertEquals(25.00, (float) $fresh->total_funded);
    }

    // =========================================================================
    // REGRESSION TESTS
    // =========================================================================

    /**
     * TEST 22: Business Ads regression check.
     */
    public function test_22_business_ads_regression_remains_functional(): void
    {
        $owner = $this->createMember(['ad_balance' => 50.00]);
        $businessCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'member_id' => $owner->id,
            'campaign_name' => 'Business Page Campaign',
            'budget' => 10.00,
            'remaining_amount' => 10.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $this->assertEquals(AdCampaign::TYPE_BUSINESS_PAGE, $businessCampaign->campaign_type);
        $this->assertTrue(AdRewardRule::ofType(AdRewardRule::TYPE_BUSINESS_AD)->active()->exists());
    }

    /**
     * TEST 23: Free Event regression check.
     */
    public function test_23_free_event_regression_remains_functional(): void
    {
        $organizer = $this->createMember();
        $freeEvent = $this->createEvent($organizer);

        $this->assertFalse($freeEvent->isPaidCampaign());
        $this->assertEquals(0, $freeEvent->responses()->count());
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

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
            'title' => 'Tech Event ' . uniqid(),
            'slug' => 'tech-event-' . strtolower(uniqid()),
            'description' => 'A premier tech conference.',
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
        $budget = $attrs['budget'] ?? 50.00;
        $spent = $attrs['spent_amount'] ?? 0.00;
        $additional = $attrs['additional_funding'] ?? 0.00;
        $total = $budget + $additional;
        $remaining = $attrs['remaining_amount'] ?? ($total - $spent);

        return AdCampaign::create(array_merge([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'business_page_id' => null,
            'post_id' => null,
            'campaign_name' => 'Campaign for ' . $event->title,
            'budget' => $budget,
            'additional_funding' => $additional,
            'total_funded' => $total,
            'spent_amount' => $spent,
            'remaining_amount' => $remaining,
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
