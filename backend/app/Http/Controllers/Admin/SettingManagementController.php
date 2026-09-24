<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingManagementController extends Controller
{
    /**
     * Display current platform settings and system diagnostics.
     */
    public function index()
    {
        $allSettings = Setting::all()->pluck('value', 'key')->all();
        $brandingData = Setting::getBrandingData();

        // Merge branding URLs into settings array
        $settings = array_merge($allSettings, [
            'site_logo_url' => $brandingData['site_logo_url'],
            'site_dark_logo_url' => $brandingData['site_dark_logo_url'],
            'site_favicon_url' => $brandingData['site_favicon_url'],
        ]);

        $systemInfo = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'db_driver' => DB::connection()->getDriverName(),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug') ? 'Enabled' : 'Disabled',
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
            'mail_driver' => config('mail.default'),
            'timezone' => config('app.timezone'),
            'maintenance_mode' => app()->isDownForMaintenance() ? 'Enabled' : 'Disabled',
            'server_time' => now()->format('Y-m-d H:i:s T'),
        ];

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'settings' => $settings,
                'branding' => $brandingData,
                'systemInfo' => $systemInfo,
                'isMaintenance' => app()->isDownForMaintenance(),
            ]);
        }

        return view('admin.settings.index', compact('settings', 'systemInfo'));
    }

    /**
     * Update settings and upload branding assets.
     */
    public function update(Request $request)
    {
        $request->validate([
            'group' => 'nullable|string|max:50',
            'site_name' => 'nullable|string|max:150',
            'site_description' => 'nullable|string|max:1000',
            'timezone' => 'nullable|string|max:100',
            'date_format' => 'nullable|string|max:50',
            'time_format' => 'nullable|string|max:50',
            'default_language' => 'nullable|string|max:20',
            'currency' => 'nullable|string|max:50',
            'pagination_size' => 'nullable|integer|min:1|max:500',
            'company_name' => 'nullable|string|max:150',
            'support_email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'social_facebook' => 'nullable|string|max:255',
            'social_twitter' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',
            'social_linkedin' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',
            'social_telegram' => 'nullable|string|max:255',
            'site_logo' => 'nullable|file|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'site_dark_logo' => 'nullable|file|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
            'site_favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $group = $request->input('group', 'general');

        // Text & Select fields
        $fields = [
            'site_name', 'site_description', 'timezone', 'date_format', 'time_format',
            'default_language', 'currency', 'pagination_size',
            'company_name', 'support_email', 'phone', 'address', 'website',
            'meta_title', 'meta_description', 'meta_keywords',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube', 'social_telegram'
        ];

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                Setting::set($field, $request->input($field), $group);
            }
        }

        // Branding Uploads
        if ($request->hasFile('site_logo')) {
            $this->deleteOldBrandingAsset(Setting::get('site_logo'));
            $path = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $request->file('site_logo'),
                'branding',
                'logo',
                null,
                'public'
            );
            if ($path) {
                Setting::set('site_logo', 'storage/' . $path, 'branding');
            }
        }

        if ($request->hasFile('site_dark_logo')) {
            $this->deleteOldBrandingAsset(Setting::get('site_dark_logo'));
            $path = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $request->file('site_dark_logo'),
                'branding',
                'logo',
                null,
                'public'
            );
            if ($path) {
                Setting::set('site_dark_logo', 'storage/' . $path, 'branding');
            }
        }

        if ($request->hasFile('site_favicon')) {
            $this->deleteOldBrandingAsset(Setting::get('site_favicon'));
            $path = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $request->file('site_favicon'),
                'branding',
                'favicon',
                null,
                'public'
            );
            if ($path) {
                Setting::set('site_favicon', 'storage/' . $path, 'branding');
            }
        }

        $allSettings = Setting::all()->pluck('value', 'key')->all();
        $brandingData = Setting::getBrandingData();
        $settings = array_merge($allSettings, [
            'site_logo_url' => $brandingData['site_logo_url'],
            'site_dark_logo_url' => $brandingData['site_dark_logo_url'],
            'site_favicon_url' => $brandingData['site_favicon_url'],
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Platform settings updated successfully.',
                'settings' => $settings,
                'branding' => $brandingData,
            ]);
        }

        return redirect()->back()->with('success', 'Platform settings updated successfully.');
    }

    /**
     * Public endpoint to fetch active platform branding assets.
     */
    public function getBranding(Request $request)
    {
        $branding = Setting::getBrandingData();

        return response()->json([
            'success' => true,
            'branding' => $branding,
        ]);
    }

    /**
     * Safely delete a previously uploaded custom asset from public storage.
     */
    protected function deleteOldBrandingAsset(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        $trimmed = trim($path);
        // Only delete files stored within our branding directory, never default assets
        if (str_starts_with($trimmed, 'storage/branding/') || str_starts_with($trimmed, 'branding/')) {
            $relative = str_replace('storage/', '', $trimmed);
            if (Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }
    }

    /**
     * Clear application cache using Laravel Artisan commands.
     */
    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');

        if ($type === 'cache' || $type === 'all') {
            Artisan::call('cache:clear');
        }
        if ($type === 'config' || $type === 'all') {
            Artisan::call('config:clear');
        }
        if ($type === 'route' || $type === 'all') {
            Artisan::call('route:clear');
        }
        if ($type === 'view' || $type === 'all') {
            Artisan::call('view:clear');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Application cache cleared successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Application cache cleared successfully.');
    }

    /**
     * Toggle Maintenance Mode via Artisan down / up.
     */
    public function toggleMaintenance(Request $request)
    {
        $action = $request->input('action');
        $shouldDisable = $action === 'up' || ($action === null && app()->isDownForMaintenance());

        if ($shouldDisable) {
            Artisan::call('up');
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Platform maintenance mode disabled. Application is LIVE.',
                    'is_maintenance' => false,
                ]);
            }
            return redirect()->back()->with('success', 'Platform maintenance mode disabled. Application is LIVE.');
        } else {
            $secret = $request->input('secret', 'admin-access');
            Artisan::call('down', ['--secret' => $secret]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Platform maintenance mode enabled.',
                    'is_maintenance' => true,
                ]);
            }
            return redirect()->back()->with('success', 'Platform maintenance mode enabled.');
        }
    }
}
