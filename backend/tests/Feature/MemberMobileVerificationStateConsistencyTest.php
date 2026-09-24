<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberMobileVerificationStateConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ValidateCsrfToken::class]);
    }

    private function validBusinessPagePayload(array $overrides = []): array
    {
        return array_merge([
            'page_name' => 'Apex Health Solutions',
            'category' => 'Health, Nutrition & Wellness MLM',
            'visibility' => 'public',
            'description' => 'A premier business page for health networkers across the platform.',
            'email' => 'contact@apexhealth.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => 'India',
        ], $overrides);
    }

    /**
     * TEST 1: Verified member state consistency.
     * When mobile_verified_at != null, model, /me, /verification-status,
     * and business page creation endpoints are 100% consistent and unlocked.
     */
    public function test_verified_member_state_consistency_and_business_page_creation(): void
    {
        $member = Member::create([
            'name' => 'Verified John',
            'email' => 'john.verified@example.com',
            'password' => bcrypt('secret123'),
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
            'mobile_verification_requested_at' => now()->subHour(),
        ]);

        // 1. Model method & accessor verification
        $this->assertTrue($member->isMobileVerified());
        $this->assertFalse($member->isMobileVerificationPending());
        $this->assertTrue($member->is_verified);
        $this->assertFalse($member->is_verification_pending);
        $this->assertSame('verified', $member->verification_status);

        // 2. /api/member/me endpoint verification
        $meResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/me');

        $meResponse->assertOk()
            ->assertJsonPath('member.is_verified', true)
            ->assertJsonPath('member.is_verification_pending', false)
            ->assertJsonPath('member.verification_status', 'verified');
        $this->assertNotNull($meResponse->json('member.mobile_verified_at'));

        // 3. /api/member/account/verification-status endpoint verification
        $statusResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/verification-status');

        $statusResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_verified', true)
            ->assertJsonPath('is_pending', false)
            ->assertJsonPath('verification_status', 'verified');

        // 4. Business page creation succeeds without restriction
        $createResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/business-pages', $this->validBusinessPagePayload());

        $createResponse->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('business_pages', [
            'member_id' => $member->id,
            'page_name' => 'Apex Health Solutions',
        ]);
    }

    /**
     * TEST 2: Unverified member state consistency.
     * When mobile_verified_at == null, model, /me, /verification-status,
     * and business page creation endpoints are 100% consistent and restricted.
     */
    public function test_unverified_member_state_consistency_and_business_page_restriction(): void
    {
        $member = Member::create([
            'name' => 'Unverified Alice',
            'email' => 'alice.unverified@example.com',
            'password' => bcrypt('secret123'),
            'phone' => '+919876543211',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ]);

        // 1. Model method & accessor verification
        $this->assertFalse($member->isMobileVerified());
        $this->assertFalse($member->isMobileVerificationPending());
        $this->assertFalse($member->is_verified);
        $this->assertFalse($member->is_verification_pending);
        $this->assertSame('unverified', $member->verification_status);

        // 2. /api/member/me endpoint verification
        $meResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/me');

        $meResponse->assertOk()
            ->assertJsonPath('member.is_verified', false)
            ->assertJsonPath('member.is_verification_pending', false)
            ->assertJsonPath('member.verification_status', 'unverified')
            ->assertJsonPath('member.mobile_verified_at', null);

        // 3. /api/member/account/verification-status endpoint verification
        $statusResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/verification-status');

        $statusResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_verified', false)
            ->assertJsonPath('is_pending', false)
            ->assertJsonPath('verification_status', 'unverified');

        // 4. Business page creation is blocked with 403 and requires_mobile_verification
        $createResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/business-pages', $this->validBusinessPagePayload());

        $createResponse->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', EnsureMemberMobileVerified::UNVERIFIED_MESSAGE)
            ->assertJsonPath('requires_mobile_verification', true);

        $this->assertDatabaseMissing('business_pages', [
            'member_id' => $member->id,
            'page_name' => 'Apex Health Solutions',
        ]);
    }

    /**
     * TEST 3: Pending member state consistency.
     * When mobile_verified_at == null and mobile_verification_requested_at != null,
     * status is strictly 'pending', /me reflects pending, and creation remains restricted.
     */
    public function test_pending_member_state_consistency_and_business_page_restriction(): void
    {
        $member = Member::create([
            'name' => 'Pending Bob',
            'email' => 'bob.pending@example.com',
            'password' => bcrypt('secret123'),
            'phone' => '+919876543212',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        // 1. Model method & accessor verification
        $this->assertFalse($member->isMobileVerified());
        $this->assertTrue($member->isMobileVerificationPending());
        $this->assertFalse($member->is_verified);
        $this->assertTrue($member->is_verification_pending);
        $this->assertSame('pending', $member->verification_status);

        // 2. /api/member/me endpoint verification
        $meResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/me');

        $meResponse->assertOk()
            ->assertJsonPath('member.is_verified', false)
            ->assertJsonPath('member.is_verification_pending', true)
            ->assertJsonPath('member.verification_status', 'pending');

        // 3. /api/member/account/verification-status endpoint verification
        $statusResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/verification-status');

        $statusResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_verified', false)
            ->assertJsonPath('is_pending', true)
            ->assertJsonPath('verification_status', 'pending');

        // 4. Business page creation remains restricted
        $createResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/business-pages', $this->validBusinessPagePayload());

        $createResponse->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('requires_mobile_verification', true);
    }

    /**
     * TEST 4: Dynamic approval transition immediately unlocks business page creation.
     * When admin approves a member (setting mobile_verified_at),
     * the member session immediately reflects is_verified: true,
     * and business page creation succeeds without requiring re-authentication.
     */
    public function test_dynamic_approval_transition_immediately_unlocks_business_page_creation(): void
    {
        $member = Member::create([
            'name' => 'Evolving Member',
            'email' => 'evolving@example.com',
            'password' => bcrypt('secret123'),
            'phone' => '+919876543213',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        // Initial attempt blocked
        $this->actingAs($member, 'member')
            ->postJson('/api/member/business-pages', $this->validBusinessPagePayload())
            ->assertStatus(403);

        // Admin approves member
        $member->update([
            'mobile_verified_at' => now(),
        ]);

        // Immediate /me check returns fresh verified state
        $meResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/me');

        $meResponse->assertOk()
            ->assertJsonPath('member.is_verified', true)
            ->assertJsonPath('member.verification_status', 'verified');

        // Immediate business page creation succeeds
        $createResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/business-pages', $this->validBusinessPagePayload());

        $createResponse->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('business_pages', [
            'member_id' => $member->id,
            'page_name' => 'Apex Health Solutions',
        ]);
    }
}
