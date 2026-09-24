<?php

namespace Tests\Feature\ContentModeration;

use App\Contracts\ContentModerationProvider;
use App\Http\Controllers\ImageCompressionController;
use App\Services\ContentModeration\ModerationResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentModerationEnforcementTest extends TestCase
{
    private string $testUploadDir = 'uploads/test_moderation_enforcement';

    protected function tearDown(): void
    {
        if (File::exists(public_path($this->testUploadDir))) {
            File::deleteDirectory(public_path($this->testUploadDir));
        }
        parent::tearDown();
    }

    private function createSampleImage(int $width = 300, int $height = 300, string $ext = 'jpg'): UploadedFile
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('enf_') . '.' . $ext;
        $im = imagecreatetruecolor($width, $height);
        $col = imagecolorallocate($im, 70, 130, 180);
        imagefill($im, 0, 0, $col);
        imagejpeg($im, $tempPath, 90);
        imagedestroy($im);

        return new UploadedFile($tempPath, 'enforce_sample.' . $ext, 'image/jpeg', null, true);
    }

    public function test_safe_image_is_allowed_and_committed_to_public_storage(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy';
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
                    'durationMs' => 90,
                    'requestId' => 'req_safe',
                ], 200);
            },
        ]);

        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage();

        $result = $controller->compressAndStore(
            $file,
            $this->testUploadDir,
            'post',
            'safe_image.jpg',
            'public_uploads',
            'POST_IMAGE'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);

        @unlink($fullPath);
    }

    public function test_blocked_image_throws_validation_exception_and_creates_zero_public_files(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Porn', 'probability' => 0.92],
                        ['className' => 'Neutral', 'probability' => 0.05],
                        ['className' => 'Sexy', 'probability' => 0.02],
                        ['className' => 'Hentai', 'probability' => 0.01],
                        ['className' => 'Drawing', 'probability' => 0.0],
                    ],
                    'dominantClass' => 'Porn',
                    'confidence' => 0.92,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 110,
                    'requestId' => 'req_blocked',
                ], 200);
            },
        ]);

        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage();

        $destinationPath = public_path($this->testUploadDir . '/blocked_image.jpg');

        $this->expectException(ValidationException::class);

        try {
            $controller->compressAndStore(
                $file,
                $this->testUploadDir,
                'post',
                'blocked_image.jpg',
                'public_uploads',
                'POST_IMAGE'
            );
        } finally {
            // Assert that ZERO public files were created
            $this->assertFileDoesNotExist($destinationPath, 'Blocked file must NEVER be written to public storage!');
        }
    }

    public function test_client_bypass_attempt_is_ignored_and_server_moderation_enforces_block(): void
    {
        // Even if request claims client-side approval, server executes its own classification
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Hentai', 'probability' => 0.88],
                        ['className' => 'Drawing', 'probability' => 0.10],
                        ['className' => 'Neutral', 'probability' => 0.02],
                    ],
                    'dominantClass' => 'Hentai',
                    'confidence' => 0.88,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 95,
                    'requestId' => 'req_bypass_attempt',
                ], 200);
            },
        ]);

        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage();

        $this->expectException(ValidationException::class);

        $controller->compressAndStore(
            $file,
            $this->testUploadDir,
            'post',
            'bypass_image.jpg',
            'public_uploads',
            'POST_IMAGE'
        );
    }

    public function test_provider_timeout_fails_closed_and_creates_zero_public_files(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Service connection timeout');
            },
        ]);

        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage();

        $destinationPath = public_path($this->testUploadDir . '/timeout_image.jpg');

        $this->expectException(ValidationException::class);

        try {
            $controller->compressAndStore(
                $file,
                $this->testUploadDir,
                'post',
                'timeout_image.jpg',
                'public_uploads',
                'POST_IMAGE'
            );
        } finally {
            $this->assertFileDoesNotExist($destinationPath, 'Timed out file must NEVER be written to public storage!');
        }
    }

    public function test_non_image_document_bypasses_social_nsfw_moderation(): void
    {
        $tempPdf = sys_get_temp_dir() . '/' . uniqid('doc_') . '.pdf';
        file_put_contents($tempPdf, '%PDF-1.4 sample document content');

        $file = new UploadedFile($tempPdf, 'sample.pdf', 'application/pdf', null, true);

        $controller = app(ImageCompressionController::class);
        $result = $controller->compressAndStore(
            $file,
            $this->testUploadDir,
            'document',
            'doc.pdf',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $this->assertFileExists(public_path($result));

        @unlink(public_path($result));
    }

    public function test_admin_branding_is_exempt_from_social_moderation(): void
    {
        $file = $this->createSampleImage();

        $controller = app(ImageCompressionController::class);
        $result = $controller->compressAndStore(
            $file,
            'branding',
            'logo',
            'site_logo.jpg',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $this->assertFileExists(public_path($result));

        @unlink(public_path($result));
    }
}
