<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdCampaignActivity;
use App\Models\AdReward;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdCampaignEngagementService
{
    /**
     * Record an Interested action event (when a verified member clicks "Interested" / Earn button).
     */
    public function recordInterested(
        AdCampaign $campaign,
        Member $member,
        ?string $ip = null,
        ?string $userAgent = null,
        array $metadata = []
    ): AdCampaignActivity {
        return AdCampaignActivity::create([
            'ad_campaign_id' => $campaign->id,
            'business_page_id' => $campaign->business_page_id,
            'member_id' => $member->id,
            'action' => AdCampaignActivity::ACTION_INTERESTED,
            'action_label' => 'Interested',
            'qualifying_event_id' => $metadata['click_key'] ?? ('int_' . $campaign->id . '_' . $member->id . '_' . time()),
            'ad_reward_id' => null,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record an Ad Click action event (when any member clicks the ad card/destination link).
     */
    public function recordClick(
        AdCampaign $campaign,
        ?Member $member,
        ?string $placement = 'social_feed',
        ?string $clickKey = null,
        ?string $ip = null,
        ?string $userAgent = null,
        array $metadata = []
    ): AdCampaignActivity {
        return AdCampaignActivity::create([
            'ad_campaign_id' => $campaign->id,
            'business_page_id' => $campaign->business_page_id,
            'member_id' => $member?->id,
            'action' => AdCampaignActivity::ACTION_CLICKED,
            'action_label' => 'Ad Click',
            'qualifying_event_id' => $clickKey,
            'ad_reward_id' => null,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'metadata' => array_merge($metadata, ['placement' => $placement]),
        ]);
    }

    /**
     * Record a Landing Page Visit action event.
     */
    public function recordLandingVisit(
        AdCampaign $campaign,
        Member $member,
        ?string $qualifyingEventId = null,
        ?string $landingPageUrl = null,
        ?string $ip = null,
        ?string $userAgent = null,
        array $metadata = []
    ): AdCampaignActivity {
        return AdCampaignActivity::create([
            'ad_campaign_id' => $campaign->id,
            'business_page_id' => $campaign->business_page_id,
            'member_id' => $member->id,
            'action' => AdCampaignActivity::ACTION_VISITED_LANDING_PAGE,
            'action_label' => 'Visited Landing Page',
            'qualifying_event_id' => $qualifyingEventId,
            'ad_reward_id' => null,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'metadata' => array_merge($metadata, array_filter(['landing_page_url' => $landingPageUrl])),
        ]);
    }

    /**
     * Record a successful Rewarded Visit action event linked to an AdReward transaction.
     */
    public function recordRewarded(
        AdCampaign $campaign,
        Member $member,
        AdReward $reward,
        ?string $ip = null,
        ?string $userAgent = null,
        array $metadata = []
    ): AdCampaignActivity {
        return AdCampaignActivity::create([
            'ad_campaign_id' => $campaign->id,
            'business_page_id' => $campaign->business_page_id,
            'member_id' => $member->id,
            'action' => AdCampaignActivity::ACTION_REWARDED,
            'action_label' => 'Rewarded Visit',
            'qualifying_event_id' => $reward->qualifying_event_id,
            'ad_reward_id' => $reward->id,
            'ip_address' => $ip ?? $reward->ip_address,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : $reward->user_agent,
            'metadata' => array_merge($metadata, [
                'reward_amount_usd' => (float) $reward->reward_amount_usd,
                'tier_label' => $reward->tier_label,
                'direct_verified_referral_count' => $reward->direct_verified_referral_count,
            ]),
        ]);
    }

    /**
     * Record a Failed / Rejected qualification attempt (e.g. unverified, budget depleted, duplicate).
     */
    public function recordFailed(
        AdCampaign $campaign,
        ?Member $member,
        string $reason,
        ?string $action = AdCampaignActivity::ACTION_FAILED,
        ?string $ip = null,
        ?string $userAgent = null,
        array $metadata = []
    ): AdCampaignActivity {
        return AdCampaignActivity::create([
            'ad_campaign_id' => $campaign->id,
            'business_page_id' => $campaign->business_page_id,
            'member_id' => $member?->id,
            'action' => $action ?? AdCampaignActivity::ACTION_FAILED,
            'action_label' => 'Failed Qualification',
            'qualifying_event_id' => $metadata['qualifying_event_id'] ?? null,
            'ad_reward_id' => null,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'metadata' => array_merge($metadata, ['reason' => $reason]),
        ]);
    }

    /**
     * Build the filterable query for campaign activities.
     */
    public function getActivitiesQuery(AdCampaign $campaign, array $filters = []): Builder
    {
        $query = AdCampaignActivity::query()
            ->select('ad_campaign_activities.*')
            ->where('ad_campaign_activities.ad_campaign_id', $campaign->id)
            ->with([
                'member:id,name,user_id,email,phone,profile_photo,mobile_verified_at,city,country',
                'reward:id,ad_campaign_id,member_id,reward_amount_usd,qualifying_event_id,status,direct_verified_referral_count,rule_min_referrals,rule_max_referrals,rule_version,created_at',
            ]);

        // 1. Action filter
        $action = $filters['action'] ?? 'all';
        if ($action !== 'all' && !empty($action)) {
            if ($action === 'rewards' || $action === 'rewarded') {
                $query->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_REWARDED);
            } elseif ($action === 'clicks') {
                $query->whereIn('ad_campaign_activities.action', [AdCampaignActivity::ACTION_CLICKED, AdCampaignActivity::ACTION_INTERESTED]);
            } elseif ($action === 'clicked') {
                $query->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_CLICKED);
            } elseif ($action === 'interested') {
                $query->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_INTERESTED);
            } elseif ($action === 'visited_landing_page' || $action === 'landing_visits') {
                $query->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_VISITED_LANDING_PAGE);
            } elseif ($action === 'failed') {
                $query->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_FAILED);
            } else {
                $query->where('ad_campaign_activities.action', $action);
            }
        }

        // 2. Reward Status filter
        $rewardStatus = $filters['reward_status'] ?? 'all';
        if ($rewardStatus !== 'all' && !empty($rewardStatus)) {
            if ($rewardStatus === 'rewarded') {
                $query->where(function ($q) {
                    $q->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_REWARDED)
                        ->orWhereHas('reward', fn ($rq) => $rq->where('status', AdReward::STATUS_CREDITED));
                });
            } elseif ($rewardStatus === 'not_rewarded') {
                $query->where('ad_campaign_activities.action', '!=', AdCampaignActivity::ACTION_REWARDED)
                    ->where(function ($q) {
                        $q->whereNull('ad_campaign_activities.ad_reward_id')
                            ->orWhereDoesntHave('reward', fn ($rq) => $rq->where('status', AdReward::STATUS_CREDITED));
                    });
            } elseif ($rewardStatus === 'failed') {
                $query->where(function ($q) {
                    $q->where('ad_campaign_activities.action', AdCampaignActivity::ACTION_FAILED)
                        ->orWhereHas('reward', fn ($rq) => $rq->where('status', '!=', AdReward::STATUS_CREDITED));
                });
            }
        }

        // 3. Keyword Search filter (Search display name, username, numerical ID, email, or phone)
        if (!empty($filters['search']) || !empty($filters['q'])) {
            $search = trim((string) ($filters['search'] ?? $filters['q']));
            $query->where(function ($sq) use ($search) {
                $sq->whereHas('member', function ($mq) use ($search) {
                    $mq->where('name', 'like', "%{$search}%")
                        ->orWhere('user_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });

                if (is_numeric($search)) {
                    $sq->orWhere('ad_campaign_activities.member_id', (int) $search);
                }
            });
        }

        // 4. Verification filter
        $verification = $filters['verification'] ?? (!empty($filters['verified_only']) ? 'verified' : 'all');
        if ($verification === 'verified') {
            $query->whereHas('member', fn ($mq) => $mq->whereNotNull('mobile_verified_at'));
        } elseif ($verification === 'unverified') {
            $query->whereHas('member', fn ($mq) => $mq->whereNull('mobile_verified_at'));
        }

        // 5. Date Preset and Custom Range filter
        $datePreset = $filters['date_preset'] ?? 'all';
        if ($datePreset === 'today') {
            $query->where('ad_campaign_activities.created_at', '>=', now()->startOfDay());
        } elseif ($datePreset === 'yesterday') {
            $query->whereBetween('ad_campaign_activities.created_at', [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
            ]);
        } elseif ($datePreset === 'last_7_days') {
            $query->where('ad_campaign_activities.created_at', '>=', now()->subDays(7)->startOfDay());
        } elseif ($datePreset === 'last_30_days') {
            $query->where('ad_campaign_activities.created_at', '>=', now()->subDays(30)->startOfDay());
        } elseif ($datePreset === 'custom' || (!empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['date_from']) || !empty($filters['date_to']))) {
            $startDate = $filters['start_date'] ?? $filters['date_from'] ?? null;
            $endDate = $filters['end_date'] ?? $filters['date_to'] ?? null;

            if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
                // Swap if inverted
                $temp = $startDate;
                $startDate = $endDate;
                $endDate = $temp;
            }

            if ($startDate) {
                $query->where('ad_campaign_activities.created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
            }
            if ($endDate) {
                $query->where('ad_campaign_activities.created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
            }
        }

        // 6. Member ID filter (for single member timeline queries)
        if (!empty($filters['member_id'])) {
            $query->where('ad_campaign_activities.member_id', $filters['member_id']);
        }

        // 7. Sorting
        $sort = $filters['sort'] ?? $filters['sort_by'] ?? 'newest';
        if ($sort === 'oldest') {
            return $query->orderBy('ad_campaign_activities.created_at', 'asc')->orderBy('ad_campaign_activities.id', 'asc');
        } elseif ($sort === 'highest_reward') {
            return $query->leftJoin('ad_rewards', 'ad_campaign_activities.ad_reward_id', '=', 'ad_rewards.id')
                ->orderByDesc('ad_rewards.reward_amount_usd')
                ->orderByDesc('ad_campaign_activities.created_at');
        } elseif ($sort === 'lowest_reward') {
            return $query->leftJoin('ad_rewards', 'ad_campaign_activities.ad_reward_id', '=', 'ad_rewards.id')
                ->orderBy('ad_rewards.reward_amount_usd', 'asc')
                ->orderBy('ad_campaign_activities.created_at', 'asc');
        }

        return $query->orderByDesc('ad_campaign_activities.created_at')->orderByDesc('ad_campaign_activities.id');
    }

    /**
     * Get aggregate engagement KPI statistics and advanced analytics for a campaign.
     */
    public function getSummary(AdCampaign $campaign): array
    {
        $baseQuery = AdCampaignActivity::where('ad_campaign_id', $campaign->id);

        $totalActivities = (clone $baseQuery)->count();
        $totalEngagedMembers = (clone $baseQuery)->whereNotNull('member_id')->distinct('member_id')->count('member_id');

        $actionCounts = (clone $baseQuery)
            ->select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->pluck('count', 'action');

        $interestedCount = (int) ($actionCounts[AdCampaignActivity::ACTION_INTERESTED] ?? 0);
        $clickCount = (int) ($actionCounts[AdCampaignActivity::ACTION_CLICKED] ?? 0);
        $landingVisitCount = (int) ($actionCounts[AdCampaignActivity::ACTION_VISITED_LANDING_PAGE] ?? 0);
        $rewardedCount = (int) ($actionCounts[AdCampaignActivity::ACTION_REWARDED] ?? 0);
        $rewardQualifiedCount = (clone $baseQuery)
            ->whereIn('action', [AdCampaignActivity::ACTION_REWARDED, AdCampaignActivity::ACTION_VISITED_LANDING_PAGE])
            ->whereNotNull('qualifying_event_id')
            ->distinct('member_id')
            ->count('member_id');

        // Authoritative reward records from ad_rewards
        $successfulRewardsQuery = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('status', AdReward::STATUS_CREDITED);

        $totalRewardsPaid = round((float) (clone $successfulRewardsQuery)->sum('reward_amount_usd'), 4);
        $actualRewardRecordCount = (clone $successfulRewardsQuery)->count();
        $effectiveRewardCount = max($rewardedCount, $actualRewardRecordCount);

        // Verified engaged members
        $verifiedMembersCount = (clone $baseQuery)
            ->whereHas('member', fn ($mq) => $mq->whereNotNull('mobile_verified_at'))
            ->distinct('member_id')
            ->count('member_id');

        // Financial reconciliation
        $originalBudget = (float) $campaign->budget;
        $additionalFunding = (float) $campaign->additional_funding;
        $totalFunded = (float) ($campaign->total_funded ?? ($originalBudget + $additionalFunding));
        $feePercent = (float) ($campaign->fee_percent ?? 2.5);
        $feeAmount = (float) ($campaign->fee_amount ?? round($totalFunded * ($feePercent / 100), 2));
        $spentAmount = (float) $campaign->spent_amount;
        $remainingBudget = (float) $campaign->remaining_amount;

        // Conversion and engagement rates
        $ctrPercent = $totalActivities > 0 ? round(($clickCount / $totalActivities) * 100, 2) : 0.0;
        $landingConversionRate = $clickCount > 0 ? round(($landingVisitCount / $clickCount) * 100, 2) : 0.0;
        $rewardConversionRate = $totalEngagedMembers > 0 ? round(($effectiveRewardCount / $totalEngagedMembers) * 100, 2) : 0.0;

        // Phase 5: Grouping by tier_label removed as People Engaged now exclusively shows qualified rewarded users
        $tierBreakdown = [];

        // 30-Day Daily Activity & Reward Trend Time Series
        $dailyTrends = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN action = 'interested' THEN 1 ELSE 0 END) as interested"),
                DB::raw("SUM(CASE WHEN action = 'clicked' THEN 1 ELSE 0 END) as clicks"),
                DB::raw("SUM(CASE WHEN action = 'visited_landing_page' THEN 1 ELSE 0 END) as landing_visits"),
                DB::raw("SUM(CASE WHEN action = 'rewarded' THEN 1 ELSE 0 END) as rewards")
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'interested' => (int) $row->interested,
                    'clicks' => (int) $row->clicks,
                    'landing_visits' => (int) $row->landing_visits,
                    'rewards' => (int) $row->rewards,
                ];
            })->values()->toArray();

        return [
            'total_engagements' => $totalActivities,
            'total_engaged_members' => $totalEngagedMembers,
            'interested_count' => $interestedCount,
            'click_count' => $clickCount,
            'landing_visit_count' => $landingVisitCount,
            'reward_qualified_count' => $rewardQualifiedCount,
            'total_rewards_count' => $effectiveRewardCount,
            'total_rewards_paid' => $totalRewardsPaid,
            'remaining_budget' => round($remainingBudget, 2),
            'verified_members_count' => $verifiedMembersCount,
            'financials' => [
                'original_budget' => round($originalBudget, 2),
                'additional_funding' => round($additionalFunding, 2),
                'total_funded' => round($totalFunded, 2),
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount' => round($feeAmount, 2),
                'spent_amount' => round($spentAmount, 4),
                'remaining_budget' => round($remainingBudget, 4),
            ],
            'rates' => [
                'ctr_percent' => $ctrPercent,
                'landing_conversion_rate' => $landingConversionRate,
                'reward_conversion_rate' => $rewardConversionRate,
            ],
            'tier_breakdown' => $tierBreakdown,
            'daily_trends' => $dailyTrends,
        ];
    }

    /**
     * Get paginated activities formatted for the People Engaged log.
     */
    public function getPaginatedActivities(
        AdCampaign $campaign,
        array $filters = [],
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        $query = $this->getActivitiesQuery($campaign, $filters);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get complete authoritative engagement history and reward drill-down for a single member on a campaign.
     */
    public function getMemberEngagementDetail(AdCampaign $campaign, Member $member): array
    {
        // 1. Fetch all activity events chronologically
        $activities = AdCampaignActivity::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->with('reward')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 2. Fetch authoritative reward record (if rewarded)
        $reward = AdReward::where('ad_campaign_id', $campaign->id)
            ->where('member_id', $member->id)
            ->first();

        // 3. Member summary counts on this specific campaign
        $interestedCount = $activities->where('action', AdCampaignActivity::ACTION_INTERESTED)->count();
        $clickCount = $activities->where('action', AdCampaignActivity::ACTION_CLICKED)->count();
        $landingVisitCount = $activities->where('action', AdCampaignActivity::ACTION_VISITED_LANDING_PAGE)->count();
        $failedCount = $activities->where('action', AdCampaignActivity::ACTION_FAILED)->count();
        $rewardedCount = $activities->where('action', AdCampaignActivity::ACTION_REWARDED)->count();

        // Fallback for legacy campaigns
        if ($activities->isEmpty()) {
            $legacyClicks = \App\Models\AdClick::where('ad_campaign_id', $campaign->id)
                ->where('member_id', $member->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($legacyClicks as $lc) {
                $isInterested = str_contains((string) $lc->placement, 'interested') || str_starts_with((string) $lc->click_key, 'int_');
                if ($isInterested) {
                    $interestedCount++;
                } else {
                    $clickCount++;
                }
            }
            if ($reward) {
                $rewardedCount = 1;
                $landingVisitCount = 1;
            }
        }

        $rewardAmount = $reward ? (float) ($reward->reward_amount_usd ?? 0.00) : 0.00;
        $rewardStatus = $reward && $reward->status === AdReward::STATUS_CREDITED 
            ? 'rewarded' 
            : ($failedCount > 0 ? 'failed' : ($activities->isNotEmpty() || $reward ? 'not_rewarded' : 'no_activity'));

        // 4. Map timeline events
        $timeline = $activities->map(function (AdCampaignActivity $act) {
            $actReward = $act->reward;
            $amt = $actReward ? (float) ($actReward->reward_amount_usd ?? 0.00) : 0.00;

            return [
                'id' => $act->id,
                'event_id' => $act->qualifying_event_id,
                'action' => $act->action,
                'action_label' => $act->action_label ?? ucfirst(str_replace('_', ' ', $act->action)),
                'reward_amount_usd' => $amt,
                'reward_formatted' => $amt > 0 ? '+$' . number_format($amt, 4, '.', '') . ' USD' : '$0.00',
                'tier_label' => $actReward?->tier_label,
                'direct_verified_referral_count' => $actReward?->direct_verified_referral_count,
                'metadata' => $act->metadata,
                'created_at' => $act->created_at?->toIso8601String(),
                'timestamp' => $act->created_at?->timestamp ?? 0,
            ];
        })->values();

        // If timeline was empty but legacy clicks/rewards existed, map them
        if ($timeline->isEmpty()) {
            $legacyTimeline = collect();
            $legacyClicks = \App\Models\AdClick::where('ad_campaign_id', $campaign->id)
                ->where('member_id', $member->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($legacyClicks as $lc) {
                $isInterested = str_contains((string) $lc->placement, 'interested') || str_starts_with((string) $lc->click_key, 'int_');
                $legacyTimeline->push([
                    'id' => 'click_' . $lc->id,
                    'event_id' => $lc->click_key,
                    'action' => $isInterested ? AdCampaignActivity::ACTION_INTERESTED : AdCampaignActivity::ACTION_CLICKED,
                    'action_label' => $isInterested ? 'Interested' : 'Ad Click',
                    'reward_amount_usd' => 0.00,
                    'reward_formatted' => '$0.00',
                    'tier_label' => null,
                    'direct_verified_referral_count' => null,
                    'metadata' => ['placement' => $lc->placement],
                    'created_at' => $lc->created_at?->toIso8601String(),
                    'timestamp' => $lc->created_at?->timestamp ?? 0,
                ]);
            }

            if ($reward) {
                $legacyTimeline->push([
                    'id' => 'reward_' . $reward->id,
                    'event_id' => $reward->qualifying_event_id,
                    'action' => AdCampaignActivity::ACTION_REWARDED,
                    'action_label' => 'Rewarded Visit',
                    'reward_amount_usd' => $rewardAmount,
                    'reward_formatted' => '+$' . number_format($rewardAmount, 4, '.', '') . ' USD',
                    'tier_label' => $reward->tier_label,
                    'direct_verified_referral_count' => $reward->direct_verified_referral_count,
                    'metadata' => ['qualifying_event_id' => $reward->qualifying_event_id],
                    'created_at' => $reward->created_at?->toIso8601String(),
                    'timestamp' => $reward->created_at?->timestamp ?? 0,
                ]);
            }

            $timeline = $legacyTimeline->sortBy('timestamp')->values();
        }

        return [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'username' => $member->user_id,
                'email' => $member->email,
                'phone' => $member->phone,
                'avatar_url' => $member->avatar_url,
                'is_verified' => method_exists($member, 'isMobileVerified') && $member->isMobileVerified(),
                'city' => $member->city,
                'country' => $member->country,
                'joined_at' => $member->created_at?->toIso8601String(),
            ],
            'campaign' => [
                'id' => $campaign->id,
                'campaign_id' => $campaign->campaign_id,
                'campaign_name' => $campaign->campaign_name,
                'campaign_objective' => $campaign->campaign_objective,
                'destination_link' => $campaign->destination_link,
                'budget' => (float) $campaign->budget,
                'remaining_amount' => (float) ($campaign->remaining_amount ?? 0.00),
                'spent_amount' => (float) ($campaign->spent_amount ?? 0.00),
                'status' => $campaign->status,
                'business_page_id' => $campaign->business_page_id,
                'business_page_name' => $campaign->businessPage?->page_name,
                'post_id' => $campaign->post_id,
            ],
            'summary' => [
                'total_activities' => $timeline->count(),
                'interested_count' => $interestedCount,
                'clicks_count' => $clickCount,
                'landing_visits_count' => $landingVisitCount,
                'rewarded_count' => $rewardedCount,
                'failed_count' => $failedCount,
                'reward_status' => $rewardStatus,
                'is_rewarded' => $reward !== null && $reward->status === AdReward::STATUS_CREDITED,
                'reward_amount_usd' => $rewardAmount,
                'reward_formatted' => $rewardAmount > 0 ? '+$' . number_format($rewardAmount, 4, '.', '') . ' USD' : '$0.00',
            ],
            'reward_detail' => $reward ? [
                'id' => $reward->id,
                'qualifying_event_id' => $reward->qualifying_event_id,
                'status' => $reward->status,
                'reward_amount_usd' => (float) $reward->reward_amount_usd,
                'reward_amount_exact' => number_format((float) $reward->reward_amount_usd, 4, '.', ''),
                'reward_formatted' => '+$' . number_format((float) $reward->reward_amount_usd, 4, '.', '') . ' USD',
                'direct_verified_referral_count' => (int) ($reward->direct_verified_referral_count ?? 0),
                'tier_label' => $reward->tier_label,
                'rule_min_referrals' => $reward->rule_min_referrals,
                'rule_max_referrals' => $reward->rule_max_referrals,
                'rule_version' => $reward->rule_version,
                'credited_at' => $reward->created_at?->toIso8601String(),
                'transaction_reference' => 'REW-AD-' . $reward->id . '-' . substr(md5((string) $reward->qualifying_event_id), 0, 8),
            ] : null,
            'timeline' => $timeline,
        ];
    }

    /**
     * Generate structured CSV export for a campaign's audience based on current filters or explicit selection.
     */
    public function exportAudienceCsv(
        AdCampaign $campaign,
        array $filters = [],
        array $selectedMemberIds = [],
        string $mode = 'audience'
    ): StreamedResponse {
        $filename = 'campaign-audience-' . ($campaign->campaign_id ?: $campaign->id) . '-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($campaign, $filters, $selectedMemberIds, $mode) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            if ($mode === 'activities') {
                // Activity-level export
                fputcsv($handle, [
                    'Activity ID',
                    'Event ID',
                    'Member ID',
                    'Display Name',
                    'Username',
                    'Action',
                    'Reward Status',
                    'Reward Amount (USD)',
                    'Dynamic Tier Snapshot',
                    'Direct Referrals at Reward',
                    'Mobile / WhatsApp',
                    'Email',
                    'City',
                    'Country',
                    'Timestamp',
                ]);

                $query = $this->getActivitiesQuery($campaign, $filters);
                if (!empty($selectedMemberIds)) {
                    $query->whereIn('ad_campaign_activities.member_id', $selectedMemberIds);
                }

                $query->chunk(200, function ($activities) use ($handle) {
                    foreach ($activities as $act) {
                        $member = $act->member;
                        $reward = $act->reward;
                        $isRewarded = $reward && $reward->status === AdReward::STATUS_CREDITED;

                        fputcsv($handle, [
                            $act->id,
                            $act->qualifying_event_id ?: ('ACT-' . $act->id),
                            $member ? $member->id : 'Guest',
                            $member ? $member->name : 'Platform Visitor',
                            $member ? $member->user_id : '',
                            $act->action,
                            $isRewarded ? 'Rewarded' : ($act->action === 'rewarded' ? 'Attempted' : 'Not Rewarded'),
                            $isRewarded ? number_format((float) $reward->reward_amount_usd, 4, '.', '') : '0.0000',
                            $isRewarded ? ($reward->tier_label ?: 'Standard') : '',
                            $isRewarded ? (int) $reward->direct_verified_referral_count : 0,
                            $member ? ($member->phone ?: 'Not provided') : '',
                            $member ? ($member->email ?: 'Not provided') : '',
                            $member ? ($member->city ?: '') : '',
                            $member ? ($member->country ?: '') : '',
                            $act->created_at ? $act->created_at->toDateTimeString() : '',
                        ]);
                    }
                });
            } else {
                // Deduplicated Audience/Member-level export (Default)
                fputcsv($handle, [
                    'Member ID',
                    'Display Name',
                    'Username',
                    'Mobile / WhatsApp',
                    'Email',
                    'Location',
                    'Mobile Verified',
                    'Total Campaign Activities',
                    'Interested Count',
                    'Click Count',
                    'Landing Visit Count',
                    'Reward Status',
                    'Historical Reward (USD)',
                    'Referral Tier Snapshot',
                    'Direct Referrals at Reward',
                    'First Engaged At',
                    'Latest Activity At',
                ]);

                $rewardQuery = AdReward::where('ad_campaign_id', $campaign->id)
                    ->where('status', AdReward::STATUS_CREDITED)
                    ->with('member');

                if (!empty($selectedMemberIds)) {
                    $rewardQuery->whereIn('member_id', $selectedMemberIds);
                }

                if (!empty($filters['search']) || !empty($filters['q'])) {
                    $search = trim((string) ($filters['search'] ?? $filters['q']));
                    $rewardQuery->where(function ($sq) use ($search) {
                        $sq->whereHas('member', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%")
                                ->orWhere('user_id', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                        if (is_numeric($search)) {
                            $sq->orWhere('ad_rewards.member_id', (int) $search);
                        }
                    });
                }

                $verification = $filters['verification'] ?? (!empty($filters['verified_only']) ? 'verified' : 'all');
                if ($verification === 'verified') {
                    $rewardQuery->whereHas('member', fn ($mq) => $mq->whereNotNull('mobile_verified_at'));
                } elseif ($verification === 'unverified') {
                    $rewardQuery->whereHas('member', fn ($mq) => $mq->whereNull('mobile_verified_at'));
                }

                $datePreset = $filters['date_preset'] ?? 'all';
                if ($datePreset === 'today') {
                    $rewardQuery->where('ad_rewards.created_at', '>=', now()->startOfDay());
                } elseif ($datePreset === 'yesterday') {
                    $rewardQuery->whereBetween('ad_rewards.created_at', [
                        now()->subDay()->startOfDay(),
                        now()->subDay()->endOfDay(),
                    ]);
                } elseif ($datePreset === 'last_7_days') {
                    $rewardQuery->where('ad_rewards.created_at', '>=', now()->subDays(7)->startOfDay());
                } elseif ($datePreset === 'last_30_days') {
                    $rewardQuery->where('ad_rewards.created_at', '>=', now()->subDays(30)->startOfDay());
                } elseif ($datePreset === 'custom' || (!empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['date_from']) || !empty($filters['date_to']))) {
                    $startDate = $filters['start_date'] ?? $filters['date_from'] ?? null;
                    $endDate = $filters['end_date'] ?? $filters['date_to'] ?? null;
                    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
                        $temp = $startDate;
                        $startDate = $endDate;
                        $endDate = $temp;
                    }
                    if ($startDate) {
                        $rewardQuery->where('ad_rewards.created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
                    }
                    if ($endDate) {
                        $rewardQuery->where('ad_rewards.created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
                    }
                }

                $rewardQuery->chunk(100, function ($rewards) use ($handle, $campaign) {
                    $memberIds = $rewards->pluck('member_id')->filter()->unique()->toArray();
                    $memberActivities = AdCampaignActivity::where('ad_campaign_id', $campaign->id)
                        ->whereIn('member_id', $memberIds)
                        ->get()
                        ->groupBy('member_id');

                    foreach ($rewards as $reward) {
                        $member = $reward->member;
                        if (!$member) {
                            continue;
                        }

                        $acts = $memberActivities->get($member->id, collect());
                        $interestedCount = $acts->where('action', AdCampaignActivity::ACTION_INTERESTED)->count();
                        $clickCount = $acts->where('action', AdCampaignActivity::ACTION_CLICKED)->count();
                        $landingVisitCount = $acts->where('action', AdCampaignActivity::ACTION_VISITED_LANDING_PAGE)->count();
                        $firstActivity = $acts->sortBy('created_at')->first();
                        $latestActivity = $acts->sortByDesc('created_at')->first();

                        $location = array_filter([$member->city, $member->country]);

                        fputcsv($handle, [
                            $member->id,
                            $member->name,
                            $member->user_id,
                            $member->phone ?: 'Not provided',
                            $member->email ?: 'Not provided',
                            !empty($location) ? implode(', ', $location) : 'N/A',
                            $member->mobile_verified_at ? 'Yes' : 'No',
                            $acts->count() ?: 1,
                            $interestedCount,
                            $clickCount,
                            $landingVisitCount ?: 1,
                            'Rewarded',
                            number_format((float) $reward->reward_amount_usd, 4, '.', ''),
                            $reward->tier_label ?: 'Standard Slab',
                            (int) $reward->direct_verified_referral_count,
                            $firstActivity?->created_at ? $firstActivity->created_at->toDateTimeString() : $reward->created_at?->toDateTimeString(),
                            $latestActivity?->created_at ? $latestActivity->created_at->toDateTimeString() : $reward->created_at?->toDateTimeString(),
                        ]);
                    }
                });
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Validate selected member IDs against the campaign audience and compute contactability breakdown.
     */
    public function validateAndPrepareAudienceContact(
        AdCampaign $campaign,
        array $memberIds,
        string $method = 'whatsapp',
        ?string $message = null
    ): array {
        $uniqueMemberIds = array_values(array_unique(array_filter($memberIds, 'is_numeric')));

        // Find which members legitimately belong to this campaign audience (qualified & rewarded)
        $validMembers = Member::whereIn('id', $uniqueMemberIds)
            ->whereHas('adRewards', fn ($q) => $q->where('ad_campaign_id', $campaign->id)->where('status', AdReward::STATUS_CREDITED))
            ->get();

        $validIds = $validMembers->pluck('id')->toArray();
        $unauthorizedIds = array_values(array_diff($uniqueMemberIds, $validIds));

        $contactableMembers = [];
        $unavailableMembers = [];

        foreach ($validMembers as $member) {
            $hasPhone = !empty($member->phone);
            $hasEmail = !empty($member->email);
            $isContactable = $method === 'email' ? $hasEmail : $hasPhone;

            $entry = [
                'id' => $member->id,
                'name' => $member->name,
                'username' => $member->user_id,
                'phone' => $member->phone,
                'email' => $member->email,
                'is_verified' => method_exists($member, 'isMobileVerified') && $member->isMobileVerified(),
                'is_contactable' => $isContactable,
            ];

            if ($isContactable) {
                $contactableMembers[] = $entry;
            } else {
                $unavailableMembers[] = $entry;
            }
        }

        return [
            'total_requested' => count($uniqueMemberIds),
            'total_valid_in_campaign' => count($validIds),
            'contactable_count' => count($contactableMembers),
            'unavailable_count' => count($unavailableMembers),
            'unauthorized_count' => count($unauthorizedIds),
            'method' => $method,
            'contactable_members' => $contactableMembers,
            'unavailable_members' => $unavailableMembers,
            'unauthorized_ids' => $unauthorizedIds,
        ];
    }
}
