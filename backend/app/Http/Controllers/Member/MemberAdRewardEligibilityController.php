<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\RewardRuleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberAdRewardEligibilityController extends Controller
{
    /**
     * Check the authenticated Member's current advertising reward eligibility and tier.
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

        // Authoritative resolution strictly from DB relationships & active admin rules
        $resolution = $resolver->resolveForMember($member);

        if (!$resolution['success']) {
            return response()->json([
                'success' => false,
                'status' => $resolution['status'] ?? 'unresolved',
                'message' => $resolution['message'] ?? 'Reward eligibility could not be resolved.',
                'direct_verified_referral_count' => $resolution['direct_verified_referral_count'] ?? 0,
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'status' => 'eligible',
            'direct_verified_referral_count' => $resolution['direct_verified_referral_count'],
            'eligible' => true,
            'reward_amount_usd' => $resolution['reward_amount_usd'],
            'reward_amount_exact' => $resolution['reward_amount_exact'],
            'currency' => $resolution['currency'] ?? 'USD',
            'currency_symbol' => $resolution['currency_symbol'] ?? '$',
            'matched_range' => $resolution['matched_range'] ?? null,
            'matched_rule_id' => $resolution['matched_rule_id'] ?? null,
        ]);
    }
}
