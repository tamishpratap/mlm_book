<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSocialVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 1;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "user_{$unique}_" . \Illuminate\Support\Str::random(5),
            'email' => "user_{$unique}_" . \Illuminate\Support\Str::random(5) . "@example.com",
            'password' => 'password123',
            'phone' => '+1555' . sprintf('%07d', $unique),
            'mobile_verified_at' => now(),
            'country' => 'India',
            'city' => 'Mumbai',
        ], $attributes));
    }

    public function test_01_unverified_member_in_find_friends_not_visible(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer Member']);
        $verifiedTarget = $this->createMember(['name' => 'Verified Target']);
        $unverifiedTarget = $this->createMember([
            'name' => 'Lifeleads India',
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/people/suggestions');

        $response->assertOk();
        $suggestions = collect($response->json('suggestions.data'));

        $this->assertTrue($suggestions->contains('id', $verifiedTarget->id));
        $this->assertFalse($suggestions->contains('id', $unverifiedTarget->id));
    }

    public function test_02_unverified_member_in_new_connections_not_visible(): void
    {
        $viewer = $this->createMember();
        $unverifiedTarget = $this->createMember(['mobile_verified_at' => null]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/people/suggestions?filter=new');

        $response->assertOk();
        $suggestions = collect($response->json('suggestions.data'));

        $this->assertFalse($suggestions->contains('id', $unverifiedTarget->id));
    }

    public function test_03_search_exact_username_of_unverified_member_not_returned(): void
    {
        $viewer = $this->createMember();
        $unverified = $this->createMember([
            'user_id' => 'secretunverified',
            'name' => 'Secret User',
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/search?type=members&q=secretunverified');

        $response->assertOk();
        $members = collect($response->json('members.data'));

        $this->assertFalse($members->contains('id', $unverified->id));
        $this->assertEquals(0, $response->json('counts.members'));
    }

    public function test_04_direct_profile_url_of_unverified_member_from_another_account_not_returned(): void
    {
        $viewer = $this->createMember();
        $unverified = $this->createMember([
            'name' => 'Private Unverified',
            'bio' => 'Top secret bio data',
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/people/{$unverified->id}");

        $response->assertStatus(404);
        $response->assertJsonMissing(['name' => 'Private Unverified']);
        $response->assertJsonMissing(['bio' => 'Top secret bio data']);
    }

    public function test_05_direct_connection_api_request_to_unverified_member_rejected(): void
    {
        $viewer = $this->createMember();
        $unverified = $this->createMember(['mobile_verified_at' => null]);

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/friends/request/{$unverified->id}");

        $response->assertStatus(403);
    }

    public function test_06_rejected_request_must_not_create_a_connection_record(): void
    {
        $viewer = $this->createMember();
        $unverified = $this->createMember(['mobile_verified_at' => null]);

        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/friends/request/{$unverified->id}");

        $this->assertDatabaseMissing('friendships', [
            'member_one_id' => min($viewer->id, $unverified->id),
            'member_two_id' => max($viewer->id, $unverified->id),
        ]);
    }

    public function test_07_rejected_request_must_not_create_a_pending_notification(): void
    {
        $viewer = $this->createMember();
        $unverified = $this->createMember(['mobile_verified_at' => null]);

        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/friends/request/{$unverified->id}");

        $this->assertEquals(0, $unverified->notifications()->count());
    }

    public function test_08_verified_member_in_find_friends_visible_normally(): void
    {
        $viewer = $this->createMember();
        $verified = $this->createMember();

        $response = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/people/suggestions');

        $response->assertOk();
        $suggestions = collect($response->json('suggestions.data'));

        $this->assertTrue($suggestions->contains('id', $verified->id));
    }

    public function test_09_verified_member_profile_opens_normally(): void
    {
        $viewer = $this->createMember();
        $verified = $this->createMember(['name' => 'Verified User']);

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/people/{$verified->id}");

        $response->assertOk();
        $this->assertEquals('Verified User', $response->json('member.name'));
    }

    public function test_10_verified_member_can_receive_connection_request_normally(): void
    {
        $viewer = $this->createMember();
        $verified = $this->createMember();

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/friends/request/{$verified->id}");

        $response->assertOk();
        $this->assertDatabaseHas('friendships', [
            'member_one_id' => min($viewer->id, $verified->id),
            'member_two_id' => max($viewer->id, $verified->id),
            'status' => Friendship::STATUS_PENDING,
        ]);
    }

    public function test_11_unverified_member_can_still_access_their_own_profile_account(): void
    {
        $unverified = $this->createMember([
            'name' => 'My Own Name',
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($unverified, 'member')
            ->getJson("/api/member/people/{$unverified->id}");

        $response->assertOk();
        $this->assertEquals('My Own Name', $response->json('member.name'));
        $this->assertEquals('self', $response->json('friendship_state'));
    }

    public function test_12_switch_from_unverified_to_verified_becomes_discoverable(): void
    {
        $viewer = $this->createMember();
        $member = $this->createMember([
            'name' => 'Transitioning Member',
            'mobile_verified_at' => null,
        ]);

        // When unverified
        $response1 = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/people/{$member->id}");
        $response1->assertStatus(404);

        // Verify mobile
        $member->update(['mobile_verified_at' => now()]);

        // When verified
        $response2 = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/people/{$member->id}");
        $response2->assertOk();
        $this->assertEquals('Transitioning Member', $response2->json('member.name'));

        // Can now receive requests
        $requestResponse = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/friends/request/{$member->id}");
        $requestResponse->assertOk();
    }

    public function test_13_different_logged_in_member_cannot_retrieve_unverified_profile_data_by_changing_url(): void
    {
        $viewer1 = $this->createMember();
        $viewer2 = $this->createMember();
        $unverified = $this->createMember(['mobile_verified_at' => null]);

        $this->actingAs($viewer1, 'member')
            ->getJson("/api/member/people/{$unverified->id}")
            ->assertStatus(404);

        $this->actingAs($viewer2, 'member')
            ->getJson("/api/member/people/{$unverified->id}")
            ->assertStatus(404);
    }

    public function test_14_global_member_search_does_not_leak_unverified_users(): void
    {
        $viewer = $this->createMember();
        $unverified = $this->createMember([
            'name' => 'Global Leak Tester',
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/search?type=all&q=Leak');

        $response->assertOk();
        $members = collect($response->json('members'));

        $this->assertFalse($members->contains('id', $unverified->id));
        $this->assertEquals(0, $response->json('counts.members'));
    }

    public function test_15_connection_suggestions_do_not_contain_unverified_users(): void
    {
        $viewer = $this->createMember(['city' => 'Bangalore', 'country' => 'India']);
        $unverifiedInSameCity = $this->createMember([
            'city' => 'Bangalore',
            'country' => 'India',
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/people/suggestions?filter=nearby');

        $response->assertOk();
        $suggestions = collect($response->json('suggestions.data'));

        $this->assertFalse($suggestions->contains('id', $unverifiedInSameCity->id));
    }
}
