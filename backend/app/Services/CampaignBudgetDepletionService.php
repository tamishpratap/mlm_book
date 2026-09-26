<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\RewardRankRule;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class CampaignBudgetDepletionService
{
    /**
     * Optional custom minimum reward resolver callback for testing or future extension.
     * Signature: fn(?AdCampaign $campaign = null): float
     *
     * @var callable|null
     */
    protected static $minimumRewardResolver = null;

    /**
     * Set or clear a custom minimum reward resolver callback.
     *
     * @param callable|null $resolver
     */
    public static function setMinimumRewardResolver(?callable $resolver): void
    {
        static::$minimumRewardResolver = $resolver;
    }

    /**
     * Resolve the current dynamic minimum active reward amount in USD.
     * Campaign-level safety threshold (smallest possible qualifying reward).
     *
     * Strict fail-closed validation:
     * - Fails if no active rules exist (never assumes $0.05 or $0.025 default).
     * - Fails if active rules have null, zero, or negative amounts.
     * - Fails if active rules have continuity or overlap errors.
     *
     * @param AdCampaign|null $campaign
     * @param string|null $ruleType
     * @return float
     *
     * @throws RuntimeException
     */
    public function resolveMinimumRewardAmount(?AdCampaign $campaign = null, ?string $ruleType = null): float
    {
        if (static::$minimumRewardResolver !== null) {
            $resolved = (float) call_user_func(static::$minimumRewardResolver, $campaign);
            if ($resolved <= 0.0) {
                throw new RuntimeException('Cannot resolve minimum reward: Custom resolver returned a non-positive amount.');
            }
            return round($resolved, 4);
        }

        // Determine scope: explicit $ruleType > campaign type > default to TYPE_EVENT
        $type = $ruleType;
        if ($type === null) {
            if ($campaign && $campaign->campaign_type === AdCampaign::TYPE_BUSINESS_PAGE) {
                $type = AdRewardRule::TYPE_BUSINESS_AD;
            } else {
                $type = AdRewardRule::TYPE_EVENT;
            }
        }

        // Check dynamic RewardRankRule first
        if (RewardRankRule::active()->exists()) {
            $minRankReward = RewardRankRule::getMinimumActiveRewardAmount();
            if ($minRankReward !== null && $minRankReward > 0.0) {
                return round((float) $minRankReward, 4);
            }
        }

        // 1. Fetch active rules from canonical AdRewardRule for target type
        $activeRules = AdRewardRule::ofType($type)->active()->get();

        if ($activeRules->isEmpty()) {
            throw new RuntimeException('Cannot resolve minimum reward: No active reward rules are configured in the system.');
        }

        // 2. Validate configuration continuity and overlap
        $continuityErrors = AdRewardRule::validateActiveSetContinuity($type);
        if (!empty($continuityErrors)) {
            throw new RuntimeException(
                'Cannot resolve minimum reward: Active reward rules have configuration errors: ' . implode('; ', $continuityErrors)
            );
        }

        // 3. Validate each active rule's amount strictly (fail closed)
        $validAmounts = [];
        foreach ($activeRules as $rule) {
            if ($rule->reward_amount === null || $rule->reward_amount === '' || !is_numeric($rule->reward_amount)) {
                throw new RuntimeException("Cannot resolve minimum reward: Active rule #{$rule->id} has a null or non-numeric reward amount.");
            }

            $amount = (float) $rule->reward_amount;
            if ($amount <= 0.0) {
                throw new RuntimeException("Cannot resolve minimum reward: Active rule #{$rule->id} has a zero or negative reward amount ({$amount}).");
            }

            $validAmounts[] = $amount;
        }

        if (empty($validAmounts)) {
            throw new RuntimeException('Cannot resolve minimum reward: No valid positive reward amounts found in active reward rules.');
        }

        $minReward = min($validAmounts);
        return round((float) $minReward, 4);
    }

    /**
     * Check if a campaign's remaining budget is sufficient for the dynamic minimum reward.
     *
     * @param AdCampaign $campaign
     * @return bool
     */
    public function isBudgetSufficient(AdCampaign $campaign): bool
    {
        $minReward = $this->resolveMinimumRewardAmount($campaign);
        return (float) ($campaign->remaining_amount ?? 0.00) >= $minReward;
    }

    /**
     * Check if a campaign's remaining budget is exhausted (< dynamic minimum reward).
     *
     * @param AdCampaign $campaign
     * @return bool
     */
    public function isBudgetExhausted(AdCampaign $campaign): bool
    {
        $minReward = $this->resolveMinimumRewardAmount($campaign);
        return (float) ($campaign->remaining_amount ?? 0.00) < $minReward;
    }

    /**
     * Authoritatively check if an Event Campaign's remaining budget is below the minimum active reward,
     * and transition it to the canonical stopped state if needed.
     *
     * @param AdCampaign $campaign
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function checkAndEnforceExhaustion(AdCampaign $campaign): array
    {
        if (!$campaign->exists) {
            throw new InvalidArgumentException('Cannot evaluate exhaustion for a non-persisted Campaign.');
        }

        $minReward = $this->resolveMinimumRewardAmount($campaign);

        return DB::transaction(function () use ($campaign, $minReward) {
            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();
            if (!$lockedCampaign) {
                throw new RuntimeException("Campaign #{$campaign->id} not found under row lock.");
            }

            $remaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);
            $isExhausted = ($remaining < $minReward);
            $transitioned = false;

            if ($isExhausted && in_array($lockedCampaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
                $previousStatus = $lockedCampaign->status;
                $lockedCampaign->status = AdCampaign::STATUS_STOPPED;

                $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
                $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
                $lifecycleHistory[] = [
                    'action' => 'budget_exhausted_stop',
                    'from_status' => $previousStatus,
                    'to_status' => AdCampaign::STATUS_STOPPED,
                    'remaining_amount' => $remaining,
                    'minimum_reward' => $minReward,
                    'timestamp' => now()->toISOString(),
                ];
                $targetAudience['lifecycle_history'] = $lifecycleHistory;
                $lockedCampaign->target_audience = $targetAudience;
                $lockedCampaign->save();

                $transitioned = true;

                Log::info('Campaign auto-stopped due to budget exhaustion below minimum reward', [
                    'campaign_id' => $lockedCampaign->id,
                    'campaign_type' => $lockedCampaign->campaign_type,
                    'event_id' => $lockedCampaign->event_id,
                    'remaining_amount' => $remaining,
                    'minimum_reward' => $minReward,
                    'previous_status' => $previousStatus,
                    'new_status' => AdCampaign::STATUS_STOPPED,
                ]);
            }

            return [
                'campaign_id' => $lockedCampaign->id,
                'campaign_type' => $lockedCampaign->campaign_type,
                'remaining_amount' => $remaining,
                'minimum_reward' => $minReward,
                'is_exhausted' => $isExhausted,
                'transitioned' => $transitioned,
                'status' => $lockedCampaign->status,
            ];
        });
    }

    /**
     * Atomically consume an authorized spend amount from an Event Campaign.
     *
     * @param Event $event
     * @param float|string|int $amount
     * @param string $spendReference Unique idempotency/spend reference identifier
     * @param array $metadata Optional audit metadata
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function depleteEventBudget(
        Event $event,
        $amount,
        string $spendReference,
        array $metadata = []
    ): array {
        if (!$event->exists) {
            throw new InvalidArgumentException('Cannot deplete budget for a non-persisted Event.');
        }

        $campaign = $event->campaign()->first();

        if (!$campaign) {
            throw new RuntimeException("Event #{$event->id} does not have an associated campaign foundation.");
        }

        if ($campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            throw new RuntimeException("Campaign #{$campaign->id} associated with Event #{$event->id} is not an event campaign.");
        }

        return $this->depleteCampaignBudget($campaign, $amount, $spendReference, $event, $metadata);
    }

    /**
     * Atomically consume an authorized spend amount from an AdCampaign (Shared Engine).
     *
     * @param AdCampaign $campaign
     * @param float|string|int $amount
     * @param string $spendReference Unique idempotency/spend reference identifier
     * @param Event|null $eventContext Optional Event to strictly verify Event-to-Campaign relationship
     * @param array $metadata Optional audit metadata
     * @return array
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function depleteCampaignBudget(
        AdCampaign $campaign,
        $amount,
        string $spendReference,
        ?Event $eventContext = null,
        array $metadata = []
    ): array {
        // 1. Spend Reference Validation
        $spendReference = trim((string) $spendReference);
        if ($spendReference === '') {
            throw new InvalidArgumentException('A unique spend reference is required for budget depletion.');
        }

        // 2. Amount Validation
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Spend amount must be a valid numeric value.');
        }

        $floatAmount = (float) $amount;

        if (is_nan($floatAmount) || is_infinite($floatAmount)) {
            throw new InvalidArgumentException('Spend amount must be a finite numeric value.');
        }

        if ($floatAmount <= 0.0) {
            throw new InvalidArgumentException('Spend amount must be greater than zero.');
        }

        // Precision check: maximum 4 decimal places (matching database precision decimal:4)
        $numericAmount = round($floatAmount, 4);
        if (abs($floatAmount - $numericAmount) > 0.00001) {
            throw new InvalidArgumentException('Spend amount exceeds maximum allowable precision of 4 decimal places.');
        }

        // 3. Currency Validation
        $currency = strtoupper((string) ($metadata['currency'] ?? $campaign->currency ?? 'USD'));
        if ($currency !== 'USD') {
            throw new InvalidArgumentException("Unsupported currency '{$currency}'. Only USD is supported.");
        }

        // 4. Campaign Existence & Context Integrity
        if (!$campaign->exists) {
            throw new InvalidArgumentException('Cannot deplete budget for a non-persisted Campaign.');
        }

        if ($eventContext !== null) {
            if ($campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
                throw new InvalidArgumentException("Campaign #{$campaign->id} is not an event campaign.");
            }
            if ((int) $campaign->event_id !== (int) $eventContext->id) {
                throw new InvalidArgumentException("Campaign #{$campaign->id} does not belong to Event #{$eventContext->id}.");
            }
        }

        // 5. Atomic Transaction & Concurrency Protection
        return DB::transaction(function () use (
            $campaign,
            $numericAmount,
            $spendReference,
            $eventContext,
            $metadata
        ) {
            // Pessimistic lock on Event (if context supplied)
            if ($eventContext !== null) {
                Event::where('id', $eventContext->id)->lockForUpdate()->first();
            }

            // Pessimistic lock on AdCampaign record
            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

            if (!$lockedCampaign) {
                throw new RuntimeException("Campaign #{$campaign->id} not found under row lock.");
            }

            $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
            $spendHistory = (array) ($targetAudience['spend_history'] ?? []);

            // 6. Idempotency Check (processed spend replay succeeds regardless of subsequent campaign status)
            foreach ($spendHistory as $entry) {
                if (($entry['spend_reference'] ?? null) === $spendReference) {
                    return [
                        'success' => true,
                        'campaign' => $lockedCampaign,
                        'consumed_amount' => (float) ($entry['amount'] ?? 0.0),
                        'previous_remaining' => (float) ($entry['previous_remaining'] ?? 0.0),
                        'new_remaining' => (float) ($entry['new_remaining'] ?? 0.0),
                        'previous_spent' => (float) ($entry['previous_spent'] ?? 0.0),
                        'new_spent' => (float) ($entry['new_spent'] ?? 0.0),
                        'is_replay' => true,
                        'spend_reference' => $spendReference,
                        'is_exhausted' => in_array($lockedCampaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_BUDGET_EXHAUSTED], true),
                        'campaign_status' => $lockedCampaign->status,
                        'message' => "Spend operation '{$spendReference}' was already processed.",
                    ];
                }
            }

            // 7. Campaign State & Eligibility Verification
            if ($lockedCampaign->approval_status !== AdCampaign::APPROVAL_APPROVED ||
                $lockedCampaign->status !== AdCampaign::STATUS_ACTIVE) {
                throw new RuntimeException(
                    "Campaign #{$lockedCampaign->id} is not eligible for spending. Current status: '{$lockedCampaign->status}' (Approval: '{$lockedCampaign->approval_status}')."
                );
            }

            // 8. Insufficient Budget Protection
            $currentRemaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);

            if ($currentRemaining < $numericAmount) {
                throw new RuntimeException(
                    "Insufficient campaign budget. Available: \${$currentRemaining} USD, Requested: \${$numericAmount} USD."
                );
            }

            // 9. Atomic Accounting Update
            $previousSpent = (float) ($lockedCampaign->spent_amount ?? 0.00);
            $newRemaining = max(0.00, round($currentRemaining - $numericAmount, 4));
            $newSpent = round($previousSpent + $numericAmount, 4);

            $lockedCampaign->remaining_amount = $newRemaining;
            $lockedCampaign->spent_amount = $newSpent;

            // 10. Minimum Reward Threshold & Campaign Stop/Exhaustion Evaluation
            $minReward = $this->resolveMinimumRewardAmount($lockedCampaign);
            $isExhausted = ($newRemaining < $minReward);

            if ($isExhausted) {
                $previousStatus = $lockedCampaign->status;
                $lockedCampaign->status = AdCampaign::STATUS_STOPPED;

                $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
                $lifecycleHistory[] = [
                    'action' => 'budget_exhausted_stop',
                    'from_status' => $previousStatus,
                    'to_status' => AdCampaign::STATUS_STOPPED,
                    'remaining_amount' => $newRemaining,
                    'minimum_reward' => $minReward,
                    'spend_reference' => $spendReference,
                    'timestamp' => now()->toISOString(),
                ];
                $targetAudience['lifecycle_history'] = $lifecycleHistory;

                Log::info('Campaign auto-stopped due to budget exhaustion below minimum reward', [
                    'campaign_id' => $lockedCampaign->id,
                    'campaign_type' => $lockedCampaign->campaign_type,
                    'event_id' => $lockedCampaign->event_id,
                    'remaining_amount' => $newRemaining,
                    'minimum_reward' => $minReward,
                    'spend_reference' => $spendReference,
                    'previous_status' => $previousStatus,
                    'new_status' => AdCampaign::STATUS_STOPPED,
                ]);
            }

            // 11. Persist Auditable Spend History in Campaign Ledger
            $spendHistory[] = [
                'spend_reference' => $spendReference,
                'amount' => $numericAmount,
                'spent_at' => now()->toISOString(),
                'previous_remaining' => $currentRemaining,
                'new_remaining' => $newRemaining,
                'previous_spent' => $previousSpent,
                'new_spent' => $newSpent,
                'metadata' => $metadata,
            ];

            $targetAudience['spend_history'] = $spendHistory;
            $lockedCampaign->target_audience = $targetAudience;

            $lockedCampaign->save();

            Log::info('Campaign budget consumed successfully', [
                'campaign_id' => $lockedCampaign->id,
                'campaign_type' => $lockedCampaign->campaign_type,
                'event_id' => $lockedCampaign->event_id,
                'spend_reference' => $spendReference,
                'amount' => $numericAmount,
                'previous_remaining' => $currentRemaining,
                'new_remaining' => $newRemaining,
                'previous_spent' => $previousSpent,
                'new_spent' => $newSpent,
                'is_exhausted' => $isExhausted,
                'minimum_reward' => $minReward,
            ]);

            return [
                'success' => true,
                'campaign' => $lockedCampaign,
                'consumed_amount' => $numericAmount,
                'previous_remaining' => $currentRemaining,
                'new_remaining' => $newRemaining,
                'previous_spent' => $previousSpent,
                'new_spent' => $newSpent,
                'is_replay' => false,
                'spend_reference' => $spendReference,
                'is_exhausted' => $isExhausted,
                'minimum_reward' => $minReward,
                'campaign_status' => $lockedCampaign->status,
                'message' => $isExhausted
                    ? "Successfully consumed \${$numericAmount} USD from campaign budget. Remaining budget (\${$newRemaining} USD) is below minimum reward (\${$minReward} USD). Campaign has been stopped."
                    : "Successfully consumed \${$numericAmount} USD from campaign budget.",
            ];
        });
    }
}
