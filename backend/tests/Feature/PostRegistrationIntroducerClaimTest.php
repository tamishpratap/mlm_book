<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRegistrationIntroducerClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_endpoint_returns_null_introducer_when_not_assigned(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/settings');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'member' => [
                    'id' => $member->id,
                    'user_id' => 'john_doe',
                    'introducer_id' => null,
                ],
                'introducer' => null,
            ]);
    }

    public function test_settings_endpoint_returns_assigned_introducer_when_present(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => 'sponsor_alice',
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/settings');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'introducer' => [
                    'name' => 'Sponsor Alice',
                    'user_id' => 'sponsor_alice',
                ],
            ]);
    }

    public function test_check_introducer_previews_valid_mobile_verified_introducer(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/check-introducer?introducer_id=sponsor_alice');

        $response->assertOk()
            ->assertJson([
                'valid' => true,
                'exists' => true,
                'is_eligible' => true,
                'user_id' => 'sponsor_alice',
                'name' => 'Sponsor Alice',
            ]);
    }

    public function test_check_introducer_rejects_unverified_introducer(): void
    {
        $unverifiedSponsor = Member::create([
            'name' => 'Unverified Alice',
            'user_id' => 'unverified_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => null,
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/check-introducer?introducer_id=unverified_alice');

        $response->assertOk()
            ->assertJson([
                'valid' => false,
                'exists' => true,
                'is_eligible' => false,
            ]);
    }

    public function test_check_introducer_rejects_non_existent_introducer(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/check-introducer?introducer_id=non_existent_user');

        $response->assertOk()
            ->assertJson([
                'valid' => false,
                'exists' => false,
                'is_eligible' => false,
            ]);
    }

    public function test_check_introducer_rejects_self_referral(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/check-introducer?introducer_id=john_doe');

        $response->assertOk()
            ->assertJson([
                'valid' => false,
                'is_self' => true,
                'message' => 'You cannot use your own ID as your introducer.',
            ]);
    }

    public function test_claim_introducer_success_for_unverified_member_without_incrementing_count(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'sponsor_alice',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Introducer added successfully.',
                'introducer' => [
                    'name' => 'Sponsor Alice',
                    'user_id' => 'sponsor_alice',
                ],
            ]);

        $this->assertSame('sponsor_alice', $member->fresh()->introducer_id);
        $this->assertNull($member->fresh()->referral_counted_at);
        $this->assertSame(0, $sponsor->fresh()->direct_referral_count);
    }

    public function test_unverified_member_qualifies_referral_after_subsequent_mobile_verification(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'mobile_verified_at' => null,
        ]);

        // 1. Claim introducer while unverified
        $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'sponsor_alice',
            ])
            ->assertOk();

        $this->assertSame(0, $sponsor->fresh()->direct_referral_count);

        // 2. Member completes mobile verification
        $member->update(['mobile_verified_at' => now()]);
        $qualified = $member->fresh()->qualifyReferral();

        $this->assertTrue($qualified);
        $this->assertSame(1, $sponsor->fresh()->direct_referral_count);
        $this->assertNotNull($member->fresh()->referral_counted_at);
    }

    public function test_claim_introducer_success_for_already_verified_member_qualifies_referral_immediately(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'mobile_verified_at' => now(), // Already verified
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'sponsor_alice',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Introducer added successfully.',
            ]);

        $this->assertSame('sponsor_alice', $member->fresh()->introducer_id);
        $this->assertNotNull($member->fresh()->referral_counted_at);
        $this->assertSame(1, $sponsor->fresh()->direct_referral_count);
    }

    public function test_claim_introducer_rejects_if_introducer_not_mobile_verified(): void
    {
        $unverifiedSponsor = Member::create([
            'name' => 'Unverified Alice',
            'user_id' => 'unverified_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => null,
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'unverified_alice',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This Member is not eligible to be an introducer until their mobile number is verified.',
            ]);

        $this->assertNull($member->fresh()->introducer_id);
    }

    public function test_claim_introducer_rejects_if_introducer_does_not_exist(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'non_existent_user',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The selected Introducer ID does not exist.',
            ]);

        $this->assertNull($member->fresh()->introducer_id);
    }

    public function test_claim_introducer_rejects_self_referral(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'john_doe',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You cannot use your own ID as your introducer.',
            ]);

        $this->assertNull($member->fresh()->introducer_id);
    }

    public function test_claim_introducer_rejects_if_already_has_introducer_immutable(): void
    {
        $sponsor1 = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $sponsor2 = Member::create([
            'name' => 'Sponsor Bob',
            'user_id' => 'sponsor_bob',
            'email' => 'bob@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => 'sponsor_alice',
        ]);

        // Attempting to change to sponsor_bob must fail
        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'sponsor_bob',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Your introducer is already assigned and cannot be changed.',
            ]);

        $this->assertSame('sponsor_alice', $member->fresh()->introducer_id);
    }

    public function test_repeated_qualification_is_idempotent_no_double_count(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'introducer_id' => 'sponsor_alice',
            'mobile_verified_at' => now(),
        ]);

        // First qualification
        $first = $member->qualifyReferral();
        $this->assertTrue($first);
        $this->assertSame(1, $sponsor->fresh()->direct_referral_count);

        // Second qualification attempt
        $second = $member->fresh()->qualifyReferral();
        $this->assertFalse($second);
        $this->assertSame(1, $sponsor->fresh()->direct_referral_count);
    }
}
