<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdRewardRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdRewardRuleController extends Controller
{
    /**
     * Display a listing of advertising reward rules for the admin configuration panel.
     */
    public function index(Request $request): JsonResponse
    {
        $rules = AdRewardRule::forBusinessAd()
            ->with(['createdBy:id,name,email', 'updatedBy:id,name,email'])
            ->orderBy('min_referrals', 'asc')
            ->get();

        // Check if baseline tier (0-5) or any active tier is incomplete
        $zeroTier = $rules->firstWhere('min_referrals', 0);
        $isConfigurationIncomplete = $zeroTier === null || $zeroTier->reward_amount === null;
        $incompleteWarning = $isConfigurationIncomplete
            ? 'Complete the 0–5 direct verified referral reward value before finalizing the reward engine.'
            : null;

        // Run set continuity check for business ads
        $continuityErrors = AdRewardRule::validateActiveSetContinuity(AdRewardRule::TYPE_BUSINESS_AD);

        // Build standard preview test resolutions (0, 5, 6, 14, 15, 20)
        $testCounts = [0, 5, 6, 14, 15, 20];
        $previews = [];

        foreach ($testCounts as $count) {
            $resolved = AdRewardRule::resolveForDirectVerifiedReferrals($count, AdRewardRule::TYPE_BUSINESS_AD);
            $rewardAmount = $resolved ? $resolved->reward_amount : null;

            $previews[] = [
                'direct_verified_referrals' => $count,
                'matched_rule_id' => $resolved ? $resolved->id : null,
                'range_label' => $resolved ? ($resolved->min_referrals . ($resolved->max_referrals !== null ? '–' . $resolved->max_referrals : '+')) : 'None',
                'reward_amount_usd' => $rewardAmount !== null ? (float) $rewardAmount : null,
                'formatted_reward' => $rewardAmount !== null ? '$' . number_format((float) $rewardAmount, 3) . ' USD' : 'Configuration Required',
                'is_configured' => $rewardAmount !== null,
                'is_active_rule' => $resolved !== null && $resolved->is_active,
            ];
        }

        return response()->json([
            'success' => true,
            'rules' => $rules,
            'is_configuration_complete' => !$isConfigurationIncomplete,
            'incomplete_warning' => $incompleteWarning,
            'continuity_errors' => $continuityErrors,
            'previews' => $previews,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'max_permissible_reward_usd' => AdRewardRule::MAX_PERMISSIBLE_REWARD_USD,
        ]);
    }

    /**
     * Store a newly created ad reward rule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'min_referrals' => ['required', 'integer', 'min:0'],
            'max_referrals' => ['nullable', 'integer', 'gte:min_referrals'],
            'reward_amount' => ['nullable', 'numeric', 'min:0', 'max:' . AdRewardRule::MAX_PERMISSIBLE_REWARD_USD],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'min_referrals.min' => 'Minimum direct verified referrals must be 0 or greater.',
            'max_referrals.gte' => 'Maximum direct verified referrals must be greater than or equal to minimum referrals.',
            'reward_amount.min' => 'Reward amount cannot be negative.',
            'reward_amount.max' => 'Reward amount cannot exceed $' . number_format(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, 3) . ' USD.',
        ]);

        $min = (int) $validated['min_referrals'];
        $max = isset($validated['max_referrals']) && $validated['max_referrals'] !== '' ? (int) $validated['max_referrals'] : null;
        $reward = isset($validated['reward_amount']) && $validated['reward_amount'] !== '' ? round((float) $validated['reward_amount'], 4) : null;
        $isActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : true;

        // Check range overlap if rule is active
        if ($isActive) {
            $overlappingRule = AdRewardRule::checkOverlap($min, $max, null, AdRewardRule::TYPE_BUSINESS_AD);
            if ($overlappingRule) {
                $eLabel = $overlappingRule->min_referrals . ($overlappingRule->max_referrals !== null ? '–' . $overlappingRule->max_referrals : '+');
                return response()->json([
                    'success' => false,
                    'message' => "Range {$min}–" . ($max ?? 'Unlimited') . " overlaps with existing active rule #{$overlappingRule->id} ({$eLabel}). Overlapping active ranges are blocked.",
                ], 422);
            }
        }

        $admin = auth('admin')->user();

        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => $min,
            'max_referrals' => $max,
            'reward_amount' => $reward,
            'is_active' => $isActive,
            'created_by' => $admin ? $admin->id : null,
            'updated_by' => $admin ? $admin->id : null,
        ]);

        AdRewardRule::clearCache(AdRewardRule::TYPE_BUSINESS_AD);

        return response()->json([
            'success' => true,
            'message' => 'Ad reward rule created successfully.',
            'rule' => $rule->load(['createdBy:id,name,email', 'updatedBy:id,name,email']),
        ], 201);
    }

    /**
     * Update an existing ad reward rule safely without rewriting historical reward records.
     */
    public function update(Request $request, AdRewardRule $adRewardRule): JsonResponse
    {
        if ($adRewardRule->rule_type !== AdRewardRule::TYPE_BUSINESS_AD) {
            return response()->json([
                'success' => false,
                'message' => 'Target rule is not a business ad reward rule.',
            ], 404);
        }

        $validated = $request->validate([
            'min_referrals' => ['required', 'integer', 'min:0'],
            'max_referrals' => ['nullable', 'integer', 'gte:min_referrals'],
            'reward_amount' => ['nullable', 'numeric', 'min:0', 'max:' . AdRewardRule::MAX_PERMISSIBLE_REWARD_USD],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'min_referrals.min' => 'Minimum direct verified referrals must be 0 or greater.',
            'max_referrals.gte' => 'Maximum direct verified referrals must be greater than or equal to minimum referrals.',
            'reward_amount.min' => 'Reward amount cannot be negative.',
            'reward_amount.max' => 'Reward amount cannot exceed $' . number_format(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, 3) . ' USD.',
        ]);

        $min = (int) $validated['min_referrals'];
        $max = isset($validated['max_referrals']) && $validated['max_referrals'] !== '' ? (int) $validated['max_referrals'] : null;
        $reward = isset($validated['reward_amount']) && $validated['reward_amount'] !== '' ? round((float) $validated['reward_amount'], 4) : null;
        $isActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : $adRewardRule->is_active;

        // Check range overlap if rule is active
        if ($isActive) {
            $overlappingRule = AdRewardRule::checkOverlap($min, $max, $adRewardRule->id, AdRewardRule::TYPE_BUSINESS_AD);
            if ($overlappingRule) {
                $eLabel = $overlappingRule->min_referrals . ($overlappingRule->max_referrals !== null ? '–' . $overlappingRule->max_referrals : '+');
                return response()->json([
                    'success' => false,
                    'message' => "Range {$min}–" . ($max ?? 'Unlimited') . " overlaps with existing active rule #{$overlappingRule->id} ({$eLabel}). Overlapping active ranges are blocked.",
                ], 422);
            }
        }

        $admin = auth('admin')->user();

        $adRewardRule->update([
            'min_referrals' => $min,
            'max_referrals' => $max,
            'reward_amount' => $reward,
            'is_active' => $isActive,
            'updated_by' => $admin ? $admin->id : null,
        ]);

        AdRewardRule::clearCache(AdRewardRule::TYPE_BUSINESS_AD);

        return response()->json([
            'success' => true,
            'message' => 'Ad reward rule updated successfully.',
            'rule' => $adRewardRule->fresh(['createdBy:id,name,email', 'updatedBy:id,name,email']),
        ]);
    }

    /**
     * Safely enable or disable an ad reward rule (preserves historical configuration).
     */
    public function toggleStatus(Request $request, AdRewardRule $adRewardRule): JsonResponse
    {
        if ($adRewardRule->rule_type !== AdRewardRule::TYPE_BUSINESS_AD) {
            return response()->json([
                'success' => false,
                'message' => 'Target rule is not a business ad reward rule.',
            ], 404);
        }

        $newStatus = !$adRewardRule->is_active;

        // If activating, verify no overlaps with existing active rules
        if ($newStatus) {
            $min = (int) $adRewardRule->min_referrals;
            $max = $adRewardRule->max_referrals !== null ? (int) $adRewardRule->max_referrals : null;
            $overlappingRule = AdRewardRule::checkOverlap($min, $max, $adRewardRule->id, AdRewardRule::TYPE_BUSINESS_AD);

            if ($overlappingRule) {
                $eLabel = $overlappingRule->min_referrals . ($overlappingRule->max_referrals !== null ? '–' . $overlappingRule->max_referrals : '+');
                return response()->json([
                    'success' => false,
                    'message' => "Cannot activate rule. Range {$min}–" . ($max ?? 'Unlimited') . " overlaps with active rule #{$overlappingRule->id} ({$eLabel}).",
                ], 422);
            }
        }

        $admin = auth('admin')->user();

        $adRewardRule->update([
            'is_active' => $newStatus,
            'updated_by' => $admin ? $admin->id : null,
        ]);

        AdRewardRule::clearCache(AdRewardRule::TYPE_BUSINESS_AD);

        return response()->json([
            'success' => true,
            'message' => 'Reward rule ' . ($newStatus ? 'activated' : 'disabled') . ' successfully.',
            'rule' => $adRewardRule->fresh(['createdBy:id,name,email', 'updatedBy:id,name,email']),
        ]);
    }

    /**
     * Simulate reward resolution for an arbitrary direct verified referral count.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referral_count' => ['required', 'integer', 'min:0'],
        ]);

        $count = (int) $validated['referral_count'];
        $resolved = AdRewardRule::resolveForDirectVerifiedReferrals($count, AdRewardRule::TYPE_BUSINESS_AD);
        $rewardAmount = $resolved ? $resolved->reward_amount : null;

        return response()->json([
            'success' => true,
            'referral_count' => $count,
            'matched_rule' => $resolved ? $resolved->load(['createdBy:id,name,email', 'updatedBy:id,name,email']) : null,
            'reward_amount_usd' => $rewardAmount !== null ? (float) $rewardAmount : null,
            'formatted_reward' => $rewardAmount !== null ? '$' . number_format((float) $rewardAmount, 3) . ' USD' : 'Configuration Required',
            'is_configured' => $rewardAmount !== null,
        ]);
    }
}
