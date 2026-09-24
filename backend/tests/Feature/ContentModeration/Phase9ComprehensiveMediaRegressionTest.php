<?php

namespace Tests\Feature\ContentModeration;

use App\Models\Category;
use App\Models\Member;
use App\Models\Post;
use App\Models\Product;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase9ComprehensiveMediaRegressionTest extends TestCase
{
    use RefreshDatabase;

    private array $createdFiles = [];
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        File::ensureDirectoryExists(storage_path('framework/testing'));
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            $full = public_path($path);
            if (File::exists($full)) {
                @File::delete($full);
            }
        }

        foreach ($this->tempFiles as $tempPath) {
            if (File::exists($tempPath)) {
                @File::delete($tempPath);
            }
        }

        // Clean any leftover test quarantine video folders
        $testQuarantine = storage_path('app/quarantine');
        if (File::isDirectory($testQuarantine)) {
            foreach (File::allFiles($testQuarantine) as $file) {
                @File::delete($file->getRealPath());
            }
        }

        parent::tearDown();
    }

    private function createTestMember(): Member
    {
        return Member::create([
            'name' => 'Regression Tester',
            'user_id' => 'reg_' . uniqid(),
            'email' => 'regtester_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    private function createSampleImage(int $width = 300, int $height = 300, string $ext = 'jpg'): UploadedFile
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $tempPath = $dir . '/' . uniqid('p9_img_') . '.' . $ext;
        $im = imagecreatetruecolor($width, $height);
        $col = imagecolorallocate($im, 70, 130, 180);
        imagefill($im, 0, 0, $col);
        imagejpeg($im, $tempPath, 90);
        imagedestroy($im);

        $this->tempFiles[] = $tempPath;

        return new UploadedFile($tempPath, 'sample_' . uniqid() . '.' . $ext, 'image/jpeg', null, true);
    }

    private function mockSafeModeration(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Neutral', 'probability' => 0.95],
                        ['className' => 'Drawing', 'probability' => 0.04],
                        ['className' => 'Sexy', 'probability' => 0.005],
                        ['className' => 'Hentai', 'probability' => 0.003],
                        ['className' => 'Porn', 'probability' => 0.002],
                    ],
                    'dominantClass' => 'Neutral',
                    'confidence' => 0.95,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 45,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);
    }

    private function mockBlockedModeration(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Porn', 'probability' => 0.94],
                        ['className' => 'Neutral', 'probability' => 0.03],
                        ['className' => 'Sexy', 'probability' => 0.02],
                        ['className' => 'Hentai', 'probability' => 0.01],
                        ['className' => 'Drawing', 'probability' => 0.0],
                    ],
                    'dominantClass' => 'Porn',
                    'confidence' => 0.94,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 50,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);
    }

    // =========================================================================
    // 1. DIRECT API BYPASS REGRESSION ACROSS PRIMARY SURFACES
    // =========================================================================

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_post_image(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $image = $this->createSampleImage();

        $response = $this->postJson('/api/member/posts', [
            'content' => 'Bypass attempt with forged flags',
            'media' => $image,
            'is_safe' => true,
            'moderation_passed' => true,
            'moderation_status' => 'approved',
            'confidence' => 0.999,
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Post::count(), 'Zero posts must be created on blocked upload');
    }

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_story_image(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $image = $this->createSampleImage();

        $response = $this->postJson('/api/member/stories', [
            'caption' => 'Bypass attempt on story',
            'media' => $image,
            'is_safe' => true,
            'moderation_status' => 'approved',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Story::count(), 'Zero stories must be created on blocked upload');
    }

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_profile_photo(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $originalPhoto = $member->profile_photo;
        $this->actingAs($member, 'member');

        $image = $this->createSampleImage();

        $response = $this->postJson('/api/member/profile/photo', [
            'profile_photo' => $image,
            'is_safe' => true,
            'approved' => true,
        ]);

        $response->assertStatus(422);
        $member->refresh();
        $this->assertSame($originalPhoto, $member->profile_photo, 'Profile photo must remain unmodified');
    }

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_profile_cover(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $originalCover = $member->cover_photo;
        $this->actingAs($member, 'member');

        $image = $this->createSampleImage();

        $response = $this->postJson('/api/member/profile/cover', [
            'cover_photo' => $image,
            'is_safe' => true,
            'approved' => true,
        ]);

        $response->assertStatus(422);
        $member->refresh();
        $this->assertSame($originalCover, $member->cover_photo, 'Profile cover must remain unmodified');
    }

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_marketplace_product(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics-' . uniqid(),
            'type' => 'marketplace',
            'is_active' => true,
        ]);

        $image = $this->createSampleImage();

        $response = $this->postJson('/api/member/marketplace', [
            'title' => 'Test Laptop',
            'description' => 'A great laptop for developers',
            'price' => 999.99,
            'category_id' => $category->id,
            'media' => [$image],
            'is_safe' => true,
            'approved' => true,
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Product::count(), 'Zero marketplace products created on blocked media');
    }

    // =========================================================================
    // 2. MIME SPOOFING & MALFORMED MEDIA TESTS
    // =========================================================================

    public function test_mime_spoofing_php_script_disguised_as_jpg_is_rejected(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $tempPath = $dir . '/spoofed_' . uniqid() . '.jpg';
        File::put($tempPath, '<?php echo "malicious_payload"; ?>');
        $this->tempFiles[] = $tempPath;

        $spoofedFile = new UploadedFile($tempPath, 'avatar.jpg', 'image/jpeg', null, true);

        $response = $this->postJson('/api/member/profile/photo', [
            'profile_photo' => $spoofedFile,
        ]);

        $response->assertStatus(422);
    }

    public function test_mime_spoofing_text_disguised_as_mp4_is_rejected(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $tempPath = $dir . '/spoofed_' . uniqid() . '.mp4';
        File::put($tempPath, 'This is a text file renamed to mp4 format with plain text data.');
        $this->tempFiles[] = $tempPath;

        $spoofedVideo = new UploadedFile($tempPath, 'fake_video.mp4', 'video/mp4', null, true);

        $response = $this->postJson('/api/member/posts', [
            'content' => 'Trying to upload text as mp4',
            'media' => $spoofedVideo,
            'media_type' => 'video',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Post::count());
    }

    public function test_malformed_zero_byte_image_is_rejected(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $tempPath = $dir . '/empty_' . uniqid() . '.jpg';
        File::put($tempPath, '');
        $this->tempFiles[] = $tempPath;

        $emptyFile = new UploadedFile($tempPath, 'empty.jpg', 'image/jpeg', null, true);

        $response = $this->postJson('/api/member/profile/photo', [
            'profile_photo' => $emptyFile,
        ]);

        $response->assertStatus(422);
    }

    public function test_malformed_truncated_image_is_rejected(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $tempPath = $dir . '/truncated_' . uniqid() . '.jpg';
        File::put($tempPath, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01"); // 12-byte truncated header
        $this->tempFiles[] = $tempPath;

        $truncatedFile = new UploadedFile($tempPath, 'truncated.jpg', 'image/jpeg', null, true);

        $response = $this->postJson('/api/member/profile/photo', [
            'profile_photo' => $truncatedFile,
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // 3. CONCURRENT & MIXED WORKLOAD ISOLATION
    // =========================================================================

    public function test_concurrent_uploads_simulation_with_mixed_safe_and_unsafe_payloads(): void
    {
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $currentIsSafe = true;

        Http::fake([
            '*/v1/moderate/image' => function ($request) use (&$currentIsSafe) {
                $sha = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                if ($currentIsSafe) {
                    return Http::response([
                        'success' => true,
                        'media_type' => 'image',
                        'predictions' => [
                            ['className' => 'Neutral', 'probability' => 0.95],
                            ['className' => 'Drawing', 'probability' => 0.04],
                            ['className' => 'Sexy', 'probability' => 0.005],
                            ['className' => 'Hentai', 'probability' => 0.003],
                            ['className' => 'Porn', 'probability' => 0.002],
                        ],
                        'dominantClass' => 'Neutral',
                        'confidence' => 0.95,
                        'model' => 'MobileNetV2',
                        'modelVersion' => 'v1',
                        'fileSha256' => $sha,
                        'durationMs' => 45,
                        'requestId' => 'req_' . uniqid(),
                    ], 200);
                } else {
                    return Http::response([
                        'success' => true,
                        'media_type' => 'image',
                        'predictions' => [
                            ['className' => 'Porn', 'probability' => 0.94],
                            ['className' => 'Neutral', 'probability' => 0.03],
                            ['className' => 'Sexy', 'probability' => 0.02],
                            ['className' => 'Hentai', 'probability' => 0.01],
                            ['className' => 'Drawing', 'probability' => 0.0],
                        ],
                        'dominantClass' => 'Porn',
                        'confidence' => 0.94,
                        'model' => 'MobileNetV2',
                        'modelVersion' => 'v1',
                        'fileSha256' => $sha,
                        'durationMs' => 50,
                        'requestId' => 'req_' . uniqid(),
                    ], 200);
                }
            },
        ]);

        $results = [];

        for ($i = 0; $i < 10; $i++) {
            $isSafe = ($i % 2 === 0);
            $currentIsSafe = $isSafe;

            $image = $this->createSampleImage(200, 200);

            $res = $this->postJson('/api/member/posts', [
                'content' => "Concurrent test post #{$i}",
                'media' => $image,
            ]);

            $results[] = [
                'index' => $i,
                'expectedSafe' => $isSafe,
                'status' => $res->status(),
            ];
        }

        $safeCount = 0;
        $blockedCount = 0;

        foreach ($results as $res) {
            if ($res['expectedSafe']) {
                $this->assertTrue(in_array($res['status'], [200, 201]), "Safe upload at index {$res['index']} must succeed (got {$res['status']})");
                $safeCount++;
            } else {
                $this->assertSame(422, $res['status'], "Unsafe upload at index {$res['index']} must be blocked");
                $blockedCount++;
            }
        }

        $this->assertSame(5, $safeCount);
        $this->assertSame(5, $blockedCount);
        $this->assertSame(5, Post::count(), 'Exactly 5 safe posts must be committed to database');

        // Cleanup public files created by safe posts
        foreach (Post::all() as $post) {
            if ($post->media_path) {
                $this->createdFiles[] = $post->media_path;
            }
        }
    }

    // =========================================================================
    // 4. STORAGE & DATABASE NON-MUTATION ON BLOCKED UPLOADS
    // =========================================================================

    public function test_public_storage_and_database_zero_mutation_guarantee_on_block(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $postsDir = public_path('uploads/posts/images');
        $initialPublicFilesCount = File::isDirectory($postsDir) ? count(File::allFiles($postsDir)) : 0;
        $initialPostsCount = Post::count();

        $image = $this->createSampleImage();

        $response = $this->postJson('/api/member/posts', [
            'content' => 'Blocked post attempt',
            'media' => $image,
        ]);

        $response->assertStatus(422);

        $afterPublicFilesCount = File::isDirectory($postsDir) ? count(File::allFiles($postsDir)) : 0;
        $afterPostsCount = Post::count();

        $this->assertSame($initialPublicFilesCount, $afterPublicFilesCount, 'Public storage file count must be identical');
        $this->assertSame($initialPostsCount, $afterPostsCount, 'Database post count must be identical');
    }

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_direct_message_image(): void
    {
        $this->mockBlockedModeration();
        $sender = $this->createTestMember();
        $receiver = $this->createTestMember();
        $this->actingAs($sender, 'member');

        $image = $this->createSampleImage();

        $response = $this->postJson("/api/member/messages/{$receiver->id}", [
            'message' => 'Test message with blocked image',
            'attachment' => $image,
            'is_safe' => true,
            'approved' => true,
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, \App\Models\DirectMessage::count(), 'Zero direct messages created on blocked attachment');
    }

    public function test_direct_api_bypass_with_forged_metadata_is_blocked_on_video_upload(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Porn', 'probability' => 0.95],
                        ['className' => 'Neutral', 'probability' => 0.05],
                    ],
                    'dominantClass' => 'Porn',
                    'confidence' => 0.95,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => 'dummy',
                    'durationMs' => 40,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);

        $member = $this->createTestMember();
        $this->actingAs($member, 'member');

        $videoPath = storage_path('framework/testing/' . uniqid('p9_vid_') . '.mp4');
        $cmd = "ffmpeg -y -f lavfi -i testsrc=duration=1:size=160x120:rate=10 -c:v libx264 -pix_fmt yuv420p \"{$videoPath}\" 2>&1";
        shell_exec($cmd);
        $this->tempFiles[] = $videoPath;

        $videoFile = new UploadedFile($videoPath, 'test_video.mp4', 'video/mp4', null, true);

        $response = $this->postJson('/api/member/posts', [
            'content' => 'Bypass video attempt',
            'media' => $videoFile,
            'media_type' => 'video',
            'is_safe' => true,
            'approved' => true,
            'confidence' => 0.99,
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Post::count(), 'Zero posts created on blocked video upload');
    }
}
