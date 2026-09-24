<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionRequestsCompactRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_01_requests_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/member/friend-requests');
        $response->assertUnauthorized();
    }

    public function test_02_requests_endpoint_returns_incoming_and_outgoing_with_mutual_data(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('User B');
        $mutual1 = $this->createVerifiedMember('Mutual One');
        $mutual2 = $this->createVerifiedMember('Mutual Two');

        // userA is friends with mutual1 and mutual2
        $this->createAcceptedFriendship($userA, $mutual1);
        $this->createAcceptedFriendship($userA, $mutual2);

        // userB is friends with mutual1 and mutual2
        $this->createAcceptedFriendship($userB, $mutual1);
        $this->createAcceptedFriendship($userB, $mutual2);

        // userB sends friend request to userA (Incoming for userA)
        $req = $this->createPendingFriendship($userB, $userA);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friend-requests');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('incoming_count', 1)
            ->assertJsonPath('outgoing_count', 0)
            ->assertJsonPath('incoming_requests.0.friend.id', $userB->id)
            ->assertJsonPath('incoming_requests.0.friend.name', 'User B')
            ->assertJsonPath('incoming_requests.0.mutual_count', 2);

        $mutualFriends = $response->json('incoming_requests.0.mutual_friends');
        $this->assertCount(2, $mutualFriends);
    }

    public function test_03_requests_with_zero_mutual_connections(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('User B');

        // userB sends friend request to userA with zero mutual connections
        $this->createPendingFriendship($userB, $userA);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friend-requests');

        $response->assertOk()
            ->assertJsonPath('incoming_count', 1)
            ->assertJsonPath('incoming_requests.0.mutual_count', 0)
            ->assertJsonPath('incoming_requests.0.mutual_friends', []);
    }

    public function test_04_outgoing_sent_requests_are_segregated_and_include_mutuals(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('User B');

        // userA sends request to userB (Outgoing for userA)
        $this->createPendingFriendship($userA, $userB);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friend-requests');

        $response->assertOk()
            ->assertJsonPath('incoming_count', 0)
            ->assertJsonPath('outgoing_count', 1)
            ->assertJsonPath('outgoing_requests.0.friend.id', $userB->id)
            ->assertJsonPath('outgoing_requests.0.friendship_state', 'pending_sent');
    }

    public function test_05_accept_incoming_request(): void
    {
        $sender = $this->createVerifiedMember('Sender User');
        $receiver = $this->createVerifiedMember('Receiver User');

        $friendship = $this->createPendingFriendship($sender, $receiver);

        $response = $this->actingAs($receiver, 'member')
            ->postJson("/api/member/friend-requests/{$friendship->id}/accept");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'friends');

        $friendship->refresh();
        $this->assertSame(Friendship::STATUS_ACCEPTED, $friendship->status);
    }

    public function test_06_reject_incoming_request(): void
    {
        $sender = $this->createVerifiedMember('Sender User');
        $receiver = $this->createVerifiedMember('Receiver User');

        $friendship = $this->createPendingFriendship($sender, $receiver);

        $response = $this->actingAs($receiver, 'member')
            ->postJson("/api/member/friend-requests/{$friendship->id}/reject");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'none');

        $friendship->refresh();
        $this->assertSame(Friendship::STATUS_REJECTED, $friendship->status);
    }

    public function test_07_cancel_sent_request(): void
    {
        $sender = $this->createVerifiedMember('Sender User');
        $receiver = $this->createVerifiedMember('Receiver User');

        $friendship = $this->createPendingFriendship($sender, $receiver);

        $response = $this->actingAs($sender, 'member')
            ->deleteJson("/api/member/friend-requests/{$friendship->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'none');

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);
    }

    public function test_08_long_member_name_and_username_handled_safely(): void
    {
        $longName = 'Very Long Name Example With Multiple Surnames And Middle Names';
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember($longName);
        $userB->update(['user_id' => 'very_long_custom_username_1234567890']);

        $this->createPendingFriendship($userB, $userA);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friend-requests');

        $response->assertOk()
            ->assertJsonPath('incoming_requests.0.friend.name', $longName)
            ->assertJsonPath('incoming_requests.0.friend.user_id', 'very_long_custom_username_1234567890');
    }

    public function test_09_sender_cannot_accept_own_sent_request(): void
    {
        $sender = $this->createVerifiedMember('Sender User');
        $receiver = $this->createVerifiedMember('Receiver User');

        $friendship = $this->createPendingFriendship($sender, $receiver);

        $response = $this->actingAs($sender, 'member')
            ->postJson("/api/member/friend-requests/{$friendship->id}/accept");

        $response->assertForbidden();
    }

    public function test_10_stranger_cannot_accept_or_cancel_others_request(): void
    {
        $sender = $this->createVerifiedMember('Sender User');
        $receiver = $this->createVerifiedMember('Receiver User');
        $stranger = $this->createVerifiedMember('Stranger User');

        $friendship = $this->createPendingFriendship($sender, $receiver);

        $this->actingAs($stranger, 'member')
            ->postJson("/api/member/friend-requests/{$friendship->id}/accept")
            ->assertForbidden();

        $this->actingAs($stranger, 'member')
            ->deleteJson("/api/member/friend-requests/{$friendship->id}/cancel")
            ->assertForbidden();
    }

    private function createVerifiedMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => str($name)->slug() . '-' . str()->random(6) . '@example.com',
            'phone' => '+9198' . random_int(10000000, 99999999),
            'mobile_verified_at' => now(),
            'password' => 'secret123',
        ]);
    }

    private function createAcceptedFriendship(Member $first, Member $second): Friendship
    {
        [$one, $two] = Friendship::normalizePair($first->id, $second->id);

        return Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $first->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    private function createPendingFriendship(Member $sender, Member $receiver): Friendship
    {
        [$one, $two] = Friendship::normalizePair($sender->id, $receiver->id);

        return Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $sender->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
    }
}
