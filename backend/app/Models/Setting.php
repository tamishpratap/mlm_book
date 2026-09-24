<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Path to local file-based settings store if database table is omitted.
     */
    protected static function getStoragePath(): string
    {
        return storage_path('app/settings.json');
    }

    /**
     * Default platform settings.
     */
    protected static function defaultSettings(): array
    {
        return [
            'site_name' => 'MLM Book',
            'site_description' => 'Enterprise MLM Book Social & Commerce Platform.',
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'default_language' => 'en',
            'currency' => 'USD ($)',
            'pagination_size' => '15',
            'site_logo' => 'logo/logo.png',
            'site_dark_logo' => 'logo/logo.png',
            'site_favicon' => 'logo/logo.png',
            'company_name' => 'MLM Book Enterprise',
            'support_email' => 'support@mlmbook.com',
            'phone' => '+1 (800) 123-4567',
            'website' => 'https://mlmbook.com',
            'address' => '123 Enterprise Way, Suite 500, Tech City',
            'meta_title' => 'MLM Book - Social Network & Marketplace',
            'meta_description' => 'Connect, collaborate, and trade in a secure business social network.',
            'meta_keywords' => 'mlm, social network, business, marketplace, communities',
            'social_facebook' => 'https://facebook.com/mlmbook',
            'social_twitter' => 'https://twitter.com/mlmbook',
            'social_instagram' => 'https://instagram.com/mlmbook',
            'social_linkedin' => 'https://linkedin.com/company/mlmbook',
            'social_youtube' => 'https://youtube.com/mlmbook',
            'social_telegram' => 'https://t.me/mlmbook',
        ];
    }

    /**
     * Get all settings as a collection of Setting models.
     * Merges database records with defaultSettings and file settings for any missing keys.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public static function all($columns = ['*'])
    {
        $dbSettings = new Collection();
        $hasDb = false;

        try {
            if (Schema::hasTable('settings')) {
                $dbSettings = parent::all($columns);
                $hasDb = true;
            }
        } catch (\Throwable $e) {
            $hasDb = false;
        }

        $fileSettings = static::loadFromFile();

        if (!$hasDb) {
            return $fileSettings;
        }

        // Merge defaults and file settings for any keys not currently in database
        $existingKeys = $dbSettings->pluck('key')->all();
        $merged = clone $dbSettings;

        foreach ($fileSettings as $fileSetting) {
            if (!in_array($fileSetting->key, $existingKeys, true)) {
                $merged->push($fileSetting);
            }
        }

        return $merged;
    }

    /**
     * Load settings from local json storage.
     */
    protected static function loadFromFile(): Collection
    {
        $path = static::getStoragePath();
        $stored = [];

        if (File::exists($path)) {
            $decoded = json_decode(File::get($path), true);
            if (is_array($decoded)) {
                $stored = $decoded;
            }
        }

        $merged = array_merge(static::defaultSettings(), $stored);
        $collection = new Collection();

        foreach ($merged as $key => $value) {
            $setting = new static([
                'key' => $key,
                'value' => $value,
                'group' => 'general',
            ]);
            $collection->push($setting);
        }

        return $collection;
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        try {
            if (Schema::hasTable('settings')) {
                $item = static::where('key', $key)->first();
                if ($item) {
                    return $item->value;
                }
            }
        } catch (\Throwable $e) {
            // Fall back
        }

        $all = static::loadFromFile()->pluck('value', 'key');
        return $all->get($key, $default);
    }

    /**
     * Set a setting key/value.
     */
    public static function set(string $key, $value, string $group = 'general'): void
    {
        $hasDb = false;
        try {
            if (Schema::hasTable('settings')) {
                static::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'group' => $group]
                );
                $hasDb = true;
            }
        } catch (\Throwable $e) {
            $hasDb = false;
        }

        // Always persist to local file as well for high availability & speed
        $path = static::getStoragePath();
        $stored = [];

        if (File::exists($path)) {
            $decoded = json_decode(File::get($path), true);
            if (is_array($decoded)) {
                $stored = $decoded;
            }
        }

        $stored[$key] = $value;
        $dir = dirname($path);
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($path, json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Resolve a browser-accessible asset URL with cache-busting timestamp.
     */
    public static function resolveAssetUrl(?string $path, string $fallback = '/logo/logo.png'): string
    {
        if (empty($path)) {
            return $fallback;
        }

        $trimmed = trim($path);

        // Absolute URLs (external CDN, data URL, blob)
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, 'data:') || str_starts_with($trimmed, 'blob:')) {
            return $trimmed;
        }

        // Ensure clean root-relative path
        $cleanPath = '/' . ltrim($trimmed, '/');

        // Determine disk location for cache busting version timestamp
        $version = 1;
        try {
            if (str_starts_with($trimmed, 'storage/')) {
                $storageRelative = substr($trimmed, 8); // remove 'storage/'
                $diskPath = storage_path('app/public/' . $storageRelative);
                if (File::exists($diskPath)) {
                    $version = File::lastModified($diskPath);
                } elseif (File::exists(public_path($trimmed))) {
                    $version = File::lastModified(public_path($trimmed));
                } else {
                    // Stored asset does not physically exist on disk; gracefully fallback to canonical asset
                    return static::resolveAssetUrl($fallback, '/logo/logo.png');
                }
            } elseif (File::exists(public_path($trimmed))) {
                $version = File::lastModified(public_path($trimmed));
            }
        } catch (\Throwable $e) {
            $version = 1;
        }

        return $cleanPath . '?v=' . $version;
    }

    /**
     * Get consolidated branding assets and URLs.
     */
    public static function getBrandingData(): array
    {
        $siteLogo = static::get('site_logo', 'logo/logo.png');
        $siteDarkLogo = static::get('site_dark_logo', 'logo/logo.png');
        $siteFavicon = static::get('site_favicon', 'favicon.ico');

        return [
            'site_name' => static::get('site_name', 'MLM Book'),
            'site_description' => static::get('site_description', 'Enterprise MLM Book Social & Commerce Platform.'),
            'site_logo' => $siteLogo,
            'site_logo_url' => static::resolveAssetUrl($siteLogo, '/logo/logo.png'),
            'site_dark_logo' => $siteDarkLogo,
            'site_dark_logo_url' => static::resolveAssetUrl($siteDarkLogo, '/logo/logo.png'),
            'site_favicon' => $siteFavicon,
            'site_favicon_url' => static::resolveAssetUrl($siteFavicon, '/favicon.svg'),
        ];
    }
}
