<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'member_id',
        'business_page_id',
        'community_id',
        'group_id',
        'event_id',
        'is_announcement',
        'is_featured',
        'original_post_id',
        'is_pinned',
        'body',
        'media_type',
        'media_path',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_featured' => 'boolean',
        'is_announcement' => 'boolean',
    ];

    protected $appends = [
        'media_url',
        'author',
        'is_hidden',
        'user_reaction',
        'has_liked',
        'is_saved',
        'shares_count',
    ];

    public function getSharesCountAttribute(): int
    {
        if (array_key_exists('shares_count', $this->attributes)) {
            $count = (int) $this->attributes['shares_count'];
            if ($count > 0 || ! $this->original_post_id) {
                return $count;
            }
        }

        if ($this->original_post_id) {
            if ($this->relationLoaded('originalPost') && $this->originalPost) {
                return (int) $this->originalPost->shares_count;
            }
            return (int) PostShare::where('original_post_id', $this->original_post_id)->count();
        }

        if ($this->relationLoaded('shares')) {
            return $this->shares->count();
        }

        return (int) $this->shares()->count();
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

    public function getAuthorAttribute(): ?array
    {
        $member = $this->relationLoaded('member') ? $this->member : ($this->member_id ? $this->member : null);
        if (!$member) {
            return null;
        }
        return [
            'id' => $member->id,
            'name' => $member->name,
            'user_id' => $member->user_id,
            'avatar_url' => $member->avatar_url ?? null,
        ];
    }

    public function getIsHiddenAttribute(): bool
    {
        $currentMemberId = auth('member')->id();
        if (!$currentMemberId) return false;
        if ($this->relationLoaded('hiddenPosts')) {
            return $this->hiddenPosts->where('member_id', $currentMemberId)->isNotEmpty();
        }
        return $this->hiddenPosts()->where('member_id', $currentMemberId)->exists();
    }

    public function getUserReactionAttribute(): ?string
    {
        $currentMemberId = auth('member')->id();
        if (!$currentMemberId) return null;
        if ($this->relationLoaded('reactions')) {
            return $this->reactions->firstWhere('member_id', $currentMemberId)?->reaction;
        }
        return $this->reactions()->where('member_id', $currentMemberId)->value('reaction');
    }

    public function getHasLikedAttribute(): bool
    {
        $currentMemberId = auth('member')->id();
        if (!$currentMemberId) return false;
        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('member_id', $currentMemberId);
        }
        return $this->likes()->where('member_id', $currentMemberId)->exists();
    }

    public function getIsSavedAttribute(): bool
    {
        $currentMemberId = auth('member')->id();
        if (!$currentMemberId) return false;
        if ($this->relationLoaded('savedPosts')) {
            return $this->savedPosts->contains('member_id', $currentMemberId);
        }
        return $this->savedPosts()->where('member_id', $currentMemberId)->exists();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    public function adCampaigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdCampaign::class, 'post_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }

    public function sharedPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Post::class, 'original_post_id');
    }

    public function shares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostShare::class, 'original_post_id');
    }

    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostReaction::class, 'post_id');
    }

    public function topReactions()
    {
        return PostReaction::query()
            ->where('post_id', $this->id)
            ->selectRaw('reaction, COUNT(*) as total')
            ->groupBy('reaction')
            ->orderByDesc('total')
            ->take(3)
            ->get();
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function savedPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SavedPost::class, 'post_id');
    }

    public function hiddenPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HiddenPost::class, 'post_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReportedPost::class, 'post_id');
    }

    public function isSavedBy(?int $memberId): bool
    {
        if (! $memberId) return false;
        return $this->savedPosts()->where('member_id', $memberId)->exists();
    }

    public function isHiddenBy(?int $memberId): bool
    {
        if (! $memberId) return false;
        return $this->hiddenPosts()->where('member_id', $memberId)->exists();
    }

    public function isShared(): bool
    {
        return ! is_null($this->original_post_id);
    }

    public function hasImage(): bool
    {
        return $this->media_type === 'image' && filled($this->media_path);
    }

    public function hasVideo(): bool
    {
        return $this->media_type === 'video' && filled($this->media_path);
    }
}
