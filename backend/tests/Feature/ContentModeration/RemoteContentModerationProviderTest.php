<?php

namespace Tests\Feature\ContentModeration;

use App\Services\ContentModeration\ModerationPolicy;
use App\Services\ContentModeration\RemoteContentModerationProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemoteContentModerationProviderTest extends TestCase
{
    private string $tempImagePath;
    private string $tempSha256;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempImagePath = sys_get_temp_dir() . '/' . uniqid('prov_test_') . '.jpg';
        $im = imagecreatetruecolor(80, 80);
        $col = imagecolorallocate($im, 20, 80, 140);
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

    public function test_provider_sends_hmac_signed_headers_and_parses_success(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response([
                'success' => true,
                'media_type' => 'image',
                'predictions' => [
                    ['className' => 'Neutral', 'probability' => 0.94],
                    ['className' => 'Drawing', 'probability' => 0.04],
                    ['className' => 'Sexy', 'probability' => 0.01],
                    ['className' => 'Hentai', 'probability' => 0.005],
                    ['className' => 'Porn', 'probability' => 0.005],
                ],
                'dominantClass' => 'Neutral',
                'confidence' => 0.94,
                'model' => 'MobileNetV2',
                'modelVersion' => 'v1',
                'fileSha256' => $this->tempSha256,
                'durationMs' => 85,
                'requestId' => 'req_test_123',
            ], 200),
        ]);

        $provider = new RemoteContentModerationProvider();
        $result = $provider->moderateImage(
            $this->tempImagePath,
            'image/jpeg',
            $this->tempSha256,
            'POST_IMAGE'
        );

        $this->assertTrue($result->isAllowed());
        $this->assertSame('ALLOW', $result->action);
        $this->assertSame('Neutral', $result->dominantClass);
        $this->assertSame('req_test_123', $result->providerRequestId);

        // Verify that HMAC headers were sent
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Moderation-Timestamp')
                && $request->hasHeader('X-Moderation-Nonce')
                && $request->hasHeader('X-Moderation-SHA256')
                && $request->hasHeader('X-Moderation-Signature')
                && $request->header('X-Moderation-SHA256')[0] === $this->tempSha256;
        });
    }

    public function test_provider_fails_closed_on_http_500_server_error(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response(['error' => 'Internal server error'], 500),
        ]);

        $provider = new RemoteContentModerationProvider();
        $result = $provider->moderateImage(
            $this->tempImagePath,
            'image/jpeg',
            $this->tempSha256,
            'POST_IMAGE'
        );

        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertTrue($result->isFailed());
        $this->assertSame('PROVIDER_HTTP_500', $result->errorCode);
    }

    public function test_provider_fails_closed_on_connection_failure(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $provider = new RemoteContentModerationProvider();
        $result = $provider->moderateImage(
            $this->tempImagePath,
            'image/jpeg',
            $this->tempSha256,
            'POST_IMAGE'
        );

        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertTrue($result->isFailed());
        $this->assertSame('PROVIDER_CONNECTION_FAILED', $result->errorCode);
    }

    public function test_provider_fails_closed_on_hash_mismatch_toctou_violation(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response([
                'success' => true,
                'predictions' => [
                    ['className' => 'Neutral', 'probability' => 0.99],
                ],
                'fileSha256' => 'tampered_or_different_hash_value',
                'requestId' => 'req_tampered',
            ], 200),
        ]);

        $provider = new RemoteContentModerationProvider();
        $result = $provider->moderateImage(
            $this->tempImagePath,
            'image/jpeg',
            $this->tempSha256,
            'POST_IMAGE'
        );

        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertSame('HASH_MISMATCH', $result->errorCode);
    }

    public function test_provider_fails_closed_on_malformed_response(): void
    {
        Http::fake([
            '*/v1/moderate/image' => Http::response('NOT_JSON_HTML_BODY', 200),
        ]);

        $provider = new RemoteContentModerationProvider();
        $result = $provider->moderateImage(
            $this->tempImagePath,
            'image/jpeg',
            $this->tempSha256,
            'POST_IMAGE'
        );

        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertSame('MALFORMED_PROVIDER_RESPONSE', $result->errorCode);
    }
}
