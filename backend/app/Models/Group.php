<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'category',
        'privacy',
        'cover_photo',
        'logo',
        'rules',
        'tags',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function acceptedMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)->where('status', 'accepted');
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class)->where('status', 'pending');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class);
    }

    public function isMember(?int $memberId): bool
    {
        if (! $memberId) return false;
        return $this->acceptedMembers()->where('member_id', $memberId)->exists();
    }

    public function isPending(?int $memberId): bool
    {
        if (! $memberId) return false;
        return $this->pendingMembers()->where('member_id', $memberId)->exists();
    }

    public function memberRole(?int $memberId): ?string
    {
        if (! $memberId) return null;
        return $this->members()->where('member_id', $memberId)->where('status', 'accepted')->value('role');
    }

    public function isOwner(?int $memberId): bool
    {
        return $memberId && $this->owner_id === $memberId;
    }

    public function isAdmin(?int $memberId): bool
    {
        if (! $memberId) return false;
        $role = $this->memberRole($memberId);
        return in_array($role, ['owner', 'admin']);
    }

    public function isModerator(?int $memberId): bool
    {
        if (! $memberId) return false;
        $role = $this->memberRole($memberId);
        return in_array($role, ['owner', 'admin', 'moderator']);
    }
}
