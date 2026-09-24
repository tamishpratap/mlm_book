<?php

namespace Tests\Feature;

use App\Mail\MemberEmailVerificationOtp;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountSettingsEmailCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Tamish Pratap',
            'email' => 'tamishpratap1713@gmail.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'google_id' => 'google_oauth_123456',
        ], $attributes));
    }

    public function test_frontend_account_settings_page_removed_extra_ui(): void
    {
        $jsxPath = base_path('../frontend/src/pages/account/AccountSettingsPage.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // 1. Assert removed extra text and badges
        $this->assertStringNotContainsString('Code sent to current email to verify changes.', $jsx);
        $this->assertStringNotContainsString('Protected', $jsx);
        $this->assertStringNotContainsString('Google connection linked', $jsx);
        $this->assertStringNotContainsString('A confirmation code will be sent to your current email address.', $jsx);

        // 2. Assert required elements remain
        $this->assertStringContainsString('Email Address</h2>', $jsx);
        $this->assertStringContainsString('Current Email</span>', $jsx);
        $this->assertStringContainsString('{member.email}</strong>', $jsx);
        $this->assertStringContainsString('New email address', $jsx);
        $this->assertStringContainsString('placeholder="new.email@example.com"', $jsx);
        $this->assertStringContainsString('Send Code', $jsx);
        $this->assertStringContainsString('handleSendEmailOtp', $jsx);
        $this->assertStringContainsString('handleVerifyEmailOtp', $jsx);
    }

    public function test_blade_account_settings_removed_extra_ui_and_preserves_form(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->get(route('member.account.settings'));

        $response->assertOk();

        // 1. Assert removed extra text and badges
        $response->assertDontSee('The code is sent to your current email before any change is saved.');
        $response->assertDontSee('Google connection remains linked after an email change.');
        $response->assertDontSee('You must have access to ' . $member->email . ' to approve this change.');

        // 2. Assert required elements are rendered
        $response->assertSee('Email Address');
        $response->assertSee('Current email');
        $response->assertSee($member->email);
        $response->assertSee('New email address');
        $response->assertSee('Send Code');
    }

    public function test_email_otp_backend_flow_and_google_account_intact(): void
    {
        Mail::fake();

        $member = $this->createMember();
        $this->actingAs($member, 'member');

        // Member's google_id is preserved
        $this->assertEquals('google_oauth_123456', $member->fresh()->google_id);

        // Existing email OTP send functionality works properly
        $response = $this->postJson('/api/member/account/email/send-otp', [
            'new_email' => 'updated.tamish@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        // Google ID is still intact before verification
        $this->assertEquals('google_oauth_123456', $member->fresh()->google_id);
    }

    public function test_email_otp_verification_updates_email_and_clears_google_id(): void
    {
        Mail::fake();

        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $this->postJson('/api/member/account/email/send-otp', [
            'new_email' => 'updated.tamish@example.com',
        ])->assertOk();

        $otpCode = null;
        Mail::assertSent(MemberEmailVerificationOtp::class, function ($mail) use (&$otpCode) {
            $otpCode = $mail->otpCode;
            return true;
        });

        $this->assertNotNull($otpCode);

        $verifyResponse = $this->postJson('/api/member/account/email/verify-otp', [
            'email_otp' => $otpCode,
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJson([
            'success' => true,
        ]);

        $refreshed = $member->fresh();
        $this->assertEquals('updated.tamish@example.com', $refreshed->email);
        $this->assertNull($refreshed->google_id);
    }
}
