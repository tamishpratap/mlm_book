<?php

namespace Tests\Feature;

use App\Http\Controllers\ImageCompressionController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompressionTest extends TestCase
{
    protected function tearDown(): void
    {
        // Cleanup test directories if created
        File::deleteDirectory(public_path('uploads/test_test_compression'));
        parent::tearDown();
    }

    private function createSampleImage(int $width = 800, int $height = 800, string $ext = 'jpg'): UploadedFile
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('test_img_') . '.' . $ext;
        $im = imagecreatetruecolor($width, $height);
        $col = imagecolorallocate($im, 100, 150, 200);
        imagefill($im, 0, 0, $col);

        if ($ext === 'png') {
            imagepng($im, $tempPath);
        } elseif ($ext === 'webp') {
            imagewebp($im, $tempPath);
        } else {
            imagejpeg($im, $tempPath, 90);
        }
        imagedestroy($im);

        $mime = match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return new UploadedFile($tempPath, 'sample.' . $ext, $mime, null, true);
    }

    public function test_image_compression_controller_scales_down_large_image(): void
    {
        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage(2400, 2400, 'jpg');

        $result = $controller->compressAndStore(
            $file,
            'uploads/test_test_compression',
            'post',
            'scaled_post.jpg',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);

        [$w, $h] = getimagesize($fullPath);
        $this->assertLessThanOrEqual(1600, $w);
        $this->assertLessThanOrEqual(1600, $h);

        @unlink($fullPath);
    }

    public function test_avatar_and_logo_profile_scales_to_500px(): void
    {
        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage(1200, 1200, 'jpg');

        $result = $controller->compressAndStore(
            $file,
            'uploads/test_test_compression',
            'avatar',
            'scaled_avatar.jpg',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);

        [$w, $h] = getimagesize($fullPath);
        $this->assertLessThanOrEqual(500, $w);
        $this->assertLessThanOrEqual(500, $h);

        @unlink($fullPath);
    }

    public function test_svg_file_bypasses_raster_compression(): void
    {
        $controller = app(ImageCompressionController::class);
        $tempPath = sys_get_temp_dir() . '/' . uniqid('test_svg_') . '.svg';
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50"><circle cx="25" cy="25" r="20" fill="green"/></svg>';
        file_put_contents($tempPath, $svgContent);

        $file = new UploadedFile($tempPath, 'icon.svg', 'image/svg+xml', null, true);

        $result = $controller->compressAndStore(
            $file,
            'uploads/test_test_compression',
            'logo',
            'test_icon.svg',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('<svg', file_get_contents($fullPath));

        @unlink($fullPath);
        @unlink($tempPath);
    }

    public function test_non_image_document_bypasses_compression_safely(): void
    {
        $controller = app(ImageCompressionController::class);
        $tempPath = sys_get_temp_dir() . '/' . uniqid('test_pdf_') . '.pdf';
        file_put_contents($tempPath, '%PDF-1.4 test dummy content');

        $file = new UploadedFile($tempPath, 'document.pdf', 'application/pdf', null, true);

        $result = $controller->compressAndStore(
            $file,
            'uploads/test_test_compression',
            'verification',
            'doc.pdf',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);
        $this->assertEquals('%PDF-1.4 test dummy content', file_get_contents($fullPath));

        @unlink($fullPath);
        @unlink($tempPath);
    }

    public function test_storage_public_disk_stores_file_in_storage(): void
    {
        $controller = app(ImageCompressionController::class);
        $file = $this->createSampleImage(800, 800, 'png');

        $result = $controller->compressAndStore(
            $file,
            'branding',
            'logo',
            'test_storage.png',
            'public'
        );

        $this->assertNotNull($result);
        $this->assertTrue(Storage::disk('public')->exists($result));

        Storage::disk('public')->delete($result);
    }

    public function test_corrupted_image_falls_back_to_raw_storage(): void
    {
        $controller = app(ImageCompressionController::class);
        $tempPath = sys_get_temp_dir() . '/' . uniqid('corrupt_') . '.jpg';
        file_put_contents($tempPath, 'NOT_A_VALID_IMAGE_DATA_123');

        $file = new UploadedFile($tempPath, 'corrupt.jpg', 'image/jpeg', null, true);

        $result = $controller->compressAndStore(
            $file,
            'uploads/test_test_compression',
            'general',
            'fallback.jpg',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);
        $this->assertEquals('NOT_A_VALID_IMAGE_DATA_123', file_get_contents($fullPath));

        @unlink($fullPath);
        @unlink($tempPath);
    }

    public function test_png_transparency_preserved(): void
    {
        $controller = app(ImageCompressionController::class);
        $tempPath = sys_get_temp_dir() . '/' . uniqid('test_png_') . '.png';
        $im = imagecreatetruecolor(400, 400);
        imagesavealpha($im, true);
        $transColour = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transColour);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefilledellipse($im, 200, 200, 100, 100, $red);
        imagepng($im, $tempPath);
        imagedestroy($im);

        $file = new UploadedFile($tempPath, 'transparent.png', 'image/png', null, true);
        $result = $controller->compressAndStore(
            $file,
            'uploads/test_test_compression',
            'logo',
            'trans_logo.png',
            'public_uploads'
        );

        $this->assertNotNull($result);
        $fullPath = public_path($result);
        $this->assertFileExists($fullPath);

        // Verify that alpha transparency remains intact
        $savedIm = imagecreatefrompng($fullPath);
        $rgba = imagecolorat($savedIm, 10, 10);
        $colors = imagecolorsforindex($savedIm, $rgba);
        imagedestroy($savedIm);

        $this->assertEquals(127, $colors['alpha']);

        @unlink($fullPath);
        @unlink($tempPath);
    }

    public function test_5mb_logo_upload_allowed_by_validation(): void
    {
        // A 3.5 MB file (3500 KB) exceeds old 2048 KB limit, but is under new 5120 KB limit
        $threePointFiveMbFile = UploadedFile::fake()->image('logo.jpg')->size(3500);

        $rules = [
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['logo' => $threePointFiveMbFile],
            $rules
        );

        $this->assertFalse($validator->fails(), 'Validation should pass for 3.5MB file under the new 5MB limit');

        // A 6 MB file (6000 KB) should still fail
        $sixMbFile = UploadedFile::fake()->image('huge.jpg')->size(6000);
        $validatorOver = \Illuminate\Support\Facades\Validator::make(
            ['logo' => $sixMbFile],
            $rules
        );

        $this->assertTrue($validatorOver->fails(), 'Validation should fail for 6MB file');
    }
}
