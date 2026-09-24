<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchFeedConnectionsTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, bool $verified = true): Member
    {
        return Member::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => $verified ? now() : null,
        ]);
    }

    private function createVideoPost(Member $author, string $title): Post
    {
        $page = \App\Models\BusinessPage::firstOrCreate(
            ['member_id' => $author->id],
            [
                'page_name' => 'Page for ' . $author->name,
                'page_username' => 'page_' . $author->id . '_' . uniqid(),
                'slug' => 'page-' . $author->id . '-' . uniqid(),
                'category' => 'Technology',
                'status' => 'active',
            ]
        );

        $post = Post::create([
            'member_id' => $author->id,
            'business_page_id' => $page->id,
            'body' => $title,
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/' . uniqid() . '.mp4',
        ]);

        \App\Models\AdCampaign::create([
            'member_id' => $author->id,
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

    private function establishPendingConnection(Member $from, Member $to): Friendship
    {
        [$m1, $m2] = Friendship::normalizePair($from->id, $to->id);

        return Friendship::create([
            'member_one_id' => $m1,
            'member_two_id' => $m2,
            'requested_by_id' => $from->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
    }

    public function test_watch_feed_shows_videos_only_from_accepted_connections_and_self(): void
    {
        // Scenario from Section 28:
        // User A = authenticated current user
        // User B = accepted connection
        // User C = pending connection
        // User D = no connection
        // User E = disconnected
        $userA = $this->createMember('Tamish User A');
        $userB = $this->createMember('Anand User B');
        $userC = $this->createMember('Devendra User C');
        $userD = $this->createMember('Sumit User D');
        $userE = $this->createMember('Varsha User E');

        // Establish relationships
        $this->establishAcceptedConnection($userA, $userB);
        $this->establishPendingConnection($userA, $userC);
        // userD has no connection
        // userE is disconnected (friendship removed, BlockedUser recorded)
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userE->id,
        ]);

        // Create videos
        $videoA = $this->createVideoPost($userA, 'Tamish Video A');
        $videoB = $this->createVideoPost($userB, 'Anand Video B');
        $videoC = $this->createVideoPost($userC, 'Devendra Video C');
        $videoD = $this->createVideoPost($userD, 'Sumit Video D');
        $videoE = $this->createVideoPost($userE, 'Varsha Video E');

        // Request Watch feed for User A
        $this->actingAs($userA, 'member');
        $response = $this->getJson('/api/member/watch?filter=all');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $postIds = collect($response->json('posts'))->pluck('id')->all();

        // Must include Video A (self) and Video B (accepted connection)
        $this->assertContains($videoA->id, $postIds);
        $this->assertContains($videoB->id, $postIds);

        // Must NOT include Video C (pending), Video D (no connection), Video E (disconnected)
        $this->assertNotContains($videoC->id, $postIds);
        $this->assertNotContains($videoD->id, $postIds);
        $this->assertNotContains($videoE->id, $postIds);
    }

    public function test_connection_acceptance_makes_creator_videos_visible(): void
    {
        // Scenario from Section 29
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        $videoB = $this->createVideoPost($userB, 'Video B');

        // 1. Initially not connected: Video B must NOT be visible to A
        $this->actingAs($userA, 'member');
        $resBefore = $this->getJson('/api/member/watch?filter=all');
        $idsBefore = collect($resBefore->json('posts'))->pluck('id')->all();
        $this->assertNotContains($videoB->id, $idsBefore);

        // 2. Accept connection A <-> B
        $this->establishAcceptedConnection($userA, $userB);

        // 3. Now Video B MUST be visible to A
        $resAfter = $this->getJson('/api/member/watch?filter=all');
        $idsAfter = collect($resAfter->json('posts'))->pluck('id')->all();
        $this->assertContains($videoB->id, $idsAfter);
    }

    public function test_disconnecting_removes_creator_videos_from_watch_feed(): void
    {
        // Scenario from Section 30
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        $videoB = $this->createVideoPost($userB, 'Video B');
        $friendship = $this->establishAcceptedConnection($userA, $userB);

        // 1. Connected: Video B is visible to A
        $this->actingAs($userA, 'member');
        $resBefore = $this->getJson('/api/member/watch?filter=all');
        $idsBefore = collect($resBefore->json('posts'))->pluck('id')->all();
        $this->assertContains($videoB->id, $idsBefore);

        // 2. Disconnect A and B (delete friendship, record BlockedUser disconnection)
        $friendship->delete();
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        // 3. After disconnect: Video B must NO LONGER be visible to A
        $resAfter = $this->getJson('/api/member/watch?filter=all');
        $idsAfter = collect($resAfter->json('posts'))->pluck('id')->all();
        $this->assertNotContains($videoB->id, $idsAfter);
    }

    public function test_pending_connection_request_does_not_reveal_videos(): void
    {
        // Scenario from Section 31
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        $videoB = $this->createVideoPost($userB, 'Video B');

        // A sends request to B (pending)
        $this->establishPendingConnection($userA, $userB);

        $this->actingAs($userA, 'member');
        $response = $this->getJson('/api/member/watch?filter=all');
        $postIds = collect($response->json('posts'))->pluck('id')->all();

        $this->assertNotContains($videoB->id, $postIds);
    }

    public function test_unverified_member_video_does_not_appear_to_other_member(): void
    {
        // Scenario from Section 32
        $userA = $this->createMember('User A', true);
        $unverifiedB = $this->createMember('Unverified B', false); // Not verified

        $videoB = $this->createVideoPost($unverifiedB, 'Unverified Video B');

        // Even with an accepted friendship row, unverified member must not be exposed
        $this->establishAcceptedConnection($userA, $unverifiedB);

        $this->actingAs($userA, 'member');
        $response = $this->getJson('/api/member/watch?filter=all');
        $postIds = collect($response->json('posts'))->pluck('id')->all();

        $this->assertNotContains($videoB->id, $postIds);
    }

    public function test_feed_isolation_between_different_users(): void
    {
        // Scenario from Section 33
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');
        $friendOfA = $this->createMember('Friend of A');
        $friendOfB = $this->createMember('Friend of B');

        $this->establishAcceptedConnection($userA, $friendOfA);
        $this->establishAcceptedConnection($userB, $friendOfB);

        $videoFriendA = $this->createVideoPost($friendOfA, 'Friend A Video');
        $videoFriendB = $this->createVideoPost($friendOfB, 'Friend B Video');

        // User A should see Friend A's video, NOT Friend B's video
        $this->actingAs($userA, 'member');
        $resA = $this->getJson('/api/member/watch?filter=all');
        $idsA = collect($resA->json('posts'))->pluck('id')->all();
        $this->assertContains($videoFriendA->id, $idsA);
        $this->assertNotContains($videoFriendB->id, $idsA);

        // User B should see Friend B's video, NOT Friend A's video
        $this->actingAs($userB, 'member');
        $resB = $this->getJson('/api/member/watch?filter=all');
        $idsB = collect($resB->json('posts'))->pluck('id')->all();
        $this->assertContains($videoFriendB->id, $idsB);
        $this->assertNotContains($videoFriendA->id, $idsB);
    }

    public function test_my_videos_tab_shows_only_authenticated_users_videos(): void
    {
        // Scenario from Section 17
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');
        $this->establishAcceptedConnection($userA, $userB);

        $videoA = $this->createVideoPost($userA, 'Video A');
        $videoB = $this->createVideoPost($userB, 'Video B');

        $this->actingAs($userA, 'member');
        $response = $this->getJson('/api/member/watch?filter=my_videos');
        $postIds = collect($response->json('posts'))->pluck('id')->all();

        $this->assertContains($videoA->id, $postIds);
        $this->assertNotContains($videoB->id, $postIds);
    }
}
