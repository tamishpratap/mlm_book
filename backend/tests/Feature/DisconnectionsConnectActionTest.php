<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use App\Notifications\FriendRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DisconnectionsConnectActionTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, bool $verified = true): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'phone' => '+9198' . random_int(10000000, 99999999),
            'password' => 'secret123',
            'mobile_verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_disconnections_endpoint_returns_friendship_state_none(): void
    {
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        // User A disconnected User B
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        $this->actingAs($userA, 'member');
        $response = $this->getJson('/api/member/blocked-users');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('disconnected_members.data.0.id', $userB->id)
            ->assertJsonPath('disconnected_members.data.0.friendship_state', 'none');
    }

    public function test_connecting_with_disconnected_member_sends_request_and_preserves_history(): void
    {
        Notification::fake();

        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        // User A disconnected User B
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        $this->actingAs($userA, 'member');

        // Click Connect -> POST /api/member/friends/request/{member}
        $response = $this->postJson("/api/member/friends/request/{$userB->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'pending_sent')
            ->assertJsonPath('member_id', $userB->id);

        // Disconnection record in blocked_users MUST be preserved (Section 10)
        $this->assertDatabaseHas('blocked_users', [
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        // Connection request is created with STATUS_PENDING
        $this->assertDatabaseHas('friendships', [
            'requested_by_id' => $userA->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // Recipient received notification
        Notification::assertSentTo($userB, FriendRequestReceivedNotification::class);

        // Disconnections list now reflects 'pending_sent'
        $disconnectionsResponse = $this->getJson('/api/member/blocked-users');
        $disconnectionsResponse->assertOk()
            ->assertJsonPath('disconnected_members.data.0.friendship_state', 'pending_sent');

        // Request appears in Sent Connection Requests
        $requestsResponse = $this->getJson('/api/member/friends/requests');
        $requestsResponse->assertOk()
            ->assertJsonPath('outgoing_count', 1)
            ->assertJsonPath('outgoing_requests.0.friend.id', $userB->id);
    }

    public function test_duplicate_connect_clicks_do_not_create_duplicate_requests(): void
    {
        Notification::fake();

        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        $this->actingAs($userA, 'member');

        // First click
        $this->postJson("/api/member/friends/request/{$userB->id}")->assertOk();

        // Second click
        $response2 = $this->postJson("/api/member/friends/request/{$userB->id}");
        $response2->assertOk();

        // Ensure only 1 friendship row exists
        [$one, $two] = Friendship::normalizePair($userA->id, $userB->id);
        $this->assertSame(1, Friendship::where('member_one_id', $one)->where('member_two_id', $two)->count());

        // Notification only sent once
        Notification::assertSentToTimes($userB, FriendRequestReceivedNotification::class, 1);
    }

    public function test_cannot_connect_if_target_member_is_unverified(): void
    {
        Notification::fake();

        $userA = $this->createMember('User A', true);
        $unverifiedB = $this->createMember('Unverified B', false); // unverified phone

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $unverifiedB->id,
        ]);

        $this->actingAs($userA, 'member');

        $response = $this->postJson("/api/member/friends/request/{$unverifiedB->id}");

        $response->assertStatus(403);

        // No request created
        $this->assertSame(0, Friendship::count());

        // No notification sent
        Notification::assertNothingSent();
    }

    public function test_cannot_connect_if_target_member_has_blocked_requester(): void
    {
        Notification::fake();

        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        // User B has blocked User A
        BlockedUser::create([
            'member_id' => $userB->id,
            'blocked_member_id' => $userA->id,
        ]);

        $this->actingAs($userA, 'member');

        $response = $this->postJson("/api/member/friends/request/{$userB->id}");

        $response->assertStatus(403);
        $this->assertSame(0, Friendship::count());
        Notification::assertNothingSent();
    }

    public function test_acceptance_flow_makes_members_connected_and_clears_block(): void
    {
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        // A sends request to B
        $this->actingAs($userA, 'member');
        $this->postJson("/api/member/friends/request/{$userB->id}")->assertOk();

        [$one, $two] = Friendship::normalizePair($userA->id, $userB->id);
        $friendship = Friendship::where('member_one_id', $one)->where('member_two_id', $two)->firstOrFail();

        // B accepts request from A
        $this->actingAs($userB, 'member');
        $acceptResponse = $this->postJson("/api/member/friend-requests/{$friendship->id}/accept");
        $acceptResponse->assertOk();

        // Now friendship is accepted
        $this->assertSame(Friendship::STATUS_ACCEPTED, $friendship->fresh()->status);

        // BlockedUser was cleared upon actual acceptance
        $this->assertDatabaseMissing('blocked_users', [
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);
    }

    public function test_cancellation_flow_resets_request_state_and_preserves_disconnection(): void
    {
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        $this->actingAs($userA, 'member');
        $this->postJson("/api/member/friends/request/{$userB->id}")->assertOk();

        [$one, $two] = Friendship::normalizePair($userA->id, $userB->id);
        $friendship = Friendship::where('member_one_id', $one)->where('member_two_id', $two)->firstOrFail();

        // A cancels the request
        $cancelResponse = $this->deleteJson("/api/member/friend-requests/{$friendship->id}/cancel");
        $cancelResponse->assertOk();

        // Disconnection record in blocked_users is still preserved
        $this->assertDatabaseHas('blocked_users', [
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        // Disconnections list shows state 'none' again
        $disconnectionsResponse = $this->getJson('/api/member/blocked-users');
        $disconnectionsResponse->assertOk()
            ->assertJsonPath('disconnected_members.data.0.friendship_state', 'none');
    }

    public function test_connecting_to_one_disconnected_member_does_not_modify_others(): void
    {
        $userA = $this->createMember('User A');
        $userB = $this->createMember('User B');
        $userC = $this->createMember('User C');

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userC->id,
        ]);

        $this->actingAs($userA, 'member');

        // Connect only to User B
        $this->postJson("/api/member/friends/request/{$userB->id}")->assertOk();

        $response = $this->getJson('/api/member/blocked-users');
        $response->assertOk();

        $members = collect($response->json('disconnected_members.data'));
        $itemB = $members->firstWhere('id', $userB->id);
        $itemC = $members->firstWhere('id', $userC->id);

        $this->assertSame('pending_sent', $itemB['friendship_state']);
        $this->assertSame('none', $itemC['friendship_state']);
    }
}
