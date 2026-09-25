<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class RewardRankRule extends Model
{
    use HasFactory;

    public const RANK_ADVERTISER = 'advertiser';
    public const RANK_INFLUENCER = 'influencer';
    public const RANK_LEADERS = 'leaders';
    public const RANK_PRO_LEADERS = 'pro_leaders';
    public const RANK_MASTER_LEADERS = 'master_leaders';

    public const CANONICAL_RANKS = [
        self::RANK_ADVERTISER => [
            'name' => 'Advertiser',
            'priority' => 1,
            'default_referral' => 0,
            'default_team' => 1,
            'default_reward' => 0.0250,
        ],
        self::RANK_INFLUENCER => [
            'name' => 'Influencer',
            'priority' => 2,
            'default_referral' => 6,
            'default_team' => 15,
            'default_reward' => 0.0350,
        ],
        self::RANK_LEADERS => [
            'name' => 'Leaders',
            'priority' => 3,
            'default_referral' => 15,
            'default_team' => 50,
            'default_reward' => 0.0500,
        ],
        self::RANK_PRO_LEADERS => [
            'name' => 'Pro Leaders',
            'priority' => 4,
            'default_referral' => 30,
            'default_team' => 150,
            'default_reward' => 0.0750,
        ],
        self::RANK_MASTER_LEADERS => [
            'name' => 'Master Leaders',
            'priority' => 5,
            'default_referral' => 50,
            'default_team' => 500,
            'default_reward' => 0.1000,
        ],
    ];

    public const CACHE_KEY_ACTIVE_RANK_RULES = 'reward_rank_rules_active';

    protected $table = 'reward_rank_rules';

    protected $fillable = [
        'rank_key',
        'rank_name',
        'priority',
        'referral_requirement',
        'team_requirement',
        'reward_amount',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'referral_requirement' => 'integer',
            'team_requirement' => 'integer',
            'reward_amount' => 'decimal:4',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Admin who created this rank rule.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Admin who last updated this rank rule.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Scope for active rules ordered by priority ascending.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'asc');
    }

    /**
     * Scope for active rules ordered by priority descending (highest rank first).
     */
    public function scopeHighestFirst($query)
    {
        return $query->where('is_active', true)->orderBy('priority', 'desc');
    }

    /**
     * Clear the cached active rank rules.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE_RANK_RULES);
    }

    /**
     * Get all active rank rules from cache or database ordered by priority ascending.
     */
    public static function getActiveRules(): Collection
    {
        return Cache::remember(self::CACHE_KEY_ACTIVE_RANK_RULES, 3600, function () {
            return static::active()->get();
        });
    }

    /**
     * Get the minimum active reward amount in USD.
     */
    public static function getMinimumActiveRewardAmount(): ?float
    {
        $active = static::getActiveRules();
        $validRewards = $active->whereNotNull('reward_amount')
            ->map(fn (self $r) => (float) $r->reward_amount)
            ->filter(fn ($amt) => $amt > 0);

        if ($validRewards->isEmpty()) {
            return null;
        }

        return (float) $validRewards->min();
    }

    /**
     * Get the maximum active reward amount in USD (for "Earn up to $X" messaging).
     */
    public static function getMaximumActiveRewardAmount(): ?float
    {
        $active = static::getActiveRules();
        $validRewards = $active->whereNotNull('reward_amount')
            ->map(fn (self $r) => (float) $r->reward_amount)
            ->filter(fn ($amt) => $amt > 0);

        if ($validRewards->isEmpty()) {
            return null;
        }

        return (float) $validRewards->max();
    }
}
