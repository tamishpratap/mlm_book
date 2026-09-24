<?php

namespace App\Services;

use App\Models\AdRewardRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RewardRuleValidationService
{
    public const SUPPORTED_CURRENCY = 'USD';
    public const DEFAULT_DETERMINISTIC_SAMPLE_MAX = 100;

    /**
     * Validate an individual rule definition before create or update.
     *
     * @param array $data Input attributes (min_referrals, max_referrals, reward_amount, is_active, currency, etc.)
     * @param int|null $ignoreId Rule ID to ignore when checking overlap
     * @param string $type Scope: 'event' or 'business_ad'
     * @return array ['valid' => bool, 'errors' => string[], 'error_codes' => string[], 'details' => array]
     */
    public function validateRuleDefinition(array $data, ?int $ignoreId = null, string $type = AdRewardRule::TYPE_EVENT): array
    {
        $errors = [];
        $errorCodes = [];
        $details = [];

        // 1. Rule Scope
        if (!in_array($type, [AdRewardRule::TYPE_EVENT, AdRewardRule::TYPE_BUSINESS_AD], true)) {
            $errors[] = "Invalid rule scope '{$type}'. Only 'event' and 'business_ad' are supported.";
            $errorCodes[] = 'invalid_rule_scope';
        }

        // 2. Minimum Referrals
        if (!array_key_exists('min_referrals', $data) || $data['min_referrals'] === null || $data['min_referrals'] === '') {
            $errors[] = 'Minimum referrals is required.';
            $errorCodes[] = 'min_referrals_required';
        } elseif (!is_numeric($data['min_referrals']) || (int) $data['min_referrals'] < 0 || (int) $data['min_referrals'] != $data['min_referrals']) {
            $errors[] = 'Minimum referrals must be a non-negative integer (0 or greater).';
            $errorCodes[] = 'invalid_min_referrals';
        }

        $min = isset($data['min_referrals']) && is_numeric($data['min_referrals']) ? (int) $data['min_referrals'] : null;

        // 3. Maximum Referrals
        $max = null;
        if (array_key_exists('max_referrals', $data) && $data['max_referrals'] !== null && $data['max_referrals'] !== '') {
            if (!is_numeric($data['max_referrals']) || (int) $data['max_referrals'] != $data['max_referrals']) {
                $errors[] = 'Maximum referrals must be an integer or null for an open-ended tier.';
                $errorCodes[] = 'invalid_max_referrals';
            } else {
                $max = (int) $data['max_referrals'];
                if ($max < 0) {
                    $errors[] = 'Maximum referrals cannot be negative.';
                    $errorCodes[] = 'negative_max_referrals';
                } elseif ($min !== null && $max < $min) {
                    $errors[] = "Maximum referrals ({$max}) cannot be less than minimum referrals ({$min}).";
                    $errorCodes[] = 'inverted_referral_range';
                }
            }
        }

        // 4. Currency Validation
        if (isset($data['currency']) && strtoupper(trim((string) $data['currency'])) !== self::SUPPORTED_CURRENCY) {
            $errors[] = "Unsupported currency '{$data['currency']}'. Only '" . self::SUPPORTED_CURRENCY . "' is supported.";
            $errorCodes[] = 'unsupported_currency';
        }

        // 5. Reward Amount
        $reward = null;
        $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : false;

        if (array_key_exists('reward_amount', $data) && $data['reward_amount'] !== null && $data['reward_amount'] !== '') {
            if (!is_numeric($data['reward_amount'])) {
                $errors[] = 'Reward amount must be a numeric value.';
                $errorCodes[] = 'invalid_reward_amount';
            } else {
                $rawAmount = (float) $data['reward_amount'];
                if (is_nan($rawAmount) || is_infinite($rawAmount)) {
                    $errors[] = 'Reward amount must be a finite number.';
                    $errorCodes[] = 'invalid_reward_amount';
                } elseif ($rawAmount < 0.0) {
                    $errors[] = 'Reward amount cannot be negative.';
                    $errorCodes[] = 'negative_reward_amount';
                } elseif ($rawAmount > AdRewardRule::MAX_PERMISSIBLE_REWARD_USD) {
                    $errors[] = 'Reward amount cannot exceed $' . number_format(AdRewardRule::MAX_PERMISSIBLE_REWARD_USD, 4) . ' USD.';
                    $errorCodes[] = 'reward_exceeds_maximum';
                } else {
                    // Precision check: at most 4 decimal places
                    $strAmount = (string) $data['reward_amount'];
                    $dotPos = strpos($strAmount, '.');
                    if ($dotPos !== false && strlen(substr($strAmount, $dotPos + 1)) > 4) {
                        $errors[] = 'Reward amount precision cannot exceed 4 decimal places.';
                        $errorCodes[] = 'invalid_decimal_precision';
                    }
                    $reward = round($rawAmount, 4);
                }
            }
        }

        // Active rules must have configured positive reward
        if ($isActive) {
            if ($reward === null || $reward <= 0.0) {
                $errors[] = 'An active reward rule must have a configured positive reward amount greater than $0.0000 USD.';
                $errorCodes[] = 'active_rule_missing_positive_reward';
            }
        }

        // 6. Overlap Check if rule is active and min is valid
        if ($isActive && $min !== null && empty($errors)) {
            $overlappingRule = AdRewardRule::checkOverlap($min, $max, $ignoreId, $type);
            if ($overlappingRule) {
                $eLabel = $overlappingRule->min_referrals . ($overlappingRule->max_referrals !== null ? '–' . $overlappingRule->max_referrals : '+');
                $targetLabel = $min . ($max !== null ? '–' . $max : '+');
                $errors[] = "Range {$targetLabel} overlaps with existing active {$type} rule #{$overlappingRule->id} ({$eLabel}). Overlapping active ranges are strictly prohibited.";
                $errorCodes[] = 'range_overlap';
                $details['conflicting_rule_id'] = $overlappingRule->id;
                $details['conflicting_range'] = $eLabel;
            }
        }

        return [
            'valid' => empty($errors),
            'status' => empty($errors) ? 'valid' : 'invalid',
            'errors' => $errors,
            'error_codes' => $errorCodes,
            'details' => $details,
        ];
    }

    /**
     * Authoritatively validate the entire active rule set for a given scope.
     * Evaluates all structural invariants:
     * - Non-empty active set (fails closed, zero fallback)
     * - Baseline starts at 0
     * - Monotonic ordering
     * - Zero duplicate thresholds or intervals
     * - Zero gaps
     * - Zero overlaps
     * - Exactly one open-ended top tier (max_referrals IS NULL) placed at the highest tier
     * - All active tiers have configured positive reward amounts
     * - Property-style deterministic 1:1 coverage verification
     *
     * @param string $type Scope: 'event' or 'business_ad'
     * @return array
     */
    public function validateActiveSet(string $type = AdRewardRule::TYPE_EVENT): array
    {
        $errors = [];
        $errorCodes = [];
        $rules = AdRewardRule::ofType($type)->active()->get();
        $activeCount = $rules->count();

        // 1. Non-empty check (fails closed, zero fallback)
        if ($rules->isEmpty()) {
            return [
                'valid' => false,
                'status' => 'invalid',
                'rule_type' => $type,
                'active_count' => 0,
                'errors' => ["No active {$type} reward rules are configured in the system."],
                'error_codes' => ['no_active_rules'],
                'tiers_summary' => [],
                'minimum_active_reward_usd' => null,
                'has_open_ended_tier' => false,
                'has_baseline_tier' => false,
                'deterministic_coverage' => [
                    'is_deterministic' => false,
                    'tested_domain' => [0, self::DEFAULT_DETERMINISTIC_SAMPLE_MAX],
                    'violations_count' => self::DEFAULT_DETERMINISTIC_SAMPLE_MAX + 1,
                    'first_violation' => ['count' => 0, 'reason' => 'No active rules configured.'],
                ],
            ];
        }

        // 2. Check baseline tier starts at 0
        $first = $rules->first();
        $hasBaseline = ((int) $first->min_referrals === 0);
        if (!$hasBaseline) {
            $errors[] = "The lowest active reward tier must start at 0 direct verified referrals (currently starts at {$first->min_referrals}).";
            $errorCodes[] = 'missing_baseline_tier';
        }

        // 3. Inspect individual tiers, duplicates, open-ended rules, gaps, overlaps
        $openEndedCount = 0;
        $openEndedIndex = -1;
        $seenMins = [];
        $tiersSummary = [];
        $validPositiveRewards = [];

        for ($i = 0; $i < $activeCount; $i++) {
            $current = $rules[$i];
            $currentMin = (int) $current->min_referrals;
            $currentMax = $current->max_referrals !== null ? (int) $current->max_referrals : null;
            $currentReward = $current->reward_amount !== null ? (float) $current->reward_amount : null;

            // Track duplicate min_referrals
            if (isset($seenMins[$currentMin])) {
                $prevId = $seenMins[$currentMin];
                $errors[] = "Duplicate tier starting threshold: Rule #{$current->id} and Rule #{$prevId} both start at {$currentMin} referrals.";
                $errorCodes[] = 'duplicate_tier';
            } else {
                $seenMins[$currentMin] = $current->id;
            }

            // Inverted range check
            if ($currentMax !== null && $currentMax < $currentMin) {
                $errors[] = "Rule #{$current->id} has inverted range ({$currentMin}–{$currentMax}). Maximum cannot be less than minimum.";
                $errorCodes[] = 'inverted_referral_range';
            }

            // Reward amount check on active rule
            if ($currentReward === null || $currentReward <= 0.0) {
                $errors[] = "Active tier {$currentMin}–" . ($currentMax ?? '+') . " (Rule #{$current->id}) has no configured positive reward amount.";
                $errorCodes[] = 'unconfigured_active_rule';
            } elseif ($currentReward > AdRewardRule::MAX_PERMISSIBLE_REWARD_USD) {
                $errors[] = "Active tier {$currentMin}–" . ($currentMax ?? '+') . " (Rule #{$current->id}) reward amount ($" . number_format($currentReward, 4) . ") exceeds maximum permissible limit ($" . AdRewardRule::MAX_PERMISSIBLE_REWARD_USD . ").";
                $errorCodes[] = 'reward_exceeds_maximum';
            } else {
                $validPositiveRewards[] = $currentReward;
            }

            // Track open-ended tiers
            if ($currentMax === null) {
                $openEndedCount++;
                $openEndedIndex = $i;
            }

            // Check relationship with subsequent tier
            if ($i < $activeCount - 1) {
                $next = $rules[$i + 1];
                $nextMin = (int) $next->min_referrals;
                $nextMax = $next->max_referrals !== null ? (int) $next->max_referrals : null;

                if ($currentMax === null) {
                    // Current is open-ended but not the last tier
                    $errors[] = "Only the highest active tier can have an unlimited maximum referrals setting. Tier {$currentMin}+ is placed before subsequent tier starting at {$nextMin}.";
                    $errorCodes[] = 'open_ended_not_highest';
                } else {
                    $expectedNextMin = $currentMax + 1;
                    if ($nextMin < $expectedNextMin) {
                        // Overlap
                        $errors[] = "Active tiers overlap between {$currentMin}–{$currentMax} and {$nextMin}–" . ($nextMax ?? '+') . ".";
                        $errorCodes[] = 'overlap_detected';
                    } elseif ($nextMin > $expectedNextMin) {
                        // Gap
                        $gapStart = $currentMax + 1;
                        $gapEnd = $nextMin - 1;
                        $gapLabel = ($gapStart === $gapEnd) ? (string) $gapStart : "{$gapStart} to {$gapEnd}";
                        $errors[] = "Gap detected in active tiers between referral count {$currentMax} and {$nextMin}. Missing configuration for referral count {$gapLabel}.";
                        $errorCodes[] = 'gap_detected';
                    }
                }
            }

            $tiersSummary[] = [
                'rule_id' => $current->id,
                'min_referrals' => $currentMin,
                'max_referrals' => $currentMax,
                'label' => $currentMin . ($currentMax !== null ? "–{$currentMax}" : '+'),
                'is_open_ended' => ($currentMax === null),
                'reward_amount_usd' => $currentReward,
                'is_configured' => ($currentReward !== null && $currentReward > 0.0),
            ];
        }

        // Open-ended tier invariants
        $hasOpenEndedTier = ($openEndedCount === 1 && $openEndedIndex === $activeCount - 1);
        if ($openEndedCount === 0) {
            $last = $rules->last();
            $errors[] = "Active configuration lacks an open-ended highest tier. Referral counts greater than {$last->max_referrals} will not match any active rule.";
            $errorCodes[] = 'missing_open_ended_tier';
        } elseif ($openEndedCount > 1) {
            $errors[] = "Multiple open-ended active tiers detected ({$openEndedCount} rules with unlimited referrals). Exactly one highest open-ended tier is permitted.";
            $errorCodes[] = 'multiple_open_ended_tiers';
        }

        // 4. Deterministic Coverage Property Check across [0..DEFAULT_DETERMINISTIC_SAMPLE_MAX]
        $coverageCheck = $this->verifyDeterministicCoverage($type, self::DEFAULT_DETERMINISTIC_SAMPLE_MAX);
        if (!$coverageCheck['is_deterministic']) {
            foreach ($coverageCheck['violations'] as $v) {
                if ($v['matched_rule_count'] === 0) {
                    $errors[] = "Coverage gap: referral count {$v['count']} matches 0 active rules.";
                    $errorCodes[] = 'coverage_gap';
                } elseif ($v['matched_rule_count'] > 1) {
                    $errors[] = "Ambiguous overlap: referral count {$v['count']} matches {$v['matched_rule_count']} active rules (IDs: " . implode(', ', $v['matched_rule_ids']) . ').';
                    $errorCodes[] = 'coverage_ambiguity';
                }
            }
        }

        $isValid = empty($errors);
        $minimumActiveReward = !empty($validPositiveRewards) ? min($validPositiveRewards) : null;

        return [
            'valid' => $isValid,
            'status' => $isValid ? 'valid' : 'invalid',
            'rule_type' => $type,
            'active_count' => $activeCount,
            'errors' => array_values(array_unique($errors)),
            'error_codes' => array_values(array_unique($errorCodes)),
            'tiers_summary' => $tiersSummary,
            'minimum_active_reward_usd' => $minimumActiveReward,
            'has_open_ended_tier' => $hasOpenEndedTier,
            'has_baseline_tier' => $hasBaseline,
            'deterministic_coverage' => $coverageCheck,
        ];
    }

    /**
     * Check if a specific rule can safely transition to active.
     *
     * @param AdRewardRule $rule
     * @return array ['can_activate' => bool, 'reason' => ?string, 'code' => ?string, 'conflicting_rule_id' => ?int]
     */
    public function canActivateRule(AdRewardRule $rule): array
    {
        // 1. Rule must have configured positive reward amount
        if ($rule->reward_amount === null || (float) $rule->reward_amount <= 0.0) {
            return [
                'can_activate' => false,
                'reason' => "Cannot activate rule #{$rule->id}. Active rules must have a configured positive reward amount greater than $0.0000 USD.",
                'code' => 'missing_reward_amount',
                'conflicting_rule_id' => null,
            ];
        }

        // 2. Enforce maximum cap
        if ((float) $rule->reward_amount > AdRewardRule::MAX_PERMISSIBLE_REWARD_USD) {
            return [
                'can_activate' => false,
                'reason' => "Cannot activate rule #{$rule->id}. Reward amount exceeds maximum permissible limit of $" . AdRewardRule::MAX_PERMISSIBLE_REWARD_USD . ' USD.',
                'code' => 'reward_exceeds_maximum',
                'conflicting_rule_id' => null,
            ];
        }

        // 3. Check for range overlaps with existing active rules of same scope
        $min = (int) $rule->min_referrals;
        $max = $rule->max_referrals !== null ? (int) $rule->max_referrals : null;
        $type = $rule->rule_type ?? AdRewardRule::TYPE_EVENT;

        $conflicting = AdRewardRule::checkOverlap($min, $max, $rule->id, $type);
        if ($conflicting) {
            $cLabel = $conflicting->min_referrals . ($conflicting->max_referrals !== null ? '–' . $conflicting->max_referrals : '+');
            $rLabel = $min . ($max !== null ? '–' . $max : '+');
            return [
                'can_activate' => false,
                'reason' => "Cannot activate rule #{$rule->id} ({$rLabel}). It overlaps with active {$type} rule #{$conflicting->id} ({$cLabel}).",
                'code' => 'range_overlap',
                'conflicting_rule_id' => $conflicting->id,
            ];
        }

        return [
            'can_activate' => true,
            'reason' => null,
            'code' => null,
            'conflicting_rule_id' => null,
        ];
    }

    /**
     * Run a property-style coverage check across a range of referral counts [0..$sampleMax].
     * Proves deterministic coverage: COUNT(N) == 1 for every integer N in the range.
     *
     * @param string $type
     * @param int $sampleMax
     * @return array
     */
    public function verifyDeterministicCoverage(string $type = AdRewardRule::TYPE_EVENT, int $sampleMax = 100): array
    {
        $activeRules = AdRewardRule::ofType($type)->active()->get();
        $violations = [];

        if ($activeRules->isEmpty()) {
            return [
                'is_deterministic' => false,
                'tested_domain' => [0, $sampleMax],
                'violations_count' => $sampleMax + 1,
                'violations' => [
                    [
                        'count' => 0,
                        'matched_rule_count' => 0,
                        'matched_rule_ids' => [],
                        'reason' => 'No active rules in set.',
                    ],
                ],
            ];
        }

        // Precompute rule intervals for high-speed deterministic checking
        $intervals = [];
        foreach ($activeRules as $r) {
            $intervals[] = [
                'id' => $r->id,
                'min' => (int) $r->min_referrals,
                'max' => $r->max_referrals !== null ? (int) $r->max_referrals : null,
            ];
        }

        for ($n = 0; $n <= $sampleMax; $n++) {
            $matches = [];
            foreach ($intervals as $interval) {
                if ($n >= $interval['min'] && ($interval['max'] === null || $n <= $interval['max'])) {
                    $matches[] = $interval['id'];
                }
            }

            $matchCount = count($matches);
            if ($matchCount !== 1) {
                // Keep only first 10 violations to avoid payload explosion
                if (count($violations) < 10) {
                    $violations[] = [
                        'count' => $n,
                        'matched_rule_count' => $matchCount,
                        'matched_rule_ids' => $matches,
                    ];
                }
            }
        }

        return [
            'is_deterministic' => empty($violations),
            'tested_domain' => [0, $sampleMax],
            'violations_count' => count($violations),
            'violations' => $violations,
        ];
    }
}
