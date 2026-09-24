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

class AdCampaignEndToEndTest extends TestCase
{
    protected function tearDown(): void
    {
        AdClick::query()->where('placement', 'social_feed')->delete();
        AdImpression::query()->where('placement', 'social_feed')->delete();
        AdCampaign::query()->where('campaign_name', 'LIKE', '%E2E%')->orWhere('campaign_name', 'LIKE', '%Test%')->delete();
        Post::query()->where('body', 'LIKE', '%E2E post content%')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'Biz e2e_%')->delete();
        Member::query()->where('email', 'LIKE', '%_e2e@test.com')->orWhere('email', 'LIKE', 'owner_%@test.com')->orWhere('email', 'LIKE', 'stranger_%@test.com')->delete();

        parent::tearDown();
    }

    protected function createVerifiedMember(string $prefix = 'member'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_e2e@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('e2e_');
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
            'body' => 'E2E post content for ' . $page->page_name,
            'visibility' => 'public',
        ]);
    }

    protected function getOrCreateAdmin(): Admin
    {
        $admin = Admin::first();
        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Super Admin',
                'email' => 'pro@cpanel.com',
                'password' => bcrypt('123456'),
            ]);
        }
        return $admin;
    }

    /**
     * Complete End-to-End Advertising Flow:
     * Owner Create -> Submit -> Admin Pending -> Admin Approve -> Social Feed Delivery
     * -> Impression Track -> Click Track -> Metrics Check -> Pause -> Resume -> Stop
     */
    public function test_complete_end_to_end_ad_lifecycle(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $admin = $this->getOrCreateAdmin();

        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        // 1. Owner creates draft ad campaign
        $createRes = $this->actingAs($owner, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'E2E Complete Lifecycle Ad',
            'post_id' => $post->id,
            'budget' => 500.00,
            'currency' => 'USD',
            'start_at' => now()->toIso8601String(),
        ]);
        $createRes->assertStatus(201);
        $campaignData = $createRes->json('campaign');
        $campaignId = $campaignData['campaign_id'];

        $this->assertEquals(AdCampaign::STATUS_DRAFT, $campaignData['status']);
        $this->assertEquals(AdCampaign::APPROVAL_PENDING, $campaignData['approval_status']);
        $this->assertEquals('USD', $campaignData['currency']);

        // 2. Owner submits campaign for review
        $submitRes = $this->actingAs($owner, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}/submit");
        $submitRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_PENDING_REVIEW, $submitRes->json('campaign.status'));

        // 3. Verify pending ad is NOT served in member social feed
        $feedPending = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $feedPending->assertStatus(200);
        $sponsoredPending = collect($feedPending->json('posts'))->where('is_sponsored', true);
        $this->assertEmpty($sponsoredPending, 'Pending ad must not appear in feed');

        // 4. Admin reviews and approves campaign
        $approveRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-campaigns/{$campaignData['id']}/approve");
        $approveRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_APPROVED, $approveRes->json('campaign.status'));
        $this->assertEquals(AdCampaign::APPROVAL_APPROVED, $approveRes->json('campaign.approval_status'));

        // 5. Member accesses Social Feed -> Sponsored Ad IS delivered
        $feedActive = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $feedActive->assertStatus(200);
        $sponsoredActive = collect($feedActive->json('posts'))->where('is_sponsored', true)->first();
        $this->assertNotNull($sponsoredActive, 'Approved ad must appear in Social Feed');
        $this->assertEquals($campaignId, $sponsoredActive['ad_campaign_id']);

        // 6. Record real impression
        $impKey = 'imp_e2e_' . uniqid();
        $impRes = $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaignId}/impression", [
            'impression_key' => $impKey,
            'placement' => 'social_feed',
        ]);
        $impRes->assertStatus(200);
        $this->assertEquals(1, AdImpression::where('ad_campaign_id', $campaignData['id'])->count());

        // 7. Record real click
        $clkKey = 'clk_e2e_' . uniqid();
        $clkRes = $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaignId}/click", [
            'click_key' => $clkKey,
            'placement' => 'social_feed',
        ]);
        $clkRes->assertStatus(200);
        $this->assertEquals(1, AdClick::where('ad_campaign_id', $campaignData['id'])->count());

        // 8. Owner checks analytics -> exactly 1 impression, 1 click, 100.00% CTR
        $ownerAnalytics = $this->actingAs($owner, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}/analytics");
        $ownerAnalytics->assertStatus(200);
        $this->assertEquals(1, $ownerAnalytics->json('metrics.impressions_count'));
        $this->assertEquals(1, $ownerAnalytics->json('metrics.clicks_count'));
        $this->assertEquals(100.00, $ownerAnalytics->json('metrics.ctr'));

        // 9. Admin pauses campaign
        $pauseRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-campaigns/{$campaignData['id']}/pause");
        $pauseRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $pauseRes->json('campaign.status'));

        // 10. Verify paused ad is NOT delivered
        $feedPaused = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $sponsoredPaused = collect($feedPaused->json('posts'))->where('is_sponsored', true);
        $this->assertEmpty($sponsoredPaused, 'Paused ad must not be served');

        // 11. Admin resumes campaign
        $resumeRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-campaigns/{$campaignData['id']}/resume");
        $resumeRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $resumeRes->json('campaign.status'));

        // 12. Verify resumed ad is served again
        $feedResumed = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $sponsoredResumed = collect($feedResumed->json('posts'))->where('is_sponsored', true)->first();
        $this->assertNotNull($sponsoredResumed, 'Resumed ad must be served again');

        // 13. Admin stops campaign
        $stopRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-campaigns/{$campaignData['id']}/stop");
        $stopRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $stopRes->json('campaign.status'));

        // 14. Verify stopped ad is NOT delivered
        $feedStopped = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $sponsoredStopped = collect($feedStopped->json('posts'))->where('is_sponsored', true);
        $this->assertEmpty($sponsoredStopped, 'Stopped ad must not be served');
    }

    /**
     * Admin Rejection Workflow:
     * Owner submits -> Admin rejects with reason -> Stored and not deleted -> Owner sees reason.
     */
    public function test_admin_rejection_workflow(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $admin = $this->getOrCreateAdmin();

        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'E2E Rejection Test',
            'budget' => 300.00,
            'status' => AdCampaign::STATUS_PENDING_REVIEW,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Admin rejects campaign with reason
        $rejectReason = 'Content violates advertising guidelines regarding misleading financial promises.';
        $rejectRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-campaigns/{$campaign->id}/reject", [
            'rejection_reason' => $rejectReason,
        ]);
        $rejectRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_REJECTED, $rejectRes->json('campaign.status'));
        $this->assertEquals(AdCampaign::APPROVAL_REJECTED, $rejectRes->json('campaign.approval_status'));
        $this->assertEquals($rejectReason, $rejectRes->json('campaign.rejection_reason'));

        // Campaign is NOT deleted from database
        $this->assertDatabaseHas('ad_campaigns', [
            'id' => $campaign->id,
            'status' => AdCampaign::STATUS_REJECTED,
            'approval_status' => AdCampaign::APPROVAL_REJECTED,
        ]);

        // Feed does not deliver rejected ad
        $feed = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $sponsored = collect($feed->json('posts'))->where('is_sponsored', true);
        $this->assertEmpty($sponsored, 'Rejected ad must not appear in feed');

        // Owner queries campaign detail and sees rejection reason
        $ownerView = $this->actingAs($owner, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}");
        $ownerView->assertStatus(200);
        $this->assertEquals($rejectReason, $ownerView->json('campaign.rejection_reason'));
    }

    /**
     * Security & Authorization checks.
     */
    public function test_security_unauthorized_member_cannot_access_admin_or_cross_owner_controls(): void
    {
        $ownerA = $this->createVerifiedMember('owner_a');
        $ownerB = $this->createVerifiedMember('owner_b');

        $pageA = $this->createBusinessPage($ownerA);
        $postA = $this->createPagePost($pageA, $ownerA);

        $campaignA = AdCampaign::create([
            'business_page_id' => $pageA->id,
            'member_id' => $ownerA->id,
            'post_id' => $postA->id,
            'campaign_name' => 'Owner A Secret Campaign',
            'budget' => 500.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // 1. Regular member cannot call admin approve endpoint
        $adminAttempt = $this->actingAs($ownerB, 'member')->postJson("/api/admin/ad-campaigns/{$campaignA->id}/approve");
        $this->assertTrue(in_array($adminAttempt->status(), [401, 403, 404]), 'Regular member must not access admin routes');

        // 2. Owner B cannot view Owner A's page analytics
        $crossAnalytics = $this->actingAs($ownerB, 'member')->getJson("/api/member/business-pages/{$pageA->slug}/ad-campaigns/analytics");
        $crossAnalytics->assertStatus(403);

        // 3. Owner B cannot pause Owner A's campaign
        $crossPause = $this->actingAs($ownerB, 'member')->postJson("/api/member/business-pages/{$pageA->slug}/ad-campaigns/{$campaignA->campaign_id}/pause");
        $crossPause->assertStatus(403);
    }
}
