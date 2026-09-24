<?php

namespace Tests\Unit\ContentModeration;

use App\Services\ContentModeration\QuarantineFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class QuarantineFileServiceTest extends TestCase
{
    private QuarantineFileService $service;
    private string $testQuarantineDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testQuarantineDir = sys_get_temp_dir() . '/test_quarantine_' . uniqid();
        $this->service = new QuarantineFileService($this->testQuarantineDir);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testQuarantineDir)) {
            File::deleteDirectory($this->testQuarantineDir);
        }
        parent::tearDown();
    }

    private function createSampleImageFile(string $ext = 'jpg'): UploadedFile
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('sample_') . '.' . $ext;
        $im = imagecreatetruecolor(100, 100);
        $col = imagecolorallocate($im, 50, 100, 150);
        imagefill($im, 0, 0, $col);

        if ($ext === 'png') {
            imagepng($im, $tempPath);
            $mime = 'image/png';
        } else {
            imagejpeg($im, $tempPath, 90);
            $mime = 'image/jpeg';
        }
        imagedestroy($im);

        return new UploadedFile($tempPath, 'test_image.' . $ext, $mime, null, true);
    }

    public function test_quarantine_stages_file_with_random_name_and_sha256(): void
    {
        $uploaded = $this->createSampleImageFile('jpg');
        $expectedSha256 = hash_file('sha256', $uploaded->getRealPath());

        $info = $this->service->quarantine($uploaded);

        $this->assertFileExists($info['path']);
        $this->assertSame($expectedSha256, $info['sha256']);
        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(100, $info['width']);
        $this->assertSame(100, $info['height']);
        $this->assertStringEndsWith('.tmp', $info['path']);

        // Verify cleanup
        $this->assertTrue($this->service->cleanup($info['path']));
        $this->assertFileDoesNotExist($info['path']);
    }

    public function test_is_decodable_image_returns_true_for_valid_image(): void
    {
        $uploaded = $this->createSampleImageFile('png');
        $info = $this->service->quarantine($uploaded);

        $this->assertTrue($this->service->isDecodableImage($info['path']));

        $this->service->cleanup($info['path']);
    }

    public function test_is_decodable_image_returns_false_for_corrupted_file(): void
    {
        $corruptPath = $this->testQuarantineDir . '/corrupt.tmp';
        file_put_contents($corruptPath, 'THIS_IS_NOT_AN_IMAGE_DATA');

        $this->assertFalse($this->service->isDecodableImage($corruptPath));

        @unlink($corruptPath);
    }

    public function test_cleanup_handles_null_or_non_existent_path_safely(): void
    {
        $this->assertFalse($this->service->cleanup(null));
        $this->assertFalse($this->service->cleanup('/non/existent/path/here.tmp'));
    }
}
