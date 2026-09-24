<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostReactionEmojiPickerInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Member User',
            'user_id' => 'user_' . uniqid(),
            'email' => 'user-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => null,
        ], $attributes));
    }

    private function createPost(Member $author, string $body = 'Test Post Content'): Post
    {
        return Post::create([
            'member_id' => $author->id,
            'body' => $body,
            'media_type' => null,
            'media_path' => null,
        ]);
    }

    public function test_reaction_api_supports_all_six_emoji_reactions(): void
    {
        $author = $this->createMember(['name' => 'Author']);
        $viewer = $this->createMember(['name' => 'Viewer']);
        $post = $this->createPost($author);

        $this->actingAs($viewer, 'member');

        $reactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

        foreach ($reactions as $reactionType) {
            $response = $this->postJson("/member/posts/{$post->id}/react", [
                'reaction' => $reactionType,
            ]);

            $response->assertOk();
            $response->assertJson([
                'success' => true,
                'user_reaction' => $reactionType,
            ]);

            $this->assertDatabaseHas('post_reactions', [
                'post_id' => $post->id,
                'member_id' => $viewer->id,
                'reaction' => $reactionType,
            ]);
        }
    }

    public function test_reaction_toggle_off_behavior(): void
    {
        $author = $this->createMember(['name' => 'Author']);
        $viewer = $this->createMember(['name' => 'Viewer']);
        $post = $this->createPost($author);

        $this->actingAs($viewer, 'member');

        // React 'love'
        $response1 = $this->postJson("/member/posts/{$post->id}/react", ['reaction' => 'love']);
        $response1->assertOk();
        $this->assertEquals('love', $response1->json('user_reaction'));

        // Toggle 'love' off
        $response2 = $this->postJson("/member/posts/{$post->id}/react", ['reaction' => 'love']);
        $response2->assertOk();
        $this->assertNull($response2->json('user_reaction'));

        $this->assertDatabaseMissing('post_reactions', [
            'post_id' => $post->id,
            'member_id' => $viewer->id,
        ]);
    }

    public function test_css_pointer_bridge_defined_for_reaction_picker(): void
    {
        $cssPath = base_path('../frontend/src/styles/member-posts.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // Verify .post-reaction-picker base styles
        $this->assertStringContainsString('.post-reaction-picker {', $css);
        $this->assertStringContainsString('.post-reaction-picker.is-visible {', $css);

        // Verify the safe invisible pointer bridge ::before pseudo-element
        $this->assertStringContainsString('.post-reaction-picker::before {', $css);
        $this->assertStringContainsString('content: \'\';', $css);
        $this->assertStringContainsString('pointer-events: auto;', $css);
        $this->assertStringContainsString('background: transparent;', $css);
    }

    public function test_react_post_card_contains_graceful_close_and_pointer_handlers(): void
    {
        $jsxPath = base_path('../frontend/src/components/posts/PostCard.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // Verify graceful close timer ref exists
        $this->assertStringContainsString('pickerCloseTimerRef = useRef(null);', $jsx);
        $this->assertStringContainsString('pickerOpenTimerRef = useRef(null);', $jsx);
        $this->assertStringContainsString('touchLongPressTimerRef = useRef(null);', $jsx);

        // Verify clearPickerTimers helper
        $this->assertStringContainsString('const clearPickerTimers = () => {', $jsx);

        // Verify close delay of 320ms
        $this->assertStringContainsString('setTimeout(() => {', $jsx);
        $this->assertStringContainsString('setPickerOpen(false);', $jsx);
        $this->assertStringContainsString('}, 320);', $jsx);

        // Verify touch listeners for mobile outside-touch
        $this->assertStringContainsString("document.addEventListener('touchstart', handleClickOutside);", $jsx);

        // Verify onMouseEnter & onMouseLeave applied to picker container
        $this->assertStringContainsString('onMouseEnter={handleMouseEnterLike}', $jsx);
        $this->assertStringContainsString('onMouseLeave={handleMouseLeaveLike}', $jsx);
    }

    public function test_watch_video_card_contains_graceful_close_and_pointer_handlers(): void
    {
        $jsxPath = base_path('../frontend/src/components/watch/WatchVideoCard.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        $this->assertStringContainsString('pickerCloseTimerRef = useRef(null);', $jsx);
        $this->assertStringContainsString('clearPickerTimers();', $jsx);
        $this->assertStringContainsString('}, 320);', $jsx);
        $this->assertStringContainsString("document.addEventListener('touchstart', handleClickOutside);", $jsx);
    }

    public function test_comment_item_contains_graceful_close_and_pointer_handlers(): void
    {
        $jsxPath = base_path('../frontend/src/components/posts/CommentItem.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        $this->assertStringContainsString('pickerCloseTimerRef = useRef(null);', $jsx);
        $this->assertStringContainsString('clearPickerTimers();', $jsx);
        $this->assertStringContainsString('}, 320);', $jsx);
        $this->assertStringContainsString("document.addEventListener('touchstart', handleClickOutside);", $jsx);
    }
}
