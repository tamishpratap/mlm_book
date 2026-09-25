<?php

namespace App\Services;

use App\Models\AdRewardRule;
use App\Models\Member;
use App\Models\RewardRankRule;

class RewardRuleResolver
{
    public function __construct(
        protected ?RewardRankResolver $rankResolver = null
    ) {
        $this->rankResolver = $rankResolver ?? app(RewardRankResolver::class);
    }

    /**
     * Get the authoritative DIRECT VERIFIED referral count for a member.
     * Strictly:
     * 1. Direct introducer relationship (introducer_id = member.user_id).
     * 2. Referred member has completed mobile/WhatsApp verification (mobile_verified_at is not null).
     * Excludes: unverified registrations, indirect descendants, followers, connections.
     */
    public function getVerifiedDirectReferralCount(Member $member): int
    {
        return $this->rankResolver->getVerifiedDirectReferralCount($member);
    }

    /**
     * Get the authoritative TOTAL VERIFIED DOWNLINE TEAM count for a member.
     */
    public function getVerifiedTeamCount(Member $member): int
    {
        return $this->rankResolver->getVerifiedTeamCount($member);
    }

    /**
     * Resolve the eligible reward rule and amount for an authenticated Member.
     * Prioritizes the central dynamic rank-based reward engine.
     */
    public function resolveForMember(Member $member, string $type = AdRewardRule::TYPE_CENTRAL): array
    {
        // 1. Authoritative Rank-Based Resolution
        if (RewardRankRule::active()->exists()) {
            $rankResult = $this->rankResolver->resolveForMember($member);

            if (!$rankResult['success'] || !$rankResult['eligible']) {
                return array_merge($rankResult, [
                    'direct_verified_referral_count' => $rankResult['user_referrals'] ?? 0,
                    'team_count' => $rankResult['user_team'] ?? 0,
                    'reward_amount_usd' => 0.00,
                    'reward_amount_exact' => '0.0000',
                    'rule_type' => 'rank',
                    'matched_rule_id' => null,
                ]);
            }

            return [
                'success' => true,
                'status' => 'resolved',
                'rule_type' => 'rank',
                'eligible' => true,
                'rank' => $rankResult['rank'],
                'rank_key' => $rankResult['rank_key'],
                'priority' => $rankResult['priority'],
                'referral_requirement' => $rankResult['referral_requirement'],
                'team_requirement' => $rankResult['team_requirement'],
                'user_referrals' => $rankResult['user_referrals'],
                'user_team' => $rankResult['user_team'],
                'direct_verified_referral_count' => $rankResult['user_referrals'],
                'team_count' => $rankResult['user_team'],
                'reward' => $rankResult['reward'],
                'reward_amount_usd' => $rankResult['reward'],
                'reward_amount_exact' => $rankResult['reward_amount_exact'],
                'currency' => $rankResult['currency'] ?? 'USD',
                'currency_symbol' => $rankResult['currency_symbol'] ?? '$',
                'matched_rule_id' => $rankResult['rule_id'],
                'snapshot' => [
                    'rank' => $rankResult['rank'],
                    'rank_key' => $rankResult['rank_key'],
                    'priority' => $rankResult['priority'],
                    'referral_requirement' => $rankResult['referral_requirement'],
                    'team_requirement' => $rankResult['team_requirement'],
                    'reward_amount' => $rankResult['reward'],
                    'rule_id' => $rankResult['rule_id'],
                    'user_referrals' => $rankResult['user_referrals'],
                    'user_team' => $rankResult['user_team'],
                ],
            ];
        }

        // 2. Fallback to legacy referral-tier resolution if no rank rules exist
        $verifiedCount = $this->getVerifiedDirectReferralCount($member);
        return $this->resolveForCount($verifiedCount, $member, $type);
    }

    /**
     * Resolve reward tier given a verified direct referral count against active Admin rules.
     */
    public function resolveForCount(int $count, ?Member $member = null, string $type = AdRewardRule::TYPE_CENTRAL): array
    {
        // If member is provided and rank rules exist, use the full rank resolver
        if ($member !== null && RewardRankRule::active()->exists()) {
            return $this->resolveForMember($member, $type);
        }

        // If no member provided but rank rules exist, evaluate metrics with team = count as fallback
        if (RewardRankRule::active()->exists()) {
            $rankResult = $this->rankResolver->resolveForMetrics($count, $count);

            if (!$rankResult['success'] || !$rankResult['eligible']) {
                return array_merge($rankResult, [
                    'direct_verified_referral_count' => $count,
                    'reward_amount_usd' => 0.00,
                    'reward_amount_exact' => '0.0000',
                    'rule_type' => 'rank',
                    'matched_rule_id' => null,
                ]);
            }

            return [
                'success' => true,
                'status' => 'resolved',
                'rule_type' => 'rank',
                'eligible' => true,
                'rank' => $rankResult['rank'],
                'rank_key' => $rankResult['rank_key'],
                'priority' => $rankResult['priority'],
                'referral_requirement' => $rankResult['referral_requirement'],
                'team_requirement' => $rankResult['team_requirement'],
                'user_referrals' => $count,
                'user_team' => $count,
                'direct_verified_referral_count' => $count,
                'reward' => $rankResult['reward'],
                'reward_amount_usd' => $rankResult['reward'],
                'reward_amount_exact' => $rankResult['reward_amount_exact'],
                'currency' => $rankResult['currency'] ?? 'USD',
                'currency_symbol' => $rankResult['currency_symbol'] ?? '$',
                'matched_rule_id' => $rankResult['rule_id'],
                'snapshot' => [
                    'rank' => $rankResult['rank'],
                    'rank_key' => $rankResult['rank_key'],
                    'priority' => $rankResult['priority'],
                    'referral_requirement' => $rankResult['referral_requirement'],
                    'team_requirement' => $rankResult['team_requirement'],
                    'reward_amount' => $rankResult['reward'],
                    'rule_id' => $rankResult['rule_id'],
                ],
            ];
        }

        $targetType = $type;
        if ($targetType === AdRewardRule::TYPE_CENTRAL) {
            $hasCentral = AdRewardRule::ofType(AdRewardRule::TYPE_CENTRAL)->active()->exists();
            if (!$hasCentral) {
                $targetType = AdRewardRule::TYPE_BUSINESS_AD;
            }
        }

        if ($count < 0) {
            return [
                'success' => false,
                'status' => 'invalid_referral_count',
                'rule_type' => $targetType,
                'message' => 'Direct verified referral count cannot be negative.',
                'eligible' => false,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        $activeRules = AdRewardRule::getActiveRules($targetType);

        if ($activeRules->isEmpty()) {
            return [
                'success' => false,
                'status' => 'no_active_rules',
                'rule_type' => $targetType,
                'message' => "No active {$targetType} reward rules are configured in the system.",
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
                'rule_type' => $targetType,
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
                'rule_type' => $targetType,
                'message' => "No active {$targetType} reward rule matches direct verified referral count: {$count}.",
                'eligible' => false,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        /** @var AdRewardRule $rule */
        $rule = $matchingRules->first();

        if ($rule->reward_amount === null || $rule->reward_amount === '' || (float) $rule->reward_amount <= 0.0) {
            return [
                'success' => false,
                'status' => 'reward_rule_not_configured',
                'rule_type' => $targetType,
                'message' => "Reward rule for range {$rule->min_referrals}–" . ($rule->max_referrals ?? '+') . " requires admin configuration.",
                'eligible' => false,
                'matched_rule_id' => $rule->id,
                'direct_verified_referral_count' => $count,
                'reward_amount_usd' => 0.00,
            ];
        }

        $rawAmount = (float) $rule->reward_amount;
        $rewardAmount = min(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, max(0.00, $rawAmount));
        $exactRewardString = number_format($rewardAmount, 4, '.', '');

        return [
            'success' => true,
            'status' => 'resolved',
            'rule_type' => $targetType,
            'eligible' => true,
            'direct_verified_referral_count' => $count,
            'reward_amount_usd' => $rewardAmount,
            'reward_amount_exact' => $exactRewardString,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'matched_rule_id' => $rule->id,
            'matched_range' => [
                'min' => (int) $rule->min_referrals,
                'max' => $rule->max_referrals !== null ? (int) $rule->max_referrals : null,
                'is_unlimited' => $rule->max_referrals === null,
            ],
            'snapshot' => [
                'rule_id' => $rule->id,
                'min_referrals' => (int) $rule->min_referrals,
                'max_referrals' => $rule->max_referrals !== null ? (int) $rule->max_referrals : null,
                'reward_amount' => $rewardAmount,
                'rule_type' => $targetType,
            ],
        ];
    }
}
