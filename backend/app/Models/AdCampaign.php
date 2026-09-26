<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdCampaign extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_BUDGET_EXHAUSTED = 'budget_exhausted';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING_REVIEW => 'Pending Review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_STOPPED => 'Stopped',
        self::STATUS_BUDGET_EXHAUSTED => 'Budget Exhausted',
    ];

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING => 'Pending',
        self::APPROVAL_APPROVED => 'Approved',
        self::APPROVAL_REJECTED => 'Rejected',
    ];

    public const TYPE_BUSINESS_PAGE = 'business_ad';
    public const TYPE_BUSINESS_AD = 'business_ad';
    public const TYPE_EVENT = 'event';

    public const CAMPAIGN_TYPES = [
        self::TYPE_BUSINESS_PAGE => 'Business Ad',
        self::TYPE_EVENT => 'Event Campaign',
    ];

    /**
     * Fixed verified member landing-page visit reward in USD.
     */
    public const REWARD_AMOUNT = 0.05;

    protected $fillable = [
        'campaign_id',
        'campaign_type',
        'business_page_id',
        'member_id',
        'post_id',
        'event_id',
        'campaign_name',
        'budget',
        'additional_funding',
        'total_funded',
        'currency',
        'fee_percent',
        'fee_amount',
        'wallet_debit',
        'spent_amount',
        'remaining_amount',
        'target_audience',
        'start_at',
        'end_at',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $appends = [
        'is_budget_low',
        'is_budget_exhausted',
        'display_status',
        'promoted_post',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:4',
            'additional_funding' => 'decimal:4',
            'total_funded' => 'decimal:4',
            'fee_percent' => 'decimal:2',
            'fee_amount' => 'decimal:4',
            'wallet_debit' => 'decimal:4',
            'spent_amount' => 'decimal:4',
            'remaining_amount' => 'decimal:4',
            'target_audience' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AdCampaign $campaign) {
            if (empty($campaign->campaign_type)) {
                $campaign->campaign_type = self::TYPE_BUSINESS_PAGE;
            }
            if (empty($campaign->campaign_id)) {
                $prefix = $campaign->campaign_type === self::TYPE_EVENT ? 'evcamp_' : 'camp_';
                $campaign->campaign_id = $prefix . Str::lower(Str::random(12));
            }
            if (!isset($campaign->additional_funding)) {
                $campaign->additional_funding = 0.00;
            }
            if (!isset($campaign->total_funded)) {
                $campaign->total_funded = round((float) $campaign->budget + (float) $campaign->additional_funding, 4);
            }
            if (!isset($campaign->spent_amount)) {
                $campaign->spent_amount = 0.00;
            }
            if (!isset($campaign->remaining_amount)) {
                $campaign->remaining_amount = max(0.00, round((float) $campaign->total_funded - (float) $campaign->spent_amount, 4));
            }
        });

        static::saving(function (AdCampaign $campaign) {
            if (isset($campaign->budget)) {
                $additional = (float) ($campaign->additional_funding ?? 0.00);
                $totalFunded = round((float) $campaign->budget + $additional, 4);
                $campaign->total_funded = $totalFunded;

                $spent = (float) ($campaign->spent_amount ?? 0.00);
                $campaign->remaining_amount = max(0.00, round($totalFunded - $spent, 4));
            }
        });
    }

    public function getIsBudgetLowAttribute(): bool
    {
        $remaining = (float) ($this->remaining_amount ?? 0.00);
        $minReward = AdRewardRule::getMinimumActiveRewardAmount();
        $threshold = (float) Setting::get('ad_campaign_low_budget_threshold', 1.00);
        return $remaining >= $minReward && $remaining <= $threshold;
    }

    public function getIsBudgetExhaustedAttribute(): bool
    {
        $remaining = (float) ($this->remaining_amount ?? 0.00);
        $minReward = AdRewardRule::getMinimumActiveRewardAmount();
        return $remaining < $minReward || $this->status === self::STATUS_BUDGET_EXHAUSTED;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->is_budget_exhausted) {
            return 'Budget Exhausted';
        }
        if ($this->is_budget_low && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_APPROVED], true)) {
            return 'Budget Low';
        }
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function promotedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function getPromotedPostAttribute(): ?Post
    {
        if ($this->relationLoaded('post')) {
            return $this->getRelation('post');
        }
        if ($this->relationLoaded('promotedPost')) {
            return $this->getRelation('promotedPost');
        }
        return $this->post_id ? $this->post : null;
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class, 'ad_campaign_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdClick::class, 'ad_campaign_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(AdReward::class, 'ad_campaign_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AdCampaignActivity::class, 'ad_campaign_id');
    }

    public function getImpressionsCountAttribute(): int
    {
        return (int) ($this->attributes['impressions_count'] ?? $this->impressions()->count());
    }

    public function getClicksCountAttribute(): int
    {
        return (int) ($this->attributes['clicks_count'] ?? $this->clicks()->count());
    }

    public function getRewardsCountAttribute(): int
    {
        return (int) ($this->attributes['rewards_count'] ?? $this->rewards()->count());
    }

    public function getTotalRewardedAmountAttribute(): float
    {
        return round((float) ($this->rewards()->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd')), 4);
    }

    public function getCtrAttribute(): float
    {
        $impressions = $this->impressions_count;
        $clicks = $this->clicks_count;

        if ($impressions <= 0) {
            return 0.00;
        }

        return round(($clicks / $impressions) * 100, 2);
    }

    public function getMetricsAttribute(): array
    {
        $budget = (float) $this->budget;
        $spent = (float) $this->spent_amount;
        $remaining = (float) $this->remaining_amount;
        $impressions = $this->impressions_count;
        $clicks = $this->clicks_count;
        $rewards = $this->rewards_count;
        $ctr = $this->ctr;

        return [
            'budget' => $budget,
            'spent_amount' => $spent,
            'remaining_amount' => $remaining,
            'impressions_count' => $impressions,
            'clicks_count' => $clicks,
            'rewards_count' => $rewards,
            'ctr' => $ctr,
        ];
    }

    // State Checks
    public function isEligibleForDelivery(): bool
    {
        if ($this->approval_status !== self::APPROVAL_APPROVED) {
            return false;
        }

        if (!in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVE], true)) {
            return false;
        }

        $minReward = RewardRankRule::getMinimumActiveRewardAmount() ?? AdRewardRule::getMinimumActiveRewardAmount() ?? 0.0250;
        if ((float) $this->remaining_amount < $minReward || (float) $this->budget < $minReward) {
            return false;
        }

        $now = now();
        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        return true;
    }

    public function scopeEligibleForDelivery($query)
    {
        $minReward = RewardRankRule::getMinimumActiveRewardAmount() ?? AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_BUSINESS_AD) ?? AdRewardRule::getMinimumActiveRewardAmount() ?? 0.0250;

        return $query->where('approval_status', self::APPROVAL_APPROVED)
            ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_ACTIVE])
            ->where('budget', '>=', $minReward)
            ->where('remaining_amount', '>=', $minReward)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }

    /**
     * Check if an event campaign is eligible for delivery (approved, active, budget eligible, event not passed).
     */
    public function isEligibleEventForDelivery(): bool
    {
        if ($this->campaign_type !== self::TYPE_EVENT) {
            return false;
        }

        if ($this->approval_status !== self::APPROVAL_APPROVED) {
            return false;
        }

        if (!in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVE], true)) {
            return false;
        }

        $minEventReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        if ($minEventReward === null) {
            return false;
        }

        if ((float) $this->remaining_amount < $minEventReward || (float) $this->budget < $minEventReward) {
            return false;
        }

        $now = now();
        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        $event = $this->event;
        if (!$event || $event->status !== 'published') {
            return false;
        }

        if ($event->hasPassed()) {
            return false;
        }

        return true;
    }

    /**
     * Scope for event campaigns eligible for social feed delivery.
     */
    public function scopeEligibleEventForDelivery($query)
    {
        $minEventReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        if ($minEventReward === null) {
            return $query->whereRaw('0 = 1');
        }

        $now = now();

        return $query->where('campaign_type', self::TYPE_EVENT)
            ->where('approval_status', self::APPROVAL_APPROVED)
            ->whereIn('status', [self::STATUS_APPROVED, self::STATUS_ACTIVE])
            ->where('budget', '>=', $minEventReward)
            ->where('remaining_amount', '>=', $minEventReward)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->whereHas('event', function ($eq) {
                $eq->where('status', 'published')
                    ->notPassed();
            });
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW || $this->approval_status === self::APPROVAL_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED || $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function isStopped(): bool
    {
        return in_array($this->status, [self::STATUS_STOPPED, self::STATUS_CANCELLED, self::STATUS_COMPLETED], true);
    }

    public function scopeBusinessAds($query)
    {
        return $query->where('campaign_type', self::TYPE_BUSINESS_PAGE);
    }

    public function scopeEvents($query)
    {
        return $query->where('campaign_type', self::TYPE_EVENT);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('campaign_type', self::TYPE_EVENT)->where('event_id', $eventId);
    }

    public function scopeOwnedBy($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function isOwner(?int $memberId): bool
    {
        if (!$memberId) {
            return false;
        }

        if ((int) $this->member_id === (int) $memberId) {
            return true;
        }

        if ($this->event_id) {
            $event = $this->relationLoaded('event') ? $this->getRelation('event') : $this->event;
            if ($event && $event->isOrganizer($memberId)) {
                return true;
            }
        }

        if ($this->business_page_id) {
            $page = $this->relationLoaded('businessPage') ? $this->getRelation('businessPage') : $this->businessPage;
            if ($page && $page->isOwner($memberId)) {
                return true;
            }
        }

        return false;
    }

    // Lifecycle State Transitions
    public function submitForReview(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PENDING_REVIEW,
            'approval_status' => self::APPROVAL_PENDING,
            'rejection_reason' => null,
        ]);

        return true;
    }

    public function approve(int $adminId): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING_REVIEW, self::STATUS_PAUSED, self::STATUS_DRAFT], true) && $this->approval_status !== self::APPROVAL_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return true;
    }

    public function reject(int $adminId, string $reason): bool
    {
        if ($this->status === self::STATUS_STOPPED || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approval_status' => self::APPROVAL_REJECTED,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    public function pause(): bool
    {
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_APPROVED], true)) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PAUSED,
        ]);

        return true;
    }

    public function resume(): bool
    {
        if ($this->status !== self::STATUS_PAUSED && $this->status !== self::STATUS_APPROVED) {
            return false;
        }

        if ($this->approval_status !== self::APPROVAL_APPROVED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);

        return true;
    }

    public function stop(): bool
    {
        if (in_array($this->status, [self::STATUS_STOPPED, self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_STOPPED,
        ]);

        return true;
    }

    public function restart(): bool
    {
        if ($this->status !== self::STATUS_STOPPED) {
            return false;
        }

        if ($this->approval_status !== self::APPROVAL_APPROVED) {
            return false;
        }

        $minReward = AdRewardRule::getMinimumActiveRewardAmount($this->campaign_type === self::TYPE_EVENT ? AdRewardRule::TYPE_EVENT : AdRewardRule::TYPE_BUSINESS_AD)
            ?? AdRewardRule::getMinimumActiveRewardAmount()
            ?? 0.0250;

        if ((float) $this->remaining_amount < $minReward || (float) $this->remaining_amount <= 0) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);

        return true;
    }

    /**
     * Consume a specified amount from this campaign's available budget.
     * Delegates to the canonical CampaignBudgetDepletionService.
     *
     * @param float|string|int $amount
     * @param string $spendReference
     * @param array $metadata
     * @return array
     */
    public function consumeBudget($amount, string $spendReference, array $metadata = []): array
    {
        return app(\App\Services\CampaignBudgetDepletionService::class)->depleteCampaignBudget(
            $this,
            $amount,
            $spendReference,
            $this->campaign_type === self::TYPE_EVENT ? $this->event : null,
            $metadata
        );
    }
}


