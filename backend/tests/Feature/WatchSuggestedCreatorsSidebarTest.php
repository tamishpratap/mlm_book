<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchSuggestedCreatorsSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Creator User',
            'user_id' => 'creator_' . uniqid(),
            'email' => 'creator-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => null,
        ], $attributes));
    }

    private function createVideoPost(Member $creator, string $body = 'Test Video'): Post
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

        $post = Post::create([
            'member_id' => $creator->id,
            'business_page_id' => $page->id,
            'body' => $body,
            'media_type' => 'video',
            'media_path' => 'posts/videos/' . uniqid() . '.mp4',
            'video_status' => 'ready',
        ]);

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

    public function test_watch_api_returns_suggested_creators_with_profile_photo_attributes(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer User']);
        $creator = $this->createMember([
            'name' => 'Battleground mobile India',
            'user_id' => 'bgmi_official_channel',
            'profile_photo' => 'profile_photos/creator123.jpg',
        ]);

        // Create a video post for the creator so they qualify as a suggested creator
        $this->createVideoPost($creator, 'Epic gameplay video highlights');

        $this->actingAs($viewer, 'member');

        $response = $this->getJson(route('member.watch.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'suggested_creators' => [
                '*' => [
                    'id',
                    'name',
                    'user_id',
                    'profile_photo',
                    'profile_photo_url',
                    'avatar_url',
                ],
            ],
        ]);

        $creators = $response->json('suggested_creators');
        $this->assertNotEmpty($creators);
        $found = collect($creators)->firstWhere('id', $creator->id);
        $this->assertNotNull($found);
        $this->assertEquals('Battleground mobile India', $found['name']);
        $this->assertEquals('bgmi_official_channel', $found['user_id']);
        $this->assertArrayHasKey('profile_photo', $found);
        $this->assertArrayHasKey('profile_photo_url', $found);
        $this->assertArrayHasKey('avatar_url', $found);
    }

    public function test_blade_renders_suggested_creator_with_avatar_and_details(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer User']);
        $creator = $this->createMember([
            'name' => 'Battleground mobile India',
            'user_id' => 'bgmi_official',
            'profile_photo' => null,
        ]);

        $this->createVideoPost($creator, 'Video for sidebar test');

        $this->actingAs($viewer, 'member');

        $response = $this->get(route('member.watch.index'));

        $response->assertOk();
        $response->assertSee('watch-sidebar-creator');
        $response->assertSee('watch-sidebar-creator__details');
        $response->assertSee('Battleground mobile India');
        $response->assertSee('@bgmi_official');
        $response->assertSee('watch-sidebar-creator__action');
        $response->assertSee('Connect');
    }

    public function test_css_defines_watch_sidebar_creator_layout_and_wrapping(): void
    {
        $cssPath = base_path('../frontend/src/styles/member-watch.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // 1. Flex container with wrapping and gap
        $this->assertStringContainsString('.watch-sidebar-creator {', $css);
        $this->assertStringContainsString('flex-wrap: wrap;', $css);
        $this->assertStringContainsString('justify-content: space-between;', $css);

        // 2. Info container with min-width: 0 and flex
        $this->assertStringContainsString('.watch-sidebar-creator__info {', $css);
        $this->assertStringContainsString('min-width: 0;', $css);

        // 3. Details container with column layout and min-width: 0
        $this->assertStringContainsString('.watch-sidebar-creator__details {', $css);
        $this->assertStringContainsString('flex-direction: column;', $css);
        $this->assertStringContainsString('min-width: 0;', $css);

        // 4. Name & handle text truncation
        $this->assertStringContainsString('.watch-sidebar-creator__name {', $css);
        $this->assertStringContainsString('overflow: hidden;', $css);
        $this->assertStringContainsString('text-overflow: ellipsis;', $css);
        $this->assertStringContainsString('.watch-sidebar-creator__handle {', $css);

        // 5. Action button styling
        $this->assertStringContainsString('.watch-sidebar-creator__action {', $css);
        $this->assertStringContainsString('margin-left: auto;', $css);
        $this->assertStringContainsString('flex-shrink: 0;', $css);
    }

    public function test_frontend_watch_sidebar_uses_member_avatar_and_details_layout(): void
    {
        $jsxPath = base_path('../frontend/src/components/watch/WatchRightSidebar.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        $this->assertStringContainsString("import MemberAvatar from '../common/MemberAvatar';", $jsx);
        $this->assertStringContainsString('<MemberAvatar', $jsx);
        $this->assertStringContainsString('member={creator}', $jsx);
        $this->assertStringContainsString('watch-sidebar-creator__details', $jsx);
        $this->assertStringContainsString('watch-sidebar-creator__name', $jsx);
        $this->assertStringContainsString('watch-sidebar-creator__handle', $jsx);
        $this->assertStringContainsString('watch-sidebar-creator__action', $jsx);
    }
}
