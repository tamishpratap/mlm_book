<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchSidebarConnectionVerificationRulesTest extends TestCase
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

    private function establishPendingConnection(Member $sender, Member $receiver): Friendship
    {
        [$m1, $m2] = Friendship::normalizePair($sender->id, $receiver->id);

        return Friendship::create([
            'member_one_id' => $m1,
            'member_two_id' => $m2,
            'requested_by_id' => $sender->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
    }

    public function test_recent_videos_sidebar_follows_strict_connection_rules(): void
    {
        // Setup User A
        $userA = $this->createMember(['name' => 'User A']);

        // User B: Accepted Connection
        $userB = $this->createMember(['name' => 'User B']);
        $this->establishAcceptedConnection($userA, $userB);

        // User C: Not Connected
        $userC = $this->createMember(['name' => 'User C']);

        // User D: Pending Connection
        $userD = $this->createMember(['name' => 'User D']);
        $this->establishPendingConnection($userA, $userD);

        // User E: Disconnected / Blocked
        $userE = $this->createMember(['name' => 'User E']);
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userE->id,
        ]);

        // User F: Unverified member (even if friendship record exists)
        $userF = $this->createMember([
            'name' => 'User F',
            'mobile_verified_at' => null,
        ]);
        $this->establishAcceptedConnection($userA, $userF);

        // Create videos
        $videoA = $this->createVideoPost($userA, 'Video by User A (Self)');
        $videoB = $this->createVideoPost($userB, 'Video by User B (Connected)');
        $videoC = $this->createVideoPost($userC, 'Video by User C (Not Connected)');
        $videoD = $this->createVideoPost($userD, 'Video by User D (Pending)');
        $videoE = $this->createVideoPost($userE, 'Video by User E (Disconnected)');
        $videoF = $this->createVideoPost($userF, 'Video by User F (Unverified)');

        $this->actingAs($userA, 'member');

        $response = $this->getJson(route('member.watch.index'));

        $response->assertOk();
        $recentVideos = $response->json('recent_videos');
        $recentIds = collect($recentVideos)->pluck('id')->all();

        // User B (Connected) -> SHOW
        $this->assertContains($videoB->id, $recentIds);

        // User A (Self) -> SHOW
        $this->assertContains($videoA->id, $recentIds);

        // User C (Not connected) -> HIDE
        $this->assertNotContains($videoC->id, $recentIds);

        // User D (Pending) -> HIDE
        $this->assertNotContains($videoD->id, $recentIds);

        // User E (Disconnected) -> HIDE
        $this->assertNotContains($videoE->id, $recentIds);

        // User F (Unverified) -> HIDE
        $this->assertNotContains($videoF->id, $recentIds);
    }

    public function test_suggested_creators_excludes_connected_pending_disconnected_and_unverified(): void
    {
        $userA = $this->createMember(['name' => 'User A']);

        // User B: Accepted Connection (should NOT be suggested)
        $userB = $this->createMember(['name' => 'User B']);
        $this->establishAcceptedConnection($userA, $userB);
        $this->createVideoPost($userB);

        // User D: Pending Connection (should NOT be suggested)
        $userD = $this->createMember(['name' => 'User D']);
        $this->establishPendingConnection($userA, $userD);
        $this->createVideoPost($userD);

        // User E: Disconnected / Blocked (should NOT be suggested)
        $userE = $this->createMember(['name' => 'User E']);
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userE->id,
        ]);
        $this->createVideoPost($userE);

        // User F: Unverified (should NOT be suggested)
        $userF = $this->createMember([
            'name' => 'User F',
            'mobile_verified_at' => null,
        ]);
        $this->createVideoPost($userF);

        // User G: Valid, verified, has video, no relationship -> SHOULD BE SUGGESTED
        $userG = $this->createMember(['name' => 'Eligible Creator G']);
        $this->createVideoPost($userG);

        $this->actingAs($userA, 'member');

        $response = $this->getJson(route('member.watch.index'));

        $response->assertOk();
        $suggested = $response->json('suggested_creators');
        $suggestedIds = collect($suggested)->pluck('id')->all();

        $this->assertNotContains($userA->id, $suggestedIds);
        $this->assertNotContains($userB->id, $suggestedIds);
        $this->assertNotContains($userD->id, $suggestedIds);
        $this->assertNotContains($userE->id, $suggestedIds);
        $this->assertNotContains($userF->id, $suggestedIds);
        $this->assertContains($userG->id, $suggestedIds);
    }

    public function test_trending_videos_enforces_verification_and_excludes_blocked(): void
    {
        $userA = $this->createMember(['name' => 'User A']);

        // Verified public creator
        $userH = $this->createMember(['name' => 'Trending Creator H']);
        $videoH = $this->createVideoPost($userH, 'Viral Video H');

        // Blocked creator (even with video)
        $userBlocked = $this->createMember(['name' => 'Blocked Creator']);
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userBlocked->id,
        ]);
        $videoBlocked = $this->createVideoPost($userBlocked, 'Blocked Video');

        // Unverified creator
        $userUnverified = $this->createMember([
            'name' => 'Unverified Creator',
            'mobile_verified_at' => null,
        ]);
        $videoUnverified = $this->createVideoPost($userUnverified, 'Unverified Video');

        $this->actingAs($userA, 'member');

        $response = $this->getJson(route('member.watch.index'));

        $response->assertOk();
        $trending = $response->json('trending_videos');
        $trendingIds = collect($trending)->pluck('id')->all();

        $this->assertContains($videoH->id, $trendingIds);
        $this->assertNotContains($videoBlocked->id, $trendingIds);
        $this->assertNotContains($videoUnverified->id, $trendingIds);
    }
}
