<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdRewardRule;
use App\Services\RewardRuleValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventRewardRuleController extends Controller
{
    public function __construct(
        protected RewardRuleValidationService $validationService
    ) {}

    /**
     * Display a listing of paid event reward rules for the admin configuration panel.
     */
    public function index(Request $request): JsonResponse
    {
        $rules = AdRewardRule::forEvent()
            ->with(['createdBy:id,name,email', 'updatedBy:id,name,email'])
            ->orderBy('min_referrals', 'asc')
            ->get();

        // Check if baseline tier (0-something) or any active tier is incomplete
        $zeroTier = $rules->firstWhere('min_referrals', 0);
        $isConfigurationIncomplete = $zeroTier === null || $zeroTier->reward_amount === null;
        $incompleteWarning = $isConfigurationIncomplete
            ? 'Complete the baseline direct verified referral reward value (starts at 0) before finalizing the event reward engine.'
            : null;

        // Run full authoritative active set validation
        $activeValidation = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);
        $continuityErrors = $activeValidation['errors'];

        // Build standard preview test resolutions (0, 5, 6, 14, 15, 20)
        $testCounts = [0, 5, 6, 14, 15, 20];
        $previews = [];

        foreach ($testCounts as $count) {
            $resolved = AdRewardRule::resolveForDirectVerifiedReferrals($count, AdRewardRule::TYPE_EVENT);
            $rewardAmount = $resolved ? $resolved->reward_amount : null;

            $previews[] = [
                'direct_verified_referrals' => $count,
                'matched_rule_id' => $resolved ? $resolved->id : null,
                'range_label' => $resolved ? ($resolved->min_referrals . ($resolved->max_referrals !== null ? '–' . $resolved->max_referrals : '+')) : 'None',
                'reward_amount_usd' => $rewardAmount !== null ? (float) $rewardAmount : null,
                'formatted_reward' => $rewardAmount !== null ? '$' . number_format((float) $rewardAmount, 4) . ' USD' : 'Configuration Required',
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
            'active_set_validation' => $activeValidation,
            'previews' => $previews,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'max_permissible_reward_usd' => AdRewardRule::MAX_PERMISSIBLE_REWARD_USD,
        ]);
    }

    /**
     * Dedicated endpoint returning authoritative active set validation status.
     */
    public function validateActiveSet(Request $request): JsonResponse
    {
        $validation = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        return response()->json([
            'success' => true,
            'validation' => $validation,
        ]);
    }

    /**
     * Store a newly created event reward rule.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Initial Request Validation
        $validated = $request->validate([
            'min_referrals' => ['required', 'integer', 'min:0'],
            'max_referrals' => ['nullable', 'integer', 'gte:min_referrals'],
            'reward_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . AdRewardRule::MAX_PERMISSIBLE_REWARD_USD,
                'regex:/^\d+(\.\d{1,4})?$/',
            ],
            'is_active' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'string'],
        ], [
            'min_referrals.min' => 'Minimum direct verified referrals must be 0 or greater.',
            'max_referrals.gte' => 'Maximum direct verified referrals must be greater than or equal to minimum referrals.',
            'reward_amount.min' => 'Reward amount cannot be negative.',
            'reward_amount.max' => 'Reward amount cannot exceed $' . number_format(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, 4) . ' USD.',
            'reward_amount.regex' => 'Reward amount precision cannot exceed 4 decimal places.',
        ]);

        $min = (int) $validated['min_referrals'];
        $max = isset($validated['max_referrals']) && $validated['max_referrals'] !== '' ? (int) $validated['max_referrals'] : null;
        $rawReward = $request->input('reward_amount');
        $reward = isset($validated['reward_amount']) && $validated['reward_amount'] !== '' ? round((float) $validated['reward_amount'], 4) : null;
        $isActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : true;

        // 2. Authoritative Domain Validation
        $ruleData = [
            'min_referrals' => $min,
            'max_referrals' => $max,
            'reward_amount' => $rawReward !== null && $rawReward !== '' ? $rawReward : null,
            'is_active' => $isActive,
            'currency' => $request->input('currency', 'USD'),
        ];

        $domainValidation = $this->validationService->validateRuleDefinition($ruleData, null, AdRewardRule::TYPE_EVENT);
        if (!$domainValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $domainValidation['errors'][0] ?? 'Event reward rule validation failed.',
                'errors' => $domainValidation['errors'],
                'error_codes' => $domainValidation['error_codes'],
                'details' => $domainValidation['details'],
            ], 422);
        }

        $admin = auth('admin')->user();

        // 3. Atomic persistence with row-level concurrency lock
        $rule = DB::transaction(function () use ($min, $max, $reward, $isActive, $admin) {
            // Lock existing active event rules to prevent race conditions during insertion
            AdRewardRule::forEvent()->where('is_active', true)->lockForUpdate()->get();

            $created = AdRewardRule::create([
                'rule_type' => AdRewardRule::TYPE_EVENT,
                'min_referrals' => $min,
                'max_referrals' => $max,
                'reward_amount' => $reward,
                'is_active' => $isActive,
                'created_by' => $admin ? $admin->id : null,
                'updated_by' => $admin ? $admin->id : null,
            ]);

            AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => 'Event reward rule created successfully.',
            'rule' => $rule->load(['createdBy:id,name,email', 'updatedBy:id,name,email']),
            'currency' => 'USD',
            'currency_symbol' => '$',
        ], 201);
    }

    /**
     * Update an existing event reward rule safely without rewriting historical reward records.
     */
    public function update(Request $request, AdRewardRule $adRewardRule): JsonResponse
    {
        if ($adRewardRule->rule_type !== AdRewardRule::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Target rule is not an event reward rule.',
            ], 404);
        }

        $validated = $request->validate([
            'min_referrals' => ['required', 'integer', 'min:0'],
            'max_referrals' => ['nullable', 'integer', 'gte:min_referrals'],
            'reward_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . AdRewardRule::MAX_PERMISSIBLE_REWARD_USD,
                'regex:/^\d+(\.\d{1,4})?$/',
            ],
            'is_active' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'string'],
        ], [
            'min_referrals.min' => 'Minimum direct verified referrals must be 0 or greater.',
            'max_referrals.gte' => 'Maximum direct verified referrals must be greater than or equal to minimum referrals.',
            'reward_amount.min' => 'Reward amount cannot be negative.',
            'reward_amount.max' => 'Reward amount cannot exceed $' . number_format(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, 4) . ' USD.',
            'reward_amount.regex' => 'Reward amount precision cannot exceed 4 decimal places.',
        ]);

        $min = (int) $validated['min_referrals'];
        $max = isset($validated['max_referrals']) && $validated['max_referrals'] !== '' ? (int) $validated['max_referrals'] : null;
        $rawReward = $request->input('reward_amount');
        $reward = isset($validated['reward_amount']) && $validated['reward_amount'] !== '' ? round((float) $validated['reward_amount'], 4) : null;
        $isActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : $adRewardRule->is_active;

        // Authoritative Domain Validation
        $ruleData = [
            'min_referrals' => $min,
            'max_referrals' => $max,
            'reward_amount' => $rawReward !== null && $rawReward !== '' ? $rawReward : null,
            'is_active' => $isActive,
            'currency' => $request->input('currency', 'USD'),
        ];

        $domainValidation = $this->validationService->validateRuleDefinition($ruleData, $adRewardRule->id, AdRewardRule::TYPE_EVENT);
        if (!$domainValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $domainValidation['errors'][0] ?? 'Event reward rule validation failed.',
                'errors' => $domainValidation['errors'],
                'error_codes' => $domainValidation['error_codes'],
                'details' => $domainValidation['details'],
            ], 422);
        }

        $admin = auth('admin')->user();

        // Atomic update with concurrency lock
        DB::transaction(function () use ($adRewardRule, $min, $max, $reward, $isActive, $admin) {
            AdRewardRule::forEvent()->where('id', $adRewardRule->id)->lockForUpdate()->first();

            $adRewardRule->update([
                'min_referrals' => $min,
                'max_referrals' => $max,
                'reward_amount' => $reward,
                'is_active' => $isActive,
                'updated_by' => $admin ? $admin->id : null,
            ]);

            AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);
        });

        return response()->json([
            'success' => true,
            'message' => 'Event reward rule updated successfully.',
            'rule' => $adRewardRule->fresh(['createdBy:id,name,email', 'updatedBy:id,name,email']),
            'currency' => 'USD',
            'currency_symbol' => '$',
        ]);
    }

    /**
     * Safely enable or disable an event reward rule with atomic concurrency and invariant validation.
     */
    public function toggleStatus(Request $request, AdRewardRule $adRewardRule): JsonResponse
    {
        if ($adRewardRule->rule_type !== AdRewardRule::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Target rule is not an event reward rule.',
            ], 404);
        }

        return DB::transaction(function () use ($adRewardRule) {
            /** @var AdRewardRule $lockedRule */
            $lockedRule = AdRewardRule::forEvent()->where('id', $adRewardRule->id)->lockForUpdate()->first();
            $newStatus = !$lockedRule->is_active;

            // If activating, verify activation feasibility via validation service
            if ($newStatus) {
                $canActivate = $this->validationService->canActivateRule($lockedRule);
                if (!$canActivate['can_activate']) {
                    return response()->json([
                        'success' => false,
                        'message' => $canActivate['reason'],
                        'error_code' => $canActivate['code'],
                        'conflicting_rule_id' => $canActivate['conflicting_rule_id'],
                    ], 422);
                }
            }

            $admin = auth('admin')->user();

            $lockedRule->update([
                'is_active' => $newStatus,
                'updated_by' => $admin ? $admin->id : null,
            ]);

            AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

            $activeValidation = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

            return response()->json([
                'success' => true,
                'message' => 'Event reward rule ' . ($newStatus ? 'activated' : 'disabled') . ' successfully.',
                'rule' => $lockedRule->fresh(['createdBy:id,name,email', 'updatedBy:id,name,email']),
                'active_set_valid' => $activeValidation['valid'],
                'active_set_validation' => $activeValidation,
            ]);
        });
    }

    /**
     * Simulate reward resolution for an arbitrary direct verified referral count against active event rules.
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referral_count' => ['required', 'integer', 'min:0'],
        ]);

        $count = (int) $validated['referral_count'];
        $resolved = AdRewardRule::resolveForDirectVerifiedReferrals($count, AdRewardRule::TYPE_EVENT);
        $rewardAmount = $resolved ? $resolved->reward_amount : null;

        return response()->json([
            'success' => true,
            'referral_count' => $count,
            'matched_rule' => $resolved ? $resolved->load(['createdBy:id,name,email', 'updatedBy:id,name,email']) : null,
            'reward_amount_usd' => $rewardAmount !== null ? (float) $rewardAmount : null,
            'formatted_reward' => $rewardAmount !== null ? '$' . number_format((float) $rewardAmount, 4) . ' USD' : 'Configuration Required',
            'is_configured' => $rewardAmount !== null,
        ]);
    }
}
