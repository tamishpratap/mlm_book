<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\RewardRankRule;
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
     * Public endpoint to fetch active platform branding and contact information (dynamic for frontend).
     */
    public function getPublicSettings(Request $request)
    {
        $branding = Setting::getBrandingData();
        $contact = [
            'company_name' => Setting::get('company_name', 'MLM Book Enterprise'),
            'support_email' => Setting::get('support_email', 'support@mlmbook.com'),
            'phone' => Setting::get('phone', '+1 (800) 123-4567'),
            'website' => Setting::get('website', 'https://mlmbook.com'),
            'address' => '',
            'social_facebook' => Setting::get('social_facebook', 'https://facebook.com/mlmbook'),
            'social_twitter' => Setting::get('social_twitter', null),
            'social_instagram' => Setting::get('social_instagram', 'https://instagram.com/mlmbook'),
            'social_linkedin' => Setting::get('social_linkedin', 'https://linkedin.com/company/mlmbook'),
            'social_youtube' => Setting::get('social_youtube', 'https://youtube.com/mlmbook'),
            'social_telegram' => Setting::get('social_telegram', 'https://t.me/mlmbook'),
            'business_hours' => Setting::get('business_hours', 'Monday - Friday: 9:00 AM - 6:00 PM (UTC)'),
        ];

        return response()->json([
            'success' => true,
            'branding' => $branding,
            'contact' => $contact,
        ]);
    }

    /**
     * Public endpoint to fetch active reward rank rules from reward_rank_rules table.
     */
    public function getPublicRankRules(Request $request)
    {
        $rules = RewardRankRule::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'rules' => $rules,
            'data' => $rules,
        ]);
    }

    /**
     * Public endpoint to submit contact inquiries from the frontend Contact Us page.
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|min:5|max:5000',
        ]);

        $path = storage_path('app/contact_messages.json');
        $messages = [];
        try {
            if (File::exists($path)) {
                $decoded = json_decode(File::get($path), true);
                if (is_array($decoded)) {
                    $messages = $decoded;
                }
            }
        } catch (\Throwable $e) {
            $messages = [];
        }

        $newMessage = [
            'id' => uniqid('msg_', true),
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => trim($validated['phone'] ?? ''),
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => 'new',
            'created_at' => now()->toIso8601String(),
            'ip' => $request->ip(),
        ];

        array_unshift($messages, $newMessage);

        try {
            $dir = dirname($path);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::put($path, json_encode(array_slice($messages, 0, 500), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            // Non-blocking
        }

        // If authenticated member or existing member email, also record in FeedbackSuggestion table
        try {
            $member = auth('member')->user();
            if (!$member && !empty($validated['email'])) {
                $member = \App\Models\Member::where('email', trim($validated['email']))->first();
            }
            if ($member) {
                \App\Models\FeedbackSuggestion::create([
                    'member_id' => $member->id,
                    'type' => 'other',
                    'subject' => '[Contact Form] ' . $validated['subject'],
                    'message' => "From: {$validated['name']} ({$validated['email']}, Phone: {$validated['phone']})\n\n" . $validated['message'],
                    'status' => 'new',
                ]);
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for contacting us! We have received your message and will respond promptly.',
        ]);
    }

    /**
     * Admin endpoint: Fetch all contact inquiries submitted via frontend Contact Us.
     */
    public function getContactMessages(Request $request)
    {
        $path = storage_path('app/contact_messages.json');
        $messages = [];
        try {
            if (File::exists($path)) {
                $decoded = json_decode(File::get($path), true);
                if (is_array($decoded)) {
                    $messages = $decoded;
                }
            }
        } catch (\Throwable $e) {
            $messages = [];
        }

        foreach ($messages as &$msg) {
            if (empty($msg['status'])) {
                $msg['status'] = 'new';
            }
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'total' => count($messages),
        ]);
    }

    /**
     * Admin endpoint: Delete a contact inquiry by ID.
     */
    public function deleteContactMessage($id)
    {
        $path = storage_path('app/contact_messages.json');
        if (!File::exists($path)) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        try {
            $decoded = json_decode(File::get($path), true);
            if (!is_array($decoded)) {
                return response()->json(['success' => false, 'message' => 'Message not found'], 404);
            }

            $filtered = array_values(array_filter($decoded, function ($item) use ($id) {
                return ($item['id'] ?? '') !== $id;
            }));

            File::put($path, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'message' => 'Inquiry message deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete inquiry message.'], 500);
        }
    }

    /**
     * Admin endpoint: Update status (e.g. read, replied, archived) of a contact inquiry.
     */
    public function updateContactMessageStatus(Request $request, $id)
    {
        $status = $request->input('status', 'read');
        $path = storage_path('app/contact_messages.json');
        if (!File::exists($path)) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        try {
            $decoded = json_decode(File::get($path), true);
            if (!is_array($decoded)) {
                return response()->json(['success' => false, 'message' => 'Message not found'], 404);
            }

            $found = false;
            foreach ($decoded as &$item) {
                if (($item['id'] ?? '') === $id) {
                    $item['status'] = $status;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                File::put($path, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status.'], 500);
        }
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
