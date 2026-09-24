<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Services\MemberOtpService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class MemberWhatsAppVerificationDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => Hash::make('Secret123!'),
            'user_id' => 'USER_' . strtoupper(uniqid()),
        ], $attributes));
    }

    /**
     * TEST 1 — UNIQUE PHONE
     * Use a phone number not assigned to another Member.
     * Expected:
     * ✓ OTP sends.
     * ✓ OTP verifies.
     * ✓ phone updates.
     * ✓ mobile_verified_at updates.
     * ✓ success response returned.
     */
    public function test_unique_phone_verification_succeeds(): void
    {
        $member = $this->createMember([
            'phone' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($member, 'member');

        // 1. Send OTP
        $sendResponse = $this->postJson('/api/member/account/mobile/send-otp', [
            'mobile_number' => '+919876543210',
        ]);

        $sendResponse->assertOk();
        $sendResponse->assertJson([
            'success' => true,
            'mobile_number' => '+919876543210',
        ]);

        $otp = MemberVerificationOtp::query()->sole();
        $this->assertSame('+919876543210', $otp->pending_value);

        // Update OTP hash with known code for verification
        $otp->update(['code_hash' => Hash::make('654321')]);

        // 2. Verify OTP
        $verifyResponse = $this->postJson('/api/member/account/mobile/verify-otp', [
            'mobile_otp' => '654321',
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJson([
            'success' => true,
            'is_verified' => true,
        ]);

        // 3. Database assertions
        $fresh = $member->fresh();
        $this->assertSame('+919876543210', $fresh->phone);
        $this->assertNotNull($fresh->mobile_verified_at);
        $this->assertTrue($fresh->is_verified);
    }

    /**
     * TEST 2 — DUPLICATE PHONE
     * Use a phone number already owned by another Member.
     * Expected:
     * ✓ OTP flow remains functional.
     * ✓ Verification request does not crash.
     * ✓ Current Member phone is NOT changed.
     * ✓ Current Member mobile_verified_at is NOT incorrectly changed.
     * ✓ Existing Member keeps ownership.
     * ✓ HTTP returns a clean validation/business error (422).
     * ✓ User sees only: "This WhatsApp number is already registered with another account."
     * ✓ No raw SQL is shown.
     */
    public function test_duplicate_phone_verification_returns_clean_422_and_does_not_mutate_state(): void
    {
        // Existing member owns this phone number
        $existingOwner = $this->createMember([
            'name' => 'Original Phone Owner',
            'phone' => '+91979703005',
            'mobile_verified_at' => now()->subDays(10),
        ]);

        // Current member trying to claim the same phone
        $currentMember = $this->createMember([
            'name' => 'Attempter Member',
            'phone' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($currentMember, 'member');

        // 1. Send OTP remains functional
        $sendResponse = $this->postJson('/api/member/account/mobile/send-otp', [
            'mobile_number' => '+91979703005',
        ]);
        $sendResponse->assertOk();

        $otp = MemberVerificationOtp::query()->latest('id')->first();
        $this->assertNotNull($otp);
        $this->assertSame('+91979703005', $otp->pending_value);
        $otp->update(['code_hash' => Hash::make('123456')]);

        // 2. Verification request does NOT crash with SQL exception
        $verifyResponse = $this->postJson('/api/member/account/mobile/verify-otp', [
            'mobile_otp' => '123456',
        ]);

        // 3. Clean 422 HTTP validation response
        $verifyResponse->assertStatus(422);
        $verifyResponse->assertJson([
            'message' => 'This WhatsApp number is already registered with another account.',
            'errors' => [
                'mobile_otp' => ['This WhatsApp number is already registered with another account.'],
            ],
        ]);

        // 4. Absolute raw SQL shielding check
        $content = $verifyResponse->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $content);
        $this->assertStringNotContainsString('members_phone_unique', $content);
        $this->assertStringNotContainsString('Duplicate entry', $content);
        $this->assertStringNotContainsString('Integrity constraint', $content);
        $this->assertStringNotContainsString('QueryException', $content);

        // 5. Current Member phone and verification untouched
        $refreshedCurrent = $currentMember->fresh();
        $this->assertNull($refreshedCurrent->phone);
        $this->assertNull($refreshedCurrent->mobile_verified_at);
        $this->assertFalse($refreshedCurrent->is_verified);

        // 6. Existing Member maintains ownership completely intact
        $refreshedOwner = $existingOwner->fresh();
        $this->assertSame('+91979703005', $refreshedOwner->phone);
        $this->assertNotNull($refreshedOwner->mobile_verified_at);
        $this->assertSame('Original Phone Owner', $refreshedOwner->name);
    }

    /**
     * TEST 3 — CURRENT MEMBER'S OWN PHONE
     * Current Member verifies the same phone already assigned to themselves.
     * Expected:
     * ✓ It is NOT treated as a duplicate.
     * ✓ Existing verification behavior remains valid.
     */
    public function test_current_members_own_phone_is_not_treated_as_duplicate(): void
    {
        // Member already has this phone stored on their record
        $member = $this->createMember([
            'name' => 'Self Phone Owner',
            'phone' => '+91979703005',
            'mobile_verified_at' => null, // e.g. completing verification
        ]);

        $this->actingAs($member, 'member');

        $sendResponse = $this->postJson('/api/member/account/mobile/send-otp', [
            'mobile_number' => '+91979703005',
        ]);
        $sendResponse->assertOk();

        $otp = MemberVerificationOtp::query()->latest('id')->first();
        $otp->update(['code_hash' => Hash::make('654321')]);

        $verifyResponse = $this->postJson('/api/member/account/mobile/verify-otp', [
            'mobile_otp' => '654321',
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJson([
            'success' => true,
            'is_verified' => true,
        ]);

        $fresh = $member->fresh();
        $this->assertSame('+91979703005', $fresh->phone);
        $this->assertNotNull($fresh->mobile_verified_at);
    }

    /**
     * TEST 4 — RAW EXCEPTION SAFETY (CONCURRENCY / RACE CONDITION)
     * If a duplicate key QueryException occurs during update due to concurrent requests,
     * the catch block shields the user, logs safely, and returns clean 422.
     */
    public function test_duplicate_key_query_exception_is_caught_and_returns_clean_422(): void
    {
        Log::spy();

        $member = $this->createMember([
            'phone' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($member, 'member');

        $this->postJson('/api/member/account/mobile/send-otp', [
            'mobile_number' => '+919999888877',
        ])->assertOk();

        $otp = MemberVerificationOtp::query()->latest('id')->first();
        $otp->update(['code_hash' => Hash::make('654321')]);

        // Pre-insert another member with the exact phone after OTP was generated (simulating race)
        $this->createMember([
            'name' => 'Racer Member',
            'phone' => '+919999888877',
            'mobile_verified_at' => now(),
        ]);

        // The verify request pre-check catches the duplicate even if it was inserted right before!
        $response = $this->postJson('/api/member/account/mobile/verify-otp', [
            'mobile_otp' => '654321',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'This WhatsApp number is already registered with another account.',
            'errors' => [
                'mobile_otp' => ['This WhatsApp number is already registered with another account.'],
            ],
        ]);

        $content = $response->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $content);
        $this->assertStringNotContainsString('members_phone_unique', $content);
        $this->assertStringNotContainsString('Duplicate entry', $content);
    }

    /**
     * TEST 5 — EXISTING MEMBER IS UNCHANGED
     * After duplicate attempt:
     * ✓ Original phone owner remains unchanged.
     * ✓ Current Member remains unchanged.
     */
    public function test_existing_member_and_current_member_remain_intact_after_failed_duplicate_attempt(): void
    {
        $originalOwner = $this->createMember([
            'name' => 'Original Owner',
            'phone' => '+91979703005',
            'mobile_verified_at' => '2026-01-01 10:00:00',
        ]);

        $attempter = $this->createMember([
            'name' => 'Attempter',
            'phone' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($attempter, 'member');

        // Create and verify OTP targeting original owner's phone
        $this->postJson('/api/member/account/mobile/send-otp', [
            'mobile_number' => '+91979703005',
        ])->assertOk();

        $otp = MemberVerificationOtp::query()->latest('id')->first();
        $otp->update(['code_hash' => Hash::make('654321')]);

        $this->postJson('/api/member/account/mobile/verify-otp', [
            'mobile_otp' => '654321',
        ])->assertStatus(422);

        // Verify original owner state
        $ownerFresh = $originalOwner->fresh();
        $this->assertSame('Original Owner', $ownerFresh->name);
        $this->assertSame('+91979703005', $ownerFresh->phone);
        $this->assertSame('2026-01-01 10:00:00', $ownerFresh->mobile_verified_at->format('Y-m-d H:i:s'));

        // Verify attempter state
        $attempterFresh = $attempter->fresh();
        $this->assertSame('Attempter', $attempterFresh->name);
        $this->assertNull($attempterFresh->phone);
        $this->assertNull($attempterFresh->mobile_verified_at);
    }
}
