<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PendingMemberRegistration;
use App\Services\ReferralRelationshipValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReferralRelationshipValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, bool $verified = true, ?string $introducerId = null): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'phone' => '+9198' . random_int(10000000, 99999999),
            'password' => 'secret123',
            'mobile_verified_at' => $verified ? now() : null,
            'introducer_id' => $introducerId,
        ]);
    }

    public function test_service_blocks_self_referral(): void
    {
        $memberA = $this->createMember('User A');
        $validator = app(ReferralRelationshipValidator::class);

        $res = $validator->validate($memberA, $memberA->user_id);

        $this->assertFalse($res['valid']);
        $this->assertSame('SELF_REFERRAL', $res['code']);
        $this->assertSame(ReferralRelationshipValidator::SELF_REFERRAL_MESSAGE, $res['message']);
    }

    public function test_service_blocks_direct_reverse_referral(): void
    {
        // A introduced B (B has introducer_id = A)
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B', true, $memberA->user_id);

        $validator = app(ReferralRelationshipValidator::class);

        // Member A attempts to set Member B as introducer
        $res = $validator->validate($memberA, $memberB->user_id);

        $this->assertFalse($res['valid']);
        $this->assertSame('DIRECT_REVERSE', $res['code']);
        $this->assertSame('You have already introduced this user; they cannot be set as your introducer.', $res['message']);
    }

    public function test_service_blocks_longer_ancestor_descendant_cycle(): void
    {
        // A introduced B -> B introduced C -> C introduced D
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B', true, $memberA->user_id);
        $memberC = $this->createMember('User C', true, $memberB->user_id);
        $memberD = $this->createMember('User D', true, $memberC->user_id);

        $validator = app(ReferralRelationshipValidator::class);

        // A attempts to set C as introducer (A -> B -> C -> A cycle)
        $res1 = $validator->validate($memberA, $memberC->user_id);
        $this->assertFalse($res1['valid']);
        $this->assertSame('CYCLE', $res1['code']);
        $this->assertSame('Selecting this introducer would create an invalid circular referral relationship.', $res1['message']);

        // A attempts to set D as introducer (A -> B -> C -> D -> A cycle)
        $res2 = $validator->validate($memberA, $memberD->user_id);
        $this->assertFalse($res2['valid']);
        $this->assertSame('CYCLE', $res2['code']);
        $this->assertSame('Selecting this introducer would create an invalid circular referral relationship.', $res2['message']);
    }

    public function test_service_allows_valid_referral(): void
    {
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B'); // unrelated

        $validator = app(ReferralRelationshipValidator::class);

        $res = $validator->validate($memberB, $memberA->user_id);

        $this->assertTrue($res['valid']);
        $this->assertSame('VALID', $res['code']);
        $this->assertSame("Introduced by {$memberA->name}", $res['message']);
    }

    public function test_api_check_introducer_rejects_direct_reverse(): void
    {
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B', true, $memberA->user_id);

        $this->actingAs($memberA, 'member');

        $response = $this->getJson("/api/member/account/check-introducer?introducer_id={$memberB->user_id}");

        $response->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('code', 'DIRECT_REVERSE')
            ->assertJsonPath('message', 'You have already introduced this user; they cannot be set as your introducer.');
    }

    public function test_api_claim_introducer_rejects_direct_reverse_and_preserves_database(): void
    {
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B', true, $memberA->user_id);

        $this->actingAs($memberA, 'member');

        $response = $this->postJson('/api/member/account/introducer', [
            'introducer_id' => $memberB->user_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id'])
            ->assertJsonPath('errors.introducer_id.0', 'You have already introduced this user; they cannot be set as your introducer.');

        // Verify database is completely unchanged
        $this->assertNull($memberA->fresh()->introducer_id);
    }

    public function test_api_claim_introducer_rejects_cycle(): void
    {
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B', true, $memberA->user_id);
        $memberC = $this->createMember('User C', true, $memberB->user_id);

        $this->actingAs($memberA, 'member');

        $response = $this->postJson('/api/member/account/introducer', [
            'introducer_id' => $memberC->user_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id'])
            ->assertJsonPath('errors.introducer_id.0', 'Selecting this introducer would create an invalid circular referral relationship.');

        $this->assertNull($memberA->fresh()->introducer_id);
    }

    public function test_api_claim_introducer_succeeds_for_valid_introducer(): void
    {
        $memberA = $this->createMember('User A');
        $memberSponsor = $this->createMember('Sponsor S');

        $this->actingAs($memberA, 'member');

        $response = $this->postJson('/api/member/account/introducer', [
            'introducer_id' => $memberSponsor->user_id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('introducer.user_id', $memberSponsor->user_id);

        $this->assertSame($memberSponsor->user_id, $memberA->fresh()->introducer_id);
    }

    public function test_normal_signup_blocks_self_referral_and_invalid_introducer(): void
    {
        // Attempting to use same User ID as introducer
        $response = $this->postJson('/api/member/register', [
            'name' => 'New User',
            'user_id' => 'new_user_123',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'introducer_id' => 'new_user_123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id'])
            ->assertJsonPath('errors.introducer_id.0', 'You cannot use your own ID as your introducer.');

        // Unverified member cannot be introducer
        $unverified = $this->createMember('Unverified Member', false);

        $response2 = $this->postJson('/api/member/register', [
            'name' => 'New User 2',
            'user_id' => 'new_user_456',
            'email' => 'newuser2@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'introducer_id' => $unverified->user_id,
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id'])
            ->assertJsonPath('errors.introducer_id.0', 'This Member is not eligible to be an introducer until their mobile number is verified.');
    }

    public function test_google_signup_blocks_invalid_introducer(): void
    {
        $token = 'test_token_' . uniqid();
        Cache::put('pending_google_signup:' . $token, [
            'email' => 'googleuser@example.com',
            'name' => 'Google User',
            'google_id' => 'google_123456',
        ], 600);

        $unverified = $this->createMember('Unverified Member', false);

        $response = $this->postJson('/auth/google/complete', [
            'token' => $token,
            'phone' => '9876543210',
            'introducer_id' => $unverified->user_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id'])
            ->assertJsonPath('errors.introducer_id.0', 'This Member is not eligible to be an introducer until their mobile number is verified.');
    }

    public function test_profile_endpoint_maintains_directional_referrals(): void
    {
        // A -> B -> C
        // A -> D
        $memberA = $this->createMember('User A');
        $memberB = $this->createMember('User B', true, $memberA->user_id);
        $memberC = $this->createMember('User C', true, $memberB->user_id);
        $memberD = $this->createMember('User D', true, $memberA->user_id);

        $this->actingAs($memberB, 'member');
        $bProfile = $this->getJson("/api/member/people/{$memberB->id}");
        $bProfile->assertOk();

        // B was introduced by A
        $this->assertSame($memberA->user_id, $bProfile->json('introducer.user_id'));
        // B introduced C
        $this->assertCount(1, $bProfile->json('direct_referrals'));
        $this->assertSame($memberC->user_id, $bProfile->json('direct_referrals.0.user_id'));

        // A was not introduced by anyone, but introduced B and D
        $this->actingAs($memberA, 'member');
        $aProfile = $this->getJson("/api/member/people/{$memberA->id}");
        $aProfile->assertOk();
        $this->assertNull($aProfile->json('introducer'));
        $this->assertCount(2, $aProfile->json('direct_referrals'));
        $referralUserIds = collect($aProfile->json('direct_referrals'))->pluck('user_id')->all();
        $this->assertContains($memberB->user_id, $referralUserIds);
        $this->assertContains($memberD->user_id, $referralUserIds);
    }
}
