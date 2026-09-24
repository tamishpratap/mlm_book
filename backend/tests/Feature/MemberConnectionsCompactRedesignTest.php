<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberConnectionsCompactRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_01_my_connections_api_returns_paginated_friends(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $friend1 = $this->createVerifiedMember('Friend One');
        $friend2 = $this->createVerifiedMember('Friend Two');

        $this->createAcceptedFriendship($userA, $friend1);
        $this->createAcceptedFriendship($userA, $friend2);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friends');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 2);

        $friendNames = collect($response->json('friends.data'))->pluck('name')->all();
        $this->assertContains('Friend One', $friendNames);
        $this->assertContains('Friend Two', $friendNames);
    }

    public function test_02_suggestions_api_returns_verified_members_with_mutuals(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('User B');
        $mutual1 = $this->createVerifiedMember('Mutual One');

        // User A and User B share Mutual One
        $this->createAcceptedFriendship($userA, $mutual1);
        $this->createAcceptedFriendship($userB, $mutual1);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/people/suggestions');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $suggestions = collect($response->json('suggestions.data'));
        $userBSuggestion = $suggestions->firstWhere('id', $userB->id);

        $this->assertNotNull($userBSuggestion);
        $this->assertSame(1, $userBSuggestion['mutual_count']);
        $this->assertCount(1, $userBSuggestion['mutual_friends']);
        $this->assertSame('Mutual One', $userBSuggestion['mutual_friends'][0]['name']);
    }

    public function test_03_unverified_member_is_excluded_from_suggestions(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $unverified = Member::create([
            'name' => 'Unverified Target',
            'user_id' => 'unverified_target_99',
            'email' => 'unverified@example.com',
            'phone' => '+919999900000',
            'mobile_verified_at' => null, // UNVERIFIED
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/people/suggestions');

        $response->assertOk();
        $suggestionIds = collect($response->json('suggestions.data'))->pluck('id')->all();
        $this->assertNotContains($unverified->id, $suggestionIds);
    }

    public function test_04_connect_action_sends_request_to_suggestion(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('User B');

        $response = $this->actingAs($userA, 'member')
            ->postJson("/api/member/friends/request/{$userB->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state', 'pending_sent');

        $this->assertDatabaseHas('friendships', [
            'requested_by_id' => $userA->id,
            'status' => Friendship::STATUS_PENDING,
        ]);
    }

    public function test_05_connect_to_unverified_member_is_strictly_blocked(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $unverified = Member::create([
            'name' => 'Unverified Victim',
            'user_id' => 'unverified_victim_99',
            'email' => 'victim@example.com',
            'phone' => '+919999911111',
            'mobile_verified_at' => null,
            'password' => 'secret123',
        ]);

        $response = $this->actingAs($userA, 'member')
            ->postJson("/api/member/friends/request/{$unverified->id}");

        $response->assertForbidden();
    }

    public function test_06_long_name_and_username_handled_safely(): void
    {
        $longName = 'Dr. Alexander Bartholomew Montgomery-Cunningham III';
        $longUsername = 'alexander_bartholomew_montgomery_cunningham_998877';

        $userA = $this->createVerifiedMember('User A');
        $friend = $this->createVerifiedMember($longName);
        $friend->update(['user_id' => $longUsername]);

        $this->createAcceptedFriendship($userA, $friend);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friends');

        $response->assertOk();
        $friendItem = collect($response->json('friends.data'))->firstWhere('id', $friend->id);
        $this->assertNotNull($friendItem);
        $this->assertSame($longName, $friendItem['name']);
        $this->assertSame($longUsername, $friendItem['user_id']);
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
}
