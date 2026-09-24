<?php

namespace App\Http\Controllers;

use App\Services\ContentModeration\ContentModerationService;
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
     * Compress and store an uploaded image or file with authoritative server-side moderation.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory Target relative directory (e.g., 'uploads/avatars', 'branding')
     * @param string $type Optimization profile ('avatar', 'logo', 'cover', 'post', 'story', 'marketplace', 'review', 'qr', 'document', 'general')
     * @param string|null $customFilename Optional custom filename
     * @param string $disk 'public_uploads' (default, uses public_path()) or 'public' (uses Storage::disk('public'))
     * @param string|null $moderationContext Authoritative server context (e.g. 'POST_IMAGE', 'PROFILE_PHOTO')
     * @return string|null Stored relative path, or null on failure
     * @throws \Illuminate\Validation\ValidationException If content moderation fails or blocks image
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

        // Authoritative server-side content moderation before any public commit
        $quarantinePath = null;
        if ($this->shouldModerateFile($file, $extension, $mime, $directory, $type)) {
            $context = $this->resolveModerationContext($directory, $type, $moderationContext);

            /** @var ContentModerationService $moderationService */
            $moderationService = app(ContentModerationService::class);

            // Staging in quarantine + decode check + remote moderation + policy evaluation.
            // If BLOCKED or SERVICE FAILS, checkAndQuarantine deletes quarantine and throws ValidationException.
            $moderationData = $moderationService->checkAndQuarantine($file, $context, 'media');
            $quarantinePath = $moderationData['quarantine_path'] ?? null;
        }

        $sourceFileOrPath = $quarantinePath ?: $file;

        // Attempt compression with safe fallback to raw storage on error
        try {
            return $this->processAndSaveImage($sourceFileOrPath, $directory, $filename, $extension, $type, $disk);
        } catch (\Throwable $e) {
            Log::warning("Image compression failed for [{$file->getClientOriginalName()}]: {$e->getMessage()}. Falling back to raw file save.");

            if ($quarantinePath && File::exists($quarantinePath)) {
                return $this->storeRawFileFromPath($quarantinePath, $directory, $filename, $disk);
            }

            return $this->storeRawFile($file, $directory, $filename, $disk);
        } finally {
            if ($quarantinePath && File::exists($quarantinePath)) {
                @unlink($quarantinePath);
            }
        }
    }

    /**
     * Determine whether an uploaded file should undergo server-side content moderation.
     */
    protected function shouldModerateFile(
        UploadedFile $file,
        string $extension,
        string $mime,
        string $directory,
        string $type
    ): bool {
        // Exempt if moderation is disabled
        if (! config('content_moderation.enabled', true)) {
            return false;
        }

        // Exempt test compression directory
        if (str_contains($directory, 'test_test_compression')) {
            return false;
        }

        // Exempt non-images (PDF, DOC, audio, video)
        if (! str_starts_with($mime, 'image/') && ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return false;
        }

        // Exempt vector SVGs
        if ($extension === 'svg' || str_contains($mime, 'svg')) {
            return false;
        }

        // Exempt animated GIFs
        if ($extension === 'gif' || str_contains($mime, 'gif')) {
            try {
                $image = $this->getManager()->decodePath($file->getRealPath());
                if ($image->isAnimated()) {
                    return false;
                }
            } catch (\Throwable) {
                return false;
            }
        }

        // Exempt Admin trusted branding, deposit QR, and business KYC verification docs
        if (
            $type === 'qr'
            || $type === 'favicon'
            || $type === 'verification'
            || $type === 'document'
            || str_contains($directory, 'branding')
            || str_contains($directory, 'deposits')
            || str_contains($directory, 'verifications')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Resolve authoritative server-side context for policy evaluation.
     */
    protected function resolveModerationContext(string $directory, string $type, ?string $explicitContext = null): string
    {
        if (! empty($explicitContext)) {
            return strtoupper(trim($explicitContext));
        }

        return match (true) {
            str_contains($directory, 'profile') || $type === 'avatar' => 'PROFILE_PHOTO',
            str_contains($directory, 'cover') || $type === 'cover' => 'PROFILE_COVER',
            str_contains($directory, 'stories') || $type === 'story' => 'STORY_IMAGE',
            str_contains($directory, 'marketplace') || $type === 'marketplace' => 'MARKETPLACE_IMAGE',
            str_contains($directory, 'messages') => 'MESSAGE_IMAGE',
            str_contains($directory, 'reviews') || $type === 'review' => 'REVIEW_IMAGE',
            str_contains($directory, 'communities') => 'COMMUNITY_IMAGE',
            str_contains($directory, 'business_pages') => 'BUSINESS_IMAGE',
            str_contains($directory, 'groups') => 'COMMUNITY_IMAGE',
            str_contains($directory, 'events') => 'POST_IMAGE',
            default => 'POST_IMAGE',
        };
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
     * @param UploadedFile|string $fileOrPath UploadedFile instance or absolute path to quarantined file
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

    /**
     * Store raw file from a quarantined file path.
     */
    protected function storeRawFileFromPath(string $sourcePath, string $directory, string $filename, string $disk): ?string
    {
        try {
            if ($disk === 'public_uploads' || $disk === 'public_path') {
                $fullDir = public_path($directory);
                File::ensureDirectoryExists($fullDir);
                $destination = $fullDir . DIRECTORY_SEPARATOR . $filename;
                File::copy($sourcePath, $destination);

                return trim($directory, '/\\') . '/' . $filename;
            } else {
                $storagePath = trim($directory, '/\\') . '/' . $filename;
                $content = File::get($sourcePath);
                Storage::disk($disk)->put($storagePath, $content);

                return $storagePath;
            }
        } catch (\Throwable $e) {
            Log::error("Failed to store raw file from quarantine [{$filename}]: {$e->getMessage()}");

            return null;
        }
    }
}
