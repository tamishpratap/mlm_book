<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\RewardRankRule;
use App\Services\RewardRankResolver;
use App\Services\RewardRankService;
use App\Services\RewardRankValidationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RewardManagementController extends Controller
{
    public function __construct(
        protected RewardRankService $rankService,
        protected RewardRankValidationService $validationService,
        protected RewardRankResolver $rankResolver
    ) {}

    // =========================================================================
    // REWARD RULES SECTION (Dynamic Rank-Based Reward Rules)
    // =========================================================================

    /**
     * Display listing of central rank-based reward rules for the admin configuration panel.
     */
    public function indexRules(Request $request): JsonResponse
    {
        $rules = $this->rankService->getRules();
        $activeValidation = $this->validationService->validateActiveSet($rules);

        return response()->json([
            'success' => true,
            'rules' => $rules,
            'is_configuration_complete' => $activeValidation['is_complete'],
            'incomplete_warning' => !empty($activeValidation['errors']) ? $activeValidation['errors'][0] : null,
            'continuity_errors' => $activeValidation['errors'],
            'order_warnings' => $activeValidation['warnings'],
            'active_set_validation' => $activeValidation,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'max_active_reward_usd' => RewardRankRule::getMaximumActiveRewardAmount(),
            'min_active_reward_usd' => RewardRankRule::getMinimumActiveRewardAmount(),
        ]);
    }

    /**
     * Dedicated active set structural validation endpoint.
     */
    public function validateActiveSet(Request $request): JsonResponse
    {
        $validation = $this->rankService->validateActiveSet();

        return response()->json([
            'success' => true,
            'validation' => $validation,
        ]);
    }

    /**
     * Store/configure a rank reward rule.
     */
    public function storeRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rank_key' => ['required', 'string', 'in:advertiser,influencer,leaders,pro_leaders,master_leaders'],
            'referral_requirement' => ['required', 'integer', 'min:0'],
            'team_requirement' => ['required', 'integer', 'min:0'],
            'reward_amount' => [
                'required',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,4})?$/',
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'rank_key.in' => 'Rank must be one of: Advertiser, Influencer, Leaders, Pro Leaders, Master Leaders.',
            'referral_requirement.min' => 'Referral requirement must be 0 or greater.',
            'team_requirement.min' => 'Team requirement must be 0 or greater.',
            'reward_amount.min' => 'Reward amount cannot be negative.',
            'reward_amount.regex' => 'Reward amount precision cannot exceed 4 decimal places.',
        ]);

        try {
            $admin = auth('admin')->user();
            $rule = $this->rankService->createRule($validated, $admin);

            return response()->json([
                'success' => true,
                'message' => "Rank rule for '{$rule->rank_name}' configured successfully.",
                'rule' => $rule->load(['createdBy:id,name,email', 'updatedBy:id,name,email']),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update an existing rank rule safely without rewriting historical reward records.
     */
    public function updateRule(Request $request, $rewardRankRule): JsonResponse
    {
        $rule = $rewardRankRule instanceof RewardRankRule ? $rewardRankRule : RewardRankRule::findOrFail($rewardRankRule);

        $validated = $request->validate([
            'referral_requirement' => ['required', 'integer', 'min:0'],
            'team_requirement' => ['required', 'integer', 'min:0'],
            'reward_amount' => [
                'required',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,4})?$/',
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'referral_requirement.min' => 'Referral requirement must be 0 or greater.',
            'team_requirement.min' => 'Team requirement must be 0 or greater.',
            'reward_amount.min' => 'Reward amount cannot be negative.',
            'reward_amount.regex' => 'Reward amount precision cannot exceed 4 decimal places.',
        ]);

        try {
            $admin = auth('admin')->user();
            $updated = $this->rankService->updateRule($rule, $validated, $admin);

            return response()->json([
                'success' => true,
                'message' => "Rank rule for '{$updated->rank_name}' updated successfully.",
                'rule' => $updated->fresh(['createdBy:id,name,email', 'updatedBy:id,name,email']),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Safely toggle the active status of a rank rule.
     */
    public function toggleRuleStatus(Request $request, $rewardRankRule): JsonResponse
    {
        $rule = $rewardRankRule instanceof RewardRankRule ? $rewardRankRule : RewardRankRule::findOrFail($rewardRankRule);

        try {
            $admin = auth('admin')->user();
            $updated = $this->rankService->toggleStatus($rule, $admin);

            return response()->json([
                'success' => true,
                'message' => "Rank rule '{$updated->rank_name}' " . ($updated->is_active ? 'activated' : 'disabled') . ' successfully.',
                'rule' => $updated->fresh(['createdBy:id,name,email', 'updatedBy:id,name,email']),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Simulate rank and reward resolution for arbitrary direct referral count and team count.
     */
    public function previewRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referral_count' => ['required', 'integer', 'min:0'],
            'team_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $referrals = (int) $validated['referral_count'];
        $team = isset($validated['team_count']) ? (int) $validated['team_count'] : 0;

        $preview = $this->rankService->previewResolution($referrals, $team);

        return response()->json($preview);
    }

    // =========================================================================
    // REWARD HISTORY SECTION (Event Rewards Tab & Ad Rewards Tab)
    // =========================================================================

    /**
     * TAB 1: Event Rewards History.
     * Displays existing event-related reward history from ad_rewards table.
     */
    public function eventRewardsHistory(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'date_preset' => 'nullable|string',
        ]);

        $query = AdReward::query()
            ->whereHas('adCampaign', function ($cq) {
                $cq->where('campaign_type', AdCampaign::TYPE_EVENT)
                    ->orWhereNotNull('event_id');
            })
            ->with([
                'member:id,name,email,user_id,mobile_verified_at',
                'adCampaign:id,campaign_id,campaign_name,campaign_type,event_id,budget,status',
                'adCampaign.event:id,title,organizer_id,start_date,end_date',
                'adRewardRule:id,min_referrals,max_referrals,reward_amount',
            ]);

        $this->applyHistoryFilters($query, $request);

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $rewards = $query->paginate($perPage)->withQueryString();

        $rewards->getCollection()->transform(function (AdReward $r) {
            $r->tier_label = $r->tier_label;
            $r->reward_formatted = $r->reward_formatted;
            $r->reward_amount_usd = (float) $r->reward_amount_usd;
            return $r;
        });

        // Compute tab-specific metrics
        $metricsQuery = AdReward::query()
            ->whereHas('adCampaign', function ($cq) {
                $cq->where('campaign_type', AdCampaign::TYPE_EVENT)
                    ->orWhereNotNull('event_id');
            });
        $this->applyDateFilters($metricsQuery, $request);

        $metrics = [
            'total_rewards_count' => (clone $metricsQuery)->count(),
            'total_rewards_paid' => round((float) (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4),
            'total_verified_members' => (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->distinct('member_id')->count('member_id'),
            'total_events_rewarded' => (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->distinct('ad_campaign_id')->count('ad_campaign_id'),
        ];

        return response()->json([
            'success' => true,
            'rewards' => $rewards,
            'metrics' => $metrics,
        ]);
    }

    /**
     * TAB 2: Ad / Business Page Advertisement Rewards History.
     * Displays existing ad-related reward history from ad_rewards table.
     */
    public function adRewardsHistory(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'date_preset' => 'nullable|string',
        ]);

        $query = AdReward::query()
            ->whereHas('adCampaign', function ($cq) {
                $cq->where('campaign_type', '!=', AdCampaign::TYPE_EVENT)
                    ->whereNull('event_id');
            })
            ->with([
                'member:id,name,email,user_id,mobile_verified_at',
                'adCampaign:id,campaign_id,campaign_name,campaign_type,business_page_id,budget,status',
                'adCampaign.businessPage:id,name,slug',
                'adRewardRule:id,min_referrals,max_referrals,reward_amount',
            ]);

        $this->applyHistoryFilters($query, $request);

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $rewards = $query->paginate($perPage)->withQueryString();

        $rewards->getCollection()->transform(function (AdReward $r) {
            $r->tier_label = $r->tier_label;
            $r->reward_formatted = $r->reward_formatted;
            $r->reward_amount_usd = (float) $r->reward_amount_usd;
            return $r;
        });

        // Compute tab-specific metrics
        $metricsQuery = AdReward::query()
            ->whereHas('adCampaign', function ($cq) {
                $cq->where('campaign_type', '!=', AdCampaign::TYPE_EVENT)
                    ->whereNull('event_id');
            });
        $this->applyDateFilters($metricsQuery, $request);

        $metrics = [
            'total_rewards_count' => (clone $metricsQuery)->count(),
            'total_rewards_paid' => round((float) (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4),
            'total_verified_members' => (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->distinct('member_id')->count('member_id'),
            'total_campaigns_rewarded' => (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->distinct('ad_campaign_id')->count('ad_campaign_id'),
        ];

        return response()->json([
            'success' => true,
            'rewards' => $rewards,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Overall aggregate metrics for Reward History.
     */
    public function historyMetrics(Request $request): JsonResponse
    {
        $eventBase = AdReward::query()->whereHas('adCampaign', fn ($cq) => $cq->where('campaign_type', AdCampaign::TYPE_EVENT)->orWhereNotNull('event_id'));
        $adBase = AdReward::query()->whereHas('adCampaign', fn ($cq) => $cq->where('campaign_type', '!=', AdCampaign::TYPE_EVENT)->whereNull('event_id'));
        $overallBase = AdReward::query();

        $metrics = [
            'overall' => [
                'total_rewards_count' => (clone $overallBase)->count(),
                'total_rewards_paid' => round((float) (clone $overallBase)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4),
                'total_verified_members' => (clone $overallBase)->where('status', AdReward::STATUS_CREDITED)->distinct('member_id')->count('member_id'),
                'total_rewarded_campaigns' => (clone $overallBase)->where('status', AdReward::STATUS_CREDITED)->distinct('ad_campaign_id')->count('ad_campaign_id'),
            ],
            'events' => [
                'total_rewards_count' => (clone $eventBase)->count(),
                'total_rewards_paid' => round((float) (clone $eventBase)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4),
                'total_verified_members' => (clone $eventBase)->where('status', AdReward::STATUS_CREDITED)->distinct('member_id')->count('member_id'),
                'total_rewarded_campaigns' => (clone $eventBase)->where('status', AdReward::STATUS_CREDITED)->distinct('ad_campaign_id')->count('ad_campaign_id'),
            ],
            'ads' => [
                'total_rewards_count' => (clone $adBase)->count(),
                'total_rewards_paid' => round((float) (clone $adBase)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4),
                'total_verified_members' => (clone $adBase)->where('status', AdReward::STATUS_CREDITED)->distinct('member_id')->count('member_id'),
                'total_rewarded_campaigns' => (clone $adBase)->where('status', AdReward::STATUS_CREDITED)->distinct('ad_campaign_id')->count('ad_campaign_id'),
            ],
        ];

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function applyHistoryFilters($query, Request $request): void
    {
        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $tier = $request->input('tier');

        // Search Filter
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('qualifying_event_id', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('user_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('adCampaign', function ($cq) use ($search) {
                        $cq->where('campaign_name', 'like', "%{$search}%")
                            ->orWhere('campaign_id', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        // Tier Filter
        if (!empty($tier) && $tier !== 'all') {
            if ($tier === '0-5') {
                $query->where('rule_min_referrals', 0);
            } elseif ($tier === '6-14') {
                $query->where('rule_min_referrals', 6);
            } elseif ($tier === '15+') {
                $query->where('rule_min_referrals', '>=', 15);
            }
        }

        // Date Range Filters
        $this->applyDateFilters($query, $request);

        // Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = strtolower((string) $request->input('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['created_at', 'reward_amount_usd', 'direct_verified_referral_count', 'status'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    protected function applyDateFilters($query, Request $request): void
    {
        $preset = $request->input('date_preset');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($preset) {
            $now = Carbon::now();
            match ($preset) {
                'today' => $query->whereDate('created_at', $now->toDateString()),
                'yesterday' => $query->whereDate('created_at', $now->subDay()->toDateString()),
                'last_7_days' => $query->where('created_at', '>=', $now->subDays(7)->startOfDay()),
                'last_30_days' => $query->where('created_at', '>=', $now->subDays(30)->startOfDay()),
                'this_month' => $query->where('created_at', '>=', $now->startOfMonth()),
                'last_month' => $query->whereBetween('created_at', [
                    $now->subMonth()->startOfMonth(),
                    $now->subMonth()->endOfMonth(),
                ]),
                default => null,
            };
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif ($dateTo) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }
    }
}
