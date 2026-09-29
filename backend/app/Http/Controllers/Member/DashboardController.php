<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdDeposit;
use App\Models\AdImpression;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var Member|null $currentMember */
        $currentMember = auth('member')->user();

        if (!$currentMember instanceof Member) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /** @var Member $member */
        $member = $currentMember->fresh() ?? $currentMember;

        if ($request->expectsJson() || $request->is('api/*')) {
            // -------------------------------------------------------------
            // 1. Wallets & Financial Metrics (Kitna fund hai, kitna baaki hai, kitna use hua)
            // -------------------------------------------------------------
            $fundWalletBalance = round((float) ($member->fresh()->p2p_wallet ?? $member->fresh()->ad_balance ?? 0.00), 4);
            $rewardWalletBalance = round((float) ($member->fresh()->wallet ?? 0.00), 4);

            // Total Approved Lifetime Deposits
            $totalDeposited = round((float) AdDeposit::where('member_id', $member->id)
                ->where('status', AdDeposit::STATUS_APPROVED)
                ->sum('net_amount_inr'), 2);

            // Total Debited/Committed for Ad Campaigns
            $totalAdBudgetFunded = round((float) AdCampaign::where('member_id', $member->id)->sum('wallet_debit'), 4);

            // Total Actually Spent in Ad Campaigns (impressions, clicks, payouts)
            $totalAdSpent = round((float) AdCampaign::where('member_id', $member->id)->sum('spent_amount'), 4);

            // Remaining Budget locked in Active/Running/Approved Ad Campaigns
            $activeAdCampaignsBudgetRemaining = round((float) AdCampaign::where('member_id', $member->id)
                ->whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED])
                ->sum('remaining_amount'), 4);

            // Total Withdrawn Funds
            $totalWithdrawn = round((float) WithdrawalRequest::where('member_id', $member->id)
                ->whereIn('status', [WithdrawalRequest::STATUS_APPROVED, WithdrawalRequest::STATUS_VERIFIED])
                ->sum('net_amount'), 2);

            // Pending Withdrawal Requests
            $pendingWithdrawals = round((float) WithdrawalRequest::where('member_id', $member->id)
                ->where('status', WithdrawalRequest::STATUS_PENDING)
                ->sum('gross_amount'), 2);

            // Total Rewards Earned from Ads (credited)
            $totalRewardEarned = round((float) AdReward::where('member_id', $member->id)
                ->where('status', 'credited')
                ->sum('reward_amount_usd'), 4);

            // Fund Utilization Calculation
            $totalOutflows = $totalAdBudgetFunded + $totalWithdrawn;
            $utilizationRate = ($totalDeposited > 0)
                ? round(min(100.0, ($totalOutflows / $totalDeposited) * 100), 1)
                : ($totalOutflows > 0 ? 100.0 : 0.0);

            $wallets = [
                'fund_wallet_balance' => $fundWalletBalance,
                'reward_wallet_balance' => $rewardWalletBalance,
                'total_deposited' => $totalDeposited,
                'total_used_ads' => $totalAdBudgetFunded,
                'total_actual_spent_ads' => $totalAdSpent,
                'active_ad_remaining' => $activeAdCampaignsBudgetRemaining,
                'total_withdrawn' => $totalWithdrawn,
                'pending_withdrawals' => $pendingWithdrawals,
                'total_reward_earned' => $totalRewardEarned,
                'utilization_rate' => $utilizationRate,
                'connected_wallet_address' => $member->wallet_address,
                'reward_wallet_network' => $member->reward_wallet_network ?? 'BEP-20',
                'reward_wallet_currency' => $member->reward_wallet_currency ?? 'USDT',
                'has_verified_reward_wallet' => $member->hasVerifiedRewardWallet(),
            ];

            // -------------------------------------------------------------
            // 2. Business Pages Overview & List
            // -------------------------------------------------------------
            $businessPagesQuery = BusinessPage::where('member_id', $member->id)
                ->withCount(['followers', 'posts', 'reviews'])
                ->latest();

            $businessPages = $businessPagesQuery->get();

            // Count active campaigns per business page
            $pageIds = $businessPages->pluck('id');
            $activeCampaignCountsByPage = AdCampaign::whereIn('business_page_id', $pageIds)
                ->whereIn('status', [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED])
                ->groupBy('business_page_id')
                ->selectRaw('business_page_id, count(*) as count')
                ->pluck('count', 'business_page_id');

            $businessPagesList = $businessPages->map(function (BusinessPage $page) use ($activeCampaignCountsByPage) {
                return [
                    'id' => $page->id,
                    'page_id' => $page->page_id,
                    'page_name' => $page->page_name,
                    'page_username' => $page->page_username,
                    'slug' => $page->slug,
                    'category' => $page->category,
                    'description' => $page->description,
                    'logo_url' => $page->logo_url,
                    'cover_url' => $page->cover_url,
                    'status' => $page->status,
                    'is_verified' => (bool) $page->is_verified,
                    'followers_count' => (int) $page->followers_count,
                    'posts_count' => (int) $page->posts_count,
                    'reviews_count' => (int) $page->reviews_count,
                    'active_campaigns_count' => (int) ($activeCampaignCountsByPage[$page->id] ?? 0),
                    'created_at' => $page->created_at?->toIso8601String(),
                ];
            });

            $businessPagesSummary = [
                'total_pages' => $businessPages->count(),
                'total_followers' => (int) $businessPages->sum('followers_count'),
                'total_posts' => (int) $businessPages->sum('posts_count'),
                'total_reviews' => (int) $businessPages->sum('reviews_count'),
                'pages' => $businessPagesList,
            ];

            // -------------------------------------------------------------
            // 3. Ad Campaigns Performance Metrics & Recent Campaigns
            // -------------------------------------------------------------
            $allCampaigns = AdCampaign::where('member_id', $member->id)->get();
            $campaignIds = $allCampaigns->pluck('id');

            $totalImpressions = $campaignIds->isNotEmpty()
                ? AdImpression::whereIn('ad_campaign_id', $campaignIds)->count()
                : 0;
            $totalClicks = $campaignIds->isNotEmpty()
                ? AdClick::whereIn('ad_campaign_id', $campaignIds)->count()
                : 0;
            $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.00;
            $totalEngagements = $campaignIds->isNotEmpty()
                ? AdReward::whereIn('ad_campaign_id', $campaignIds)->count()
                : 0;

            $recentCampaigns = AdCampaign::where('member_id', $member->id)
                ->with(['promotedPost:id,body,media_path,media_type,created_at'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function (AdCampaign $c) {
                    $impressions = AdImpression::where('ad_campaign_id', $c->id)->count();
                    $clicks = AdClick::where('ad_campaign_id', $c->id)->count();
                    $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.00;

                    return [
                        'id' => $c->id,
                        'campaign_id' => $c->campaign_id,
                        'campaign_name' => $c->campaign_name,
                        'business_page_id' => $c->business_page_id,
                        'status' => $c->status,
                        'approval_status' => $c->approval_status,
                        'budget' => (float) $c->budget,
                        'spent_amount' => (float) $c->spent_amount,
                        'remaining_amount' => (float) $c->remaining_amount,
                        'wallet_debit' => (float) $c->wallet_debit,
                        'fee_amount' => (float) $c->fee_amount,
                        'impressions_count' => $impressions,
                        'clicks_count' => $clicks,
                        'ctr' => $ctr,
                        'promoted_post' => $c->promotedPost ? [
                            'id' => $c->promotedPost->id,
                            'body' => $c->promotedPost->body ? Str::limit($c->promotedPost->body, 90) : '',
                            'media_path' => $c->promotedPost->media_path,
                            'media_type' => $c->promotedPost->media_type,
                            'media_url' => $c->promotedPost->media_url,
                        ] : null,
                        'created_at' => $c->created_at?->toIso8601String(),
                    ];
                });

            $adCampaignsSummary = [
                'metrics' => [
                    'total_campaigns' => $allCampaigns->count(),
                    'active_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_ACTIVE)->count(),
                    'pending_review' => $allCampaigns->where('status', AdCampaign::STATUS_PENDING_REVIEW)->count(),
                    'approved_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_APPROVED)->count(),
                    'paused_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_PAUSED)->count(),
                    'completed_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_COMPLETED)->count(),
                    'total_budget' => round((float) $allCampaigns->sum('budget'), 4),
                    'total_spent' => round((float) $allCampaigns->sum('spent_amount'), 4),
                    'total_remaining' => round((float) $allCampaigns->sum('remaining_amount'), 4),
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'average_ctr' => $avgCtr,
                    'total_engagements' => $totalEngagements,
                ],
                'recent_campaigns' => $recentCampaigns,
            ];

            // -------------------------------------------------------------
            // 4. Referral Network & MLM Rank Status
            // -------------------------------------------------------------
            $directReferralsCount = $member->directReferrals()->count();
            $verifiedDirectReferralsCount = $member->verifiedDirectReferrals()->count();
            $introducer = !empty($member->introducer_id)
                ? Member::where('user_id', $member->introducer_id)->first(['name', 'user_id', 'avatar_url', 'email'])
                : null;

            $frontendBase = config('app.frontend_url') ?: (app()->isLocal() ? 'http://localhost:5173' : url(''));
            $referralUrl = rtrim($frontendBase, '/') . '/member/register?ref=' . urlencode($member->user_id);

            $referralNetwork = [
                'user_id' => $member->user_id,
                'reward_rank' => $member->reward_rank ?? 'Free Member',
                'direct_referrals_count' => $directReferralsCount,
                'verified_direct_referrals_count' => $verifiedDirectReferralsCount,
                'referral_url' => $referralUrl,
                'sponsor' => $introducer ? [
                    'name' => $introducer->name,
                    'user_id' => $introducer->user_id,
                    'avatar_url' => $introducer->avatar_url,
                ] : null,
            ];

            // -------------------------------------------------------------
            // 5. Recent Financial Activity (Deposits & Withdrawals)
            // -------------------------------------------------------------
            $recentDeposits = AdDeposit::where('member_id', $member->id)
                ->latest()
                ->take(5)
                ->get()
                ->map(function (AdDeposit $d) {
                    return [
                        'id' => $d->id,
                        'deposit_id' => $d->deposit_id,
                        'submitted_amount' => (float) ($d->submitted_amount ?: $d->amount_inr),
                        'net_amount' => (float) $d->net_amount_inr,
                        'fee_amount' => (float) $d->fee_amount_inr,
                        'fee_percent' => (float) $d->fee_percent,
                        'currency' => $d->currency_in ?? 'USDT',
                        'network' => $d->network ?? 'BEP-20',
                        'status' => $d->status,
                        'verification_status' => $d->verification_status,
                        'transaction_hash' => $d->transaction_hash,
                        'created_at' => $d->created_at?->toIso8601String(),
                    ];
                });

            $recentWithdrawals = WithdrawalRequest::where('member_id', $member->id)
                ->latest()
                ->take(5)
                ->get()
                ->map(function (WithdrawalRequest $w) {
                    return [
                        'id' => $w->id,
                        'request_id' => $w->request_id,
                        'gross_amount' => (float) $w->gross_amount,
                        'service_charge' => (float) $w->service_charge,
                        'net_amount' => (float) $w->net_amount,
                        'wallet_address' => $w->wallet_address,
                        'status' => $w->status,
                        'created_at' => $w->created_at?->toIso8601String(),
                    ];
                });

            // -------------------------------------------------------------
            // 6. Social Overview
            // -------------------------------------------------------------
            $socialOverview = [
                'posts_count' => $member->posts()->whereNull('business_page_id')->count(),
                'friends_count' => count($member->acceptedFriendIds()),
                'followers_count' => $member->followers()->count(),
                'following_count' => $member->following()->count(),
                'communities_count' => $member->joinedCommunities()->count(),
            ];

            return response()->json([
                'success' => true,
                'member' => $member,
                'wallets' => $wallets,
                'business_pages' => $businessPagesSummary,
                'ad_campaigns' => $adCampaignsSummary,
                'referral_network' => $referralNetwork,
                'recent_transactions' => [
                    'deposits' => $recentDeposits,
                    'withdrawals' => $recentWithdrawals,
                ],
                'social_overview' => $socialOverview,
                'quick_shortcuts' => $this->getQuickShortcuts(),
                'capabilities' => $this->getCapabilities(),
            ]);
        }

        return view('member.dashboard', compact('currentMember'));
    }

    protected function getQuickShortcuts(): array
    {
        return [
            [
                'name' => 'Socials Feed',
                'path' => '/member/socials',
                'icon' => 'rss',
            ],
            [
                'name' => 'My Connections',
                'path' => '/member/friends',
                'icon' => 'users-round',
            ],
            [
                'name' => 'New Connections',
                'path' => '/member/people/suggestions',
                'icon' => 'sparkles',
            ],
            [
                'name' => 'Watch Videos',
                'path' => '/member/watch',
                'icon' => 'monitor-play',
            ],
            [
                'name' => 'Community Groups',
                'path' => '/member/community',
                'icon' => 'users',
            ],
            [
                'name' => 'Business Directory',
                'path' => '/member/business-directory',
                'icon' => 'compass',
            ],
            [
                'name' => 'Upcoming Events',
                'path' => '/member/events',
                'icon' => 'calendar',
            ],
        ];
    }

    protected function getCapabilities(): array
    {
        return [
            [
                'title' => 'Social Features & Smart Feed',
                'description' => 'Share posts, photo & video updates, 24-hour stories, and interact through likes, custom reactions, threaded comments, shares, and saved posts.',
                'path' => '/member/socials',
                'icon' => 'rss',
                'link_text' => 'View Socials',
            ],
            [
                'title' => 'Watch Video Platform',
                'description' => 'Discover video content uploaded by members, watch video posts seamlessly, and explore visual updates from across the network.',
                'path' => '/member/watch',
                'icon' => 'monitor-play',
                'link_text' => 'Watch Videos',
            ],
            [
                'title' => 'Community Features',
                'description' => 'Create or join public and private groups, participate in discussion threads, invite peers via custom invite links, and manage group moderation.',
                'path' => '/member/community',
                'icon' => 'users',
                'link_text' => 'Browse Communities',
            ],
            [
                'title' => 'Business Pages & Directory',
                'description' => 'Establish brand pages, manage customer reviews and inbox messages, track page analytics, and feature your organization in the Business Directory.',
                'path' => '/member/business-directory',
                'icon' => 'building-2',
                'link_text' => 'Business Directory',
            ],
            [
                'title' => 'Events Platform',
                'description' => 'Host upcoming events, manage attendee lists, track RSVPs, and stay updated on networking meetups and community gatherings.',
                'path' => '/member/events',
                'icon' => 'calendar-days',
                'link_text' => 'Discover Events',
            ],
            [
                'title' => 'New Connections & Discovery',
                'description' => 'Discover recommended members based on mutual connections and geographic location, build your network, and manage your account privacy.',
                'path' => '/member/people/suggestions',
                'icon' => 'sparkles',
                'link_text' => 'Find Connections',
            ],
        ];
    }
}
