<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Role;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoryExportTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $member1;
    protected Member $member2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin->roles()->sync([$superRole->id]);

        $this->member1 = Member::create([
            'name' => 'Alice Storyteller',
            'user_id' => 'alice_123',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->member2 = Member::create([
            'name' => 'Bob Builder',
            'user_id' => 'bob_456',
            'email' => 'bob@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/admin/stories/export');
        $response->assertStatus(401);
    }

    public function test_basic_export_returns_streamed_csv_with_correct_headers(): void
    {
        Story::create([
            'member_id' => $this->member1->id,
            'caption' => 'Morning Motivation',
            'media_type' => 'image',
            'media_path' => 'uploads/stories/test1.jpg',
            'expires_at' => now()->addHours(12),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('stories_export_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        // Verify UTF-8 BOM
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $this->assertStringStartsWith($bom, $content);

        // Verify CSV header row
        $cleanContent = substr($content, 3);
        $lines = explode("\n", trim($cleanContent));
        $this->assertNotEmpty($lines);

        $headerRow = str_getcsv($lines[0]);
        $expectedHeaders = [
            'Story ID',
            'Author Name',
            'Author User ID',
            'Caption',
            'Media Type',
            'Status',
            'Views',
            'Reactions',
            'Replies',
            'Created Date',
            'Expires Date',
        ];
        $this->assertEquals($expectedHeaders, $headerRow);

        // Verify data row
        $this->assertCount(2, $lines);
        $dataRow = str_getcsv($lines[1]);
        $this->assertEquals('Alice Storyteller', $dataRow[1]);
        $this->assertEquals('alice_123', $dataRow[2]);
        $this->assertEquals('Morning Motivation', $dataRow[3]);
        $this->assertEquals('image', $dataRow[4]);
        $this->assertEquals('Active', $dataRow[5]);
    }

    public function test_export_handles_long_captions_commas_and_newlines_safely(): void
    {
        $complexCaption = "Line 1: Special offer! \"50% OFF\", don't miss out.\nLine 2: Limited time only, call now & succeed!";

        $story = Story::create([
            'member_id' => $this->member2->id,
            'caption' => $complexCaption,
            'media_type' => 'video',
            'media_path' => 'uploads/stories/video1.mp4',
            'expires_at' => now()->addHours(6),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        // Strip BOM and parse with str_getcsv or temp stream
        $cleanContent = substr($content, 3);
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $cleanContent);
        rewind($fp);

        $headers = fgetcsv($fp);
        $row = fgetcsv($fp);
        fclose($fp);

        $this->assertNotNull($row);
        $this->assertEquals($story->id, $row[0]);
        $this->assertEquals('Bob Builder', $row[1]);
        $this->assertEquals('bob_456', $row[2]);
        $this->assertEquals($complexCaption, $row[3]);
        $this->assertEquals('video', $row[4]);
        $this->assertEquals('Active', $row[5]);
    }

    public function test_export_filters_by_search_query(): void
    {
        Story::create([
            'member_id' => $this->member1->id,
            'caption' => 'Crypto Trading Secrets',
            'media_type' => 'image',
            'media_path' => 'stories/crypto.jpg',
            'expires_at' => now()->addHours(10),
        ]);

        Story::create([
            'member_id' => $this->member2->id,
            'caption' => 'Real Estate Mastery',
            'media_type' => 'image',
            'media_path' => 'stories/realestate.jpg',
            'expires_at' => now()->addHours(10),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export?q=Crypto');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Crypto Trading Secrets', $content);
        $this->assertStringNotContainsString('Real Estate Mastery', $content);
    }

    public function test_export_filters_by_status_active_and_expired(): void
    {
        // Active story
        Story::create([
            'member_id' => $this->member1->id,
            'caption' => 'Active Story Alpha',
            'media_type' => 'image',
            'media_path' => 'stories/a.jpg',
            'expires_at' => now()->addHours(5),
        ]);

        // Expired story
        Story::create([
            'member_id' => $this->member2->id,
            'caption' => 'Expired Story Beta',
            'media_type' => 'image',
            'media_path' => 'stories/b.jpg',
            'expires_at' => now()->subHours(2),
        ]);

        // 1. Export active
        $activeResponse = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export?status=active');
        $activeResponse->assertStatus(200);
        $activeContent = $activeResponse->streamedContent();

        $this->assertStringContainsString('Active Story Alpha', $activeContent);
        $this->assertStringNotContainsString('Expired Story Beta', $activeContent);

        // 2. Export expired
        $expiredResponse = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export?status=expired');
        $expiredResponse->assertStatus(200);
        $expiredContent = $expiredResponse->streamedContent();

        $this->assertStringNotContainsString('Active Story Alpha', $expiredContent);
        $this->assertStringContainsString('Expired Story Beta', $expiredContent);
    }

    public function test_export_filters_by_media_type(): void
    {
        Story::create([
            'member_id' => $this->member1->id,
            'caption' => 'Only Image Story',
            'media_type' => 'image',
            'media_path' => 'stories/img.jpg',
            'expires_at' => now()->addHours(5),
        ]);

        Story::create([
            'member_id' => $this->member2->id,
            'caption' => 'Only Video Story',
            'media_type' => 'video',
            'media_path' => 'stories/vid.mp4',
            'expires_at' => now()->addHours(5),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export?media_type=video');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Only Video Story', $content);
        $this->assertStringNotContainsString('Only Image Story', $content);
    }

    public function test_empty_result_returns_valid_csv_with_headers_only(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/stories/export?q=nonexistent_query_xyz');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $cleanContent = substr($content, 3); // Strip BOM
        $lines = array_filter(explode("\n", trim($cleanContent)));
        $this->assertCount(1, $lines); // Only header row

        $headers = str_getcsv($lines[0]);
        $this->assertEquals('Story ID', $headers[0]);
        $this->assertEquals('Caption', $headers[3]);
    }
}
