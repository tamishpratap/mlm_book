<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AdminMaintenanceModeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Ensure maintenance mode is disabled after each test
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
        }

        parent::tearDown();
    }

    protected function createAdmin(array $attrs = []): Admin
    {
        $unique = uniqid('admin_');
        return Admin::create(array_merge([
            'name' => 'Test Admin ' . $unique,
            'email' => $unique . '@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ], $attrs));
    }

    protected function createMember(array $attrs = []): Member
    {
        $unique = uniqid('member_');
        return Member::create(array_merge([
            'name' => 'Test Member ' . $unique,
            'email' => $unique . '@example.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('secret123'),
            'mobile_verified_at' => now(),
        ], $attrs));
    }

    /**
     * TEST 1 — MAINTENANCE ON:
     * Normal/member-facing routes return 503, but Admin login paths remain accessible.
     */
    public function test_1_maintenance_on_restricts_regular_routes_but_keeps_admin_login_accessible(): void
    {
        Artisan::call('down', ['--secret' => 'admin-access']);
        $this->assertTrue(app()->isDownForMaintenance());

        // Regular/member routes receive 503
        $responseMember = $this->get('/member/socials');
        $responseMember->assertStatus(503);

        $responseMemberApi = $this->getJson('/api/member/socials');
        $responseMemberApi->assertStatus(503);

        // Admin login page is accessible (not 503)
        $responseAdminWeb = $this->get('/admin/login');
        $responseAdminWeb->assertStatus(200);

        // Admin branding API is accessible (not 503)
        $responseAdminBranding = $this->getJson('/api/admin/branding');
        $responseAdminBranding->assertStatus(200);
    }

    /**
     * TEST 2 — ADMIN LOGIN PAGE LOADS:
     * Admin login page loads successfully with no 503 or server error.
     */
    public function test_2_admin_login_page_loads_cleanly_during_maintenance(): void
    {
        Artisan::call('down', ['--secret' => 'admin-access']);

        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Admin Sign In');
    }

    /**
     * TEST 3 — INVALID ADMIN LOGIN:
     * Invalid credentials return standard 422 validation response, not 503.
     */
    public function test_3_invalid_admin_login_returns_validation_error_not_503(): void
    {
        Artisan::call('down', ['--secret' => 'admin-access']);

        $response = $this->postJson('/api/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * TEST 4 — VALID ADMIN LOGIN:
     * Valid admin credentials authenticate successfully and create session during maintenance.
     */
    public function test_4_valid_admin_login_succeeds_and_creates_admin_session(): void
    {
        $admin = $this->createAdmin([
            'email' => 'superadmin@example.com',
            'password' => 'secret123',
        ]);

        Artisan::call('down', ['--secret' => 'admin-access']);

        // JSON / API login
        $response = $this->postJson('/api/admin/login', [
            'email' => 'superadmin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged in successfully.',
        ]);
        $this->assertAuthenticatedAs($admin, 'admin');

        // Web login redirect check
        $this->flushSession();
        $responseWeb = $this->post('/admin/login', [
            'email' => 'superadmin@example.com',
            'password' => 'secret123',
        ]);
        $responseWeb->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * TEST 5 — MAINTENANCE SETTINGS LOAD:
     * Authenticated admin can inspect settings and see maintenance status is active.
     */
    public function test_5_authenticated_admin_can_load_maintenance_settings(): void
    {
        $admin = $this->createAdmin();

        Artisan::call('down', ['--secret' => 'admin-access']);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/settings');
        $response->assertStatus(200);
        $this->assertTrue($response->json('isMaintenance'));
        $this->assertEquals('Enabled', $response->json('systemInfo.maintenance_mode'));
    }

    /**
     * TEST 6 — DISABLE MAINTENANCE VIA ADMIN CONTROL:
     * Authenticated admin can toggle maintenance OFF and app returns to live.
     */
    public function test_6_authenticated_admin_can_disable_maintenance_mode(): void
    {
        $admin = $this->createAdmin();

        Artisan::call('down', ['--secret' => 'admin-access']);
        $this->assertTrue(app()->isDownForMaintenance());

        // Call toggle endpoint with action = up
        $response = $this->actingAs($admin, 'admin')->postJson('/api/admin/settings/maintenance', [
            'action' => 'up',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_maintenance' => false,
        ]);

        $this->assertFalse(app()->isDownForMaintenance());
        $this->assertFalse(file_exists(storage_path('framework/down')));
    }

    /**
     * TEST 7 — NORMAL WEBSITE RECOVERS:
     * Once maintenance is OFF, regular member routes function normally.
     */
    public function test_7_normal_website_behavior_restored_after_maintenance_off(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        // 1. Put down
        Artisan::call('down', ['--secret' => 'admin-access']);
        $this->assertTrue(app()->isDownForMaintenance());
        $this->get('/member/socials')->assertStatus(503);

        // 2. Admin turns it OFF
        $this->actingAs($admin, 'admin')->postJson('/api/admin/settings/maintenance', [
            'action' => 'up',
        ])->assertStatus(200);

        $this->assertFalse(app()->isDownForMaintenance());

        // 3. Member routes are back live (200)
        $response = $this->actingAs($member, 'member')->get('/member/socials');
        $response->assertStatus(200);

        $responseApi = $this->actingAs($member, 'member')->getJson('/api/member/socials');
        $responseApi->assertStatus(200);
    }

    /**
     * TEST 8 — SECURITY AUDIT:
     * While Maintenance is ON:
     * - Anonymous cannot view dashboard (redirected or 401).
     * - Normal member cannot view admin dashboard (redirected or 401).
     * - Authenticated Admin can view dashboard.
     */
    public function test_8_security_access_control_during_maintenance(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        Artisan::call('down', ['--secret' => 'admin-access']);

        // 1. Anonymous user accessing admin dashboard:
        // Web redirects to login
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        // API returns 401 Unauthenticated
        $this->getJson('/api/admin/dashboard')->assertStatus(401);

        // 2. Normal member accessing admin dashboard:
        $this->actingAs($member, 'member')->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->actingAs($member, 'member')->getJson('/api/admin/dashboard')->assertStatus(401);

        // 3. Authenticated Admin accessing admin dashboard:
        $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($admin, 'admin')->getJson('/api/admin/dashboard')->assertStatus(200);
    }

    /**
     * TEST 9 — REFRESH / IDEMPOTENT STATE:
     * Calling settings after turning maintenance OFF reports Live and remains OFF.
     */
    public function test_9_maintenance_off_persists_across_subsequent_requests(): void
    {
        $admin = $this->createAdmin();

        Artisan::call('down', ['--secret' => 'admin-access']);
        $this->actingAs($admin, 'admin')->postJson('/api/admin/settings/maintenance', ['action' => 'up']);

        // Refresh settings
        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/settings');
        $response->assertStatus(200);
        $this->assertFalse($response->json('isMaintenance'));
        $this->assertEquals('Disabled', $response->json('systemInfo.maintenance_mode'));
    }

    /**
     * TEST 10 — PERSISTENCE:
     * Maintenance state is properly represented by the filesystem lock and framework status.
     */
    public function test_10_maintenance_mode_lifecycle_persistence(): void
    {
        $admin = $this->createAdmin();

        // Enable
        $this->actingAs($admin, 'admin')->postJson('/api/admin/settings/maintenance', ['action' => 'down']);
        $this->assertTrue(app()->isDownForMaintenance());
        $this->assertTrue(file_exists(storage_path('framework/down')));

        // Disable
        $this->actingAs($admin, 'admin')->postJson('/api/admin/settings/maintenance', ['action' => 'up']);
        $this->assertFalse(app()->isDownForMaintenance());
        $this->assertFalse(file_exists(storage_path('framework/down')));
    }
}
