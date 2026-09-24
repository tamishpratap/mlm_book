<?php

namespace Tests\Feature;

use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDisconnectionsPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_01_blocked_users_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/member/blocked-users');
        $response->assertUnauthorized();
    }

    public function test_02_disconnections_endpoint_returns_disconnected_members(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('Disconnected User B');

        // userA disconnects userB
        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/blocked-users');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('disconnected_members.data.0.id', $userB->id)
            ->assertJsonPath('disconnected_members.data.0.name', 'Disconnected User B');
    }

    public function test_03_unblock_action_removes_disconnection(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('Disconnected User B');

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->deleteJson("/api/member/people/{$userB->id}/unblock");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('blocked_users', [
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);
    }

    public function test_04_member_with_photo_and_cover_serialized_properly(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userWithBoth = $this->createVerifiedMember('Member Both Photos');
        $userWithBoth->update([
            'profile_photo' => 'uploads/profile/test_profile_123.png',
            'cover_photo' => 'uploads/cover/test_cover_123.png',
        ]);

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userWithBoth->id,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/blocked-users');

        $response->assertOk();
        $item = $response->json('disconnected_members.data.0');
        $this->assertSame('uploads/profile/test_profile_123.png', $item['profile_photo']);
        $this->assertSame('uploads/cover/test_cover_123.png', $item['cover_photo']);
    }

    public function test_05_member_with_no_profile_photo_handles_cleanly(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userNoPhoto = $this->createVerifiedMember('Member No Photo');
        $userNoPhoto->update([
            'profile_photo' => null,
            'cover_photo' => null,
        ]);

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userNoPhoto->id,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/blocked-users');

        $response->assertOk();
        $item = $response->json('disconnected_members.data.0');
        $this->assertNull($item['profile_photo']);
        $this->assertNull($item['cover_photo']);
    }

    public function test_06_long_name_and_username_are_preserved_safely(): void
    {
        $longName = 'Bartholomew Alexander Christopher Montgomery';
        $longUsername = 'bartholomew_alexander_christopher_montgomery_888';

        $userA = $this->createVerifiedMember('User A');
        $userLong = $this->createVerifiedMember($longName);
        $userLong->update(['user_id' => $longUsername]);

        BlockedUser::create([
            'member_id' => $userA->id,
            'blocked_member_id' => $userLong->id,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/blocked-users');

        $response->assertOk();
        $item = $response->json('disconnected_members.data.0');
        $this->assertSame($longName, $item['name']);
        $this->assertSame($longUsername, $item['user_id']);
    }

    public function test_07_disconnect_deletes_existing_friendship(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $userB = $this->createVerifiedMember('User B');

        [$one, $two] = Friendship::normalizePair($userA->id, $userB->id);
        $friendship = Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $userA->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->postJson("/api/member/people/{$userB->id}/block");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);

        $this->assertDatabaseHas('blocked_users', [
            'member_id' => $userA->id,
            'blocked_member_id' => $userB->id,
        ]);
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
}
