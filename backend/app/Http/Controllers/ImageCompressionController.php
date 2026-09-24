<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageCompressionController extends Controller
{
    /**
     * Cached ImageManager instance.
     */
    protected static ?ImageManager $manager = null;

    /**
     * Get or initialize the ImageManager instance.
     */
    protected function getManager(): ImageManager
    {
        if (static::$manager === null) {
            static::$manager = new ImageManager(new Driver());
        }

        return static::$manager;
    }

    /**
     * Compress and store an uploaded image or file.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory Target relative directory (e.g., 'uploads/avatars', 'branding')
     * @param string $type Optimization profile ('avatar', 'logo', 'cover', 'post', 'story', 'marketplace', 'review', 'qr', 'document', 'general')
     * @param string|null $customFilename Optional custom filename
     * @param string $disk 'public_uploads' (default, uses public_path()) or 'public' (uses Storage::disk('public'))
     * @param string|null $moderationContext Deprecated unused parameter for backwards compatibility
     * @return string|null Stored relative path, or null on failure
     */
    public function compressAndStore(
        UploadedFile $file,
        string $directory,
        string $type = 'general',
        ?string $customFilename = null,
        string $disk = 'public_uploads',
        ?string $moderationContext = null
    ): ?string {
        if (! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        // Determine destination filename
        $filename = $this->determineFilename($customFilename, $extension, $type);

        // Check if file should bypass raster compression (SVG, animated GIF, non-image)
        if ($this->shouldBypassCompression($extension, $mime, $file)) {
            return $this->storeRawFile($file, $directory, $filename, $disk);
        }

        // Attempt compression with safe fallback to raw storage on error
        try {
            return $this->processAndSaveImage($file, $directory, $filename, $extension, $type, $disk);
        } catch (\Throwable $e) {
            Log::warning("Image compression failed for [{$file->getClientOriginalName()}]: {$e->getMessage()}. Falling back to raw file save.");

            return $this->storeRawFile($file, $directory, $filename, $disk);
        }
    }

    /**
     * Determine final filename ensuring consistent extension.
     */
    protected function determineFilename(?string $customFilename, string $extension, string $type): string
    {
        if (! empty($customFilename)) {
            $hasExt = pathinfo($customFilename, PATHINFO_EXTENSION);
            if (! empty($hasExt)) {
                return $customFilename;
            }

            return $customFilename . '.' . $extension;
        }

        return sprintf('%s_%d_%s.%s', $type, time(), Str::lower(Str::random(8)), $extension);
    }

    /**
     * Check whether compression should be bypassed (SVG, animated GIF, documents, etc.).
     */
    protected function shouldBypassCompression(string $extension, string $mime, UploadedFile $file): bool
    {
        // Bypass SVGs (vector XML)
        if ($extension === 'svg' || str_contains($mime, 'svg')) {
            return true;
        }

        // Bypass non-images (e.g. PDF, DOC, audio, video)
        if (! str_starts_with($mime, 'image/') && ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            return true;
        }

        // For GIFs: inspect if animated to preserve animation frames
        if ($extension === 'gif' || str_contains($mime, 'gif')) {
            try {
                $image = $this->getManager()->decodePath($file->getRealPath());
                if ($image->isAnimated()) {
                    return true;
                }
            } catch (\Throwable) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process, resize, and save the compressed image.
     *
     * @param UploadedFile|string $fileOrPath UploadedFile instance or absolute path to file
     */
    protected function processAndSaveImage(
        UploadedFile|string $fileOrPath,
        string $directory,
        string $filename,
        string $extension,
        string $type,
        string $disk
    ): string {
        $manager = $this->getManager();
        $sourcePath = is_string($fileOrPath) ? $fileOrPath : $fileOrPath->getRealPath();
        $image = $manager->decodePath($sourcePath);

        [$maxWidth, $maxHeight, $quality] = $this->getProfileDimensionsAndQuality($type);

        // Scale down without upscaling
        $image->scaleDown(width: $maxWidth, height: $maxHeight);

        // Normalize format extension: 'jpeg' -> 'jpg'
        $formatExt = in_array($extension, ['jpg', 'jpeg']) ? 'jpg' : $extension;

        if ($disk === 'public_uploads' || $disk === 'public_path') {
            $fullDir = public_path($directory);
            File::ensureDirectoryExists($fullDir);

            $destination = $fullDir . DIRECTORY_SEPARATOR . $filename;

            if ($formatExt === 'png') {
                $image->save($destination);
            } else {
                $image->save($destination, $quality);
            }

            return trim($directory, '/\\') . '/' . $filename;
        } else {
            // Laravel Storage disk (e.g. 'public')
            if ($formatExt === 'png') {
                $encoded = $image->encodeUsingFileExtension('png');
            } else {
                $encoded = $image->encodeUsingFileExtension($formatExt, $quality);
            }

            $storagePath = trim($directory, '/\\') . '/' . $filename;
            Storage::disk($disk)->put($storagePath, (string) $encoded);

            return $storagePath;
        }
    }

    /**
     * Get target max dimensions and quality for a given profile type.
     *
     * @return array{0: int, 1: int, 2: int} [maxWidth, maxHeight, quality]
     */
    protected function getProfileDimensionsAndQuality(string $type): array
    {
        return match ($type) {
            'avatar', 'logo', 'favicon' => [500, 500, 82],
            'cover', 'banner' => [1600, 1600, 82],
            'qr' => [1000, 1000, 95],
            'document', 'verification' => [1600, 1600, 85],
            'post', 'story', 'marketplace', 'review' => [1600, 1600, 80],
            default => [1600, 1600, 80],
        };
    }

    /**
     * Store raw file directly without re-encoding.
     */
    protected function storeRawFile(UploadedFile $file, string $directory, string $filename, string $disk): ?string
    {
        try {
            if ($disk === 'public_uploads' || $disk === 'public_path') {
                $fullDir = public_path($directory);
                File::ensureDirectoryExists($fullDir);
                $file->move($fullDir, $filename);

                return trim($directory, '/\\') . '/' . $filename;
            } else {
                $storagePath = trim($directory, '/\\');
                $stored = $file->storeAs($storagePath, $filename, $disk);

                return $stored ?: null;
            }
        } catch (\Throwable $e) {
            Log::error("Failed to store raw file [{$filename}]: {$e->getMessage()}");

            return null;
        }
    }
}
