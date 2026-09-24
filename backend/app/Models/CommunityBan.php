<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityBan extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'member_id',
        'banned_by_id',
        'reason',
        'expires_at',
        'is_permanent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_permanent' => 'boolean',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'banned_by_id');
    }

    public function isExpired(): bool
    {
        return ! $this->is_permanent && $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_permanent', true)
              ->orWhere('expires_at', '>', now());
        });
    }
}
