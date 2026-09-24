<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdCampaignsListTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $owner;
    protected BusinessPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->owner = Member::create([
            'name' => 'Business Owner',
            'user_id' => 'biz_owner_001',
            'email' => 'bizowner001@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'profile_photo' => 'uploads/profile/photo_123.jpg',
            'mobile_verified_at' => now(),
        ]);

        $this->page = BusinessPage::create([
            'page_id' => 'page_test_001',
            'member_id' => $this->owner->id,
            'page_name' => 'Tech Innovations Ltd',
            'page_username' => 'techinnovations',
            'slug' => 'techinnovations',
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_list_ad_campaigns_successfully(): void
    {
        $post = Post::create([
            'member_id' => $this->owner->id,
            'business_page_id' => $this->page->id,
            'body' => 'High tech announcement',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/test_post.png',
        ]);

        $campaign = AdCampaign::create([
            'campaign_id' => 'camp_test_01',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Tech Launch 2026',
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'spent_amount' => 10.00,
            'remaining_amount' => 40.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/ad-campaigns');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json();
        $this->assertArrayHasKey('campaigns', $data);
        $this->assertArrayHasKey('metrics', $data);
        $this->assertArrayHasKey('statuses', $data);
        $this->assertArrayHasKey('approval_statuses', $data);

        $this->assertEquals(1, count($data['campaigns']['data']));
        $c = $data['campaigns']['data'][0];
        $this->assertEquals('Tech Launch 2026', $c['campaign_name']);
        $this->assertEquals($this->owner->name, $c['owner']['name']);
        $this->assertEquals($this->owner->avatar_url, $c['owner']['avatar_url']);
        $this->assertNotNull($c['post']);
        $this->assertEquals('High tech announcement', $c['post']['body']);
        $this->assertEquals($this->owner->name, $c['post']['member']['name']);
        $this->assertEquals($this->owner->avatar_url, $c['post']['member']['avatar_url']);

        // Verify financial summary reconciliation
        $this->assertTrue($c['financial_summary']['reconciles_exactly']);
        $this->assertEquals(50.00, $c['financial_summary']['campaign_budget']);
        $this->assertEquals(10.00, $c['financial_summary']['rewards_paid']);
        $this->assertEquals(40.00, $c['financial_summary']['remaining_campaign_budget']);
    }

    public function test_admin_list_ad_campaigns_handles_empty_state_cleanly(): void
    {
        // 0 campaigns in database
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/ad-campaigns');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'metrics' => [
                    'total_campaigns' => 0,
                    'active' => 0,
                    'pending_review' => 0,
                ],
            ]);

        $this->assertEquals(0, count($response->json('campaigns.data')));
        $this->assertEquals(0, $response->json('campaigns.total'));
    }

    public function test_admin_can_filter_campaigns_by_status_and_search(): void
    {
        AdCampaign::create([
            'campaign_id' => 'camp_filter_01',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'campaign_name' => 'Active Alpha Campaign',
            'budget' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        AdCampaign::create([
            'campaign_id' => 'camp_filter_02',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'campaign_name' => 'Pending Beta Campaign',
            'budget' => 20.00,
            'status' => AdCampaign::STATUS_PENDING_REVIEW,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Filter by status=active
        $resActive = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/ad-campaigns?status=active');
        $resActive->assertOk();
        $this->assertEquals(1, count($resActive->json('campaigns.data')));
        $this->assertEquals('Active Alpha Campaign', $resActive->json('campaigns.data.0.campaign_name'));

        // Filter by search query
        $resSearch = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/ad-campaigns?q=Beta');
        $resSearch->assertOk();
        $this->assertEquals(1, count($resSearch->json('campaigns.data')));
        $this->assertEquals('Pending Beta Campaign', $resSearch->json('campaigns.data.0.campaign_name'));
    }

    public function test_admin_can_view_campaign_details(): void
    {
        $post = Post::create([
            'member_id' => $this->owner->id,
            'business_page_id' => $this->page->id,
            'body' => 'Detailed test post body',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/detail_test.jpg',
        ]);

        $campaign = AdCampaign::create([
            'campaign_id' => 'camp_detail_01',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Campaign Detail Test',
            'budget' => 100.00,
            'fee_percent' => 2.50,
            'fee_amount' => 2.50,
            'wallet_debit' => 102.50,
            'spent_amount' => 25.00,
            'remaining_amount' => 75.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/ad-campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $detail = $response->json('campaign');
        $this->assertEquals('Campaign Detail Test', $detail['campaign_name']);
        $this->assertNotNull($detail['post']);
        $this->assertEquals('Detailed test post body', $detail['post']['body']);
        $this->assertNotNull($detail['financial_summary']);
        $this->assertEquals(100.00, $detail['financial_summary']['campaign_budget']);
        $this->assertEquals(25.00, $detail['financial_summary']['rewards_paid']);
        $this->assertEquals(75.00, $detail['financial_summary']['remaining_campaign_budget']);
    }

    public function test_admin_can_approve_pause_resume_and_stop_campaign(): void
    {
        $campaign = AdCampaign::create([
            'campaign_id' => 'camp_lifecycle_01',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'campaign_name' => 'Lifecycle Ad',
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_PENDING_REVIEW,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // 1. Approve
        $resApprove = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/approve");
        $resApprove->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(AdCampaign::STATUS_APPROVED, $resApprove->json('campaign.status'));
        $this->assertEquals(AdCampaign::APPROVAL_APPROVED, $resApprove->json('campaign.approval_status'));

        // 2. Pause
        $resPause = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/pause");
        $resPause->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $resPause->json('campaign.status'));

        // 3. Resume
        $resResume = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/resume");
        $resResume->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $resResume->json('campaign.status'));

        // 4. Stop
        $resStop = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/stop");
        $resStop->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $resStop->json('campaign.status'));
    }
}
