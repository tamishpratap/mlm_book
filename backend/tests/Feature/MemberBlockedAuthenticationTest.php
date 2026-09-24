<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberBlockedAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }
        if (class_exists(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        }
    }

    /**
     * TEST 1 — ACTIVE MEMBER + CORRECT PASSWORD
     */
    public function test_active_member_can_login_successfully(): void
    {
        $member = Member::create([
            'name' => 'Active Member',
            'user_id' => 'ACTV123456',
            'email' => 'active@example.com',
            'password' => Hash::make('secret123'),
            'mobile_verified_at' => now(),
        ]);

        // JSON / API login
        $response = $this->postJson('/api/member/login', [
            'email' => 'active@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged in successfully.',
        ]);
        $this->assertAuthenticatedAs($member, 'member');
    }

    /**
     * TEST 2 — ACTIVE MEMBER + WRONG PASSWORD
     */
    public function test_active_member_with_wrong_password_fails_with_standard_invalid_credentials(): void
    {
        $member = Member::create([
            'name' => 'Active Member',
            'user_id' => 'ACTV123456',
            'email' => 'active@example.com',
            'password' => Hash::make('secret123'),
            'mobile_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/member/login', [
            'email' => 'active@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The provided email or password is incorrect.',
            'errors' => [
                'email' => ['The provided email or password is incorrect.'],
            ],
        ]);
        $this->assertFalse(Auth::guard('member')->check());
    }

    /**
     * TEST 3 & 4 — ADMIN BLOCKS MEMBER AND BLOCKED MEMBER LOGIN WITH CORRECT CREDENTIALS IS DENIED
     */
    public function test_blocked_member_with_correct_credentials_is_denied_with_exact_blocked_message(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpass123'),
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Target Member',
            'user_id' => 'TRGT123456',
            'email' => 'blocked@example.com',
            'password' => Hash::make('secret123'),
            'mobile_verified_at' => now(),
        ]);

        // Admin blocks member
        $blockRes = $this->actingAs($admin, 'admin')->post(route('admin.members.block', $member));
        $member->refresh();
        $this->assertNotNull($member->blocked_at);
        $this->assertTrue($member->isBlocked());

        // Blocked member attempts login via API with CORRECT password
        $response = $this->postJson('/api/member/login', [
            'email' => 'blocked@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'code' => 'ACCOUNT_BLOCKED',
            'message' => 'Your account has been blocked by the admin. You cannot log in.',
            'errors' => [
                'email' => ['Your account has been blocked by the admin. You cannot log in.'],
            ],
        ]);

        // Verify NO authenticated session is created
        $this->assertFalse(Auth::guard('member')->check());
        $this->assertNull(Auth::guard('member')->user());
    }

    /**
     * BLOCKED MEMBER + WRONG PASSWORD DOES NOT LEAK BLOCKED STATUS
     */
    public function test_blocked_member_with_wrong_password_does_not_leak_blocked_status(): void
    {
        Member::create([
            'name' => 'Blocked Member',
            'user_id' => 'BLCK123456',
            'email' => 'blocked@example.com',
            'password' => Hash::make('correctpassword'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/member/login', [
            'email' => 'blocked@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The provided email or password is incorrect.',
            'errors' => [
                'email' => ['The provided email or password is incorrect.'],
            ],
        ]);
        $response->assertJsonMissing(['code' => 'ACCOUNT_BLOCKED']);
        $this->assertFalse(Auth::guard('member')->check());
    }

    /**
     * TEST 5 & 6 — DIRECT API ATTEMPT HAS NO SESSION CREATION AND ME ENDPOINT RETURNS 401
     */
    public function test_direct_api_attempt_leaves_no_session_or_tokens(): void
    {
        Member::create([
            'name' => 'Blocked Member',
            'user_id' => 'BLCK123456',
            'email' => 'blocked@example.com',
            'password' => Hash::make('password123'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/member/login', [
            'email' => 'blocked@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(403);
        $loginResponse->assertJson([
            'code' => 'ACCOUNT_BLOCKED',
            'message' => 'Your account has been blocked by the admin. You cannot log in.',
        ]);

        // Immediately attempt accessing /api/member/me
        $meResponse = $this->getJson('/api/member/me');
        $meResponse->assertStatus(401);
    }

    /**
     * TEST 7 & 8 — UNBLOCK RESTORES NORMAL LOGIN IMMEDIATELY
     */
    public function test_unblock_restores_normal_login_behavior(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpass123'),
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Restorable Member',
            'user_id' => 'RSTR123456',
            'email' => 'restorable@example.com',
            'password' => Hash::make('password123'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        // Attempt while blocked
        $this->postJson('/api/member/login', [
            'email' => 'restorable@example.com',
            'password' => 'password123',
        ])->assertStatus(403);

        // Admin unblocks member
        $this->actingAs($admin, 'admin')->post(route('admin.members.unblock', $member));
        $member->refresh();
        $this->assertNull($member->blocked_at);
        $this->assertFalse($member->isBlocked());

        // Login after unblock succeeds
        $successResponse = $this->postJson('/api/member/login', [
            'email' => 'restorable@example.com',
            'password' => 'password123',
        ]);

        $successResponse->assertStatus(200);
        $successResponse->assertJson([
            'success' => true,
            'message' => 'Logged in successfully.',
        ]);
        $this->assertAuthenticatedAs($member, 'member');
    }

    /**
     * TEST 9 — REPEATED BLOCKED LOGIN ATTEMPTS PRODUCE CONSISTENT 403 RESPONSES
     */
    public function test_repeated_blocked_login_attempts_remain_consistent_without_leaks(): void
    {
        Member::create([
            'name' => 'Repeated Blocked Member',
            'user_id' => 'RPTD123456',
            'email' => 'repeated@example.com',
            'password' => Hash::make('password123'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        for ($i = 0; $i < 4; $i++) {
            $res = $this->postJson('/api/member/login', [
                'email' => 'repeated@example.com',
                'password' => 'password123',
            ]);

            $res->assertStatus(403);
            $res->assertJson([
                'success' => false,
                'code' => 'ACCOUNT_BLOCKED',
                'message' => 'Your account has been blocked by the admin. You cannot log in.',
            ]);
            $this->assertFalse(Auth::guard('member')->check());
        }
    }

    /**
     * TEST 10 — WEB FORM LOGIN FOR BLOCKED MEMBER FLASHES SESSION ERROR AND RENDERS PROMINENT ALERT
     */
    public function test_web_form_login_for_blocked_member_sets_session_error_and_displays_alert(): void
    {
        Member::create([
            'name' => 'Web Blocked Member',
            'user_id' => 'WBBLCK1234',
            'email' => 'webblocked@example.com',
            'password' => Hash::make('secret123'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $response = $this->from('/member/login')->post('/member/login', [
            'email' => 'webblocked@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/member/login');
        $response->assertSessionHas('error', 'Your account has been blocked by the admin. You cannot log in.');
        $response->assertSessionHasErrors([
            'email' => 'Your account has been blocked by the admin. You cannot log in.',
        ]);
        $this->assertFalse(Auth::guard('member')->check());

        // Follow the redirect and verify HTML rendered
        $followResponse = $this->get('/member/login');
        $followResponse->assertStatus(200);
        $followResponse->assertSee('Your account has been blocked by the admin. You cannot log in.');
        $followResponse->assertSee('member-auth-alert');
    }

    /**
     * TEST 11 — WEB FORM LOGIN FOR BLOCKED MEMBER WITH WRONG PASSWORD DOES NOT LEAK BLOCKED STATUS
     */
    public function test_web_form_login_for_blocked_member_with_wrong_password_does_not_leak_blocked_status(): void
    {
        Member::create([
            'name' => 'Web Blocked Member',
            'user_id' => 'WBBLCK5678',
            'email' => 'webblocked2@example.com',
            'password' => Hash::make('secret123'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $response = $this->from('/member/login')->post('/member/login', [
            'email' => 'webblocked2@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/member/login');
        $response->assertSessionMissing('error');
        $response->assertSessionHasErrors([
            'email' => 'The provided email or password is incorrect.',
        ]);
        $this->assertFalse(Auth::guard('member')->check());

        $followResponse = $this->get('/member/login');
        $followResponse->assertStatus(200);
        $followResponse->assertDontSee('Your account has been blocked by the admin. You cannot log in.');
    }

    /**
     * TEST 12 — BLOCKED MEMBER VISITING LOGIN PAGE IS LOGGED OUT AND SHOWN ERROR
     */
    public function test_blocked_authenticated_member_visiting_login_page_is_logged_out_with_error(): void
    {
        $member = Member::create([
            'name' => 'Blocked Member',
            'user_id' => 'WBBLCK9999',
            'email' => 'webblocked3@example.com',
            'password' => Hash::make('secret123'),
            'blocked_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($member, 'member')->get('/member/login');

        $response->assertRedirect(route('member.login'));
        $response->assertSessionHas('error', 'Your account has been blocked by the admin. You cannot log in.');
        $this->assertFalse(Auth::guard('member')->check());
    }
}

