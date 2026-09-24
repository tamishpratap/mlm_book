<?php

namespace App\Services\ContentModeration;

use App\Contracts\ContentModerationProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VideoModerationService
{
    protected QuarantineFileService $quarantineService;
    protected VideoFrameExtractor $frameExtractor;
    protected ContentModerationProvider $provider;
    protected VideoAggregationPolicy $aggregationPolicy;

    public function __construct(
        ?QuarantineFileService $quarantineService = null,
        ?VideoFrameExtractor $frameExtractor = null,
        ?ContentModerationProvider $provider = null,
        ?VideoAggregationPolicy $aggregationPolicy = null
    ) {
        $this->quarantineService = $quarantineService ?: new QuarantineFileService();
        $this->frameExtractor = $frameExtractor ?: new VideoFrameExtractor();
        $this->provider = $provider ?: app(RemoteContentModerationProvider::class);
        $this->aggregationPolicy = $aggregationPolicy ?: new VideoAggregationPolicy();
    }

    /**
     * Determine whether server-side video moderation is active.
     */
    public function isEnabled(): bool
    {
        return (bool) config('content_moderation.enabled', true)
            && (bool) config('content_moderation.video.enabled', true);
    }

    /**
     * Quarantine, probe, extract keyframes, moderate frames, and enforce video policy.
     *
     * @param UploadedFile $file The uploaded video file
     * @param string $context Server-controlled video moderation context
     * @param string $errorKey Validation message error key (default 'media')
     * @return array{result: VideoModerationResult, quarantine_path: string, sha256: string, metadata: array}
     * @throws ValidationException
     */
    public function checkAndQuarantineVideo(
        UploadedFile $file,
        string $context,
        string $errorKey = 'media'
    ): array {
        $startTime = hrtime(true);

        // Stage into quarantine
        try {
            $quarantineInfo = $this->quarantineService->quarantine($file);
        } catch (\Throwable $e) {
            Log::error('[VideoModerationService] Failed to quarantine uploaded video: ' . $e->getMessage());
            throw ValidationException::withMessages([
                $errorKey => 'Could not process uploaded video. Please try again.',
            ]);
        }

        $videoPath = $quarantineInfo['path'];
        $videoSha = $quarantineInfo['sha256'];

        if (! $this->isEnabled()) {
            $allowResult = VideoModerationResult::allow(
                peakProbabilities: [],
                dominantClassOverall: 'Neutral',
                peakConfidence: 0.0,
                totalFrames: 0,
                successfulFrames: 0,
                frameSummaries: [],
                videoMetadata: [],
                videoHash: $videoSha,
                durationMs: 0,
                reasonCode: 'MODERATION_DISABLED'
            );

            return [
                'result' => $allowResult,
                'quarantine_path' => $videoPath,
                'sha256' => $videoSha,
                'metadata' => [],
            ];
        }

        $quarantineBase = config('content_moderation.video.quarantine_path', storage_path('app/quarantine/video-moderation'));
        $frameDir = $quarantineBase . DIRECTORY_SEPARATOR . Str::uuid();
        $isSuccess = false;

        try {
            // 1. Validate video container & streams via ffprobe
            $probe = $this->frameExtractor->probe($videoPath);
            if (! $probe['success']) {
                $this->quarantineService->cleanup($videoPath);

                Log::info('[VideoModerationService] Video rejected by probe', [
                    'error_code' => $probe['error_code'] ?? 'UNKNOWN',
                    'context' => $context,
                ]);

                throw ValidationException::withMessages([
                    $errorKey => $probe['error'] ?? 'The uploaded file is not a valid or supported video.',
                ]);
            }

            // 2. Sample timestamps
            $timestamps = $this->frameExtractor->calculateSampleTimestamps($probe['duration']);

            // 3. Extract representative frames
            $frames = $this->frameExtractor->extractFrames($videoPath, $frameDir, $timestamps);
            if (empty($frames)) {
                $this->quarantineService->cleanup($videoPath);

                Log::warning('[VideoModerationService] Zero frames extracted from video', [
                    'duration' => $probe['duration'],
                    'context' => $context,
                ]);

                throw ValidationException::withMessages([
                    $errorKey => 'Failed to process video keyframes for safety inspection. Please try again.',
                ]);
            }

            // 4. Moderate each frame through existing provider (Node microservice)
            $frameResults = [];
            foreach ($frames as $frame) {
                try {
                    $frameResult = $this->provider->moderateImage(
                        absolutePath: $frame['path'],
                        mimeType: 'image/jpeg',
                        sha256: $frame['sha256'],
                        context: $context
                    );
                    $frameResults[] = $frameResult;
                } catch (\Throwable $e) {
                    Log::warning('[VideoModerationService] Frame moderation exception: ' . $e->getMessage());
                    $frameResults[] = ModerationResult::failClosed(
                        errorCode: 'FRAME_PROVIDER_ERROR',
                        userMessage: 'Frame classification failed',
                        fileHash: $frame['sha256']
                    );
                }
            }

            $elapsedMs = (int) round((hrtime(true) - $startTime) / 1e6);

            // 5. Aggregate frame decisions
            $result = $this->aggregationPolicy->evaluate(
                frameResults: $frameResults,
                context: $context,
                videoMetadata: $probe,
                videoHash: $videoSha,
                durationMs: $elapsedMs
            );

            // 6. Enforce authoritative decision
            if ($result->isBlocked()) {
                $this->quarantineService->cleanup($videoPath);

                $userMessage = $result->userMessage ?: 'This video cannot be uploaded because it violates our community safety guidelines.';

                Log::info('[VideoModerationService] Video blocked by server policy', [
                    'context' => $context,
                    'dominant_class' => $result->dominantClassOverall,
                    'confidence' => $result->peakConfidence,
                    'reason' => $result->reasonCode,
                    'duration_ms' => $result->durationMs,
                ]);

                throw ValidationException::withMessages([
                    $errorKey => $userMessage,
                ]);
            }

            $isSuccess = true;

            return [
                'result' => $result,
                'quarantine_path' => $videoPath,
                'sha256' => $videoSha,
                'metadata' => $probe,
            ];
        } finally {
            // Guaranteed cleanup of quarantine file if not successfully allowed and handed off
            if (! $isSuccess && isset($videoPath)) {
                $this->quarantineService->cleanup($videoPath);
            }

            // Guaranteed cleanup of temporary frame directory
            $this->frameExtractor->cleanupDirectory($frameDir);
        }
    }
}
