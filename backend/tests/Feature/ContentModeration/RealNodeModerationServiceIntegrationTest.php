<?php

namespace Tests\Feature\ContentModeration;

use App\Services\ContentModeration\RemoteContentModerationProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RealNodeModerationServiceIntegrationTest extends TestCase
{
    private string $serviceUrl;
    private string $tempImagePath;
    private string $tempSha256;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceUrl = rtrim(config('content_moderation.url', 'http://127.0.0.1:3001'), '/');

        // Create a real sample JPEG fixture
        $this->tempImagePath = sys_get_temp_dir() . '/' . uniqid('real_service_') . '.jpg';
        $im = imagecreatetruecolor(224, 224);
        $col = imagecolorallocate($im, 45, 120, 190);
        imagefill($im, 0, 0, $col);
        imagejpeg($im, $this->tempImagePath, 90);
        imagedestroy($im);

        $this->tempSha256 = hash_file('sha256', $this->tempImagePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempImagePath)) {
            @unlink($this->tempImagePath);
        }
        parent::tearDown();
    }

    private function isServiceAvailable(): bool
    {
        try {
            $res = Http::timeout(2)->get("{$this->serviceUrl}/health");
            return $res->successful() && ($res->json('ready') === true);
        } catch (\Throwable) {
            return false;
        }
    }

    public function test_live_service_health_and_readiness_endpoint(): void
    {
        if (! $this->isServiceAvailable()) {
            $this->markTestSkipped('Live Node.js moderation service is not running on ' . $this->serviceUrl);
        }

        $res = Http::get("{$this->serviceUrl}/health");

        $this->assertTrue($res->successful());
        $data = $res->json();
        $this->assertSame('healthy', $data['status'] ?? null);
        $this->assertTrue($data['ready'] ?? false);
        $this->assertSame('MobileNetV2', $data['model'] ?? null);
        $this->assertSame('v1', $data['version'] ?? null);
        $this->assertArrayNotHasKey('secret', $data, 'Health check must never leak secrets');
    }

    public function test_live_service_full_roundtrip_inference_with_hmac_auth(): void
    {
        if (! $this->isServiceAvailable()) {
            $this->markTestSkipped('Live Node.js moderation service is not running on ' . $this->serviceUrl);
        }

        $provider = new RemoteContentModerationProvider();

        $startTime = microtime(true);
        $result = $provider->moderateImage(
            $this->tempImagePath,
            'image/jpeg',
            $this->tempSha256,
            'POST_IMAGE'
        );
        $elapsedMs = (int) round((microtime(true) - $startTime) * 1000);

        $this->assertTrue($result->success, 'Live moderation call must succeed');
        $this->assertSame('ALLOW', $result->action);
        $this->assertNotEmpty($result->predictions);
        $this->assertCount(5, $result->predictions);
        $this->assertSame($this->tempSha256, $result->fileHash);
        $this->assertNotNull($result->providerRequestId);
        $this->assertGreaterThan(0, $result->durationMs);
        $this->assertLessThan(10000, $elapsedMs, 'Live inference must finish within timeout');
    }

    public function test_live_service_rejects_tampered_hmac_signature(): void
    {
        if (! $this->isServiceAvailable()) {
            $this->markTestSkipped('Live Node.js moderation service is not running on ' . $this->serviceUrl);
        }

        $timestamp = (string) time();
        $nonce = 'nonce_' . uniqid();
        $fileSha256 = $this->tempSha256;
        $invalidSignature = 'deadbeefcafebabe0123456789abcdef0123456789abcdef0123456789abcdef';

        $fileHandle = fopen($this->tempImagePath, 'r');
        $res = Http::withHeaders([
            'X-Moderation-Timestamp' => $timestamp,
            'X-Moderation-Nonce' => $nonce,
            'X-Moderation-SHA256' => $fileSha256,
            'X-Moderation-Signature' => $invalidSignature,
        ])
        ->attach('file', $fileHandle, basename($this->tempImagePath))
        ->post("{$this->serviceUrl}/v1/moderate/image");

        fclose($fileHandle);

        $this->assertSame(401, $res->status(), 'Service must reject tampered HMAC with 401');
        $this->assertSame('INVALID_SIGNATURE', $res->json('error'));
    }

    public function test_live_service_rejects_expired_timestamp(): void
    {
        if (! $this->isServiceAvailable()) {
            $this->markTestSkipped('Live Node.js moderation service is not running on ' . $this->serviceUrl);
        }

        $staleTimestamp = (string) (time() - 300); // 5 minutes in past
        $nonce = 'nonce_stale_' . uniqid();
        $secret = config('content_moderation.secret', 'mlm-moderation-dev-secret-2026');
        $payloadToSign = "{$staleTimestamp}\n{$nonce}\n{$this->tempSha256}";
        $signature = hash_hmac('sha256', $payloadToSign, $secret);

        $fileHandle = fopen($this->tempImagePath, 'r');
        $res = Http::withHeaders([
            'X-Moderation-Timestamp' => $staleTimestamp,
            'X-Moderation-Nonce' => $nonce,
            'X-Moderation-SHA256' => $this->tempSha256,
            'X-Moderation-Signature' => $signature,
        ])
        ->attach('file', $fileHandle, basename($this->tempImagePath))
        ->post("{$this->serviceUrl}/v1/moderate/image");

        fclose($fileHandle);

        $this->assertSame(401, $res->status(), 'Service must reject expired timestamp with 401');
        $this->assertSame('EXPIRED_TIMESTAMP', $res->json('error'));
    }
}
