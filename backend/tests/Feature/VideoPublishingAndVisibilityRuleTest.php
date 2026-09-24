<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class VideoPublishingAndVisibilityRuleTest extends TestCase
{
    use RefreshDatabase;

    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['content_moderation.enabled' => false]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if ($path && File::exists(public_path($path))) {
                File::delete(public_path($path));
            }
        }

        parent::tearDown();
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Rule Test User',
            'user_id' => 'rtu_' . Str::lower(Str::random(8)),
            'email' => 'rtu_' . Str::lower(Str::random(8)) . '@example.com',
            'password' => bcrypt('password123'),
            'mobile_number' => '+1' . random_int(1000000000, 9999999999),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ], $attributes));
    }

    private function createBusinessPage(Member $owner, array $attributes = []): BusinessPage
    {
        $unique = Str::lower(Str::random(6));

        return BusinessPage::create(array_merge([
            'member_id' => $owner->id,
            'page_name' => 'Business Page ' . $unique,
            'page_username' => 'bizpage_' . $unique,
            'slug' => 'biz-page-' . $unique,
            'category' => 'Technology',
            'status' => 'active',
            'verification_badge' => 1,
        ], $attributes));
    }

    private function createAdCampaign(BusinessPage $businessPage, Post $post, array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'business_page_id' => $businessPage->id,
            'member_id' => $businessPage->member_id,
            'post_id' => $post->id,
            'campaign_name' => 'Campaign ' . Str::random(6),
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 500.00,
            'remaining_amount' => 450.00,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(7),
        ], $attributes));
    }

    private function establishFriendship(Member $a, Member $b): Friendship
    {
        [$m1, $m2] = Friendship::normalizePair($a->id, $b->id);

        return Friendship::create([
            'member_one_id' => $m1,
            'member_two_id' => $m2,
            'requested_by_id' => $a->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Test 1: Generic PostController::store() rejects video with 422 "Videos can only be posted from a Business Page.".
     */
    public function test_01_generic_post_controller_store_rejects_video_upload_with_422(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $video = UploadedFile::fake()->create('attempt.mp4', 1024, 'video/mp4');

        $response = $this->postJson('/api/member/posts', [
            'body' => 'Attempting to upload video on member feed',
            'media' => $video,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);
    }

    /**
     * Test 2: Generic PostController::store() allows organic text and image posting.
     */
    public function test_02_generic_post_controller_store_allows_organic_text_and_image_posting(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $image = UploadedFile::fake()->image('organic-photo.jpg', 600, 400);

        $response = $this->postJson('/api/member/posts', [
            'body' => 'Organic text update with photo',
            'media' => $image,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $postId = $response->json('post_id');
        $post = Post::findOrFail($postId);

        $this->assertSame('image', $post->media_type);
        $this->assertNull($post->business_page_id);
        $this->assertSame($member->id, $post->member_id);
        $this->createdFiles[] = $post->media_path;
    }

    /**
     * Test 3: CommunityPostController::store() rejects video with 422 "Videos can only be posted from a Business Page.".
     */
    public function test_03_community_post_controller_store_rejects_video_upload_with_422(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $community = Community::create([
            'community_id' => 'comm_' . Str::random(8),
            'owner_id' => $member->id,
            'name' => 'Tech Community',
            'slug' => 'tech-community-' . Str::random(6),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $video = UploadedFile::fake()->create('comm-vid.mp4', 1024, 'video/mp4');

        $response = $this->postJson("/api/member/community/{$community->slug}/posts", [
            'body' => 'Video attempt in community',
            'media' => $video,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);
    }

    /**
     * Test 4: CommunityPostController::store() allows image posting.
     */
    public function test_04_community_post_controller_store_allows_image_posting(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $community = Community::create([
            'community_id' => 'comm_' . Str::random(8),
            'owner_id' => $member->id,
            'name' => 'Design Community',
            'slug' => 'design-community-' . Str::random(6),
            'category' => 'Design',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $image = UploadedFile::fake()->image('design-wireframe.png', 400, 300);

        $response = $this->postJson("/api/member/community/{$community->slug}/posts", [
            'body' => 'New wireframe design',
            'media' => $image,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'community_id' => $community->id,
            'member_id' => $member->id,
            'media_type' => 'image',
        ]);
    }

    /**
     * Test 5: BusinessPageController::storePost() accepts video upload.
     */
    public function test_05_business_page_controller_store_post_accepts_video_upload(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = $this->createBusinessPage($member);
        $video = UploadedFile::fake()->create('biz-demo.mp4', 2048, 'video/mp4');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Official product launch video',
            'media' => $video,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $postId = $response->json('post.id');
        $post = Post::findOrFail($postId);

        $this->assertSame('video', $post->media_type);
        $this->assertSame($businessPage->id, $post->business_page_id);
        $this->createdFiles[] = $post->media_path;
    }

    /**
     * Test 6: BusinessPageController::storePost() allows image posting.
     */
    public function test_06_business_page_controller_store_post_allows_image_posting(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = $this->createBusinessPage($member);
        $image = UploadedFile::fake()->image('biz-banner.jpg', 800, 400);

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Official announcement banner',
            'media' => $image,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $postId = $response->json('post.id');
        $post = Post::findOrFail($postId);

        $this->assertSame('image', $post->media_type);
        $this->assertSame($businessPage->id, $post->business_page_id);
        $this->createdFiles[] = $post->media_path;
    }

    /**
     * Test 7: Business Page ownership/permission checks remain enforced.
     */
    public function test_07_business_page_ownership_and_permission_checks_remain_enforced(): void
    {
        $owner = $this->createMember(['name' => 'Page Owner']);
        $businessPage = $this->createBusinessPage($owner);

        $unauthorizedMember = $this->createMember(['name' => 'Intruder']);
        $this->actingAs($unauthorizedMember, 'member');

        $video = UploadedFile::fake()->create('hack-video.mp4', 1024, 'video/mp4');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Unauthorized post attempt',
            'media' => $video,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 8: Watch feed excludes unadvertised Business Page video.
     */
    public function test_08_watch_feed_excludes_unadvertised_business_page_video(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = $this->createBusinessPage($member);

        $post = Post::create([
            'member_id' => $member->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Organic Business Video Without Ad Campaign',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/biz-unad.mp4',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/member/watch?filter=all');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $this->assertFalse($posts->contains('id', $post->id));
    }

    /**
     * Test 9: Watch feed includes eligible advertised Business Page video.
     */
    public function test_09_watch_feed_includes_eligible_advertised_business_page_video(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $businessPage = $this->createBusinessPage($member);

        $post = Post::create([
            'member_id' => $member->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Promoted Business Video With Active Ad Campaign',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/biz-ad.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $post);

        $response = $this->getJson('/api/member/watch?filter=all');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $this->assertTrue($posts->contains('id', $post->id));
    }

    /**
     * Test 10: Watch feed excludes non-Business historical video posts.
     */
    public function test_10_watch_feed_excludes_non_business_historical_video_posts(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $post = Post::create([
            'member_id' => $member->id,
            'business_page_id' => null,
            'body' => 'Historical Personal Video Post',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/personal.mp4',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/member/watch?filter=all');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $this->assertFalse($posts->contains('id', $post->id));
    }

    /**
     * Test 11: Socials organic feed excludes video posts and shared video posts.
     */
    public function test_11_socials_organic_feed_excludes_video_posts_and_shared_video_posts(): void
    {
        $viewer = $this->createMember(['name' => 'Feed Viewer']);
        $friend = $this->createMember(['name' => 'Friend User']);
        $this->establishFriendship($viewer, $friend);

        $businessPage = $this->createBusinessPage($friend);

        // 1. Organic business video post
        $videoPost = Post::create([
            'member_id' => $friend->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Organic Business Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/feed-vid.mp4',
            'status' => 'published',
        ]);

        // 2. Share of that video post
        $sharedPost = Post::create([
            'member_id' => $friend->id,
            'original_post_id' => $videoPost->id,
            'body' => 'Check out this shared video',
            'status' => 'published',
        ]);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson('/member/socials');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $postIds = $posts->pluck('id');

        $this->assertFalse($postIds->contains($videoPost->id), 'Organic feed must not contain video posts.');
        $this->assertFalse($postIds->contains($sharedPost->id), 'Organic feed must not contain shared video posts.');
    }

    /**
     * Test 12: Socials feed includes eligible sponsored Business Page video via paid delivery.
     */
    public function test_12_socials_feed_includes_eligible_sponsored_business_page_video_via_paid_delivery(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer User']);
        $advertiser = $this->createMember(['name' => 'Advertiser User']);

        $businessPage = $this->createBusinessPage($advertiser);

        $videoPost = Post::create([
            'member_id' => $advertiser->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Sponsored Business Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/sponsored-vid.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $videoPost);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson('/member/socials');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $sponsoredPost = $posts->firstWhere('id', $videoPost->id);

        $this->assertNotNull($sponsoredPost, 'Sponsored business video should appear in Socials feed.');
        $this->assertTrue((bool) ($sponsoredPost['is_sponsored'] ?? false));
    }

    /**
     * Test 13: Socials feed includes normal organic image posts.
     */
    public function test_13_socials_feed_includes_normal_organic_image_posts(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer User']);
        $friend = $this->createMember(['name' => 'Friend User']);
        $this->establishFriendship($viewer, $friend);

        $imagePost = Post::create([
            'member_id' => $friend->id,
            'body' => 'Organic Image Post in Feed',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/test.jpg',
            'status' => 'published',
        ]);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson('/member/socials');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $this->assertTrue($posts->contains('id', $imagePost->id), 'Organic image post must appear in Socials feed.');
    }

    /**
     * Test 14: Expired campaign removes Business Page video from Watch/Socials distribution.
     */
    public function test_14_expired_campaign_removes_business_page_video_from_watch_and_socials(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $businessPage = $this->createBusinessPage($viewer);

        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Expired Campaign Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/expired.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $videoPost, [
            'start_at' => now()->subDays(5),
            'end_at' => now()->subHour(),
        ]);

        // Watch feed
        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchPosts = collect($watchResponse->json('posts'));
        $this->assertFalse($watchPosts->contains('id', $videoPost->id));

        // Socials paid ranking
        $paidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($viewer);
        $this->assertFalse(collect($paidItems)->contains('id', $videoPost->id));
    }

    /**
     * Test 15: Pending campaign does not distribute video to Watch/Socials.
     */
    public function test_15_pending_campaign_does_not_distribute_video_to_watch_or_socials(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $businessPage = $this->createBusinessPage($viewer);

        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Pending Campaign Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/pending.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $videoPost, [
            'approval_status' => 'pending',
            'status' => 'active',
        ]);

        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchPosts = collect($watchResponse->json('posts'));
        $this->assertFalse($watchPosts->contains('id', $videoPost->id));

        $paidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($viewer);
        $this->assertFalse(collect($paidItems)->contains('id', $videoPost->id));
    }

    /**
     * Test 16: Rejected campaign does not distribute video to Watch/Socials.
     */
    public function test_16_rejected_campaign_does_not_distribute_video_to_watch_or_socials(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $businessPage = $this->createBusinessPage($viewer);

        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Rejected Campaign Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/rejected.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $videoPost, [
            'approval_status' => 'rejected',
            'status' => 'rejected',
        ]);

        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchPosts = collect($watchResponse->json('posts'));
        $this->assertFalse($watchPosts->contains('id', $videoPost->id));

        $paidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($viewer);
        $this->assertFalse(collect($paidItems)->contains('id', $videoPost->id));
    }

    /**
     * Test 17: Budget-ineligible campaign does not distribute video.
     */
    public function test_17_budget_ineligible_campaign_does_not_distribute_video(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $businessPage = $this->createBusinessPage($viewer);

        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Exhausted Budget Campaign Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/exhausted.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $videoPost, [
            'budget' => 500.00,
            'spent_amount' => 500.00,
            'remaining_amount' => 0.00,
        ]);

        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchPosts = collect($watchResponse->json('posts'));
        $this->assertFalse($watchPosts->contains('id', $videoPost->id));

        $paidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($viewer);
        $this->assertFalse(collect($paidItems)->contains('id', $videoPost->id));
    }

    /**
     * Test 18: Active/eligible campaign distributes video to Watch and Socials paid ad delivery.
     */
    public function test_18_active_eligible_campaign_distributes_video_to_watch_and_socials(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $businessPage = $this->createBusinessPage($viewer);

        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Active Prime Campaign Video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/active-prime.mp4',
            'status' => 'published',
        ]);

        $this->createAdCampaign($businessPage, $videoPost, [
            'budget' => 200.00,
            'remaining_amount' => 180.00,
            'status' => 'active',
            'approval_status' => 'approved',
            'start_at' => now()->subHour(),
            'end_at' => now()->addDays(5),
        ]);

        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchPosts = collect($watchResponse->json('posts'));
        $this->assertTrue($watchPosts->contains('id', $videoPost->id));

        $paidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($viewer);
        $this->assertTrue(collect($paidItems)->contains('id', $videoPost->id));
    }

    /**
     * Test 19: Campaign linked to wrong Business Page does not distribute video.
     */
    public function test_19_campaign_linked_to_wrong_business_page_does_not_distribute_video(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $pageA = $this->createBusinessPage($viewer);
        $pageB = $this->createBusinessPage($viewer);

        // Video belongs to Page A
        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $pageA->id,
            'body' => 'Video for Page A',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/pagea.mp4',
            'status' => 'published',
        ]);

        // Campaign belongs to Page B
        $this->createAdCampaign($pageB, $videoPost);

        $watchResponse = $this->getJson('/api/member/watch?filter=all');
        $watchPosts = collect($watchResponse->json('posts'));
        $this->assertFalse($watchPosts->contains('id', $videoPost->id));

        $paidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($viewer);
        $this->assertFalse(collect($paidItems)->contains('id', $videoPost->id));
    }

    /**
     * Test 20: Existing Business Page video remains visible on Business Page without ad campaign.
     */
    public function test_20_existing_business_page_video_remains_visible_on_business_page_without_ad_campaign(): void
    {
        $viewer = $this->createMember();
        $this->actingAs($viewer, 'member');

        $businessPage = $this->createBusinessPage($viewer);

        $videoPost = Post::create([
            'member_id' => $viewer->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Organic Profile Video For Business',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/biz-profile.mp4',
            'status' => 'published',
        ]);

        // Verified through BusinessPage posts relationship
        $pagePosts = $businessPage->posts()->get();
        $this->assertTrue($pagePosts->contains('id', $videoPost->id));

        // Verified through Business Page show endpoint
        $response = $this->getJson("/api/member/business-pages/{$businessPage->slug}");
        $response->assertOk();
    }

    /**
     * Test 21: Direct API video bypass attempts are rejected (422) across surfaces.
     */
    public function test_21_direct_api_video_bypass_attempts_rejected_across_all_surfaces(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $video = UploadedFile::fake()->create('bypass.mp4', 1024, 'video/mp4');

        // 1. Stories bypass attempt
        $storyResponse = $this->postJson('/api/member/stories', [
            'media' => $video,
            'caption' => 'Bypass attempt story',
        ]);
        $storyResponse->assertStatus(422)
            ->assertJsonValidationErrors([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);

        // 2. Events discussion bypass attempt
        $event = Event::create([
            'organizer_id' => $member->id,
            'title' => 'Annual Summit',
            'slug' => 'annual-summit-' . Str::random(6),
            'description' => 'Summit description',
            'category' => 'Business',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'status' => 'published',
        ]);

        $eventResponse = $this->postJson("/api/member/events/{$event->id}/posts", [
            'body' => 'Bypass attempt in event',
            'media' => $video,
        ]);
        $eventResponse->assertStatus(422)
            ->assertJsonValidationErrors([
                'media' => 'Videos can only be posted from a Business Page.',
            ]);

        // 3. Marketplace bypass attempt
        $marketResponse = $this->postJson('/api/member/marketplace', [
            'title' => 'Laptop for sale',
            'price' => 500,
            'description' => 'Great condition',
            'category' => 'Electronics',
            'videos' => [$video],
        ]);
        $marketResponse->assertStatus(422)
            ->assertJsonValidationErrors([
                'videos' => 'Videos can only be posted from a Business Page.',
            ]);
    }

    /**
     * Test 22: Historical video records in database are preserved and not deleted.
     */
    public function test_22_historical_video_records_in_database_are_preserved(): void
    {
        $member = $this->createMember();

        $historicalPost = Post::create([
            'member_id' => $member->id,
            'business_page_id' => null,
            'body' => 'Legacy personal video from 2025',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/historical_2025.mp4',
            'status' => 'published',
        ]);

        // Assert record is still fully preserved and retrievable
        $retrieved = Post::find($historicalPost->id);
        $this->assertNotNull($retrieved);
        $this->assertSame('video', $retrieved->media_type);
        $this->assertSame('uploads/posts/videos/historical_2025.mp4', $retrieved->media_path);
        $this->assertNull($retrieved->business_page_id);
    }
}
