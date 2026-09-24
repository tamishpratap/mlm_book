<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class CompressVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Target Eloquent model class (e.g. App\Models\Post::class).
     */
    public string $modelClass;

    /**
     * Target Eloquent model ID.
     */
    public int $modelId;

    /**
     * Staging relative file path (e.g. 'uploads/posts/videos/staging_post_1_123.mp4').
     */
    public string $stagingPath;

    /**
     * Destination directory (e.g. 'uploads/posts/videos').
     */
    public string $directory;

    /**
     * Optional custom/target filename (e.g. 'post_1_123.mp4').
     */
    public ?string $targetFilename;

    /**
     * Database attribute name on the model that stores the file path.
     */
    public string $attribute;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $modelClass,
        int $modelId,
        string $stagingPath,
        string $directory,
        ?string $targetFilename = null,
        string $attribute = 'media_path'
    ) {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->stagingPath = $stagingPath;
        $this->directory = $directory;
        $this->targetFilename = $targetFilename;
        $this->attribute = $attribute;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fullSource = public_path($this->stagingPath);

        if (! File::exists($fullSource)) {
            Log::warning("CompressVideoJob: Source video file not found at [{$fullSource}]. Skipping.");
            return;
        }

        $model = $this->modelClass::find($this->modelId);
        if (! $model) {
            Log::warning("CompressVideoJob: Target model [{$this->modelClass}] with ID [{$this->modelId}] not found.");
            return;
        }

        $ffmpegBinary = $this->resolveFfmpegBinary();
        if (! $ffmpegBinary) {
            Log::error("CompressVideoJob: FFmpeg binary could not be found. Preserving staged file [{$this->stagingPath}].");
            return;
        }

        $destinationDir = public_path($this->directory);
        File::ensureDirectoryExists($destinationDir);

        // Determine target filename (guaranteed .mp4)
        $finalFilename = $this->determineFinalFilename();
        $finalRelativePath = trim($this->directory, '/\\') . '/' . $finalFilename;
        $fullFinalPath = public_path($finalRelativePath);

        // We transcode to a temporary file first so that in-flight reads are not broken
        $tempOutputFilename = 'tmp_' . time() . '_' . Str::lower(Str::random(8)) . '.mp4';
        $tempOutputPath = $destinationDir . DIRECTORY_SEPARATOR . $tempOutputFilename;

        $command = [
            $ffmpegBinary,
            '-y',
            '-i',
            $fullSource,
            '-c:v',
            'libx264',
            '-crf',
            '24',
            '-preset',
            'medium',
            '-pix_fmt',
            'yuv420p',
            '-vf',
            "scale=w='min(1920,iw)':h='min(1080,ih)':force_original_aspect_ratio=decrease,scale=trunc(iw/2)*2:trunc(ih/2)*2",
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            '-movflags',
            '+faststart',
            $tempOutputPath,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout($this->timeout);
            $process->run();

            if ($process->isSuccessful() && File::exists($tempOutputPath) && File::size($tempOutputPath) > 0) {
                $originalSize = File::size($fullSource);
                $compressedSize = File::size($tempOutputPath);

                // Move temp compressed file to final location
                if (File::exists($fullFinalPath)) {
                    File::delete($fullFinalPath);
                }
                File::move($tempOutputPath, $fullFinalPath);

                // Update model's database reference
                $model->update([$this->attribute => $finalRelativePath]);

                // Delete original staging file if it's different from the final path
                if (realpath($fullSource) !== realpath($fullFinalPath)) {
                    File::delete($fullSource);
                }

                $savingsPct = $originalSize > 0 ? round((($originalSize - $compressedSize) / $originalSize) * 100, 1) : 0;
                Log::info("CompressVideoJob: Video successfully compressed for [{$this->modelClass} #{$this->modelId}]. Original: {$originalSize} bytes, Compressed: {$compressedSize} bytes (Saved {$savingsPct}%). Path: [{$finalRelativePath}].");
            } else {
                if (File::exists($tempOutputPath)) {
                    File::delete($tempOutputPath);
                }

                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                Log::warning("CompressVideoJob: Transcoding failed for [{$this->modelClass} #{$this->modelId}] with exit code [{$process->getExitCode()}]. Error: {$errorOutput}. Keeping original file [{$this->stagingPath}] as fallback.");
            }
        } catch (\Throwable $e) {
            if (File::exists($tempOutputPath)) {
                File::delete($tempOutputPath);
            }

            Log::error("CompressVideoJob: Exception during transcoding for [{$this->modelClass} #{$this->modelId}]: {$e->getMessage()}. Keeping original file [{$this->stagingPath}].");
        }
    }

    /**
     * Resolve the path to the ffmpeg executable.
     */
    protected function resolveFfmpegBinary(): ?string
    {
        // 1. Check environment variable
        $envPath = env('FFMPEG_PATH');
        if (! empty($envPath) && File::exists($envPath)) {
            return $envPath;
        }

        // 2. Check local project bin directory
        $localBin = base_path('bin/ffmpeg.exe');
        if (File::exists($localBin)) {
            return $localBin;
        }

        $localBinLinux = base_path('bin/ffmpeg');
        if (File::exists($localBinLinux)) {
            return $localBinLinux;
        }

        // 3. Check system PATH
        $process = new Process(['ffmpeg', '-version']);
        try {
            $process->run();
            if ($process->isSuccessful()) {
                return 'ffmpeg';
            }
        } catch (\Throwable) {
            // Not in system PATH
        }

        return null;
    }

    /**
     * Determine the final target filename ensuring .mp4 extension.
     */
    protected function determineFinalFilename(): string
    {
        if (! empty($this->targetFilename)) {
            $info = pathinfo($this->targetFilename);
            $nameWithoutExt = $info['filename'];

            // Remove any staging prefix like 'staging_' or 'raw_'
            $cleanName = preg_replace('/^(staging_|raw_)/', '', $nameWithoutExt);

            return $cleanName . '.mp4';
        }

        return sprintf(
            'video_%s_%d_%s.mp4',
            $this->modelId,
            time(),
            Str::lower(Str::random(8))
        );
    }
}
