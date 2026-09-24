<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_id',
        'member_id',
        'parent_id',
        'comment',
    ];

    protected $appends = [
        'user_reaction',
        'reactions_count',
    ];

    public function getUserReactionAttribute(): ?string
    {
        $currentMemberId = auth('member')->id();
        if (!$currentMemberId) {
            return null;
        }

        if ($this->relationLoaded('reactions')) {
            return $this->reactions->firstWhere('member_id', $currentMemberId)?->reaction;
        }

        return $this->reactions()->where('member_id', $currentMemberId)->value('reaction');
    }

    public function getReactionsCountAttribute(): int
    {
        if ($this->relationLoaded('reactions')) {
            return $this->reactions->count();
        }

        return (int) $this->reactions()->count();
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }

    public function reactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CommentReaction::class, 'comment_id');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isEdited(): bool
    {
        if (! $this->updated_at || ! $this->created_at) {
            return false;
        }

        return $this->updated_at->diffInSeconds($this->created_at) > 5;
    }
}
