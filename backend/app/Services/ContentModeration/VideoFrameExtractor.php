<?php

namespace App\Services\ContentModeration;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class VideoFrameExtractor
{
    protected ?string $ffmpegBinary = null;
    protected ?string $ffprobeBinary = null;

    public function __construct()
    {
        $this->ffmpegBinary = $this->resolveBinary('ffmpeg');
        $this->ffprobeBinary = $this->resolveBinary('ffprobe');
    }

    /**
     * Inspect and validate video container, streams, and metadata via ffprobe.
     *
     * @param string $videoPath Absolute path to quarantined video
     * @return array Metadata array with keys: success, duration, width, height, codec, etc.
     */
    public function probe(string $videoPath): array
    {
        if (! File::exists($videoPath) || File::size($videoPath) === 0) {
            return [
                'success' => false,
                'error' => 'Video file is empty or does not exist.',
                'error_code' => 'EMPTY_OR_MISSING_VIDEO',
            ];
        }

        if (! $this->ffprobeBinary) {
            Log::error('[VideoFrameExtractor] ffprobe binary could not be resolved on system.');

            return [
                'success' => false,
                'error' => 'Video inspection utility is unavailable on the server.',
                'error_code' => 'FFPROBE_UNAVAILABLE',
            ];
        }

        $command = [
            $this->ffprobeBinary,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $videoPath,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                return [
                    'success' => false,
                    'error' => 'Video container is unreadable or corrupted.',
                    'error_code' => 'CORRUPT_CONTAINER',
                ];
            }

            $data = json_decode($process->getOutput(), true);
            if (! is_array($data) || empty($data['streams'])) {
                return [
                    'success' => false,
                    'error' => 'Video does not contain any valid media streams.',
                    'error_code' => 'NO_STREAMS_FOUND',
                ];
            }

            // Find primary video stream
            $videoStream = null;
            foreach ($data['streams'] as $stream) {
                if (($stream['codec_type'] ?? '') === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }

            if (! $videoStream) {
                return [
                    'success' => false,
                    'error' => 'The uploaded file does not contain a valid video stream.',
                    'error_code' => 'NO_VIDEO_STREAM',
                ];
            }

            // Extract duration from stream or container format
            $duration = (float) ($videoStream['duration'] ?? ($data['format']['duration'] ?? 0));
            $width = (int) ($videoStream['width'] ?? 0);
            $height = (int) ($videoStream['height'] ?? 0);
            $codec = (string) ($videoStream['codec_name'] ?? 'unknown');

            if ($duration <= 0.0 || $width <= 0 || $height <= 0) {
                return [
                    'success' => false,
                    'error' => 'Video stream has invalid dimensions or zero duration.',
                    'error_code' => 'INVALID_STREAM_METRICS',
                ];
            }

            $maxDuration = (float) config('content_moderation.video.max_duration_seconds', 300);
            if ($duration > $maxDuration) {
                return [
                    'success' => false,
                    'error' => "Video duration exceeds maximum allowed limit of {$maxDuration} seconds.",
                    'error_code' => 'MAX_DURATION_EXCEEDED',
                ];
            }

            return [
                'success' => true,
                'duration' => $duration,
                'width' => $width,
                'height' => $height,
                'codec' => $codec,
                'bitrate' => (int) ($videoStream['bit_rate'] ?? ($data['format']['bit_rate'] ?? 0)),
                'size_bytes' => File::size($videoPath),
            ];
        } catch (\Throwable $e) {
            Log::error('[VideoFrameExtractor] Probe exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to inspect video metadata: ' . $e->getMessage(),
                'error_code' => 'PROBE_EXCEPTION',
            ];
        }
    }

    /**
     * Compute representative sample timestamps across video duration.
     *
     * @param float $duration Video duration in seconds
     * @return array<int, float> Timestamps in seconds
     */
    public function calculateSampleTimestamps(float $duration): array
    {
        if ($duration <= 0.0) {
            return [];
        }

        $samplingConfig = config('content_moderation.video.sampling', []);
        $shortThreshold = (float) ($samplingConfig['short_video_threshold'] ?? 5.0);
        $longThreshold = (float) ($samplingConfig['long_video_threshold'] ?? 30.0);

        if ($duration <= $shortThreshold) {
            $count = (int) ($samplingConfig['short_frames'] ?? 6);
        } elseif ($duration <= $longThreshold) {
            $count = (int) ($samplingConfig['normal_frames'] ?? 8);
        } else {
            $count = (int) ($samplingConfig['long_frames'] ?? 12);
        }

        $maxCap = (int) ($samplingConfig['max_frames_cap'] ?? 12);
        $count = min(max($count, 3), $maxCap);

        // For ultra-short videos (<= 1.0s), evenly slice
        if ($duration <= 1.0) {
            $timestamps = [];
            $step = $duration / ($count + 1);
            for ($i = 1; $i <= $count; $i++) {
                $timestamps[] = round($step * $i, 2);
            }
            return array_values(array_unique($timestamps));
        }

        $startMargin = (float) ($samplingConfig['start_margin_pct'] ?? 0.05);
        $endMargin = (float) ($samplingConfig['end_margin_pct'] ?? 0.95);

        $startSec = $duration * max(0.01, min($startMargin, 0.40));
        $endSec = $duration * min(0.99, max($endMargin, 0.60));
        $span = $endSec - $startSec;
        $step = $span / ($count - 1);

        $timestamps = [];
        for ($i = 0; $i < $count; $i++) {
            $ts = round($startSec + ($i * $step), 2);
            $timestamps[] = min($ts, round($duration - 0.05, 2));
        }

        return array_values(array_unique($timestamps));
    }

    /**
     * Extract representative frames from video at sampled timestamps into a private directory.
     *
     * @param string $videoPath Path to video
     * @param string $outputDir Isolated private directory for extracted frames
     * @param array|null $timestamps Optional timestamps array; if null, will probe and compute
     * @return array Array of extracted frame details [['index' => 0, 'timestamp' => 0.5, 'path' => ...], ...]
     */
    public function extractFrames(string $videoPath, string $outputDir, ?array $timestamps = null): array
    {
        if (! $this->ffmpegBinary) {
            Log::error('[VideoFrameExtractor] ffmpeg binary could not be resolved on system.');
            return [];
        }

        File::ensureDirectoryExists($outputDir);

        if ($timestamps === null) {
            $probe = $this->probe($videoPath);
            if (! $probe['success']) {
                return [];
            }
            $timestamps = $this->calculateSampleTimestamps($probe['duration']);
        }

        $extracted = [];

        foreach ($timestamps as $index => $timestamp) {
            $frameFilename = sprintf('frame_%03d_%s.jpg', $index + 1, Str::random(6));
            $framePath = $outputDir . DIRECTORY_SEPARATOR . $frameFilename;

            // -ss before -i for fast seek, -vframes 1 to extract single frame, -q:v 2 for high JPEG quality
            $cmd = [
                $this->ffmpegBinary,
                '-y',
                '-ss', (string) $timestamp,
                '-i', $videoPath,
                '-vframes', '1',
                '-q:v', '2',
                $framePath,
            ];

            try {
                $process = new Process($cmd);
                $process->setTimeout(15);
                $process->run();

                if ($process->isSuccessful() && File::exists($framePath) && File::size($framePath) > 0) {
                    $extracted[] = [
                        'index' => $index,
                        'timestamp' => $timestamp,
                        'path' => $framePath,
                        'sha256' => hash_file('sha256', $framePath),
                    ];
                } else {
                    Log::warning("[VideoFrameExtractor] Failed to extract frame at {$timestamp}s", [
                        'error' => $process->getErrorOutput(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("[VideoFrameExtractor] Exception extracting frame at {$timestamp}s: " . $e->getMessage());
            }
        }

        return $extracted;
    }

    /**
     * Delete an isolated temporary directory and all extracted frame files inside it.
     *
     * @param string $directory Path to quarantine frame directory
     */
    public function cleanupDirectory(string $directory): void
    {
        try {
            if (File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        } catch (\Throwable $e) {
            Log::warning('[VideoFrameExtractor] Failed to delete temporary directory: ' . $directory, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve binary path from ENV, local project bin, or system PATH.
     */
    protected function resolveBinary(string $binaryName): ?string
    {
        // 1. Environment variable (e.g. FFMPEG_PATH, FFPROBE_PATH)
        $envKey = strtoupper($binaryName) . '_PATH';
        $envPath = env($envKey);
        if (! empty($envPath) && File::exists($envPath)) {
            return $envPath;
        }

        // 2. Project local bin
        $localExe = base_path("bin/{$binaryName}.exe");
        if (File::exists($localExe)) {
            return $localExe;
        }

        $localUnix = base_path("bin/{$binaryName}");
        if (File::exists($localUnix)) {
            return $localUnix;
        }

        // 3. System PATH check
        try {
            $process = new Process([$binaryName, '-version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $binaryName;
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
