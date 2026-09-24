<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberGlobalProfileImageFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_01_member_with_valid_profile_photo_accessors(): void
    {
        $member = $this->createVerifiedMember('Member With Real Photo');
        $member->update([
            'profile_photo' => 'uploads/profile/real_photo.png',
            'cover_photo' => 'uploads/cover/real_cover.png',
        ]);

        $this->assertSame('uploads/profile/real_photo.png', $member->profile_photo);
        $this->assertSame('uploads/cover/real_cover.png', $member->cover_photo);

        // When serialized to JSON / array
        $array = $member->toArray();
        $this->assertArrayHasKey('profile_photo', $array);
        $this->assertArrayHasKey('avatar_url', $array);
        $this->assertArrayHasKey('profile_photo_url', $array);
        $this->assertSame('uploads/profile/real_photo.png', $array['profile_photo']);
        $this->assertNotSame($array['cover_photo'], $array['profile_photo']);
    }

    public function test_02_member_without_profile_photo_returns_null_profile_photo_url(): void
    {
        $member = $this->createVerifiedMember('Member Without Photo');
        $member->update([
            'profile_photo' => null,
            'cover_photo' => null,
        ]);

        $this->assertNull($member->profile_photo);
        $this->assertNull($member->profile_photo_url);
        $this->assertStringContainsString('profile.png', $member->avatar_url);

        $array = $member->toArray();
        $this->assertNull($array['profile_photo']);
        $this->assertNull($array['profile_photo_url']);
    }

    public function test_03_incoming_requests_api_provides_authoritative_profile_photo(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $sender = $this->createVerifiedMember('Sender B');
        $sender->update([
            'profile_photo' => 'uploads/profile/sender_b.png',
            'cover_photo' => 'uploads/cover/sender_b_cover.png',
        ]);

        [$one, $two] = Friendship::normalizePair($userA->id, $sender->id);
        Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $sender->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friend-requests');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('incoming_count', 1);

        $incomingItem = $response->json('incoming_requests.0');
        $this->assertSame($sender->id, $incomingItem['friend']['id']);
        $this->assertSame('uploads/profile/sender_b.png', $incomingItem['friend']['profile_photo']);
        $this->assertNotSame($incomingItem['friend']['cover_photo'], $incomingItem['friend']['profile_photo']);
    }

    public function test_04_my_connections_api_provides_authoritative_profile_photo(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $friend = $this->createVerifiedMember('Connected Friend C');
        $friend->update([
            'profile_photo' => 'uploads/profile/friend_c.png',
            'cover_photo' => 'uploads/cover/friend_c_cover.png',
        ]);

        [$one, $two] = Friendship::normalizePair($userA->id, $friend->id);
        Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $userA->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friends');

        $response->assertOk()
            ->assertJsonPath('friends.total', 1);

        $friendItem = $response->json('friends.data.0');
        $this->assertSame($friend->id, $friendItem['id']);
        $this->assertSame('uploads/profile/friend_c.png', $friendItem['profile_photo']);
        $this->assertNotSame($friendItem['cover_photo'], $friendItem['profile_photo']);
    }

    public function test_05_suggestions_api_provides_authoritative_profile_photo(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $suggested = $this->createVerifiedMember('Suggested D');
        $suggested->update([
            'profile_photo' => 'uploads/profile/suggested_d.png',
            'cover_photo' => 'uploads/cover/suggested_d_cover.png',
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson('/api/member/people/suggestions');

        $response->assertOk();
        $items = collect($response->json('suggestions.data'));
        $target = $items->firstWhere('id', $suggested->id);

        $this->assertNotNull($target);
        $this->assertSame('uploads/profile/suggested_d.png', $target['profile_photo']);
        $this->assertNotSame($target['cover_photo'], $target['profile_photo']);
    }

    public function test_06_same_member_photo_is_consistent_across_all_endpoints_and_profile(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $starMember = $this->createVerifiedMember('Anand Kashyap');
        $photoPath = 'uploads/profile/anand_kashyap_photo.png';
        $coverPath = 'uploads/cover/anand_kashyap_cover.png';
        $starMember->update([
            'profile_photo' => $photoPath,
            'cover_photo' => $coverPath,
        ]);

        // 1. Check Profile endpoint
        $profileRes = $this->actingAs($userA, 'member')
            ->getJson("/api/member/people/{$starMember->id}");
        $profileRes->assertOk();
        $profilePhoto = $profileRes->json('member.profile_photo');
        $this->assertSame($photoPath, $profilePhoto);

        // 2. Check Suggestions endpoint
        $suggestionsRes = $this->actingAs($userA, 'member')
            ->getJson('/api/member/people/suggestions');
        $suggestionsRes->assertOk();
        $suggestedStar = collect($suggestionsRes->json('suggestions.data'))->firstWhere('id', $starMember->id);
        $this->assertNotNull($suggestedStar);
        $this->assertSame($photoPath, $suggestedStar['profile_photo']);

        // 3. Check Requests endpoint (send request from starMember to userA)
        [$one, $two] = Friendship::normalizePair($userA->id, $starMember->id);
        $req = Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $starMember->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $requestsRes = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friend-requests');
        $requestsRes->assertOk();
        $reqStar = $requestsRes->json('incoming_requests.0.friend');
        $this->assertSame($photoPath, $reqStar['profile_photo']);

        // 4. Accept request -> Check Friends endpoint
        $req->update(['status' => Friendship::STATUS_ACCEPTED]);
        $friendsRes = $this->actingAs($userA, 'member')
            ->getJson('/api/member/friends');
        $friendsRes->assertOk();
        $friendStar = collect($friendsRes->json('friends.data'))->firstWhere('id', $starMember->id);
        $this->assertNotNull($friendStar);
        $this->assertSame($photoPath, $friendStar['profile_photo']);

        // Assert that in all 4 contexts, profile_photo is identical and NEVER equal to cover_photo
        $this->assertSame($profilePhoto, $suggestedStar['profile_photo']);
        $this->assertSame($profilePhoto, $reqStar['profile_photo']);
        $this->assertSame($profilePhoto, $friendStar['profile_photo']);
        $this->assertNotSame($coverPath, $profilePhoto);
    }

    public function test_07_member_with_long_name_and_username(): void
    {
        $userA = $this->createVerifiedMember('User A');
        $longMember = $this->createVerifiedMember('Alexander Montgomery Christopher Featherstonehaugh');
        $longMember->update([
            'user_id' => 'alexander_montgomery_christopher_featherstonehaugh_9999',
            'profile_photo' => 'uploads/profile/alexander.png',
        ]);

        $response = $this->actingAs($userA, 'member')
            ->getJson("/api/member/people/{$longMember->id}");

        $response->assertOk();
        $this->assertSame('Alexander Montgomery Christopher Featherstonehaugh', $response->json('member.name'));
        $this->assertSame('alexander_montgomery_christopher_featherstonehaugh_9999', $response->json('member.user_id'));
        $this->assertSame('uploads/profile/alexander.png', $response->json('member.profile_photo'));
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
