<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Story extends Model
{
    protected $fillable = [
        'member_id',
        'caption',
        'media_type',
        'media_path',
        'expires_at',
    ];

    protected $appends = [
        'media_url',
        'is_expired',
        'status',
        'created_at_human',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->media_path) {
            return null;
        }

        $path = trim($this->media_path);
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $clean = ltrim($path, '/');

        // Normalize legacy /storage/uploads/ to /uploads/
        if (str_starts_with($clean, 'storage/uploads/')) {
            $clean = substr($clean, 8);
        }

        return asset($clean);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at ? $this->expires_at->lessThanOrEqualTo(now()) : false;
    }

    public function getStatusAttribute(): string
    {
        return ($this->expires_at && $this->expires_at->lessThanOrEqualTo(now())) ? 'expired' : 'active';
    }

    public function getCreatedAtHumanAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryView::class, 'story_id');
    }

    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryLike::class, 'story_id');
    }

    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryReaction::class, 'story_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoryReply::class, 'story_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function isImage(): bool
    {
        return strtolower($this->media_type ?? '') === 'image';
    }

    public function isVideo(): bool
    {
        return strtolower($this->media_type ?? '') === 'video';
    }
}
