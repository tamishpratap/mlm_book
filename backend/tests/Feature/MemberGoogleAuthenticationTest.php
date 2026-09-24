<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PendingMemberRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MemberGoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_use_the_shared_google_redirect_route(): void
    {
        $googleUrl = route('member.google.redirect');

        $this->get(route('member.login'))
            ->assertOk()
            ->assertSee('Login with Google')
            ->assertSee($googleUrl, false);

        $this->get(route('member.register'))
            ->assertOk()
            ->assertSee('Sign up with Google')
            ->assertSee($googleUrl, false);
    }

    public function test_login_mode_unregistered_google_triggers_signup_required(): void
    {
        $initialMemberCount = Member::count();

        $this->fakeGoogleUser([
            'id' => 'google-new-unregistered-123',
            'name' => 'New Unregistered User',
            'email' => 'unregistered@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(
            str_contains($location, 'error=signup_required') ||
            $response->isRedirect(route('member.login', ['error' => 'signup_required']))
        );

        $this->assertSame($initialMemberCount, Member::count());
        $this->assertSame(0, Member::count());
        $this->assertSame(0, PendingMemberRegistration::count());
        $this->assertGuest('member');
    }

    public function test_login_mode_unregistered_google_preserves_referral_parameter(): void
    {
        $initialMemberCount = Member::count();

        $this->fakeGoogleUser([
            'id' => 'google-referred-123',
            'name' => 'Referred Unregistered User',
            'email' => 'referred-unregistered@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login', 'ref' => 'sponsor_john'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(str_contains($location, 'error=signup_required'));
        $this->assertTrue(str_contains($location, 'ref=sponsor_john'));

        $this->assertSame($initialMemberCount, Member::count());
        $this->assertSame(0, Member::count());
        $this->assertGuest('member');
    }

    public function test_login_mode_existing_google_member_logs_in_directly(): void
    {
        $member = Member::create([
            'name' => 'Existing Google Member',
            'email' => 'existing-google@example.com',
            'password' => 'password123',
            'google_id' => 'google-existing-789',
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-existing-789',
            'name' => 'Existing Google Member',
            'email' => 'existing-google@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $this->assertTrue(
            $response->isRedirect(route('member.dashboard')) ||
            str_contains((string) $response->headers->get('Location'), '/member/dashboard')
        );

        $this->assertSame(1, Member::count());
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_register_mode_existing_google_member_shows_account_exists_error(): void
    {
        $member = Member::create([
            'name' => 'Existing Google Member',
            'email' => 'existing-signup@example.com',
            'password' => 'password123',
            'google_id' => 'google-existing-555',
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-existing-555',
            'name' => 'Existing Google Member',
            'email' => 'existing-signup@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'register'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(
            str_contains($location, 'error=account_exists') ||
            $response->isRedirect(route('member.register', ['error' => 'account_exists']))
        );

        // No duplicate member created
        $this->assertSame(1, Member::count());
        $this->assertGuest('member');
    }

    public function test_register_mode_new_google_account_creates_pending_state_and_redirects_to_introducer_screen(): void
    {
        $this->fakeGoogleUser([
            'id' => 'google-newbie-999',
            'name' => 'New Google Registrant',
            'email' => 'newbie@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'register', 'ref' => 'teart3'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(str_contains($location, '/member/google-introducer'));
        $this->assertTrue(str_contains($location, 'ref=teart3'));
        $this->assertTrue(str_contains($location, 'token='));

        // Member not yet created before Introducer decision
        $this->assertSame(0, Member::count());
        $this->assertSame(1, PendingMemberRegistration::count());
    }

    public function test_google_signup_completion_with_skip_creates_member_without_introducer(): void
    {
        $token = 'test_token_skip_123';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Skip Introducer User',
            'user_id' => 'skip_user_1',
            'email' => 'skip@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-skip-id-123',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Skip Introducer User',
            'email' => 'skip@example.com',
            'google_id' => 'google-skip-id-123',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876543210',
            'skip' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your account has been created successfully. You can now log in.',
                'redirect' => '/member/login?success=account_created',
            ]);

        $this->assertSame(1, Member::count());
        $member = Member::where('email', 'skip@example.com')->first();
        $this->assertNotNull($member);
        $this->assertSame('+919876543210', $member->phone);
        $this->assertNull($member->introducer_id);
        $this->assertSame(0, $member->direct_referral_count);

        // Phase 76: Do NOT auto-login new Google member
        $this->assertGuest('member');

        // Token cleaned up
        $this->assertSame(0, PendingMemberRegistration::count());
    }

    public function test_google_signup_completion_with_valid_verified_introducer_links_referral(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor John',
            'user_id' => 'sponsor_john',
            'email' => 'sponsor@example.com',
            'password' => 'secret123',
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $token = 'test_token_ref_456';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Referred Newbie',
            'user_id' => 'referred_newbie',
            'email' => 'newbie-ref@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-ref-id-456',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Referred Newbie',
            'email' => 'newbie-ref@example.com',
            'google_id' => 'google-ref-id-456',
            'ref' => 'sponsor_john',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '+919111223344',
            'introducer_id' => 'sponsor_john',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your account has been created successfully. You can now log in.',
                'redirect' => '/member/login?success=account_created',
            ]);

        $newbie = Member::where('email', 'newbie-ref@example.com')->first();
        $this->assertNotNull($newbie);
        $this->assertSame('+919111223344', $newbie->phone);
        $this->assertSame('sponsor_john', $newbie->introducer_id);

        // Phase 76: Do NOT auto-login new Google member
        $this->assertGuest('member');

        // At registration: referral count unchanged
        $this->assertSame(0, $sponsor->fresh()->direct_referral_count);

        // After newbie mobile verification: referral count qualifies +1
        $newbie->mobile_verified_at = now();
        $newbie->save();

        $newbie->qualifyReferral();
        $this->assertSame(1, $sponsor->fresh()->direct_referral_count);

        // Idempotency: repeated call does not increment again
        $newbie->qualifyReferral();
        $this->assertSame(1, $sponsor->fresh()->direct_referral_count);
    }

    public function test_newly_created_google_member_can_manually_login_afterwards(): void
    {
        // 1. Create Google member via signup completion
        $token = 'test_token_login_after_signup';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Newly Signed Up',
            'user_id' => 'newly_signed_up',
            'email' => 'newly_created@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-newly-id',
            'expires_at' => now()->addMinutes(30),
        ]);

        $signupResponse = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876500001',
            'skip' => true,
        ]);

        $signupResponse->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => '/member/login?success=account_created',
            ]);

        $this->assertGuest('member');

        // 2. Member manually logs in using Google Login (mode=login)
        $this->fakeGoogleUser([
            'id' => 'google-newly-id',
            'name' => 'Newly Signed Up',
            'email' => 'newly_created@example.com',
            'email_verified' => true,
        ]);

        session(['google_oauth_mode' => 'login']);

        $loginResponse = $this->get(route('member.google.callback'));
        $loginResponse->assertRedirect();
        $location = (string) $loginResponse->headers->get('Location');
        $this->assertTrue(str_contains($location, '/member/dashboard'));

        $member = Member::where('email', 'newly_created@example.com')->first();
        $this->assertNotNull($member);
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_google_signup_completion_rejects_unverified_introducer(): void
    {
        $unverifiedSponsor = Member::create([
            'name' => 'Unverified Sponsor',
            'user_id' => 'unverified_sponsor',
            'email' => 'unverified-sponsor@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => null, // Unverified
        ]);

        $token = 'test_token_unverified_789';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Test User',
            'user_id' => 'test_user_789',
            'email' => 'test789@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-id-789',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876500002',
            'introducer_id' => 'unverified_sponsor',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id']);

        $this->assertSame(1, Member::count()); // Only sponsor exists
    }

    public function test_google_signup_completion_rejects_invalid_introducer(): void
    {
        $token = 'test_token_invalid_000';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Test User',
            'user_id' => 'test_user_000',
            'email' => 'test000@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-id-000',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876500003',
            'introducer_id' => 'non_existent_introducer_id',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id']);

        $this->assertSame(0, Member::count());
    }

    public function test_google_signup_completion_rejects_self_referral(): void
    {
        $existing = Member::create([
            'name' => 'Self Sponsor',
            'user_id' => 'self_sponsor',
            'email' => 'sponsor@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $token = 'test_token_self_111';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Self User',
            'user_id' => 'self_sponsor', // Matching user_id
            'email' => 'new-unique-email@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-id-111',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Self User',
            'email' => 'new-unique-email@example.com',
            'google_id' => 'google-id-111',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876500004',
            'introducer_id' => 'self_sponsor',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['introducer_id']);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogleUser([
            'id' => 'google-unverified-123',
            'name' => 'Unverified Member',
            'email' => 'unverified@example.com',
            'email_verified' => false,
        ]);

        $response = $this->get(route('member.google.callback'));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(str_contains($location, '/member/login'));
        $this->assertTrue(str_contains($location, 'error=') || session()->has('error'));

        $this->assertDatabaseMissing('members', [
            'email' => 'unverified@example.com',
        ]);
        $this->assertGuest('member');
    }

    public function test_google_callback_handles_cancellation_safely(): void
    {
        $response = $this->get(route('member.google.callback', ['error' => 'access_denied']));
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(str_contains($location, '/member/login'));
        $this->assertTrue(str_contains($location, 'error=') || session()->has('error'));

        $this->assertGuest('member');
    }

    public function test_google_callback_logs_failures_without_callback_secrets(): void
    {
        Log::spy();

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new RuntimeException('Controlled Google failure.'));
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('member.google.callback', [
            'code' => 'secret-authorization-code',
            'state' => 'secret-state-value',
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertTrue(str_contains($location, '/member/login'));
        $this->assertTrue(str_contains($location, 'error=') || session()->has('error'));

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Google Member authentication failed'
                    && $context['exception'] === RuntimeException::class
                    && $context['message'] === 'Controlled Google failure.'
                    && ! str_contains($context['request_url'], 'code=')
                    && ! str_contains($context['request_url'], 'state=')
                    && $context['session_started'] === true
                    && $context['session_has_id'] === true;
            });
    }

    public function test_google_signup_completion_stores_avatar_and_resolves_profile_photo_url(): void
    {
        $token = 'test_token_avatar_999';
        $googleAvatar = 'https://lh3.googleusercontent.com/a/ACg8ocIS_sample_avatar_url=s96-c';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Avatar User',
            'user_id' => 'avatar_user',
            'email' => 'avatar-user@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-avatar-id-999',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Avatar User',
            'email' => 'avatar-user@example.com',
            'google_id' => 'google-avatar-id-999',
            'avatar' => $googleAvatar,
            'ref' => null,
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876500005',
            'skip' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your account has been created successfully. You can now log in.',
            ]);

        $member = Member::where('email', 'avatar-user@example.com')->first();
        $this->assertNotNull($member);
        $this->assertSame($googleAvatar, $member->profile_photo);
        $this->assertSame($googleAvatar, $member->profile_photo_url);
        $this->assertSame($googleAvatar, $member->toArray()['profile_photo_url']);
    }

    public function test_login_mode_blocked_existing_google_member_is_denied_login_and_redirected(): void
    {
        $member = Member::create([
            'name' => 'Blocked Google Member',
            'email' => 'blocked-google@example.com',
            'password' => 'password123',
            'google_id' => 'google-blocked-111',
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-blocked-111',
            'name' => 'Blocked Google Member',
            'email' => 'blocked-google@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $expectedMsg = 'Your account has been blocked by the admin. You cannot log in.';
        $this->assertTrue(
            str_contains($location, '/member/login') &&
            str_contains(urldecode($location), $expectedMsg)
        );

        $this->assertGuest('member');
    }

    public function test_login_mode_blocked_email_matched_member_is_denied_and_google_id_not_linked(): void
    {
        $member = Member::create([
            'name' => 'Blocked Email Member',
            'email' => 'blocked-email@example.com',
            'password' => 'password123',
            'google_id' => null,
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-new-id-222',
            'name' => 'Blocked Email Member',
            'email' => 'blocked-email@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $expectedMsg = 'Your account has been blocked by the admin. You cannot log in.';
        $this->assertTrue(
            str_contains($location, '/member/login') &&
            str_contains(urldecode($location), $expectedMsg)
        );

        $member->refresh();
        $this->assertNull($member->google_id);
        $this->assertGuest('member');
    }

    public function test_login_mode_blocked_member_redirects_to_blade_route_when_frontend_url_is_not_set(): void
    {
        config(['app.frontend_url' => null]);
        putenv('FRONTEND_URL=');
        $_ENV['FRONTEND_URL'] = null;
        $_SERVER['FRONTEND_URL'] = null;

        $member = Member::create([
            'name' => 'Blocked Blade Member',
            'email' => 'blocked-blade@example.com',
            'password' => 'password123',
            'google_id' => 'google-blade-333',
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->fakeGoogleUser([
            'id' => 'google-blade-333',
            'name' => 'Blocked Blade Member',
            'email' => 'blocked-blade@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $response->assertRedirect(route('member.login'));
        $response->assertSessionHas('error', 'Your account has been blocked by the admin. You cannot log in.');
        $response->assertSessionHasErrors([
            'email' => 'Your account has been blocked by the admin. You cannot log in.',
        ]);
        $this->assertGuest('member');
    }

    public function test_unblock_restores_google_login(): void
    {
        $member = Member::create([
            'name' => 'Unblocked Google Member',
            'email' => 'unblocked-google@example.com',
            'password' => 'password123',
            'google_id' => 'google-unblock-444',
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        // Unblock
        $member->update(['blocked_at' => null]);

        $this->fakeGoogleUser([
            'id' => 'google-unblock-444',
            'name' => 'Unblocked Google Member',
            'email' => 'unblocked-google@example.com',
            'email_verified' => true,
        ]);

        $response = $this->get(route('member.google.callback', [
            'state' => base64_encode(json_encode(['mode' => 'login'])),
        ]));

        $this->assertTrue(
            $response->isRedirect(route('member.dashboard')) ||
            str_contains((string) $response->headers->get('Location'), '/member/dashboard')
        );
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_google_signup_completion_for_blocked_existing_member_returns_403_and_leaves_no_session(): void
    {
        Member::create([
            'name' => 'Existing Blocked Member',
            'user_id' => 'EXISTINGBLK1',
            'email' => 'existing-blocked@example.com',
            'password' => Hash::make('password123'),
            'google_id' => 'google-blocked-existing-999',
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $token = 'test_token_blocked_existing';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Existing Blocked Member',
            'user_id' => 'EXISTINGBLK1',
            'email' => 'existing-blocked@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-blocked-existing-999',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Existing Blocked Member',
            'email' => 'existing-blocked@example.com',
            'google_id' => 'google-blocked-existing-999',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876500006',
            'skip' => true,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'ACCOUNT_BLOCKED',
                'message' => 'Your account has been blocked by the admin. You cannot log in.',
                'errors' => [
                    'email' => ['Your account has been blocked by the admin. You cannot log in.'],
                ],
            ]);

        $this->assertGuest('member');
    }

    public function test_google_signup_completion_requires_valid_phone(): void
    {
        $token = 'test_token_req_phone_123';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Req Phone User',
            'user_id' => 'req_phone_1',
            'email' => 'reqphone@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-req-phone-123',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Req Phone User',
            'email' => 'reqphone@example.com',
            'google_id' => 'google-req-phone-123',
        ], now()->addMinutes(30));

        // 1. Missing phone
        $responseNoPhone = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
        ]);
        $responseNoPhone->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        // 2. Empty phone
        $responseEmptyPhone = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '',
        ]);
        $responseEmptyPhone->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        // 3. Invalid phone format (less than 10 digits for +91)
        $responseInvalidPhone = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '12345',
        ]);
        $responseInvalidPhone->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        $this->assertSame(0, Member::where('email', 'reqphone@example.com')->count());
    }

    public function test_google_signup_completion_rejects_duplicate_phone(): void
    {
        // Existing member with this phone
        Member::create([
            'name' => 'Existing Phone Owner',
            'user_id' => 'phone_owner1',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
        ]);

        $token = 'test_token_dup_phone_456';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Duplicate Phone User',
            'user_id' => 'dup_phone_1',
            'email' => 'dupphone@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-dup-phone-456',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Duplicate Phone User',
            'email' => 'dupphone@example.com',
            'google_id' => 'google-dup-phone-456',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876543210', // Raw 10 digits normalizes to +919876543210
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonPath('errors.phone.0', 'This number already exists. Please use the existing login option.');

        $this->assertSame(0, Member::where('email', 'dupphone@example.com')->count());
    }

    public function test_google_signup_completion_saves_phone_into_members_phone_column(): void
    {
        $token = 'test_token_save_phone_789';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Saved Phone Member',
            'user_id' => 'save_phone_1',
            'email' => 'savedphone@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-save-phone-789',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'Saved Phone Member',
            'email' => 'savedphone@example.com',
            'google_id' => 'google-save-phone-789',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876599999',
            'country_code' => '+91',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'member' => [
                    'email' => 'savedphone@example.com',
                    'phone' => '+919876599999',
                ],
            ]);

        $member = Member::where('email', 'savedphone@example.com')->first();
        $this->assertNotNull($member);
        $this->assertSame('+919876599999', $member->phone);
        $this->assertNull($member->mobile_verified_at);
    }

    public function test_google_signup_completion_succeeds_with_valid_phone_and_no_introducer(): void
    {
        $token = 'test_token_no_intro_888';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'No Introducer Member',
            'user_id' => 'no_intro_888',
            'email' => 'nointro@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-no-intro-888',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'No Introducer Member',
            'email' => 'nointro@example.com',
            'google_id' => 'google-no-intro-888',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876588888',
            'introducer_id' => '', // explicitly blank
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your account has been created successfully. You can now log in.',
            ]);

        $member = Member::where('email', 'nointro@example.com')->first();
        $this->assertNotNull($member);
        $this->assertSame('+919876588888', $member->phone);
        $this->assertNull($member->introducer_id);
    }

    public function test_google_signup_completion_succeeds_with_valid_phone_and_valid_introducer(): void
    {
        $sponsor = Member::create([
            'name' => 'Verified Sponsor',
            'user_id' => 'sponsor_verified',
            'email' => 'ver_sponsor@example.com',
            'password' => 'secret123',
            'phone' => '+919876511111',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $token = 'test_token_with_intro_999';
        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'With Introducer Member',
            'user_id' => 'with_intro_999',
            'email' => 'withintro@example.com',
            'password_hash' => Hash::make('secret'),
            'otp_hash' => 'google-with-intro-999',
            'expires_at' => now()->addMinutes(30),
        ]);

        Cache::put('pending_google_signup:'.$token, [
            'name' => 'With Introducer Member',
            'email' => 'withintro@example.com',
            'google_id' => 'google-with-intro-999',
            'ref' => 'sponsor_verified',
        ], now()->addMinutes(30));

        $response = $this->postJson(url('/api/member/auth/google/complete'), [
            'token' => $token,
            'phone' => '9876577777',
            'introducer_id' => 'sponsor_verified',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your account has been created successfully. You can now log in.',
            ]);

        $member = Member::where('email', 'withintro@example.com')->first();
        $this->assertNotNull($member);
        $this->assertSame('+919876577777', $member->phone);
        $this->assertSame('sponsor_verified', $member->introducer_id);
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
}
