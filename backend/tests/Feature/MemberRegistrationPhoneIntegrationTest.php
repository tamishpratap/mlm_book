<?php

namespace Tests\Feature;

use App\Mail\MemberRegistrationOtpMail;
use App\Models\Member;
use App\Models\PendingMemberRegistration;
use App\Services\MemberPhoneNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberRegistrationPhoneIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_phone_number(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/member/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
        $this->assertDatabaseCount('pending_member_registrations', 0);
        Mail::assertNothingSent();
    }

    public function test_registration_rejects_invalid_phone_number_format(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/member/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '12345',
            'country_code' => '+91',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
        $this->assertDatabaseCount('pending_member_registrations', 0);
        Mail::assertNothingSent();
    }

    public function test_registration_rejects_duplicate_phone_and_does_not_create_pending_or_send_otp(): void
    {
        Mail::fake();

        $existingMember = Member::create([
            'name' => 'Existing User',
            'user_id' => 'EXIST00001',
            'email' => 'existing@example.com',
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/member/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'phone' => '9876543210',
            'country_code' => '+91',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
        $this->assertEquals(
            'This number already exists. Please use the existing login option.',
            $response->json('errors.phone.0')
        );

        // Assert pending_member_registrations has no new rows
        $this->assertDatabaseCount('pending_member_registrations', 0);

        // Assert no email OTP was dispatched
        Mail::assertNothingSent();

        // Assert existing member remains untouched
        $this->assertDatabaseHas('members', [
            'id' => $existingMember->id,
            'phone' => '+919876543210',
        ]);
    }

    public function test_successful_registration_staged_in_pending_with_normalized_phone_and_sends_email_otp(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/member/register', [
            'name' => 'New Staged User',
            'email' => 'staged@example.com',
            'phone' => '09876543211', // National trunk prefix 0
            'country_code' => '+91',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'email' => 'staged@example.com',
        ]);

        // Pending registration has normalized E.164 phone
        $this->assertDatabaseHas('pending_member_registrations', [
            'email' => 'staged@example.com',
            'phone' => '+919876543211',
        ]);

        // Member table must NOT have this record yet
        $this->assertDatabaseMissing('members', [
            'email' => 'staged@example.com',
        ]);

        // Email OTP must have been sent
        Mail::assertSent(MemberRegistrationOtpMail::class, function ($mail) {
            return $mail->hasTo('staged@example.com');
        });
    }

    public function test_email_otp_verification_transfers_phone_and_leaves_mobile_verified_at_null(): void
    {
        $token = Str::random(64);
        $email = 'verifyphone@example.com';
        $phone = '+919876543212';
        $otp = '654321';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Verify Phone User',
            'user_id' => 'VRFY000001',
            'email' => $email,
            'phone' => $phone,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->postJson('/api/member/register/verify', [
                'otp' => $otp,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Account verified successfully.',
        ]);

        // Member must exist with phone transferred and mobile_verified_at NULL
        $this->assertDatabaseHas('members', [
            'email' => $email,
            'phone' => $phone,
            'mobile_verified_at' => null,
        ]);

        $member = Member::where('email', $email)->first();
        $this->assertNotNull($member);
        $this->assertEquals($phone, $member->phone);
        $this->assertNull($member->mobile_verified_at);

        // Pending record must be deleted
        $this->assertDatabaseMissing('pending_member_registrations', [
            'token' => $token,
        ]);

        // User must be authenticated
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertEquals($member->id, Auth::guard('member')->id());
    }

    public function test_check_phone_endpoint_availability_and_privacy(): void
    {
        Member::create([
            'name' => 'Secret User',
            'user_id' => 'SECR000001',
            'email' => 'secret@example.com',
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
            'password' => Hash::make('Password123!'),
        ]);

        // Test available phone
        $responseAvailable = $this->getJson('/api/member/register/check-phone?phone=9876543219&country_code=+91');
        $responseAvailable->assertOk();
        $responseAvailable->assertJson([
            'available' => true,
            'normalized_phone' => '+919876543219',
            'message' => 'Phone number is available.',
        ]);

        // Test taken phone
        $responseTaken = $this->getJson('/api/member/register/check-phone?phone=9876543210&country_code=+91');
        $responseTaken->assertOk();
        $responseTaken->assertJson([
            'available' => false,
            'normalized_phone' => '+919876543210',
            'message' => 'This number already exists. Please use the existing login option.',
        ]);

        // Assert no user metadata leaked
        $json = $responseTaken->json();
        $this->assertArrayNotHasKey('name', $json);
        $this->assertArrayNotHasKey('email', $json);
        $this->assertArrayNotHasKey('user_id', $json);
        $this->assertArrayNotHasKey('id', $json);
    }

    public function test_verify_otp_rejects_concurrently_registered_phone(): void
    {
        $token = Str::random(64);
        $email = 'racephone@example.com';
        $phone = '+919876543213';
        $otp = '123456';

        $pending = PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Race User',
            'user_id' => 'RACE000001',
            'email' => $email,
            'phone' => $phone,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        // Concurrently insert member with same phone
        Member::create([
            'name' => 'Concurrent Winner',
            'user_id' => 'WINN000001',
            'email' => 'winner@example.com',
            'phone' => $phone,
            'mobile_verified_at' => now(),
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->postJson('/api/member/register/verify', [
                'otp' => $otp,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'This number already exists. Please use the existing login option.',
            'errors' => [
                'phone' => ['This number already exists. Please use the existing login option.'],
            ],
        ]);

        // Pending registration must be cleaned up
        $this->assertDatabaseMissing('pending_member_registrations', [
            'token' => $token,
        ]);

        // Not authenticated
        $this->assertFalse(Auth::guard('member')->check());
    }

    public function test_check_phone_endpoint_validates_input(): void
    {
        // Missing phone
        $responseMissing = $this->getJson('/api/member/register/check-phone');
        $responseMissing->assertStatus(422);

        // Invalid phone (too short)
        $responseInvalid = $this->getJson('/api/member/register/check-phone?phone=123&country_code=+91');
        $responseInvalid->assertStatus(422);
    }

    public function test_trunk_prefix_and_international_phone_normalization(): void
    {
        Mail::fake();

        // 1. National trunk prefix 0
        $response1 = $this->postJson('/api/member/register', [
            'name' => 'Trunk User',
            'email' => 'trunk@example.com',
            'phone' => '09876543214',
            'country_code' => '+91',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $response1->assertOk();
        $this->assertDatabaseHas('pending_member_registrations', [
            'email' => 'trunk@example.com',
            'phone' => '+919876543214',
        ]);

        // 2. International USA number (+1)
        $response2 = $this->postJson('/api/member/register', [
            'name' => 'USA User',
            'email' => 'usa@example.com',
            'phone' => '+1 (202) 555-0199',
            'country_code' => '+1',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $response2->assertOk();
        $this->assertDatabaseHas('pending_member_registrations', [
            'email' => 'usa@example.com',
            'phone' => '+12025550199',
        ]);
    }
}
