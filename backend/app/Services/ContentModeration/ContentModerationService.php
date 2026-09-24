<?php

namespace App\Services\ContentModeration;

use App\Contracts\ContentModerationProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContentModerationService
{
    // Image Contexts
    public const CONTEXT_PROFILE_PHOTO = 'PROFILE_PHOTO';
    public const CONTEXT_PROFILE_COVER = 'PROFILE_COVER';
    public const CONTEXT_POST_IMAGE = 'POST_IMAGE';
    public const CONTEXT_STORY_IMAGE = 'STORY_IMAGE';
    public const CONTEXT_COMMUNITY_IMAGE = 'COMMUNITY_IMAGE';
    public const CONTEXT_BUSINESS_IMAGE = 'BUSINESS_IMAGE';
    public const CONTEXT_REVIEW_IMAGE = 'REVIEW_IMAGE';
    public const CONTEXT_MARKETPLACE_IMAGE = 'MARKETPLACE_IMAGE';
    public const CONTEXT_MESSAGE_IMAGE = 'MESSAGE_IMAGE';

    // Video Contexts
    public const CONTEXT_POST_VIDEO = 'POST_VIDEO';
    public const CONTEXT_STORY_VIDEO = 'STORY_VIDEO';
    public const CONTEXT_COMMUNITY_VIDEO = 'COMMUNITY_VIDEO';
    public const CONTEXT_BUSINESS_VIDEO = 'BUSINESS_VIDEO';
    public const CONTEXT_MARKETPLACE_VIDEO = 'MARKETPLACE_VIDEO';

    protected QuarantineFileService $quarantineService;
    protected ContentModerationProvider $provider;

    public function __construct(
        ?QuarantineFileService $quarantineService = null,
        ?ContentModerationProvider $provider = null
    ) {
        $this->quarantineService = $quarantineService ?: new QuarantineFileService();
        $this->provider = $provider ?: $this->resolveProvider();
    }

    /**
     * Resolve configured moderation provider.
     */
    protected function resolveProvider(): ContentModerationProvider
    {
        $providerName = config('content_moderation.provider', 'remote');

        return match ($providerName) {
            'remote' => app(RemoteContentModerationProvider::class),
            default => app(RemoteContentModerationProvider::class),
        };
    }

    /**
     * Determine whether server-side moderation is active.
     */
    public function isEnabled(): bool
    {
        return (bool) config('content_moderation.enabled', true);
    }

    /**
     * Quarantine, validate, and moderate an uploaded image.
     *
     * If the image is BLOCKED or service fails, the quarantine file is deleted immediately
     * and a ValidationException is thrown (resulting in a safe 422 HTTP response).
     *
     * If ALLOWED, returns quarantine details for downstream compression and storage.
     *
     * @param UploadedFile $file
     * @param string $context Internal authoritative context (e.g., 'POST_IMAGE')
     * @param string $errorKey Validation field name for error reporting
     * @return array{result: ModerationResult, quarantine_path: string, sha256: string, mime: string}
     * @throws ValidationException
     */
    public function checkAndQuarantine(
        UploadedFile $file,
        string $context,
        string $errorKey = 'media'
    ): array {
        // If disabled, quarantine and return artificial allow
        if (! $this->isEnabled()) {
            $quarantineInfo = $this->quarantineService->quarantine($file);
            $allowResult = ModerationResult::allow([], $quarantineInfo['sha256'], 0, null, 'disabled');

            return [
                'result' => $allowResult,
                'quarantine_path' => $quarantineInfo['path'],
                'sha256' => $quarantineInfo['sha256'],
                'mime' => $quarantineInfo['mime'],
            ];
        }

        // 1. Stage in secure temporary quarantine
        try {
            $quarantineInfo = $this->quarantineService->quarantine($file);
        } catch (\Throwable $e) {
            Log::error('[ContentModeration] Failed to stage file in quarantine', [
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            throw ValidationException::withMessages([
                $errorKey => 'Could not process uploaded file. Please try again.',
            ]);
        }

        $quarantinePath = $quarantineInfo['path'];
        $sha256 = $quarantineInfo['sha256'];
        $mime = $quarantineInfo['mime'];

        // 2. Validate image decodability
        if (! $this->quarantineService->isDecodableImage($quarantinePath)) {
            $this->quarantineService->cleanup($quarantinePath);

            throw ValidationException::withMessages([
                $errorKey => 'The selected file is not a valid image or is corrupted.',
            ]);
        }

        // 3. Perform server moderation via provider
        $result = $this->provider->moderateImage(
            absolutePath: $quarantinePath,
            mimeType: $mime,
            sha256: $sha256,
            context: $context
        );

        // 4. Enforce authoritative decision
        if ($result->isBlocked()) {
            // Immediately purge quarantine file - 0 bytes reach public storage
            $this->quarantineService->cleanup($quarantinePath);

            $userMessage = $result->userMessage ?: 'This image cannot be uploaded because it may contain restricted content.';

            Log::info('[ContentModeration] Upload blocked by server policy', [
                'context' => $context,
                'dominant_class' => $result->dominantClass,
                'confidence' => $result->confidence,
                'reason' => $result->reasonCode,
                'duration_ms' => $result->durationMs,
            ]);

            throw ValidationException::withMessages([
                $errorKey => $userMessage,
            ]);
        }

        return [
            'result' => $result,
            'quarantine_path' => $quarantinePath,
            'sha256' => $sha256,
            'mime' => $mime,
        ];
    }

    /**
     * Standalone check for an image file, automatically cleaning up quarantine afterwards.
     *
     * @param UploadedFile $file
     * @param string $context
     * @return ModerationResult
     */
    public function checkImage(UploadedFile $file, string $context): ModerationResult
    {
        try {
            $data = $this->checkAndQuarantine($file, $context);
            $this->quarantineService->cleanup($data['quarantine_path']);

            return $data['result'];
        } catch (ValidationException $e) {
            $firstMsg = collect($e->errors())->flatten()->first() ?: 'Content violation';

            return ModerationResult::block([], '', 'POLICY_BLOCKED', (string) $firstMsg);
        }
    }

    /**
     * Delete a quarantine file.
     */
    public function cleanupQuarantine(?string $path): bool
    {
        return $this->quarantineService->cleanup($path);
    }
}
