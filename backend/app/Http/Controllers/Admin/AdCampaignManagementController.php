<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdCampaignManagementController extends Controller
{
    /**
     * Display a listing of advertising campaigns for the admin control panel.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $approvalStatus = $request->input('approval_status');
        $businessPageId = $request->input('business_page_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = AdCampaign::query()
            ->businessAds()
            ->with([
                'businessPage:id,page_name,page_username,slug,logo',
                'owner:id,name,user_id,email,profile_photo',
                'post:id,member_id,business_page_id,body,media_type,media_path,created_at',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ])
            ->withCount(['impressions', 'clicks', 'rewards']);

        // Search Filter (Campaign ID, Campaign Name, Business Page Name/Username, Owner Name/Email)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('campaign_id', 'like', "%{$search}%")
                    ->orWhere('campaign_name', 'like', "%{$search}%")
                    ->orWhereHas('businessPage', function ($bq) use ($search) {
                        $bq->where('page_name', 'like', "%{$search}%")
                            ->orWhere('page_username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('owner', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('user_id', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Approval Status Filter
        if (!empty($approvalStatus)) {
            $query->where('approval_status', $approvalStatus);
        }

        // Business Page Filter
        if (!empty($businessPageId)) {
            $query->where('business_page_id', $businessPageId);
        }

        // Date Range Filter
        if (!empty($dateFrom) || !empty($dateTo)) {
            $request->validate([
                'date_from' => 'nullable|date_format:Y-m-d',
                'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            ]);
            $startDate = !empty($dateFrom) ? Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay() : null;
            $endDate = !empty($dateTo) ? Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay() : null;

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $campaigns = $query->latest('created_at')->paginate($perPage)->withQueryString();

        $campaigns->getCollection()->transform(function (AdCampaign $c) {
            $c->ctr = $c->ctr;
            $c->rewards_count = (int) ($c->rewards_count ?? 0);
            $c->rewards_paid = round((float) ($c->spent_amount ?? 0.00), 2);
            $c->remaining_campaign_budget = round((float) ($c->remaining_amount ?? 0.00), 2);
            $budget = round((float) ($c->budget ?? 0.00), 2);
            $spent = round((float) ($c->spent_amount ?? 0.00), 2);
            $remaining = round((float) ($c->remaining_amount ?? 0.00), 2);
            $c->financial_summary = [
                'campaign_budget' => $budget,
                'admin_fee_percent' => round((float) ($c->fee_percent ?? 0.00), 2),
                'admin_fee_amount' => round((float) ($c->fee_amount ?? 0.00), 2),
                'wallet_debit' => round((float) ($c->wallet_debit ?? 0.00), 2),
                'rewards_paid' => $spent,
                'remaining_campaign_budget' => $remaining,
                'verified_visits' => (int) ($c->rewards_count ?? 0),
                'reward_unit_usd' => AdCampaign::REWARD_AMOUNT,
                'campaign_status' => $c->status,
                'reconciles_exactly' => $budget === round($spent + $remaining, 2),
            ];
            return $c;
        });

        $totalImpressions = AdImpression::count();
        $totalClicks = AdClick::count();
        $totalRewardsPaid = round((float) AdReward::where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 2);
        $totalVerifiedVisits = AdReward::where('status', AdReward::STATUS_CREDITED)->count();
        $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.00;

        // Admin Overview Metrics with Complete Financial Separation & Reconciliation
        $metrics = [
            'total_campaigns' => AdCampaign::businessAds()->count(),
            'pending_review' => AdCampaign::businessAds()->where(function ($q) {
                $q->where('status', AdCampaign::STATUS_PENDING_REVIEW)
                    ->orWhere('approval_status', AdCampaign::APPROVAL_PENDING);
            })->count(),
            'approved' => AdCampaign::businessAds()->where('approval_status', AdCampaign::APPROVAL_APPROVED)->count(),
            'active' => AdCampaign::businessAds()->where('status', AdCampaign::STATUS_ACTIVE)->count(),
            'paused' => AdCampaign::businessAds()->where('status', AdCampaign::STATUS_PAUSED)->count(),
            'rejected' => AdCampaign::businessAds()->where('approval_status', AdCampaign::APPROVAL_REJECTED)->count(),
            'completed' => AdCampaign::businessAds()->where('status', AdCampaign::STATUS_COMPLETED)->count(),
            'stopped' => AdCampaign::businessAds()->where('status', AdCampaign::STATUS_STOPPED)->count(),
            'total_budget' => round((float) AdCampaign::businessAds()->sum('budget'), 2),
            'total_platform_fees' => round((float) AdCampaign::businessAds()->sum('fee_amount'), 2),
            'total_wallet_debits' => round((float) AdCampaign::businessAds()->sum('wallet_debit'), 2),
            'total_rewards_paid' => $totalRewardsPaid,
            'total_spent' => round((float) AdCampaign::businessAds()->sum('spent_amount'), 2),
            'total_remaining' => round((float) AdCampaign::businessAds()->sum('remaining_amount'), 2),
            'total_verified_visits' => $totalVerifiedVisits,
            'total_reward_count' => $totalVerifiedVisits,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'average_ctr' => $avgCtr,
            'campaign_platform_fee_percent' => (float) Setting::get('campaign_platform_fee_percent', 2.50),
        ];

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
            'metrics' => $metrics,
            'statuses' => AdCampaign::STATUSES,
            'approval_statuses' => AdCampaign::APPROVAL_STATUSES,
        ]);
    }

    /**
     * Display the specified campaign details in Admin panel.
     */
    public function show(Request $request, AdCampaign $adCampaign)
    {
        $adCampaign->load([
            'businessPage',
            'owner:id,name,user_id,email,profile_photo',
            'post',
            'post.member:id,name,user_id,profile_photo',
            'approver:id,name,email',
        ])->loadCount(['impressions', 'clicks', 'rewards']);

        $adCampaign->ctr = $adCampaign->ctr;
        $adCampaign->rewards_paid = round((float) ($adCampaign->spent_amount ?? 0.00), 2);
        $adCampaign->remaining_campaign_budget = round((float) ($adCampaign->remaining_amount ?? 0.00), 2);
        $adCampaign->verified_visits = (int) ($adCampaign->rewards_count ?? 0);
        $budget = round((float) ($adCampaign->budget ?? 0.00), 2);
        $spent = round((float) ($adCampaign->spent_amount ?? 0.00), 2);
        $remaining = round((float) ($adCampaign->remaining_amount ?? 0.00), 2);
        $adCampaign->financial_summary = [
            'campaign_budget' => $budget,
            'admin_fee_percent' => round((float) ($adCampaign->fee_percent ?? 0.00), 2),
            'admin_fee_amount' => round((float) ($adCampaign->fee_amount ?? 0.00), 2),
            'wallet_debit' => round((float) ($adCampaign->wallet_debit ?? 0.00), 2),
            'rewards_paid' => $spent,
            'remaining_campaign_budget' => $remaining,
            'verified_visits' => (int) ($adCampaign->rewards_count ?? 0),
            'reward_unit_usd' => AdCampaign::REWARD_AMOUNT,
            'campaign_status' => $adCampaign->status,
            'reconciles_exactly' => $budget === round($spent + $remaining, 2),
        ];

        return response()->json([
            'success' => true,
            'campaign' => $adCampaign,
        ]);
    }

    /**
     * Global platform-wide advertising analytics for Admin.
     */
    public function analytics(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'date_preset' => 'nullable|string',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $datePreset = $request->input('date_preset');

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
                case '7days':
                case '7d':
                    $startDate = now()->subDays(6)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case '30days':
                case '30d':
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

        $impressionsQuery = AdImpression::query();
        $clicksQuery = AdClick::query();
        $rewardsQuery = AdReward::where('status', AdReward::STATUS_CREDITED);
        $campaignsQuery = AdCampaign::businessAds();

        if ($startDate && $endDate) {
            $impressionsQuery->whereBetween('created_at', [$startDate, $endDate]);
            $clicksQuery->whereBetween('created_at', [$startDate, $endDate]);
            $rewardsQuery->whereBetween('created_at', [$startDate, $endDate]);
            $campaignsQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $impressionsQuery->where('created_at', '>=', $startDate);
            $clicksQuery->where('created_at', '>=', $startDate);
            $rewardsQuery->where('created_at', '>=', $startDate);
            $campaignsQuery->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $impressionsQuery->where('created_at', '<=', $endDate);
            $clicksQuery->where('created_at', '<=', $endDate);
            $rewardsQuery->where('created_at', '<=', $endDate);
            $campaignsQuery->where('created_at', '<=', $endDate);
        }

        $totalImpressions = (clone $impressionsQuery)->count();
        $totalClicks = (clone $clicksQuery)->count();
        $totalRewardsPaid = round((float) (clone $rewardsQuery)->sum('reward_amount_usd'), 2);
        $totalVerifiedVisits = (clone $rewardsQuery)->count();
        $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.00;

        $topCampaignsQuery = AdCampaign::query()
            ->businessAds()
            ->with(['businessPage:id,page_name,slug,logo', 'owner:id,name,user_id,email']);

        if ($startDate && $endDate) {
            $topCampaignsQuery->withCount([
                'impressions' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                'clicks' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                'rewards' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            ]);
        } elseif ($startDate) {
            $topCampaignsQuery->withCount([
                'impressions' => fn($q) => $q->where('created_at', '>=', $startDate),
                'clicks' => fn($q) => $q->where('created_at', '>=', $startDate),
                'rewards' => fn($q) => $q->where('created_at', '>=', $startDate),
            ]);
        } elseif ($endDate) {
            $topCampaignsQuery->withCount([
                'impressions' => fn($q) => $q->where('created_at', '<=', $endDate),
                'clicks' => fn($q) => $q->where('created_at', '<=', $endDate),
                'rewards' => fn($q) => $q->where('created_at', '<=', $endDate),
            ]);
        } else {
            $topCampaignsQuery->withCount(['impressions', 'clicks', 'rewards']);
        }

        $topCampaigns = $topCampaignsQuery
            ->orderByDesc('impressions_count')
            ->take(5)
            ->get()
            ->map(function (AdCampaign $c) {
                $c->ctr = $c->ctr;
                $c->rewards_paid = round((float) ($c->spent_amount ?? 0.00), 2);
                $c->remaining_campaign_budget = round((float) ($c->remaining_amount ?? 0.00), 2);
                $c->verified_visits = (int) ($c->rewards_count ?? 0);
                return $c;
            });

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_campaigns' => (clone $campaignsQuery)->count(),
                'active_campaigns' => (clone $campaignsQuery)->where('status', AdCampaign::STATUS_ACTIVE)->count(),
                'pending_review' => (clone $campaignsQuery)->where(function ($q) {
                    $q->where('status', AdCampaign::STATUS_PENDING_REVIEW)
                        ->orWhere('approval_status', AdCampaign::APPROVAL_PENDING);
                })->count(),
                'approved' => (clone $campaignsQuery)->where('approval_status', AdCampaign::APPROVAL_APPROVED)->count(),
                'paused' => (clone $campaignsQuery)->where('status', AdCampaign::STATUS_PAUSED)->count(),
                'rejected' => (clone $campaignsQuery)->where('approval_status', AdCampaign::APPROVAL_REJECTED)->count(),
                'completed' => (clone $campaignsQuery)->where('status', AdCampaign::STATUS_COMPLETED)->count(),
                'stopped' => (clone $campaignsQuery)->where('status', AdCampaign::STATUS_STOPPED)->count(),
                'total_budget' => round((float) (clone $campaignsQuery)->sum('budget'), 2),
                'total_admin_fees' => round((float) (clone $campaignsQuery)->sum('fee_amount'), 2),
                'total_platform_fees' => round((float) (clone $campaignsQuery)->sum('fee_amount'), 2),
                'total_wallet_debits' => round((float) (clone $campaignsQuery)->sum('wallet_debit'), 2),
                'total_rewards_paid' => $totalRewardsPaid,
                'total_spent' => round((float) (clone $campaignsQuery)->sum('spent_amount'), 2),
                'total_remaining' => round((float) (clone $campaignsQuery)->sum('remaining_amount'), 2),
                'total_remaining_budget' => round((float) (clone $campaignsQuery)->sum('remaining_amount'), 2),
                'total_verified_visits' => $totalVerifiedVisits,
                'total_reward_count' => $totalVerifiedVisits,
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'average_ctr' => $avgCtr,
                'recent_impressions_24h' => AdImpression::where('created_at', '>=', now()->subDay())->count(),
                'recent_clicks_24h' => AdClick::where('created_at', '>=', now()->subDay())->count(),
                'recent_verified_visits_24h' => AdReward::where('status', AdReward::STATUS_CREDITED)->where('created_at', '>=', now()->subDay())->count(),
                'recent_rewards_paid_24h' => round((float) AdReward::where('status', AdReward::STATUS_CREDITED)->where('created_at', '>=', now()->subDay())->sum('reward_amount_usd'), 2),
                'campaign_platform_fee_percent' => (float) Setting::get('campaign_platform_fee_percent', 2.50),
            ],
            'top_campaigns' => $topCampaigns,
        ]);
    }

    /**
     * Display a listing of reward history across all campaigns for Admin.
     */
    public function rewards(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'date_preset' => 'nullable|string',
        ]);

        $search = trim((string) $request->input('q'));
        $status = $request->input('status');
        $tier = $request->input('tier');
        $campaignId = $request->input('campaign_id');
        $memberId = $request->input('member_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $datePreset = $request->input('date_preset'); // today, yesterday, 7days, 30days, month

        $query = AdReward::query()
            ->with([
                'adCampaign:id,campaign_id,campaign_name,budget,status',
                'member:id,name,email,user_id,mobile_verified_at',
            ]);

        // Tier Filter
        if (!empty($tier)) {
            if ($tier === '0-5') {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('rule_min_referrals', 0)->where('rule_max_referrals', 5);
                    })->orWhere(function ($sub) {
                        $sub->whereNull('rule_min_referrals')->where('reward_amount_usd', '<=', 0.025);
                    });
                });
            } elseif ($tier === '6-14') {
                $query->where('rule_min_referrals', 6)->where('rule_max_referrals', 14);
            } elseif ($tier === '15+' || $tier === '15') {
                $query->where(function ($q) {
                    $q->where('rule_min_referrals', '>=', 15)
                        ->orWhere(function ($sub) {
                            $sub->where('rule_min_referrals', 15)->whereNull('rule_max_referrals');
                        });
                });
            }
        }

        // Date Handling
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
                case '7days':
                case '7d':
                    $startDate = now()->subDays(6)->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case '30days':
                case '30d':
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

        // Status Filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Campaign Filter
        if (!empty($campaignId)) {
            $query->where(function ($q) use ($campaignId) {
                if (is_numeric($campaignId)) {
                    $q->where('ad_campaign_id', $campaignId)
                        ->orWhereHas('adCampaign', function ($sub) use ($campaignId) {
                            $sub->where('campaign_id', $campaignId);
                        });
                } else {
                    $q->whereHas('adCampaign', function ($sub) use ($campaignId) {
                        $sub->where('campaign_id', $campaignId);
                    });
                }
            });
        }

        // Member Filter
        if (!empty($memberId)) {
            $query->where('member_id', $memberId);
        }

        // Search Filter (Member Name/Email/UserID, Campaign Name, Event ID)
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

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'created_at', 'reward_amount_usd', 'status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('created_at');
        }

        $rewards = $query->paginate(15)->withQueryString();

        $rewards->getCollection()->transform(function (AdReward $r) {
            $r->tier_label = $r->tier_label;
            $r->reward_formatted = $r->reward_formatted;
            $r->reward_amount_usd = (float) $r->reward_amount_usd;
            return $r;
        });

        $metricsQuery = AdReward::query();
        if ($startDate && $endDate) {
            $metricsQuery->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $metricsQuery->where('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $metricsQuery->where('created_at', '<=', $endDate);
        }

        $totalRewardsCount = (clone $metricsQuery)->count();
        $totalRewardsPaid = round((float) (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4);
        $totalVerifiedMembers = (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->distinct('member_id')->count('member_id');
        $totalRewardedCampaigns = (clone $metricsQuery)->where('status', AdReward::STATUS_CREDITED)->distinct('ad_campaign_id')->count('ad_campaign_id');

        $minReward = \App\Models\AdRewardRule::getMinimumActiveRewardAmount();
        $maxReward = (float) (\App\Models\AdRewardRule::getActiveRules()->max('reward_amount') ?? 0.05);

        return response()->json([
            'success' => true,
            'rewards' => $rewards,
            'metrics' => [
                'total_rewards_count' => $totalRewardsCount,
                'total_rewards_paid' => $totalRewardsPaid,
                'total_verified_members' => $totalVerifiedMembers,
                'total_rewarded_campaigns' => $totalRewardedCampaigns,
                'min_reward_usd' => $minReward,
                'max_reward_usd' => $maxReward,
                'reward_rate_range' => '$' . number_format($minReward, 3) . ' – $' . number_format($maxReward, 3) . ' USD',
                'reward_unit_usd' => $minReward,
            ],
        ]);
    }

    /**
     * Display reward history for a specific campaign.
     */
    public function campaignRewards(Request $request, AdCampaign $adCampaign)
    {
        $rewards = AdReward::where('ad_campaign_id', $adCampaign->id)
            ->with(['member:id,name,email,user_id,mobile_verified_at'])
            ->latest('created_at')
            ->paginate(15);

        $rewards->getCollection()->transform(function (AdReward $r) {
            $r->tier_label = $r->tier_label;
            $r->reward_formatted = $r->reward_formatted;
            $r->reward_amount_usd = (float) $r->reward_amount_usd;
            return $r;
        });

        return response()->json([
            'success' => true,
            'campaign_id' => $adCampaign->id,
            'campaign_name' => $adCampaign->campaign_name,
            'rewards' => $rewards,
            'summary' => [
                'total_rewards_count' => $adCampaign->rewards()->count(),
                'total_rewards_paid' => round((float) $adCampaign->rewards()->where('status', AdReward::STATUS_CREDITED)->sum('reward_amount_usd'), 4),
                'remaining_budget' => round((float) $adCampaign->remaining_amount, 4),
            ],
        ]);
    }

    /**
     * Export campaign financial summary data.
     */
    public function export(Request $request)
    {
        $campaigns = AdCampaign::with(['owner:id,name,email,user_id', 'businessPage:id,page_name'])
            ->withCount(['impressions', 'clicks', 'rewards'])
            ->latest('created_at')
            ->get()
            ->map(function (AdCampaign $c) {
                return [
                    'id' => $c->id,
                    'campaign_id' => $c->campaign_id,
                    'campaign_name' => $c->campaign_name,
                    'business_page' => $c->businessPage?->page_name,
                    'advertiser' => $c->owner?->name,
                    'advertiser_email' => $c->owner?->email,
                    'budget' => round((float) $c->budget, 2),
                    'fee_percent' => round((float) $c->fee_percent, 2),
                    'fee_amount' => round((float) $c->fee_amount, 2),
                    'wallet_debit' => round((float) $c->wallet_debit, 2),
                    'rewards_paid' => round((float) $c->spent_amount, 2),
                    'remaining_budget' => round((float) $c->remaining_amount, 2),
                    'verified_visits' => (int) $c->rewards_count,
                    'status' => $c->status,
                    'created_at' => $c->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Approve a submitted advertising campaign.
     */
    public function approve(Request $request, AdCampaign $adCampaign)
    {
        if ($adCampaign->businessPage && $adCampaign->businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        $admin = auth('admin')->user();
        $adminId = $admin ? $admin->id : 1;

        if (!$adCampaign->approve($adminId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot approve campaign in current state: ' . $adCampaign->status . ' (Approval: ' . $adCampaign->approval_status . ')',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign approved successfully.',
            'campaign' => $adCampaign->fresh([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ]),
        ]);
    }

    /**
     * Reject a submitted advertising campaign.
     */
    public function reject(Request $request, AdCampaign $adCampaign)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $admin = auth('admin')->user();
        $adminId = $admin ? $admin->id : 1;

        $refundedAmount = 0.00;
        DB::transaction(function () use ($adCampaign, $adminId, $validated, &$refundedAmount) {
            if (!$adCampaign->reject($adminId, trim($validated['rejection_reason']))) {
                throw new \RuntimeException('Cannot reject campaign in current state: ' . $adCampaign->status);
            }

            $unspent = (float) ($adCampaign->remaining_amount ?? 0.00);
            if ($unspent > 0 && $adCampaign->member_id) {
                /** @var Member $owner */
                $owner = Member::where('id', $adCampaign->member_id)->lockForUpdate()->first();
                if ($owner) {
                    $owner->ad_balance = round((float) ($owner->ad_balance ?? 0.00) + $unspent, 2);
                    $owner->save();
                    $refundedAmount = $unspent;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $refundedAmount > 0
                ? "Ad campaign rejected. Unspent budget of \${$refundedAmount} USD has been refunded to member's ad funds."
                : 'Ad campaign rejected.',
            'refunded_amount' => $refundedAmount,
            'campaign' => $adCampaign->fresh([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ]),
        ]);
    }

    /**
     * Pause an approved or active campaign from Admin panel.
     */
    public function pause(Request $request, AdCampaign $adCampaign)
    {
        if (!$adCampaign->pause()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot pause campaign in current status: ' . $adCampaign->status,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign paused by administrator.',
            'campaign' => $adCampaign->fresh([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ]),
        ]);
    }

    /**
     * Resume a paused campaign from Admin panel.
     */
    public function resume(Request $request, AdCampaign $adCampaign)
    {
        if ($adCampaign->businessPage && $adCampaign->businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        if (!$adCampaign->resume()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resume campaign. Campaign must be approved and currently paused.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign resumed by administrator.',
            'campaign' => $adCampaign->fresh([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ]),
        ]);
    }

    /**
     * Stop a campaign from Admin panel (preserves data row).
     */
    public function stop(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($adCampaign->status === AdCampaign::STATUS_STOPPED) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign is already stopped.',
            ], 422);
        }

        if (in_array($adCampaign->status, [AdCampaign::STATUS_CANCELLED, AdCampaign::STATUS_COMPLETED], true)) {
            return response()->json([
                'success' => false,
                'message' => "Campaign cannot be stopped because it is {$adCampaign->status}.",
            ], 422);
        }

        if (!$adCampaign->stop()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is already stopped or finished.',
            ], 422);
        }

        // Record lifecycle history
        $targetAudience = $adCampaign->target_audience ?? [];
        $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
        $lifecycleHistory[] = [
            'action' => 'admin_stopped',
            'admin_id' => $request->user('admin')?->id ?? auth('admin')->id(),
            'timestamp' => now()->toIso8601String(),
        ];
        $targetAudience['lifecycle_history'] = $lifecycleHistory;
        $adCampaign->target_audience = $targetAudience;
        $adCampaign->save();

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign stopped by administrator.',
            'campaign' => $adCampaign->fresh([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ]),
        ]);
    }

    /**
     * Restart a stopped campaign from Admin panel.
     */
    public function restart(Request $request, AdCampaign $adCampaign): JsonResponse
    {
        if ($adCampaign->businessPage && $adCampaign->businessPage->posts()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one post or photo to your Business Page before running an ad campaign.',
                'errors' => [
                    'business_page' => ['Please add at least one post or photo to your Business Page before running an ad campaign.'],
                ],
            ], 422);
        }

        if ($adCampaign->status === AdCampaign::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'This campaign is already active.',
            ], 422);
        }

        if ($adCampaign->status === AdCampaign::STATUS_PAUSED) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign is currently paused. Please use Resume Campaign instead.',
            ], 422);
        }

        if ($adCampaign->status !== AdCampaign::STATUS_STOPPED) {
            return response()->json([
                'success' => false,
                'message' => "Campaign cannot be restarted because its current status is '{$adCampaign->status}'. Only stopped campaigns can be restarted.",
            ], 422);
        }

        if ($adCampaign->approval_status !== AdCampaign::APPROVAL_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be restarted because it is not approved.',
            ], 422);
        }

        $minReward = AdRewardRule::getMinimumActiveRewardAmount(
            $adCampaign->campaign_type === AdCampaign::TYPE_EVENT ? AdRewardRule::TYPE_EVENT : AdRewardRule::TYPE_BUSINESS_AD
        ) ?? AdRewardRule::getMinimumActiveRewardAmount() ?? 0.0250;

        if ((float) $adCampaign->remaining_amount < $minReward || (float) $adCampaign->remaining_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be restarted because its budget has been exhausted.',
            ], 422);
        }

        if ($adCampaign->end_at && $adCampaign->end_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be restarted because it has expired.',
            ], 422);
        }

        if (!$adCampaign->restart()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart campaign. Please verify campaign status and budget.',
            ], 422);
        }

        // Record lifecycle history
        $targetAudience = $adCampaign->target_audience ?? [];
        $lifecycleHistory = (array) ($targetAudience['lifecycle_history'] ?? []);
        $lifecycleHistory[] = [
            'action' => 'admin_restarted',
            'admin_id' => $request->user('admin')?->id ?? auth('admin')->id(),
            'timestamp' => now()->toIso8601String(),
        ];
        $targetAudience['lifecycle_history'] = $lifecycleHistory;
        $adCampaign->target_audience = $targetAudience;
        $adCampaign->save();

        return response()->json([
            'success' => true,
            'message' => 'Ad campaign restarted successfully.',
            'campaign' => $adCampaign->fresh([
                'businessPage',
                'owner:id,name,user_id,email,profile_photo',
                'post',
                'post.member:id,name,user_id,profile_photo',
                'approver:id,name,email',
            ]),
        ]);
    }

    /**
     * Get campaign settings (Platform Fee %).
     */
    public function getSettings(): JsonResponse
    {
        $feePercent = (float) Setting::get('campaign_platform_fee_percent', 2.50);

        return response()->json([
            'success' => true,
            'settings' => [
                'campaign_platform_fee_percent' => $feePercent,
                'currency' => 'USD',
                'currency_symbol' => '$',
                'description' => 'Admin-configurable Campaign Platform Fee deducted from member advertising wallet upon campaign creation. The campaign running budget remains the exact campaign budget entered by the advertiser.',
            ],
        ]);
    }

    /**
     * Update campaign settings (Platform Fee %).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_platform_fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $feePercent = round((float) $validated['campaign_platform_fee_percent'], 2);
        Setting::set('campaign_platform_fee_percent', (string) $feePercent, 'ads');

        return response()->json([
            'success' => true,
            'message' => 'Campaign settings updated successfully.',
            'settings' => [
                'campaign_platform_fee_percent' => $feePercent,
                'currency' => 'USD',
                'currency_symbol' => '$',
                'description' => 'Admin-configurable Campaign Platform Fee deducted from member advertising wallet upon campaign creation. The campaign running budget remains the exact campaign budget entered by the advertiser.',
            ],
        ]);
    }
}
