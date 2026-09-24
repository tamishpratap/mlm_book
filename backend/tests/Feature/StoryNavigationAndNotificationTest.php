<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use App\Models\Story;
use App\Models\StoryView;
use App\Notifications\StoryViewNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoryNavigationAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'email' => str($name)->slug().'-'.Str::lower(Str::random(8)).'@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    private function createFriendship(Member $first, Member $second, string $status = Friendship::STATUS_ACCEPTED): Friendship
    {
        [$memberOneId, $memberTwoId] = Friendship::normalizePair($first->id, $second->id);

        return Friendship::create([
            'member_one_id' => $memberOneId,
            'member_two_id' => $memberTwoId,
            'requested_by_id' => $first->id,
            'status' => $status,
            'accepted_at' => $status === Friendship::STATUS_ACCEPTED ? now() : null,
        ]);
    }

    private function createStory(Member $author, string $caption = 'Sample story', $expiresAt = null, string $mediaType = 'image'): Story
    {
        return $author->stories()->create([
            'caption' => $caption,
            'media_type' => $mediaType,
            'media_path' => 'uploads/stories/images/test.jpg',
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);
    }

    // ==========================================
    // NOTIFICATION & DUPLICATE VIEW TESTS
    // ==========================================

    public function test_first_story_view_records_view_and_sends_one_notification_to_owner(): void
    {
        Notification::fake();

        $author = $this->createMember('Story Owner');
        $viewer = $this->createMember('Viewer A');
        $this->createFriendship($author, $viewer);

        $story = $this->createStory($author, 'First View Story');

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story->id}");

        $response->assertOk();

        // 1 view recorded
        $this->assertDatabaseHas('story_views', [
            'story_id' => $story->id,
            'viewer_member_id' => $viewer->id,
        ]);
        $this->assertSame(1, StoryView::where('story_id', $story->id)->where('viewer_member_id', $viewer->id)->count());

        // 1 notification sent to author
        Notification::assertSentTo(
            $author,
            StoryViewNotification::class,
            function (StoryViewNotification $notification) use ($viewer, $story) {
                return $notification->actor->id === $viewer->id
                    && $notification->story->id === $story->id;
            }
        );
        Notification::assertSentTimes(StoryViewNotification::class, 1);
    }

    public function test_repeated_story_view_by_same_member_does_not_send_duplicate_notification(): void
    {
        Notification::fake();

        $author = $this->createMember('Story Owner');
        $viewer = $this->createMember('Viewer A');
        $this->createFriendship($author, $viewer);

        $story = $this->createStory($author, 'Repeated View Story');

        // First view
        $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story->id}")
            ->assertOk();

        Notification::assertSentTimes(StoryViewNotification::class, 1);

        // Second view (repeat playback)
        $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story->id}")
            ->assertOk();

        // Still exactly 1 notification, no duplicate sent
        Notification::assertSentTimes(StoryViewNotification::class, 1);
        $this->assertSame(1, StoryView::where('story_id', $story->id)->where('viewer_member_id', $viewer->id)->count());
    }

    public function test_viewing_different_story_creates_separate_notification(): void
    {
        Notification::fake();

        $author = $this->createMember('Story Owner');
        $viewer = $this->createMember('Viewer A');
        $this->createFriendship($author, $viewer);

        $story1 = $this->createStory($author, 'Story One');
        $story2 = $this->createStory($author, 'Story Two');

        // View Story 1
        $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story1->id}")
            ->assertOk();

        Notification::assertSentTimes(StoryViewNotification::class, 1);

        // View Story 2
        $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story2->id}")
            ->assertOk();

        // 2 notifications total: one for each story
        Notification::assertSentTimes(StoryViewNotification::class, 2);
    }

    public function test_different_viewers_generate_separate_notifications(): void
    {
        Notification::fake();

        $author = $this->createMember('Story Owner');
        $viewerA = $this->createMember('Viewer A');
        $viewerB = $this->createMember('Viewer B');
        $this->createFriendship($author, $viewerA);
        $this->createFriendship($author, $viewerB);

        $story = $this->createStory($author, 'Shared Story');

        // Viewer A views
        $this->actingAs($viewerA, 'member')
            ->getJson("/api/member/stories/{$story->id}")
            ->assertOk();

        // Viewer B views
        $this->actingAs($viewerB, 'member')
            ->getJson("/api/member/stories/{$story->id}")
            ->assertOk();

        Notification::assertSentTimes(StoryViewNotification::class, 2);
        $this->assertSame(2, StoryView::where('story_id', $story->id)->count());
    }

    public function test_owner_viewing_own_story_does_not_send_notification_and_does_not_record_view(): void
    {
        Notification::fake();

        $author = $this->createMember('Story Owner');
        $story = $this->createStory($author, 'Owner Story');

        $this->actingAs($author, 'member')
            ->getJson("/api/member/stories/{$story->id}")
            ->assertOk();

        Notification::assertNothingSent();
        $this->assertSame(0, StoryView::where('story_id', $story->id)->count());
    }

    // ==========================================
    // BACK & CROSS-MEMBER NAVIGATION TESTS
    // ==========================================

    public function test_back_navigation_within_same_member_returns_previous_story_of_same_member(): void
    {
        $author = $this->createMember('Story Author');
        $viewer = $this->createMember('Story Viewer');
        $this->createFriendship($author, $viewer);

        $story1 = $this->createStory($author, 'Member Story 1');
        $story2 = $this->createStory($author, 'Member Story 2');
        $story3 = $this->createStory($author, 'Member Story 3');

        // On Story 3: back should be Story 2
        $res3 = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story3->id}")
            ->assertOk();
        $this->assertSame($story2->id, $res3->json('previous_story_id'));
        $this->assertNull($res3->json('next_story_id'));

        // On Story 2: back should be Story 1
        $res2 = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$story2->id}")
            ->assertOk();
        $this->assertSame($story1->id, $res2->json('previous_story_id'));
        $this->assertSame($story3->id, $res2->json('next_story_id'));
    }

    public function test_back_navigation_from_first_story_of_member_moves_to_previous_members_latest_story(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');
        $viewer = $this->createMember('Story Viewer');
        $this->createFriendship($memberA, $viewer);
        $this->createFriendship($memberB, $viewer);

        // Member A has Story A1 and Story A2 (created earlier)
        $storyA1 = $this->createStory($memberA, 'A1');
        $storyA2 = $this->createStory($memberA, 'A2');

        // Member B has Story B1 and Story B2 (created later)
        $storyB1 = $this->createStory($memberB, 'B1');
        $storyB2 = $this->createStory($memberB, 'B2');

        // On Member B's first story (B1):
        // Back navigation must return Member A's LATEST story (A2), not A1!
        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$storyB1->id}")
            ->assertOk();

        $this->assertSame($storyA2->id, $response->json('previous_story_id'));
        $this->assertSame($storyB2->id, $response->json('next_story_id'));
    }

    public function test_back_navigation_on_first_story_of_first_member_returns_null_safe_boundary(): void
    {
        $memberA = $this->createMember('Member A');
        $viewer = $this->createMember('Story Viewer');
        $this->createFriendship($memberA, $viewer);

        $storyA1 = $this->createStory($memberA, 'A1');
        $storyA2 = $this->createStory($memberA, 'A2');

        // On first story of first member:
        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$storyA1->id}")
            ->assertOk();

        // Safe boundary: previous_story_id must be null
        $this->assertNull($response->json('previous_story_id'));
        $this->assertSame($storyA2->id, $response->json('next_story_id'));
    }

    public function test_next_navigation_advances_from_last_story_of_member_to_first_story_of_next_member(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');
        $viewer = $this->createMember('Story Viewer');
        $this->createFriendship($memberA, $viewer);
        $this->createFriendship($memberB, $viewer);

        $storyA1 = $this->createStory($memberA, 'A1');
        $storyA2 = $this->createStory($memberA, 'A2');
        $storyB1 = $this->createStory($memberB, 'B1');

        // On Member A's last story (A2):
        // Next should be Member B's first story (B1)
        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/stories/{$storyA2->id}")
            ->assertOk();

        $this->assertSame($storyA1->id, $response->json('previous_story_id'));
        $this->assertSame($storyB1->id, $response->json('next_story_id'));
    }

    public function test_rapid_concurrent_views_generate_at_most_one_notification(): void
    {
        Notification::fake();

        $author = $this->createMember('Story Owner');
        $viewer = $this->createMember('Viewer A');
        $this->createFriendship($author, $viewer);

        $story = $this->createStory($author, 'Rapid View Story');

        // Simulate rapid successive calls (e.g. double-click or multiple tabs)
        $res1 = $this->actingAs($viewer, 'member')->getJson("/api/member/stories/{$story->id}");
        $res2 = $this->actingAs($viewer, 'member')->getJson("/api/member/stories/{$story->id}");

        $res1->assertOk();
        $res2->assertOk();

        // Exactly 1 notification and 1 view record
        Notification::assertSentTimes(StoryViewNotification::class, 1);
        $this->assertSame(1, StoryView::where('story_id', $story->id)->where('viewer_member_id', $viewer->id)->count());
    }
}
