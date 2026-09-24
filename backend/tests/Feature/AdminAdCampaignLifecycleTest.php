<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdCampaignLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $owner;
    protected BusinessPage $page;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_lifecycle@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $this->owner = Member::create([
            'name' => 'Business Owner',
            'user_id' => 'biz_lifecycle_001',
            'email' => 'bizlifecycle@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);

        $this->page = BusinessPage::create([
            'page_id' => 'page_lifecycle_001',
            'member_id' => $this->owner->id,
            'page_name' => 'Lifecycle Page',
            'page_username' => 'lifecyclepage',
            'slug' => 'lifecyclepage',
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $this->post = Post::create([
            'member_id' => $this->owner->id,
            'business_page_id' => $this->page->id,
            'body' => 'Promoted lifecycle content',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/test.png',
        ]);
    }

    protected function createCampaign(array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'campaign_id' => 'camp_' . uniqid(),
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'post_id' => $this->post->id,
            'campaign_name' => 'Test Campaign',
            'budget' => 100.00,
            'spent_amount' => 20.00,
            'remaining_amount' => 80.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(7),
        ], $attributes));
    }

    public function test_admin_can_stop_an_active_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/stop");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Ad campaign stopped by administrator.',
            ]);

        $this->assertDatabaseHas('ad_campaigns', [
            'id' => $campaign->id,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        $fresh = $campaign->fresh();
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);
        $lifecycle = $fresh->target_audience['lifecycle_history'] ?? [];
        $this->assertNotEmpty($lifecycle);
        $this->assertEquals('admin_stopped', end($lifecycle)['action']);
    }

    public function test_admin_cannot_stop_already_stopped_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/stop");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This campaign is already stopped.',
            ]);
    }

    public function test_admin_can_restart_a_stopped_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_STOPPED,
            'budget' => 100.00,
            'spent_amount' => 35.00,
            'remaining_amount' => 65.00,
        ]);

        for ($i = 0; $i < 5; $i++) {
            \App\Models\AdImpression::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $this->owner->id,
                'post_id' => $this->post->id,
                'placement' => 'social_feed',
                'created_at' => now(),
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            \App\Models\AdClick::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $this->owner->id,
                'post_id' => $this->post->id,
                'placement' => 'social_feed',
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Ad campaign restarted successfully.',
            ]);

        $fresh = $campaign->fresh();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $fresh->status);

        // Verify that budget, spend, remaining, impressions, clicks are intact
        $this->assertEquals(100.00, (float) $fresh->budget);
        $this->assertEquals(35.00, (float) $fresh->spent_amount);
        $this->assertEquals(65.00, (float) $fresh->remaining_amount);
        $this->assertEquals(5, $fresh->impressions_count);
        $this->assertEquals(2, $fresh->clicks_count);

        // Verify lifecycle audit log
        $lifecycle = $fresh->target_audience['lifecycle_history'] ?? [];
        $this->assertNotEmpty($lifecycle);
        $this->assertEquals('admin_restarted', end($lifecycle)['action']);
    }

    public function test_admin_cannot_restart_already_active_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This campaign is already active.',
            ]);
    }

    public function test_admin_cannot_restart_paused_campaign_and_is_guided_to_resume(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_PAUSED,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Campaign is currently paused. Please use Resume Campaign instead.',
            ]);
    }

    public function test_admin_cannot_restart_unapproved_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_STOPPED,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Campaign cannot be restarted because it is not approved.',
            ]);
    }

    public function test_admin_cannot_restart_budget_exhausted_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_STOPPED,
            'budget' => 50.00,
            'spent_amount' => 50.00,
            'remaining_amount' => 0.00,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Campaign cannot be restarted because its budget has been exhausted.',
            ]);
    }

    public function test_admin_cannot_restart_expired_campaign(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_STOPPED,
            'end_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Campaign cannot be restarted because it has expired.',
            ]);
    }

    public function test_complete_bidirectional_lifecycle_loop(): void
    {
        $campaign = $this->createCampaign([
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        // 1. Stop
        $resStop1 = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/stop");
        $resStop1->assertOk();
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);

        // 2. Restart
        $resRestart1 = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");
        $resRestart1->assertOk();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);

        // 3. Stop again
        $resStop2 = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/stop");
        $resStop2->assertOk();
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);

        // 4. Restart again
        $resRestart2 = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/restart");
        $resRestart2->assertOk();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
    }
}
