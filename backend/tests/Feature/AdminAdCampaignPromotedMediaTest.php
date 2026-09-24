<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdCampaignPromotedMediaTest extends TestCase
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
            'email' => 'admin_media@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->owner = Member::create([
            'name' => 'Business Owner',
            'user_id' => 'biz_owner_1',
            'email' => 'bizowner@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);

        $this->page = BusinessPage::create([
            'page_id' => 'page_test_1',
            'member_id' => $this->owner->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp',
            'slug' => 'acmecorp',
            'category' => 'Business & Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_list_campaigns_with_promoted_post_media_url(): void
    {
        $post = Post::create([
            'member_id' => $this->owner->id,
            'business_page_id' => $this->page->id,
            'body' => 'Promoted Image Post',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/biz_post_163_1788265951_Cy1qLB5J.png',
        ]);

        $campaign = AdCampaign::create([
            'campaign_id' => 'CAMP-IMAGE-01',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Summer Image Promo',
            'budget' => 100.00,
            'fee_percent' => 2.50,
            'fee_amount' => 2.50,
            'wallet_debit' => 102.50,
            'spent_amount' => 0.00,
            'remaining_amount' => 100.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/ad-campaigns');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $campaignData = collect($response->json('campaigns.data'))->firstWhere('id', $campaign->id);
        $this->assertNotNull($campaignData);
        $this->assertNotNull($campaignData['post']);
        $this->assertEquals('image', $campaignData['post']['media_type']);
        $this->assertEquals('uploads/posts/images/biz_post_163_1788265951_Cy1qLB5J.png', $campaignData['post']['media_path']);
        
        // Assert media_url is present, does NOT contain /storage/, and points to the upload
        $this->assertNotEmpty($campaignData['post']['media_url']);
        $this->assertStringNotContainsString('/storage/uploads/', $campaignData['post']['media_url']);
        $this->assertStringContainsString('uploads/posts/images/biz_post_163_1788265951_Cy1qLB5J.png', $campaignData['post']['media_url']);

        // Assert promoted_post alias exists and includes author
        $this->assertNotNull($campaignData['promoted_post']);
        $this->assertEquals($campaignData['post']['id'], $campaignData['promoted_post']['id']);
        $this->assertNotNull($campaignData['post']['author']);
        $this->assertEquals($this->owner->id, $campaignData['post']['author']['id']);
        $this->assertEquals($this->owner->name, $campaignData['post']['author']['name']);
        $this->assertEquals($this->owner->user_id, $campaignData['post']['author']['user_id']);
    }

    public function test_admin_can_show_campaign_with_video_promoted_post(): void
    {
        $videoPost = Post::create([
            'member_id' => $this->owner->id,
            'business_page_id' => $this->page->id,
            'body' => 'Promoted Video Presentation',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/biz_video_163_1788265951_AbCdEfGh.mp4',
        ]);

        $campaign = AdCampaign::create([
            'campaign_id' => 'CAMP-VIDEO-02',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'post_id' => $videoPost->id,
            'campaign_name' => 'Summer Video Promo',
            'budget' => 200.00,
            'fee_percent' => 2.50,
            'fee_amount' => 5.00,
            'wallet_debit' => 205.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 200.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/ad-campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'campaign' => [
                    'id' => $campaign->id,
                    'post' => [
                        'id' => $videoPost->id,
                        'media_type' => 'video',
                        'media_path' => 'uploads/posts/videos/biz_video_163_1788265951_AbCdEfGh.mp4',
                    ],
                ],
            ]);

        $mediaUrl = $response->json('campaign.post.media_url');
        $this->assertNotEmpty($mediaUrl);
        $this->assertStringNotContainsString('/storage/uploads/', $mediaUrl);
        $this->assertStringContainsString('uploads/posts/videos/biz_video_163_1788265951_AbCdEfGh.mp4', $mediaUrl);
    }

    public function test_post_media_url_normalizes_storage_prefix_and_handles_full_urls(): void
    {
        // 1. Storage prefix normalization
        $postWithStorage = Post::create([
            'member_id' => $this->owner->id,
            'body' => 'Post with storage prefix',
            'media_type' => 'image',
            'media_path' => 'storage/uploads/posts/images/test.png',
        ]);
        $this->assertStringNotContainsString('/storage/uploads/', $postWithStorage->media_url);
        $this->assertStringContainsString('/uploads/posts/images/test.png', $postWithStorage->media_url);

        // 2. Full URL preserved
        $postWithFullUrl = Post::create([
            'member_id' => $this->owner->id,
            'body' => 'Post with absolute CDN URL',
            'media_type' => 'image',
            'media_path' => 'https://cdn.example.com/images/sample.jpg',
        ]);
        $this->assertEquals('https://cdn.example.com/images/sample.jpg', $postWithFullUrl->media_url);

        // 3. Null when no media
        $postWithoutMedia = Post::create([
            'member_id' => $this->owner->id,
            'body' => 'Text only post',
            'media_type' => null,
            'media_path' => null,
        ]);
        $this->assertNull($postWithoutMedia->media_url);
    }

    public function test_campaign_with_deleted_post_returns_safely_without_500(): void
    {
        $post = Post::create([
            'member_id' => $this->owner->id,
            'business_page_id' => $this->page->id,
            'body' => 'Post to delete',
        ]);

        $campaign = AdCampaign::create([
            'campaign_id' => 'CAMP-ORPHAN-03',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->page->id,
            'member_id' => $this->owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Orphan Post Campaign',
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $post->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/ad-campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'campaign' => [
                    'id' => $campaign->id,
                    'post' => null,
                ],
            ]);
    }
}
