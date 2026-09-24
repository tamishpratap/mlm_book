<?php

namespace App\Services\ContentModeration;

class VideoModerationResult
{
    /**
     * @param bool $success Whether extraction, classification, and aggregation succeeded
     * @param string $action 'ALLOW' or 'BLOCK'
     * @param string $mediaType Always 'video'
     * @param array<string, float> $peakProbabilities Peak probabilities across all frames
     * @param string $dominantClassOverall Dominant class with highest confidence
     * @param float $peakConfidence Highest probability encountered for dominant class
     * @param int $totalFrames Total frames sampled and attempted
     * @param int $successfulFrames Number of frames successfully classified
     * @param int $failedFrames Number of frames that failed classification
     * @param int $unsafeFrameCount Number of frames exceeding policy thresholds
     * @param array<int, array> $frameSummaries Per-frame details
     * @param array $videoMetadata ffprobe extracted metadata (duration, width, height, codec)
     * @param int $durationMs Overall processing duration in milliseconds
     * @param string $videoHash SHA-256 hash of the source video
     * @param string|null $errorCode Machine-readable error code
     * @param string|null $userMessage User-facing explanation
     * @param string|null $reasonCode Machine-readable policy reason code
     */
    public function __construct(
        public bool $success,
        public string $action,
        public string $mediaType = 'video',
        public array $peakProbabilities = [],
        public string $dominantClassOverall = 'Neutral',
        public float $peakConfidence = 0.0,
        public int $totalFrames = 0,
        public int $successfulFrames = 0,
        public int $failedFrames = 0,
        public int $unsafeFrameCount = 0,
        public array $frameSummaries = [],
        public array $videoMetadata = [],
        public int $durationMs = 0,
        public string $videoHash = '',
        public ?string $errorCode = null,
        public ?string $userMessage = null,
        public ?string $reasonCode = null,
    ) {
    }

    /**
     * Determine if the video is allowed by policy.
     */
    public function isAllowed(): bool
    {
        return $this->success && $this->action === 'ALLOW';
    }

    /**
     * Determine if the video is blocked by policy or failure.
     */
    public function isBlocked(): bool
    {
        return ! $this->isAllowed();
    }

    /**
     * Determine if the video check failed due to technical error.
     */
    public function isFailed(): bool
    {
        return ! $this->success;
    }

    /**
     * Factory for an allowed video result.
     */
    public static function allow(
        array $peakProbabilities,
        string $dominantClassOverall,
        float $peakConfidence,
        int $totalFrames,
        int $successfulFrames,
        array $frameSummaries,
        array $videoMetadata,
        string $videoHash,
        int $durationMs = 0,
        string $reasonCode = 'SAFE'
    ): self {
        return new self(
            success: true,
            action: 'ALLOW',
            mediaType: 'video',
            peakProbabilities: $peakProbabilities,
            dominantClassOverall: $dominantClassOverall,
            peakConfidence: $peakConfidence,
            totalFrames: $totalFrames,
            successfulFrames: $successfulFrames,
            failedFrames: 0,
            unsafeFrameCount: 0,
            frameSummaries: $frameSummaries,
            videoMetadata: $videoMetadata,
            durationMs: $durationMs,
            videoHash: $videoHash,
            errorCode: null,
            userMessage: null,
            reasonCode: $reasonCode
        );
    }

    /**
     * Factory for a policy-blocked video result.
     */
    public static function block(
        array $peakProbabilities,
        string $dominantClassOverall,
        float $peakConfidence,
        int $totalFrames,
        int $successfulFrames,
        int $unsafeFrameCount,
        array $frameSummaries,
        array $videoMetadata,
        string $videoHash,
        int $durationMs = 0,
        string $reasonCode = 'VIDEO_POLICY_VIOLATION',
        string $userMessage = 'This video cannot be uploaded because it violates our community safety guidelines.'
    ): self {
        return new self(
            success: true,
            action: 'BLOCK',
            mediaType: 'video',
            peakProbabilities: $peakProbabilities,
            dominantClassOverall: $dominantClassOverall,
            peakConfidence: $peakConfidence,
            totalFrames: $totalFrames,
            successfulFrames: $successfulFrames,
            failedFrames: $totalFrames - $successfulFrames,
            unsafeFrameCount: $unsafeFrameCount,
            frameSummaries: $frameSummaries,
            videoMetadata: $videoMetadata,
            durationMs: $durationMs,
            videoHash: $videoHash,
            errorCode: 'CONTENT_MODERATION_BLOCKED',
            userMessage: $userMessage,
            reasonCode: $reasonCode
        );
    }

    /**
     * Factory for a fail-closed error.
     */
    public static function failClosed(
        string $errorCode,
        string $userMessage,
        string $videoHash = '',
        array $videoMetadata = [],
        int $durationMs = 0,
        string $reasonCode = 'FAIL_CLOSED'
    ): self {
        return new self(
            success: false,
            action: 'BLOCK',
            mediaType: 'video',
            peakProbabilities: [],
            dominantClassOverall: 'Unknown',
            peakConfidence: 0.0,
            totalFrames: 0,
            successfulFrames: 0,
            failedFrames: 0,
            unsafeFrameCount: 0,
            frameSummaries: [],
            videoMetadata: $videoMetadata,
            durationMs: $durationMs,
            videoHash: $videoHash,
            errorCode: $errorCode,
            userMessage: $userMessage,
            reasonCode: $reasonCode
        );
    }

    /**
     * Serialize to array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'action' => $this->action,
            'media_type' => $this->mediaType,
            'peak_probabilities' => $this->peakProbabilities,
            'dominant_class' => $this->dominantClassOverall,
            'confidence' => $this->peakConfidence,
            'total_frames' => $this->totalFrames,
            'successful_frames' => $this->successfulFrames,
            'failed_frames' => $this->failedFrames,
            'unsafe_frame_count' => $this->unsafeFrameCount,
            'frame_summaries' => $this->frameSummaries,
            'video_metadata' => $this->videoMetadata,
            'duration_ms' => $this->durationMs,
            'video_hash' => $this->videoHash,
            'error_code' => $this->errorCode,
            'user_message' => $this->userMessage,
            'reason_code' => $this->reasonCode,
        ];
    }
}
