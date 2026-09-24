<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessPage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @deprecated Use BusinessPageCategory::getActiveCategoryNames() or BusinessPage::categories() instead.
     * Hardcoded category constant is deprecated in favor of Admin-managed business_page_categories SSOT.
     */
    public const CATEGORIES = [];

    public static function categories(): array
    {
        return BusinessPageCategory::getActiveCategoryNames();
    }

    public static function categorySlug(string $category): string
    {
        return Str::slug($category);
    }

    public static function findCategoryBySlug(string $slug): ?string
    {
        return BusinessPageCategory::resolveCanonicalName($slug);
    }

    public const VISIBILITIES = [
        'public' => 'Public',
        'private' => 'Private',
        'draft' => 'Draft',
    ];

    public const COUNTRIES = [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Argentina', 'Armenia', 'Australia', 'Austria',
        'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin',
        'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso',
        'Burundi', 'Cambodia', 'Cameroon', 'Canada', 'Chile', 'China', 'Colombia', 'Costa Rica', 'Croatia',
        'Cuba', 'Cyprus', 'Czech Republic', 'Denmark', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador',
        'Estonia', 'Ethiopia', 'Fiji', 'Finland', 'France', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Guatemala',
        'Honduras', 'Hong Kong', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel',
        'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia',
        'Lebanon', 'Libya', 'Lithuania', 'Luxembourg', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Mexico',
        'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Myanmar', 'Nepal', 'Netherlands', 'New Zealand',
        'Nicaragua', 'Nigeria', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palestine', 'Panama', 'Paraguay',
        'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saudi Arabia',
        'Senegal', 'Serbia', 'Singapore', 'Slovakia', 'Slovenia', 'South Africa', 'South Korea', 'Spain',
        'Sri Lanka', 'Sudan', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand',
        'Tunisia', 'Turkey', 'Turkmenistan', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom',
        'United States', 'Uruguay', 'Uzbekistan', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'
    ];

    public const DIALING_CODES = [
        ['code' => '+91', 'country' => 'India', 'flag' => '🇮🇳'],
        ['code' => '+1', 'country' => 'United States / Canada', 'flag' => '🇺🇸'],
        ['code' => '+44', 'country' => 'United Kingdom', 'flag' => '🇬🇧'],
        ['code' => '+971', 'country' => 'United Arab Emirates', 'flag' => '🇦🇪'],
        ['code' => '+61', 'country' => 'Australia', 'flag' => '🇦🇺'],
        ['code' => '+49', 'country' => 'Germany', 'flag' => '🇩🇪'],
        ['code' => '+33', 'country' => 'France', 'flag' => '🇫🇷'],
        ['code' => '+81', 'country' => 'Japan', 'flag' => '🇯🇵'],
        ['code' => '+86', 'country' => 'China', 'flag' => '🇨🇳'],
        ['code' => '+966', 'country' => 'Saudi Arabia', 'flag' => '🇸🇦'],
        ['code' => '+65', 'country' => 'Singapore', 'flag' => '🇸🇬'],
        ['code' => '+27', 'country' => 'South Africa', 'flag' => '🇿🇦'],
        ['code' => '+55', 'country' => 'Brazil', 'flag' => '🇧🇷'],
        ['code' => '+7', 'country' => 'Russia', 'flag' => '🇷🇺'],
        ['code' => '+39', 'country' => 'Italy', 'flag' => '🇮🇹'],
        ['code' => '+34', 'country' => 'Spain', 'flag' => '🇪🇸'],
        ['code' => '+82', 'country' => 'South Korea', 'flag' => '🇰🇷'],
        ['code' => '+92', 'country' => 'Pakistan', 'flag' => '🇵🇰'],
        ['code' => '+880', 'country' => 'Bangladesh', 'flag' => '🇧🇩'],
        ['code' => '+94', 'country' => 'Sri Lanka', 'flag' => '🇱🇰'],
        ['code' => '+977', 'country' => 'Nepal', 'flag' => '🇳🇵'],
        ['code' => '+60', 'country' => 'Malaysia', 'flag' => '🇲🇾'],
        ['code' => '+62', 'country' => 'Indonesia', 'flag' => '🇮🇩'],
        ['code' => '+63', 'country' => 'Philippines', 'flag' => '🇵🇭'],
        ['code' => '+84', 'country' => 'Vietnam', 'flag' => '🇻🇳'],
        ['code' => '+90', 'country' => 'Turkey', 'flag' => '🇹🇷'],
        ['code' => '+20', 'country' => 'Egypt', 'flag' => '🇪🇬'],
        ['code' => '+234', 'country' => 'Nigeria', 'flag' => '🇳🇬'],
        ['code' => '+254', 'country' => 'Kenya', 'flag' => '🇰🇪'],
        ['code' => '+965', 'country' => 'Kuwait', 'flag' => '🇰🇼'],
        ['code' => '+968', 'country' => 'Oman', 'flag' => '🇴🇲'],
        ['code' => '+974', 'country' => 'Qatar', 'flag' => '🇶🇦'],
        ['code' => '+973', 'country' => 'Bahrain', 'flag' => '🇧🇭'],
    ];

    protected $fillable = [
        'page_id',
        'member_id',
        'page_name',
        'page_username',
        'slug',
        'category',
        'description',
        'website',
        'email',
        'phone',
        'country',
        'state',
        'city',
        'address',
        'logo',
        'cover_photo',
        'visibility',
        'status',
        'is_verified',
        'is_featured',
        'trending_score',
        'seo_title',
        'meta_description',
        'social_links',
        'business_hours',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'trending_score' => 'float',
            'social_links' => 'array',
            'business_hours' => 'array',
        ];
    }

    protected $appends = [
        'logo_url',
        'cover_url',
        'initials',
        'formatted_location',
    ];

    public function verifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessVerification::class, 'business_page_id');
    }

    public function latestVerification(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BusinessVerification::class, 'business_page_id')->latestOfMany();
    }

    public function verificationStatus(): string
    {
        if ($this->is_verified) {
            return 'verified';
        }

        $latest = $this->latestVerification;

        return $latest ? $latest->status : 'not_verified';
    }

    public function isVerificationPending(): bool
    {
        return $this->verificationStatus() === 'pending';
    }

    public function calculateTrendingScore(): float
    {
        $followers = $this->followersCount();
        $posts = Post::where('business_page_id', $this->id)->count();
        $reviews = $this->reviewsCount();
        $avgRating = $this->averageRating();

        $score = ($followers * 3.0) + ($posts * 2.0) + ($reviews * 5.0) + ($avgRating * 4.0);
        $this->update(['trending_score' => $score]);

        return $score;
    }

    public function scopePublicPages($query)
    {
        return $query->where('visibility', 'public')->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeTrending($query)
    {
        return $query->orderBy('trending_score', 'desc');
    }

    public function scopeCategory($query, ?string $category)
    {
        if (filled($category)) {
            return $query->where('category', $category);
        }

        return $query;
    }

    public function scopeSearchFilter($query, array $filters)
    {
        $q = trim($filters['q'] ?? '');
        $cat = trim($filters['category'] ?? '');
        $country = trim($filters['country'] ?? '');
        $state = trim($filters['state'] ?? '');
        $city = trim($filters['city'] ?? '');
        $sort = trim($filters['sort'] ?? 'popular');
        $verifiedOnly = ! empty($filters['verified_only']);

        if (filled($q)) {
            $query->where(function ($subq) use ($q) {
                $subq->where('page_name', 'like', "%{$q}%")
                    ->orWhere('page_username', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('state', 'like', "%{$q}%")
                    ->orWhere('country', 'like', "%{$q}%")
                    ->orWhereHas('owner', function ($mq) use ($q) {
                        $mq->where('name', 'like', "%{$q}%");
                    });
            });
        }

        if (filled($cat)) {
            $query->where('category', $cat);
        }

        if (filled($country)) {
            $query->where('country', 'like', "%{$country}%");
        }

        if (filled($state)) {
            $query->where('state', 'like', "%{$state}%");
        }

        if (filled($city)) {
            $query->where('city', 'like', "%{$city}%");
        }

        if ($verifiedOnly) {
            $query->where('is_verified', true);
        }

        if ($sort === 'newest') {
            $query->latest();
        } elseif ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'alphabetical') {
            $query->orderBy('page_name', 'asc');
        } elseif ($sort === 'rating') {
            $query->orderBy('page_name', 'asc'); // Ratings dynamic ordering
        } else {
            $query->orderBy('trending_score', 'desc')->latest();
        }

        return $query;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Post::class, 'business_page_id');
    }

    public function adCampaigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdCampaign::class, 'business_page_id');
    }

    public function teamMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessTeamMember::class, 'business_page_id');
    }

    public function activeTeamMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessTeamMember::class, 'business_page_id')->where('status', 'active');
    }

    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessInvitation::class, 'business_page_id');
    }

    public function followers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessFollower::class, 'business_page_id');
    }

    public function acceptedFollowers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessFollower::class, 'business_page_id')->where('status', 'accepted');
    }

    public function pendingFollowRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessFollower::class, 'business_page_id')->where('status', 'pending');
    }

    public function followerInvitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessFollowerInvitation::class, 'business_page_id');
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessReview::class, 'business_page_id');
    }

    public function visibleReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessReview::class, 'business_page_id')->where('is_hidden', false);
    }

    public function conversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessConversation::class, 'business_page_id');
    }

    public function quickReplies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessQuickReply::class, 'business_page_id');
    }

    public function businessNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessNotification::class, 'business_page_id');
    }

    public function analyticsViews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessPageAnalyticsView::class, 'business_page_id');
    }

    public function analyticsSnapshots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessPageAnalyticsSnapshot::class, 'business_page_id');
    }

    public function recordView(?int $memberId = null): void
    {
        $request = request();
        $agent = $request->header('User-Agent', '');

        $deviceType = 'desktop';
        if (preg_match('/(android|bb\d+|meego).+mobile|avail|blackberry|emulator|iphone|ipod|sm-t|palm|phone|ipad|tablet/i', $agent)) {
            $deviceType = preg_match('/ipad|tablet|playbook|silk/i', $agent) ? 'tablet' : 'mobile';
        }

        $this->analyticsViews()->create([
            'member_id' => $memberId,
            'ip_address' => $request->ip(),
            'user_agent' => substr($agent, 0, 255),
            'device_type' => $deviceType,
            'country' => $this->country ?: 'Global',
            'city' => $this->city ?: 'Online',
            'viewed_at' => now(),
        ]);
    }

    public function calculateHealthScores(string $period = '30days'): array
    {
        $totalFollowers = $this->followersCount();
        $totalReviews = $this->reviewsCount();
        $avgRating = $this->averageRating();
        $totalPosts = Post::where('business_page_id', $this->id)->count();

        $growthScore = min(100, round(($totalFollowers * 2.5) + ($totalPosts * 1.5)));
        $engagementScore = min(100, round(($totalPosts * 4.0) + ($totalReviews * 3.0)));
        $satisfactionScore = $totalReviews > 0 ? round(($avgRating / 5.0) * 100) : 100;
        $healthIndex = round(($growthScore * 0.3) + ($engagementScore * 0.3) + ($satisfactionScore * 0.4));

        return [
            'health_score' => max(1, min(100, $healthIndex)),
            'growth_score' => max(1, min(100, $growthScore)),
            'engagement_score' => max(1, min(100, $engagementScore)),
            'satisfaction_score' => max(1, min(100, $satisfactionScore)),
        ];
    }

    public function unreadMessagesCount(): int
    {
        return BusinessMessage::whereHas('conversation', function ($q) {
            $q->where('business_page_id', $this->id);
        })->where('sender_type', 'customer')->where('is_read', false)->count();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->businessNotifications()->where('is_read', false)->count();
    }

    public function canAccessInbox(?int $memberId): bool
    {
        $role = $this->getTeamRole($memberId);

        return in_array($role, ['owner', 'admin', 'editor', 'moderator'], true);
    }

    public function canReplyInInbox(?int $memberId): bool
    {
        $role = $this->getTeamRole($memberId);

        return in_array($role, ['owner', 'admin', 'editor'], true);
    }

    public function isOwner(?int $memberId): bool
    {
        if (! $memberId) {
            return false;
        }

        return (int) $this->member_id === (int) $memberId;
    }

    public function averageRating(): float
    {
        $avg = $this->visibleReviews()->avg('rating');

        return $avg ? round((float) $avg, 1) : 0.0;
    }

    public function reviewsCount(): int
    {
        return $this->visibleReviews()->count();
    }

    public function ratingDistribution(): array
    {
        $total = $this->reviewsCount();
        $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        if ($total === 0) {
            return $dist;
        }

        $counts = $this->visibleReviews()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        foreach ($dist as $star => &$val) {
            $val = $counts[$star] ?? 0;
        }

        return $dist;
    }

    public function recommendationBadge(): string
    {
        $total = $this->reviewsCount();

        if ($total === 0) {
            return 'No Reviews Yet';
        }

        $avg = $this->averageRating();
        $recommendCount = $this->visibleReviews()->where('recommendation', 'recommend')->count();
        $ratio = ($recommendCount / $total) * 100;

        if ($avg >= 4.5 && $ratio >= 80) {
            return 'Highly Recommended';
        }

        if ($avg >= 3.5 && $ratio >= 60) {
            return 'Recommended';
        }

        if ($avg >= 2.5) {
            return 'Mixed Feedback';
        }

        return 'Needs Improvement';
    }

    public function hasReviewedBy(?int $memberId): bool
    {
        if (! $memberId) {
            return false;
        }

        return $this->reviews()->where('member_id', $memberId)->exists();
    }

    public function userReview(?int $memberId): ?BusinessReview
    {
        if (! $memberId) {
            return null;
        }

        return $this->reviews()->where('member_id', $memberId)->first();
    }

    public function isFollowedBy(?int $memberId): bool
    {
        if (! $memberId) {
            return false;
        }

        return $this->acceptedFollowers()->where('member_id', $memberId)->exists();
    }

    public function hasPendingFollowRequestFrom(?int $memberId): bool
    {
        if (! $memberId) {
            return false;
        }

        return $this->pendingFollowRequests()->where('member_id', $memberId)->exists();
    }

    public function getFollowStatus(?int $memberId): string
    {
        if (! $memberId) {
            return 'none';
        }

        $record = $this->followers()->where('member_id', $memberId)->first();

        return $record?->status ?? 'none';
    }

    public function followersCount(): int
    {
        return $this->acceptedFollowers()->count();
    }

    public function getTeamRole(?int $memberId): ?string
    {
        if (! $memberId) {
            return null;
        }

        if ($this->isOwner($memberId)) {
            return 'owner';
        }

        $memberRecord = $this->activeTeamMembers()->where('member_id', $memberId)->first();

        return $memberRecord?->role;
    }

    public function isTeamMember(?int $memberId): bool
    {
        if (! $memberId) {
            return false;
        }

        if ($this->isOwner($memberId)) {
            return true;
        }

        return $this->activeTeamMembers()->where('member_id', $memberId)->exists();
    }

    public function isTeamAdmin(?int $memberId): bool
    {
        $role = $this->getTeamRole($memberId);

        return in_array($role, ['owner', 'admin'], true);
    }

    public function hasTeamPermission(?int $memberId, string $permission): bool
    {
        $role = $this->getTeamRole($memberId);

        if (! $role) {
            return false;
        }

        if ($role === 'owner') {
            return true;
        }

        return match ($permission) {
            'manage_team', 'invite_members', 'manage_info' => in_array($role, ['admin'], true),
            'publish_posts', 'upload_media' => in_array($role, ['admin', 'editor'], true),
            'moderate_comments' => in_array($role, ['admin', 'moderator'], true),
            'view_analytics' => in_array($role, ['admin', 'analyst'], true),
            default => false,
        };
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    public function isDraft(): bool
    {
        return $this->visibility === 'draft';
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            if (str_contains($this->logo, 'mytesting.fun')) {
                $appUrl = rtrim((string) config('app.url', 'https://mlmbookai.com'), '/');
                return str_replace(['http://mytesting.fun', 'https://mytesting.fun'], $appUrl, $this->logo);
            }
            if (str_starts_with($this->logo, 'http://mlmbookai.com')) {
                return 'https://' . substr($this->logo, 7);
            }
            return $this->logo;
        }

        if (file_exists(public_path($this->logo))) {
            return asset($this->logo);
        }

        return asset($this->logo);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_photo) {
            return null;
        }

        if (str_starts_with($this->cover_photo, 'http://') || str_starts_with($this->cover_photo, 'https://')) {
            if (str_contains($this->cover_photo, 'mytesting.fun')) {
                $appUrl = rtrim((string) config('app.url', 'https://mlmbookai.com'), '/');
                return str_replace(['http://mytesting.fun', 'https://mytesting.fun'], $appUrl, $this->cover_photo);
            }
            if (str_starts_with($this->cover_photo, 'http://mlmbookai.com')) {
                return 'https://' . substr($this->cover_photo, 7);
            }
            return $this->cover_photo;
        }

        if (file_exists(public_path($this->cover_photo))) {
            return asset($this->cover_photo);
        }

        return asset($this->cover_photo);
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->page_name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('') ?: 'BP';
    }

    public function getFormattedLocationAttribute(): string
    {
        return collect([$this->address, $this->city, $this->state, $this->country])
            ->filter()
            ->implode(', ');
    }
}
