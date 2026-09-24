<?php

namespace App\Services\ContentModeration;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class QuarantineFileService
{
    /**
     * Staging directory for temporary quarantine.
     */
    protected string $quarantineDirectory;

    public function __construct(?string $quarantineDirectory = null)
    {
        $this->quarantineDirectory = $quarantineDirectory ?: config(
            'content_moderation.quarantine_path',
            storage_path('app/quarantine/moderation')
        );

        if (! File::exists($this->quarantineDirectory)) {
            File::makeDirectory($this->quarantineDirectory, 0755, true, true);
        }
    }

    /**
     * Safely quarantine an uploaded file before moderation.
     *
     * @param UploadedFile $file
     * @return array{path: string, sha256: string, mime: string, size: int, width: ?int, height: ?int}
     * @throws \RuntimeException If file cannot be moved or is invalid
     */
    public function quarantine(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new \RuntimeException('Uploaded file is corrupted or incomplete.');
        }

        $uuid = (string) Str::uuid();
        $targetFilename = "{$uuid}.tmp";
        $targetPath = $this->quarantineDirectory . DIRECTORY_SEPARATOR . $targetFilename;

        // Copy/move file to quarantine
        $realPath = $file->getRealPath();
        if (! copy($realPath, $targetPath)) {
            throw new \RuntimeException('Failed to quarantine uploaded file.');
        }

        // Calculate cryptographic SHA-256
        $sha256 = hash_file('sha256', $targetPath);
        if ($sha256 === false) {
            $this->cleanup($targetPath);
            throw new \RuntimeException('Failed to calculate SHA-256 for quarantined file.');
        }

        // Detect real byte-level MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $targetPath) : null;
        if ($finfo) {
            finfo_close($finfo);
        }
        $mime = $mime ?: 'application/octet-stream';

        // Check image decodability
        $imageSize = @getimagesize($targetPath);
        $width = $imageSize ? (int) $imageSize[0] : null;
        $height = $imageSize ? (int) $imageSize[1] : null;

        return [
            'path' => $targetPath,
            'sha256' => strtolower($sha256),
            'mime' => strtolower($mime),
            'size' => (int) filesize($targetPath),
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Check if a quarantined file is a valid decodable raster image.
     */
    public function isDecodableImage(string $absolutePath): bool
    {
        if (! File::exists($absolutePath) || File::size($absolutePath) === 0) {
            return false;
        }

        $imageSize = @getimagesize($absolutePath);

        return $imageSize !== false && ! empty($imageSize[0]) && ! empty($imageSize[1]);
    }

    /**
     * Safely delete a file from quarantine.
     */
    public function cleanup(?string $absolutePath): bool
    {
        if ($absolutePath && File::exists($absolutePath)) {
            return @unlink($absolutePath);
        }

        return false;
    }

    /**
     * Purge stale quarantine files older than $maxAgeSeconds (default 1 hour).
     */
    public function purgeExpired(int $maxAgeSeconds = 3600): int
    {
        if (! File::exists($this->quarantineDirectory)) {
            return 0;
        }

        $count = 0;
        $now = time();
        $files = File::files($this->quarantineDirectory);

        foreach ($files as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            if (($now - $file->getMTime()) > $maxAgeSeconds) {
                if (@unlink($file->getPathname())) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get quarantine directory path.
     */
    public function getQuarantineDirectory(): string
    {
        return $this->quarantineDirectory;
    }
}
