<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileReferralsTest extends TestCase
{
    use RefreshDatabase;

    public function test_own_profile_returns_referral_network_while_preserving_connections(): void
    {
        // 1. Create Introducer
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'sponsor_alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 1,
        ]);

        // 2. Create Current Member introduced by Alice
        $me = Member::create([
            'name' => 'Me User',
            'user_id' => 'me_user',
            'email' => 'me@example.com',
            'password' => 'secret123',
            'introducer_id' => 'sponsor_alice',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 2,
        ]);

        // 3. Create 2 direct referrals introduced by Me
        $ref1 = Member::create([
            'name' => 'Referral Bob',
            'user_id' => 'ref_bob',
            'email' => 'bob@example.com',
            'password' => 'secret123',
            'introducer_id' => 'me_user',
            'mobile_verified_at' => now(),
        ]);

        $ref2 = Member::create([
            'name' => 'Referral Charlie',
            'user_id' => 'ref_charlie',
            'email' => 'charlie@example.com',
            'password' => 'secret123',
            'introducer_id' => 'me_user',
            'mobile_verified_at' => null,
        ]);

        // 4. Create 1 separate Connection (Friendship) that is NOT a referral
        $friend = Member::create([
            'name' => 'Social Friend Dave',
            'user_id' => 'friend_dave',
            'email' => 'dave@example.com',
            'password' => 'secret123',
        ]);

        Friendship::create([
            'member_one_id' => $me->id,
            'member_two_id' => $friend->id,
            'requested_by_id' => $friend->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Request own profile API
        $response = $this->actingAs($me, 'member')
            ->getJson('/api/member/profile');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'stats' => [
                    'friends_count' => 1,
                    'direct_referrals_count' => 2,
                ],
                'direct_referral_count' => 2,
            ]);

        // Verify Connections (Friends) preserved and distinct
        $friends = $response->json('friends');
        $this->assertCount(1, $friends);
        $this->assertSame('Social Friend Dave', $friends[0]['name']);

        // Verify Introducer present
        $introducer = $response->json('introducer');
        $this->assertNotNull($introducer);
        $this->assertSame('Sponsor Alice', $introducer['name']);
        $this->assertSame('sponsor_alice', $introducer['user_id']);

        // Verify Direct Referrals present
        $directReferrals = $response->json('direct_referrals');
        $this->assertCount(2, $directReferrals);
        $refUsernames = collect($directReferrals)->pluck('user_id')->all();
        $this->assertContains('ref_bob', $refUsernames);
        $this->assertContains('ref_charlie', $refUsernames);
    }

    public function test_viewed_other_member_profile_returns_their_specific_referrals(): void
    {
        $viewer = Member::create([
            'name' => 'Viewer User',
            'user_id' => 'viewer_user',
            'email' => 'viewer@example.com',
            'password' => 'secret123',
        ]);

        $targetMember = Member::create([
            'name' => 'Target Member',
            'user_id' => 'target_member',
            'email' => 'target@example.com',
            'password' => 'secret123',
            'introducer_id' => 'some_sponsor',
            'direct_referral_count' => 1,
        ]);

        $targetSponsor = Member::create([
            'name' => 'Some Sponsor',
            'user_id' => 'some_sponsor',
            'email' => 'sponsor@example.com',
            'password' => 'secret123',
        ]);

        $targetReferral = Member::create([
            'name' => 'Target Referral Child',
            'user_id' => 'target_ref_child',
            'email' => 'child@example.com',
            'password' => 'secret123',
            'introducer_id' => 'target_member',
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/people/{$targetMember->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'member' => [
                    'id' => $targetMember->id,
                    'name' => 'Target Member',
                ],
                'direct_referral_count' => 1,
            ]);

        $this->assertSame('Some Sponsor', $response->json('introducer.name'));
        $this->assertSame('Target Referral Child', $response->json('direct_referrals.0.name'));
    }

    public function test_empty_referral_state_and_no_introducer(): void
    {
        $soloMember = Member::create([
            'name' => 'Solo Member',
            'user_id' => 'solo_member',
            'email' => 'solo@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'direct_referral_count' => 0,
        ]);

        $response = $this->actingAs($soloMember, 'member')
            ->getJson('/api/member/profile');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'introducer' => null,
                'direct_referrals' => [],
                'direct_referral_count' => 0,
            ]);
    }
}