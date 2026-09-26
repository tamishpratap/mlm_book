<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\RewardRankResolver;
use App\Services\RewardRuleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberAdRewardEligibilityController extends Controller
{
    /**
     * Check the authenticated Member's current advertising reward eligibility and rank.
     * All inputs are recalculated dynamically on backend (tamper-proof).
     */
    public function checkEligibility(Request $request, RewardRuleResolver $resolver): JsonResponse
    {
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Fresh database instance to guarantee up-to-date state
        $member = $member->fresh() ?? $member;

        // Authoritative resolution strictly from DB relationships & active admin rank rules
        $resolution = $resolver->resolveForMember($member);

        if (!$resolution['success']) {
            return response()->json([
                'success' => false,
                'status' => $resolution['status'] ?? 'unresolved',
                'message' => $resolution['message'] ?? 'Reward eligibility could not be resolved.',
                'direct_verified_referral_count' => $resolution['direct_verified_referral_count'] ?? 0,
                'user_referrals' => $resolution['user_referrals'] ?? 0,
                'user_team' => $resolution['user_team'] ?? 0,
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'status' => 'eligible',
            'eligible' => true,
            'current_rank' => $resolution['rank'] ?? 'No Rank',
            'rank' => $resolution['rank'] ?? null,
            'rank_key' => $resolution['rank_key'] ?? null,
            'priority' => $resolution['priority'] ?? null,
            'referral_requirement' => $resolution['referral_requirement'] ?? null,
            'team_requirement' => $resolution['team_requirement'] ?? null,
            'direct_verified_referral_count' => $resolution['direct_verified_referral_count'] ?? 0,
            'user_referrals' => $resolution['user_referrals'] ?? ($resolution['direct_verified_referral_count'] ?? 0),
            'user_team' => $resolution['user_team'] ?? ($resolution['team_count'] ?? 0),
            'verified_connections' => $resolution['verified_connections'] ?? ($resolution['user_team'] ?? 0),
            'reward' => $resolution['reward'] ?? $resolution['reward_amount_usd'],
            'reward_amount_usd' => $resolution['reward_amount_usd'],
            'reward_amount_exact' => $resolution['reward_amount_exact'],
            'currency' => $resolution['currency'] ?? 'USD',
            'currency_symbol' => $resolution['currency_symbol'] ?? '$',
            'matched_rule_id' => $resolution['matched_rule_id'] ?? null,
            'next_rank' => $resolution['next_rank'] ?? null,
        ]);
    }

    /**
     * Dedicated user-side endpoint to fetch the member's current rank and reward.
     */
    public function currentRank(Request $request, RewardRankResolver $rankResolver): JsonResponse
    {
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $member = $member->fresh() ?? $member;
        $resolution = $rankResolver->resolveForMember($member);

        return response()->json([
            'success' => true,
            'current_rank' => $resolution['rank'] ?? 'No Rank',
            'rank' => $resolution['rank'] ?? null,
            'rank_key' => $resolution['rank_key'] ?? null,
            'priority' => $resolution['priority'] ?? null,
            'eligible' => $resolution['eligible'] ?? false,
            'user_referrals' => $resolution['user_referrals'] ?? 0,
            'user_team' => $resolution['user_team'] ?? 0,
            'verified_connections' => $resolution['verified_connections'] ?? ($resolution['user_team'] ?? 0),
            'referral_requirement' => $resolution['referral_requirement'] ?? null,
            'team_requirement' => $resolution['team_requirement'] ?? null,
            'reward' => $resolution['reward'] ?? 0.0000,
            'reward_amount_usd' => $resolution['reward_amount_usd'] ?? 0.0000,
            'reward_amount_exact' => $resolution['reward_amount_exact'] ?? '0.0000',
            'currency' => $resolution['currency'] ?? 'USD',
            'currency_symbol' => $resolution['currency_symbol'] ?? '$',
            'status' => $resolution['status'] ?? 'resolved',
            'next_rank' => $resolution['next_rank'] ?? null,
            'message' => $resolution['message'] ?? null,
        ]);
    }
}
