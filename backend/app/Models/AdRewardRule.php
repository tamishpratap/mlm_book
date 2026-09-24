<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AdRewardRule extends Model
{
    use HasFactory;

    public const TYPE_BUSINESS_AD = 'business_ad';
    public const TYPE_EVENT = 'event';

    public const MAX_PERMISSIBLE_REWARD_USD = 0.0500;
    public const CACHE_KEY_ACTIVE_RULES = 'ad_reward_rules_active';
    public const CACHE_KEY_ACTIVE_EVENT_RULES = 'event_reward_rules_active';

    protected $table = 'ad_reward_rules';

    protected $fillable = [
        'rule_type',
        'min_referrals',
        'max_referrals',
        'reward_amount',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => 'string',
            'min_referrals' => 'integer',
            'max_referrals' => 'integer',
            'reward_amount' => 'decimal:4',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Admin who created this rule.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Admin who last updated this rule.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Scope for business ad reward rules.
     */
    public function scopeForBusinessAd($query)
    {
        return $query->where('rule_type', self::TYPE_BUSINESS_AD);
    }

    /**
     * Scope for event reward rules.
     */
    public function scopeForEvent($query)
    {
        return $query->where('rule_type', self::TYPE_EVENT);
    }

    /**
     * Scope for a specific rule type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope for active rules ordered by minimum referrals ascending.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('min_referrals', 'asc');
    }

    /**
     * Clear the cached active rules.
     * If type is null, clears both business_ad and event cache keys.
     */
    public static function clearCache(?string $type = null): void
    {
        if ($type === null) {
            Cache::forget(self::CACHE_KEY_ACTIVE_RULES);
            Cache::forget(self::CACHE_KEY_ACTIVE_EVENT_RULES);
        } elseif ($type === self::TYPE_EVENT) {
            Cache::forget(self::CACHE_KEY_ACTIVE_EVENT_RULES);
        } else {
            Cache::forget(self::CACHE_KEY_ACTIVE_RULES);
        }
    }

    /**
     * Get all active rules for a specific rule type from cache or database.
     */
    public static function getActiveRules(string $type = self::TYPE_BUSINESS_AD): Collection
    {
        $cacheKey = ($type === self::TYPE_EVENT)
            ? self::CACHE_KEY_ACTIVE_EVENT_RULES
            : self::CACHE_KEY_ACTIVE_RULES;

        return Cache::remember($cacheKey, 3600, function () use ($type) {
            return static::ofType($type)->active()->get();
        });
    }

    /**
     * Get the minimum active reward amount in USD.
     * Centralized source for campaign exhaustion checks.
     *
     * Strict rule: For TYPE_EVENT, if no active rules exist, returns null (never fabricates $0.05 or $0.025).
     * For legacy Business Ads, maintains backward compatibility.
     */
    public static function getMinimumActiveRewardAmount(string $type = self::TYPE_BUSINESS_AD): ?float
    {
        $active = static::getActiveRules($type);
        $validRewards = $active->whereNotNull('reward_amount')
            ->map(fn (self $r) => (float) $r->reward_amount)
            ->filter(fn ($amt) => $amt > 0);

        if ($validRewards->isEmpty()) {
            return $type === self::TYPE_EVENT ? null : 0.0250;
        }

        return (float) $validRewards->min();
    }

    /**
     * Get the maximum active reward amount in USD.
     * Centralized dynamic source for "Earn up to $X" messaging.
     *
     * Strict rule: For TYPE_EVENT, returns the highest active reward configured (or null if none exist).
     * Never fabricates hardcoded rewards.
     */
    public static function getMaximumActiveRewardAmount(string $type = self::TYPE_EVENT): ?float
    {
        $active = static::getActiveRules($type);
        $validRewards = $active->whereNotNull('reward_amount')
            ->map(fn (self $r) => (float) $r->reward_amount)
            ->filter(fn ($amt) => $amt > 0);

        if ($validRewards->isEmpty()) {
            return $type === self::TYPE_EVENT ? null : 0.0500;
        }

        return (float) $validRewards->max();
    }

    /**
     * Resolve the applicable active reward rule for a given direct verified referral count.
     * Pure reader / resolver - does NOT execute any wallet credits.
     */
    public static function resolveForDirectVerifiedReferrals(int $count, string $type = self::TYPE_BUSINESS_AD): ?self
    {
        $activeRules = static::getActiveRules($type);

        foreach ($activeRules as $rule) {
            $min = (int) $rule->min_referrals;
            $max = $rule->max_referrals !== null ? (int) $rule->max_referrals : null;

            if ($count >= $min && ($max === null || $count <= $max)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Check if a specific rule range overlaps with any other active rule of the same type.
     */
    public static function checkOverlap(int $min, ?int $max, ?int $ignoreId = null, string $type = self::TYPE_BUSINESS_AD): ?self
    {
        $query = static::ofType($type)->where('is_active', true);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        $activeRules = $query->get();

        foreach ($activeRules as $existing) {
            $eMin = (int) $existing->min_referrals;
            $eMax = $existing->max_referrals !== null ? (int) $existing->max_referrals : null;

            // Two ranges [A_min, A_max] and [B_min, B_max] overlap if:
            // (A_min <= B_max or B_max is null) AND (B_min <= A_max or A_max is null)
            $aBeforeBEnd = ($max === null) || ($eMax === null) || ($min <= $eMax);
            $bBeforeAEnd = ($max === null) || ($eMax === null) || ($eMin <= $max);

            if ($aBeforeBEnd && $bBeforeAEnd) {
                // Double check specific intersection
                if ($max === null && $eMax === null) {
                    return $existing; // Both unlimited
                }
                if ($max === null && $eMax !== null) {
                    if ($min <= $eMax) return $existing;
                } elseif ($max !== null && $eMax === null) {
                    if ($eMin <= $max) return $existing;
                } else {
                    if (max($min, $eMin) <= min($max, $eMax)) {
                        return $existing;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate the entire active rule set for continuity (starts at 0, no gaps, single unlimited top tier).
     * Returns an array of error messages (empty if valid).
     */
    public static function validateActiveSetContinuity(string $type = self::TYPE_BUSINESS_AD): array
    {
        $errors = [];
        $rules = static::ofType($type)->active()->get();

        if ($rules->isEmpty()) {
            return $errors;
        }

        // Check first tier starts at 0
        $first = $rules->first();
        if ((int) $first->min_referrals !== 0) {
            $errors[] = "The lowest active reward tier must start at 0 direct verified referrals (currently starts at {$first->min_referrals}).";
        }

        $count = $rules->count();
        for ($i = 0; $i < $count; $i++) {
            $current = $rules[$i];
            $currentMin = (int) $current->min_referrals;
            $currentMax = $current->max_referrals !== null ? (int) $current->max_referrals : null;

            // Only the last item may have max_referrals = null
            if ($currentMax === null && $i < $count - 1) {
                $errors[] = "Only the highest active tier can have an unlimited maximum referrals setting (Rule {$currentMin}+ is placed before subsequent rules).";
            }

            // Check gap and overlap with next tier
            if ($i < $count - 1) {
                $next = $rules[$i + 1];
                $nextMin = (int) $next->min_referrals;

                if ($currentMax !== null) {
                    $expectedNextMin = $currentMax + 1;
                    if ($nextMin < $expectedNextMin) {
                        $errors[] = "Active tiers overlap between {$currentMin}–{$currentMax} and {$nextMin}–" . ($next->max_referrals ?? 'Unlimited') . ".";
                    } elseif ($nextMin > $expectedNextMin) {
                        $errors[] = "Gap detected in active tiers between referral count {$currentMax} and {$nextMin}. Missing configuration for referral count " . ($currentMax + 1) . ($nextMin - 1 > $currentMax + 1 ? " to " . ($nextMin - 1) : "") . ".";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get full structural active set validation results via RewardRuleValidationService.
     */
    public static function validateActiveSet(string $type = self::TYPE_EVENT): array
    {
        return app(\App\Services\RewardRuleValidationService::class)->validateActiveSet($type);
    }
}

