<?php

namespace Tests\Feature\ContentModeration;

use App\Models\Member;
use App\Models\Post;
use App\Services\ContentModeration\ModerationPolicy;
use App\Services\ContentModeration\ModerationResult;
use App\Services\ContentModeration\VideoAggregationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ContentModerationHardeningTest extends TestCase
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

        // Clean quarantine directories
        $videoQuarantine = storage_path('app/quarantine/video-moderation');
        if (File::isDirectory($videoQuarantine)) {
            foreach (File::directories($videoQuarantine) as $dir) {
                @File::deleteDirectory($dir);
            }
        }

        $imageQuarantine = storage_path('app/quarantine/moderation');
        if (File::isDirectory($imageQuarantine)) {
            foreach (File::files($imageQuarantine) as $f) {
                @File::delete($f->getRealPath());
            }
        }

        parent::tearDown();
    }

    private function generateValidTestVideo(int $width = 320, int $height = 240, int $durationSeconds = 2): UploadedFile
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $filename = 'hardening_vid_' . uniqid() . '.mp4';
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

        $this->assertTrue($process->isSuccessful(), 'FFmpeg failed to generate video fixture');
        $this->tempFiles[] = $fullPath;

        return new UploadedFile($fullPath, $filename, 'video/mp4', null, true);
    }

    private function createSampleImage(int $width = 200, int $height = 200): UploadedFile
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $filename = 'hardening_img_' . uniqid() . '.jpg';
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        $im = imagecreatetruecolor($width, $height);
        $col = imagecolorallocate($im, 100, 150, 200);
        imagefill($im, 0, 0, $col);
        imagejpeg($im, $fullPath, 90);
        imagedestroy($im);

        $this->tempFiles[] = $fullPath;

        return new UploadedFile($fullPath, $filename, 'image/jpeg', null, true);
    }

    private function createTestMember(): Member
    {
        return Member::create([
            'name' => 'Hardening Tester',
            'user_id' => 'ht_' . uniqid(),
            'email' => 'hardtester_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    // =========================================================================
    // 1. EXACT THRESHOLD BOUNDARY TESTS (VALUE < T, VALUE == T, VALUE > T)
    // =========================================================================

    public function test_image_policy_boundary_strictly_deterministic(): void
    {
        $policy = new ModerationPolicy();
        $hash = 'dummy_hash_' . uniqid();

        // 1. Porn Threshold (0.60 for POST_IMAGE)
        // 0.59999 -> ALLOW
        $resLow = $policy->evaluate([
            ['className' => 'Porn', 'probability' => 0.59999],
            ['className' => 'Neutral', 'probability' => 0.40001],
        ], 'POST_IMAGE', $hash);
        $this->assertTrue($resLow->isAllowed(), 'Porn at 0.59999 should be allowed');

        // 0.60000 -> BLOCK (>= operator)
        $resExact = $policy->evaluate([
            ['className' => 'Porn', 'probability' => 0.60000],
            ['className' => 'Neutral', 'probability' => 0.40000],
        ], 'POST_IMAGE', $hash);
        $this->assertTrue($resExact->isBlocked(), 'Porn at exact 0.60000 must be blocked');
        $this->assertSame('EXCEEDS_PORN_THRESHOLD', $resExact->reasonCode);

        // 0.60001 -> BLOCK
        $resHigh = $policy->evaluate([
            ['className' => 'Porn', 'probability' => 0.60001],
            ['className' => 'Neutral', 'probability' => 0.39999],
        ], 'POST_IMAGE', $hash);
        $this->assertTrue($resHigh->isBlocked(), 'Porn at 0.60001 must be blocked');

        // 2. Sexy Threshold (0.80 for POST_IMAGE)
        // 0.79999 -> ALLOW
        $resSexyLow = $policy->evaluate([
            ['className' => 'Sexy', 'probability' => 0.79999],
            ['className' => 'Neutral', 'probability' => 0.20001],
        ], 'POST_IMAGE', $hash);
        $this->assertTrue($resSexyLow->isAllowed(), 'Sexy at 0.79999 should be allowed');

        // 0.80000 -> BLOCK
        $resSexyExact = $policy->evaluate([
            ['className' => 'Sexy', 'probability' => 0.80000],
            ['className' => 'Neutral', 'probability' => 0.20000],
        ], 'POST_IMAGE', $hash);
        $this->assertTrue($resSexyExact->isBlocked(), 'Sexy at exact 0.80000 must be blocked');
        $this->assertSame('EXCEEDS_SEXY_THRESHOLD', $resSexyExact->reasonCode);

        // 3. Stricter Context Check (MARKETPLACE_IMAGE: Porn 0.50, Sexy 0.75)
        $resMarketplacePorn = $policy->evaluate([
            ['className' => 'Porn', 'probability' => 0.52],
            ['className' => 'Neutral', 'probability' => 0.48],
        ], 'MARKETPLACE_IMAGE', $hash);
        $this->assertTrue($resMarketplacePorn->isBlocked(), 'Marketplace Porn at 0.52 must be blocked');
    }

    public function test_video_aggregation_repeated_sexy_and_failure_boundaries(): void
    {
        $policy = new VideoAggregationPolicy();

        // Single Sexy frame at 0.80 -> ALLOW in POST_VIDEO context
        $framesSingleSexy = [
            ModerationResult::allow([['className' => 'Sexy', 'probability' => 0.82]], 'h1', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h2', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h3', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h4', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h5', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h6', 10),
        ];
        $decisionSingle = $policy->evaluate($framesSingleSexy, 'POST_VIDEO');
        $this->assertTrue($decisionSingle->isAllowed(), 'Single sexy frame in feed post should be allowed');

        // 2 Sexy frames at 0.80 -> BLOCK (REPEATED_SEXY_EXCEEDED)
        $framesDoubleSexy = [
            ModerationResult::allow([['className' => 'Sexy', 'probability' => 0.82]], 'h1', 10),
            ModerationResult::allow([['className' => 'Sexy', 'probability' => 0.80]], 'h2', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h3', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h4', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h5', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h6', 10),
        ];
        $decisionDouble = $policy->evaluate($framesDoubleSexy, 'POST_VIDEO');
        $this->assertTrue($decisionDouble->isBlocked(), 'Repeated sexy frames must be blocked');
        $this->assertSame('REPEATED_SEXY_EXCEEDED', $decisionDouble->reasonCode);

        // Coverage Failure Boundary: 8 frames total, max ratio = 0.25 (25%)
        // 2 failures / 8 = 25.0% <= 25.0% -> ALLOWED
        $frames8With2Failures = [
            ModerationResult::failClosed('FRAME_ERR_1', 'err', 'h1'),
            ModerationResult::failClosed('FRAME_ERR_2', 'err', 'h2'),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h3', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h4', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h5', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h6', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h7', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h8', 10),
        ];
        $decisionRatio25 = $policy->evaluate($frames8With2Failures, 'POST_VIDEO');
        $this->assertTrue($decisionRatio25->isAllowed(), '2/8 failures (25%) should pass coverage check');

        // 3 failures / 8 = 37.5% > 25.0% -> FAIL CLOSED
        $frames8With3Failures = [
            ModerationResult::failClosed('FRAME_ERR_1', 'err', 'h1'),
            ModerationResult::failClosed('FRAME_ERR_2', 'err', 'h2'),
            ModerationResult::failClosed('FRAME_ERR_3', 'err', 'h3'),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h4', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h5', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h6', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h7', 10),
            ModerationResult::allow([['className' => 'Neutral', 'probability' => 0.95]], 'h8', 10),
        ];
        $decisionRatio37 = $policy->evaluate($frames8With3Failures, 'POST_VIDEO');
        $this->assertTrue($decisionRatio37->isFailed(), '3/8 failures (37.5%) must fail closed');
        $this->assertSame('INSUFFICIENT_COVERAGE', $decisionRatio37->reasonCode);
    }

    // =========================================================================
    // 2. LIVE HMAC SECURITY & NONCE REPLAY ATTACK DEFENSE
    // =========================================================================

    public function test_live_hmac_replay_attack_and_stale_timestamp_rejection(): void
    {
        $url = config('content_moderation.url', 'http://127.0.0.1:3001');
        $secret = config('content_moderation.secret', 'mlm-moderation-dev-secret-2026');

        // Create sample image
        $image = $this->createSampleImage();
        $fileBytes = file_get_contents($image->getRealPath());
        $fileSha256 = hash('sha256', $fileBytes);

        $timestamp = (string) time();
        $nonce = 'nonce_hardening_' . Str::random(16);
        $payload = "{$timestamp}\n{$nonce}\n{$fileSha256}";
        $signature = hash_hmac('sha256', $payload, $secret);

        // First Request: Fresh, valid HMAC -> HTTP 200
        $resFirst = Http::timeout(5)
            ->withHeaders([
                'X-Moderation-Timestamp' => $timestamp,
                'X-Moderation-Nonce' => $nonce,
                'X-Moderation-SHA256' => $fileSha256,
                'X-Moderation-Signature' => $signature,
                'Accept' => 'application/json',
            ])
            ->attach('file', $fileBytes, 'sample.jpg')
            ->post("{$url}/v1/moderate/image");

        $this->assertTrue($resFirst->successful(), 'Initial HMAC request should succeed: ' . $resFirst->body());
        $this->assertTrue($resFirst->json('success'));

        // Attack 1: Replay with identical nonce -> HTTP 401 REPLAY_ATTACK_DETECTED
        $resReplay = Http::timeout(5)
            ->withHeaders([
                'X-Moderation-Timestamp' => $timestamp,
                'X-Moderation-Nonce' => $nonce,
                'X-Moderation-SHA256' => $fileSha256,
                'X-Moderation-Signature' => $signature,
                'Accept' => 'application/json',
            ])
            ->attach('file', $fileBytes, 'sample.jpg')
            ->post("{$url}/v1/moderate/image");

        $this->assertSame(401, $resReplay->status(), 'Replayed nonce must be rejected with 401');
        $this->assertSame('REPLAY_ATTACK_DETECTED', $resReplay->json('error'));

        // Attack 2: Stale Timestamp (> 60s in the past) -> HTTP 401 EXPIRED_TIMESTAMP
        $staleTimestamp = (string) (time() - 120);
        $staleNonce = 'nonce_stale_' . Str::random(16);
        $stalePayload = "{$staleTimestamp}\n{$staleNonce}\n{$fileSha256}";
        $staleSig = hash_hmac('sha256', $stalePayload, $secret);

        $resStale = Http::timeout(5)
            ->withHeaders([
                'X-Moderation-Timestamp' => $staleTimestamp,
                'X-Moderation-Nonce' => $staleNonce,
                'X-Moderation-SHA256' => $fileSha256,
                'X-Moderation-Signature' => $staleSig,
                'Accept' => 'application/json',
            ])
            ->attach('file', $fileBytes, 'sample.jpg')
            ->post("{$url}/v1/moderate/image");

        $this->assertSame(401, $resStale->status(), 'Stale timestamp must be rejected with 401');
        $this->assertSame('EXPIRED_TIMESTAMP', $resStale->json('error'));

        // Attack 3: Invalid signature -> HTTP 401 INVALID_SIGNATURE
        $freshNonce = 'nonce_tampered_' . Str::random(16);
        $resInvalidSig = Http::timeout(5)
            ->withHeaders([
                'X-Moderation-Timestamp' => (string) time(),
                'X-Moderation-Nonce' => $freshNonce,
                'X-Moderation-SHA256' => $fileSha256,
                'X-Moderation-Signature' => '0000000000000000000000000000000000000000000000000000000000000000',
                'Accept' => 'application/json',
            ])
            ->attach('file', $fileBytes, 'sample.jpg')
            ->post("{$url}/v1/moderate/image");

        $this->assertSame(401, $resInvalidSig->status(), 'Invalid signature must be rejected with 401');
        $this->assertSame('INVALID_SIGNATURE', $resInvalidSig->json('error'));

        // Attack 4: Tampered SHA-256 header vs actual file bytes -> HTTP 400 HASH_MISMATCH
        $tamperedSha = hash('sha256', 'completely_different_payload');
        $tamperedNonce = 'nonce_hash_mismatch_' . Str::random(16);
        $tamperedPayload = "{$timestamp}\n{$tamperedNonce}\n{$tamperedSha}";
        $tamperedSig = hash_hmac('sha256', $tamperedPayload, $secret);

        $resHashMismatch = Http::timeout(5)
            ->withHeaders([
                'X-Moderation-Timestamp' => $timestamp,
                'X-Moderation-Nonce' => $tamperedNonce,
                'X-Moderation-SHA256' => $tamperedSha,
                'X-Moderation-Signature' => $tamperedSig,
                'Accept' => 'application/json',
            ])
            ->attach('file', $fileBytes, 'sample.jpg')
            ->post("{$url}/v1/moderate/image");

        $this->assertSame(400, $resHashMismatch->status(), 'Tampered SHA-256 header must be rejected with 400');
        $this->assertSame('HASH_MISMATCH', $resHashMismatch->json('error'));
    }

    // =========================================================================
    // 3. FAIL-CLOSED BEHAVIOR UNDER MALFORMED AND 500 PROVIDER RESPONSES
    // =========================================================================

    public function test_malformed_provider_payload_fails_closed(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response([
                'success' => true,
                // Missing 'predictions' array entirely
                'broken_field' => 'malformed',
            ], 200),
        ]);

        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Malformed provider test',
            'media' => $video,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Post::where('member_id', $member->id)->count());
    }

    public function test_server_500_provider_error_fails_closed(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response(['error' => 'Internal Model Crash'], 500),
        ]);

        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Server 500 test',
            'media' => $video,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);
        $this->assertSame(0, Post::where('member_id', $member->id)->count());
    }

    // =========================================================================
    // 4. RESOURCE HYGIENE & COMPLETE QUARANTINE PURGE VERIFICATION
    // =========================================================================

    public function test_blocked_video_guarantees_quarantine_and_frame_purge(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Porn', 'probability' => 0.99],
                        ['className' => 'Neutral', 'probability' => 0.01],
                    ],
                    'dominantClass' => 'Porn',
                    'confidence' => 0.99,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 20,
                    'requestId' => 'req_' . uniqid(),
                ], 200);
            },
        ]);

        $member = $this->createTestMember();
        $video = $this->generateValidTestVideo(320, 240, 2);

        $res = $this->actingAs($member, 'member')->post('/api/member/posts', [
            'body' => 'Resource purge test',
            'media' => $video,
        ], ['Accept' => 'application/json']);

        $res->assertStatus(422);

        // Verification: Check that NO files remain in quarantine
        $videoQuarantine = storage_path('app/quarantine/video-moderation');
        if (File::isDirectory($videoQuarantine)) {
            $files = File::allFiles($videoQuarantine);
            $this->assertCount(0, $files, 'No quarantined video or extracted frames may linger on disk after a blocked upload');
        }
    }
}
