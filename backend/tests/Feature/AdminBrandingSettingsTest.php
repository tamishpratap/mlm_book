<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }
        if (class_exists(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        }

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
    }

    /**
     * TEST 1: Public Admin & Member Branding Endpoints return defaults and resolved URLs.
     */
    public function test_public_branding_endpoints_return_identical_canonical_branding(): void
    {
        $adminResponse = $this->getJson('/api/admin/branding');
        $memberResponse = $this->getJson('/api/member/branding');
        $globalResponse = $this->getJson('/api/branding');

        $adminResponse->assertStatus(200);
        $memberResponse->assertStatus(200);
        $globalResponse->assertStatus(200);

        $expectedStructure = [
            'success',
            'branding' => [
                'site_name',
                'site_description',
                'site_logo',
                'site_logo_url',
                'site_dark_logo',
                'site_dark_logo_url',
                'site_favicon',
                'site_favicon_url',
            ],
        ];

        $adminResponse->assertJsonStructure($expectedStructure);
        $memberResponse->assertJsonStructure($expectedStructure);
        $globalResponse->assertJsonStructure($expectedStructure);

        $adminData = $adminResponse->json('branding');
        $memberData = $memberResponse->json('branding');
        $globalData = $globalResponse->json('branding');

        $this->assertEquals($adminData, $memberData, 'Member and Admin branding endpoints must return identical canonical branding.');
        $this->assertEquals($adminData, $globalData, 'Global and Admin branding endpoints must return identical canonical branding.');
    }

    /**
     * TEST 2: Admin settings endpoint returns all settings merged with branding URLs.
     */
    public function test_admin_settings_index_returns_settings_and_branding(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'settings' => [
                'site_name',
                'site_logo',
                'site_logo_url',
                'site_dark_logo',
                'site_dark_logo_url',
                'site_favicon',
                'site_favicon_url',
            ],
            'branding',
            'systemInfo',
        ]);
    }

    /**
     * TEST 3: Admin can upload site_logo with a valid image file.
     */
    public function test_admin_can_upload_site_logo_and_persists_to_disk_and_db(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('custom_logo.png', 400, 100);

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $savedPath = Setting::get('site_logo');
        $this->assertNotNull($savedPath);
        $this->assertStringStartsWith('storage/branding/', $savedPath);

        $relativeStoragePath = str_replace('storage/', '', $savedPath);
        Storage::disk('public')->assertExists($relativeStoragePath);

        // Verify JSON response has updated URL
        $data = $response->json();
        $this->assertStringContainsString('/storage/branding/', $data['branding']['site_logo_url']);
    }

    /**
     * TEST 4: Uploading a new logo deletes the old custom file from disk.
     */
    public function test_uploading_new_logo_deletes_old_custom_file_on_disk(): void
    {
        Storage::fake('public');

        // First upload
        $file1 = UploadedFile::fake()->image('logo1.png', 200, 60);
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $file1,
        ]);

        $path1 = Setting::get('site_logo');
        $relative1 = str_replace('storage/', '', $path1);
        Storage::disk('public')->assertExists($relative1);

        // Second upload
        $file2 = UploadedFile::fake()->image('logo2.png', 300, 80);
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $file2,
        ]);

        $path2 = Setting::get('site_logo');
        $relative2 = str_replace('storage/', '', $path2);

        // Old file deleted, new file exists
        Storage::disk('public')->assertMissing($relative1);
        Storage::disk('public')->assertExists($relative2);
        $this->assertNotEquals($path1, $path2);
    }

    /**
     * TEST 5: Partial upload (only favicon) preserves existing logo.
     */
    public function test_partial_upload_preserves_existing_assets(): void
    {
        Storage::fake('public');

        // Set initial logo
        $logoFile = UploadedFile::fake()->image('existing_logo.png', 300, 80);
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $logoFile,
        ]);
        $savedLogo = Setting::get('site_logo');

        // Upload only favicon
        $faviconFile = UploadedFile::fake()->create('custom_favicon.ico', 10, 'image/x-icon');
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_favicon' => $faviconFile,
        ]);

        $response->assertStatus(200);

        // Verify favicon was saved
        $savedFavicon = Setting::get('site_favicon');
        $this->assertStringStartsWith('storage/branding/', $savedFavicon);

        // Verify existing logo was preserved!
        $this->assertEquals($savedLogo, Setting::get('site_logo'));
    }

    /**
     * TEST 6: Reject invalid file type (e.g. txt file for logo).
     */
    public function test_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('public');

        $invalidFile = UploadedFile::fake()->create('malicious.txt', 20, 'text/plain');

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $invalidFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['site_logo']);
    }

    /**
     * TEST 7: Reject oversized file (> 5MB).
     */
    public function test_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');

        // 6MB image (max is 5120KB)
        $oversizedFile = UploadedFile::fake()->create('huge.png', 6144, 'image/png');

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $oversizedFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['site_logo']);
    }

    /**
     * TEST 8: Admin can upload all 3 branding assets simultaneously.
     */
    public function test_admin_can_upload_all_three_branding_assets_simultaneously(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('main_logo.png', 400, 100);
        $darkLogo = UploadedFile::fake()->image('dark_logo.png', 400, 100);
        $favicon = UploadedFile::fake()->image('favicon.png', 32, 32);

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $logo,
            'site_dark_logo' => $darkLogo,
            'site_favicon' => $favicon,
        ]);

        $response->assertStatus(200);

        $savedLogo = Setting::get('site_logo');
        $savedDarkLogo = Setting::get('site_dark_logo');
        $savedFavicon = Setting::get('site_favicon');

        $this->assertStringStartsWith('storage/branding/', $savedLogo);
        $this->assertStringStartsWith('storage/branding/', $savedDarkLogo);
        $this->assertStringStartsWith('storage/branding/', $savedFavicon);

        Storage::disk('public')->assertExists(str_replace('storage/', '', $savedLogo));
        Storage::disk('public')->assertExists(str_replace('storage/', '', $savedDarkLogo));
        Storage::disk('public')->assertExists(str_replace('storage/', '', $savedFavicon));

        $data = $response->json();
        $this->assertStringContainsString('/storage/branding/', $data['branding']['site_logo_url']);
        $this->assertStringContainsString('/storage/branding/', $data['branding']['site_dark_logo_url']);
        $this->assertStringContainsString('/storage/branding/', $data['branding']['site_favicon_url']);
    }

    /**
     * TEST 9: Member branding endpoint immediately reflects updates made by Admin.
     */
    public function test_member_branding_endpoint_reflects_admin_logo_updates_immediately(): void
    {
        Storage::fake('public');

        $initialMemberResponse = $this->getJson('/api/member/branding');
        $initialMemberResponse->assertStatus(200);

        $newLogo = UploadedFile::fake()->image('member_sync_logo.png', 500, 150);
        $updateResponse = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'branding',
            'site_logo' => $newLogo,
        ]);
        $updateResponse->assertStatus(200);

        $updatedMemberResponse = $this->getJson('/api/member/branding');
        $updatedMemberResponse->assertStatus(200);

        $updatedMemberLogoUrl = $updatedMemberResponse->json('branding.site_logo_url');
        $this->assertStringContainsString('/storage/branding/', $updatedMemberLogoUrl);
        $this->assertEquals(
            $updateResponse->json('branding.site_logo_url'),
            $updatedMemberLogoUrl,
            'Member branding API must immediately reflect the new logo uploaded by Admin.'
        );
    }
}

