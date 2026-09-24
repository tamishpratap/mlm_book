<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'Technology',
        'Business',
        'Education',
        'Gaming',
        'Sports',
        'Finance',
        'Crypto',
        'Entertainment',
        'Lifestyle',
        'Health',
        'Other',
    ];

    public const VISIBILITIES = [
        'public' => 'Public',
        'private' => 'Private',
        'invite_only' => 'Invite Only',
        'secret' => 'Secret',
    ];

    protected $fillable = [
        'community_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'category',
        'visibility',
        'status',
        'posting_permissions',
        'join_approval_mode',
        'cover_photo',
        'logo',
        'rules',
        'tags',
        'invite_code',
        'is_featured',
        'trending_score',
        'member_count',
        'post_count',
    ];

    /**
     * The accessors to append to the model's array and JSON form.
     */
    protected $appends = [
        'is_member',
        'is_pending',
        'is_owner',
        'member_role',
    ];

    /**
     * In-memory cache for the computed membership state of the current user.
     * Stored as a plain property so it NEVER contaminates $attributes or triggers SQL updates.
     */
    public ?array $membership_state = null;

    public function getIsMemberAttribute(): bool
    {
        if (isset($this->membership_state['is_member'])) {
            return (bool) $this->membership_state['is_member'];
        }

        $memberId = auth('member')->id();
        return $memberId ? $this->isMember($memberId) : false;
    }

    public function getIsPendingAttribute(): bool
    {
        if (isset($this->membership_state['is_pending'])) {
            return (bool) $this->membership_state['is_pending'];
        }

        $memberId = auth('member')->id();
        return $memberId ? $this->isPending($memberId) : false;
    }

    public function getIsOwnerAttribute(): bool
    {
        if (isset($this->membership_state['is_owner'])) {
            return (bool) $this->membership_state['is_owner'];
        }

        $memberId = auth('member')->id();
        return $memberId ? $this->isOwner($memberId) : false;
    }

    public function getMemberRoleAttribute(): ?string
    {
        if (array_key_exists('member_role', $this->membership_state ?? [])) {
            return $this->membership_state['member_role'];
        }

        $memberId = auth('member')->id();
        return $memberId ? $this->memberRole($memberId) : null;
    }

    public function scopeTrending($query)
    {
        return $query->where('visibility', '!=', 'secret')
            ->orderByRaw('(member_count + (post_count * 3)) DESC');
    }

    public function scopeFeatured($query)
    {
        return $query->where('visibility', '!=', 'secret')
            ->where(function ($q) {
                $q->where('is_featured', true)
                  ->orWhere('member_count', '>=', 5);
            });
    }

    public function scopeRecommendedFor($query, Member $member)
    {
        $joinedCategoryList = $member->joinedCommunities()
            ->pluck('category')
            ->unique()
            ->filter()
            ->toArray();

        $joinedCommunityIds = $member->joinedCommunities()
            ->pluck('communities.id')
            ->toArray();

        return $query->where('visibility', '!=', 'secret')
            ->whereNotIn('id', $joinedCommunityIds)
            ->where(function ($q) use ($joinedCategoryList) {
                if (! empty($joinedCategoryList)) {
                    $q->whereIn('category', $joinedCategoryList);
                } else {
                    $q->where('member_count', '>=', 1);
                }
            });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function acceptedMembers(): HasMany
    {
        return $this->hasMany(CommunityMember::class)->where('status', 'accepted');
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(CommunityMember::class)->where('status', 'pending');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CommunityInvitation::class);
    }

    public function isOwner(?int $memberId): bool
    {
        return $memberId && (int) $this->owner_id === (int) $memberId;
    }

    public function isMember(?int $memberId): bool
    {
        if (! $memberId) return false;
        if ($this->isOwner($memberId)) return true;

        return $this->acceptedMembers()->where('member_id', $memberId)->exists();
    }

    public function isPending(?int $memberId): bool
    {
        if (! $memberId) return false;

        return $this->pendingMembers()->where('member_id', $memberId)->exists();
    }

    public function memberStatus(?int $memberId): ?string
    {
        if (! $memberId) return null;
        if ($this->isOwner($memberId)) return 'accepted';

        return $this->members()->where('member_id', $memberId)->value('status');
    }

    public function memberRole(?int $memberId): ?string
    {
        if (! $memberId) return null;
        if ($this->isOwner($memberId)) return 'owner';

        return $this->members()->where('member_id', $memberId)->where('status', 'accepted')->value('role');
    }

    public function isAdmin(?int $memberId): bool
    {
        if (! $memberId) return false;
        if ($this->isOwner($memberId)) return true;

        $role = $this->memberRole($memberId);
        return in_array($role, ['owner', 'admin']);
    }

    public function isModerator(?int $memberId): bool
    {
        if (! $memberId) return false;
        if ($this->isOwner($memberId)) return true;

        $role = $this->memberRole($memberId);
        return in_array($role, ['owner', 'admin', 'moderator']);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function isInviteOnly(): bool
    {
        return $this->visibility === 'invite_only';
    }

    public function isSecret(): bool
    {
        return $this->visibility === 'secret';
    }

    public function getInviteUrlAttribute(): string
    {
        $code = $this->invite_code ?: $this->generateInviteCode();

        return url('/community/invite/' . $code);
    }

    public function generateInviteCode(): string
    {
        $code = \Illuminate\Support\Str::random(12);
        $this->update(['invite_code' => $code]);

        return $code;
    }

    public function bans(): HasMany
    {
        return $this->hasMany(CommunityBan::class);
    }

    public function mutes(): HasMany
    {
        return $this->hasMany(CommunityMute::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(CommunityWarning::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityReport::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(CommunityAuditLog::class);
    }

    public function isBanned(?int $memberId): bool
    {
        if (! $memberId) return false;

        $ban = $this->bans()->where('member_id', $memberId)->first();
        if (! $ban) return false;

        if ($ban->isExpired()) {
            $ban->delete();
            return false;
        }

        return true;
    }

    public function isMuted(?int $memberId): bool
    {
        if (! $memberId) return false;

        $mute = $this->mutes()->where('member_id', $memberId)->first();
        if (! $mute) return false;

        if ($mute->isExpired()) {
            $mute->delete();
            return false;
        }

        return true;
    }

    public function canPost(?int $memberId): bool
    {
        if (! $memberId) return false;
        if ($this->isBanned($memberId) || $this->isMuted($memberId)) return false;

        $perm = $this->posting_permissions ?: 'everyone';

        if ($perm === 'owner_only') return $this->isOwner($memberId);
        if ($perm === 'admins_only') return $this->isAdmin($memberId);
        if ($perm === 'moderators_admins') return $this->isModerator($memberId);
        if ($perm === 'members_only') return $this->isMember($memberId);

        return $this->isMember($memberId) || $this->isPublic();
    }

    /**
     * Synchronizes and updates the member_count attribute on this Community instance
     * by counting actual accepted members excluding the Community Owner.
     */
    public function syncMemberCount(): int
    {
        $count = $this->acceptedMembers()
            ->where('member_id', '!=', $this->owner_id)
            ->count();

        if ((int) $this->member_count !== (int) $count) {
            static::whereKey($this->getKey())->update(['member_count' => $count]);
            $this->member_count = $count;
            $this->syncOriginalAttribute('member_count');
        }

        return $count;
    }

    /**
     * Appends membership attributes (is_member, is_pending, is_owner, member_role)
     * to this Community instance for JSON serialization.
     */
    public function appendMembershipStatus(?int $memberId): self
    {
        if (! $memberId) {
            $this->membership_state = [
                'is_member' => false,
                'is_pending' => false,
                'is_owner' => false,
                'member_role' => null,
            ];
            return $this;
        }

        $isOwner = (int) $this->owner_id === (int) $memberId;
        if ($isOwner) {
            $this->membership_state = [
                'is_member' => true,
                'is_pending' => false,
                'is_owner' => true,
                'member_role' => 'owner',
            ];
            return $this;
        }

        $membership = CommunityMember::where('community_id', $this->id)
            ->where('member_id', $memberId)
            ->first();

        $isAccepted = $membership && $membership->status === 'accepted';
        $isPending = $membership && $membership->status === 'pending';

        $this->membership_state = [
            'is_member' => $isAccepted,
            'is_pending' => $isPending,
            'is_owner' => false,
            'member_role' => $membership ? ($membership->role ?: ($isAccepted ? 'member' : null)) : null,
        ];

        return $this;
    }

    /**
     * Efficiently appends membership attributes to an array, collection, or paginator
     * of Community instances using a single batch query (0 N+1 query overhead).
     *
     * @param iterable $communities
     * @param int|null $memberId
     */
    public static function appendMembershipStatusToCollection($communities, ?int $memberId): void
    {
        if (empty($communities)) {
            return;
        }

        $items = is_array($communities)
            ? $communities
            : (method_exists($communities, 'all') ? $communities->all() : iterator_to_array($communities));

        if (! $memberId) {
            foreach ($items as $community) {
                if ($community instanceof self) {
                    $community->membership_state = [
                        'is_member' => false,
                        'is_pending' => false,
                        'is_owner' => false,
                        'member_role' => null,
                    ];
                }
            }
            return;
        }

        $communityIds = [];
        foreach ($items as $community) {
            if ($community instanceof self && $community->id) {
                $communityIds[] = $community->id;
            }
        }

        $communityIds = array_unique($communityIds);
        if (empty($communityIds)) {
            return;
        }

        // Single efficient query to fetch all active or pending memberships for this member
        $memberships = CommunityMember::query()
            ->where('member_id', $memberId)
            ->whereIn('community_id', $communityIds)
            ->get()
            ->keyBy('community_id');

        foreach ($items as $community) {
            if (! ($community instanceof self)) {
                continue;
            }

            $isOwner = (int) $community->owner_id === (int) $memberId;
            $membership = $memberships->get($community->id);

            if ($isOwner) {
                $community->membership_state = [
                    'is_member' => true,
                    'is_pending' => false,
                    'is_owner' => true,
                    'member_role' => 'owner',
                ];
            } elseif ($membership) {
                $isAccepted = $membership->status === 'accepted';
                $isPending = $membership->status === 'pending';
                $community->membership_state = [
                    'is_member' => $isAccepted,
                    'is_pending' => $isPending,
                    'is_owner' => false,
                    'member_role' => $membership->role ?: ($isAccepted ? 'member' : null),
                ];
            } else {
                $community->membership_state = [
                    'is_member' => false,
                    'is_pending' => false,
                    'is_owner' => false,
                    'member_role' => null,
                ];
            }
        }
    }
}
