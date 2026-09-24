<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminMemberCoverBannerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);

        $this->admin = Admin::first() ?? Admin::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test upload files created in uploads/cover
        $coverDir = public_path('uploads/cover');
        if (File::exists($coverDir)) {
            $testFiles = File::glob($coverDir . '/*test*');
            foreach ($testFiles as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
        parent::tearDown();
    }

    private function createTestMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'user_id' => 'testmember_' . uniqid(),
            'email' => 'member_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'mobile_verified_at' => now(),
            'profile_photo' => null,
            'cover_photo' => null,
        ], $overrides));
    }

    public function test_01_member_model_serializes_cover_photo_and_banner_urls(): void
    {
        $member = $this->createTestMember([
            'cover_photo' => 'uploads/cover/test_cover.jpg',
        ]);

        $array = $member->toArray();

        $this->assertArrayHasKey('cover_photo_url', $array);
        $this->assertArrayHasKey('cover_banner_url', $array);
        $this->assertNotNull($array['cover_photo_url']);
        $this->assertNotNull($array['cover_banner_url']);
        $this->assertStringContainsString('uploads/cover/test_cover.jpg', $array['cover_photo_url']);
        $this->assertSame($array['cover_photo_url'], $array['cover_banner_url']);
    }

    public function test_02_member_without_cover_photo_returns_null_urls(): void
    {
        $member = $this->createTestMember([
            'cover_photo' => null,
        ]);

        $array = $member->toArray();

        $this->assertArrayHasKey('cover_photo_url', $array);
        $this->assertArrayHasKey('cover_banner_url', $array);
        $this->assertNull($array['cover_photo_url']);
        $this->assertNull($array['cover_banner_url']);
    }

    public function test_03_admin_member_show_api_returns_cover_photo_url(): void
    {
        $member = $this->createTestMember([
            'cover_photo' => 'uploads/cover/sample_banner.png',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/members/{$member->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'member' => [
                'id',
                'name',
                'cover_photo',
                'cover_photo_url',
                'cover_banner_url',
                'avatar_url',
                'profile_photo_url',
            ],
        ]);

        $json = $response->json('member');
        $this->assertNotNull($json['cover_photo_url']);
        $this->assertNotNull($json['cover_banner_url']);
        $this->assertStringContainsString('sample_banner.png', $json['cover_photo_url']);
    }

    public function test_04_admin_member_edit_api_returns_cover_photo_url(): void
    {
        $member = $this->createTestMember([
            'cover_photo' => 'uploads/cover/edit_banner.png',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/members/{$member->id}/edit");

        $response->assertStatus(200);
        $json = $response->json('member');
        $this->assertNotNull($json['cover_photo_url']);
        $this->assertNotNull($json['cover_banner_url']);
        $this->assertStringContainsString('edit_banner.png', $json['cover_photo_url']);
    }

    public function test_05_admin_can_upload_and_update_member_cover_photo(): void
    {
        $member = $this->createTestMember();

        $fakeImage = UploadedFile::fake()->image('test_new_cover.jpg', 1200, 400);

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/members/{$member->id}", [
                'name' => $member->name,
                'user_id' => $member->user_id,
                'email' => $member->email,
                'cover_photo' => $fakeImage,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $updatedMember = $member->fresh();
        $this->assertNotNull($updatedMember->cover_photo);
        $this->assertStringStartsWith('uploads/cover/', $updatedMember->cover_photo);
        $this->assertFileExists(public_path($updatedMember->cover_photo));

        // Response contains updated cover URLs
        $json = $response->json('member');
        $this->assertNotNull($json['cover_photo_url']);
        $this->assertNotNull($json['cover_banner_url']);
        $this->assertSame($updatedMember->cover_photo_url, $json['cover_photo_url']);

        // Clean up test file
        if (File::exists(public_path($updatedMember->cover_photo))) {
            @unlink(public_path($updatedMember->cover_photo));
        }
    }

    public function test_06_admin_can_remove_member_cover_photo(): void
    {
        // First create a real dummy file in uploads/cover
        $coverDir = public_path('uploads/cover');
        if (!File::exists($coverDir)) {
            File::makeDirectory($coverDir, 0755, true);
        }
        $filename = 'cover_test_removal_' . time() . '.png';
        file_put_contents($coverDir . '/' . $filename, 'fake image content');

        $member = $this->createTestMember([
            'cover_photo' => 'uploads/cover/' . $filename,
        ]);

        $this->assertFileExists($coverDir . '/' . $filename);

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/members/{$member->id}", [
                'name' => $member->name,
                'user_id' => $member->user_id,
                'email' => $member->email,
                'remove_cover_photo' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $updatedMember = $member->fresh();
        $this->assertNull($updatedMember->cover_photo);
        $this->assertNull($updatedMember->cover_photo_url);
        $this->assertNull($updatedMember->cover_banner_url);

        // Verify the file was deleted from disk
        $this->assertFileDoesNotExist($coverDir . '/' . $filename);
    }
}
