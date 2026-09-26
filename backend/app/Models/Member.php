<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'user_id',
        'introducer_id',
        'direct_referral_count',
        'referral_counted_at',
        'email',
        'password',
        'google_id',
        'phone',
        'mobile_verified_at',
        'mobile_verification_requested_at',
        'p2p_wallet',
        'wallet',
        'wallet_address',
        'reward_wallet_network',
        'reward_wallet_currency',
        'reward_wallet_verified_at',
        'bio',
        'date_of_birth',
        'gender',
        'city',
        'country',
        'website',
        'profile_photo',
        'cover_photo',
        'last_seen_at',
        'blocked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'mobile_verified_at' => 'datetime',
            'mobile_verification_requested_at' => 'datetime',
            'reward_wallet_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'blocked_at' => 'datetime',
            'direct_referral_count' => 'integer',
            'referral_counted_at' => 'datetime',
            'p2p_wallet' => 'float',
            'wallet' => 'float',
            'password' => 'hashed',
        ];
    }

    public function getFundWalletAttribute(): float
    {
        return (float) ($this->p2p_wallet ?? 0.00);
    }

    public function creditP2pWallet(float $amount): float
    {
        $this->p2p_wallet = round((float) ($this->p2p_wallet ?? 0.00) + max(0.00, $amount), 2);
        $this->save();
        return (float) $this->p2p_wallet;
    }

    public function creditWallet(float $amount): float
    {
        $this->wallet = round((float) ($this->wallet ?? 0.00) + max(0.00, $amount), 2);
        $this->save();
        return (float) $this->wallet;
    }

    public function hasVerifiedRewardWallet(): bool
    {
        return !empty($this->wallet_address ?? null) && $this->reward_wallet_verified_at !== null;
    }

    public function creditAdBalance(float $amount): float
    {
        return $this->creditP2pWallet($amount);
    }

    public function creditRewardBalance(float $amount): float
    {
        return $this->creditWallet($amount);
    }

    public function hasAdBalance(float $amount): bool
    {
        return ((float) ($this->p2p_wallet ?? 0.00)) >= $amount;
    }

    public function adRewards(): HasMany
    {
        return $this->hasMany(AdReward::class, 'member_id');
    }

    public function feedbackSuggestions(): HasMany
    {
        return $this->hasMany(FeedbackSuggestion::class, 'member_id');
    }
    
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'member_id');
    }


    protected $appends = [
        'avatar_url',
        'profile_photo_url',
        'cover_photo_url',
        'cover_banner_url',
        'is_verified',
        'is_verification_pending',
        'verification_status',
        'is_blocked',
        'status',
        'fund_wallet',
    ];

    public function getIsVerifiedAttribute(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    public function getIsVerificationPendingAttribute(): bool
    {
        return $this->isMobileVerificationPending();
    }

    public function getVerificationStatusAttribute(): string
    {
        if ($this->mobile_verified_at !== null) {
            return 'verified';
        }

        if ($this->mobile_verification_requested_at !== null) {
            return 'pending';
        }

        return 'unverified';
    }

    public function getIsBlockedAttribute(): bool
    {
        return $this->blocked_at !== null;
    }

    public function getStatusAttribute(): string
    {
        if ($this->blocked_at !== null) {
            return 'blocked';
        }

        if ($this->mobile_verified_at !== null) {
            return 'active';
        }

        return 'unverified';
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->user_id)) {
                $member->user_id = app(\App\Services\MemberUserIdService::class)->generateFromName($member->name ?? 'member');
            }
        });
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isMobileVerified(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    public function isMobileVerificationPending(): bool
    {
        return $this->mobile_verified_at === null && $this->mobile_verification_requested_at !== null;
    }

    /**
     * Check if member is socially eligible for discovery and new connections (verified and active).
     */
    public function isSociallyEligible(): bool
    {
        return $this->isMobileVerified() && ! $this->isBlocked();
    }

    /**
     * Scope query to members who are socially eligible (verified and active).
     */
    public function scopeSociallyEligible(Builder $query): Builder
    {
        return $query->whereNotNull('mobile_verified_at')
            ->whereNull('blocked_at');
    }

    public function verificationOtps(): HasMany
    {
        return $this->hasMany(MemberVerificationOtp::class);
    }

    public function businessPages(): HasMany
    {
        return $this->hasMany(BusinessPage::class, 'member_id');
    }

    public function businessTeamMemberships(): HasMany
    {
        return $this->hasMany(BusinessTeamMember::class, 'member_id');
    }

    public function receivedBusinessInvitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class, 'invitee_id');
    }

    public function followedBusinessPages(): HasMany
    {
        return $this->hasMany(BusinessFollower::class, 'member_id');
    }

    public function businessReviews(): HasMany
    {
        return $this->hasMany(BusinessReview::class, 'member_id');
    }

    public function customerBusinessConversations(): HasMany
    {
        return $this->hasMany(BusinessConversation::class, 'customer_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follower::class, 'following_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(Follower::class, 'follower_id');
    }

    public function friendshipsAsFirst(): HasMany
    {
        return $this->hasMany(Friendship::class, 'member_one_id');
    }

    public function friendshipsAsSecond(): HasMany
    {
        return $this->hasMany(Friendship::class, 'member_two_id');
    }

    public function sentDirectMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    public function receivedDirectMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'receiver_id');
    }

    public function unreadDirectMessagesCount(): int
    {
        return $this->receivedDirectMessages()->where('is_read', false)->count();
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requested_by_id');
    }

    public function storyViews(): HasMany
    {
        return $this->hasMany(StoryView::class, 'viewer_member_id');
    }

    public function storyLikes(): HasMany
    {
        return $this->hasMany(StoryLike::class, 'member_id');
    }

    public function storyReactions(): HasMany
    {
        return $this->hasMany(StoryReaction::class, 'member_id');
    }

    public function sentStoryReplies(): HasMany
    {
        return $this->hasMany(StoryReply::class, 'sender_id');
    }

    public function receivedStoryReplies(): HasMany
    {
        return $this->hasMany(StoryReply::class, 'receiver_id');
    }

    public function postLikes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'member_id');
    }

    public function postReactions(): HasMany
    {
        return $this->hasMany(PostReaction::class, 'member_id');
    }

    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class, 'member_id');
    }

    public function commentReactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class, 'member_id');
    }

    public function sharedPosts(): HasMany
    {
        return $this->hasMany(PostShare::class, 'shared_by');
    }

    public function savedPosts(): HasMany
    {
        return $this->hasMany(SavedPost::class, 'member_id');
    }

    public function hiddenPosts(): HasMany
    {
        return $this->hasMany(HiddenPost::class, 'member_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReportedPost::class, 'member_id');
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'member_id');
    }

    public function profileVisits(): HasMany
    {
        return $this->hasMany(ProfileVisit::class, 'profile_owner_id');
    }

    public function communityMemberships(): HasMany
    {
        return $this->hasMany(CommunityMember::class, 'member_id');
    }

    public function joinedCommunities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_members', 'member_id', 'community_id')
            ->wherePivot('status', 'accepted')
            ->withPivot(['role', 'status', 'notification_level', 'joined_at'])
            ->withTimestamps();
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'member_id');
    }

    public function groupInvitations(): HasMany
    {
        return $this->hasMany(GroupInvitation::class, 'invited_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function eventResponses(): HasMany
    {
        return $this->hasMany(EventResponse::class, 'member_id');
    }

    public function eventInvitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class, 'invited_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'member_id');
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class, 'member_id');
    }

    public function adCampaignActivities(): HasMany
    {
        return $this->hasMany(AdCampaignActivity::class, 'member_id');
    }

    public function hasBlocked(int $memberId): bool
    {
        return $this->blockedUsers()->where('blocked_member_id', $memberId)->exists();
    }

    public function isBlockedBy(int $memberId): bool
    {
        return BlockedUser::query()->where('member_id', $memberId)->where('blocked_member_id', $this->id)->exists();
    }

    public function acceptedFriendIds(?int $memberId = null): array
    {
        $targetId = $memberId ?? $this->id;
        if (! $targetId) {
            return [];
        }

        $f1 = Friendship::query()->forMember($targetId)->accepted()->get(['member_one_id', 'member_two_id']);

        return $f1->map(fn (Friendship $f) => $f->member_one_id === $targetId ? $f->member_two_id : $f->member_one_id)->all();
    }

    /**
     * Get IDs of members with an active accepted connection, excluding blocked/disconnected relationships.
     */
    public function acceptedConnectionIds(bool $verifiedOnly = true): array
    {
        $rawFriendIds = collect($this->acceptedFriendIds());
        if ($rawFriendIds->isEmpty()) {
            return [];
        }

        // Exclude blocked members in either direction
        $blockedIds = BlockedUser::query()
            ->where('member_id', $this->id)
            ->pluck('blocked_member_id')
            ->concat(
                BlockedUser::query()
                    ->where('blocked_member_id', $this->id)
                    ->pluck('member_id')
            );

        $activeFriendIds = $rawFriendIds->diff($blockedIds)->values();
        if ($activeFriendIds->isEmpty()) {
            return [];
        }

        if ($verifiedOnly) {
            return Member::query()
                ->whereIn('id', $activeFriendIds)
                ->sociallyEligible()
                ->pluck('id')
                ->all();
        }

        return $activeFriendIds->all();
    }

    /**
     * Get member IDs eligible to appear in the authenticated member's connection Watch feed.
     * Contains the authenticated member themselves plus their active accepted, verified, non-blocked connections.
     */
    public function watchEligibleMemberIds(): array
    {
        $connectionIds = $this->acceptedConnectionIds(true);

        return array_values(array_unique(array_merge([$this->id], $connectionIds)));
    }

    public function mutualFriendIds(int $otherMemberId): array
    {
        if (! $this->id || ! $otherMemberId) {
            return [];
        }

        $myFriends = $this->acceptedFriendIds();
        $otherFriends = $this->acceptedFriendIds($otherMemberId);

        return array_values(array_intersect($myFriends, $otherFriends));
    }

    public function mutualFriendsCount(int $otherMemberId): int
    {
        return count($this->mutualFriendIds($otherMemberId));
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3));
    }

    public function onlineStatusLabel(): string
    {
        if ($this->isOnline()) {
            return 'Active Now';
        }
        return $this->last_seen_at ? 'Active '.$this->last_seen_at->diffForHumans() : 'Offline';
    }

    /**
     * Get the full URL to the member's uploaded profile photo, or null if not available/exists.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        $photo = ltrim(str_replace('\\', '/', $this->profile_photo), '/');

        if (str_contains($photo, '..')) {
            return null;
        }

        // Handle full URLs (Google avatars, external URLs, CDN)
        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            // Dynamically upgrade old test domain to production canonical URL
            if (str_contains($photo, 'mytesting.fun')) {
                $appUrl = rtrim((string) config('app.url', 'https://mlmbookai.com'), '/');
                $photo = str_replace(['http://mytesting.fun', 'https://mytesting.fun'], $appUrl, $photo);
            }
            // Ensure production domain uses HTTPS
            if (str_starts_with($photo, 'http://mlmbookai.com')) {
                $photo = 'https://' . substr($photo, 7);
            }
            return $photo;
        }

        $version = $this->updated_at?->timestamp ?? now()->timestamp;

        if (str_starts_with($photo, 'uploads/profile/')) {
            $filename = basename($photo);
            $cleanPath = 'uploads/profile/'.$filename;
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath).'?v='.$version;
            }
            return asset($cleanPath);
        }

        if (str_starts_with($photo, 'member/profile-photos/')) {
            $cleanPath = 'storage/'.$photo;
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath).'?v='.$version;
            }
            return asset($cleanPath);
        }

        if (str_starts_with($photo, 'storage/')) {
            if (file_exists(public_path($photo))) {
                return asset($photo).'?v='.$version;
            }
            return asset($photo);
        }

        if (! str_starts_with($photo, 'uploads/') && ! str_starts_with($photo, 'storage/') && ! str_starts_with($photo, 'member/')) {
            $cleanPath = 'uploads/profile/'.basename($photo);
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath).'?v='.$version;
            }
            return asset($cleanPath);
        }

        if (file_exists(public_path($photo))) {
            return asset($photo).'?v='.$version;
        }

        return asset($photo);
    }

    /**
     * Get the full URL to the member's uploaded cover photo, or null if not available/exists.
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        if (! $this->cover_photo) {
            return null;
        }

        $photo = ltrim(str_replace('\\', '/', $this->cover_photo), '/');

        if (str_contains($photo, '..')) {
            return null;
        }

        // Handle full URLs (external URLs, CDN)
        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            if (str_contains($photo, 'mytesting.fun')) {
                $appUrl = rtrim((string) config('app.url', 'https://mlmbookai.com'), '/');
                $photo = str_replace(['http://mytesting.fun', 'https://mytesting.fun'], $appUrl, $photo);
            }
            if (str_starts_with($photo, 'http://mlmbookai.com')) {
                $photo = 'https://' . substr($photo, 7);
            }
            return $photo;
        }

        $version = $this->updated_at?->timestamp ?? now()->timestamp;

        if (str_starts_with($photo, 'uploads/cover/')) {
            $filename = basename($photo);
            $cleanPath = 'uploads/cover/'.$filename;
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath).'?v='.$version;
            }
            return asset($cleanPath);
        }

        if (str_starts_with($photo, 'member/cover-photos/')) {
            $cleanPath = 'storage/'.$photo;
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath).'?v='.$version;
            }
            return asset($cleanPath);
        }

        if (str_starts_with($photo, 'storage/')) {
            if (file_exists(public_path($photo))) {
                return asset($photo).'?v='.$version;
            }
            return asset($photo);
        }

        if (! str_starts_with($photo, 'uploads/') && ! str_starts_with($photo, 'storage/') && ! str_starts_with($photo, 'member/')) {
            $cleanPath = 'uploads/cover/'.basename($photo);
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath).'?v='.$version;
            }
            return asset($cleanPath);
        }

        if (file_exists(public_path($photo))) {
            return asset($photo).'?v='.$version;
        }

        return asset($photo);
    }

    /**
     * Get the full URL to the member's uploaded cover banner (alias for cover_photo_url).
     */
    public function getCoverBannerUrlAttribute(): ?string
    {
        return $this->cover_photo_url;
    }

    /**
     * Get avatar image URL (uploaded profile photo or default placeholder image).
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->profile_photo_url ?? asset('member_assets/images/dashboard/image/profile.png');
    }

    /**
     * Get 1-2 uppercase initials from the member's name.
     */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name ?? 'Member'));
        $first = mb_substr($parts[0] ?? 'M', 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';
        $res = mb_strtoupper($first.$second);

        return $res !== '' ? $res : 'M';
    }

    /**
     * Get the introducing member (sponsor / referrer).
     */
    public function introducer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class, 'introducer_id', 'user_id');
    }

    /**
     * Get the directly referred members.
     */
    public function directReferrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Member::class, 'introducer_id', 'user_id');
    }

    /**
     * Get only the directly referred members who have completed mobile verification.
     */
    public function verifiedDirectReferrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Member::class, 'introducer_id', 'user_id')
            ->whereNotNull('mobile_verified_at');
    }

    /**
     * Get the authoritative count of direct verified referrals.
     */
    public function getVerifiedDirectReferralCount(): int
    {
        return app(\App\Services\RewardRuleResolver::class)->getVerifiedDirectReferralCount($this);
    }

    /**
     * Resolve eligible ad reward slab based on direct verified referral count.
     */
    public function resolveEligibleAdReward(): array
    {
        return app(\App\Services\RewardRuleResolver::class)->resolveForMember($this);
    }

    /**
     * Determine if this member is eligible to refer other members (requires verified mobile).
     */
    public function isEligibleToRefer(): bool
    {
        return $this->isMobileVerified();
    }

    /**
     * Qualify and atomically count referral attribution upon successful mobile verification.
     * Idempotent: Executes exactly once per member under concurrency.
     */
    public function qualifyReferral(): bool
    {
        if (empty($this->introducer_id) || $this->referral_counted_at !== null || ! $this->isMobileVerified()) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () {
            /** @var Member|null $fresh */
            $fresh = Member::where('id', $this->id)
                ->whereNull('referral_counted_at')
                ->whereNotNull('mobile_verified_at')
                ->lockForUpdate()
                ->first();

            if (! $fresh || empty($fresh->introducer_id) || $fresh->referral_counted_at !== null || ! $fresh->isMobileVerified()) {
                return false;
            }

            /** @var Member|null $introducer */
            $introducer = Member::where('user_id', $fresh->introducer_id)
                ->whereNotNull('mobile_verified_at')
                ->lockForUpdate()
                ->first();

            $fresh->update(['referral_counted_at' => now()]);

            if ($introducer) {
                $introducer->increment('direct_referral_count');
                return true;
            }

            return false;
        });
    }

    /**
     * Virtual accessor for legacy references to ad_balance. Maps directly to p2p_wallet (Fund Wallet).
     */
    public function getAdBalanceAttribute(): float
    {
        return (float) ($this->attributes['p2p_wallet'] ?? 0.00);
    }
}
