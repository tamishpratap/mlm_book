<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdReward;
use App\Models\BusinessFollower;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignInterestLandingRewardFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function createVerifiedMember(string $prefix = 'verified', array $attributes = []): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create(array_merge([
            'name' => 'Verified ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'ad_balance' => 0.00,
            'reward_balance' => 0.00,
        ], $attributes));
    }

    protected function createUnverifiedMember(string $prefix = 'unverified', array $attributes = []): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create(array_merge([
            'name' => 'Unverified ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => null,
            'ad_balance' => 0.00,
            'reward_balance' => 0.00,
        ], $attributes));
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('page_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Biz ' . $unique,
            'page_username' => 'biz_' . $unique,
            'slug' => 'biz-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    protected function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        $unique = uniqid('camp_');
        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Sponsored post content ' . $unique,
            'visibility' => 'public',
        ]);

        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Landing Reward Campaign ' . $unique,
            'budget' => 30.00,
            'currency' => 'USD',
            'spent_amount' => 0.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attributes));
    }

    /**
     * TEST 1: Verified member clicks Interested -> records click event, returns target page with campaign context. Does NOT reward directly.
     */
    public function test_verified_member_expresses_interest_without_immediate_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $visitor = $this->createVerifiedMember('visitor_a', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest");

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'campaign_id' => $campaign->campaign_id,
                'business_page_slug' => $page->slug,
            ]);

        // Verify that NO financial reward was granted for Interested click alone
        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
        $this->assertEquals(30.00, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.00, (float) $visitor->fresh()->reward_balance);

        // Verify that Interested interaction was logged in ad_clicks
        $this->assertEquals(1, AdClick::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 2: Unverified member cannot express interest.
     */
    public function test_unverified_member_cannot_express_interest(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $unverified = $this->createUnverifiedMember('unverified_user');
        $this->actingAs($unverified, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest");

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
            ]);
    }

    /**
     * TEST 3: NO FOLLOW REQUIREMENT: Verified member qualifies via landing visit WITHOUT following the page -> +$0.05 Reward.
     */
    public function test_verified_member_qualifies_via_landing_visit_without_following_page(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);

        $visitor = $this->createVerifiedMember('visitor_nofollow', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // Confirm member does NOT follow the page
        $this->assertFalse(BusinessFollower::where('business_page_id', $page->id)->where('member_id', $visitor->id)->exists());

        // 1. Check Landing Reward Status
        $statusRes = $this->getJson("/api/member/ad-campaigns/{$campaign->campaign_id}/landing-reward-status");
        $statusRes->assertOk()
            ->assertJson([
                'success' => true,
                'is_verified' => true,
                'already_rewarded' => false,
                'eligible_to_earn' => true,
                'reward_amount_usd' => 0.05,
            ]);

        // 2. Qualify Landing Visit
        $earnRes = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => "visit_{$campaign->id}_{$visitor->id}_" . time(),
            'landing_page_url' => "https://example.com/member/business-pages/{$page->slug}?campaign={$campaign->campaign_id}",
        ]);

        $earnRes->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'member_reward_balance' => 0.05,
                'remaining_budget' => 29.95,
            ]);

        // Verify Campaign Budget deduction
        $campaign->refresh();
        $this->assertEquals(29.95, (float) $campaign->remaining_amount);
        $this->assertEquals(0.05, (float) $campaign->spent_amount);

        // Verify Member Reward Wallet credit
        $visitor->refresh();
        $this->assertEquals(0.05, (float) $visitor->reward_balance);

        // PROVE FOLLOW IS NOT REQUIRED: Member still does NOT follow the page
        $this->assertFalse(BusinessFollower::where('business_page_id', $page->id)->where('member_id', $visitor->id)->exists());

        // Verify Ledger record exists
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->where('status', 'credited')->count());
    }

    /**
     * TEST 4: Repeat landing visits on same campaign yield $0.00 additional reward.
     */
    public function test_repeat_landing_visit_on_same_campaign_yields_zero_additional_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);

        $visitor = $this->createVerifiedMember('visitor_repeat', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // First visit -> +$0.05
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_1',
        ])->assertOk();

        // Second visit -> $0.00
        $res2 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_2',
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'already_rewarded' => true,
                'reward_amount_usd' => 0.00,
            ]);

        // Campaign budget remains 29.95
        $this->assertEquals(29.95, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.05, (float) $visitor->fresh()->reward_balance);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 5: Member who already follows the page prior to the campaign receives one-time reward when qualifying landing visit.
     */
    public function test_pre_existing_follower_qualifies_for_one_time_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 20.00, 'remaining_amount' => 20.00]);

        $visitor = $this->createVerifiedMember('existing_follower', ['reward_balance' => 0.00]);

        // Historical follow created prior to campaign
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $visitor->id,
            'status' => 'accepted',
            'followed_at' => now()->subDays(5),
        ]);

        $this->actingAs($visitor, 'member');

        // Verified member qualifies landing visit
        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_follower',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'member_reward_balance' => 0.05,
            ]);

        $this->assertEquals(19.95, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.05, (float) $visitor->fresh()->reward_balance);
    }

    /**
     * TEST 6: Unverified member blocked from qualify-visit.
     */
    public function test_unverified_member_blocked_from_qualify_visit(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $unverified = $this->createUnverifiedMember('unverified_user');
        $this->actingAs($unverified, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_unverified',
        ]);

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
                'rewarded' => false,
            ]);

        $this->assertEquals(0, AdReward::where('member_id', $unverified->id)->count());
        $this->assertEquals(0.00, (float) $unverified->fresh()->reward_balance);
        $this->assertEquals(30.00, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * TEST 7: Exhausted campaign (< $0.05) cannot grant rewards and auto-stops.
     */
    public function test_exhausted_campaign_blocks_visit_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 0.05, 'remaining_amount' => 0.00, 'spent_amount' => 0.05]);

        $visitor = $this->createVerifiedMember('visitor_exhausted');
        $this->actingAs($visitor, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_exhausted',
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ]);

        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 8: Same member can earn on different campaigns.
     */
    public function test_same_member_can_earn_on_different_campaigns(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page1 = $this->createBusinessPage($owner);
        $page2 = $this->createBusinessPage($owner);

        $campaign1 = $this->createActiveCampaign($page1, $owner, ['budget' => 10.00, 'remaining_amount' => 10.00]);
        $campaign2 = $this->createActiveCampaign($page2, $owner, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $visitor = $this->createVerifiedMember('multi_earner', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // Campaign 1 -> +$0.05
        $this->postJson("/api/member/ad-campaigns/{$campaign1->campaign_id}/qualify-visit", ['qualifying_event_id' => 'evt_c1'])->assertOk();
        $this->assertEquals(0.05, (float) $visitor->fresh()->reward_balance);

        // Campaign 2 -> +$0.05 (Total: $0.10)
        $this->postJson("/api/member/ad-campaigns/{$campaign2->campaign_id}/qualify-visit", ['qualifying_event_id' => 'evt_c2'])->assertOk();
        $this->assertEquals(0.10, (float) $visitor->fresh()->reward_balance);
    }

    /**
     * TEST 9: Different members can each earn once on the same campaign.
     */
    public function test_different_members_each_earn_once_on_same_campaign(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 10.00, 'remaining_amount' => 10.00]);

        $memberA = $this->createVerifiedMember('member_a', ['reward_balance' => 0.00]);
        $memberB = $this->createVerifiedMember('member_b', ['reward_balance' => 0.00]);

        // Member A earns
        $this->actingAs($memberA, 'member');
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", ['qualifying_event_id' => 'evt_ma'])->assertOk();
        $this->assertEquals(0.05, (float) $memberA->fresh()->reward_balance);

        // Member B earns
        $this->actingAs($memberB, 'member');
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", ['qualifying_event_id' => 'evt_mb'])->assertOk();
        $this->assertEquals(0.05, (float) $memberB->fresh()->reward_balance);

        $this->assertEquals(9.90, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * TEST 10: Campaign feed returns member-specific already_rewarded flag.
     */
    public function test_feed_returns_member_specific_already_rewarded_flag(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $rewardedMember = $this->createVerifiedMember('rewarded_user');
        $unrewardedMember = $this->createVerifiedMember('unrewarded_user');

        // Reward member A
        $this->actingAs($rewardedMember, 'member');
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", ['qualifying_event_id' => 'evt_ra'])->assertOk();

        // Check feed for Rewarded Member
        $this->actingAs($rewardedMember, 'member');
        $feedRewarded = $this->getJson("/api/member/ad-campaigns/feed");
        $feedRewarded->assertOk();
        $itemA = collect($feedRewarded->json('campaigns'))->firstWhere('campaign_id', $campaign->campaign_id);
        $this->assertNotNull($itemA);
        $this->assertTrue($itemA['already_rewarded']);

        // Check feed for Unrewarded Member
        $this->actingAs($unrewardedMember, 'member');
        $feedUnrewarded = $this->getJson("/api/member/ad-campaigns/feed");
        $feedUnrewarded->assertOk();
        $itemB = collect($feedUnrewarded->json('campaigns'))->firstWhere('campaign_id', $campaign->campaign_id);
        $this->assertNotNull($itemB);
        $this->assertFalse($itemB['already_rewarded']);
    }

    /**
     * TEST 11: Campaign engagements API returns itemized events with Interested and Rewarded Visit action labels.
     */
    public function test_campaign_engagements_shows_interested_and_rewarded_visit_actions(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $visitor = $this->createVerifiedMember('visitor_logged');
        $this->actingAs($visitor, 'member');

        // 1. Click Interested
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest")->assertOk();

        // 2. Qualify Landing Visit
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", ['qualifying_event_id' => 'evt_v'])->assertOk();

        // Owner views engagements
        $this->actingAs($owner, 'member');
        $engRes = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $engRes->assertOk();
        $items = collect($engRes->json('engagements.data'));

        // Check that Rewarded Visit action exists with $0.05 reward
        $rewardItem = $items->firstWhere('action_label', 'Rewarded Visit');
        $this->assertNotNull($rewardItem);
        $this->assertEquals(0.05, (float) $rewardItem['reward_amount_usd']);

        // Check that Interested action exists with $0.00 reward
        $interestedItem = $items->firstWhere('action_label', 'Interested');
        $this->assertNotNull($interestedItem);
        $this->assertEquals(0.00, (float) $interestedItem['reward_amount_usd']);
    }
}