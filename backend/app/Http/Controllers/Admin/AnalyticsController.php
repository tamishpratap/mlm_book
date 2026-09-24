<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\CommunityReport;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\PostShare;
use App\Models\Product;
use App\Models\ReportedPost;
use App\Models\ReportedProduct;
use App\Models\SavedProduct;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display centralized Analytics & Business Intelligence Dashboard.
     */
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'period'    => 'nullable|string',
        ]);

        $period = $request->input('period');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Priority: Explicit custom date range takes precedence over period
        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $period = 'custom';
        } elseif (!empty($period)) {
            if ($period === 'today') {
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'yesterday') {
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
            } elseif ($period === '7days' || $period === '7d') {
                $startDate = now()->subDays(6)->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'year') {
                $startDate = now()->subYear()->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'month' || $period === 'this_month') {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
            } else {
                $startDate = now()->subDays(29)->startOfDay();
                $endDate = now()->endOfDay();
                $period = '30days';
            }
        } else {
            $period = '30days';
            $startDate = now()->subDays(29)->startOfDay();
            $endDate = now()->endOfDay();
        }

        // 1. Executive Key Metrics
        $totalMembers = Member::count();
        $newMembersCount = Member::whereBetween('created_at', [$startDate, $endDate])->count();
        $verifiedMembersCount = Member::whereNotNull('mobile_verified_at')->count();

        $totalPosts = Post::count();
        $periodPostsCount = Post::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalLikes = PostLike::count();
        $totalComments = PostComment::count();
        $totalShares = PostShare::count();

        $totalStories = Story::count();
        $activeStoriesCount = Story::where('expires_at', '>', now())->count();
        $totalStoryViews = StoryView::count();

        $totalCommunities = Community::count();
        $publicCommunitiesCount = Community::where('visibility', 'public')->count();

        $totalBusinessPages = BusinessPage::count();
        $verifiedBusinessPagesCount = BusinessPage::where('is_verified', true)->count();

        $totalProducts = Product::count();
        $activeProductsCount = Product::where('status', 'available')->count();
        $savedProductsCount = SavedProduct::count();

        $totalEvents = Event::count();
        $upcomingEventsCount = Event::where('start_date', '>', now()->format('Y-m-d'))->count();
        $totalEventResponses = EventResponse::count();

        $totalReports = ReportedPost::count() + CommunityReport::count() + ReportedProduct::count();
        $pendingReports = ReportedPost::where('status', 'pending')->count() + CommunityReport::where('status', 'pending')->count();
        $totalNotifications = DB::table('notifications')->count();

        // 2. Demographics & Distributions
        $genderDistribution = Member::select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        // 3. Top Performers & Data Tables
        $topCreators = Member::withCount('posts')
            ->orderByDesc('posts_count')
            ->take(5)
            ->get();

        $topCommunities = Community::withCount('members')
            ->orderByDesc('members_count')
            ->take(5)
            ->get();

        $topProducts = Product::with(['member', 'category'])
            ->withCount('savedProducts')
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        // 4. Registration Daily Trend Data for Chart (Continuous Day-Wise Timeline)
        $dailyMap = [];
        $cursor = $startDate->copy()->startOfDay();
        $endCursor = $endDate->copy()->startOfDay();

        while ($cursor->lte($endCursor)) {
            $dailyMap[$cursor->format('Y-m-d')] = 0;
            $cursor->addDay();
        }

        $registrationTrends = Member::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($registrationTrends as $trend) {
            $d = is_string($trend->date) ? $trend->date : Carbon::parse($trend->date)->format('Y-m-d');
            if (array_key_exists($d, $dailyMap)) {
                $dailyMap[$d] = (int) $trend->count;
            }
        }

        $chartDates = array_keys($dailyMap);
        $chartCounts = array_values($dailyMap);
        $newMembersCount = array_sum($chartCounts);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'period' => $period,
                'dateFrom' => $startDate->format('Y-m-d'),
                'dateTo' => $endDate->format('Y-m-d'),
                'totalMembers' => $totalMembers,
                'newMembersCount' => $newMembersCount,
                'verifiedMembersCount' => $verifiedMembersCount,
                'totalPosts' => $totalPosts,
                'periodPostsCount' => $periodPostsCount,
                'totalLikes' => $totalLikes,
                'totalComments' => $totalComments,
                'totalShares' => $totalShares,
                'totalStories' => $totalStories,
                'activeStoriesCount' => $activeStoriesCount,
                'totalStoryViews' => $totalStoryViews,
                'totalCommunities' => $totalCommunities,
                'publicCommunitiesCount' => $publicCommunitiesCount,
                'totalBusinessPages' => $totalBusinessPages,
                'verifiedBusinessPagesCount' => $verifiedBusinessPagesCount,
                'totalProducts' => $totalProducts,
                'activeProductsCount' => $activeProductsCount,
                'savedProductsCount' => $savedProductsCount,
                'totalEvents' => $totalEvents,
                'upcomingEventsCount' => $upcomingEventsCount,
                'totalEventResponses' => $totalEventResponses,
                'totalReports' => $totalReports,
                'pendingReports' => $pendingReports,
                'totalNotifications' => $totalNotifications,
                'genderDistribution' => $genderDistribution,
                'topCreators' => $topCreators,
                'topCommunities' => $topCommunities,
                'topProducts' => $topProducts,
                'chartDates' => $chartDates,
                'chartCounts' => $chartCounts,
            ]);
        }

        return view('admin.analytics.index', compact(
            'period',
            'dateFrom',
            'dateTo',
            'totalMembers',
            'newMembersCount',
            'verifiedMembersCount',
            'totalPosts',
            'periodPostsCount',
            'totalLikes',
            'totalComments',
            'totalShares',
            'totalStories',
            'activeStoriesCount',
            'totalStoryViews',
            'totalCommunities',
            'publicCommunitiesCount',
            'totalBusinessPages',
            'verifiedBusinessPagesCount',
            'totalProducts',
            'activeProductsCount',
            'savedProductsCount',
            'totalEvents',
            'upcomingEventsCount',
            'totalEventResponses',
            'totalReports',
            'pendingReports',
            'totalNotifications',
            'genderDistribution',
            'topCreators',
            'topCommunities',
            'topProducts',
            'chartDates',
            'chartCounts'
        ));
    }

    /**
     * Export Analytics BI Metrics to CSV stream.
     */
    public function export(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'period'    => 'nullable|string',
            'type'      => 'nullable|string|in:all,bi,metrics,summary,general',
        ]);

        $period = $request->input('period');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $type = $request->input('type', 'general');

        $isAllDates = ($period === 'all' && empty($dateFrom));
        $startDate = null;
        $endDate = null;

        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $dateRangeLabel = $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d');
        } elseif (!empty($period) && $period !== 'all') {
            if ($period === 'today') {
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'yesterday') {
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
            } elseif ($period === '7days' || $period === '7d') {
                $startDate = now()->subDays(6)->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'year') {
                $startDate = now()->subYear()->startOfDay();
                $endDate = now()->endOfDay();
            } elseif ($period === 'month' || $period === 'this_month') {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
            } else {
                $startDate = now()->subDays(29)->startOfDay();
                $endDate = now()->endOfDay();
            }
            $dateRangeLabel = $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d');
        } else {
            $isAllDates = true;
            $dateRangeLabel = 'All Dates';
        }

        $prefix = ($type === 'bi' || $type === 'metrics') ? 'analytics-bi-metrics' : 'analytics-export';
        if ($isAllDates) {
            $filename = "{$prefix}-" . now()->format('Y-m-d') . '.csv';
        } else {
            $filename = "{$prefix}-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.csv";
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Access-Control-Expose-Headers' => 'Content-Disposition',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($startDate, $endDate, $isAllDates, $dateRangeLabel, $type) {
            $file = fopen('php://output', 'w');

            // UTF-8 Byte Order Mark (BOM) for complete Excel / spreadsheet compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Helper to scope queries by date if not All Dates
            $applyFilter = function ($query) use ($startDate, $endDate, $isAllDates) {
                if (!$isAllDates && $startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
                return $query;
            };

            $nowStr = now()->toDateTimeString();

            if ($type === 'bi' || $type === 'metrics') {
                // Section 1: Executive KPI Metrics
                fputcsv($file, ['=== SECTION 1: EXECUTIVE KPI SUMMARY ===', '', '', '', '', '']);
                fputcsv($file, ['Category', 'Metric Name', 'Period Count', 'Lifetime Total', 'Date Filter', 'Generated At']);

                $totalMembers = Member::count();
                $periodMembers = $isAllDates ? $totalMembers : Member::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Users', 'Total Registered Members', $periodMembers, $totalMembers, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Users', 'Verified Mobile Accounts', Member::whereNotNull('mobile_verified_at')->count(), Member::whereNotNull('mobile_verified_at')->count(), $dateRangeLabel, $nowStr]);

                $totalPosts = Post::count();
                $periodPosts = $isAllDates ? $totalPosts : Post::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Content', 'Total Posts Published', $periodPosts, $totalPosts, $dateRangeLabel, $nowStr]);

                $totalLikes = PostLike::count();
                $periodLikes = $isAllDates ? $totalLikes : PostLike::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Engagement', 'Post Likes', $periodLikes, $totalLikes, $dateRangeLabel, $nowStr]);

                $totalComments = PostComment::count();
                $periodComments = $isAllDates ? $totalComments : PostComment::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Engagement', 'Post Comments', $periodComments, $totalComments, $dateRangeLabel, $nowStr]);

                $totalShares = PostShare::count();
                $periodShares = $isAllDates ? $totalShares : PostShare::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Engagement', 'Post Shares', $periodShares, $totalShares, $dateRangeLabel, $nowStr]);

                $totalStories = Story::count();
                $periodStories = $isAllDates ? $totalStories : Story::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Content', 'Stories Published', $periodStories, $totalStories, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Content', 'Total Story Views', StoryView::count(), StoryView::count(), $dateRangeLabel, $nowStr]);

                $totalCommunities = Community::count();
                $periodCommunities = $isAllDates ? $totalCommunities : Community::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Directory', 'Total Communities', $periodCommunities, $totalCommunities, $dateRangeLabel, $nowStr]);

                $totalBiz = BusinessPage::count();
                $periodBiz = $isAllDates ? $totalBiz : BusinessPage::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Directory', 'Total Business Pages', $periodBiz, $totalBiz, $dateRangeLabel, $nowStr]);

                $totalProducts = Product::count();
                $periodProducts = $isAllDates ? $totalProducts : Product::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Commerce', 'Marketplace Products', $periodProducts, $totalProducts, $dateRangeLabel, $nowStr]);

                $totalEvents = Event::count();
                $periodEvents = $isAllDates ? $totalEvents : Event::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Events', 'Total Platform Events', $periodEvents, $totalEvents, $dateRangeLabel, $nowStr]);

                $totalReports = ReportedPost::count() + CommunityReport::count() + ReportedProduct::count();
                fputcsv($file, ['Moderation', 'Platform Reports Filed', $totalReports, $totalReports, $dateRangeLabel, $nowStr]);

                // Section 2: Gender Demographics
                fputcsv($file, []);
                fputcsv($file, ['=== SECTION 2: MEMBER DEMOGRAPHICS ===', '', '', '', '', '']);
                fputcsv($file, ['Demographic Attribute', 'Segment', 'Member Count', 'Share (%)', 'Date Filter', 'Generated At']);
                $genderDist = Member::select('gender', DB::raw('count(*) as count'))
                    ->groupBy('gender')
                    ->pluck('count', 'gender')
                    ->toArray();
                foreach ($genderDist as $gender => $count) {
                    $share = $totalMembers > 0 ? round(($count / $totalMembers) * 100, 1) : 0;
                    $label = !empty($gender) ? ucfirst($gender) : 'Unspecified';
                    fputcsv($file, ['Gender', $label, $count, "{$share}%", $dateRangeLabel, $nowStr]);
                }

                // Section 3: Top Active Content Creators
                fputcsv($file, []);
                fputcsv($file, ['=== SECTION 3: TOP CONTENT CREATORS ===', '', '', '', '', '']);
                fputcsv($file, ['Rank', 'Creator Name', 'User ID', 'Posts Published', 'Date Filter', 'Generated At']);
                $topCreators = Member::withCount('posts')->orderByDesc('posts_count')->take(10)->get();
                $rank = 1;
                foreach ($topCreators as $creator) {
                    fputcsv($file, [$rank++, $creator->name, $creator->user_id, $creator->posts_count, $dateRangeLabel, $nowStr]);
                }

                // Section 4: Top Communities
                fputcsv($file, []);
                fputcsv($file, ['=== SECTION 4: TOP COMMUNITIES BY MEMBERSHIP ===', '', '', '', '', '']);
                fputcsv($file, ['Rank', 'Community Name', 'Category', 'Members Count', 'Date Filter', 'Generated At']);
                $topCommunities = Community::withCount('members')->orderByDesc('members_count')->take(10)->get();
                $cRank = 1;
                foreach ($topCommunities as $comm) {
                    fputcsv($file, [$cRank++, $comm->name, $comm->category ?: 'General', $comm->members_count, $dateRangeLabel, $nowStr]);
                }

                // Section 5: Top Marketplace Products
                fputcsv($file, []);
                fputcsv($file, ['=== SECTION 5: TOP VIEWED MARKETPLACE PRODUCTS ===', '', '', '', '', '']);
                fputcsv($file, ['Rank', 'Product Title', 'Price ($)', 'Views Count', 'Saved in Wishlist', 'Generated At']);
                $topProducts = Product::withCount('savedProducts')->orderByDesc('views_count')->take(10)->get();
                $pRank = 1;
                foreach ($topProducts as $p) {
                    fputcsv($file, [$pRank++, $p->title, number_format((float) $p->price, 2), $p->views_count ?? 0, $p->saved_products_count ?? 0, $nowStr]);
                }
            } else {
                // General Analytics Summary CSV
                fputcsv($file, ['Metric Category', 'Metric Name', 'Filtered Period Count', 'Lifetime Total', 'Date Filter', 'Generated At']);

                $totalMembers = Member::count();
                $periodMembers = $isAllDates ? $totalMembers : Member::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Users', 'Total Registered Members', $periodMembers, $totalMembers, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Users', 'Verified Mobile Accounts', Member::whereNotNull('mobile_verified_at')->count(), Member::whereNotNull('mobile_verified_at')->count(), $dateRangeLabel, $nowStr]);

                $totalPosts = Post::count();
                $periodPosts = $isAllDates ? $totalPosts : Post::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Content', 'Total Posts Published', $periodPosts, $totalPosts, $dateRangeLabel, $nowStr]);

                $totalLikes = PostLike::count();
                $periodLikes = $isAllDates ? $totalLikes : PostLike::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Engagement', 'Total Post Likes', $periodLikes, $totalLikes, $dateRangeLabel, $nowStr]);

                $totalComments = PostComment::count();
                $periodComments = $isAllDates ? $totalComments : PostComment::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Engagement', 'Total Post Comments', $periodComments, $totalComments, $dateRangeLabel, $nowStr]);

                $totalShares = PostShare::count();
                $periodShares = $isAllDates ? $totalShares : PostShare::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Engagement', 'Total Post Shares', $periodShares, $totalShares, $dateRangeLabel, $nowStr]);

                $totalStories = Story::count();
                $periodStories = $isAllDates ? $totalStories : Story::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Content', 'Total Stories Created', $periodStories, $totalStories, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Content', 'Active Stories', Story::where('expires_at', '>', now())->count(), Story::where('expires_at', '>', now())->count(), $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Content', 'Total Story Views', StoryView::count(), StoryView::count(), $dateRangeLabel, $nowStr]);

                $totalCommunities = Community::count();
                $periodCommunities = $isAllDates ? $totalCommunities : Community::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Directory', 'Total Communities Created', $periodCommunities, $totalCommunities, $dateRangeLabel, $nowStr]);

                $totalBiz = BusinessPage::count();
                $periodBiz = $isAllDates ? $totalBiz : BusinessPage::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Directory', 'Total Business Pages Registered', $periodBiz, $totalBiz, $dateRangeLabel, $nowStr]);

                $totalProducts = Product::count();
                $periodProducts = $isAllDates ? $totalProducts : Product::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Commerce', 'Total Marketplace Products', $periodProducts, $totalProducts, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Commerce', 'Active Available Listings', Product::where('status', 'available')->count(), Product::where('status', 'available')->count(), $dateRangeLabel, $nowStr]);

                $totalEvents = Event::count();
                $periodEvents = $isAllDates ? $totalEvents : Event::whereBetween('created_at', [$startDate, $endDate])->count();
                fputcsv($file, ['Events', 'Total Platform Events Scheduled', $periodEvents, $totalEvents, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Events', 'Total Event RSVPs / Responses', EventResponse::count(), EventResponse::count(), $dateRangeLabel, $nowStr]);

                $totalReports = ReportedPost::count() + CommunityReport::count() + ReportedProduct::count();
                $pendingReports = ReportedPost::where('status', 'pending')->count() + CommunityReport::where('status', 'pending')->count();
                fputcsv($file, ['Moderation', 'Total Reports Filed', $totalReports, $totalReports, $dateRangeLabel, $nowStr]);
                fputcsv($file, ['Moderation', 'Pending Moderation Queue', $pendingReports, $pendingReports, $dateRangeLabel, $nowStr]);

                $totalNotifications = DB::table('notifications')->count();
                fputcsv($file, ['System', 'Platform Notifications Delivered', $totalNotifications, $totalNotifications, $dateRangeLabel, $nowStr]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
