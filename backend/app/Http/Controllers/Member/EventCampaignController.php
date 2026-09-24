<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\AdCampaign;
use App\Models\AdCampaignActivity;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use App\Services\AdDeliveryService;
use App\Services\CampaignBudgetDepletionService;
use App\Services\EventRewardResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EventCampaignController extends Controller
{
    /**
     * Show the campaign associated with the specified event.
     * Accessible only by the event organizer.
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();

        if (!$event->isOrganizer($member?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can view this event campaign.',
            ], 403);
        }

        $campaign = $event->campaign;

        if (!$campaign) {
            return response()->json([
                'success' => true,
                'message' => 'This event does not have an active campaign foundation yet.',
                'campaign' => null,
                'available_ad_funds' => round((float) ($member?->fresh()->ad_balance ?? 0.00), 2),
                'platform_fee_percent' => (float) Setting::get('campaign_platform_fee_percent', 2.50),
            ], 200);
        }

        return response()->json([
            'success' => true,
            'campaign' => $this->transformCampaign($campaign),
            'available_ad_funds' => round((float) ($member?->fresh()->ad_balance ?? 0.00), 2),
            'platform_fee_percent' => (float) Setting::get('campaign_platform_fee_percent', 2.50),
        ]);
    }

    /**
     * Create or retrieve the campaign foundation for the specified event, with optional initial budget allocation.
     * Idempotent: repeated calls for an event with an existing campaign return the existing campaign without double debit.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();

        // 1. Creator Verification Gate
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // 2. Strict Ownership Authorization (Never trust client-supplied owner ID)
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can create or manage a campaign for this event.',
            ], 403);
        }

        // 3. Validation
        $validated = $request->validate([
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:1.00', 'max:999999999.99'],
            'currency' => ['nullable', 'in:USD'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'target_audience' => ['nullable', 'array'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
        ], [
            'budget.min' => 'Minimum campaign budget is $1.00 USD.',
            'budget.numeric' => 'Campaign budget must be a valid numeric dollar amount.',
            'currency.in' => 'Only USD currency is supported for event campaigns.',
        ]);

        $campaignBudget = $request->filled('budget') && (float) $request->input('budget') > 0
            ? round((float) $request->input('budget'), 2)
            : 0.00;
        $idempotencyKey = $request->filled('idempotency_key') ? trim((string) $request->input('idempotency_key')) : null;

        // 4. Fast-path Idempotency Check
        $existing = $event->campaign()->first();
        if ($existing) {
            $existingBudget = (float) ($existing->budget ?? 0.00);

            // If existing campaign is already funded with budget > 0 or no budget requested, return existing
            if ($existingBudget > 0.00 || $campaignBudget <= 0.00) {
                return response()->json([
                    'success' => true,
                    'message' => 'Event campaign foundation already exists.',
                    'campaign' => $this->transformCampaign($existing),
                    'available_ad_funds' => round((float) ($member->fresh()->ad_balance ?? 0.00), 2),
                ], 200);
            }

            // If existing campaign is an unfunded draft ($existingBudget == 0) and user is now allocating budget > 0:
            return $this->executeInitialBudgetAllocation($event, $existing, $member, $campaignBudget, $idempotencyKey, $request);
        }

        // If no campaign exists and initial budget is requested:
        if ($campaignBudget > 0.00) {
            return $this->executeInitialBudgetAllocation($event, null, $member, $campaignBudget, $idempotencyKey, $request);
        }

        // 5. Transaction-safe Concurrency & Unfunded Draft Creation
        try {
            $campaign = DB::transaction(function () use ($event, $member, $request) {
                // Pessimistic lock on event to prevent race conditions
                /** @var Event $lockedEvent */
                $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->first();

                // Re-check for existing campaign inside transaction
                $existingInTx = AdCampaign::where('event_id', $lockedEvent->id)->lockForUpdate()->first();
                if ($existingInTx) {
                    return $existingInTx;
                }

                $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);

                // Initialize draft campaign foundation: ZERO wallet debit, ZERO fee charge, ZERO reward credit
                return AdCampaign::create([
                    'campaign_type' => AdCampaign::TYPE_EVENT,
                    'event_id' => $lockedEvent->id,
                    'member_id' => $member->id,
                    'business_page_id' => null,
                    'post_id' => null,
                    'campaign_name' => trim((string) ($request->input('campaign_name') ?: $lockedEvent->title)),
                    'budget' => 0.00,
                    'additional_funding' => 0.00,
                    'total_funded' => 0.00,
                    'spent_amount' => 0.00,
                    'remaining_amount' => 0.00,
                    'currency' => 'USD',
                    'fee_percent' => $feePercent,
                    'fee_amount' => 0.00,
                    'wallet_debit' => 0.00,
                    'target_audience' => $request->input('target_audience') ?: null,
                    'start_at' => $request->input('start_at') ?: null,
                    'end_at' => $request->input('end_at') ?: null,
                    'status' => AdCampaign::STATUS_DRAFT,
                    'approval_status' => AdCampaign::APPROVAL_PENDING,
                ]);
            });

            $statusCode = $campaign->wasRecentlyCreated ? 201 : 200;
            $message = $campaign->wasRecentlyCreated
                ? 'Event campaign foundation created successfully.'
                : 'Event campaign foundation already exists.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'campaign' => $this->transformCampaign($campaign),
                'available_ad_funds' => round((float) ($member->fresh()->ad_balance ?? 0.00), 2),
            ], $statusCode);

        } catch (\Throwable $e) {
            Log::error('Failed to create event campaign foundation', [
                'event_id' => $event->id,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initializing the event campaign foundation.',
            ], 500);
        }
    }

    /**
     * Explicitly allocate initial campaign budget to an event campaign.
     */
    public function allocateBudget(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // 1. Creator Verification Gate
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // 2. Strict Ownership Authorization
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can allocate budget for this event.',
            ], 403);
        }

        // 3. Amount & Currency Validation
        $validated = $request->validate([
            'budget' => ['required', 'numeric', 'min:1.00', 'max:999999999.99'],
            'currency' => ['nullable', 'in:USD'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ], [
            'budget.required' => 'Please specify the campaign budget to allocate.',
            'budget.min' => 'Minimum campaign budget is $1.00 USD.',
            'budget.numeric' => 'Campaign budget must be a valid numeric dollar amount.',
            'currency.in' => 'Only USD currency is supported for event campaigns.',
        ]);

        $campaignBudget = round((float) $validated['budget'], 2);
        $idempotencyKey = !empty($validated['idempotency_key']) ? trim((string) $validated['idempotency_key']) : null;

        $campaign = $event->campaign()->first();
        if ($campaign && (float) $campaign->budget > 0.00) {
            // Check idempotency replay
            $targetAudience = (array) ($campaign->target_audience ?? []);
            $fundingHistory = (array) ($targetAudience['funding_history'] ?? []);
            if ($idempotencyKey) {
                foreach ($fundingHistory as $entry) {
                    if (($entry['idempotency_key'] ?? null) === $idempotencyKey) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Campaign budget was already allocated.',
                            'campaign' => $this->transformCampaign($campaign),
                            'available_ad_funds' => round((float) ($member->fresh()->ad_balance ?? 0.00), 2),
                            'is_replay' => true,
                        ], 200);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Campaign budget is already initialized. Use Add Funds to add additional funding to this campaign.',
                'campaign' => $this->transformCampaign($campaign),
            ], 422);
        }

        return $this->executeInitialBudgetAllocation($event, $campaign, $member, $campaignBudget, $idempotencyKey, $request);
    }

    /**
     * Atomically execute initial budget allocation with pessimistic locking and wallet debit.
     */
    protected function executeInitialBudgetAllocation(
        Event $event,
        ?AdCampaign $campaign,
        Member $member,
        float $campaignBudget,
        ?string $idempotencyKey,
        Request $request
    ): JsonResponse {
        $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);
        $feeAmount = round($campaignBudget * ($feePercent / 100), 2);
        $totalWalletDebit = round($campaignBudget + $feeAmount, 2);

        try {
            $allocatedCampaign = DB::transaction(function () use (
                $event,
                $campaign,
                $member,
                $campaignBudget,
                $feePercent,
                $feeAmount,
                $totalWalletDebit,
                $idempotencyKey,
                $request
            ) {
                /** @var Member $lockedMember */
                $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                /** @var Event $lockedEvent */
                $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->first();

                $targetCampaign = $campaign
                    ? AdCampaign::where('id', $campaign->id)->lockForUpdate()->first()
                    : AdCampaign::where('event_id', $lockedEvent->id)->lockForUpdate()->first();

                // Concurrency guard: if budget was already allocated by a concurrent process
                if ($targetCampaign && (float) $targetCampaign->budget > 0.00) {
                    return $targetCampaign;
                }

                $availableFunds = (float) ($lockedMember->ad_balance ?? 0.00);
                if ($availableFunds < $totalWalletDebit) {
                    $shortfall = round($totalWalletDebit - $availableFunds, 2);
                    throw ValidationException::withMessages([
                        'budget' => [
                            "Insufficient advertising funds to allocate campaign budget. Campaign Budget: \${$campaignBudget} USD, Platform Fee ({$feePercent}%): \${$feeAmount} USD, Total Required: \${$totalWalletDebit} USD, Available: \${$availableFunds} USD. Shortfall: \${$shortfall} USD. Please deposit funds first."
                        ],
                    ]);
                }

                // Deduct total debit (budget + platform fee) from advertiser's ad_balance
                $lockedMember->ad_balance = round($availableFunds - $totalWalletDebit, 2);
                $lockedMember->save();

                $fundingHistoryEntry = [
                    'type' => 'initial_allocation',
                    'amount' => $campaignBudget,
                    'fee_percent' => $feePercent,
                    'fee_amount' => $feeAmount,
                    'wallet_debit' => $totalWalletDebit,
                    'idempotency_key' => $idempotencyKey,
                    'funded_at' => now()->toIso8601String(),
                    'funded_by_member_id' => $member->id,
                ];

                if ($targetCampaign) {
                    $targetAudience = (array) ($targetCampaign->target_audience ?? []);
                    $fundingHistory = (array) ($targetAudience['funding_history'] ?? []);
                    $fundingHistory[] = $fundingHistoryEntry;
                    $targetAudience['funding_history'] = $fundingHistory;

                    $targetCampaign->budget = $campaignBudget;
                    $targetCampaign->additional_funding = 0.00;
                    $targetCampaign->total_funded = $campaignBudget;
                    $targetCampaign->remaining_amount = $campaignBudget;
                    $targetCampaign->fee_percent = $feePercent;
                    $targetCampaign->fee_amount = $feeAmount;
                    $targetCampaign->wallet_debit = $totalWalletDebit;
                    $targetCampaign->target_audience = $targetAudience;
                    if ($lockedEvent->status === 'published' && !$lockedEvent->hasPassed()) {
                        $targetCampaign->status = AdCampaign::STATUS_ACTIVE;
                        $targetCampaign->approval_status = AdCampaign::APPROVAL_APPROVED;
                        $targetCampaign->approved_at = $targetCampaign->approved_at ?? now();
                    }
                    $targetCampaign->save();
                } else {
                    $isEligibleToActivate = $lockedEvent->status === 'published' && !$lockedEvent->hasPassed();
                    $targetCampaign = AdCampaign::create([
                        'campaign_type' => AdCampaign::TYPE_EVENT,
                        'event_id' => $lockedEvent->id,
                        'member_id' => $member->id,
                        'business_page_id' => null,
                        'post_id' => null,
                        'campaign_name' => trim((string) ($request->input('campaign_name') ?: $lockedEvent->title)),
                        'budget' => $campaignBudget,
                        'additional_funding' => 0.00,
                        'total_funded' => $campaignBudget,
                        'spent_amount' => 0.00,
                        'remaining_amount' => $campaignBudget,
                        'currency' => 'USD',
                        'fee_percent' => $feePercent,
                        'fee_amount' => $feeAmount,
                        'wallet_debit' => $totalWalletDebit,
                        'target_audience' => ['funding_history' => [$fundingHistoryEntry]],
                        'start_at' => $request->input('start_at') ?: null,
                        'end_at' => $request->input('end_at') ?: null,
                        'status' => $isEligibleToActivate ? AdCampaign::STATUS_ACTIVE : AdCampaign::STATUS_DRAFT,
                        'approval_status' => $isEligibleToActivate ? AdCampaign::APPROVAL_APPROVED : AdCampaign::APPROVAL_PENDING,
                        'approved_at' => $isEligibleToActivate ? now() : null,
                    ]);
                }

                return $targetCampaign;
            });

            return response()->json([
                'success' => true,
                'message' => 'Event campaign budget allocated successfully.',
                'campaign' => $this->transformCampaign($allocatedCampaign),
                'available_ad_funds' => round((float) ($member->fresh()->ad_balance ?? 0.00), 2),
            ], 201);

        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            Log::error('Failed to allocate event campaign budget', [
                'event_id' => $event->id,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while allocating the event campaign budget.',
            ], 500);
        }
    }

    /**
     * Add funds to an existing event campaign using the advertiser's ad_balance.
     * Pessimistic row locking, platform fee calculation, idempotency protection, and atomic transaction.
     */
    public function addFunds(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // 1. Creator Verification Gate (WhatsApp Mobile Verified)
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // 2. Strict Ownership Gate (Organizer only)
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can add funds to this event campaign.',
            ], 403);
        }

        // 3. Amount & Idempotency Validation
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1.00', 'max:999999999.99'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
            'campaign_id' => ['nullable'],
        ], [
            'amount.required' => 'Please specify the amount of funds to add to the event campaign.',
            'amount.min' => 'Minimum funding amount is $1.00 USD.',
        ]);

        $topUpAmount = round((float) $validated['amount'], 2);
        $idempotencyKey = !empty($validated['idempotency_key']) ? trim($validated['idempotency_key']) : null;

        // 4. Resolve campaign (or lazily initialize draft foundation if missing)
        $campaign = $event->campaign()->first();

        // If client specified a campaign_id, ensure it strictly matches this event's campaign
        if ($request->filled('campaign_id')) {
            $suppliedCampaignId = (string) $request->input('campaign_id');
            if (!$campaign || ((string) $campaign->id !== $suppliedCampaignId && (string) $campaign->campaign_id !== $suppliedCampaignId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The specified campaign does not exist or does not belong to this event.',
                ], 422);
            }
        }

        if (!$campaign) {
            $campaign = AdCampaign::create([
                'campaign_type' => AdCampaign::TYPE_EVENT,
                'event_id' => $event->id,
                'member_id' => $member->id,
                'business_page_id' => null,
                'post_id' => null,
                'campaign_name' => trim((string) ($event->title ?: 'Event Campaign')),
                'budget' => 0.00,
                'additional_funding' => 0.00,
                'total_funded' => 0.00,
                'spent_amount' => 0.00,
                'remaining_amount' => 0.00,
                'currency' => 'USD',
                'fee_percent' => (float) Setting::get('campaign_platform_fee_percent', 2.50),
                'fee_amount' => 0.00,
                'wallet_debit' => 0.00,
                'status' => AdCampaign::STATUS_DRAFT,
                'approval_status' => AdCampaign::APPROVAL_PENDING,
            ]);
        }

        $feePercent = (float) ($campaign->fee_percent ?? Setting::get('campaign_platform_fee_percent', 2.50));
        $feeAmount = round($topUpAmount * ($feePercent / 100), 2);
        $totalWalletDebit = round($topUpAmount + $feeAmount, 2);

        // 5. Atomic Transaction with Pessimistic Row Locking
        try {
            $result = DB::transaction(function () use (
                $campaign,
                $event,
                $member,
                $topUpAmount,
                $feePercent,
                $feeAmount,
                $totalWalletDebit,
                $idempotencyKey,
                $request
            ) {
                /** @var Member $lockedMember */
                $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                /** @var AdCampaign $lockedCampaign */
                $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

                $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
                $fundingHistory = (array) ($targetAudience['funding_history'] ?? []);

                // Idempotency check: prevent duplicate debit on network retry or double-click
                if ($idempotencyKey) {
                    foreach ($fundingHistory as $entry) {
                        if (($entry['idempotency_key'] ?? null) === $idempotencyKey) {
                            return [
                                'campaign' => $lockedCampaign,
                                'is_replay' => true,
                            ];
                        }
                    }
                }

                $availableFunds = (float) ($lockedMember->ad_balance ?? 0.00);

                if ($availableFunds < $totalWalletDebit) {
                    $shortfall = round($totalWalletDebit - $availableFunds, 2);
                    throw ValidationException::withMessages([
                        'amount' => [
                            "Insufficient advertising funds to add funds. Top-up Amount: \${$topUpAmount} USD, Platform Fee ({$feePercent}%): \${$feeAmount} USD, Total Required: \${$totalWalletDebit} USD, Available: \${$availableFunds} USD. Shortfall: \${$shortfall} USD. Please deposit funds first."
                        ],
                    ]);
                }

                // Deduct total debit (top-up + platform fee) from advertiser's ad_balance
                $previousMemberBalance = $availableFunds;
                $lockedMember->ad_balance = round($availableFunds - $totalWalletDebit, 2);
                $lockedMember->save();

                $previousRemaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);

                // Update campaign financial ledgers
                if ((float) $lockedCampaign->budget <= 0.00) {
                    // Initial funding allocation
                    $lockedCampaign->budget = $topUpAmount;
                    $lockedCampaign->additional_funding = 0.00;
                    $lockedCampaign->total_funded = $topUpAmount;
                    $lockedCampaign->remaining_amount = $topUpAmount;
                    $lockedCampaign->fee_percent = $feePercent;
                    $lockedCampaign->fee_amount = $feeAmount;
                    $lockedCampaign->wallet_debit = $totalWalletDebit;
                } else {
                    // Subsequent top-up
                    $lockedCampaign->additional_funding = round((float) ($lockedCampaign->additional_funding ?? 0.00) + $topUpAmount, 2);
                    $lockedCampaign->total_funded = round((float) $lockedCampaign->budget + (float) $lockedCampaign->additional_funding, 2);
                    $lockedCampaign->remaining_amount = round($previousRemaining + $topUpAmount, 2);
                    $lockedCampaign->fee_amount = round((float) ($lockedCampaign->fee_amount ?? 0.00) + $feeAmount, 2);
                    $lockedCampaign->wallet_debit = round((float) ($lockedCampaign->wallet_debit ?? 0.00) + $totalWalletDebit, 2);
                }

                // Record financial traceability entry into campaign history
                $fundingHistory[] = [
                    'idempotency_key' => $idempotencyKey,
                    'top_up_amount' => $topUpAmount,
                    'fee_percent' => $feePercent,
                    'fee_amount' => $feeAmount,
                    'wallet_debit' => $totalWalletDebit,
                    'funded_at' => now()->toISOString(),
                    'member_id' => $member->id,
                    'previous_remaining' => $previousRemaining,
                    'new_remaining' => (float) $lockedCampaign->remaining_amount,
                    'previous_ad_balance' => $previousMemberBalance,
                    'new_ad_balance' => (float) $lockedMember->ad_balance,
                ];

                // Automatically activate and approve campaign if it now has sufficient budget and event is eligible
                $minEventReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.025;
                if ((float) $lockedCampaign->remaining_amount >= $minEventReward
                    && in_array($lockedCampaign->status, [
                        AdCampaign::STATUS_DRAFT,
                        AdCampaign::STATUS_PENDING_REVIEW,
                        AdCampaign::STATUS_STOPPED,
                        AdCampaign::STATUS_BUDGET_EXHAUSTED,
                    ], true)
                    && $event->status === 'published'
                    && !$event->hasPassed()
                ) {
                    $previousStatus = $lockedCampaign->status;
                    $lockedCampaign->status = AdCampaign::STATUS_ACTIVE;
                    $lockedCampaign->approval_status = AdCampaign::APPROVAL_APPROVED;
                    $lockedCampaign->approved_at = $lockedCampaign->approved_at ?? now();

                    $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
                    $lifecycleHistory[] = [
                        'action' => 'activated_via_funding',
                        'from_status' => $previousStatus,
                        'to_status' => AdCampaign::STATUS_ACTIVE,
                        'member_id' => $member->id,
                        'timestamp' => now()->toISOString(),
                        'remaining_amount' => (float) $lockedCampaign->remaining_amount,
                        'ip_address' => $request->ip(),
                    ];
                    $targetAudience['lifecycle_history'] = $lifecycleHistory;
                }

                $targetAudience['funding_history'] = $fundingHistory;
                $lockedCampaign->target_audience = $targetAudience;
                $lockedCampaign->save();

                return [
                    'campaign' => $lockedCampaign,
                    'is_replay' => false,
                ];
            });

            /** @var AdCampaign $updatedCampaign */
            $updatedCampaign = $result['campaign'];
            $isReplay = $result['is_replay'];

            $message = $isReplay
                ? "This funding transaction was already processed. Current running budget: \${$updatedCampaign->remaining_amount} USD."
                : "Successfully added \${$topUpAmount} USD to event campaign. New running budget: \${$updatedCampaign->remaining_amount} USD.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'campaign' => $this->transformCampaign($updatedCampaign),
                'available_ad_funds' => round((float) ($member->fresh()->ad_balance ?? 0.00), 2),
                'is_replay' => $isReplay,
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to add funds to event campaign', [
                'event_id' => $event->id,
                'member_id' => $member->id,
                'top_up_amount' => $topUpAmount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing campaign funding. Please try again.',
            ], 500);
        }
    }

    /**
     * Activate an event campaign.
     * Enforces prerequisite checks: authentication, creator verification, organizer ownership,
     * valid event state, valid campaign state, funded budget, non-expired schedule, and idempotency.
     */
    public function activate(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // 1. Creator Verification Gate (WhatsApp Mobile Verified)
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // 2. Strict Ownership Gate (Organizer only)
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can activate this event campaign.',
            ], 403);
        }

        // 3. Event Existence & State Gate
        if ($event->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => "Cannot activate campaign: Event must be in published status. Current event status is: {$event->status}.",
            ], 422);
        }

        // Check if event has already ended
        if ($event->end_date && $event->end_date->isPast() && !$event->end_date->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate campaign for an event that has already ended.',
            ], 422);
        }

        // 4. Campaign Existence & Relationship Gate
        $campaign = $event->campaign()->first();
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'No campaign foundation found for this event. Please create and fund a campaign first.',
            ], 404);
        }

        // Validate campaign type
        if ($campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid campaign type. Only event campaigns can be activated through this endpoint.',
            ], 422);
        }

        // Validate campaign ownership
        if ((int) $campaign->member_id !== (int) $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not own this campaign.',
            ], 403);
        }

        // Check if campaign has already ended
        if ($campaign->end_at && $campaign->end_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot activate campaign because its scheduled end time has already passed.',
            ], 422);
        }

        // 5. Fast-path Idempotency Check
        if ($campaign->status === AdCampaign::STATUS_ACTIVE) {
            return response()->json([
                'success' => true,
                'message' => 'Event campaign is already active.',
                'is_already_active' => true,
                'campaign' => $this->transformCampaign($campaign),
            ], 200);
        }

        // 6. Campaign State & Transition Validation
        $illegalStatuses = [
            AdCampaign::STATUS_STOPPED,
            AdCampaign::STATUS_CANCELLED,
            AdCampaign::STATUS_COMPLETED,
            AdCampaign::STATUS_REJECTED,
        ];
        if (in_array($campaign->status, $illegalStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot activate campaign in its current status: {$campaign->status}.",
            ], 422);
        }

        // 7. Budget Prerequisite Check
        $remaining = (float) ($campaign->remaining_amount ?? 0.00);
        $budget = (float) ($campaign->budget ?? 0.00);
        if ($remaining <= 0.00 && $budget <= 0.00) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be activated without configured funds. Please allocate a budget or add funds first.',
            ], 422);
        }

        if ($remaining <= 0.00) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be activated because it has no remaining budget. Please add funds first.',
            ], 422);
        }

        // 8. Atomic State Transition with Concurrency Protection
        try {
            $activatedCampaign = DB::transaction(function () use ($event, $campaign, $member, $request) {
                // Pessimistic locks on Event and AdCampaign
                /** @var Event $lockedEvent */
                $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->first();
                /** @var AdCampaign $lockedCampaign */
                $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

                // Idempotency re-check inside transaction
                if ($lockedCampaign->status === AdCampaign::STATUS_ACTIVE) {
                    return $lockedCampaign;
                }

                $previousStatus = $lockedCampaign->status;

                // Update status and approval
                $lockedCampaign->status = AdCampaign::STATUS_ACTIVE;
                $lockedCampaign->approval_status = AdCampaign::APPROVAL_APPROVED;
                $lockedCampaign->approved_at = now();

                // Record audit trail in campaign metadata
                $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
                $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
                $lifecycleHistory[] = [
                    'action' => 'activated',
                    'from_status' => $previousStatus,
                    'to_status' => AdCampaign::STATUS_ACTIVE,
                    'member_id' => $member->id,
                    'timestamp' => now()->toISOString(),
                    'ip_address' => $request->ip(),
                ];
                $targetAudience['lifecycle_history'] = $lifecycleHistory;
                $lockedCampaign->target_audience = $targetAudience;

                $lockedCampaign->save();

                return $lockedCampaign;
            });

            Log::info('Event campaign activated successfully', [
                'event_id' => $event->id,
                'campaign_id' => $activatedCampaign->id,
                'member_id' => $member->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event campaign activated successfully.',
                'campaign' => $this->transformCampaign($activatedCampaign),
                'is_already_active' => false,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to activate event campaign', [
                'event_id' => $event->id,
                'campaign_id' => $campaign->id,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while activating the campaign. Please try again.',
            ], 500);
        }
    }

    /**
     * Pause an active event campaign.
     */
    public function pause(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // Creator Verification Gate
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // Ownership Gate
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can pause this event campaign.',
            ], 403);
        }

        $campaign = $event->campaign()->first();
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'No campaign found for this event.',
            ], 404);
        }

        if ($campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid campaign type.',
            ], 422);
        }

        // Idempotency: if already paused
        if ($campaign->status === AdCampaign::STATUS_PAUSED) {
            return response()->json([
                'success' => true,
                'message' => 'Event campaign is already paused.',
                'is_already_paused' => true,
                'campaign' => $this->transformCampaign($campaign),
            ]);
        }

        if (!in_array($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot pause campaign in its current status: {$campaign->status}.",
            ], 422);
        }

        $pausedCampaign = DB::transaction(function () use ($campaign, $member, $request) {
            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();
            $previousStatus = $lockedCampaign->status;

            $lockedCampaign->status = AdCampaign::STATUS_PAUSED;

            $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
            $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
            $lifecycleHistory[] = [
                'action' => 'paused',
                'from_status' => $previousStatus,
                'to_status' => AdCampaign::STATUS_PAUSED,
                'member_id' => $member->id,
                'timestamp' => now()->toISOString(),
                'ip_address' => $request->ip(),
            ];
            $targetAudience['lifecycle_history'] = $lifecycleHistory;
            $lockedCampaign->target_audience = $targetAudience;

            $lockedCampaign->save();
            return $lockedCampaign;
        });

        return response()->json([
            'success' => true,
            'message' => 'Event campaign paused successfully.',
            'campaign' => $this->transformCampaign($pausedCampaign),
        ]);
    }

    /**
     * Resume a paused event campaign.
     */
    public function resume(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // Creator Verification Gate
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // Ownership Gate
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can resume this event campaign.',
            ], 403);
        }

        // Event State Gate
        if ($event->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => "Cannot resume campaign: Event is not published.",
            ], 422);
        }

        if ($event->end_date && $event->end_date->isPast() && !$event->end_date->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resume campaign for an event that has already ended.',
            ], 422);
        }

        $campaign = $event->campaign()->first();
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'No campaign found for this event.',
            ], 404);
        }

        if ($campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid campaign type.',
            ], 422);
        }

        // Idempotency: if already active
        if ($campaign->status === AdCampaign::STATUS_ACTIVE) {
            return response()->json([
                'success' => true,
                'message' => 'Event campaign is already active.',
                'is_already_active' => true,
                'campaign' => $this->transformCampaign($campaign),
            ]);
        }

        if (!in_array($campaign->status, [AdCampaign::STATUS_PAUSED, AdCampaign::STATUS_APPROVED], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot resume campaign in its current status: {$campaign->status}.",
            ], 422);
        }

        if ((float) ($campaign->remaining_amount ?? 0.00) <= 0.00) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resume campaign because it has no remaining budget. Please add funds first.',
            ], 422);
        }

        $resumedCampaign = DB::transaction(function () use ($campaign, $member, $request) {
            /** @var AdCampaign $lockedCampaign */
            $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();
            $previousStatus = $lockedCampaign->status;

            $lockedCampaign->status = AdCampaign::STATUS_ACTIVE;
            $lockedCampaign->approval_status = AdCampaign::APPROVAL_APPROVED;

            $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
            $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
            $lifecycleHistory[] = [
                'action' => 'resumed',
                'from_status' => $previousStatus,
                'to_status' => AdCampaign::STATUS_ACTIVE,
                'member_id' => $member->id,
                'timestamp' => now()->toISOString(),
                'ip_address' => $request->ip(),
            ];
            $targetAudience['lifecycle_history'] = $lifecycleHistory;
            $lockedCampaign->target_audience = $targetAudience;

            $lockedCampaign->save();
            return $lockedCampaign;
        });

        return response()->json([
            'success' => true,
            'message' => 'Event campaign resumed successfully.',
            'campaign' => $this->transformCampaign($resumedCampaign),
        ]);
    }

    /**
     * Preview the authoritative event reward for the authenticated member.
     * Computes verified direct referral count strictly server-side; client inputs are ignored.
     * Pure reader: Strictly ZERO side effects.
     * Does NOT credit Reward Wallet.
     * Does NOT debit campaign budget.
     * Does NOT create participant or Interested records.
     * Does NOT mutate referral or verification data.
     */
    public function rewardPreview(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if (!$member) {
            return response()->json([
                'success' => false,
                'status' => 'unauthenticated',
                'message' => 'Unauthenticated. Please log in to view event reward.',
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ], 401);
        }

        if (!$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'status' => 'unverified_member',
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        // Organizer Preview Only Gate
        if ((int) $event->organizer_id === (int) $member->id || ($event->campaign && (int) $event->campaign->member_id === (int) $member->id) || ($event->campaign && $event->campaign->isOwner($member->id))) {
            $activeRules = AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT);
            return response()->json([
                'success' => true,
                'status' => 'organizer_preview_only',
                'is_organizer' => true,
                'is_owner' => true,
                'eligible' => false,
                'message' => 'You cannot earn rewards from your own campaign.',
                'reward_amount_usd' => 0.00,
                'all_active_rules' => $activeRules->map(function (AdRewardRule $r) {
                    $min = (int) $r->min_referrals;
                    $max = $r->max_referrals !== null ? (int) $r->max_referrals : null;
                    $isUnconfigured = $r->reward_amount === null || $r->reward_amount === '';
                    return [
                        'id' => $r->id,
                        'min_referrals' => $min,
                        'max_referrals' => $max,
                        'label' => $min . ($max !== null ? "–{$max}" : '+'),
                        'reward_amount_usd' => !$isUnconfigured ? (float) $r->reward_amount : null,
                        'reward_amount_exact' => !$isUnconfigured ? number_format((float) $r->reward_amount, 4, '.', '') : null,
                        'reward_amount_formatted' => !$isUnconfigured ? '$' . number_format((float) $r->reward_amount, 3) . ' USD' : 'Config Required',
                        'is_unconfigured' => $isUnconfigured,
                        'is_current' => false,
                    ];
                })->values(),
            ], 200);
        }

        $campaignId = $request->query('campaign_id');
        $resolver = app(EventRewardResolver::class);
        $result = $resolver->resolveForEventMember($member, $event, $campaignId);

        if (!$result['success']) {
            $statusCode = in_array($result['status'], ['invalid_event', 'invalid_member']) ? 404 : 422;
            return response()->json($result, $statusCode);
        }

        $activeRules = AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT);
        $result['all_active_rules'] = $activeRules->map(function (AdRewardRule $r) use ($result) {
            $min = (int) $r->min_referrals;
            $max = $r->max_referrals !== null ? (int) $r->max_referrals : null;
            $isCurrent = (int) ($result['matched_rule_id'] ?? 0) === (int) $r->id;
            $isUnconfigured = $r->reward_amount === null || $r->reward_amount === '';

            return [
                'id' => $r->id,
                'min_referrals' => $min,
                'max_referrals' => $max,
                'label' => $min . ($max !== null ? "–{$max}" : '+'),
                'reward_amount_usd' => !$isUnconfigured ? (float) $r->reward_amount : null,
                'reward_amount_exact' => !$isUnconfigured ? number_format((float) $r->reward_amount, 4, '.', '') : null,
                'reward_amount_formatted' => !$isUnconfigured ? '$' . number_format((float) $r->reward_amount, 3) . ' USD' : 'Config Required',
                'is_unconfigured' => $isUnconfigured,
                'is_current' => $isCurrent,
            ];
        })->values();

        $campaign = $event->campaign()->first();
        $alreadyRewarded = false;
        if ($campaign && $member) {
            $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
                ->where('member_id', $member->id)
                ->where('status', AdReward::STATUS_CREDITED)
                ->exists();
        }
        $result['already_rewarded'] = $alreadyRewarded;

        return response()->json($result, 200);
    }

    /**
     * Authoritatively qualify an authenticated, verified member who clicks "Interested"
     * on a Paid Event campaign.
     *
     * Processes the atomic financial transaction:
     * 1. Re-check mobile verification
     * 2. Verify event campaign context & eligibility
     * 3. One-member-one-event duplicate reward check
     * 4. Pessimistic row locking on Member, Event, and AdCampaign
     * 5. Re-resolve authoritative reward amount via Phase 12 EventRewardResolver
     * 6. Consume exact reward amount from event campaign budget via Phase 8 CampaignBudgetDepletionService
     * 7. Atomically credit member's canonical Reward Wallet
     * 8. Create immutable AdReward record
     * 9. Record event response as 'interested' in event_responses
     * 10. Auto-exhaust/stop campaign if remaining < minimum active event reward (via Phase 8/9)
     */
    public function qualifyInterest(Request $request, Event $event): JsonResponse
    {
        $consent = $request->input('consent_accepted');
        if ($consent !== true && $consent !== 'true') {
            return response()->json([
                'success' => false,
                'status' => 'consent_required',
                'message' => 'Please accept the profile-sharing consent before continuing.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
                'errors' => [
                    'consent_accepted' => ['Please accept the profile-sharing consent before continuing.'],
                ],
            ], 422);
        }

        $member = auth('member')->user();
        if (!$member) {
            return response()->json([
                'success' => false,
                'status' => 'unauthenticated',
                'message' => 'Unauthenticated. Please log in to participate in event campaigns.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 401);
        }

        // 1. Authoritative Verified Audience Gate
        if (!$member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'status' => 'unverified_member',
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        // 2. Validate Event & Campaign Context
        if (!$event->exists) {
            return response()->json([
                'success' => false,
                'status' => 'invalid_event',
                'message' => 'The specified event does not exist.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 404);
        }

        $campaign = $event->campaign()->first();
        if (!$campaign || $campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'status' => 'not_a_paid_event',
                'message' => 'This event does not have an active paid campaign.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 422);
        }

        // Authoritative Organizer / Owner Exclusion: Event organizers and campaign owners cannot earn self-rewards
        if ((int) $event->organizer_id === (int) $member->id || (int) $campaign->member_id === (int) $member->id || $campaign->isOwner($member->id)) {
            return response()->json([
                'success' => false,
                'status' => 'organizer_self_reward_forbidden',
                'message' => 'You cannot earn rewards from your own campaign.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 403);
        }

        // Fast-path Duplicate Check (One reward per member per campaign)
        $alreadyRewarded = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->exists();

        if ($alreadyRewarded) {
            return response()->json([
                'success' => true,
                'status' => 'already_rewarded',
                'message' => 'You have already received your reward for this event campaign.',
                'already_rewarded' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
                'event_id' => $event->id,
                'campaign_id' => $campaign->id,
            ], 200);
        }

        // 3. Concurrency Protection & Atomic Financial Transaction
        try {
            $result = DB::transaction(function () use ($event, $campaign, $member, $request) {
                // Re-lock and re-verify member status
                /** @var Member $lockedMember */
                $lockedMember = Member::where('id', $member->id)->lockForUpdate()->first();
                if (!$lockedMember || !$lockedMember->isMobileVerified()) {
                    return [
                        'status_code' => 403,
                        'payload' => [
                            'success' => false,
                            'status' => 'unverified_member',
                            'message' => 'Please verify your phone number first before proceeding.',
                            'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                            'verified_required' => true,
                            'needs_verification' => true,
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                        ],
                    ];
                }

                // Re-lock Event
                /** @var Event $lockedEvent */
                $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->first();
                if (!$lockedEvent) {
                    return [
                        'status_code' => 404,
                        'payload' => [
                            'success' => false,
                            'status' => 'invalid_event',
                            'message' => 'Event not found under lock.',
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                        ],
                    ];
                }

                // Re-lock Campaign
                /** @var AdCampaign $lockedCampaign */
                $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();
                if (!$lockedCampaign) {
                    return [
                        'status_code' => 404,
                        'payload' => [
                            'success' => false,
                            'status' => 'campaign_not_found',
                            'message' => 'Campaign not found under lock.',
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                        ],
                    ];
                }

                // Re-verify organizer/owner exclusion under lock
                if ((int) $lockedEvent->organizer_id === (int) $lockedMember->id || (int) $lockedCampaign->member_id === (int) $lockedMember->id || $lockedCampaign->isOwner($lockedMember->id)) {
                    return [
                        'status_code' => 403,
                        'payload' => [
                            'success' => false,
                            'status' => 'organizer_self_reward_forbidden',
                            'message' => 'You cannot earn rewards from your own campaign.',
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                        ],
                    ];
                }

                // Re-check Duplicate inside transaction lock
                $alreadyRewardedInTx = AdReward::where('ad_campaign_id', $lockedCampaign->id)
                    ->where('member_id', $lockedMember->id)
                    ->where('status', AdReward::STATUS_CREDITED)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyRewardedInTx) {
                    return [
                        'status_code' => 200,
                        'payload' => [
                            'success' => true,
                            'status' => 'already_rewarded',
                            'message' => 'You have already received your reward for this event campaign.',
                            'already_rewarded' => true,
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                            'event_id' => $lockedEvent->id,
                            'campaign_id' => $lockedCampaign->id,
                        ],
                    ];
                }

                // Campaign Eligibility & State Check
                if ($lockedCampaign->approval_status !== AdCampaign::APPROVAL_APPROVED ||
                    !in_array($lockedCampaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
                    return [
                        'status_code' => 422,
                        'payload' => [
                            'success' => false,
                            'status' => 'campaign_not_active',
                            'message' => 'This event campaign is not active or approved.',
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                            'campaign_status' => $lockedCampaign->status,
                        ],
                    ];
                }

                // Re-resolve authoritative reward using Phase 12 EventRewardResolver
                $resolver = app(EventRewardResolver::class);
                $resolution = $resolver->resolveForEventMember($lockedMember, $lockedEvent, $lockedCampaign);

                if (!$resolution['success'] || empty($resolution['eligible'])) {
                    return [
                        'status_code' => 422,
                        'payload' => [
                            'success' => false,
                            'status' => $resolution['status'] ?? 'reward_not_configured',
                            'message' => $resolution['message'] ?? 'Reward rule is not configured for your referral tier.',
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                        ],
                    ];
                }

                $rewardAmount = (float) $resolution['reward_amount_usd'];
                $rewardAmountExact = (string) ($resolution['reward_amount_exact'] ?? sprintf('%.4f', $rewardAmount));
                $snapshot = $resolution['snapshot'] ?? [];

                // Check budget sufficiency
                $currentRemaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);
                if ($currentRemaining < $rewardAmount) {
                    // Check and auto-stop if exhausted
                    $minReward = app(CampaignBudgetDepletionService::class)->resolveMinimumRewardAmount($lockedCampaign);
                    if ($currentRemaining < $minReward) {
                        $lockedCampaign->status = AdCampaign::STATUS_STOPPED;
                        $lockedCampaign->save();
                    }

                    return [
                        'status_code' => 422,
                        'payload' => [
                            'success' => false,
                            'status' => 'insufficient_budget',
                            'message' => 'Campaign budget is insufficient to fund your reward tier.',
                            'rewarded' => false,
                            'reward_amount_usd' => 0.00,
                            'remaining_budget' => $currentRemaining,
                            'campaign_status' => $lockedCampaign->status,
                        ],
                    ];
                }

                // Consume budget using Phase 8 engine
                $spendReference = "event_reward_{$lockedCampaign->id}_{$lockedMember->id}";
                $depletionResult = app(CampaignBudgetDepletionService::class)->depleteCampaignBudget(
                    $lockedCampaign,
                    $rewardAmount,
                    $spendReference,
                    $lockedEvent,
                    [
                        'source' => 'event_interest_qualification',
                        'member_id' => $lockedMember->id,
                        'event_id' => $lockedEvent->id,
                        'reward_amount_usd' => $rewardAmount,
                    ]
                );

                // Credit Member's canonical Reward Wallet atomically
                $lockedMember->reward_balance = round((float) ($lockedMember->reward_balance ?? 0.00) + $rewardAmount, 4);
                $lockedMember->save();
                $newRewardBalance = (float) $lockedMember->reward_balance;

                // Create immutable AdReward record
                $reward = AdReward::create([
                    'ad_campaign_id' => $lockedCampaign->id,
                    'member_id' => $lockedMember->id,
                    'ad_reward_rule_id' => $snapshot['ad_reward_rule_id'] ?? null,
                    'direct_verified_referral_count' => $snapshot['direct_verified_referral_count'] ?? 0,
                    'rule_min_referrals' => $snapshot['min_referrals'] ?? null,
                    'rule_max_referrals' => $snapshot['max_referrals'] ?? null,
                    'rule_version' => (string) ($snapshot['rule_version'] ?? ''),
                    'reward_amount_usd' => $rewardAmount,
                    'qualifying_event_id' => "event_interest_{$lockedEvent->id}_{$lockedMember->id}_" . time(),
                    'landing_page_url' => url("/events/{$lockedEvent->id}"),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'status' => AdReward::STATUS_CREDITED,
                ]);

                // Record Event Response as 'interested'
                EventResponse::query()->updateOrCreate(
                    ['event_id' => $lockedEvent->id, 'member_id' => $lockedMember->id],
                    ['response' => 'interested']
                );

                $freshCampaign = $depletionResult['campaign'] ?? $lockedCampaign->fresh();

                return [
                    'status_code' => 200,
                    'payload' => [
                        'success' => true,
                        'status' => 'qualified_and_rewarded',
                        'message' => "Congratulations! You earned \${$rewardAmountExact} USD. Your reward has been credited to your Reward Wallet.",
                        'rewarded' => true,
                        'already_rewarded' => false,
                        'reward_amount_usd' => $rewardAmount,
                        'reward_amount_exact' => $rewardAmountExact,
                        'direct_verified_referrals' => $snapshot['direct_verified_referral_count'] ?? 0,
                        'event_id' => $lockedEvent->id,
                        'campaign_id' => $lockedCampaign->id,
                        'reward_id' => $reward->id,
                        'member_reward_balance' => $newRewardBalance,
                        'reward_wallet_balance' => $newRewardBalance,
                        'remaining_budget' => (float) ($freshCampaign->remaining_amount ?? 0.00),
                        'campaign_status' => $freshCampaign->status,
                    ],
                ];
            });

            return response()->json($result['payload'], $result['status_code']);

        } catch (\RuntimeException $e) {
            // Check if it was insufficient budget from depletion service
            if (str_contains($e->getMessage(), 'Insufficient campaign budget')) {
                return response()->json([
                    'success' => false,
                    'status' => 'insufficient_budget',
                    'message' => 'Campaign budget is insufficient to fund your reward tier.',
                    'rewarded' => false,
                    'reward_amount_usd' => 0.00,
                ], 422);
            }

            Log::error('Event campaign qualification error', [
                'event_id' => $event->id,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Unexpected event qualification failure', [
                'event_id' => $event->id,
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing your event reward qualification.',
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ], 500);
        }
    }

    /**
     * Display sponsored event campaigns for feed.
     * Accessible to both verified and unverified members for viewing.
     */
    public function sponsoredFeed(Request $request): JsonResponse
    {
        $member = auth('member')->user();
        $limit = min(50, max(5, (int) $request->input('limit', 20)));

        $service = app(AdDeliveryService::class);
        $campaigns = $service->getEligibleEventCampaigns($limit, $member);
        $maxReward = AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        $maxRewardFormatted = null;
        if ($maxReward !== null) {
            $formattedNumber = rtrim(rtrim(sprintf('%.4f', $maxReward), '0'), '.');
            if (strpos($formattedNumber, '.') !== false && strlen(substr($formattedNumber, strpos($formattedNumber, '.') + 1)) == 1) {
                $formattedNumber .= '0';
            }
            $maxRewardFormatted = '$' . $formattedNumber;
        }

        $items = $campaigns->map(function ($c) use ($maxReward, $maxRewardFormatted, $member) {
            $isOwner = ($member && !empty($member->id)) ? $c->isOwner($member->id) : false;
            return [
                'id' => $c->id,
                'campaign_id' => $c->campaign_id,
                'campaign_type' => AdCampaign::TYPE_EVENT,
                'campaign_name' => $c->campaign_name,
                'member_id' => $c->member_id,
                'is_owner' => $isOwner,
                'reward_amount_usd' => $isOwner ? null : $maxReward,
                'earn_up_to_usd' => $isOwner ? null : $maxReward,
                'earn_up_to_formatted' => $isOwner ? null : $maxRewardFormatted,
                'event' => $c->event,
                'start_at' => $c->start_at,
                'status' => $c->status,
            ];
        });

        return response()->json([
            'success' => true,
            'campaigns' => $items,
        ]);
    }

    /**
     * Transform campaign model into safe public API shape including financial metrics for organizer.
     */

    /**
     * Get authoritative creator participant and budget analytics for an event campaign.
     * Strictly accessible only by the event organizer.
     */
    public function analytics(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in to view event campaign analytics.',
            ], 401);
        }

        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can view this event campaign analytics.',
            ], 403);
        }

        $campaign = $event->campaign()->first();
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'This event does not have an associated campaign foundation.',
                'campaign' => null,
            ], 404);
        }

        $depletionService = app(CampaignBudgetDepletionService::class);
        $minReward = $depletionService->resolveMinimumRewardAmount($campaign);

        $initialBudget = (float) ($campaign->budget ?? 0.00);
        $additionalFunding = (float) ($campaign->additional_funding ?? 0.00);
        $totalFunded = round($initialBudget + $additionalFunding, 4);
        $spentAmount = round((float) ($campaign->spent_amount ?? 0.00), 4);
        $remainingAmount = round((float) ($campaign->remaining_amount ?? 0.00), 4);

        $lowBudgetThreshold = (float) Setting::get('ad_campaign_low_budget_threshold', 1.00);
        $isExhausted = ($remainingAmount < $minReward) || in_array($campaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_BUDGET_EXHAUSTED], true);
        $isLowBudget = (!$isExhausted && $remainingAmount <= $lowBudgetThreshold);
        $canReactivate = ($campaign->status === AdCampaign::STATUS_STOPPED && $remainingAmount >= $minReward);

        // Aggregate participant metrics (strictly scoped to this event and campaign)
        $totalParticipantsCount = EventResponse::where('event_id', $event->id)
            ->where('response', 'interested')
            ->distinct('member_id')
            ->count('member_id');

        $rewardedCount = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->count();

        $totalRewardsPaidUsd = (float) AdReward::where('ad_campaign_id', $campaign->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->sum('reward_amount_usd');

        // Unrewarded participants count: Interested members with no credited reward for this campaign
        $unrewardedCount = max(0, $totalParticipantsCount - $rewardedCount);

        // Financial reconciliation check
        $reconcilesExactly = round($totalFunded, 4) === round($spentAmount + $remainingAmount, 4);

        // Fetch itemized paginated participants
        $participantsData = $this->buildParticipantsQuery($request, $event, $campaign);

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status,
                'start_date' => $event->start_date?->toDateString(),
                'end_date' => $event->end_date?->toDateString(),
            ],
            'campaign' => $this->transformCampaign($campaign),
            'budget_metrics' => [
                'initial_budget' => $initialBudget,
                'additional_funding' => $additionalFunding,
                'total_funded' => $totalFunded,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $remainingAmount,
                'currency' => $campaign->currency ?? 'USD',
                'fee_percent' => (float) ($campaign->fee_percent ?? 2.50),
                'fee_amount' => (float) ($campaign->fee_amount ?? 0.00),
                'wallet_debit' => (float) ($campaign->wallet_debit ?? 0.00),
                'minimum_event_reward' => $minReward,
                'low_budget_threshold' => $lowBudgetThreshold,
                'is_low_budget' => $isLowBudget,
                'is_exhausted' => $isExhausted,
                'can_reactivate' => $canReactivate,
                'status' => $campaign->status,
                'approval_status' => $campaign->approval_status,
                'display_status' => $campaign->display_status,
                'financial_reconciliation' => [
                    'total_funded' => $totalFunded,
                    'rewards_paid' => $spentAmount,
                    'remaining_campaign_budget' => $remainingAmount,
                    'reconciles_exactly' => $reconcilesExactly,
                ],
            ],
            'participant_metrics' => [
                'total_participants' => $totalParticipantsCount,
                'rewarded_participants' => $rewardedCount,
                'unrewarded_participants' => $unrewardedCount,
                'total_rewards_paid_usd' => round($totalRewardsPaidUsd, 4),
                'average_reward_usd' => $rewardedCount > 0 ? round($totalRewardsPaidUsd / $rewardedCount, 4) : 0.00,
            ],
            'participants' => $participantsData,
        ]);
    }

    /**
     * Get paginated itemized participants for an event campaign.
     * Strictly accessible only by the event organizer.
     */
    public function participants(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can view participants.',
            ], 403);
        }

        $campaign = $event->campaign()->first();
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'This event does not have an associated campaign.',
                'participants' => [],
            ], 404);
        }

        $data = $this->buildParticipantsQuery($request, $event, $campaign);

        return response()->json([
            'success' => true,
            'participants' => $data,
        ]);
    }

    /**
     * Build itemized, filtered, and paginated participants list for creator analytics.
     */
    protected function buildParticipantsQuery(Request $request, Event $event, AdCampaign $campaign): array
    {
        $query = EventResponse::query()
            ->where('event_id', $event->id)
            ->where('response', 'interested')
            ->with('member:id,name,user_id,email,phone,profile_photo,mobile_verified_at');

        // 1. Keyword search (name, user_id, email, phone)
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('name', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 2. Date preset filtering
        $datePreset = $request->input('date_preset', 'all');
        if ($datePreset === 'today') {
            $query->where('created_at', '>=', now()->startOfDay());
        } elseif ($datePreset === 'yesterday') {
            $query->whereBetween('created_at', [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
            ]);
        } elseif ($datePreset === 'last_7_days') {
            $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
        } elseif ($datePreset === 'last_30_days') {
            $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
        }

        // 3. Reward status filtering
        $rewardStatus = $request->input('reward_status', 'all');
        $rewardedMemberIds = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->pluck('member_id')
            ->all();

        if ($rewardStatus === 'credited' || $rewardStatus === 'rewarded') {
            $query->whereIn('member_id', $rewardedMemberIds);
        } elseif ($rewardStatus === 'unpaid' || $rewardStatus === 'unrewarded') {
            $query->whereNotIn('member_id', $rewardedMemberIds);
        }

        // 4. Pagination
        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $paginated = $query->orderByDesc('id')->paginate($perPage);

        // Batch load rewards for current page members
        $pageMemberIds = $paginated->pluck('member_id')->unique()->all();
        $rewards = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->whereIn('member_id', $pageMemberIds)
            ->get()
            ->keyBy('member_id');

        $items = collect($paginated->items())->map(function ($resp) use ($rewards) {
            /** @var AdReward|null $reward */
            $reward = $rewards->get($resp->member_id);
            $member = $resp->member;

            return [
                'member_id' => $resp->member_id,
                'name' => $member?->name ?? 'Unknown Member',
                'user_id' => $member?->user_id ?? '',
                'email' => $member?->email ?? '',
                'phone' => $member?->phone ?? '',
                'profile_photo' => $member?->profile_photo,
                'is_verified' => (bool) ($member?->mobile_verified_at || $member?->is_verified),
                'mobile_verified_at' => $member?->mobile_verified_at?->toISOString() ?? (string) $member?->mobile_verified_at,
                'response' => $resp->response,
                'reward_status' => $reward ? 'credited' : 'unpaid',
                'is_rewarded' => $reward !== null,
                'reward_amount_usd' => $reward ? (float) $reward->reward_amount_usd : 0.00,
                'reward_amount_formatted' => $reward ? $reward->reward_formatted : '$0.0000 USD',
                'direct_verified_referrals' => $reward ? $reward->direct_verified_referral_count : null,
                'rule_tier_label' => $reward ? $reward->tier_label : null,
                'rule_version' => $reward ? $reward->rule_version : null,
                'qualifying_event_id' => $reward ? $reward->qualifying_event_id : null,
                'payout_at' => $reward?->created_at?->toISOString(),
                'interested_at' => $resp->created_at?->toISOString(),
            ];
        })->values()->all();

        return [
            'data' => $items,
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
        ];
    }

    /**
     * Reactivate an exhausted or stopped event campaign after funds have been added.
     * Strictly reuses the SAME campaign row without resetting history or previous payouts.
     */
    public function reactivate(Request $request, Event $event): JsonResponse
    {
        $member = auth('member')->user();
        if ($member) {
            $member = $member->fresh() ?? $member;
        }

        // 1. Creator Verification Gate (WhatsApp Mobile Verified)
        if (!$member || (method_exists($member, 'isMobileVerified') ? !$member->isMobileVerified() : !$member->mobile_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                'needs_verification' => true,
            ], 403);
        }

        // 2. Strict Ownership Gate (Organizer only)
        if (!$event->isOrganizer($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the event organizer can reactivate this event campaign.',
            ], 403);
        }

        // 3. Event State Gate
        if ($event->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => "Cannot reactivate campaign: Event must be in published status. Current event status is: {$event->status}.",
            ], 422);
        }

        if ($event->end_date && $event->end_date->isPast() && !$event->end_date->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reactivate campaign for an event that has already ended.',
            ], 422);
        }

        // 4. Campaign Existence & Relationship Gate
        $campaign = $event->campaign()->first();
        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'No campaign found for this event.',
            ], 404);
        }

        if ($campaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid campaign type. Only event campaigns can be reactivated through this endpoint.',
            ], 422);
        }

        // Idempotency: if already active
        if ($campaign->status === AdCampaign::STATUS_ACTIVE) {
            return response()->json([
                'success' => true,
                'message' => 'Event campaign is already active.',
                'is_already_active' => true,
                'campaign' => $this->transformCampaign($campaign),
            ], 200);
        }

        // 5. Permitted statuses for reactivation: STOPPED (e.g. from exhaustion), PAUSED, or APPROVED
        $permittedStatuses = [
            AdCampaign::STATUS_STOPPED,
            AdCampaign::STATUS_PAUSED,
            AdCampaign::STATUS_APPROVED,
            AdCampaign::STATUS_BUDGET_EXHAUSTED,
        ];
        if (!in_array($campaign->status, $permittedStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot reactivate campaign in its current status: {$campaign->status}.",
            ], 422);
        }

        // 6. Authoritative Minimum Reward Budget Prerequisite Check
        $minReward = app(CampaignBudgetDepletionService::class)->resolveMinimumRewardAmount($campaign);
        $remaining = (float) ($campaign->remaining_amount ?? 0.00);

        if ($remaining < $minReward) {
            return response()->json([
                'success' => false,
                'message' => "Cannot reactivate campaign: remaining budget (\${$remaining} USD) is below the minimum required reward of \${$minReward} USD. Please add funds first.",
                'remaining_budget' => $remaining,
                'minimum_required_reward' => $minReward,
            ], 422);
        }

        // 7. Atomic State Transition with Concurrency Protection (Row Locking)
        try {
            $reactivatedCampaign = DB::transaction(function () use ($event, $campaign, $member, $minReward, $request) {
                /** @var Event $lockedEvent */
                $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->first();
                /** @var AdCampaign $lockedCampaign */
                $lockedCampaign = AdCampaign::where('id', $campaign->id)->lockForUpdate()->first();

                // Idempotency re-check inside transaction
                if ($lockedCampaign->status === AdCampaign::STATUS_ACTIVE) {
                    return $lockedCampaign;
                }

                // Re-verify budget inside row lock
                $lockedRemaining = (float) ($lockedCampaign->remaining_amount ?? 0.00);
                if ($lockedRemaining < $minReward) {
                    throw new \RuntimeException("Insufficient remaining budget ({$lockedRemaining}) to reactivate campaign.");
                }

                $previousStatus = $lockedCampaign->status;

                // Reactivate SAME campaign
                $lockedCampaign->status = AdCampaign::STATUS_ACTIVE;
                $lockedCampaign->approval_status = AdCampaign::APPROVAL_APPROVED;
                $lockedCampaign->approved_at = $lockedCampaign->approved_at ?? now();

                // Record audit trail in campaign metadata
                $targetAudience = (array) ($lockedCampaign->target_audience ?? []);
                $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
                $lifecycleHistory[] = [
                    'action' => 'reactivated',
                    'from_status' => $previousStatus,
                    'to_status' => AdCampaign::STATUS_ACTIVE,
                    'member_id' => $member->id,
                    'timestamp' => now()->toISOString(),
                    'remaining_amount' => $lockedRemaining,
                    'ip_address' => $request->ip(),
                ];
                $targetAudience['lifecycle_history'] = $lifecycleHistory;
                $lockedCampaign->target_audience = $targetAudience;

                $lockedCampaign->save();

                return $lockedCampaign;
            });

            return response()->json([
                'success' => true,
                'message' => 'Event campaign reactivated successfully.',
                'campaign' => $this->transformCampaign($reactivatedCampaign),
                'available_ad_funds' => round((float) ($member->fresh()->ad_balance ?? 0.00), 2),
            ], 200);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Event campaign reactivation failed', [
                'event_id' => $event->id,
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reactivating the event campaign.',
            ], 500);
        }
    }

    private function transformCampaign(AdCampaign $campaign): array
    {
        $depletionService = app(CampaignBudgetDepletionService::class);
        $minReward = null;
        try {
            $minReward = $depletionService->resolveMinimumRewardAmount($campaign);
        } catch (\Throwable) {
            $minReward = null;
        }

        $remaining = (float) ($campaign->remaining_amount ?? 0.00);
        $lowThreshold = (float) Setting::get('ad_campaign_low_budget_threshold', 1.00);

        $isExhausted = ($minReward !== null && $remaining < $minReward) ||
            in_array($campaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_BUDGET_EXHAUSTED], true);
        $isLowBudget = ($minReward !== null && !$isExhausted && $remaining <= $lowThreshold);
        $canReactivate = ($campaign->status === AdCampaign::STATUS_STOPPED && $minReward !== null && $remaining >= $minReward);

        return [
            'id' => $campaign->id,
            'campaign_id' => $campaign->campaign_id,
            'campaign_type' => $campaign->campaign_type,
            'event_id' => $campaign->event_id,
            'member_id' => $campaign->member_id,
            'campaign_name' => $campaign->campaign_name,
            'budget' => (float) ($campaign->budget ?? 0.00),
            'additional_funding' => (float) ($campaign->additional_funding ?? 0.00),
            'total_funded' => (float) ($campaign->total_funded ?? 0.00),
            'remaining_amount' => $remaining,
            'spent_amount' => (float) ($campaign->spent_amount ?? 0.00),
            'currency' => $campaign->currency ?? 'USD',
            'fee_percent' => (float) ($campaign->fee_percent ?? 2.50),
            'fee_amount' => (float) ($campaign->fee_amount ?? 0.00),
            'wallet_debit' => (float) ($campaign->wallet_debit ?? 0.00),
            'status' => $campaign->status,
            'approval_status' => $campaign->approval_status,
            'display_status' => $campaign->display_status,
            'is_budget_low' => $isLowBudget,
            'is_budget_exhausted' => $isExhausted,
            'is_low_budget' => $isLowBudget,
            'is_exhausted' => $isExhausted,
            'can_reactivate' => $canReactivate,
            'minimum_event_reward' => $minReward,
            'start_at' => $campaign->start_at?->toISOString(),
            'end_at' => $campaign->end_at?->toISOString(),
            'created_at' => $campaign->created_at?->toISOString(),
            'updated_at' => $campaign->updated_at?->toISOString(),
        ];
    }
}

