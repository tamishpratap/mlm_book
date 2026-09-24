<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemSettingsSaveTest extends TestCase
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
     * TEST 1: Admin can update General Settings (site_name, timezone, etc.)
     */
    public function test_admin_can_update_general_settings(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'general',
            'site_name' => 'MLM Book Platform',
            'site_description' => 'Official Enterprise Social Network',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd/m/Y',
            'currency' => 'INR (₹)',
            'pagination_size' => '25',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals('MLM Book Platform', Setting::get('site_name'));
        $this->assertEquals('Official Enterprise Social Network', Setting::get('site_description'));
        $this->assertEquals('Asia/Kolkata', Setting::get('timezone'));
        $this->assertEquals('d/m/Y', Setting::get('date_format'));
        $this->assertEquals('INR (₹)', Setting::get('currency'));
        $this->assertEquals('25', Setting::get('pagination_size'));

        // Verify GET /api/admin/settings returns the persisted values
        $getResponse = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/settings');
        $getResponse->assertStatus(200);
        $this->assertEquals('MLM Book Platform', $getResponse->json('settings.site_name'));
        $this->assertEquals('Asia/Kolkata', $getResponse->json('settings.timezone'));
    }

    /**
     * TEST 2: Admin can update Contact Settings (company_name, support_email, phone, etc.)
     */
    public function test_admin_can_update_contact_settings(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'contact',
            'company_name' => 'Enterprise Global Ltd',
            'support_email' => 'care@enterpriseglobal.com',
            'phone' => '+1 (555) 987-6543',
            'website' => 'https://enterpriseglobal.com',
            'address' => '456 Innovation Blvd, Silicon Park',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('Enterprise Global Ltd', Setting::get('company_name'));
        $this->assertEquals('care@enterpriseglobal.com', Setting::get('support_email'));
        $this->assertEquals('+1 (555) 987-6543', Setting::get('phone'));
        $this->assertEquals('https://enterpriseglobal.com', Setting::get('website'));
        $this->assertEquals('456 Innovation Blvd, Silicon Park', Setting::get('address'));
    }

    /**
     * TEST 3: Admin can update SEO & Social Settings
     */
    public function test_admin_can_update_seo_and_social_settings(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'seo',
            'meta_title' => 'MLM Book SEO Title',
            'meta_description' => 'MLM Book Meta Description Here',
            'meta_keywords' => 'mlm, network, business',
            'social_facebook' => 'https://facebook.com/custommlm',
            'social_twitter' => 'https://twitter.com/custommlm',
            'social_instagram' => 'https://instagram.com/custommlm',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('MLM Book SEO Title', Setting::get('meta_title'));
        $this->assertEquals('MLM Book Meta Description Here', Setting::get('meta_description'));
        $this->assertEquals('https://facebook.com/custommlm', Setting::get('social_facebook'));
    }

    /**
     * TEST 4: Partial update does not overwrite or erase unrelated settings
     */
    public function test_partial_update_preserves_unrelated_settings(): void
    {
        Setting::set('site_name', 'Initial Site Name');
        Setting::set('timezone', 'Europe/London');
        Setting::set('company_name', 'Initial Company');

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'general',
            'site_name' => 'Brand New Site Name',
        ]);

        $response->assertStatus(200);

        $this->assertEquals('Brand New Site Name', Setting::get('site_name'));
        $this->assertEquals('Europe/London', Setting::get('timezone'));
        $this->assertEquals('Initial Company', Setting::get('company_name'));
    }

    /**
     * TEST 5: Validation rejects invalid email format for support_email
     */
    public function test_validation_rejects_invalid_support_email(): void
    {
        $initialEmail = Setting::get('support_email');

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/settings', [
            'group' => 'contact',
            'support_email' => 'invalid-email-address',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['support_email']);

        // Assert database value remained intact
        $this->assertEquals($initialEmail, Setting::get('support_email'));
    }

    /**
     * TEST 6: Unauthenticated request is rejected with 401
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/admin/settings', [
            'site_name' => 'Hacker Name',
        ]);

        $response->assertStatus(401);
    }
}
