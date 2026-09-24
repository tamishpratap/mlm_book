<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);
    }

    public function test_admin_login_page_is_accessible(): void
    {
        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
        $response->assertSee('Admin Sign In');
    }

    /**
     * TEST 1: Correct email, Correct password -> Login success, Admin session created, redirect to dashboard
     */
    public function test_test_1_admin_login_success_with_correct_credentials(): void
    {
        $email = 'admin_login_success_' . uniqid() . '@gmail.com';
        $admin = Admin::create([
            'name' => 'System Admin',
            'email' => $email,
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => $email,
            'password' => '123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        $admin->delete();
    }

    /**
     * TEST 2: Correct email, Wrong password -> "Invalid email or password."
     */
    public function test_test_2_admin_login_fails_with_wrong_password(): void
    {
        $email = 'admin_wrong_pw_' . uniqid() . '@gmail.com';
        $admin = Admin::create([
            'name' => 'System Admin',
            'email' => $email,
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => $email,
            'password' => 'wrong-password-xyz',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors(['email' => 'Invalid email or password.']);
        $this->assertGuest('admin');

        $admin->delete();
    }

    /**
     * TEST 3: Non-existing email, Any password -> "Invalid email or password."
     */
    public function test_test_3_admin_login_fails_with_non_existing_email(): void
    {
        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => 'nonexistent_' . uniqid() . '@example.com',
            'password' => 'any-password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors(['email' => 'Invalid email or password.']);
        $this->assertGuest('admin');
    }

    /**
     * TEST 4: Existing Admin with any status value (e.g. inactive, blocked, suspended) -> LOGIN SUCCESS
     */
    public function test_test_4_admin_login_succeeds_regardless_of_status(): void
    {
        $emailInactive = 'inactive_' . uniqid() . '@gmail.com';
        $inactiveAdmin = Admin::create([
            'name' => 'Inactive Admin',
            'email' => $emailInactive,
            'password' => '123456',
            'status' => 'inactive',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => $emailInactive,
            'password' => '123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($inactiveAdmin, 'admin');

        $emailBlocked = 'blocked_' . uniqid() . '@gmail.com';
        $blockedAdmin = Admin::create([
            'name' => 'Blocked Admin',
            'email' => $emailBlocked,
            'password' => '123456',
            'status' => 'blocked',
        ]);

        $response2 = $this->post(route('admin.login.submit'), [
            'email' => $emailBlocked,
            'password' => '123456',
        ]);

        $response2->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($blockedAdmin, 'admin');

        $inactiveAdmin->delete();
        $blockedAdmin->delete();
    }

    /**
     * TEST 5: Existing Admin with last_login fields missing/empty/null -> Login still succeeds
     */
    public function test_test_5_admin_login_succeeds_with_null_last_login_fields(): void
    {
        $emailClean = 'clean_' . uniqid() . '@gmail.com';
        $admin = Admin::create([
            'name' => 'Clean Admin',
            'email' => $emailClean,
            'password' => '123456',
            'status' => 'active',
            'last_login_at' => null,
            'last_login_ip' => null,
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => $emailClean,
            'password' => '123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        $admin->delete();
    }

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        $email = 'auth_admin_' . uniqid() . '@example.com';
        $admin = Admin::create([
            'name' => 'Active Admin',
            'email' => $email,
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Admin Overview');

        $admin->delete();
    }

    public function test_unauthenticated_user_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_logout_successfully(): void
    {
        $email = 'logout_admin_' . uniqid() . '@example.com';
        $admin = Admin::create([
            'name' => 'Active Admin',
            'email' => $email,
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.logout'));
        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');

        $admin->delete();
    }
}
