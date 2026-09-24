<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BusinessPageCategory extends Model
{
    use HasFactory;

    protected $table = 'business_page_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the count of business pages currently categorized under this category.
     */
    public function getBusinessPagesCountAttribute(): int
    {
        return BusinessPage::where('category', $this->name)->count();
    }

    /**
     * Business Pages query for this category.
     */
    public function businessPages()
    {
        return BusinessPage::where('category', $this->name);
    }

    /**
     * Get all active categories collection.
     */
    public static function getActiveCategories()
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get list of active category names (for dropdowns and validation).
     * Authoritative single source of truth: business_page_categories database table.
     */
    public static function getActiveCategoryNames(): array
    {
        if (static::count() === 0) {
            static::seedDefaultCategoriesIfMissing();
        }

        return static::query()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->toArray();
    }

    /**
     * Resolve any category representation (exact name, slug, ID, or case-insensitive string)
     * to its canonical category name from the database.
     * Returns the canonical name if valid and active, or null if invalid or inactive.
     */
    public static function resolveCanonicalName($input): ?string
    {
        if ($input === null || $input === '') {
            return null;
        }

        $rawInput = is_string($input) ? trim(urldecode((string) $input)) : (string) $input;
        if ($rawInput === '') {
            return null;
        }

        // Check against active category names directly
        $activeNames = static::getActiveCategoryNames();
        foreach ($activeNames as $name) {
            if (strcasecmp($name, $rawInput) === 0) {
                return $name;
            }
        }

        // Check active database categories by slug, name or ID
        $dbCategory = static::query()
            ->where('is_active', true)
            ->where(function ($q) use ($rawInput) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($rawInput)])
                  ->orWhere('slug', strtolower($rawInput))
                  ->orWhere('slug', Str::slug($rawInput));
                if (is_numeric($rawInput)) {
                    $q->orWhere('id', (int) $rawInput);
                }
            })
            ->first();

        if ($dbCategory) {
            return $dbCategory->name;
        }

        return null;
    }

    /**
     * Seed default MLM categories into business_page_categories if missing.
     * Preserves existing categories, IDs, and admin custom categories.
     */
    public static function seedDefaultCategoriesIfMissing(): void
    {
        $defaultCategories = [
            'Binary MLM Plan',
            'Matrix MLM Plan',
            'Unilevel MLM Plan',
            'Board / Revolving Matrix Plan',
            'Generation / Level Plan',
            'Monoline / Single Leg Plan',
            'Stair-Step Breakaway Plan',
            'Crowdfunding / Helping MLM Plan',
            'Spillover / Auto-Pool Plan',
            'Gift / Donation MLM Plan',
            'Health, Nutrition & Wellness MLM',
            'Cosmetics & Personal Care MLM',
            'Crypto, Forex & FinTech MLM',
            'E-Commerce & Affiliate MLM',
            'Real Estate & Investment MLM',
            'Digital Services & EdTech MLM',
            'Travel & Hospitality MLM',
            'MLM Software & App Solutions',
            'MLM Legal & Compliance Consultancy',
            'MLM Lead Generation & Marketing',
            'Top Leaders & Networker Profiles',
        ];

        $order = 10;
        foreach ($defaultCategories as $name) {
            $slug = Str::slug($name) ?: 'category';
            static::firstOrCreate(
                ['name' => $name],
                [
                    'slug' => $slug,
                    'description' => null,
                    'is_active' => true,
                    'order' => $order++,
                ]
            );
        }
    }

    /**
     * Get category counts for directory/filters.
     */
    public static function getCategoryCounts(): array
    {
        return BusinessPage::publicPages()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
    }

    /**
     * Generate unique slug for a category name.
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug ?: 'category';
        $query = static::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $slug = $slug . '-' . Str::lower(Str::random(4));
        }

        return $slug;
    }
}
