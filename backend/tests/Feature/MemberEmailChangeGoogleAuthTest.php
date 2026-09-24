<?php

namespace Tests\Feature;

use App\Mail\MemberEmailVerificationOtp;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
use Tests\TestCase;

class MemberEmailChangeGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * Scenario 1: Email Change Detaches Google ID
     * Verifying a new email via OTP updates members.email and resets members.google_id to null.
     */
    public function test_scenario_1_email_change_detaches_google_id(): void
    {
        Mail::fake();

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'JOHNDOE01',
            'email' => 'old_email@gmail.com',
            'password' => Hash::make('Secret123!'),
            'google_id' => 'google_old_123',
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        // 1. Request OTP for new email
        $sendResponse = $this->postJson('/api/member/account/email/send-otp', [
            'new_email' => 'new_email@gmail.com',
        ]);
        $sendResponse->assertOk();

        // Ensure google_id is preserved before verification
        $this->assertSame('google_old_123', $member->fresh()->google_id);

        // Retrieve dispatched OTP code
        $otpCode = null;
        Mail::assertSent(MemberEmailVerificationOtp::class, function ($mail) use (&$otpCode) {
            $otpCode = $mail->otpCode;
            return true;
        });
        $this->assertNotNull($otpCode);

        // 2. Submit OTP to verify email change
        $verifyResponse = $this->postJson('/api/member/account/email/verify-otp', [
            'email_otp' => $otpCode,
        ]);
        $verifyResponse->assertOk();

        // 3. Assert members.email updated and members.google_id detached to null
        $refreshed = $member->fresh();
        $this->assertSame('new_email@gmail.com', $refreshed->email);
        $this->assertNull($refreshed->google_id);
    }

    /**
     * Scenario 2: Old Google Account Login Attempt After Email Change
     * Attempting to log in with the old Google account must fail and redirect with signup_required.
     */
    public function test_scenario_2_old_google_account_cannot_login_after_email_change(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'JOHNDOE01',
            'email' => 'new_email@gmail.com',
            'password' => Hash::make('Secret123!'),
            'google_id' => null, // Detached after email change
            'mobile_verified_at' => now(),
        ]);

        // Old Google user attempts OAuth callback in login mode
        $this->fakeGoogleUser([
            'id' => 'google_old_123',
            'name' => 'John Doe',
            'email' => 'old_email@gmail.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(str_contains($location, 'error=signup_required'));

        // Member must NOT be authenticated and member record untouched
        $this->assertGuest('member');
        $this->assertNull($member->fresh()->google_id);
    }

    /**
     * Scenario 3: New Google Account Successfully Links and Authenticates
     * User logs in with a new Google account matching the new email; it safely links and logs in.
     */
    public function test_scenario_3_new_google_account_successfully_links_and_authenticates(): void
    {
        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'JOHNDOE01',
            'email' => 'new_email@gmail.com',
            'password' => Hash::make('Secret123!'),
            'google_id' => null, // Detached after email change
            'mobile_verified_at' => now(),
        ]);

        // New Google account with new email
        $this->fakeGoogleUser([
            'id' => 'google_new_456',
            'name' => 'John Doe New',
            'email' => 'new_email@gmail.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertTrue(
            $response->isRedirect(route('member.dashboard')) ||
            str_contains((string) $response->headers->get('Location'), '/member/dashboard')
        );

        // Member is authenticated and google_id linked to the new Google ID
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertSame('google_new_456', $member->fresh()->google_id);

        // Subsequent Google login with google_new_456 succeeds directly
        $this->fakeGoogleUser([
            'id' => 'google_new_456',
            'name' => 'John Doe New',
            'email' => 'new_email@gmail.com',
            'email_verified' => true,
        ]);

        $subsequentResponse = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $subsequentResponse->assertRedirect();
        $this->assertAuthenticatedAs($member, 'member');
    }

    /**
     * Scenario 4: Direct Password Login with New Email
     * Password login succeeds with the new email and fails with the old email.
     */
    public function test_scenario_4_password_login_succeeds_with_new_email_and_fails_with_old_email(): void
    {
        Member::create([
            'name' => 'John Doe',
            'user_id' => 'JOHNDOE01',
            'email' => 'new_email@gmail.com',
            'password' => Hash::make('Secret123!'),
            'google_id' => null,
            'mobile_verified_at' => now(),
        ]);

        // 1. Login with old email fails
        $failResponse = $this->postJson(route('member.login'), [
            'email' => 'old_email@gmail.com',
            'password' => 'Secret123!',
        ]);
        $failResponse->assertStatus(422);
        $this->assertGuest('member');

        // 2. Login with new email succeeds
        $successResponse = $this->post(route('member.login'), [
            'email' => 'new_email@gmail.com',
            'password' => 'Secret123!',
        ]);
        $successResponse->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticated('member');
    }

    /**
     * Scenario 5: Unchanged Google Account Authentication (Regression Test)
     * Existing Google member who has not changed their email continues to authenticate normally.
     */
    public function test_scenario_5_unchanged_google_member_authenticates_normally(): void
    {
        $member = Member::create([
            'name' => 'Alice Smith',
            'user_id' => 'ALICESMITH1',
            'email' => 'alice@gmail.com',
            'password' => Hash::make('Secret123!'),
            'google_id' => 'google_alice_789',
            'mobile_verified_at' => now(),
        ]);

        $this->fakeGoogleUser([
            'id' => 'google_alice_789',
            'name' => 'Alice Smith',
            'email' => 'alice@gmail.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $this->assertTrue(
            $response->isRedirect(route('member.dashboard')) ||
            str_contains((string) $response->headers->get('Location'), '/member/dashboard')
        );

        $this->assertAuthenticatedAs($member, 'member');
        $this->assertSame('google_alice_789', $member->fresh()->google_id);
    }

    /**
     * Scenario 6: Defense-in-depth Email Mismatch Protection
     * If an account somehow has a stale google_id that does not match the incoming Google email,
     * the callback must deny login, log a warning, and redirect to login with an error message.
     */
    public function test_scenario_6_stale_google_id_email_mismatch_is_denied(): void
    {
        Log::spy();

        // Stale state: member email was changed, but google_id retained old ID
        $member = Member::create([
            'name' => 'Stale Member',
            'user_id' => 'STALEMEM01',
            'email' => 'updated_new@example.com',
            'password' => Hash::make('Secret123!'),
            'google_id' => 'google_stale_999',
            'mobile_verified_at' => now(),
        ]);

        // Old Google user with old email and the stale google_id attempts callback
        $this->fakeGoogleUser([
            'id' => 'google_stale_999',
            'name' => 'Stale Member',
            'email' => 'stale_old@gmail.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(
            str_contains($location, '/member/login') ||
            $response->isRedirect(route('member.login'))
        );
        $this->assertTrue(
            str_contains($location, 'error=') ||
            session()->has('error')
        );

        // Warning logged for email mismatch on google_id lookup
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($member): bool {
                return $message === 'Google Member email mismatch on google_id lookup'
                    && ($context['member_id'] ?? null) === $member->id
                    && ($context['member_email'] ?? null) === 'updated_new@example.com'
                    && ($context['google_email'] ?? null) === 'stale_old@gmail.com'
                    && ($context['google_id'] ?? null) === 'google_stale_999';
            });

        $this->assertGuest('member');
    }
}
