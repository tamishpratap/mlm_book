<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Member;
use App\Models\RewardRankRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RewardRankService
{
    public function __construct(
        protected RewardRankValidationService $validationService,
        protected RewardRankResolver $resolver
    ) {}

    /**
     * Get all rank rules for Admin configuration.
     */
    public function getRules(): Collection
    {
        $this->ensureCanonicalRanksExist();

        return RewardRankRule::with(['createdBy:id,name,email', 'updatedBy:id,name,email'])
            ->orderBy('priority', 'asc')
            ->get();
    }

    /**
     * Ensure that all 5 canonical rank records exist in the database.
     */
    public function ensureCanonicalRanksExist(): void
    {
        $existingKeys = RewardRankRule::pluck('rank_key')->all();

        foreach (RewardRankRule::CANONICAL_RANKS as $key => $config) {
            if (!in_array($key, $existingKeys, true)) {
                RewardRankRule::create([
                    'rank_key' => $key,
                    'rank_name' => $config['name'],
                    'priority' => $config['priority'],
                    'referral_requirement' => $config['default_referral'],
                    'team_requirement' => $config['default_team'],
                    'reward_amount' => $config['default_reward'],
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Update an existing rank rule safely.
     */
    public function updateRule(RewardRankRule $rule, array $data, ?Admin $admin = null): RewardRankRule
    {
        $payload = [
            'rank_key' => $rule->rank_key,
            'referral_requirement' => $data['referral_requirement'] ?? $rule->referral_requirement,
            'team_requirement' => $data['team_requirement'] ?? $rule->team_requirement,
            'reward_amount' => $data['reward_amount'] ?? $rule->reward_amount,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $rule->is_active,
        ];

        $validation = $this->validationService->validateRankRuleDefinition($payload, $rule->id);
        if (!$validation['valid']) {
            throw new InvalidArgumentException($validation['errors'][0] ?? 'Rank rule validation failed.');
        }

        return DB::transaction(function () use ($rule, $payload, $admin) {
            $rule->update([
                'referral_requirement' => (int) $payload['referral_requirement'],
                'team_requirement' => (int) $payload['team_requirement'],
                'reward_amount' => round((float) $payload['reward_amount'], 4),
                'is_active' => (bool) $payload['is_active'],
                'updated_by' => $admin?->id,
            ]);

            RewardRankRule::clearCache();

            return $rule;
        });
    }

    /**
     * Store/configure a rank rule if missing.
     */
    public function createRule(array $data, ?Admin $admin = null): RewardRankRule
    {
        $validation = $this->validationService->validateRankRuleDefinition($data);
        if (!$validation['valid']) {
            throw new InvalidArgumentException($validation['errors'][0] ?? 'Rank rule validation failed.');
        }

        $key = strtolower(trim((string) $data['rank_key']));
        $canonical = RewardRankRule::CANONICAL_RANKS[$key];

        return DB::transaction(function () use ($data, $key, $canonical, $admin) {
            $rule = RewardRankRule::create([
                'rank_key' => $key,
                'rank_name' => $canonical['name'],
                'priority' => $canonical['priority'],
                'referral_requirement' => (int) $data['referral_requirement'],
                'team_requirement' => (int) $data['team_requirement'],
                'reward_amount' => round((float) $data['reward_amount'], 4),
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]);

            RewardRankRule::clearCache();

            return $rule;
        });
    }

    /**
     * Toggle the active status of a rank rule.
     */
    public function toggleStatus(RewardRankRule $rule, ?Admin $admin = null): RewardRankRule
    {
        $newStatus = !$rule->is_active;

        $rule->update([
            'is_active' => $newStatus,
            'updated_by' => $admin?->id,
        ]);

        RewardRankRule::clearCache();

        return $rule;
    }

    /**
     * Simulate rank and reward resolution using the central rank resolver.
     */
    public function previewResolution(int $referrals, int $team): array
    {
        return $this->resolver->resolveForMetrics($referrals, $team);
    }

    /**
     * Validate active set completeness and order.
     */
    public function validateActiveSet(): array
    {
        return $this->validationService->validateActiveSet();
    }
}
