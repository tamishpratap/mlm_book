<?php

namespace App\Http\Controllers;

use App\Jobs\CompressVideoJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class VideoCompressionController extends Controller
{
    /**
     * Store an uploaded video file to a staging/initial location.
     *
     * @param UploadedFile $file The uploaded video file
     * @param string $directory Target directory relative to public_path (e.g. 'uploads/posts/videos')
     * @param string|null $customFilename Optional custom filename
     * @param string|null $context Deprecated unused parameter for backwards compatibility
     * @return string|null Relative stored path, or null on failure
     */
    public function stageAndStore(
        UploadedFile $file,
        string $directory,
        ?string $customFilename = null,
        ?string $context = null
    ): ?string {
        if (! $file->isValid()) {
            return null;
        }

        try {
            $fullDir = public_path($directory);
            File::ensureDirectoryExists($fullDir);

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'mp4');
            $filename = $this->determineStagingFilename($customFilename, $extension);

            $file->move($fullDir, $filename);

            return trim($directory, '/\\') . '/' . $filename;
        } catch (\Throwable $e) {
            Log::error("VideoCompressionController: Failed to stage video: {$e->getMessage()}");

            return null;
        }
    }


    /**
     * Dispatch the background video compression job for an existing model record.
     *
     * @param Model|string $model Model instance or class-string
     * @param int|string|null $modelId Model ID (if first argument is class-string)
     * @param string|null $stagingPath Relative path to staged video
     * @param string|null $directory Target directory
     * @param string|null $targetFilename Optional target filename
     * @param string $attribute Database attribute holding the media path
     */
    public function dispatchCompression(
        Model|string $model,
        int|string|null $modelId = null,
        ?string $stagingPath = null,
        ?string $directory = null,
        ?string $targetFilename = null,
        string $attribute = 'media_path'
    ): void {
        if ($model instanceof Model) {
            $modelClass = get_class($model);
            $actualModelId = (int) $model->getKey();
            $actualStagingPath = $stagingPath ?: (string) $model->{$attribute};
            $actualDirectory = $directory ?: dirname($actualStagingPath);
            $actualTargetFilename = $targetFilename ?: basename($actualStagingPath);
        } else {
            $modelClass = (string) $model;
            $actualModelId = (int) $modelId;
            $actualStagingPath = (string) $stagingPath;
            $actualDirectory = (string) $directory;
            $actualTargetFilename = $targetFilename;
        }

        CompressVideoJob::dispatch(
            $modelClass,
            $actualModelId,
            $actualStagingPath,
            $actualDirectory,
            $actualTargetFilename,
            $attribute
        );
    }

    /**
     * Synchronously compress a video file directly to destination.
     *
     * @param string $sourceRelativePath Path relative to public_path
     * @param string $directory Target directory relative to public_path
     * @param string|null $targetFilename Desired target filename
     * @return string|null Relative path to compressed file, or null on failure
     */
    public function compressSync(string $sourceRelativePath, string $directory, ?string $targetFilename = null): ?string
    {
        $fullSource = public_path($sourceRelativePath);
        if (! File::exists($fullSource)) {
            return null;
        }

        $ffmpegBinary = $this->resolveFfmpegBinary();
        if (! $ffmpegBinary) {
            return null;
        }

        $fullDir = public_path($directory);
        File::ensureDirectoryExists($fullDir);

        $finalFilename = $targetFilename
            ? (pathinfo($targetFilename, PATHINFO_FILENAME) . '.mp4')
            : ('compressed_' . time() . '_' . Str::lower(Str::random(8)) . '.mp4');

        $fullDestination = $fullDir . DIRECTORY_SEPARATOR . $finalFilename;
        $tempOutput = $fullDir . DIRECTORY_SEPARATOR . 'tmp_sync_' . time() . '_' . Str::lower(Str::random(8)) . '.mp4';

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
            $tempOutput,
        ];

        try {
            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();

            if ($process->isSuccessful() && File::exists($tempOutput) && File::size($tempOutput) > 0) {
                if (File::exists($fullDestination)) {
                    File::delete($fullDestination);
                }
                File::move($tempOutput, $fullDestination);

                return trim($directory, '/\\') . '/' . $finalFilename;
            }

            if (File::exists($tempOutput)) {
                File::delete($tempOutput);
            }

            return null;
        } catch (\Throwable $e) {
            if (File::exists($tempOutput)) {
                File::delete($tempOutput);
            }
            Log::error("VideoCompressionController::compressSync error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Resolve the path to the ffmpeg executable.
     */
    public function resolveFfmpegBinary(): ?string
    {
        $envPath = env('FFMPEG_PATH');
        if (! empty($envPath) && File::exists($envPath)) {
            return $envPath;
        }

        $localBin = base_path('bin/ffmpeg.exe');
        if (File::exists($localBin)) {
            return $localBin;
        }

        $localBinLinux = base_path('bin/ffmpeg');
        if (File::exists($localBinLinux)) {
            return $localBinLinux;
        }

        $process = new Process(['ffmpeg', '-version']);
        try {
            $process->run();
            if ($process->isSuccessful()) {
                return 'ffmpeg';
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * Determine a staging filename.
     */
    protected function determineStagingFilename(?string $customFilename, string $extension): string
    {
        if (! empty($customFilename)) {
            $hasExt = pathinfo($customFilename, PATHINFO_EXTENSION);
            if (! empty($hasExt)) {
                return $customFilename;
            }

            return $customFilename . '.' . $extension;
        }

        return sprintf('video_%d_%s.%s', time(), Str::lower(Str::random(8)), $extension);
    }
}
