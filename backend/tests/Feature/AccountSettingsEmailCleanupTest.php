<?php

namespace Tests\Feature;

use App\Mail\MemberEmailVerificationOtp;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
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

    private function fakeGoogleUser(array $attributes): void
    {
        $googleUser = (new User)->setRaw($attributes)->map([
            'id' => $attributes['id'] ?? null,
            'name' => $attributes['name'] ?? null,
            'email' => $attributes['email'] ?? null,
        ]);
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
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

        // Member's google_id is preserved before verification
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

    /**
     * Test A: Email change clears stale google_id.
     */
    public function test_a_email_change_clears_stale_google_id(): void
    {
        Mail::fake();

        $member = $this->createMember([
            'email' => 'old_email_a@example.com',
            'google_id' => 'google_stale_aaa',
        ]);
        $this->actingAs($member, 'member');

        $this->postJson('/api/member/account/email/send-otp', [
            'new_email' => 'new_email_a@example.com',
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

        $refreshed = $member->fresh();
        $this->assertEquals('new_email_a@example.com', $refreshed->email);
        $this->assertNull($refreshed->google_id);
    }

    /**
     * Test B: New Google account with matching new email can be linked successfully.
     */
    public function test_b_new_google_account_with_matching_new_email_can_be_linked_successfully(): void
    {
        $member = $this->createMember([
            'email' => 'new_verified_b@example.com',
            'google_id' => null, // Stale google_id cleared by email change
        ]);

        $this->fakeGoogleUser([
            'id' => 'google_id_new_bbb',
            'name' => 'Tamish New Google',
            'email' => 'new_verified_b@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertEquals('google_id_new_bbb', $member->fresh()->google_id);
    }

    /**
     * Test C: Old Google account no longer authenticates the member after the email change.
     */
    public function test_c_old_google_account_no_longer_authenticates_member_after_email_change(): void
    {
        // Case 1: Member email was updated to new email and google_id was set to null
        $member = $this->createMember([
            'email' => 'current_new_c@example.com',
            'google_id' => null,
        ]);

        $this->fakeGoogleUser([
            'id' => 'google_id_old_ccc',
            'name' => 'Tamish Old Google',
            'email' => 'old_previous_c@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertGuest('member');
        $this->assertNull($member->fresh()->google_id);

        // Case 2: Even if member record still held stale google_id from legacy state, old Google account is rejected and stale ID cleared
        $staleMember = $this->createMember([
            'email' => 'updated_email_c2@example.com',
            'google_id' => 'google_stale_legacy_ccc',
        ]);

        $this->fakeGoogleUser([
            'id' => 'google_stale_legacy_ccc',
            'name' => 'Tamish Old Google 2',
            'email' => 'old_email_c2@example.com',
            'email_verified' => true,
        ]);

        $response2 = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response2->assertRedirect();
        $this->assertGuest('member');
        // Stale google_id on that member must have been cleared
        $this->assertNull($staleMember->fresh()->google_id);
    }

    /**
     * Test D: Google callback with matching current email and NULL google_id successfully links the new Google ID.
     */
    public function test_d_google_callback_with_matching_current_email_and_null_google_id_successfully_links_new_google_id(): void
    {
        $member = $this->createMember([
            'email' => 'member_d@example.com',
            'google_id' => null,
        ]);

        $this->fakeGoogleUser([
            'id' => 'google_id_fresh_ddd',
            'name' => 'Member D',
            'email' => 'member_d@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertEquals('google_id_fresh_ddd', $member->fresh()->google_id);
    }

    /**
     * Test E: Google callback with mismatched Google email does NOT authenticate the member.
     */
    public function test_e_google_callback_with_mismatched_google_email_does_not_authenticate_member(): void
    {
        $member = $this->createMember([
            'email' => 'legitimate_owner@example.com',
            'google_id' => null,
        ]);

        // Incoming Google identity has a different email
        $this->fakeGoogleUser([
            'id' => 'google_attacker_eee',
            'name' => 'Different User',
            'email' => 'attacker_different@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertGuest('member');
        $this->assertNull($member->fresh()->google_id);
    }

    /**
     * Test F: Google callback comparing emails is case-insensitive.
     */
    public function test_f_google_callback_comparing_emails_is_case_insensitive(): void
    {
        $member = $this->createMember([
            'email' => 'CaseSensitiveUser@Example.Com',
            'google_id' => null,
        ]);

        $this->fakeGoogleUser([
            'id' => 'google_case_fff',
            'name' => 'Case User',
            'email' => 'casesensitiveuser@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertEquals('google_case_fff', $member->fresh()->google_id);
    }

    /**
     * Test G: Existing normal email/password login remains unchanged.
     */
    public function test_g_existing_normal_email_password_login_remains_unchanged(): void
    {
        $member = $this->createMember([
            'email' => 'normal_login@example.com',
            'password' => Hash::make('CorrectPassword123!'),
            'google_id' => 'google_existing_ggg',
        ]);

        // Wrong password fails
        $failResponse = $this->postJson(route('member.login'), [
            'email' => 'normal_login@example.com',
            'password' => 'WrongPassword!',
        ]);
        $failResponse->assertStatus(422);
        $this->assertGuest('member');

        // Correct password succeeds
        $successResponse = $this->post(route('member.login'), [
            'email' => 'normal_login@example.com',
            'password' => 'CorrectPassword123!',
        ]);
        $successResponse->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($member, 'member');
    }
}
