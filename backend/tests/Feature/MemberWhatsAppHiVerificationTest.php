<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberWhatsAppHiVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => Hash::make('Secret123!'),
            'user_id' => 'USER_' . strtoupper(uniqid()),
            'phone' => '+919876543210',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ], $attributes));
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/member/account/verification-status')
            ->assertUnauthorized();

        $this->postJson('/api/member/account/verification/initiate')
            ->assertUnauthorized();

        $this->postJson('/api/member/account/verification/submit-hi')
            ->assertUnauthorized();
    }

    public function test_unverified_member_status_returns_unverified_payload(): void
    {
        $member = $this->createMember(['phone' => '+919876543210']);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/verification-status');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_verified' => false,
            'is_pending' => false,
            'verification_status' => 'unverified',
            'phone' => '+919876543210',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ]);

        $json = $response->json();
        $this->assertNotEmpty($json['masked_phone']);
        $this->assertStringContainsString('****', $json['masked_phone']);
        $this->assertNotEmpty($json['whatsapp_destination']);
        $this->assertStringContainsString('https://wa.me/', $json['whatsapp_url']);
        $decodedUrl = urldecode($json['whatsapp_url']);
        $this->assertStringContainsString('Hello Support Team,', $decodedUrl);
        $this->assertStringContainsString('I would like to verify my WhatsApp number for my account.', $decodedUrl);
        $this->assertStringContainsString("User ID: {$member->user_id}", $decodedUrl);
        $this->assertStringContainsString("Name: {$member->name}", $decodedUrl);
        $this->assertStringContainsString("Email: {$member->email}", $decodedUrl);
        $this->assertStringContainsString("Mobile Number: {$member->phone}", $decodedUrl);
        $this->assertStringContainsString('Kindly verify and link this number to my account. Please let me know if any additional information is required.', $decodedUrl);
        $this->assertStringContainsString('Thank you.', $decodedUrl);
        $this->assertStringNotContainsString('Hey,', $decodedUrl);
        $this->assertStringNotContainsString('I am here for WhatsApp verification', $decodedUrl);
    }

    public function test_initiate_verification_returns_whatsapp_deep_link(): void
    {
        $member = $this->createMember(['phone' => '+919876543210']);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/initiate');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'ready',
            'is_verified' => false,
            'is_pending' => false,
            'registered_phone' => '+919876543210',
        ]);

        $url = $response->json('whatsapp_url');
        $this->assertStringStartsWith('https://wa.me/', $url);

        $decoded = urldecode($url);
        $this->assertStringContainsString('Hello Support Team,', $decoded);
        $this->assertStringContainsString('I would like to verify my WhatsApp number for my account.', $decoded);
        $this->assertStringContainsString("User ID: {$member->user_id}", $decoded);
        $this->assertStringContainsString("Name: {$member->name}", $decoded);
        $this->assertStringContainsString("Email: {$member->email}", $decoded);
        $this->assertStringContainsString("Mobile Number: {$member->phone}", $decoded);
        $this->assertStringContainsString('Kindly verify and link this number to my account. Please let me know if any additional information is required.', $decoded);
        $this->assertStringContainsString('Thank you.', $decoded);

        // Ensure old template strings are completely absent
        $this->assertStringNotContainsString('Hey,', $decoded);
        $this->assertStringNotContainsString('I am here for WhatsApp verification', $decoded);

        // Ensure no passwords, OTPs, or authentication tokens leaked in URL
        $this->assertStringNotContainsString('password', strtolower($decoded));
        $this->assertStringNotContainsString('token', strtolower($decoded));
        $this->assertStringNotContainsString('otp', strtolower($decoded));
    }

    public function test_submit_hi_records_pending_request_and_leaves_mobile_verified_at_strictly_null(): void
    {
        $member = $this->createMember([
            'phone' => '+919876543210',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/submit-hi');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'pending',
            'is_verified' => false,
            'is_pending' => true,
            'message' => 'Verification request submitted. Your account is pending manual verification.',
        ]);

        $fresh = $member->fresh();
        // CRITICAL PHASE 3 INVARIANT:
        $this->assertNotNull($fresh->mobile_verification_requested_at);
        $this->assertNull($fresh->mobile_verified_at);
        $this->assertFalse($fresh->isMobileVerified());
        $this->assertTrue($fresh->isMobileVerificationPending());
        $this->assertSame('pending', $fresh->verification_status);

        // Verification status endpoint now reflects pending
        $statusResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/verification-status');

        $statusResponse->assertOk();
        $statusResponse->assertJson([
            'is_verified' => false,
            'is_pending' => true,
            'verification_status' => 'pending',
        ]);
    }

    public function test_submit_hi_is_idempotent(): void
    {
        $member = $this->createMember([
            'phone' => '+919876543210',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/submit-hi');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'pending',
            'is_verified' => false,
            'is_pending' => true,
        ]);

        $fresh = $member->fresh();
        $this->assertNotNull($fresh->mobile_verification_requested_at);
        $this->assertNull($fresh->mobile_verified_at);
    }

    public function test_already_verified_member_is_handled_cleanly(): void
    {
        $verifiedAt = now()->subDays(2);
        $member = $this->createMember([
            'phone' => '+919876543210',
            'mobile_verified_at' => $verifiedAt,
            'mobile_verification_requested_at' => now()->subDays(3),
        ]);

        $statusResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/verification-status');

        $statusResponse->assertOk();
        $statusResponse->assertJson([
            'is_verified' => true,
            'is_pending' => false,
            'verification_status' => 'verified',
        ]);

        $initResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/initiate');

        $initResponse->assertOk();
        $initResponse->assertJson([
            'success' => true,
            'status' => 'verified',
            'is_verified' => true,
        ]);

        $submitResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/submit-hi');

        $submitResponse->assertOk();
        $submitResponse->assertJson([
            'success' => true,
            'status' => 'verified',
            'is_verified' => true,
        ]);

        // Verified timestamp untouched
        $this->assertEquals($verifiedAt->timestamp, $member->fresh()->mobile_verified_at->timestamp);
    }

    public function test_missing_phone_returns_validation_error(): void
    {
        $member = $this->createMember([
            'phone' => null,
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/initiate')
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');

        $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/submit-hi')
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_submit_hi_triggers_admin_notification(): void
    {
        \App\Models\Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $member = $this->createMember([
            'phone' => '+919876543210',
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($member, 'member')
            ->postJson('/api/member/account/verification/submit-hi')
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => \App\Notifications\AdminAlertNotification::class,
        ]);
    }
}
