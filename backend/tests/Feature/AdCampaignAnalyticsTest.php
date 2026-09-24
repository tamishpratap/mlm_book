<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Tests\TestCase;

class AdCampaignAnalyticsTest extends TestCase
{
    protected function tearDown(): void
    {
        AdClick::query()->where('placement', 'social_feed')->delete();
        AdImpression::query()->where('placement', 'social_feed')->delete();
        AdCampaign::query()->where('campaign_name', 'LIKE', '%Analytics%')->orWhere('campaign_name', 'LIKE', '%Test%')->delete();
        Post::query()->where('body', 'LIKE', '%Analytics post content%')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'Biz analytics_%')->delete();
        Member::query()->where('email', 'LIKE', '%_analytics@test.com')->orWhere('email', 'LIKE', 'owner_%@test.com')->orWhere('email', 'LIKE', 'stranger_%@test.com')->delete();

        parent::tearDown();
    }

    protected function createVerifiedMember(string $prefix = 'member'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_analytics@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('analytics_');
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
            'body' => 'Analytics post content for ' . $page->page_name,
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Click recording and deduplication.
     */
    public function test_click_recording_and_deduplication(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Analytics Click Test',
            'budget' => 500.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $clickKey = 'clk_test_' . uniqid();

        // 1st click call -> success
        $res1 = $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/click", [
            'click_key' => $clickKey,
            'placement' => 'social_feed',
        ]);
        $res1->assertStatus(200);
        $this->assertTrue($res1->json('success'));

        $this->assertEquals(1, AdClick::where('ad_campaign_id', $campaign->id)->count());

        // 2nd click call with same key -> deduplicated (ignored)
        $res2 = $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/click", [
            'click_key' => $clickKey,
            'placement' => 'social_feed',
        ]);
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('deduplicated'));

        // Still exactly 1 row
        $this->assertEquals(1, AdClick::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * 2. Owner can view own page and campaign analytics.
     */
    public function test_owner_can_view_own_page_and_campaign_analytics(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Owner Analytics Test',
            'budget' => 1000.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 1000.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Add 10 impressions and 2 clicks
        for ($i = 0; $i < 10; $i++) {
            AdImpression::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $owner->id,
                'post_id' => $post->id,
                'placement' => 'social_feed',
                'created_at' => now(),
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            AdClick::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $owner->id,
                'post_id' => $post->id,
                'placement' => 'social_feed',
                'created_at' => now(),
            ]);
        }

        // Page-level analytics
        $resPage = $this->actingAs($owner, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/analytics");
        $resPage->assertStatus(200);
        $this->assertTrue($resPage->json('success'));
        $this->assertEquals(10, $resPage->json('metrics.total_impressions'));
        $this->assertEquals(2, $resPage->json('metrics.total_clicks'));
        $this->assertEquals(20.00, $resPage->json('metrics.average_ctr'));

        // Single campaign analytics
        $resCamp = $this->actingAs($owner, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/analytics");
        $resCamp->assertStatus(200);
        $this->assertTrue($resCamp->json('success'));
        $this->assertEquals(10, $resCamp->json('metrics.impressions_count'));
        $this->assertEquals(2, $resCamp->json('metrics.clicks_count'));
        $this->assertEquals(20.00, $resCamp->json('metrics.ctr'));
    }

    /**
     * 3. Unauthorized member cannot access another page's analytics.
     */
    public function test_unauthorized_member_cannot_view_another_page_analytics(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $stranger = $this->createVerifiedMember('stranger');
        $page = $this->createBusinessPage($owner);

        $response = $this->actingAs($stranger, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/analytics");
        $response->assertStatus(403);
    }

    /**
     * 4. Admin can view platform-wide advertising analytics.
     */
    public function test_admin_can_view_platform_wide_advertising_analytics(): void
    {
        $admin = Admin::first();
        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Admin Test',
                'email' => 'admin_test@cpanel.com',
                'password' => bcrypt('123456'),
            ]);
        }

        $resAdmin = $this->actingAs($admin, 'admin')->getJson('/api/admin/ad-campaigns/analytics');
        $resAdmin->assertStatus(200);
        $this->assertTrue($resAdmin->json('success'));
        $this->assertArrayHasKey('total_campaigns', $resAdmin->json('metrics'));
        $this->assertArrayHasKey('total_impressions', $resAdmin->json('metrics'));
        $this->assertArrayHasKey('total_clicks', $resAdmin->json('metrics'));
        $this->assertArrayHasKey('average_ctr', $resAdmin->json('metrics'));
    }

    /**
     * 5. CTR calculation and division-by-zero protection.
     */
    public function test_ctr_calculation_and_division_by_zero_protection(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'CTR Zero Protection Test',
            'budget' => 500.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // 0 impressions -> CTR must be 0.00
        $this->assertEquals(0.00, $campaign->ctr);

        // Add 50 impressions and 5 clicks -> CTR must be 10.00%
        for ($i = 0; $i < 50; $i++) {
            AdImpression::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $owner->id,
                'post_id' => $post->id,
                'placement' => 'social_feed',
                'created_at' => now(),
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            AdClick::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $owner->id,
                'post_id' => $post->id,
                'placement' => 'social_feed',
                'created_at' => now(),
            ]);
        }

        $this->assertEquals(10.00, $campaign->ctr);
    }
}
