<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemToolsController extends Controller
{
    /**
     * Display System Health Dashboard, Queue Status, and Failed Jobs.
     */
    public function index(Request $request)
    {
        // 1. Health Status
        $dbConnected = true;
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbConnected = false;
        }

        $logFilePath = storage_path('logs/laravel.log');
        $logSize = File::exists($logFilePath) ? File::size($logFilePath) : 0;
        $logSizeFormatted = $this->formatBytes($logSize);

        // 2. Queue & Jobs Stats
        $pendingJobsCount = DB::table('jobs')->count();
        $failedJobsCount = DB::table('failed_jobs')->count();
        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->paginate(10);

        // 3. Environment Information (Safe & Masked)
        $envInfo = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'db_driver' => DB::connection()->getDriverName(),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug') ? 'True' : 'False',
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
            'timezone' => config('app.timezone'),
            'maintenance_status' => app()->isDownForMaintenance() ? 'Maintenance Mode' : 'Live',
            'storage_symlink' => File::exists(public_path('storage')) ? 'Linked' : 'Missing',
            'log_file_size' => $logSizeFormatted,
        ];

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'dbConnected' => $dbConnected,
                'logSizeFormatted' => $logSizeFormatted,
                'pendingJobsCount' => $pendingJobsCount,
                'failedJobsCount' => $failedJobsCount,
                'failedJobs' => $failedJobs,
                'envInfo' => $envInfo,
            ]);
        }

        return view('admin.system.index', compact(
            'dbConnected',
            'logSizeFormatted',
            'pendingJobsCount',
            'failedJobsCount',
            'failedJobs',
            'envInfo'
        ));
    }

    /**
     * Display Laravel Log Viewer reading recent log entries safely.
     */
    public function logs(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $level = strtoupper((string) $request->input('level'));

        $logFilePath = storage_path('logs/laravel.log');
        $logEntries = [];

        if (File::exists($logFilePath)) {
            // Read last 200 lines to prevent memory overflow
            $content = $this->tailFile($logFilePath, 200);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                if (trim($line) === '') continue;

                // Match Laravel Log Format [YYYY-MM-DD HH:MM:SS] env.LEVEL: Message
                preg_match('/^\[(?<date>.*?)\]\s+(?<env>\w+)\.(?<level>\w+):\s+(?<message>.*)/', $line, $matches);

                if ($matches) {
                    $entry = (object) [
                        'date' => $matches['date'],
                        'env' => $matches['env'],
                        'level' => strtoupper($matches['level']),
                        'message' => $matches['message'],
                        'raw' => $line,
                    ];

                    // Filtering
                    if ($level !== '' && $entry->level !== $level) continue;
                    if ($search !== '' && stripos($entry->message, $search) === false && stripos($entry->date, $search) === false) continue;

                    $logEntries[] = $entry;
                }
            }
        }

        $logEntries = array_reverse($logEntries);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'logEntries' => $logEntries,
                'search' => $search,
                'level' => $level,
            ]);
        }

        return view('admin.system.logs', compact('logEntries', 'search', 'level'));
    }

    /**
     * Download laravel.log file.
     */
    public function downloadLog(Request $request)
    {
        $logFilePath = storage_path('logs/laravel.log');
        if (File::exists($logFilePath)) {
            return response()->download($logFilePath, 'laravel.log', [
                'Content-Type' => 'text/plain',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            ]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Log file does not exist.'], 404);
        }

        return redirect()->back()->with('error', 'Log file does not exist.');
    }

    /**
     * Safely clear laravel.log content.
     */
    public function clearLog()
    {
        $logFilePath = storage_path('logs/laravel.log');
        if (File::exists($logFilePath)) {
            File::put($logFilePath, '');
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Laravel log file emptied successfully.']);
            }
            return redirect()->back()->with('success', 'Laravel log file emptied successfully.');
        }
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Log file does not exist.'], 404);
        }
        return redirect()->back()->with('error', 'Log file does not exist.');
    }

    /**
     * Retry a failed job using Artisan.
     */
    public function retryFailedJob($id)
    {
        Artisan::call('queue:retry', ['id' => $id]);
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json(['success' => true, 'message' => "Triggered retry for failed job #{$id}."]);
        }
        return redirect()->back()->with('success', "Triggered retry for failed job #{$id}.");
    }

    /**
     * Delete a failed job record.
     */
    public function deleteFailedJob($id)
    {
        DB::table('failed_jobs')->where('id', $id)->delete();
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json(['success' => true, 'message' => "Failed job #{$id} deleted."]);
        }
        return redirect()->back()->with('success', "Failed job #{$id} deleted.");
    }

    /**
     * Execute safe Artisan optimization commands.
     */
    public function runArtisan(Request $request)
    {
        $command = $request->input('command');

        $allowedCommands = [
            'optimize:clear' => 'optimize:clear',
            'cache:clear' => 'cache:clear',
            'config:clear' => 'config:clear',
            'route:clear' => 'route:clear',
            'view:clear' => 'view:clear',
        ];

        if (isset($allowedCommands[$command])) {
            Artisan::call($allowedCommands[$command]);
            $output = Artisan::output();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => "Executed 'artisan {$command}' successfully.",
                    'output' => $output,
                ]);
            }
            return redirect()->back()->with('success', "Executed 'artisan {$command}' successfully.");
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or invalid command.'], 422);
        }

        return redirect()->back()->with('error', 'Unauthorized or invalid command.');
    }

    /**
     * Helper to read last N lines of file.
     */
    private function tailFile($filePath, $lines = 200)
    {
        $f = fopen($filePath, "rb");
        if (!$f) return '';

        $buffer = 4096;
        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n") $lines--;

        $output = '';
        $chunk = '';

        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }

        fclose($f);
        return $output;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
