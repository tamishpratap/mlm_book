<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdRewardRule;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RewardRuleService
{
    public function __construct(
        protected RewardRuleValidationService $validationService,
        protected RewardRuleResolver $resolver
    ) {}

    /**
     * Get all centralized reward rules for Admin configuration.
     */
    public function getRules(): Collection
    {
        return AdRewardRule::forCentral()
            ->with(['createdBy:id,name,email', 'updatedBy:id,name,email'])
            ->orderBy('min_referrals', 'asc')
            ->get();
    }

    /**
     * Validate baseline tier (starts at 0 referrals) and configuration completeness.
     */
    public function checkBaselineStatus(Collection $rules): array
    {
        $zeroTier = $rules->firstWhere('min_referrals', 0);
        $isConfigurationIncomplete = $zeroTier === null || $zeroTier->reward_amount === null || (float) $zeroTier->reward_amount <= 0;

        return [
            'is_configuration_complete' => !$isConfigurationIncomplete,
            'incomplete_warning' => $isConfigurationIncomplete
                ? 'Complete the baseline direct verified referral reward value (starts at 0) before finalizing the reward engine.'
                : null,
            'baseline_rule' => $zeroTier,
        ];
    }

    /**
     * Generate standard preview test resolutions.
     */
    public function getPreviews(array $testCounts = [0, 5, 6, 14, 15, 20]): array
    {
        $previews = [];

        foreach ($testCounts as $count) {
            $resolved = AdRewardRule::resolveForDirectVerifiedReferrals($count, AdRewardRule::TYPE_CENTRAL);
            $rewardAmount = $resolved ? $resolved->reward_amount : null;

            $previews[] = [
                'direct_verified_referrals' => $count,
                'matched_rule_id' => $resolved ? $resolved->id : null,
                'range_label' => $resolved ? ($resolved->min_referrals . ($resolved->max_referrals !== null ? '–' . $resolved->max_referrals : '+')) : 'None',
                'reward_amount_usd' => $rewardAmount !== null ? (float) $rewardAmount : null,
                'formatted_reward' => $rewardAmount !== null ? '$' . number_format((float) $rewardAmount, 4) . ' USD' : 'Configuration Required',
                'is_configured' => $rewardAmount !== null && (float) $rewardAmount > 0,
                'is_active_rule' => $resolved !== null && $resolved->is_active,
            ];
        }

        return $previews;
    }

    /**
     * Store a newly created central reward rule atomically with validation.
     */
    public function createRule(array $validatedData, ?Admin $admin = null): AdRewardRule
    {
        $min = (int) $validatedData['min_referrals'];
        $max = isset($validatedData['max_referrals']) && $validatedData['max_referrals'] !== '' ? (int) $validatedData['max_referrals'] : null;
        $reward = isset($validatedData['reward_amount']) && $validatedData['reward_amount'] !== '' ? round((float) $validatedData['reward_amount'], 4) : null;
        $isActive = isset($validatedData['is_active']) ? (bool) $validatedData['is_active'] : true;

        $domainValidation = $this->validationService->validateRuleDefinition([
            'min_referrals' => $min,
            'max_referrals' => $max,
            'reward_amount' => $reward,
            'is_active' => $isActive,
            'currency' => 'USD',
        ], null, AdRewardRule::TYPE_CENTRAL);

        if (!$domainValidation['valid']) {
            throw new InvalidArgumentException($domainValidation['errors'][0] ?? 'Central reward rule validation failed.');
        }

        return DB::transaction(function () use ($min, $max, $reward, $isActive, $admin) {
            // Lock active central rules to prevent race conditions during insertion
            AdRewardRule::forCentral()->where('is_active', true)->lockForUpdate()->get();

            $rule = AdRewardRule::create([
                'rule_type' => AdRewardRule::TYPE_CENTRAL,
                'min_referrals' => $min,
                'max_referrals' => $max,
                'reward_amount' => $reward,
                'is_active' => $isActive,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]);

            AdRewardRule::clearCache();

            return $rule;
        });
    }

    /**
     * Update an existing central reward rule safely.
     */
    public function updateRule(AdRewardRule $rule, array $validatedData, ?Admin $admin = null): AdRewardRule
    {
        $min = (int) $validatedData['min_referrals'];
        $max = isset($validatedData['max_referrals']) && $validatedData['max_referrals'] !== '' ? (int) $validatedData['max_referrals'] : null;
        $reward = isset($validatedData['reward_amount']) && $validatedData['reward_amount'] !== '' ? round((float) $validatedData['reward_amount'], 4) : null;
        $isActive = isset($validatedData['is_active']) ? (bool) $validatedData['is_active'] : $rule->is_active;

        $domainValidation = $this->validationService->validateRuleDefinition([
            'min_referrals' => $min,
            'max_referrals' => $max,
            'reward_amount' => $reward,
            'is_active' => $isActive,
            'currency' => 'USD',
        ], $rule->id, AdRewardRule::TYPE_CENTRAL);

        if (!$domainValidation['valid']) {
            throw new InvalidArgumentException($domainValidation['errors'][0] ?? 'Central reward rule validation failed.');
        }

        return DB::transaction(function () use ($rule, $min, $max, $reward, $isActive, $admin) {
            $rule->update([
                'min_referrals' => $min,
                'max_referrals' => $max,
                'reward_amount' => $reward,
                'is_active' => $isActive,
                'updated_by' => $admin?->id,
            ]);

            AdRewardRule::clearCache();

            return $rule;
        });
    }

    /**
     * Safely toggle the active status of a central reward rule.
     */
    public function toggleStatus(AdRewardRule $rule, ?Admin $admin = null): AdRewardRule
    {
        $newStatus = !$rule->is_active;

        if ($newStatus) {
            $canActivate = $this->validationService->canActivateRule($rule);
            if (!$canActivate['can_activate']) {
                throw new InvalidArgumentException($canActivate['reason'] ?? 'Cannot activate reward rule due to conflicting ranges.');
            }
        }

        $rule->update([
            'is_active' => $newStatus,
            'updated_by' => $admin?->id,
        ]);

        AdRewardRule::clearCache();

        return $rule;
    }

    /**
     * Simulate reward resolution for arbitrary direct verified referral count.
     */
    public function previewResolution(int $count): array
    {
        $resolved = AdRewardRule::resolveForDirectVerifiedReferrals($count, AdRewardRule::TYPE_CENTRAL);
        $rewardAmount = $resolved ? $resolved->reward_amount : null;
        $rangeLabel = $resolved
            ? ($resolved->max_referrals !== null ? "{$resolved->min_referrals}-{$resolved->max_referrals}" : "{$resolved->min_referrals}+")
            : null;

        return [
            'success' => true,
            'referral_count' => $count,
            'matched_rule' => $resolved?->load(['createdBy:id,name,email', 'updatedBy:id,name,email']),
            'tier_label' => $rangeLabel,
            'reward_amount_usd' => $rewardAmount !== null ? (float) $rewardAmount : null,
            'formatted_reward' => $rewardAmount !== null ? '$' . number_format((float) $rewardAmount, 4) . ' USD' : 'Configuration Required',
            'is_configured' => $rewardAmount !== null && (float) $rewardAmount > 0,
        ];
    }

    /**
     * Resolve reward for count against central rules.
     */
    public function resolveForCount(int $count, ?Member $member = null): array
    {
        return $this->resolver->resolveForCount($count, $member, AdRewardRule::TYPE_CENTRAL);
    }

    /**
     * Resolve reward for authenticated member against central rules.
     */
    public function resolveForMember(Member $member): array
    {
        return $this->resolver->resolveForMember($member, AdRewardRule::TYPE_CENTRAL);
    }
}
