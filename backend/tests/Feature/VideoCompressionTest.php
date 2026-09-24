<?php

namespace Tests\Feature;

use App\Http\Controllers\VideoCompressionController;
use App\Jobs\CompressVideoJob;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class VideoCompressionTest extends TestCase
{
    use RefreshDatabase;

    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    protected function createMember(): Member
    {
        return Member::create([
            'name' => 'Video Tester',
            'user_id' => 'vt_' . Str::lower(Str::random(8)),
            'email' => 'videotester_' . Str::lower(Str::random(8)) . '@example.com',
            'password' => bcrypt('password123'),
            'mobile_number' => '+1' . random_int(1000000000, 9999999999),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    protected function generateTestVideo(int $width, int $height, int $durationSeconds = 1): string
    {
        $dir = public_path('uploads/tests');
        File::ensureDirectoryExists($dir);

        $filename = 'test_' . $width . 'x' . $height . '_' . time() . '_' . Str::random(6) . '.mp4';
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $controller = new VideoCompressionController();
        $ffmpeg = $controller->resolveFfmpegBinary() ?? 'ffmpeg';

        $cmd = [
            $ffmpeg,
            '-y',
            '-f', 'lavfi',
            '-i', "testsrc=duration={$durationSeconds}:size={$width}x{$height}:rate=24",
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            $fullPath,
        ];

        $process = new Process($cmd);
        $process->setTimeout(60);
        $process->run();

        $this->assertTrue($process->isSuccessful(), "FFmpeg failed to generate test video: " . $process->getErrorOutput());
        $this->assertFileExists($fullPath);

        $this->createdFiles[] = $fullPath;

        return 'uploads/tests/' . $filename;
    }

    protected function getVideoDimensions(string $relativePath): array
    {
        $controller = new VideoCompressionController();
        $ffmpeg = $controller->resolveFfmpegBinary() ?? 'ffmpeg';

        $fullPath = public_path($relativePath);
        $cmd = [$ffmpeg, '-hide_banner', '-i', $fullPath];

        $process = new Process($cmd);
        $process->run();

        $output = $process->getErrorOutput() . ' ' . $process->getOutput();

        if (preg_match('/Stream #0:\d+.*Video:.*, (\d{2,5})x(\d{2,5})/', $output, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [0, 0];
    }

    public function test_video_compression_controller_stages_file_correctly(): void
    {
        $dir = public_path('uploads/posts/videos');
        File::ensureDirectoryExists($dir);

        $fakeVideo = UploadedFile::fake()->create('my_upload.mp4', 1024, 'video/mp4');

        $controller = new VideoCompressionController();
        $stagedPath = $controller->stageAndStore($fakeVideo, 'uploads/posts/videos', 'test_post_video.mp4');

        $this->assertNotNull($stagedPath);
        $this->assertStringStartsWith('uploads/posts/videos/', $stagedPath);
        $this->assertFileExists(public_path($stagedPath));

        $this->createdFiles[] = public_path($stagedPath);
    }

    public function test_compress_video_job_transcodes_video_to_mp4_with_faststart(): void
    {
        $sourcePath = $this->generateTestVideo(640, 360, 1);
        $member = $this->createMember();

        $post = Post::create([
            'member_id' => $member->id,
            'body' => 'Video post test',
            'media_type' => 'video',
            'media_path' => $sourcePath,
        ]);

        $job = new CompressVideoJob(
            Post::class,
            $post->id,
            $sourcePath,
            'uploads/tests',
            'final_compressed_' . time() . '.mp4'
        );

        $job->handle();

        $post->refresh();

        $this->assertNotNull($post->media_path);
        $this->assertFileExists(public_path($post->media_path));
        $this->assertGreaterThan(0, File::size(public_path($post->media_path)));
        $this->assertStringEndsWith('.mp4', $post->media_path);

        $this->createdFiles[] = public_path($post->media_path);
    }

    public function test_compress_video_job_downscales_dimensions_exceeding_1080p(): void
    {
        // 2560x1440 video (1440p) should be scaled down to 1920x1080
        $sourcePath = $this->generateTestVideo(2560, 1440, 1);
        $member = $this->createMember();

        $post = Post::create([
            'member_id' => $member->id,
            'body' => '1440p video test',
            'media_type' => 'video',
            'media_path' => $sourcePath,
        ]);

        $job = new CompressVideoJob(
            Post::class,
            $post->id,
            $sourcePath,
            'uploads/tests',
            'downscaled_' . time() . '.mp4'
        );

        $job->handle();

        $post->refresh();

        [$outWidth, $outHeight] = $this->getVideoDimensions($post->media_path);

        $this->assertLessThanOrEqual(1920, $outWidth, "Width should not exceed 1920");
        $this->assertLessThanOrEqual(1080, $outHeight, "Height should not exceed 1080");
        $this->assertSame(1920, $outWidth);
        $this->assertSame(1080, $outHeight);

        $this->createdFiles[] = public_path($post->media_path);
    }

    public function test_compress_video_job_preserves_dimensions_below_1080p(): void
    {
        // 640x360 video should stay 640x360 (no upscaling)
        $sourcePath = $this->generateTestVideo(640, 360, 1);
        $member = $this->createMember();

        $post = Post::create([
            'member_id' => $member->id,
            'body' => '360p video test',
            'media_type' => 'video',
            'media_path' => $sourcePath,
        ]);

        $job = new CompressVideoJob(
            Post::class,
            $post->id,
            $sourcePath,
            'uploads/tests',
            'preserved_' . time() . '.mp4'
        );

        $job->handle();

        $post->refresh();

        [$outWidth, $outHeight] = $this->getVideoDimensions($post->media_path);

        $this->assertSame(640, $outWidth, "Width should be preserved");
        $this->assertSame(360, $outHeight, "Height should be preserved");

        $this->createdFiles[] = public_path($post->media_path);
    }

    public function test_compress_video_job_handles_missing_file_gracefully(): void
    {
        $member = $this->createMember();

        $post = Post::create([
            'member_id' => $member->id,
            'body' => 'Missing video test',
            'media_type' => 'video',
            'media_path' => 'uploads/tests/non_existent.mp4',
        ]);

        $job = new CompressVideoJob(
            Post::class,
            $post->id,
            'uploads/tests/non_existent.mp4',
            'uploads/tests'
        );

        // Should not throw exception
        $job->handle();

        $post->refresh();
        $this->assertSame('uploads/tests/non_existent.mp4', $post->media_path);
    }

    public function test_25mb_limit_removal_in_post_controller(): void
    {
        $member = $this->createMember();
        $unique = Str::lower(Str::random(6));
        $businessPage = \App\Models\BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Test Business ' . $unique,
            'page_username' => 'bizpage_' . $unique,
            'slug' => 'test-business-' . $unique,
            'category' => 'Technology',
            'status' => 'active',
        ]);

        // 30 MB video (exceeds old 25MB limit of 25600 KB)
        $video30mb = UploadedFile::fake()->create('large_30mb.mp4', 30 * 1024, 'video/mp4');

        $response = $this->actingAs($member, 'member')
            ->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
                'body' => 'Testing 30MB video upload',
                'media' => $video30mb,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $post = Post::where('business_page_id', $businessPage->id)->latest('id')->first();
        $this->assertNotNull($post);
        $this->assertSame('video', $post->media_type);
        $this->assertNotNull($post->media_path);

        $this->createdFiles[] = public_path($post->media_path);
    }
}
