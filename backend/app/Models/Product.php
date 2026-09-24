<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'member_id',
        'category_id',
        'sub_category_id',
        'title',
        'slug',
        'description',
        'price',
        'condition',
        'brand',
        'location',
        'quantity',
        'is_negotiable',
        'tags',
        'status',
        'is_featured',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_negotiable' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function savedProducts(): HasMany
    {
        return $this->hasMany(SavedProduct::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReportedProduct::class, 'product_id');
    }

    public function isSavedBy(?int $memberId): bool
    {
        if (! $memberId) return false;
        return $this->savedProducts()->where('member_id', $memberId)->exists();
    }
}
