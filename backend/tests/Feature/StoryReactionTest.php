<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use App\Models\Story;
use App\Models\StoryLike;
use App\Models\StoryReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoryReactionTest extends TestCase
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

    private function createStory(Member $author, string $caption = 'Sample story caption', $expiresAt = null): Story
    {
        return $author->stories()->create([
            'caption' => $caption,
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/test.jpg',
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);
    }

    public function test_unauthenticated_user_cannot_react_to_story(): void
    {
        $author = $this->createMember('Story Author');
        $story = $this->createStory($author);

        $response = $this->postJson("/api/member/stories/{$story->id}/react", [
            'reaction' => 'like',
        ]);

        $response->assertUnauthorized();
    }

    public function test_non_connected_member_cannot_react_to_story(): void
    {
        $author = $this->createMember('Story Author');
        $stranger = $this->createMember('Stranger Member');
        $story = $this->createStory($author);

        $response = $this->actingAs($stranger, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", [
                'reaction' => 'love',
            ]);

        $response->assertForbidden();
    }

    public function test_all_six_canonical_reactions_can_be_applied_and_persisted(): void
    {
        $author = $this->createMember('Story Author');
        $friend = $this->createMember('Story Friend');
        $this->createFriendship($author, $friend);
        $story = $this->createStory($author);

        $reactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

        foreach ($reactions as $reactionType) {
            $response = $this->actingAs($friend, 'member')
                ->postJson("/api/member/stories/{$story->id}/react", [
                    'reaction' => $reactionType,
                ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('story_id', $story->id)
                ->assertJsonPath('user_reaction', $reactionType)
                ->assertJsonPath('reactions_count', 1);

            $this->assertDatabaseHas('story_reactions', [
                'story_id' => $story->id,
                'member_id' => $friend->id,
                'reaction' => $reactionType,
            ]);

            // If reaction is 'like', StoryLike must also exist; otherwise, must not exist
            if ($reactionType === 'like') {
                $this->assertDatabaseHas('story_likes', [
                    'story_id' => $story->id,
                    'member_id' => $friend->id,
                ]);
            } else {
                $this->assertDatabaseMissing('story_likes', [
                    'story_id' => $story->id,
                    'member_id' => $friend->id,
                ]);
            }

            // Verify GET /api/member/stories/{story} returns the active reaction
            $showResponse = $this->actingAs($friend, 'member')
                ->getJson("/api/member/stories/{$story->id}");

            $showResponse->assertOk()
                ->assertJsonPath('user_reaction', $reactionType)
                ->assertJsonPath('reactions_count', 1);
        }
    }

    public function test_switching_reaction_replaces_previous_reaction_and_keeps_count_at_one(): void
    {
        $author = $this->createMember('Story Author');
        $friend = $this->createMember('Story Friend');
        $this->createFriendship($author, $friend);
        $story = $this->createStory($author);

        // First react with like
        $this->actingAs($friend, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", ['reaction' => 'like'])
            ->assertOk()
            ->assertJsonPath('user_reaction', 'like')
            ->assertJsonPath('reactions_count', 1);

        $this->assertSame(1, StoryReaction::where('story_id', $story->id)->count());
        $this->assertSame(1, StoryLike::where('story_id', $story->id)->count());

        // Switch to love
        $this->actingAs($friend, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", ['reaction' => 'love'])
            ->assertOk()
            ->assertJsonPath('user_reaction', 'love')
            ->assertJsonPath('reactions_count', 1);

        $this->assertSame(1, StoryReaction::where('story_id', $story->id)->count());
        $this->assertSame(0, StoryLike::where('story_id', $story->id)->count());
        $this->assertDatabaseHas('story_reactions', [
            'story_id' => $story->id,
            'member_id' => $friend->id,
            'reaction' => 'love',
        ]);
    }

    public function test_toggling_same_reaction_removes_reaction(): void
    {
        $author = $this->createMember('Story Author');
        $friend = $this->createMember('Story Friend');
        $this->createFriendship($author, $friend);
        $story = $this->createStory($author);

        // React with wow
        $this->actingAs($friend, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", ['reaction' => 'wow'])
            ->assertOk()
            ->assertJsonPath('user_reaction', 'wow')
            ->assertJsonPath('reactions_count', 1);

        // React with wow again (toggle off)
        $this->actingAs($friend, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", ['reaction' => 'wow'])
            ->assertOk()
            ->assertJsonPath('user_reaction', null)
            ->assertJsonPath('reactions_count', 0);

        $this->assertSame(0, StoryReaction::where('story_id', $story->id)->count());
    }

    public function test_invalid_reaction_type_returns_validation_error(): void
    {
        $author = $this->createMember('Story Author');
        $friend = $this->createMember('Story Friend');
        $this->createFriendship($author, $friend);
        $story = $this->createStory($author);

        $response = $this->actingAs($friend, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", [
                'reaction' => 'dislike',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reaction']);
    }

    public function test_cannot_react_to_expired_story(): void
    {
        $author = $this->createMember('Story Author');
        $friend = $this->createMember('Story Friend');
        $this->createFriendship($author, $friend);
        $expiredStory = $this->createStory($author, 'Expired story', now()->subHour());

        $response = $this->actingAs($friend, 'member')
            ->postJson("/api/member/stories/{$expiredStory->id}/react", [
                'reaction' => 'like',
            ]);

        $response->assertNotFound();
    }

    public function test_story_author_can_react_to_their_own_story_without_self_notification(): void
    {
        $author = $this->createMember('Story Author');
        $story = $this->createStory($author);

        $response = $this->actingAs($author, 'member')
            ->postJson("/api/member/stories/{$story->id}/react", [
                'reaction' => 'love',
            ]);

        $response->assertOk()
            ->assertJsonPath('user_reaction', 'love')
            ->assertJsonPath('reactions_count', 1);

        $this->assertDatabaseCount('notifications', 0);
    }
}
