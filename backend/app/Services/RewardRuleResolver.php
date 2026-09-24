<?php

namespace App\Services;

use App\Models\AdRewardRule;
use App\Models\Member;

class RewardRuleResolver
{
    /**
     * Get the authoritative DIRECT VERIFIED referral count for a member.
     * Strictly:
     * 1. Direct introducer relationship (introducer_id = member.user_id).
     * 2. Referred member has completed mobile/WhatsApp verification (mobile_verified_at is not null).
     * Excludes: unverified registrations, indirect descendants, followers, connections.
     */
    public function getVerifiedDirectReferralCount(Member $member): int
    {
        if (empty($member->user_id)) {
            return 0;
        }

        return (int) Member::query()
            ->where('introducer_id', $member->user_id)
            ->whereNotNull('mobile_verified_at')
            ->count();
    }

    /**
     * Resolve the eligible reward rule and amount for an authenticated Member.
     * Ignores any client-supplied referral count or reward parameters.
     */
    public function resolveForMember(Member $member, string $type = AdRewardRule::TYPE_BUSINESS_AD): array
    {
        $verifiedCount = $this->getVerifiedDirectReferralCount($member);

        return $this->resolveForCount($verifiedCount, $member, $type);
    }

    /**
     * Resolve reward tier given a verified direct referral count against active Admin rules.
     */
    public function resolveForCount(int $count, ?Member $member = null, string $type = AdRewardRule::TYPE_BUSINESS_AD): array
    {
        if ($count < 0) {
            return [
                'success' => false,
                'status' => 'invalid_referral_count',
                'rule_type' => $type,
                'message' => 'Direct verified referral count cannot be negative.',
                'eligible' => false,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        $activeRules = AdRewardRule::getActiveRules($type);

        if ($activeRules->isEmpty()) {
            return [
                'success' => false,
                'status' => 'no_active_rules',
                'rule_type' => $type,
                'message' => "No active {$type} reward rules are configured in the system.",
                'eligible' => false,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        // Find matching active rules where min <= count AND (max >= count OR max IS NULL)
        $matchingRules = $activeRules->filter(function (AdRewardRule $rule) use ($count) {
            $min = (int) $rule->min_referrals;
            $max = $rule->max_referrals !== null ? (int) $rule->max_referrals : null;

            return $count >= $min && ($max === null || $count <= $max);
        })->values();

        // Safety Overlap Protection: refuse ambiguous resolution
        if ($matchingRules->count() > 1) {
            return [
                'success' => false,
                'status' => 'ambiguous_overlapping_rules',
                'rule_type' => $type,
                'message' => 'Configuration integrity error: multiple active reward rules overlap for this referral count.',
                'eligible' => false,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
                'matching_rule_ids' => $matchingRules->pluck('id')->all(),
            ];
        }

        // Gap Protection: no matching rule
        if ($matchingRules->isEmpty()) {
            return [
                'success' => false,
                'status' => 'no_matching_rule',
                'rule_type' => $type,
                'message' => "No active {$type} reward rule matches direct verified referral count: {$count}.",
                'eligible' => false,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        /** @var AdRewardRule $rule */
        $rule = $matchingRules->first();

        // Check if the matched rule has unconfigured reward amount (e.g. 0-5 slab pending admin input)
        if ($rule->reward_amount === null || $rule->reward_amount === '' || (float) $rule->reward_amount <= 0.0) {
            return [
                'success' => false,
                'status' => 'reward_rule_not_configured',
                'rule_type' => $type,
                'message' => "Reward rule for range {$rule->min_referrals}–" . ($rule->max_referrals ?? '+') . " requires admin configuration.",
                'eligible' => false,
                'matched_rule_id' => $rule->id,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        $rawAmount = (float) $rule->reward_amount;
        // Enforce maximum permissible reward cap ($0.050 USD)
        $rewardAmount = min(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, max(0.00, $rawAmount));
        $exactRewardString = number_format($rewardAmount, 4, '.', '');

        return [
            'success' => true,
            'status' => 'resolved',
            'rule_type' => $type,
            'eligible' => true,
            'member_id' => $member?->id,
            'user_id' => $member?->user_id,
            'direct_verified_referral_count' => $count,
            'matched_rule_id' => $rule->id,
            'matched_range' => [
                'min' => (int) $rule->min_referrals,
                'max' => $rule->max_referrals !== null ? (int) $rule->max_referrals : null,
                'label' => $rule->min_referrals . ($rule->max_referrals !== null ? '–' . $rule->max_referrals : '+'),
                'is_unlimited' => $rule->max_referrals === null,
            ],
            'reward_amount_usd' => $rewardAmount,
            'reward_amount_exact' => $exactRewardString,
            'currency' => 'USD',
            'currency_symbol' => '$',
            // Snapshot payload ready for future financial attribution in later phases:
            'snapshot' => [
                'rule_type' => $type,
                'ad_reward_rule_id' => $rule->id,
                'rule_version' => $rule->updated_at?->timestamp ?? $rule->created_at?->timestamp ?? time(),
                'direct_verified_referral_count' => $count,
                'min_referrals' => (int) $rule->min_referrals,
                'max_referrals' => $rule->max_referrals !== null ? (int) $rule->max_referrals : null,
                'reward_amount_usd' => $rewardAmount,
                'reward_amount_exact' => $exactRewardString,
                'currency' => 'USD',
                'resolved_at' => now()->toIso8601String(),
            ],
        ];
    }
}
