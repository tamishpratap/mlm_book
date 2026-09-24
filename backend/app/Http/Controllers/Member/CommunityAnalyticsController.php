<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommunityAnalyticsController extends Controller
{
    public function index(Request $request, Community $community)
    {
        $member = auth('member')->user();
        Gate::authorize('viewAnalytics', $community);

        $period = $request->query('period', '7days');

        $startDate = match ($period) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'this_year' => now()->startOfYear(),
            'all' => now()->subYears(10),
            default => now()->subDays(7),
        };

        // Overview Metrics
        $totalMembers = $community->member_count;
        $activeMembers = $community->acceptedMembers()
            ->where('joined_at', '>=', now()->subDays(30))
            ->count();
        $newMembersInPeriod = $community->acceptedMembers()
            ->where('joined_at', '>=', $startDate)
            ->count();

        $totalPosts = Post::where('community_id', $community->id)->count();
        $postsInPeriod = Post::where('community_id', $community->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalAnnouncements = Post::where('community_id', $community->id)
            ->where('is_announcement', true)
            ->count();

        $pendingRequests = $community->pendingMembers()->count();
        $pendingReports = $community->reports()->where('status', 'pending')->count();
        $bannedMembers = $community->bans()->active()->count();

        // Top 5 Contributors (MySQL 8 ONLY_FULL_GROUP_BY compliant)
        $topContributors = Member::query()
            ->whereHas('posts', function ($q) use ($community) {
                $q->where('community_id', $community->id);
            })
            ->withCount(['posts' => function ($q) use ($community) {
                $q->where('community_id', $community->id);
            }])
            ->orderBy('posts_count', 'desc')
            ->take(5)
            ->get();

        // Top 5 Engaged Posts
        $topPosts = Post::query()
            ->with(['member', 'likes', 'comments'])
            ->where('community_id', $community->id)
            ->withCount(['likes', 'comments'])
            ->orderByRaw('(likes_count + comments_count) DESC')
            ->take(5)
            ->get();

        // Community Health Score Calculation
        $growthScore = min(25, (int) round(($newMembersInPeriod / max(1, $totalMembers)) * 100));
        $activityScore = min(25, (int) round($postsInPeriod * 3));
        $retentionScore = min(25, (int) round(($activeMembers / max(1, $totalMembers)) * 25));
        $moderationHealth = max(0, 25 - ($pendingReports * 5));

        $healthScore = min(100, $growthScore + $activityScore + $retentionScore + $moderationHealth);

        $healthLabel = match (true) {
            $healthScore >= 80 => 'Excellent',
            $healthScore >= 60 => 'Good',
            $healthScore >= 40 => 'Average',
            default => 'Needs Attention',
        };

        // 7-day Daily Time Series for Charts
        $dailyLabels = [];
        $dailyMemberJoins = [];
        $dailyPostCreations = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('M d');

            $dailyMemberJoins[] = DB::table('community_members')
                ->where('community_id', $community->id)
                ->where('status', 'accepted')
                ->whereDate('joined_at', $date)
                ->count();

            $dailyPostCreations[] = DB::table('posts')
                ->where('community_id', $community->id)
                ->whereDate('created_at', $date)
                ->count();
        }

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'community' => $community,
                'period' => $period,
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'new_members_in_period' => $newMembersInPeriod,
                'total_posts' => $totalPosts,
                'posts_in_period' => $postsInPeriod,
                'total_announcements' => $totalAnnouncements,
                'pending_requests' => $pendingRequests,
                'pending_reports' => $pendingReports,
                'banned_members' => $bannedMembers,
                'top_contributors' => $topContributors,
                'top_posts' => $topPosts,
                'health_score' => $healthScore,
                'health_label' => $healthLabel,
                'daily_labels' => $dailyLabels,
                'daily_member_joins' => $dailyMemberJoins,
                'daily_post_creations' => $dailyPostCreations,
            ]);
        }

        return view('member.community.analytics', compact(
            'community',
            'period',
            'totalMembers',
            'activeMembers',
            'newMembersInPeriod',
            'totalPosts',
            'postsInPeriod',
            'totalAnnouncements',
            'pendingRequests',
            'pendingReports',
            'bannedMembers',
            'topContributors',
            'topPosts',
            'healthScore',
            'healthLabel',
            'dailyLabels',
            'dailyMemberJoins',
            'dailyPostCreations'
        ));
    }

    public function exportCsv(Request $request, Community $community)
    {
        Gate::authorize('viewAnalytics', $community);

        $filename = 'community_analytics_' . $community->slug . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($community) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Community Name', $community->name]);
            fputcsv($file, ['Community Category', $community->category]);
            fputcsv($file, ['Export Date', now()->toDateTimeString()]);
            fputcsv($file, []);

            fputcsv($file, ['Metric Name', 'Value']);
            fputcsv($file, ['Total Members', $community->member_count]);
            fputcsv($file, ['Total Posts', Post::where('community_id', $community->id)->count()]);
            fputcsv($file, ['Announcements', Post::where('community_id', $community->id)->where('is_announcement', true)->count()]);
            fputcsv($file, ['Pending Join Requests', $community->pendingMembers()->count()]);
            fputcsv($file, ['Pending Reports', $community->reports()->where('status', 'pending')->count()]);
            fputcsv($file, ['Active Bans', $community->bans()->active()->count()]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
