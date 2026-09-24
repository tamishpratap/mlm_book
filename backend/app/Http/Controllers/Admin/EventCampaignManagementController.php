<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Setting;
use App\Services\RewardRuleValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EventCampaignManagementController extends Controller
{
    public function __construct(
        protected RewardRuleValidationService $validationService
    ) {}

    /**
     * Authorize admin user with required permission.
     */
    protected function authorizeAdmin(Request $request, string $permission = 'view-events'): ?JsonResponse
    {
        $admin = $request->user('admin') ?? auth('admin')->user();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (method_exists($admin, 'roles') && $admin->roles()->exists()) {
            if (!$admin->hasRole('super-admin') && !$admin->hasPermission($permission) && !$admin->hasPermission('control-ad-campaigns')) {
                return response()->json([
                    'success' => false,
                    'message' => "Unauthorized. Missing required permission [{$permission}].",
                ], 403);
            }
        }

        return null;
    }

    /**
     * Display a paginated listing of Event campaigns for the Admin Event Control Center.
     */
    public function index(Request $request): JsonResponse
    {
        if ($authError = $this->authorizeAdmin($request, 'view-events')) {
            return $authError;
        }

        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'preset' => 'nullable|string',
        ]);

        $search = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $budgetState = $request->input('budget_state');
        $datePreset = $request->input('preset');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);

        $minActiveReward = (float) (AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.05);
        $lowBudgetThreshold = (float) Setting::get('ad_campaign_low_budget_threshold', 1.00);

        $query = AdCampaign::query()
            ->events()
            ->with([
                'event:id,title,slug,status,start_date,end_date,event_type,location_city,location_country,organizer_id,cover_photo',
                'event.organizer:id,name,email,user_id,phone,mobile_verified_at',
                'owner:id,name,email,user_id,phone,mobile_verified_at',
                'approver:id,name,email',
            ])
            ->withCount(['rewards']);

        // Search Filter (Campaign ID, Campaign Name, Event Title/Description, Creator Name/Email/Phone/User ID)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('campaign_id', 'like', "%{$search}%")
                    ->orWhere('campaign_name', 'like', "%{$search}%")
                    ->orWhereHas('event', function ($eq) use ($search) {
                        $eq->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    })
                    ->orWhereHas('owner', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('user_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('event.organizer', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('user_id', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if (!empty($status)) {
            if ($status === 'low_budget') {
                $query->whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED])
                    ->where('remaining_amount', '>=', $minActiveReward)
                    ->where('remaining_amount', '<=', $lowBudgetThreshold);
            } elseif ($status === 'exhausted') {
                $query->where(function ($q) use ($minActiveReward) {
                    $q->where('status', AdCampaign::STATUS_STOPPED)
                        ->orWhere('status', AdCampaign::STATUS_BUDGET_EXHAUSTED)
                        ->orWhere('remaining_amount', '<', $minActiveReward);
                });
            } else {
                $query->where('status', $status);
            }
        }

        // Budget State Filter
        if (!empty($budgetState)) {
            if ($budgetState === 'low_budget') {
                $query->where('remaining_amount', '>=', $minActiveReward)
                    ->where('remaining_amount', '<=', $lowBudgetThreshold);
            } elseif ($budgetState === 'exhausted') {
                $query->where('remaining_amount', '<', $minActiveReward);
            } elseif ($budgetState === 'funded') {
                $query->where('total_funded', '>', 0);
            }
        }

        // Date Range & Presets
        $startDate = null;
        $endDate = null;

        if (!empty($dateFrom) || !empty($dateTo)) {
            $startDate = !empty($dateFrom) ? Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay() : null;
            $endDate = !empty($dateTo) ? Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay() : null;
        } elseif (!empty($datePreset)) {
            switch ($datePreset) {
                case 'today':
                    $startDate = now()->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'yesterday':
                    $startDate = now()->subDay()->startOfDay();
                    $endDate = now()->subDay()->endOfDay();
                    break;
                case '7d':
                case '7days':
                    $startDate = now()->subDays(6)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case '30d':
                case '30days':
                    $startDate = now()->subDays(29)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'month':
                case 'this_month':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfDay();
                    break;
            }
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $campaigns = $query->latest('created_at')->paginate($perPage)->withQueryString();

        // Transform paginated collection
        $campaigns->getCollection()->transform(function (AdCampaign $c) use ($minActiveReward, $lowBudgetThreshold) {
            $budget = round((float) $c->budget, 4);
            $additional = round((float) ($c->additional_funding ?? 0.00), 4);
            $totalFunded = round((float) ($c->total_funded ?? ($budget + $additional)), 4);
            $spent = round((float) ($c->spent_amount ?? 0.00), 4);
            $remaining = round((float) ($c->remaining_amount ?? max(0.00, $totalFunded - $spent)), 4);

            $isLowBudget = ($remaining >= $minActiveReward) && ($remaining <= $lowBudgetThreshold);
            $isExhausted = ($remaining < $minActiveReward) || in_array($c->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_BUDGET_EXHAUSTED], true);

            $interestedCount = $c->event_id
                ? EventResponse::where('event_id', $c->event_id)->where('response', 'interested')->count()
                : 0;

            $rewardedCount = (int) ($c->rewards_count ?? 0);
            $rewardsPaidUsd = round((float) AdReward::where('ad_campaign_id', $c->id)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4);

            $creator = $c->owner ?? $c->event?->organizer;

            return [
                'id' => $c->id,
                'campaign_id' => $c->campaign_id,
                'campaign_name' => $c->campaign_name,
                'campaign_type' => $c->campaign_type,
                'status' => $c->status,
                'is_active' => (bool) $c->is_active,
                'is_low_budget' => $isLowBudget,
                'is_exhausted' => $isExhausted,
                'event' => $c->event ? [
                    'id' => $c->event->id,
                    'title' => $c->event->title,
                    'slug' => $c->event->slug,
                    'status' => $c->event->status,
                    'start_date' => $c->event->start_date?->toIso8601String(),
                    'end_date' => $c->event->end_date?->toIso8601String(),
                    'location' => trim(($c->event->location_city ?? '') . ', ' . ($c->event->location_country ?? ''), ', '),
                    'event_type' => $c->event->event_type,
                    'cover_image' => $c->event->cover_photo_url ?? $c->event->cover_photo,
                ] : null,
                'creator' => $creator ? [
                    'id' => $creator->id,
                    'name' => $creator->name,
                    'email' => $creator->email,
                    'phone' => $creator->phone,
                    'is_mobile_verified' => (bool) ($creator->mobile_verified_at),
                ] : null,
                'budget' => [
                    'initial_budget' => $budget,
                    'additional_funding' => $additional,
                    'total_funded' => $totalFunded,
                    'spent_amount' => $spent,
                    'remaining_amount' => $remaining,
                    'currency' => 'USD',
                    'minimum_event_reward' => $minActiveReward,
                    'reconciliation' => [
                        'total_funded' => $totalFunded,
                        'accounted_for' => round($spent + $remaining, 4),
                        'is_balanced' => abs($totalFunded - ($spent + $remaining)) < 0.0001,
                    ],
                ],
                'participation' => [
                    'interested_count' => $interestedCount,
                    'rewarded_count' => $rewardedCount,
                    'unrewarded_count' => max(0, $interestedCount - $rewardedCount),
                    'total_rewards_paid_usd' => $rewardsPaidUsd,
                ],
                'created_at' => $c->created_at?->toIso8601String(),
                'updated_at' => $c->updated_at?->toIso8601String(),
                'actions' => [
                    'can_pause' => in_array($c->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true),
                    'can_resume' => $c->status === AdCampaign::STATUS_PAUSED && $remaining >= $minActiveReward,
                    'can_stop' => !in_array($c->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_COMPLETED], true),
                ],
            ];
        });

        // Global Event Campaign Metrics
        $allEventCampaigns = AdCampaign::events();
        $rewardsMetricsQuery = AdReward::whereNotNull('qualifying_event_id')->where('status', AdReward::STATUS_CREDITED);

        if ($startDate && $endDate) {
            $allEventCampaigns->whereBetween('created_at', [$startDate, $endDate]);
            $rewardsMetricsQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $allEventCampaigns->where('created_at', '>=', $startDate);
            $rewardsMetricsQuery->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $allEventCampaigns->where('created_at', '<=', $endDate);
            $rewardsMetricsQuery->where('created_at', '<=', $endDate);
        }

        $totalEventCampaigns = (clone $allEventCampaigns)->count();
        $activeCampaigns = (clone $allEventCampaigns)->where('status', AdCampaign::STATUS_ACTIVE)->count();
        $pausedCampaigns = (clone $allEventCampaigns)->where('status', AdCampaign::STATUS_PAUSED)->count();
        $stoppedCampaigns = (clone $allEventCampaigns)->where('status', AdCampaign::STATUS_STOPPED)->count();

        $lowBudgetCount = (clone $allEventCampaigns)
            ->whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED])
            ->where('remaining_amount', '>=', $minActiveReward)
            ->where('remaining_amount', '<=', $lowBudgetThreshold)
            ->count();

        $exhaustedCount = (clone $allEventCampaigns)
            ->where(function ($q) use ($minActiveReward) {
                $q->where('status', AdCampaign::STATUS_STOPPED)
                    ->orWhere('status', AdCampaign::STATUS_BUDGET_EXHAUSTED)
                    ->orWhere('remaining_amount', '<', $minActiveReward);
            })->count();

        $totalBudgetFunded = round((float) (clone $allEventCampaigns)->sum('total_funded'), 4);
        $totalSpent = round((float) (clone $allEventCampaigns)->sum('spent_amount'), 4);
        $totalRemaining = round((float) (clone $allEventCampaigns)->sum('remaining_amount'), 4);

        $totalRewardsPaid = round((float) (clone $rewardsMetricsQuery)->sum('reward_amount_usd'), 4);
        $totalRewardedCount = (clone $rewardsMetricsQuery)->count();
        $totalInterestedCount = EventResponse::where('response', 'interested')
            ->whereHas('event', function ($eq) {
                $eq->whereHas('campaign', function ($cq) {
                    $cq->where('campaign_type', AdCampaign::TYPE_EVENT);
                });
            })->count();

        // Phase 10-11 Reward Rule Health Check
        $activeRulesValidation = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);
        $activeEventRules = AdRewardRule::forEvent()->where('is_active', true)->get();

        $ruleHealth = [
            'active_rules_count' => $activeEventRules->count(),
            'is_valid' => $activeRulesValidation['valid'] ?? false,
            'errors' => $activeRulesValidation['errors'] ?? [],
            'has_baseline_tier' => $activeRulesValidation['has_baseline_tier'] ?? false,
            'min_reward_usd' => $minActiveReward,
            'max_reward_usd' => (float) ($activeEventRules->max('reward_amount') ?? 0.05),
        ];

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
            'metrics' => [
                'total_event_campaigns' => $totalEventCampaigns,
                'active_campaigns' => $activeCampaigns,
                'paused_campaigns' => $pausedCampaigns,
                'stopped_campaigns' => $stoppedCampaigns,
                'low_budget_campaigns' => $lowBudgetCount,
                'exhausted_campaigns' => $exhaustedCount,
                'total_budget_funded' => $totalBudgetFunded,
                'total_spent' => $totalSpent,
                'total_remaining' => $totalRemaining,
                'total_rewards_paid' => $totalRewardsPaid,
                'total_interested' => $totalInterestedCount,
                'total_rewarded' => $totalRewardedCount,
                'minimum_event_reward' => $minActiveReward,
            ],
            'rule_health' => $ruleHealth,
            'statuses' => AdCampaign::STATUSES,
        ]);
    }

    /**
     * Display authoritative detailed inspection of a specific Event campaign.
     */
    public function show(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($authError = $this->authorizeAdmin($request, 'view-events')) {
            return $authError;
        }

        if ($adCampaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json([
                'success' => false,
                'message' => 'Requested campaign is not an event campaign.',
            ], 404);
        }

        $adCampaign->load([
            'event.organizer:id,name,email,user_id,phone,mobile_verified_at',
            'owner:id,name,email,user_id,phone,mobile_verified_at',
            'approver:id,name,email',
        ]);

        $minActiveReward = (float) (AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.05);
        $lowBudgetThreshold = (float) Setting::get('ad_campaign_low_budget_threshold', 1.00);

        $budget = round((float) $adCampaign->budget, 4);
        $additional = round((float) ($adCampaign->additional_funding ?? 0.00), 4);
        $totalFunded = round((float) ($adCampaign->total_funded ?? ($budget + $additional)), 4);
        $spent = round((float) ($adCampaign->spent_amount ?? 0.00), 4);
        $remaining = round((float) ($adCampaign->remaining_amount ?? max(0.00, $totalFunded - $spent)), 4);

        $isLowBudget = ($remaining >= $minActiveReward) && ($remaining <= $lowBudgetThreshold);
        $isExhausted = ($remaining < $minActiveReward) || in_array($adCampaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_BUDGET_EXHAUSTED], true);

        $interestedCount = $adCampaign->event_id
            ? EventResponse::where('event_id', $adCampaign->event_id)->where('response', 'interested')->count()
            : 0;

        $rewardedCount = AdReward::where('ad_campaign_id', $adCampaign->id)->where('status', AdReward::STATUS_CREDITED)->count();
        $rewardsPaidUsd = round((float) AdReward::where('ad_campaign_id', $adCampaign->id)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4);

        $recentParticipants = $adCampaign->event_id
            ? EventResponse::where('event_id', $adCampaign->event_id)
                ->where('response', 'interested')
                ->with(['member:id,name,email,phone,mobile_verified_at'])
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(function ($resp) use ($adCampaign) {
                    $reward = AdReward::where('ad_campaign_id', $adCampaign->id)
                        ->where('member_id', $resp->member_id)
                        ->where('status', AdReward::STATUS_CREDITED)
                        ->first();

                    return [
                        'member_id' => $resp->member_id,
                        'name' => $resp->member?->name,
                        'email' => $resp->member?->email,
                        'phone' => $resp->member?->phone,
                        'responded_at' => $resp->created_at?->toIso8601String(),
                        'is_rewarded' => $reward !== null,
                        'reward' => $reward ? [
                            'reward_amount_usd' => (float) $reward->reward_amount_usd,
                            'rule_tier' => $reward->rule_min_referrals . ($reward->rule_max_referrals !== null ? '–' . $reward->rule_max_referrals : '+'),
                            'credited_at' => $reward->created_at?->toIso8601String(),
                        ] : null,
                    ];
                })
            : [];

        $creator = $adCampaign->owner ?? $adCampaign->event?->organizer;

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $adCampaign->id,
                'campaign_id' => $adCampaign->campaign_id,
                'campaign_name' => $adCampaign->campaign_name,
                'campaign_type' => $adCampaign->campaign_type,
                'status' => $adCampaign->status,
                'is_active' => (bool) $adCampaign->is_active,
                'is_low_budget' => $isLowBudget,
                'is_exhausted' => $isExhausted,
                'event' => $adCampaign->event ? [
                    'id' => $adCampaign->event->id,
                    'title' => $adCampaign->event->title,
                    'slug' => $adCampaign->event->slug,
                    'status' => $adCampaign->event->status,
                    'start_date' => $adCampaign->event->start_date?->toIso8601String(),
                    'end_date' => $adCampaign->event->end_date?->toIso8601String(),
                    'location' => trim(($adCampaign->event->location_city ?? '') . ', ' . ($adCampaign->event->location_country ?? ''), ', '),
                    'event_type' => $adCampaign->event->event_type,
                    'cover_image' => $adCampaign->event->cover_photo_url ?? $adCampaign->event->cover_photo,
                ] : null,
                'creator' => $creator ? [
                    'id' => $creator->id,
                    'name' => $creator->name,
                    'email' => $creator->email,
                    'phone' => $creator->phone,
                    'is_mobile_verified' => (bool) ($creator->mobile_verified_at),
                ] : null,
                'financials' => [
                    'initial_budget' => $budget,
                    'additional_funding' => $additional,
                    'total_funded' => $totalFunded,
                    'spent_amount' => $spent,
                    'remaining_amount' => $remaining,
                    'currency' => 'USD',
                    'minimum_event_reward' => $minActiveReward,
                    'reconciliation' => [
                        'total_funded' => $totalFunded,
                        'accounted_for' => round($spent + $remaining, 4),
                        'is_balanced' => abs($totalFunded - ($spent + $remaining)) < 0.0001,
                    ],
                ],
                'participation' => [
                    'interested_count' => $interestedCount,
                    'rewarded_count' => $rewardedCount,
                    'unrewarded_count' => max(0, $interestedCount - $rewardedCount),
                    'total_rewards_paid_usd' => $rewardsPaidUsd,
                ],
                'lifecycle_history' => $adCampaign->target_audience['lifecycle_history'] ?? [],
                'funding_history' => $adCampaign->target_audience['funding_history'] ?? [],
                'created_at' => $adCampaign->created_at?->toIso8601String(),
                'updated_at' => $adCampaign->updated_at?->toIso8601String(),
            ],
            'recent_participants' => $recentParticipants,
        ]);
    }

    /**
     * Display paginated participants drilldown for an Event campaign.
     */
    public function participants(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($authError = $this->authorizeAdmin($request, 'view-events')) {
            return $authError;
        }

        if ($adCampaign->campaign_type !== AdCampaign::TYPE_EVENT || !$adCampaign->event_id) {
            return response()->json([
                'success' => false,
                'message' => 'Requested campaign is not an event campaign.',
            ], 404);
        }

        $search = trim((string) $request->input('q', ''));
        $rewardStatus = $request->input('reward_status', 'all');
        $datePreset = $request->input('preset');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = min(max((int) $request->input('per_page', 25), 5), 100);

        $query = EventResponse::where('event_id', $adCampaign->event_id)
            ->where('response', 'interested')
            ->with(['member:id,name,email,phone,mobile_verified_at,created_at']);

        if ($search !== '') {
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($datePreset === 'today') {
            $query->whereDate('created_at', now()->today());
        } elseif ($datePreset === '7d' || $datePreset === '7days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($datePreset === '30d' || $datePreset === '30days') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $participants = $query->latest('created_at')->paginate($perPage)->withQueryString();

        $memberIds = $participants->getCollection()->pluck('member_id')->unique()->filter()->all();
        $rewardsByMember = AdReward::where('ad_campaign_id', $adCampaign->id)
            ->where('status', AdReward::STATUS_CREDITED)
            ->whereIn('member_id', $memberIds)
            ->get()
            ->keyBy('member_id');

        $participants->getCollection()->transform(function ($resp) use ($rewardsByMember) {
            $reward = $rewardsByMember->get($resp->member_id);

            return [
                'id' => $resp->id,
                'member_id' => $resp->member_id,
                'name' => $resp->member?->name,
                'email' => $resp->member?->email,
                'phone' => $resp->member?->phone,
                'is_mobile_verified' => (bool) ($resp->member?->mobile_verified_at),
                'responded_at' => $resp->created_at?->toIso8601String(),
                'is_rewarded' => $reward !== null,
                'reward' => $reward ? [
                    'reward_amount_usd' => (float) $reward->reward_amount_usd,
                    'rule_tier' => $reward->rule_min_referrals . ($reward->rule_max_referrals !== null ? '–' . $reward->rule_max_referrals : '+'),
                    'direct_verified_referral_count' => $reward->direct_verified_referral_count,
                    'credited_at' => $reward->created_at?->toIso8601String(),
                ] : null,
            ];
        });

        if ($rewardStatus === 'credited' || $rewardStatus === 'rewarded') {
            $filtered = $participants->getCollection()->filter(fn($p) => $p['is_rewarded'])->values();
            $participants->setCollection($filtered);
        } elseif ($rewardStatus === 'unpaid' || $rewardStatus === 'unrewarded') {
            $filtered = $participants->getCollection()->filter(fn($p) => !$p['is_rewarded'])->values();
            $participants->setCollection($filtered);
        }

        return response()->json([
            'success' => true,
            'campaign_id' => $adCampaign->id,
            'participants' => $participants,
        ]);
    }

    /**
     * Pause an active Event campaign from Admin panel.
     */
    public function pause(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($authError = $this->authorizeAdmin($request, 'edit-events')) {
            return $authError;
        }

        if ($adCampaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json(['success' => false, 'message' => 'Not an event campaign.'], 404);
        }

        return DB::transaction(function () use ($adCampaign, $request) {
            $locked = AdCampaign::where('id', $adCampaign->id)->lockForUpdate()->first();

            if (!in_array($locked->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot pause event campaign in current status: {$locked->status}.",
                ], 422);
            }

            $locked->status = AdCampaign::STATUS_PAUSED;

            $targetAudience = $locked->target_audience ?? [];
            $lifecycleHistory = $targetAudience['lifecycle_history'] ?? [];
            $lifecycleHistory[] = [
                'action' => 'admin_paused',
                'admin_id' => $request->user('admin')?->id,
                'timestamp' => now()->toIso8601String(),
            ];
            $targetAudience['lifecycle_history'] = $lifecycleHistory;
            $locked->target_audience = $targetAudience;
            $locked->save();

            return response()->json([
                'success' => true,
                'message' => 'Event campaign has been paused by administrator.',
                'status' => $locked->status,
                'is_active' => false,
            ]);
        });
    }

    /**
     * Resume a paused Event campaign from Admin panel.
     */
    public function resume(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($authError = $this->authorizeAdmin($request, 'edit-events')) {
            return $authError;
        }

        if ($adCampaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json(['success' => false, 'message' => 'Not an event campaign.'], 404);
        }

        return DB::transaction(function () use ($adCampaign, $request) {
            $locked = AdCampaign::where('id', $adCampaign->id)->lockForUpdate()->first();

            if ($locked->status !== AdCampaign::STATUS_PAUSED && $locked->status !== AdCampaign::STATUS_STOPPED) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot resume event campaign in status: {$locked->status}. Must be paused or stopped.",
                ], 422);
            }

            $minActiveReward = (float) (AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT) ?? 0.05);

            if ((float) $locked->remaining_amount < $minActiveReward) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot resume event campaign. Remaining budget (\${$locked->remaining_amount}) is less than minimum reward (\${$minActiveReward}). Additional funds must be added first.",
                ], 422);
            }

            $locked->status = AdCampaign::STATUS_ACTIVE;

            $targetAudience = $locked->target_audience ?? [];
            $lifecycleHistory = $targetAudience['lifecycle_history'] ?? [];
            $lifecycleHistory[] = [
                'action' => 'admin_resumed',
                'admin_id' => $request->user('admin')?->id,
                'timestamp' => now()->toIso8601String(),
            ];
            $targetAudience['lifecycle_history'] = $lifecycleHistory;
            $locked->target_audience = $targetAudience;
            $locked->save();

            return response()->json([
                'success' => true,
                'message' => 'Event campaign has been resumed by administrator.',
                'status' => $locked->status,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Stop an Event campaign from Admin panel.
     */
    public function stop(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($authError = $this->authorizeAdmin($request, 'edit-events')) {
            return $authError;
        }

        if ($adCampaign->campaign_type !== AdCampaign::TYPE_EVENT) {
            return response()->json(['success' => false, 'message' => 'Not an event campaign.'], 404);
        }

        return DB::transaction(function () use ($adCampaign, $request) {
            $locked = AdCampaign::where('id', $adCampaign->id)->lockForUpdate()->first();

            if (in_array($locked->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_COMPLETED], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event campaign is already stopped.',
                ], 422);
            }

            $locked->status = AdCampaign::STATUS_STOPPED;

            $targetAudience = $locked->target_audience ?? [];
            $lifecycleHistory = $targetAudience['lifecycle_history'] ?? [];
            $lifecycleHistory[] = [
                'action' => 'admin_stopped',
                'admin_id' => $request->user('admin')?->id,
                'timestamp' => now()->toIso8601String(),
            ];
            $targetAudience['lifecycle_history'] = $lifecycleHistory;
            $locked->target_audience = $targetAudience;
            $locked->save();

            return response()->json([
                'success' => true,
                'message' => 'Event campaign has been stopped by administrator.',
                'status' => $locked->status,
                'is_active' => false,
            ]);
        });
    }
}
