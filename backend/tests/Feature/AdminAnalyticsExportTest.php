<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Post;
use App\Models\Community;
use App\Models\BusinessPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsExportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $email = 'admin_export@example.com'): Admin
    {
        return Admin::create([
            'name' => 'Admin Exporter',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_export_general_analytics_csv(): void
    {
        $admin = $this->createAdmin();

        Member::create([
            'name' => 'Member One',
            'email' => 'm1@example.com',
            'user_id' => 'USR001',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/api/admin/analytics/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="analytics-export-', $response->headers->get('Content-Disposition'));
        $response->assertHeader('Access-Control-Expose-Headers', 'Content-Disposition');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Metric Category', $content);
        $this->assertStringContainsString('Metric Name', $content);
        $this->assertStringContainsString('Total Registered Members', $content);
        $this->assertStringContainsString('Total Posts Published', $content);
    }

    public function test_admin_can_export_bi_metrics_csv(): void
    {
        $admin = $this->createAdmin();

        Member::create([
            'name' => 'Top Creator Member',
            'email' => 'creator@example.com',
            'user_id' => 'USR002',
            'gender' => 'female',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/api/admin/analytics/export?type=bi');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="analytics-bi-metrics-', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('SECTION 1: EXECUTIVE KPI SUMMARY', $content);
        $this->assertStringContainsString('SECTION 2: MEMBER DEMOGRAPHICS', $content);
        $this->assertStringContainsString('SECTION 3: TOP CONTENT CREATORS', $content);
        $this->assertStringContainsString('Female', $content);
    }

    public function test_admin_export_with_date_range(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get('/api/admin/analytics/export?date_from=2026-09-01&date_to=2026-09-08');

        $response->assertStatus(200);
        $this->assertStringContainsString('analytics-export-2026-09-01-to-2026-09-08.csv', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('2026-09-01 to 2026-09-08', $content);
    }

    public function test_admin_export_all_dates(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get('/api/admin/analytics/export?period=all');

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('All Dates', $content);
    }

    public function test_admin_export_invalid_date_range_fails_validation(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/analytics/export?date_from=2026-09-10&date_to=2026-09-01');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }

    public function test_empty_data_in_date_range_returns_valid_csv(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get('/api/admin/analytics/export?date_from=2010-01-01&date_to=2010-01-02');

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Metric Category', $content);
        $this->assertStringContainsString('2010-01-01 to 2010-01-02', $content);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/admin/analytics/export');
        $response->assertStatus(401);
    }
}
