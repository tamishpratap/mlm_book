<?php

namespace App\Services;

use App\Models\RewardRankRule;
use Illuminate\Database\Eloquent\Collection;

class RewardRankValidationService
{
    public const SUPPORTED_CURRENCY = 'USD';

    /**
     * Validate an individual rank rule definition before create or update.
     */
    public function validateRankRuleDefinition(array $data, ?int $ignoreId = null): array
    {
        $errors = [];
        $errorCodes = [];

        // 1. Rank Key validation
        if (empty($data['rank_key'])) {
            $errors[] = 'Rank is required.';
            $errorCodes[] = 'rank_required';
        } else {
            $key = strtolower(trim((string) $data['rank_key']));
            if (!array_key_exists($key, RewardRankRule::CANONICAL_RANKS)) {
                $allowed = implode(', ', array_keys(RewardRankRule::CANONICAL_RANKS));
                $errors[] = "Invalid rank '{$data['rank_key']}'. Valid ranks are: {$allowed}.";
                $errorCodes[] = 'invalid_rank';
            } else {
                // Duplicate prevention check
                $duplicateQuery = RewardRankRule::where('rank_key', $key);
                if ($ignoreId !== null) {
                    $duplicateQuery->where('id', '!=', $ignoreId);
                }
                if ($duplicateQuery->exists()) {
                    $errors[] = "A configuration for rank '{$key}' already exists. Only one configuration per rank is allowed.";
                    $errorCodes[] = 'duplicate_rank';
                }
            }
        }

        // 2. Referral Requirement
        if (!array_key_exists('referral_requirement', $data) || $data['referral_requirement'] === null || $data['referral_requirement'] === '') {
            $errors[] = 'Referral requirement is required.';
            $errorCodes[] = 'referral_required';
        } elseif (!is_numeric($data['referral_requirement']) || (int) $data['referral_requirement'] < 0 || (int) $data['referral_requirement'] != $data['referral_requirement']) {
            $errors[] = 'Referral requirement must be a non-negative integer (0 or greater).';
            $errorCodes[] = 'invalid_referral_requirement';
        }

        // 3. Team Requirement
        if (!array_key_exists('team_requirement', $data) || $data['team_requirement'] === null || $data['team_requirement'] === '') {
            $errors[] = 'Team requirement is required.';
            $errorCodes[] = 'team_required';
        } elseif (!is_numeric($data['team_requirement']) || (int) $data['team_requirement'] < 0 || (int) $data['team_requirement'] != $data['team_requirement']) {
            $errors[] = 'Team requirement must be a non-negative integer (0 or greater).';
            $errorCodes[] = 'invalid_team_requirement';
        }

        // 4. Reward Amount
        if (!array_key_exists('reward_amount', $data) || $data['reward_amount'] === null || $data['reward_amount'] === '') {
            $errors[] = 'Reward amount is required.';
            $errorCodes[] = 'reward_amount_required';
        } elseif (!is_numeric($data['reward_amount'])) {
            $errors[] = 'Reward amount must be a numeric value.';
            $errorCodes[] = 'invalid_reward_amount';
        } else {
            $amount = (float) $data['reward_amount'];
            if (is_nan($amount) || is_infinite($amount) || $amount < 0.0) {
                $errors[] = 'Reward amount must be a non-negative number.';
                $errorCodes[] = 'negative_reward_amount';
            } else {
                // Precision check: at most 4 decimal places
                $strAmount = (string) $data['reward_amount'];
                $dotPos = strpos($strAmount, '.');
                if ($dotPos !== false && strlen(substr($strAmount, $dotPos + 1)) > 4) {
                    $errors[] = 'Reward amount precision cannot exceed 4 decimal places.';
                    $errorCodes[] = 'invalid_decimal_precision';
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'error_codes' => $errorCodes,
        ];
    }

    /**
     * Validate the entire set of active rank rules for completeness and logical hierarchy order.
     */
    public function validateActiveSet(?Collection $rules = null): array
    {
        $allRules = $rules ?? RewardRankRule::orderBy('priority', 'asc')->get();

        $canonicalKeys = array_keys(RewardRankRule::CANONICAL_RANKS);
        $configuredKeys = $allRules->pluck('rank_key')->all();
        $activeRules = $allRules->where('is_active', true)->sortBy('priority')->values();
        $activeKeys = $activeRules->pluck('rank_key')->all();

        // 1. Completeness Check
        $missingConfigured = array_diff($canonicalKeys, $configuredKeys);
        $missingActive = array_diff($canonicalKeys, $activeKeys);

        $missingLabels = array_map(function ($k) {
            return RewardRankRule::CANONICAL_RANKS[$k]['name'] ?? ucfirst($k);
        }, $missingActive);

        $isComplete = empty($missingActive);
        $completenessErrors = [];
        if (!empty($missingActive)) {
            $completenessErrors[] = 'Reward configuration incomplete. Missing active ranks: ' . implode(', ', $missingLabels) . '.';
        }

        // 2. Order & Inconsistency Validation (Warnings)
        $orderWarnings = [];
        for ($i = 0; $i < $activeRules->count() - 1; $i++) {
            $current = $activeRules[$i];
            $next = $activeRules[$i + 1];

            // If next higher rank requires less referrals than current rank:
            if ((int) $next->referral_requirement < (int) $current->referral_requirement) {
                $orderWarnings[] = "Logical order warning: Higher rank '{$next->rank_name}' requires fewer referrals (" .
                    "{$next->referral_requirement}) than lower rank '{$current->rank_name}' ({$current->referral_requirement}).";
            }

            // If next higher rank requires less team than current rank:
            if ((int) $next->team_requirement < (int) $current->team_requirement) {
                $orderWarnings[] = "Logical order warning: Higher rank '{$next->rank_name}' requires fewer team members (" .
                    "{$next->team_requirement}) than lower rank '{$current->rank_name}' ({$current->team_requirement}).";
            }

            // If next higher rank pays less reward than current rank:
            if ((float) $next->reward_amount < (float) $current->reward_amount) {
                $orderWarnings[] = "Reward order warning: Higher rank '{$next->rank_name}' pays less reward ($" .
                    "{$next->reward_amount}) than lower rank '{$current->rank_name}' ($" . "{$current->reward_amount}).";
            }
        }

        return [
            'valid' => $isComplete && empty($orderWarnings),
            'is_complete' => $isComplete,
            'configured_count' => count($configuredKeys),
            'active_count' => count($activeKeys),
            'total_canonical_count' => count($canonicalKeys),
            'missing_active_ranks' => array_values($missingActive),
            'missing_active_labels' => array_values($missingLabels),
            'errors' => $completenessErrors,
            'warnings' => $orderWarnings,
        ];
    }
}
