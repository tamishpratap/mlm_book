<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'member_id',
        'role',
        'status',
        'notification_level',
        'muted_until',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'muted_until' => 'datetime',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class, 'community_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['owner', 'admin']);
    }

    public function scopeModerators($query)
    {
        return $query->whereIn('role', ['owner', 'admin', 'moderator']);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'moderator']);
    }

    public function isMutedNotifications(): bool
    {
        if ($this->notification_level === 'muted') return true;

        return $this->muted_until && $this->muted_until->isFuture();
    }

    public function shouldNotifyFor(string $event): bool
    {
        if ($this->isMutedNotifications()) return false;

        $level = $this->notification_level ?: 'all';

        if ($level === 'all') return true;
        if ($level === 'announcements_only') return in_array($event, ['announcement', 'moderation_action', 'role_changed']);
        if ($level === 'important_only') return in_array($event, ['announcement', 'pinned_post', 'join_request', 'moderation_action']);
        if ($level === 'posts_only') return in_array($event, ['new_post', 'announcement']);

        return true;
    }
}
