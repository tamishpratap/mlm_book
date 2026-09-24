<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\ImportFund;
use App\Models\Member;
use App\Models\Post;
use App\Models\Product;
use App\Models\ReportedPost;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the comprehensive Admin Overview Dashboard.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        // ---------------------------------------------------------------------
        // SECTION 1: TOP KPI METRICS (Real Data)
        // ---------------------------------------------------------------------
        $totalMembers = Member::count();
        $newMembersToday = Member::whereDate('created_at', $today)->count();
        $newMembersThisWeek = Member::where('created_at', '>=', $startOfWeek)->count();
        $newMembersThisMonth = Member::where('created_at', '>=', $startOfMonth)->count();
        $activeMembers = Member::where('last_seen_at', '>=', Carbon::now()->subDays(7))->count();
        $inactiveMembers = Member::where(function ($q) {
            $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', Carbon::now()->subDays(7));
        })->count();
        $pendingConnections = Friendship::where('status', 'pending')->count();
        $totalConnections = Friendship::where('status', 'accepted')->count();

        // ---------------------------------------------------------------------
        // SECTION 2: CONTENT OVERVIEW (Real Data)
        // ---------------------------------------------------------------------
        $totalPosts = Post::count();
        $postsToday = Post::whereDate('created_at', $today)->count();
        $postsThisWeek = Post::where('created_at', '>=', $startOfWeek)->count();
        $activeStories = Story::where('expires_at', '>', Carbon::now())->count();
        $storiesToday = Story::whereDate('created_at', $today)->count();
        $mediaPosts = Post::whereNotNull('media_type')->where('media_type', '!=', '')->count();

        // ---------------------------------------------------------------------
        // SECTION 3: BUSINESS PAGES (Real Data)
        // ---------------------------------------------------------------------
        $totalBusinessPages = BusinessPage::count();
        $activeBusinessPages = BusinessPage::where('status', 'active')->count();
        $pendingBusinessPages = BusinessPage::where('is_verified', false)->count();
        $verifiedBusinessPages = BusinessPage::where('is_verified', true)->count();
        $publicBusinessPages = BusinessPage::where('visibility', 'public')->count();

        // ---------------------------------------------------------------------
        // SECTION 4: COMMUNITIES (Real Data)
        // ---------------------------------------------------------------------
        $totalCommunities = Community::count();
        $activeCommunities = Community::where('status', 'active')->count();
        $totalCommunityMembers = DB::table('community_members')->where('status', 'accepted')->count();
        $pendingCommunityRequests = DB::table('community_members')->where('status', 'pending')->count();

        // ---------------------------------------------------------------------
        // SECTION 5: MARKETPLACE (Real Data)
        // ---------------------------------------------------------------------
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'available')->count();
        $pendingProducts = Product::where('status', 'pending')->count();
        $closedProducts = Product::whereIn('status', ['sold', 'closed', 'inactive'])->count();

        // ---------------------------------------------------------------------
        // SECTION 6: EVENTS (Real Data)
        // ---------------------------------------------------------------------
        $totalEvents = Event::count();
        $upcomingEventsCount = Event::where('start_date', '>=', $today->toDateString())->count();
        $pastEventsCount = Event::where('start_date', '<', $today->toDateString())->count();
        $eventsThisMonthCount = Event::whereMonth('start_date', Carbon::now()->month)->whereYear('start_date', Carbon::now()->year)->count();

        // ---------------------------------------------------------------------
        // SECTION 7 & 8: MODERATION & NOTIFICATIONS (Real Data)
        // ---------------------------------------------------------------------
        $pendingReports = ReportedPost::where('status', 'pending')->count();
        $totalReports = ReportedPost::count();
        $blockedMembers = DB::table('blocked_users')->count();
        $unreadNotifications = DB::table('notifications')->whereNull('read_at')->count();
        $totalNotifications = DB::table('notifications')->count();

        // ---------------------------------------------------------------------
        // SECTION 8B: CRYPTO DEPOSITS & MANUAL REQUESTS (Real Data)
        // ---------------------------------------------------------------------
        $pendingDepositsCount = ImportFund::whereIn('deposit_status', ['pending', 'verified'])
            ->orWhere(function ($q) {
                $q->whereNull('deposit_status')->where('status', 'Pending');
            })->count();
        $approvedDepositsCount = ImportFund::where('deposit_status', 'approved')
            ->orWhere(function ($q) {
                $q->whereNull('deposit_status')->where('status', 'Approved');
            })->count();
        $totalDepositedAmount = ImportFund::where(function ($q) {
            $q->where('deposit_status', 'approved')
              ->orWhere(function ($sq) {
                  $sq->whereNull('deposit_status')->where('status', 'Approved');
              });
        })->sum('amount');
        $recentDeposits = ImportFund::with('member')->latest()->limit(5)->get();

        // ---------------------------------------------------------------------
        // SECTION 9: PLATFORM ANALYTICS CHART DATA (Real Data Trends)
        // ---------------------------------------------------------------------
        $chartDays = 14;
        $trendDates = [];
        $memberRegTrend = [];
        $postsTrend = [];
        $storiesTrend = [];

        // Fetch daily counts via single aggregated queries
        $dailyMembers = Member::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays($chartDays))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailyPosts = Post::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays($chartDays))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailyStories = Story::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays($chartDays))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        for ($i = $chartDays - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i);
            $dateStr = $d->toDateString();
            $label = $d->format('M d');

            $trendDates[] = $label;
            $memberRegTrend[] = $dailyMembers[$dateStr] ?? 0;
            $postsTrend[] = $dailyPosts[$dateStr] ?? 0;
            $storiesTrend[] = $dailyStories[$dateStr] ?? 0;
        }

        // ---------------------------------------------------------------------
        // SECTION 10: BUSINESS CATEGORY ANALYTICS (Real Data)
        // ---------------------------------------------------------------------
        $businessCategories = BusinessPage::select('category', DB::raw('count(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('count')
            ->take(6)
            ->get();

        // ---------------------------------------------------------------------
        // SECTIONS 11–14: RECENT PLATFORM RECORDS (Real Data)
        // ---------------------------------------------------------------------
        $recentMembers = Member::select('id', 'name', 'user_id', 'profile_photo', 'city', 'country', 'created_at', 'mobile_verified_at', 'last_seen_at')
            ->latest('created_at')
            ->take(5)
            ->get();

        $recentBusinessPages = BusinessPage::select('id', 'page_name', 'category', 'is_verified', 'status', 'created_at', 'logo')
            ->latest('created_at')
            ->take(5)
            ->get();

        $recentPosts = Post::with('member:id,name,user_id,profile_photo')
            ->latest('created_at')
            ->take(5)
            ->get();

        $upcomingEvents = Event::select('id', 'title', 'start_date', 'start_time', 'location_address', 'location_city', 'event_type', 'organizer_id')
            ->where('start_date', '>=', $today->toDateString())
            ->orderBy('start_date')
            ->take(5)
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'totalMembers' => $totalMembers,
                'newMembersToday' => $newMembersToday,
                'newMembersThisWeek' => $newMembersThisWeek,
                'newMembersThisMonth' => $newMembersThisMonth,
                'activeMembers' => $activeMembers,
                'inactiveMembers' => $inactiveMembers,
                'pendingConnections' => $pendingConnections,
                'totalConnections' => $totalConnections,
                'totalPosts' => $totalPosts,
                'postsToday' => $postsToday,
                'postsThisWeek' => $postsThisWeek,
                'activeStories' => $activeStories,
                'storiesToday' => $storiesToday,
                'mediaPosts' => $mediaPosts,
                'totalBusinessPages' => $totalBusinessPages,
                'activeBusinessPages' => $activeBusinessPages,
                'pendingBusinessPages' => $pendingBusinessPages,
                'verifiedBusinessPages' => $verifiedBusinessPages,
                'publicBusinessPages' => $publicBusinessPages,
                'totalCommunities' => $totalCommunities,
                'activeCommunities' => $activeCommunities,
                'totalCommunityMembers' => $totalCommunityMembers,
                'pendingCommunityRequests' => $pendingCommunityRequests,
                'totalProducts' => $totalProducts,
                'activeProducts' => $activeProducts,
                'pendingProducts' => $pendingProducts,
                'closedProducts' => $closedProducts,
                'totalEvents' => $totalEvents,
                'upcomingEventsCount' => $upcomingEventsCount,
                'pastEventsCount' => $pastEventsCount,
                'eventsThisMonthCount' => $eventsThisMonthCount,
                'pendingReports' => $pendingReports,
                'totalReports' => $totalReports,
                'blockedMembers' => $blockedMembers,
                'unreadNotifications' => $unreadNotifications,
                'totalNotifications' => $totalNotifications,
                'pendingDepositsCount' => $pendingDepositsCount,
                'approvedDepositsCount' => $approvedDepositsCount,
                'totalDepositedAmount' => $totalDepositedAmount,
                'recentDeposits' => $recentDeposits,
                'trendDates' => $trendDates,
                'memberRegTrend' => $memberRegTrend,
                'postsTrend' => $postsTrend,
                'storiesTrend' => $storiesTrend,
                'businessCategories' => $businessCategories,
                'recentMembers' => $recentMembers,
                'recentBusinessPages' => $recentBusinessPages,
                'recentPosts' => $recentPosts,
                'upcomingEvents' => $upcomingEvents,
            ]);
        }

        return view('admin.dashboard', compact(
            // Section 1
            'totalMembers',
            'newMembersToday',
            'newMembersThisWeek',
            'newMembersThisMonth',
            'activeMembers',
            'inactiveMembers',
            'pendingConnections',
            'totalConnections',
            // Section 2
            'totalPosts',
            'postsToday',
            'postsThisWeek',
            'activeStories',
            'storiesToday',
            'mediaPosts',
            // Section 3
            'totalBusinessPages',
            'activeBusinessPages',
            'pendingBusinessPages',
            'verifiedBusinessPages',
            'publicBusinessPages',
            // Section 4
            'totalCommunities',
            'activeCommunities',
            'totalCommunityMembers',
            'pendingCommunityRequests',
            // Section 5
            'totalProducts',
            'activeProducts',
            'pendingProducts',
            'closedProducts',
            // Section 6
            'totalEvents',
            'upcomingEventsCount',
            'pastEventsCount',
            'eventsThisMonthCount',
            // Section 7 & 8
            'pendingReports',
            'totalReports',
            'blockedMembers',
            'unreadNotifications',
            'totalNotifications',
            // Section 8b
            'pendingDepositsCount',
            'approvedDepositsCount',
            'totalDepositedAmount',
            'recentDeposits',
            // Section 9
            'trendDates',
            'memberRegTrend',
            'postsTrend',
            'storiesTrend',
            // Section 10
            'businessCategories',
            // Sections 11–14
            'recentMembers',
            'recentBusinessPages',
            'recentPosts',
            'upcomingEvents'
        ));
    }
}
