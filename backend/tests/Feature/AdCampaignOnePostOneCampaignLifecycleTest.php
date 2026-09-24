<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use App\Models\Setting;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignOnePostOneCampaignLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Post A already has Campaign A. Attempting to create a second campaign for Post A is blocked.
     */
    public function test_cannot_create_duplicate_campaign_for_same_post(): void
    {
        $owner = $this->createMember(['ad_balance' => 500.00]);
        $page = $this->createBusinessPage($owner);
        $postA = $this->createPost($page, $owner);

        // First campaign for Post A
        $this->actingAs($owner, 'member');
        $res1 = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'First Campaign for Post A',
            'post_id' => $postA->id,
            'budget' => 20.00,
        ]);
        $res1->assertStatus(201);
        $this->assertDatabaseHas('ad_campaigns', ['post_id' => $postA->id, 'campaign_name' => 'First Campaign for Post A']);

        // Attempt second campaign for Post A -> Must be BLOCKED
        $res2 = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Duplicate Campaign for Post A',
            'post_id' => $postA->id,
            'budget' => 15.00,
        ]);
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['post_id']);
        
        $this->assertCount(1, AdCampaign::where('post_id', $postA->id)->get());
    }

    /**
     * TEST 2: Different posts can each have their own campaign.
     */
    public function test_can_create_campaigns_for_different_posts(): void
    {
        $owner = $this->createMember(['ad_balance' => 500.00]);
        $page = $this->createBusinessPage($owner);
        $postA = $this->createPost($page, $owner);
        $postB = $this->createPost($page, $owner);

        $this->actingAs($owner, 'member');

        $resA = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Campaign for Post A',
            'post_id' => $postA->id,
            'budget' => 10.00,
        ]);
        $resA->assertStatus(201);

        $resB = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Campaign for Post B',
            'post_id' => $postB->id,
            'budget' => 15.00,
        ]);
        $resB->assertStatus(201);

        $this->assertSame(2, AdCampaign::count());
    }

    /**
     * TEST 3: Check post campaign endpoint returns accurate campaign existence data.
     */
    public function test_check_post_ad_campaign_endpoint(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $post = $this->createPost($page, $owner);

        $this->actingAs($owner, 'member');

        // Before campaign exists
        $res1 = $this->getJson("/api/member/business-pages/{$page->slug}/posts/{$post->id}/ad-campaign");
        $res1->assertOk()->assertJson(['success' => true, 'has_campaign' => false]);

        // Create campaign
        $campaign = $this->createActiveCampaign($page, $owner, ['post_id' => $post->id]);

        // After campaign exists
        $res2 = $this->getJson("/api/member/business-pages/{$page->slug}/posts/{$post->id}/ad-campaign");
        $res2->assertOk()->assertJson([
            'success' => true,
            'has_campaign' => true,
            'campaign' => [
                'id' => $campaign->id,
                'campaign_name' => $campaign->campaign_name,
            ],
        ]);
    }

    /**
     * TEST 4: When campaign budget depletes to 0, it stops and is removed from the active delivery feed.
     */
    public function test_budget_exhaustion_auto_stops_and_removes_from_feed(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $post = $this->createPost($page, $owner);

        $campaign = $this->createActiveCampaign($page, $owner, [
            'post_id' => $post->id,
            'budget' => 0.05,
            'remaining_amount' => 0.05,
            'spent_amount' => 0.00,
        ]);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        // Delivery service has campaign before exhaustion
        $deliveryService = app(AdDeliveryService::class);
        $feed = $deliveryService->getEligibleCampaigns(5, $visitor);
        $this->assertTrue($feed->contains('id', $campaign->id));

        // Qualify visit and consume last $0.05
        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'evt_' . uniqid(),
        ]);
        $res->assertOk()->assertJson([
            'success' => true,
            'rewarded' => true,
            'remaining_budget' => 0.00,
            'campaign_status' => AdCampaign::STATUS_STOPPED,
        ]);

        // Campaign is now stopped/exhausted
        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
        $this->assertTrue($campaign->is_budget_exhausted);

        // Feed no longer returns exhausted campaign
        $feedAfter = $deliveryService->getEligibleCampaigns(5, $visitor);
        $this->assertFalse($feedAfter->contains('id', $campaign->id));
    }

    /**
     * TEST 5: Owner can add funds to the SAME campaign, reusing campaign_id and post_id, and reactivate it.
     */
    public function test_owner_can_add_funds_to_exhausted_campaign_and_reactivate(): void
    {
        $owner = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($owner);
        $post = $this->createPost($page, $owner);

        // Exhausted campaign
        $campaign = $this->createActiveCampaign($page, $owner, [
            'post_id' => $post->id,
            'budget' => 5.00,
            'additional_funding' => 0.00,
            'total_funded' => 5.00,
            'remaining_amount' => 0.00,
            'spent_amount' => 5.00,
            'status' => AdCampaign::STATUS_STOPPED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $this->actingAs($owner, 'member');

        // Add $10.00 funds (with 2.5% fee = $0.25, total debit $10.25)
        $res = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/add-funds", [
            'amount' => 10.00,
        ]);

        $res->assertOk()->assertJson([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'post_id' => $post->id,
                'budget' => '5.00',
                'additional_funding' => '10.00',
                'total_funded' => '15.00',
                'remaining_amount' => '10.00',
                'status' => AdCampaign::STATUS_ACTIVE,
            ],
            'available_ad_funds' => 89.75, // 100 - 10.25
        ]);

        $campaign->refresh();
        $this->assertSame(5.00, (float) $campaign->budget);
        $this->assertSame(10.00, (float) $campaign->additional_funding);
        $this->assertSame(15.00, (float) $campaign->total_funded);
        $this->assertSame(10.00, (float) $campaign->remaining_amount);
        $this->assertSame(5.00, (float) $campaign->spent_amount);
        $this->assertSame(AdCampaign::STATUS_ACTIVE, $campaign->status);

        $owner->refresh();
        $this->assertSame(89.75, (float) $owner->ad_balance);
    }

    /**
     * TEST 6: Non-owner member cannot add funds to someone else's campaign.
     */
    public function test_non_owner_cannot_add_funds_to_campaign(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $otherMember = $this->createMember(['ad_balance' => 100.00]);
        $this->actingAs($otherMember, 'member');

        $res = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/add-funds", [
            'amount' => 10.00,
        ]);

        $res->assertStatus(403);
    }

    /**
     * TEST 7: Insufficient ad balance blocks top-up without wallet or campaign mutations.
     */
    public function test_insufficient_ad_balance_blocks_top_up(): void
    {
        $owner = $this->createMember(['ad_balance' => 5.00]);
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'spent_amount' => 30.00,
            'remaining_amount' => 0.00,
        ]);

        $this->actingAs($owner, 'member');

        $res = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/add-funds", [
            'amount' => 10.00, // Requires 10.25, only 5.00 available
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors(['amount']);

        $owner->refresh();
        $this->assertSame(5.00, (float) $owner->ad_balance);
        $campaign->refresh();
        $this->assertSame(0.00, (float) $campaign->remaining_amount);
    }

    /**
     * TEST 8: Old rewarded user CANNOT earn a second reward after campaign top-up.
     */
    public function test_old_rewarded_member_cannot_earn_again_after_top_up(): void
    {
        $owner = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($owner);
        $post = $this->createPost($page, $owner);

        $campaign = $this->createActiveCampaign($page, $owner, [
            'post_id' => $post->id,
            'budget' => 0.05,
            'remaining_amount' => 0.05,
            'spent_amount' => 0.00,
        ]);

        $userA = $this->createMember(['name' => 'User A', 'mobile_verified_at' => now()]);

        // User A earns first reward
        $this->actingAs($userA, 'member');
        $resA1 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'evt_a1',
        ]);
        $resA1->assertOk()->assertJson(['rewarded' => true, 'reward_amount_usd' => 0.05]);

        // Owner tops up campaign with $10
        $this->actingAs($owner, 'member');
        $topupRes = $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/add-funds", [
            'amount' => 10.00,
        ]);
        $topupRes->assertOk();

        // User A attempts to earn AGAIN on the topped-up campaign -> BLOCKED
        $this->actingAs($userA, 'member');
        $resA2 = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'evt_a2',
        ]);
        $resA2->assertStatus(422)->assertJson([
            'already_rewarded' => true,
            'rewarded' => false,
        ]);

        $this->assertSame(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $userA->id)->count());
        $userA->refresh();
        $this->assertSame(0.05, (float) $userA->reward_balance);
    }

    /**
     * TEST 9: New verified member CAN earn reward after campaign top-up.
     */
    public function test_new_verified_member_can_earn_reward_after_top_up(): void
    {
        $owner = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($owner);
        $post = $this->createPost($page, $owner);

        $campaign = $this->createActiveCampaign($page, $owner, [
            'post_id' => $post->id,
            'budget' => 0.05,
            'remaining_amount' => 0.00,
            'spent_amount' => 0.05,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        // Owner tops up
        $this->actingAs($owner, 'member');
        $this->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/add-funds", [
            'amount' => 10.00,
        ])->assertOk();

        // New User B (never rewarded)
        $userB = $this->createMember(['name' => 'User B', 'mobile_verified_at' => now()]);
        $this->actingAs($userB, 'member');

        $resB = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'evt_b1',
        ]);

        $resB->assertOk()->assertJson([
            'rewarded' => true,
            'reward_amount_usd' => 0.05,
            'remaining_budget' => 9.95,
        ]);

        $userB->refresh();
        $this->assertSame(0.05, (float) $userB->reward_balance);

        $campaign->refresh();
        $this->assertSame(9.95, (float) $campaign->remaining_amount);
        $this->assertSame(0.10, (float) $campaign->spent_amount);
    }

    /**
     * TEST 10: Budget Low threshold presentation and active feed retention.
     */
    public function test_budget_low_threshold_presentation_and_feed_retention(): void
    {
        Setting::set('ad_campaign_low_budget_threshold', 1.00);

        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $post = $this->createPost($page, $owner);

        $campaign = $this->createActiveCampaign($page, $owner, [
            'post_id' => $post->id,
            'budget' => 5.00,
            'remaining_amount' => 0.50, // <= $1.00 but >= $0.05
            'spent_amount' => 4.50,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $this->assertTrue($campaign->is_budget_low);
        $this->assertFalse($campaign->is_budget_exhausted);
        $this->assertSame('Budget Low', $campaign->display_status);

        // Still eligible for social feed delivery
        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $deliveryService = app(AdDeliveryService::class);
        $feed = $deliveryService->getEligibleCampaigns(5, $visitor);
        $this->assertTrue($feed->contains('id', $campaign->id));
    }

    /**
     * TEST 11: Budget below $0.05 cannot issue rewards and is budget exhausted.
     */
    public function test_budget_below_five_cents_cannot_reward(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'spent_amount' => 29.96,
            'remaining_amount' => 0.04,
        ]);

        $this->assertTrue($campaign->is_budget_exhausted);
        $this->assertSame('Budget Exhausted', $campaign->display_status);

        $visitor = $this->createMember(['mobile_verified_at' => now()]);
        $this->actingAs($visitor, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
            'qualifying_event_id' => 'evt_exhausted',
        ]);

        $res->assertStatus(422)->assertJson([
            'rewarded' => false,
            'exhausted' => true,
        ]);
    }

    /**
     * TEST 12: Financial reconciliation formula.
     * Total Funded = Original Budget + Additional Funding = Rewards Paid + Remaining Running Budget.
     */
    public function test_financial_reconciliation_formula(): void
    {
        $owner = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 5.00,
            'additional_funding' => 10.00,
            'spent_amount' => 3.00,
        ]);

        $campaign->refresh();
        $this->assertSame(15.00, (float) $campaign->total_funded);
        $this->assertSame(12.00, (float) $campaign->remaining_amount);
        $this->assertSame(15.00, (float) $campaign->spent_amount + (float) $campaign->remaining_amount);
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

    private function createPost(BusinessPage $page, Member $owner, array $attributes = []): Post
    {
        return Post::create(array_merge([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Exciting promotional content post #' . uniqid(),
            'type' => 'business',
            'status' => 'active',
        ], $attributes));
    }

    private function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Active Reward Campaign ' . uniqid(),
            'budget' => 30.00,
            'additional_funding' => 0.00,
            'total_funded' => 30.00,
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