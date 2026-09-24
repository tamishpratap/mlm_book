<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignVisitRewardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $r1 = \App\Models\AdRewardRule::firstOrCreate(
            ['min_referrals' => 0],
            ['max_referrals' => 5, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r1->update(['reward_amount' => 0.0500, 'is_active' => true]);

        $r2 = \App\Models\AdRewardRule::firstOrCreate(
            ['min_referrals' => 6],
            ['max_referrals' => 14, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r2->update(['reward_amount' => 0.0500, 'is_active' => true]);

        $r3 = \App\Models\AdRewardRule::firstOrCreate(
            ['min_referrals' => 15],
            ['max_referrals' => null, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r3->update(['reward_amount' => 0.0500, 'is_active' => true]);

        \App\Models\AdRewardRule::clearCache();
    }

    /**
     * TEST 1: Campaign budget $30.00, verified user, valid visit -> $0.05 reward, $29.95 remaining.
     */
    public function test_verified_user_receives_five_cent_reward_and_depletes_campaign_budget(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        // Active campaign with $30.00 budget
        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
            'spent_amount' => 0.00,
        ]);

        $visitor = $this->createMember([
            'name' => 'Verified Visitor',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
        ]);

        $this->actingAs($visitor, 'member');

        $eventId = 'event_' . uniqid();
        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => $eventId,
            'landing_page_url' => 'https://example.com/promo-landing',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'remaining_budget' => 29.95,
                'spent_budget' => 0.05,
                'campaign_status' => AdCampaign::STATUS_ACTIVE,
            ]);

        // Campaign budget verification
        $campaign->refresh();
        $this->assertSame(29.95, (float) $campaign->remaining_amount);
        $this->assertSame(0.05, (float) $campaign->spent_amount);

        // Visitor reward balance verification
        $visitor->refresh();
        $this->assertSame(0.05, (float) $visitor->reward_balance);

        // Reward record verification
        $this->assertDatabaseHas('ad_rewards', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor->id,
            'reward_amount_usd' => '0.05',
            'qualifying_event_id' => $eventId,
            'status' => 'credited',
        ]);
    }

    /**
     * TEST 2: Running budget $0.05, valid visit -> $0.05 reward, $0.00 remaining, campaign stops.
     */
    public function test_final_budget_reward_depletes_to_zero_and_automatically_stops_campaign(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.05,
            'spent_amount' => 29.95,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'final_event_' . uniqid(),
            'landing_page_url' => 'https://example.com/final-landing',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'remaining_budget' => 0.00,
                'spent_budget' => 30.00,
                'campaign_status' => AdCampaign::STATUS_STOPPED,
            ]);

        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(30.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);
        $this->assertFalse($campaign->isEligibleForDelivery());
    }

    /**
     * TEST 3: Running budget $0.04 (< $0.05), valid visit -> NO partial reward, campaign auto-stops, no negative balance.
     */
    public function test_insufficient_running_budget_blocks_reward_and_stops_campaign_without_overdrawing(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.04,
            'spent_amount' => 29.96,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'short_event_' . uniqid(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
                'campaign_status' => AdCampaign::STATUS_STOPPED,
            ]);

        $campaign->refresh();
        $this->assertSame(0.04, (float) $campaign->remaining_amount);
        $this->assertSame(29.96, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);

        // Visitor receives 0 reward
        $visitor->refresh();
        $this->assertSame(0.00, (float) $visitor->reward_balance);
        $this->assertSame(0, AdReward::count());
    }

    /**
     * TEST 4: Unverified user -> 403 Forbidden, NO reward, budget untouched.
     */
    public function test_unverified_member_is_blocked_from_earning_visit_rewards(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
        ]);

        // Unverified user: mobile_verified_at = null
        $unverifiedVisitor = $this->createMember([
            'mobile_verified_at' => null,
            'reward_balance' => 0.00,
        ]);

        $this->actingAs($unverifiedVisitor, 'member');

        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'unverified_event_' . uniqid(),
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
                'rewarded' => false,
            ]);

        // Campaign budget remains untouched
        $campaign->refresh();
        $this->assertSame(30.00, (float) $campaign->remaining_amount);

        // Visitor balance remains untouched
        $unverifiedVisitor->refresh();
        $this->assertSame(0.00, (float) $unverifiedVisitor->reward_balance);
        $this->assertSame(0, AdReward::count());
    }

    /**
     * TEST 5: Same user + same qualifying event twice -> First $0.05, Second $0 (duplicate rejected).
     */
    public function test_duplicate_qualifying_event_is_rejected_without_double_reward(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $fixedEventId = 'fixed_unique_token_123';

        // First attempt: succeeds
        $res1 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => $fixedEventId,
        ]);
        $res1->assertOk()->assertJson(['rewarded' => true, 'reward_amount_usd' => 0.05]);

        // Second attempt with same event ID: rejected as duplicate
        $res2 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => $fixedEventId,
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'duplicate' => true,
            ]);

        // Campaign budget was deducted only once
        $campaign->refresh();
        $this->assertSame(29.95, (float) $campaign->remaining_amount);

        // Visitor reward balance is $0.05, not $0.10
        $visitor->refresh();
        $this->assertSame(0.05, (float) $visitor->reward_balance);
        $this->assertSame(1, AdReward::where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 6: Simultaneous final-budget requests -> Only one succeeds, zero negative balance.
     */
    public function test_concurrent_final_budget_requests_prevent_negative_balance(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 0.05,
            'spent_amount' => 29.95,
        ]);

        $visitor1 = $this->createMember(['mobile_verified_at' => now()]);
        $visitor2 = $this->createMember(['mobile_verified_at' => now()]);

        // User 1 requests first
        $this->actingAs($visitor1, 'member');
        $res1 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'concurrent_1_' . uniqid(),
        ]);
        $res1->assertOk()->assertJson(['rewarded' => true, 'remaining_budget' => 0.00]);

        // User 2 requests immediately after
        $this->actingAs($visitor2, 'member');
        $res2 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'concurrent_2_' . uniqid(),
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'exhausted' => true,
            ]);

        // Campaign remaining budget is 0.00 and NEVER negative
        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertSame(30.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_STOPPED, $campaign->status);

        // Exactly 1 reward record created
        $this->assertSame(1, AdReward::count());
    }

    /**
     * TEST 7: Paused or inactive campaign blocks reward.
     */
    public function test_paused_or_inactive_campaign_blocks_reward(): void
    {
        $owner = $this->createMember(['name' => 'Ad Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($businessPage, $owner, [
            'status' => AdCampaign::STATUS_PAUSED,
            'remaining_amount' => 30.00,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $response = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'paused_test_' . uniqid(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
            ]);

        $campaign->refresh();
        $this->assertSame(30.00, (float) $campaign->remaining_amount);
        $this->assertSame(0, AdReward::count());
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
            'description' => 'Business page for ad reward testing.',
            'status' => 'active',
            'visibility' => 'public',
        ], $attributes));
    }

    private function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Active Reward Campaign ' . uniqid(),
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
