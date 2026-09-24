<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedMemberAdVisibilityGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdRewardRule::query()->delete();
        AdRewardRule::clearCache();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();
    }

    protected function createVerifiedMember(string $prefix = 'verified'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'Verified User ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
        ]);
    }

    protected function createUnverifiedMember(string $prefix = 'unverified'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'Unverified User ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => null,
            'reward_balance' => 0.00,
        ]);
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

    protected function createPagePost(BusinessPage $page, Member $author, string $body = 'Post Content'): Post
    {
        return Post::create([
            'member_id' => $author->id,
            'business_page_id' => $page->id,
            'body' => $body,
            'visibility' => 'public',
        ]);
    }

    /**
     * TEST 1: Unverified member sees sponsored ads in social feed alongside organic content.
     */
    public function test_unverified_member_sees_sponsored_ads_in_feed(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $unverifiedViewer = $this->createUnverifiedMember('viewer_unverified');
        $page = $this->createBusinessPage($owner);
        $adPost = $this->createPagePost($page, $owner, 'Promoted sponsored ad post');

        // Create active approved ad campaign
        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $adPost->id,
            'campaign_name' => 'Visibility Gate Test Ad',
            'budget' => 100.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 100.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Unverified member requests socials feed
        $response = $this->actingAs($unverifiedViewer, 'member')->getJson('/api/member/socials');
        $response->assertOk();

        $posts = $response->json('posts');
        $sponsoredInFeed = collect($posts)->where('is_sponsored', true);
        $this->assertNotEmpty($sponsoredInFeed, 'Unverified member MUST be able to see sponsored ads in feed.');
        $this->assertEquals($campaign->campaign_id, $sponsoredInFeed->first()['ad_campaign_id']);
    }

    /**
     * TEST 2: Verified member sees sponsored ads in social feed.
     */
    public function test_verified_member_sees_sponsored_ads_in_feed(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $verifiedViewer = $this->createVerifiedMember('viewer_verified');
        $page = $this->createBusinessPage($owner);
        $adPost = $this->createPagePost($page, $owner, 'Promoted sponsored ad post');

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $adPost->id,
            'campaign_name' => 'Visibility Gate Verified Test Ad',
            'budget' => 100.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 100.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($verifiedViewer, 'member')->getJson('/api/member/socials');
        $response->assertOk();

        $posts = $response->json('posts');
        $sponsoredInFeed = collect($posts)->where('is_sponsored', true);
        $this->assertNotEmpty($sponsoredInFeed, 'Verified member must receive sponsored ads in feed.');
        $this->assertEquals($campaign->campaign_id, $sponsoredInFeed->first()['ad_campaign_id']);
    }

    /**
     * TEST 3: Direct API access to ad-campaigns/feed is allowed for unverified members.
     */
    public function test_direct_ad_feed_api_is_allowed_for_unverified_member(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $unverifiedMember = $this->createUnverifiedMember('unverified_api');
        $page = $this->createBusinessPage($owner);
        $adPost = $this->createPagePost($page, $owner, 'Promoted sponsored ad post');

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $adPost->id,
            'campaign_name' => 'Feed Test Ad',
            'budget' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/ad-campaigns/feed');
        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
        $campaigns = $response->json('campaigns');
        $this->assertNotEmpty($campaigns, 'Unverified member should be able to view campaigns in feed endpoint.');
    }

    /**
     * TEST 4: Direct API access to ad-campaigns/feed is allowed for verified members.
     */
    public function test_direct_ad_feed_api_is_allowed_for_verified_member(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $verifiedMember = $this->createVerifiedMember('verified_api');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'API Feed Test Ad',
            'budget' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($verifiedMember, 'member')->getJson('/api/member/ad-campaigns/feed');
        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $campaigns = $response->json('campaigns');
        $this->assertNotEmpty($campaigns);
    }

    /**
     * TEST 5: Impression recording is blocked (403) for unverified member.
     */
    public function test_ad_impression_is_blocked_for_unverified_member(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $unverifiedMember = $this->createUnverifiedMember('unverified_imp');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Impression Gate Test',
            'budget' => 50.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($unverifiedMember, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/impression", [
            'impression_key' => 'imp_' . uniqid(),
            'placement' => 'social_feed',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
            ]);

        $this->assertEquals(0, AdImpression::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 6: Ad click is blocked (403) for unverified member.
     */
    public function test_ad_click_is_blocked_for_unverified_member(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $unverifiedMember = $this->createUnverifiedMember('unverified_clk');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Click Gate Test',
            'budget' => 50.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($unverifiedMember, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/click", [
            'click_key' => 'clk_' . uniqid(),
            'placement' => 'social_feed',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);

        $this->assertEquals(0, AdClick::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 7: Landing-page visit reward ($0.05) is blocked (403) for unverified member.
     */
    public function test_qualifying_reward_is_blocked_for_unverified_member(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $unverifiedMember = $this->createUnverifiedMember('unverified_reward');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Reward Gate Test',
            'budget' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($unverifiedMember, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_' . uniqid(),
            'landing_page_url' => 'https://example.com/promo',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ]);

        // Ensure no reward recorded
        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->count());

        // Ensure budget untouched
        $campaign->refresh();
        $this->assertEquals(50.00, (float) $campaign->remaining_amount);
        $this->assertEquals(0.00, (float) $campaign->spent_amount);

        // Ensure member reward balance untouched
        $unverifiedMember->refresh();
        $this->assertEquals(0.00, (float) $unverifiedMember->reward_balance);
    }

    /**
     * TEST 8: Verified member can record click and qualify visit for $0.05 reward.
     */
    public function test_verified_member_can_click_and_qualify_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $verifiedMember = $this->createVerifiedMember('verified_reward');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Verified Reward Test',
            'budget' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // 1. Click
        $clickRes = $this->actingAs($verifiedMember, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/click", [
            'click_key' => 'clk_' . uniqid(),
            'placement' => 'social_feed',
        ]);
        $clickRes->assertOk();
        $this->assertEquals(1, AdClick::where('ad_campaign_id', $campaign->id)->count());

        // 2. Qualify visit
        $visitRes = $this->actingAs($verifiedMember, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_' . uniqid(),
            'landing_page_url' => 'https://example.com/promo',
        ]);
        $visitRes->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'remaining_budget' => 49.95,
                'spent_budget' => 0.05,
            ]);

        $verifiedMember->refresh();
        $this->assertEquals(0.05, (float) $verifiedMember->reward_balance);
    }
}
