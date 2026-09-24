<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignDeliveryTest extends TestCase
{
    use RefreshDatabase;
    protected function tearDown(): void
    {
        AdImpression::query()->where('placement', 'social_feed')->delete();
        AdCampaign::query()->where('campaign_name', 'LIKE', '%Test%')->orWhere('campaign_name', 'LIKE', '%Camp%')->delete();
        Post::query()->where('body', 'LIKE', '%Promoted post content%')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'Biz page_%')->delete();
        Member::query()->where('email', 'LIKE', '%_@test.com')->orWhere('email', 'LIKE', 'owner_%@test.com')->orWhere('email', 'LIKE', 'viewer_%@test.com')->orWhere('email', 'LIKE', 'stranger_%@test.com')->delete();

        parent::tearDown();
    }

    protected function createVerifiedMember(string $prefix = 'member'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
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

    protected function createPagePost(BusinessPage $page, Member $author): Post
    {
        return Post::create([
            'member_id' => $author->id,
            'business_page_id' => $page->id,
            'body' => 'Promoted post content for ' . $page->page_name,
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Active approved ad is delivered into Social Feed as a Sponsored post.
     */
    public function test_approved_active_ad_is_delivered_into_social_feed_as_sponsored(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Sponsored Delivery Test',
            'budget' => 500.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 500.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertNotEmpty($posts);

        $sponsoredPosts = collect($posts)->where('is_sponsored', true);
        $this->assertNotEmpty($sponsoredPosts, 'Sponsored ad should appear in Social Feed');

        $ad = $sponsoredPosts->first();
        $this->assertEquals($campaign->campaign_id, $ad['ad_campaign_id']);
        $this->assertEquals('sponsored_ad', $ad['type']);
    }

    /**
     * 2. Unapproved, paused, rejected, or stopped ads are NOT delivered into feed.
     */
    public function test_unapproved_or_paused_or_stopped_ads_are_not_delivered_into_feed(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Draft Camp',
            'budget' => 100.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Pending Camp',
            'budget' => 100.00,
            'status' => AdCampaign::STATUS_PENDING_REVIEW,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Paused Camp',
            'budget' => 100.00,
            'status' => AdCampaign::STATUS_PAUSED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Rejected Camp',
            'budget' => 100.00,
            'status' => AdCampaign::STATUS_REJECTED,
            'approval_status' => AdCampaign::APPROVAL_REJECTED,
            'rejection_reason' => 'Policy violation',
        ]);

        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Stopped Camp',
            'budget' => 100.00,
            'status' => AdCampaign::STATUS_STOPPED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $sponsoredPosts = collect($posts)->where('is_sponsored', true);
        $this->assertEmpty($sponsoredPosts, 'No unapproved, paused, rejected, or stopped ads should appear in feed');
    }

    /**
     * 3. Broad platform visibility: Non-followers and non-connections receive eligible ads.
     */
    public function test_broad_platform_visibility_non_followers_and_non_connections_receive_ads(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $stranger = $this->createVerifiedMember('stranger');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Broad Audience Test',
            'budget' => 300.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Stranger has no friends, no connections, no follows
        $response = $this->actingAs($stranger, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $sponsored = collect($posts)->where('is_sponsored', true)->first();
        $this->assertNotNull($sponsored, 'Stranger should receive broad platform sponsored ad');
        $this->assertEquals($campaign->campaign_id, $sponsored['ad_campaign_id']);
    }

    /**
     * 4. Ads with future start date or exhausted budget are NOT delivered.
     */
    public function test_expired_by_date_or_budget_exhausted_ads_are_not_delivered(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);

        // Future start date
        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Future Camp',
            'budget' => 200.00,
            'currency' => 'USD',
            'start_at' => now()->addDays(5),
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Exhausted budget
        AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $this->createPagePost($page, $owner)->id,
            'campaign_name' => 'Exhausted Camp',
            'budget' => 200.00,
            'currency' => 'USD',
            'spent_amount' => 200.00,
            'remaining_amount' => 0.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $sponsoredPosts = collect($posts)->where('is_sponsored', true);
        $this->assertEmpty($sponsoredPosts, 'Future start and budget exhausted ads must not appear in feed');
    }

    /**
     * 5. Impression tracking endpoint and deduplication.
     */
    public function test_ad_impression_recording_and_deduplication(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Impression Metric Test',
            'budget' => 500.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $impressionKey = 'imp_test_' . uniqid();

        // 1st impression call -> success
        $res1 = $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/impression", [
            'impression_key' => $impressionKey,
            'placement' => 'social_feed',
        ]);
        $res1->assertStatus(200);
        $this->assertTrue($res1->json('success'));

        $this->assertEquals(1, AdImpression::where('ad_campaign_id', $campaign->id)->count());

        // 2nd impression call with same key -> deduplicated (ignored)
        $res2 = $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/impression", [
            'impression_key' => $impressionKey,
            'placement' => 'social_feed',
        ]);
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('deduplicated'));

        // Still exactly 1 row
        $this->assertEquals(1, AdImpression::where('ad_campaign_id', $campaign->id)->count());
    }
}
