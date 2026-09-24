<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'inviter_id',
        'invitee_id',
        'email',
        'invite_code',
        'status',
        'expires_at',
        'max_uses',
        'use_count',
        'type',
        'is_revoked',
        'source',
        'qr_data',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class, 'community_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'invitee_id');
    }

    public function isRevoked(): bool
    {
        return (bool) $this->is_revoked;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isLimitReached(): bool
    {
        return ! is_null($this->max_uses) && $this->use_count >= $this->max_uses;
    }

    public function isValid(): bool
    {
        if ($this->isRevoked()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->isLimitReached()) {
            return false;
        }

        return true;
    }

    public function incrementUsage(?int $inviteeId = null): void
    {
        $this->increment('use_count');

        if ($inviteeId && ! $this->invitee_id) {
            $this->update(['invitee_id' => $inviteeId, 'status' => 'accepted']);
        }

        if ($this->max_uses && $this->use_count >= $this->max_uses) {
            $this->update(['status' => 'expired']);
        }
    }
}
