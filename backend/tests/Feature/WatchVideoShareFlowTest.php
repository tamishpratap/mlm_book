<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WatchVideoShareFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['content_moderation.enabled' => false]);
    }

    private function createMember(): Member
    {
        return Member::create([
            'name' => 'Video Creator',
            'email' => 'creator-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_authenticated_member_cannot_upload_video_via_generic_post(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $fakeVideo = UploadedFile::fake()->create('sample-video.mp4', 1024, 'video/mp4');

        $response = $this->postJson('/api/member/posts', [
            'body' => 'Check out my awesome new video on Watch!',
            'media' => $fakeVideo,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);
    }

    public function test_authenticated_member_can_upload_and_publish_video_via_business_page(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = \App\Models\BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Acme Tech',
            'page_username' => 'acmetech_' . uniqid(),
            'slug' => 'acme-tech-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
            'verification_badge' => 1,
        ]);

        $fakeVideo = UploadedFile::fake()->create('sample-biz-video.mp4', 1024, 'video/mp4');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Check out our product demo video!',
            'media' => $fakeVideo,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $postId = $response->json('post.id');
        $post = Post::find($postId);

        $this->assertNotNull($post);
        $this->assertEquals('video', $post->media_type);
        $this->assertEquals($businessPage->id, $post->business_page_id);
        $this->assertNotNull($post->media_path);

        if (File::exists(public_path($post->media_path))) {
            File::delete(public_path($post->media_path));
        }
    }

    public function test_published_business_video_appears_in_watch_feed_only_with_eligible_campaign(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = \App\Models\BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Apex Global',
            'page_username' => 'apexglobal_' . uniqid(),
            'slug' => 'apex-global-' . uniqid(),
            'category' => 'Business',
            'status' => 'active',
            'verification_badge' => 1,
        ]);

        // Create business video post without campaign
        $post = Post::create([
            'member_id' => $member->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Apex Promo Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/apex-test.mp4',
            'status' => 'published',
        ]);

        // Unadvertised Business Page video must NOT appear in Watch feed
        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchResponse->assertOk();
        $allPosts = collect($watchResponse->json('posts'));
        $this->assertFalse($allPosts->contains('id', $post->id));

        // Create active, eligible ad campaign for this business page and post
        \App\Models\AdCampaign::create([
            'business_page_id' => $businessPage->id,
            'member_id' => $member->id,
            'post_id' => $post->id,
            'campaign_name' => 'Apex Launch Campaign',
            'campaign_type' => 'business_ad',
            'status' => 'active',
            'approval_status' => 'approved',
            'budget' => 500.00,
            'remaining_amount' => 450.00,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(7),
        ]);

        // Now it MUST appear in Watch feed
        $watchResponseAfter = $this->getJson('/api/member/watch?filter=all');
        $watchResponseAfter->assertOk();
        $allPostsAfter = collect($watchResponseAfter->json('posts'));
        $this->assertTrue($allPostsAfter->contains('id', $post->id));
    }

    public function test_video_upload_rejects_files_larger_than_60mb(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = \App\Models\BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Large Video Page',
            'page_username' => 'largevid_' . uniqid(),
            'slug' => 'large-video-page-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
            'verification_badge' => 1,
        ]);

        // 61MB file
        $oversizedVideo = UploadedFile::fake()->create('large-video.mp4', 62464, 'video/mp4');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Oversized video attempt',
            'media' => $oversizedVideo,
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_upload_video(): void
    {
        $fakeVideo = UploadedFile::fake()->create('unauth-video.mp4', 500, 'video/mp4');

        $response = $this->postJson('/api/member/posts', [
            'body' => 'Unauthenticated attempt',
            'media' => $fakeVideo,
        ]);

        $response->assertStatus(401);
    }

    public function test_web_route_create_video_redirects_appropriately(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->get('/member/create-video');
        $response->assertRedirect();
    }
}
