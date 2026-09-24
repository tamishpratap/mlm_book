<?php

namespace Tests\Feature\ContentModeration;

use App\Contracts\ContentModerationProvider;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Member;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Role;
use App\Models\Story;
use App\Services\ContentModeration\ModerationResult;
use App\Services\ContentModeration\VideoAggregationPolicy;
use App\Services\ContentModeration\VideoFrameExtractor;
use App\Services\ContentModeration\VideoModerationResult;
use App\Services\ContentModeration\VideoModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class VideoModerationPipelineTest extends TestCase
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
        $testQuarantine = storage_path('app/quarantine/video-moderation');
        if (File::isDirectory($testQuarantine)) {
            foreach (File::directories($testQuarantine) as $dir) {
                @File::deleteDirectory($dir);
            }
        }

        parent::tearDown();
    }

    private function generateValidTestVideo(int $width = 320, int $height = 240, int $durationSeconds = 2): UploadedFile
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $filename = 'test_vid_' . uniqid() . '.mp4';
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $cmd = [
            'ffmpeg',
            '-y',
            '-f', 'lavfi',
            '-i', "testsrc=duration={$durationSeconds}:size={$width}x{$height}:rate=24",
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            $fullPath,
        ];

        $process = new Process($cmd);
        $process->setTimeout(30);
        $process->run();

        $this->assertTrue($process->isSuccessful(), 'FFmpeg failed to generate test video fixture: ' . $process->getErrorOutput());
        $this->assertFileExists($fullPath);
        $this->tempFiles[] = $fullPath;

        return new UploadedFile($fullPath, $filename, 'video/mp4', null, true);
    }

    private function createDiskFile(string $relativeDir, string $fileName = 'existing_video.mp4'): string
    {
        $dirPath = public_path($relativeDir);
        File::ensureDirectoryExists($dirPath);

        $fullPath = $dirPath . '/' . $fileName;

        $cmd = [
            'ffmpeg',
            '-y',
            '-f', 'lavfi',
            '-i', 'testsrc=duration=1:size=160x120:rate=10',
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            $fullPath,
        ];

        $process = new Process($cmd);
        $process->setTimeout(20);
        $process->run();

        $relativePath = $relativeDir . '/' . $fileName;
        $this->createdFiles[] = $relativePath;

        return $relativePath;
    }

    private function createTestMember(): Member
    {
        return Member::create([
            'name' => 'Video Tester',
            'user_id' => 'vt_' . uniqid(),
            'email' => 'videotester_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    private function createTestAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin->roles()->sync([$superRole->id]);

        return $admin;
    }

    private function mockAllFramesSafe(): void
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
                    'durationMs' => 35,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);
    }

    private function mockFramePornViolation(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Porn', 'probability' => 0.96],
                        ['className' => 'Neutral', 'probability' => 0.02],
                        ['className' => 'Sexy', 'probability' => 0.015],
                        ['className' => 'Hentai', 'probability' => 0.005],
                        ['className' => 'Drawing', 'probability' => 0.0],
                    ],
                    'dominantClass' => 'Porn',
                    'confidence' => 0.96,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 40,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);
    }

    private function mockFrameHentaiViolation(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Hentai', 'probability' => 0.92],
                        ['className' => 'Drawing', 'probability' => 0.05],
                        ['className' => 'Neutral', 'probability' => 0.02],
                        ['className' => 'Sexy', 'probability' => 0.01],
                        ['className' => 'Porn', 'probability' => 0.0],
                    ],
                    'dominantClass' => 'Hentai',
                    'confidence' => 0.92,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 40,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);
    }

    private function mockFrameRepeatedSexyViolation(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Sexy', 'probability' => 0.88],
                        ['className' => 'Neutral', 'probability' => 0.10],
                        ['className' => 'Drawing', 'probability' => 0.01],
                        ['className' => 'Hentai', 'probability' => 0.005],
                        ['className' => 'Porn', 'probability' => 0.005],
                    ],
                    'dominantClass' => 'Sexy',
                    'confidence' => 0.88,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 40,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);
    }

    // =========================================================================
    // 1. VIDEO FRAME EXTRACTOR UNIT TESTS
    // =========================================================================

    public function test_extractor_probe_reads_valid_video_metadata(): void
    {
        $videoFile = $this->generateValidTestVideo(320, 240, 2);
        $extractor = new VideoFrameExtractor();

        $probe = $extractor->probe($videoFile->getRealPath());

        $this->assertTrue($probe['success']);
        $this->assertGreaterThanOrEqual(1.5, $probe['duration']);
        $this->assertSame(320, $probe['width']);
        $this->assertSame(240, $probe['height']);
        $this->assertSame('h264', $probe['codec']);
    }

    public function test_extractor_probe_rejects_corrupt_or_empty_file(): void
    {
        $tempCorrupt = storage_path('framework/testing/corrupt_' . uniqid() . '.mp4');
        file_put_contents($tempCorrupt, 'Not a video file content');

        $extractor = new VideoFrameExtractor();
        $probe = $extractor->probe($tempCorrupt);

        $this->assertFalse($probe['success']);
        $this->assertNotEmpty($probe['error']);

        @unlink($tempCorrupt);
    }

    public function test_extractor_calculates_representative_sample_timestamps(): void
    {
        $extractor = new VideoFrameExtractor();

        // Short video (3s) -> 6 frames
        $shortTimestamps = $extractor->calculateSampleTimestamps(3.0);
        $this->assertCount(6, $shortTimestamps);
        $this->assertGreaterThan(0.0, $shortTimestamps[0]);
        $this->assertLessThanOrEqual(3.0, end($shortTimestamps));

        // Normal video (15s) -> 8 frames
        $normalTimestamps = $extractor->calculateSampleTimestamps(15.0);
        $this->assertCount(8, $normalTimestamps);

        // Long video (60s) -> capped at 12 frames
        $longTimestamps = $extractor->calculateSampleTimestamps(60.0);
        $this->assertCount(12, $longTimestamps);
    }

    public function test_extractor_extracts_jpeg_frames_and_cleans_up(): void
    {
        $videoFile = $this->generateValidTestVideo(320, 240, 2);
        $extractor = new VideoFrameExtractor();

        $outDir = storage_path('app/quarantine/video-moderation/test_' . uniqid());
        $frames = $extractor->extractFrames($videoFile->getRealPath(), $outDir, [0.5, 1.2]);

        $this->assertCount(2, $frames);
        foreach ($frames as $frame) {
            $this->assertFileExists($frame['path']);
            $this->assertNotEmpty($frame['sha256']);
            $this->assertGreaterThan(0, File::size($frame['path']));
        }

        $extractor->cleanupDirectory($outDir);
        $this->assertFalse(File::isDirectory($outDir));
    }

    // =========================================================================
    // 2. AGGREGATION POLICY UNIT TESTS (CASES A - J)
    // =========================================================================

    public function test_aggregation_case_a_all_safe_allows_video(): void
    {
        $policy = new VideoAggregationPolicy();
        $frameResults = [
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'hash1'),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.92]], 'hash2'),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.90]], 'hash3'),
        ];

        $decision = $policy->evaluate($frameResults, 'POST_VIDEO');

        $this->assertTrue($decision->isAllowed());
        $this->assertSame('ALLOW', $decision->action);
        $this->assertSame('SAFE', $decision->reasonCode);
        $this->assertSame(0, $decision->unsafeFrameCount);
    }

    public function test_aggregation_case_b_single_porn_frame_blocks_video(): void
    {
        $policy = new VideoAggregationPolicy();
        $frameResults = [
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'hash1'),
            ModerationResult::allow([
                ['className' => 'Porn', 'probability' => 0.94],
                ['className' => 'Neutral', 'probability' => 0.03],
            ], 'hash2'),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.91]], 'hash3'),
        ];

        $decision = $policy->evaluate($frameResults, 'POST_VIDEO');

        $this->assertTrue($decision->isBlocked());
        $this->assertSame('BLOCK', $decision->action);
        $this->assertSame('FRAME_PORN_EXCEEDED', $decision->reasonCode);
        $this->assertSame('Porn', $decision->dominantClassOverall);
        $this->assertGreaterThanOrEqual(1, $decision->unsafeFrameCount);
    }

    public function test_aggregation_case_c_single_hentai_frame_blocks_video(): void
    {
        $policy = new VideoAggregationPolicy();
        $frameResults = [
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'hash1'),
            ModerationResult::allow([
                ['className' => 'Hentai', 'probability' => 0.91],
                ['className' => 'Neutral', 'probability' => 0.05],
            ], 'hash2'),
        ];

        $decision = $policy->evaluate($frameResults, 'POST_VIDEO');

        $this->assertTrue($decision->isBlocked());
        $this->assertSame('FRAME_HENTAI_EXCEEDED', $decision->reasonCode);
    }

    public function test_aggregation_case_d_repeated_sexy_exceedance_blocks_video(): void
    {
        $policy = new VideoAggregationPolicy();
        $frameResults = [
            ModerationResult::allow([
                ['className' => 'Sexy', 'probability' => 0.85],
                ['className' => 'Neutral', 'probability' => 0.10],
            ], 'hash1'),
            ModerationResult::allow([
                ['className' => 'Sexy', 'probability' => 0.88],
                ['className' => 'Neutral', 'probability' => 0.08],
            ], 'hash2'),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.90]], 'hash3'),
        ];

        $decision = $policy->evaluate($frameResults, 'POST_VIDEO');

        $this->assertTrue($decision->isBlocked());
        $this->assertSame('REPEATED_SEXY_EXCEEDED', $decision->reasonCode);
        $this->assertSame('Sexy', $decision->dominantClassOverall);
    }

    public function test_aggregation_case_g_critical_provider_failure_fails_closed(): void
    {
        $policy = new VideoAggregationPolicy();
        $frameResults = [
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'hash1'),
            ModerationResult::failClosed('TIMEOUT', 'Timeout', 'hash2'),
            ModerationResult::failClosed('TIMEOUT', 'Timeout', 'hash3'),
        ];

        $decision = $policy->evaluate($frameResults, 'POST_VIDEO');

        $this->assertTrue($decision->isFailed());
        $this->assertTrue($decision->isBlocked());
        $this->assertSame('INSUFFICIENT_COVERAGE', $decision->reasonCode);
    }

    public function test_aggregation_case_i_zero_frames_fails_closed(): void
    {
        $policy = new VideoAggregationPolicy();
        $decision = $policy->evaluate([], 'POST_VIDEO');

        $this->assertTrue($decision->isFailed());
        $this->assertSame('ZERO_FRAMES', $decision->reasonCode);
    }

    // =========================================================================
    // 3. END-TO-END FEATURE UPLOAD & GATING TESTS
    // =========================================================================

    public function test_safe_video_upload_succeeds_and_creates_post(): void
    {
        $this->mockAllFramesSafe();
        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Safe family video post',
            'media' => $video,
        ], ['Accept' => 'application/json']);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $post = Post::where('member_id', $member->id)->latest('id')->first();
        $this->assertNotNull($post);
        $this->assertSame('video', $post->media_type);
        $this->assertNotNull($post->media_path);
        $this->assertFileExists(public_path($post->media_path));

        $this->createdFiles[] = $post->media_path;
    }

    public function test_blocked_video_upload_throws_422_and_creates_zero_posts_and_zero_files(): void
    {
        $this->mockFramePornViolation();
        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Violating video post attempt',
            'media' => $video,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['media']);

        // Assert 0 posts created
        $this->assertSame(0, Post::where('member_id', $member->id)->count());

        // Assert 0 public files created in uploads/posts/videos
        $publicDir = public_path('uploads/posts/videos');
        if (File::isDirectory($publicDir)) {
            $files = File::files($publicDir);
            foreach ($files as $f) {
                // Ensure no new file was created during this second
                $this->assertLessThan(now()->subSeconds(2)->timestamp, $f->getMTime(), 'No public file should be created for blocked video.');
            }
        }
    }

    public function test_client_bypass_flag_ignored_and_server_moderates(): void
    {
        $this->mockFramePornViolation();
        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        // Client attempts to forge moderation approval
        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Forged approval attempt',
            'media' => $video,
            'is_safe' => true,
            'moderation_passed' => true,
            'moderation_status' => 'approved',
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Post::where('member_id', $member->id)->count());
    }

    public function test_provider_timeout_fails_closed_and_creates_zero_posts(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response(null, 504),
        ]);

        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Provider timeout test',
            'media' => $video,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Post::where('member_id', $member->id)->count());
    }

    public function test_corrupt_video_rejected_by_probe(): void
    {
        $member = $this->createTestMember();
        $fakeCorrupt = UploadedFile::fake()->create('fake.mp4', 100, 'video/mp4');

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Corrupt video test',
            'media' => $fakeCorrupt,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Post::where('member_id', $member->id)->count());
    }

    public function test_story_video_blocked_throws_422_and_creates_zero_stories(): void
    {
        $this->mockFramePornViolation();
        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/stories', [
            'media' => $video,
            'caption' => 'Blocked Story Video',
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Story::where('member_id', $member->id)->count());
    }

    public function test_business_page_video_post_blocked_throws_422(): void
    {
        $this->mockFramePornViolation();
        $member = $this->createTestMember();
        $page = BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Biz Video Test',
            'page_username' => 'biz_vid_' . strtolower(uniqid()),
            'slug' => 'biz-vid-' . strtolower(uniqid()),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post("/api/member/business-pages/{$page->slug}/posts", [
            'media' => $video,
            'body' => 'Blocked Biz Post Video',
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Post::where('business_page_id', $page->id)->count());
    }

    public function test_marketplace_product_video_blocked_throws_422(): void
    {
        $this->mockFramePornViolation();
        $member = $this->createTestMember();

        $category = \App\Models\Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics-' . uniqid(),
        ]);

        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/marketplace', [
            'title' => 'Gaming Console',
            'description' => 'Brand new gaming console with video demo.',
            'price' => 499.99,
            'category_id' => $category->id,
            'condition' => 'new',
            'videos' => [$video],
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Product::where('member_id', $member->id)->count());
        $this->assertSame(0, ProductMedia::count());
    }

    public function test_admin_post_video_replacement_blocked_preserves_old_video(): void
    {
        $this->mockFramePornViolation();
        $admin = $this->createTestAdmin();
        $member = $this->createTestMember();

        $oldVideoPath = $this->createDiskFile('uploads/posts/videos', 'original_admin_video.mp4');
        $this->assertFileExists(public_path($oldVideoPath));

        $post = Post::create([
            'member_id' => $member->id,
            'body' => 'Original Post with Video',
            'media_path' => $oldVideoPath,
            'media_type' => 'video',
            'visibility' => 'public',
        ]);

        $blockedVideo = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($admin, 'admin')->post("/api/admin/posts/{$post->id}", [
            '_method' => 'PUT',
            'body' => 'Updated Post Body',
            'media' => $blockedVideo,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);

        // Verification: Existing video file must remain intact
        $this->assertFileExists(public_path($oldVideoPath));
        $post->refresh();
        $this->assertSame($oldVideoPath, $post->media_path);
    }
}
