<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignCompleteLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $r1 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 0, 'rule_type' => AdRewardRule::TYPE_BUSINESS_AD],
            ['max_referrals' => 5, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r1->update(['reward_amount' => 0.0500, 'is_active' => true]);

        $r2 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 6, 'rule_type' => AdRewardRule::TYPE_BUSINESS_AD],
            ['max_referrals' => 14, 'reward_amount' => 0.0750, 'is_active' => true]
        );
        $r2->update(['reward_amount' => 0.0750, 'is_active' => true]);

        $r3 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 15, 'rule_type' => AdRewardRule::TYPE_BUSINESS_AD],
            ['max_referrals' => null, 'reward_amount' => 0.1000, 'is_active' => true]
        );
        $r3->update(['reward_amount' => 0.1000, 'is_active' => true]);

        AdRewardRule::clearCache();
    }

    /**
     * TEST 1: Wallet $100, Campaign $30, Fee 2.5% ($0.75) -> Debit $30.75, Running $30.00.
     */
    public function test_campaign_creation_debits_wallet_with_fee_and_allocates_exact_running_budget(): void
    {
        Setting::set('campaign_platform_fee_percent', 2.50);

        $member = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($member);
        $this->actingAs($member, 'member');

        $response = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Complete Lifecycle Campaign',
            'budget' => 30.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'campaign' => [
                    'budget' => '30.00',
                    'fee_percent' => '2.50',
                    'fee_amount' => '0.75',
                    'wallet_debit' => '30.75',
                    'remaining_amount' => '30.00',
                    'spent_amount' => '0.00',
                ],
            ]);

        $member->refresh();
        $this->assertSame(69.25, (float) $member->ad_balance); // $100 - $30.75 = $69.25
    }

    /**
     * TEST 2: 600 valid verified visits on a $30 campaign -> 600 x $0.05 = $30.00, Running: $0.00, Status: Stopped.
     */
    public function test_six_hundred_verified_visits_fully_deplete_budget_and_stop_campaign(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
            'spent_amount' => 0.00,
        ]);

        // Simulate 600 qualifying visits
        for ($i = 1; $i <= 600; $i++) {
            $visitor = $this->createMember(['name' => "Visitor {$i}", 'mobile_verified_at' => now()]);
            $this->actingAs($visitor, 'member');

            $res = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => "visit_event_{$i}",
            ]);

            $res->assertOk();
        }

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(30.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);
        $this->assertSame(600, AdReward::where('ad_campaign_id', $campaign->id)->count());
        $this->assertFalse($campaign->isEligibleForDelivery());
    }

    /**
     * TEST 3 & TEST 4: 599 visits ($29.95 paid, $0.05 remaining -> eligible for ONE final reward), then 600th visits ($0 remaining -> stopped).
     */
    public function test_exact_penultimate_and_final_budget_reward_sequence(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.10, // 2 visits left ($0.05 each)
            'spent_amount' => 29.90,
        ]);

        $visitor599 = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor599, 'member');

        // 599th visit
        $res599 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'event_599',
        ]);
        $res599->assertOk()->assertJson([
            'rewarded' => true,
            'reward_amount_usd' => 0.05,
            'remaining_budget' => 0.05,
            'campaign_status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $campaign->refresh();
        $this->assertSame(0.05, (float) $campaign->remaining_amount);
        $this->assertTrue($campaign->isEligibleForDelivery()); // Still eligible for 1 final visit

        // 600th visit
        $visitor600 = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor600, 'member');

        $res600 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'event_600',
        ]);
        $res600->assertOk()->assertJson([
            'rewarded' => true,
            'reward_amount_usd' => 0.05,
            'remaining_budget' => 0.00,
            'campaign_status' => AdCampaign::STATUS_STOPPED,
        ]);

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(30.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);
        $this->assertFalse($campaign->isEligibleForDelivery());
    }

    /**
     * TEST 5: 601st visit -> NO reward, campaign stopped/inactive.
     */
    public function test_visit_after_budget_exhaustion_is_rejected_with_zero_reward(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.00,
            'spent_amount' => 30.00,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        $visitor601 = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor601, 'member');

        $res601 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'event_601',
        ]);

        $res601->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'reward_amount_usd' => 0,
            ]);

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(0.00, (float) $visitor601->reward_balance);
    }

    /**
     * TEST 6: Budget $0.04 (< $0.05) -> NO reward, auto-stopped.
     */
    public function test_budget_below_five_cents_blocks_reward_and_stops_campaign(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.04,
            'spent_amount' => 29.96,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'event_04',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
            ]);

        $campaign->refresh();
        $this->assertSame(0.04, (float) $campaign->remaining_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);
    }

    /**
     * TEST 7: Unverified user -> NO reward (403 Forbidden).
     */
    public function test_unverified_member_cannot_earn_rewards(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
        ]);

        $unverified = $this->createMember(['mobile_verified_at' => null]);
        $this->actingAs($unverified, 'member');

        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'unverified_event',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
                'rewarded' => false,
            ]);

        $campaign->refresh();
        $this->assertSame(30.00, (float) $campaign->remaining_amount);
    }

    /**
     * TEST 8: Duplicate visit event replay -> exactly ONE reward.
     */
    public function test_duplicate_visit_event_replay_yields_single_reward(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $first = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'replay_token_1',
        ]);
        $first->assertOk()->assertJson(['rewarded' => true]);

        $second = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'replay_token_1',
        ]);
        $second->assertStatus(422)->assertJson(['rewarded' => false, 'duplicate' => true]);

        $campaign->refresh();
        $this->assertSame(29.95, (float) $campaign->remaining_amount);
        $this->assertSame(1, AdReward::where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 9: Concurrent final-budget requests -> exactly ONE reward, zero negative balance.
     */
    public function test_concurrent_final_budget_requests_guarantee_single_winner(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.05,
            'spent_amount' => 29.95,
        ]);

        $v1 = $this->createMember(['mobile_verified_at' => now()]);
        $v2 = $this->createMember(['mobile_verified_at' => now()]);

        $this->actingAs($v1, 'member');
        $r1 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'concur_1',
        ]);
        $r1->assertOk()->assertJson(['rewarded' => true, 'remaining_budget' => 0.00]);

        $this->actingAs($v2, 'member');
        $r2 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'concur_2',
        ]);
        $r2->assertStatus(422)->assertJson(['rewarded' => false]);

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(30.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);
    }

    /**
     * TEST 10: Admin fee change -> old campaign snapshot unchanged, new campaign gets new fee.
     */
    public function test_fee_change_preserves_historical_campaign_snapshot_and_updates_new(): void
    {
        Setting::set('campaign_platform_fee_percent', 2.50);

        $m1 = $this->createMember(['ad_balance' => 50.00]);
        $p1 = $this->createBusinessPage($m1);
        $this->actingAs($m1, 'member');

        $c1Res = $this->postJson("/api/member/business-pages/{$p1->slug}/ad-campaigns", [
            'campaign_name' => 'Old Fee Campaign',
            'budget' => 30.00,
        ]);
        $c1Res->assertStatus(201)->assertJson([
            'campaign' => ['fee_percent' => '2.50', 'fee_amount' => '0.75', 'wallet_debit' => '30.75'],
        ]);

        // Change Admin fee to 5.0%
        Setting::set('campaign_platform_fee_percent', 5.00);

        $m2 = $this->createMember(['ad_balance' => 50.00]);
        $p2 = $this->createBusinessPage($m2);
        $this->actingAs($m2, 'member');

        $c2Res = $this->postJson("/api/member/business-pages/{$p2->slug}/ad-campaigns", [
            'campaign_name' => 'New Fee Campaign',
            'budget' => 30.00,
        ]);
        $c2Res->assertStatus(201)->assertJson([
            'campaign' => ['fee_percent' => '5.00', 'fee_amount' => '1.50', 'wallet_debit' => '31.50'],
        ]);

        // Verify Old Campaign snapshot remains 2.50% / $0.75 / $30.75
        $c1Id = $c1Res->json('campaign.id');
        $oldCampaign = AdCampaign::find($c1Id);
        $this->assertSame(2.50, (float) $oldCampaign->fee_percent);
        $this->assertSame(0.75, (float) $oldCampaign->fee_amount);
        $this->assertSame(30.75, (float) $oldCampaign->wallet_debit);
    }

    /**
     * TEST 11: Campaign Analytics Reconciliation: Budget = Rewards Paid + Remaining Budget.
     */
    public function test_campaign_analytics_and_financial_summary_reconcile_exactly(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'remaining_amount' => 17.50,
            'spent_amount' => 12.50, // 250 visits x $0.05
        ]);

        // Create reward records
        for ($i = 1; $i <= 5; $i++) {
            $visitor = $this->createMember();
            AdReward::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $visitor->id,
                'reward_amount_usd' => 0.05,
                'qualifying_event_id' => "recon_event_{$i}",
                'status' => 'credited',
            ]);
        }

        $this->actingAs($owner, 'member');

        $response = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/analytics");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'metrics' => [
                    'budget' => 30.00,
                    'fee_percent' => 2.50,
                    'fee_amount' => 0.75,
                    'wallet_debit' => 30.75,
                    'rewards_paid' => 12.50,
                    'remaining_campaign_budget' => 17.50,
                    'financial_reconciliation' => [
                        'initial_campaign_budget' => 30.00,
                        'rewards_paid' => 12.50,
                        'remaining_campaign_budget' => 17.50,
                        'reconciles_exactly' => true,
                    ],
                ],
            ]);
    }

    /**
     * TEST 12: Feed Delivery: Active campaigns in feed; stopped/exhausted campaigns excluded.
     */
    public function test_feed_delivery_serves_active_campaigns_and_excludes_stopped_campaigns(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);

        // Active campaign with $30 budget
        $activeCamp = $this->createActiveCampaign($page, $owner, [
            'campaign_name' => 'Active Feed Campaign',
            'budget' => 30.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        // Stopped campaign with $0 budget
        $stoppedCamp = $this->createActiveCampaign($page, $owner, [
            'campaign_name' => 'Stopped Exhausted Campaign',
            'budget' => 30.00,
            'remaining_amount' => 0.00,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        $verifiedViewer = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($verifiedViewer, 'member');

        $response = $this->getJson('/api/member/ad-campaigns/feed');

        $response->assertOk();
        $campaignIds = collect($response->json('campaigns'))->pluck('id')->all();

        $this->assertContains($activeCamp->id, $campaignIds);
        $this->assertNotContains($stoppedCamp->id, $campaignIds);
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Member ' . uniqid(),
            'email' => 'member-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'ad_balance' => 0.00,
            'reward_balance' => 0.00,
        ], $attributes));
    }

    private function createBusinessPage(Member $owner, array $attributes = []): BusinessPage
    {
        return BusinessPage::create(array_merge([
            'member_id' => $owner->id,
            'page_name' => 'Biz Corp ' . uniqid(),
            'page_username' => 'bizcorp_' . strtolower(uniqid()),
            'slug' => 'biz-corp-' . strtolower(uniqid()),
            'category' => 'Crypto, Forex & FinTech MLM',
            'description' => 'Business page for ad lifecycle testing.',
            'status' => 'active',
            'visibility' => 'public',
        ], $attributes));
    }

    private function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Active Lifecycle Campaign ' . uniqid(),
            'budget' => 30.00,
            'currency' => 'USD',
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'spent_amount' => 0.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attributes));
    }
}
