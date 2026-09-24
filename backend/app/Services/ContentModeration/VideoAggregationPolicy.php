<?php

namespace App\Services\ContentModeration;

use Illuminate\Support\Facades\Log;

class VideoAggregationPolicy
{
    /**
     * Evaluate an array of frame moderation results against the video policy for a context.
     *
     * @param array $frameResults Array of ModerationResult instances or arrays
     * @param string $context Target video context (e.g., 'POST_VIDEO', 'STORY_VIDEO')
     * @param array $videoMetadata Metadata from ffprobe
     * @param string $videoHash SHA-256 hash of the video
     * @param int $durationMs Elapsed processing time
     * @return VideoModerationResult
     */
    public function evaluate(
        array $frameResults,
        string $context,
        array $videoMetadata = [],
        string $videoHash = '',
        int $durationMs = 0
    ): VideoModerationResult {
        $totalFrames = count($frameResults);

        if ($totalFrames === 0) {
            return VideoModerationResult::failClosed(
                errorCode: 'ZERO_FRAMES_EXTRACTED',
                userMessage: 'Could not verify video safety: no usable frames were extracted.',
                videoHash: $videoHash,
                videoMetadata: $videoMetadata,
                durationMs: $durationMs,
                reasonCode: 'ZERO_FRAMES'
            );
        }

        // Context thresholds
        $thresholds = $this->getContextThresholds($context);
        $pornThreshold = (float) ($thresholds['Porn'] ?? 0.60);
        $hentaiThreshold = (float) ($thresholds['Hentai'] ?? 0.60);
        $sexyThreshold = (float) ($thresholds['Sexy'] ?? 0.80);

        $maxFailedRatio = (float) config('content_moderation.video.aggregation.max_failed_frame_ratio', 0.25);
        $repeatedSexyLimit = (int) config('content_moderation.video.aggregation.repeated_sexy_threshold_count', 2);

        $peakProbabilities = [
            'Porn' => 0.0,
            'Hentai' => 0.0,
            'Sexy' => 0.0,
            'Neutral' => 0.0,
            'Drawing' => 0.0,
        ];

        $successfulFrames = 0;
        $failedFrames = 0;
        $unsafeFrameCount = 0;
        $sexyExceedCount = 0;
        $frameSummaries = [];

        $highestConfidence = 0.0;
        $dominantClassOverall = 'Neutral';

        $violatingFrame = null;
        $primaryViolationReason = null;

        foreach ($frameResults as $index => $frame) {
            $isResultObj = $frame instanceof ModerationResult;
            $success = $isResultObj ? $frame->success : (bool) ($frame['success'] ?? true);

            if (! $success) {
                $failedFrames++;
                $frameSummaries[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $isResultObj ? $frame->errorCode : ($frame['error'] ?? 'Classification failed'),
                ];
                continue;
            }

            $successfulFrames++;

            // Extract probabilities
            $probabilities = $this->extractProbabilities($frame);
            $dominantClass = $isResultObj ? $frame->dominantClass : ($frame['dominantClass'] ?? 'Neutral');
            $confidence = $isResultObj ? $frame->confidence : (float) ($frame['confidence'] ?? 0.0);
            $timestamp = $isResultObj ? null : ($frame['timestamp'] ?? ($frameSummaries[$index]['timestamp'] ?? null));

            // Update peak probabilities
            foreach ($probabilities as $class => $prob) {
                if (isset($peakProbabilities[$class]) && $prob > $peakProbabilities[$class]) {
                    $peakProbabilities[$class] = $prob;
                }
            }

            // Track dominant overall
            if ($confidence > $highestConfidence) {
                $highestConfidence = $confidence;
                $dominantClassOverall = $dominantClass;
            }

            // Check frame thresholds
            $framePorn = $probabilities['Porn'] ?? 0.0;
            $frameHentai = $probabilities['Hentai'] ?? 0.0;
            $frameSexy = $probabilities['Sexy'] ?? 0.0;

            $isFrameUnsafe = false;
            $frameViolation = null;

            if ($framePorn >= $pornThreshold) {
                $isFrameUnsafe = true;
                $frameViolation = 'PORN_THRESHOLD_EXCEEDED';
                if (! $primaryViolationReason) {
                    $primaryViolationReason = 'FRAME_PORN_EXCEEDED';
                    $violatingFrame = ['index' => $index, 'class' => 'Porn', 'probability' => $framePorn, 'threshold' => $pornThreshold];
                }
            } elseif ($frameHentai >= $hentaiThreshold) {
                $isFrameUnsafe = true;
                $frameViolation = 'HENTAI_THRESHOLD_EXCEEDED';
                if (! $primaryViolationReason) {
                    $primaryViolationReason = 'FRAME_HENTAI_EXCEEDED';
                    $violatingFrame = ['index' => $index, 'class' => 'Hentai', 'probability' => $frameHentai, 'threshold' => $hentaiThreshold];
                }
            }

            if ($frameSexy >= $sexyThreshold) {
                $sexyExceedCount++;
            }

            if ($isFrameUnsafe) {
                $unsafeFrameCount++;
            }

            $frameSummaries[] = [
                'index' => $index,
                'timestamp' => $timestamp,
                'success' => true,
                'dominant_class' => $dominantClass,
                'confidence' => $confidence,
                'probabilities' => $probabilities,
                'is_unsafe' => $isFrameUnsafe,
                'violation' => $frameViolation,
            ];
        }

        // Coverage Check: Fail closed if too many frames failed classification
        $failureRatio = $totalFrames > 0 ? ($failedFrames / $totalFrames) : 1.0;
        if ($failureRatio > $maxFailedRatio || $successfulFrames === 0) {
            Log::warning('[VideoModeration] Insufficient frame classification coverage', [
                'total' => $totalFrames,
                'failed' => $failedFrames,
                'successful' => $successfulFrames,
                'ratio' => round($failureRatio, 2),
                'context' => $context,
            ]);

            return VideoModerationResult::failClosed(
                errorCode: 'INSUFFICIENT_COVERAGE',
                userMessage: 'Could not verify video safety due to a service timeout or classification error. Please try again.',
                videoHash: $videoHash,
                videoMetadata: $videoMetadata,
                durationMs: $durationMs,
                reasonCode: 'INSUFFICIENT_COVERAGE'
            );
        }

        // Single strong unsafe frame (Porn or Hentai)
        if ($primaryViolationReason) {
            $userMessage = $violatingFrame['class'] === 'Hentai'
                ? 'This video contains prohibited explicit animated/hentai content.'
                : 'This video contains prohibited adult or sexually explicit content.';

            return VideoModerationResult::block(
                peakProbabilities: $peakProbabilities,
                dominantClassOverall: $violatingFrame['class'],
                peakConfidence: $violatingFrame['probability'],
                totalFrames: $totalFrames,
                successfulFrames: $successfulFrames,
                unsafeFrameCount: $unsafeFrameCount,
                frameSummaries: $frameSummaries,
                videoMetadata: $videoMetadata,
                videoHash: $videoHash,
                durationMs: $durationMs,
                reasonCode: $primaryViolationReason,
                userMessage: $userMessage
            );
        }

        // Repeated Sexy threshold exceedance
        if ($sexyExceedCount >= $repeatedSexyLimit) {
            return VideoModerationResult::block(
                peakProbabilities: $peakProbabilities,
                dominantClassOverall: 'Sexy',
                peakConfidence: $peakProbabilities['Sexy'] ?? 0.0,
                totalFrames: $totalFrames,
                successfulFrames: $successfulFrames,
                unsafeFrameCount: $sexyExceedCount,
                frameSummaries: $frameSummaries,
                videoMetadata: $videoMetadata,
                videoHash: $videoHash,
                durationMs: $durationMs,
                reasonCode: 'REPEATED_SEXY_EXCEEDED',
                userMessage: 'This video contains excessive sexually suggestive imagery.'
            );
        }

        // All frames allowed
        return VideoModerationResult::allow(
            peakProbabilities: $peakProbabilities,
            dominantClassOverall: $dominantClassOverall,
            peakConfidence: $highestConfidence,
            totalFrames: $totalFrames,
            successfulFrames: $successfulFrames,
            frameSummaries: $frameSummaries,
            videoMetadata: $videoMetadata,
            videoHash: $videoHash,
            durationMs: $durationMs,
            reasonCode: 'SAFE'
        );
    }

    /**
     * Helper to extract standardized probability array from a result.
     */
    protected function extractProbabilities(mixed $frame): array
    {
        $probs = [
            'Porn' => 0.0,
            'Hentai' => 0.0,
            'Sexy' => 0.0,
            'Neutral' => 0.0,
            'Drawing' => 0.0,
        ];

        if ($frame instanceof ModerationResult) {
            foreach ($frame->predictions as $pred) {
                $name = ucfirst(strtolower($pred['className'] ?? ''));
                if (isset($probs[$name])) {
                    $probs[$name] = (float) ($pred['probability'] ?? 0.0);
                }
            }
        } elseif (is_array($frame)) {
            $predictions = $frame['predictions'] ?? [];
            if (! empty($predictions)) {
                foreach ($predictions as $pred) {
                    $name = ucfirst(strtolower($pred['className'] ?? ''));
                    if (isset($probs[$name])) {
                        $probs[$name] = (float) ($pred['probability'] ?? 0.0);
                    }
                }
            } elseif (isset($frame['probabilities'])) {
                foreach ($frame['probabilities'] as $name => $val) {
                    $name = ucfirst(strtolower($name));
                    if (isset($probs[$name])) {
                        $probs[$name] = (float) $val;
                    }
                }
            }
        }

        return $probs;
    }

    /**
     * Resolve thresholds for a given context from configuration.
     */
    public function getContextThresholds(string $context): array
    {
        $contexts = config('content_moderation.video.contexts', []);

        if (isset($contexts[$context])) {
            return $contexts[$context];
        }

        // Fallback to POST_VIDEO or image contexts
        return $contexts['POST_VIDEO'] ?? [
            'Porn' => 0.60,
            'Hentai' => 0.60,
            'Sexy' => 0.80,
        ];
    }
}
