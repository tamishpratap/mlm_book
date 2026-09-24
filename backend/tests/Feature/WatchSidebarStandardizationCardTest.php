<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchSidebarStandardizationCardTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Creator',
            'user_id' => 'creator_' . uniqid(),
            'email' => 'creator-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => null,
        ], $attributes));
    }

    private function createVideoPost(Member $creator, string $body = 'Test Video', array $extra = []): Post
    {
        $page = \App\Models\BusinessPage::firstOrCreate(
            ['member_id' => $creator->id],
            [
                'page_name' => 'Page for ' . $creator->name,
                'page_username' => 'page_' . $creator->id . '_' . uniqid(),
                'slug' => 'page-' . $creator->id . '-' . uniqid(),
                'category' => 'Technology',
                'status' => 'active',
            ]
        );

        $post = Post::create(array_merge([
            'member_id' => $creator->id,
            'business_page_id' => $page->id,
            'body' => $body,
            'media_type' => 'video',
            'media_path' => 'posts/videos/' . uniqid() . '.mp4',
            'video_status' => 'ready',
        ], $extra));

        \App\Models\AdCampaign::create([
            'member_id' => $creator->id,
            'business_page_id' => $page->id,
            'post_id' => $post->id,
            'campaign_name' => 'Ad ' . uniqid(),
            'budget' => 100.00,
            'remaining_amount' => 100.00,
            'status' => \App\Models\AdCampaign::STATUS_ACTIVE,
            'approval_status' => \App\Models\AdCampaign::APPROVAL_APPROVED,
            'campaign_type' => \App\Models\AdCampaign::TYPE_BUSINESS_AD,
        ]);

        return $post;
    }

    private function establishAcceptedConnection(Member $a, Member $b): Friendship
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

    public function test_suggested_creator_with_and_without_profile_image(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer']);

        // 1. Creator with photo
        $creatorWithPhoto = $this->createMember([
            'name' => 'Creator With Photo',
            'user_id' => 'photo_creator',
            'profile_photo' => 'profile_photos/avatar1.jpg',
        ]);
        $this->createVideoPost($creatorWithPhoto);

        // 2. Creator without photo
        $creatorWithoutPhoto = $this->createMember([
            'name' => 'Creator No Photo',
            'user_id' => 'no_photo_creator',
            'profile_photo' => null,
        ]);
        $this->createVideoPost($creatorWithoutPhoto);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson(route('member.watch.index'));

        $response->assertOk();
        $suggested = $response->json('suggested_creators');
        $suggestedIds = collect($suggested)->pluck('id')->all();

        $this->assertContains($creatorWithPhoto->id, $suggestedIds);
        $this->assertContains($creatorWithoutPhoto->id, $suggestedIds);

        $withPhotoItem = collect($suggested)->firstWhere('id', $creatorWithPhoto->id);
        $this->assertEquals('profile_photos/avatar1.jpg', $withPhotoItem['profile_photo']);

        $noPhotoItem = collect($suggested)->firstWhere('id', $creatorWithoutPhoto->id);
        $this->assertNull($noPhotoItem['profile_photo']);
    }

    public function test_trending_and_recent_videos_with_thumbnails_and_metadata(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer']);

        // Connected friend with video
        $friend = $this->createMember(['name' => 'Friend B']);
        $this->establishAcceptedConnection($viewer, $friend);

        // Video with body and media
        $recentVideo = $this->createVideoPost($friend, 'Super Awesome Gameplay Highlight', [
            'media_path' => 'posts/videos/recent_video.mp4',
        ]);

        // Viral creator
        $viralCreator = $this->createMember(['name' => 'Viral Creator']);
        $trendingVideo = $this->createVideoPost($viralCreator, 'Top Viral Trending Clip', [
            'media_path' => 'posts/videos/trending_video.mp4',
        ]);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson(route('member.watch.index'));

        $response->assertOk();
        $recent = $response->json('recent_videos');
        $trending = $response->json('trending_videos');

        $this->assertNotEmpty($recent);
        $this->assertEquals($recentVideo->id, $recent[0]['id']);
        $this->assertNotNull($recent[0]['media_url']);
        $this->assertEquals('Super Awesome Gameplay Highlight', $recent[0]['body']);
        $this->assertEquals('Friend B', $recent[0]['member']['name']);

        $this->assertNotEmpty($trending);
        $this->assertEquals($trendingVideo->id, $trending[0]['id']);
        $this->assertNotNull($trending[0]['media_url']);
        $this->assertEquals('Top Viral Trending Clip', $trending[0]['body']);
        $this->assertEquals('Viral Creator', $trending[0]['member']['name']);
    }

    public function test_blade_renders_standardized_cards_and_links(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer']);

        $longNameCreator = $this->createMember([
            'name' => 'Battleground Mobile India Official Streamer Long Name',
            'user_id' => 'bgmi_official_long_handle_name',
        ]);
        $this->createVideoPost($longNameCreator, 'Extremely Long Video Title That Must Not Overflow The Sidebar Card Boundaries');

        $this->actingAs($viewer, 'member');

        $response = $this->get(route('member.watch.index'));

        $response->assertOk();
        // Suggested Creator card
        $response->assertSee('watch-sidebar-card');
        $response->assertSee('watch-sidebar-creator');
        $response->assertSee('watch-sidebar-creator__info');
        $response->assertSee('watch-sidebar-creator__details');
        $response->assertSee('watch-sidebar-creator__name');
        $response->assertSee('watch-sidebar-creator__handle');
        $response->assertSee('Battleground Mobile India Official Streamer Long Name');
        $response->assertSee('@bgmi_official_long_handle_name');
        $response->assertSee(route('member.people.show', $longNameCreator->id));

        // Trending Video card
        $response->assertSee('watch-sidebar-video-item');
        $response->assertSee('watch-sidebar-video-thumb');
        $response->assertSee('watch-sidebar-video-copy__title');
        $response->assertSee('watch-sidebar-video-copy__meta');
    }

    public function test_css_contains_complete_standardized_rules(): void
    {
        $cssPath = base_path('../frontend/src/styles/member-watch.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // Standardized card layout
        $this->assertStringContainsString('.watch-sidebar-card {', $css);
        $this->assertStringContainsString('border-radius: var(--radius-lg, 16px);', $css);

        // Video item flex layout and truncation
        $this->assertStringContainsString('.watch-sidebar-video-item {', $css);
        $this->assertStringContainsString('.watch-sidebar-video-thumb {', $css);
        $this->assertStringContainsString('.watch-sidebar-video-copy {', $css);
        $this->assertStringContainsString('.watch-sidebar-video-copy__title', $css);
        $this->assertStringContainsString('text-overflow: ellipsis;', $css);
        $this->assertStringContainsString('white-space: nowrap;', $css);

        // Video thumbnail media and fallback classes
        $this->assertStringContainsString('.watch-sidebar-video-thumb__media', $css);
        $this->assertStringContainsString('.watch-sidebar-video-thumb__play-overlay', $css);
        $this->assertStringContainsString('.watch-sidebar-video-thumb--placeholder', $css);

        // Creator card flex and wrap
        $this->assertStringContainsString('.watch-sidebar-creator {', $css);
        $this->assertStringContainsString('flex-wrap: wrap;', $css);
        $this->assertStringContainsString('.watch-sidebar-creator__details {', $css);
        $this->assertStringContainsString('.watch-sidebar-creator__action {', $css);
    }
}
