<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessTeamMember extends Model
{
    use HasFactory;

    public const ROLES = [
        'owner' => 'Page Owner',
        'admin' => 'Page Admin',
        'editor' => 'Page Editor',
        'moderator' => 'Page Moderator',
        'analyst' => 'Page Analyst',
    ];

    protected $fillable = [
        'business_page_id',
        'member_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function canManageTeam(): bool
    {
        return in_array($this->role, ['owner', 'admin'], true);
    }

    public function canPublish(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'editor'], true);
    }

    public function canModerate(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'moderator'], true);
    }

    public function canViewAnalytics(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'analyst'], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }
}
