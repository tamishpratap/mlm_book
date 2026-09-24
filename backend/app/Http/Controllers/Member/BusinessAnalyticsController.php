<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BusinessConversation;
use App\Models\BusinessMessage;
use App\Models\BusinessPage;
use App\Models\BusinessReview;
use App\Models\Post;
use Illuminate\Http\Request;

class BusinessAnalyticsController extends Controller
{
    public function index(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->canAccessInbox($currentMember->id)) {
            abort(403, 'Unauthorized action. Only authorized team members can view Business Analytics.');
        }

        $period = $request->query('period', '30days');
        $scores = $businessPage->calculateHealthScores($period);

        // Core Metrics
        $followersCount = $businessPage->followersCount();
        $postsCount = Post::where('business_page_id', $businessPage->id)->count();
        $reviewsCount = $businessPage->reviewsCount();
        $avgRating = $businessPage->averageRating();
        $conversationsCount = BusinessConversation::where('business_page_id', $businessPage->id)->count();
        $viewsCount = $businessPage->analyticsViews()->count();
        $teamCount = $businessPage->activeTeamMembers()->count() + 1;
        $ratingDistribution = $businessPage->ratingDistribution();
        $recommendationBadge = $businessPage->recommendationBadge();

        // Top Posts & Top Reviews
        $topPosts = Post::where('business_page_id', $businessPage->id)
            ->withCount(['likes', 'comments'])
            ->latest()
            ->take(5)
            ->get();

        $topReviews = $businessPage->visibleReviews()
            ->with('member')
            ->orderBy('rating', 'desc')
            ->latest()
            ->take(5)
            ->get();

        // Device Breakdown
        $deviceBreakdown = $businessPage->analyticsViews()
            ->selectRaw('device_type, count(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'business_page' => $businessPage,
                'period' => $period,
                'scores' => $scores,
                'followers_count' => $followersCount,
                'posts_count' => $postsCount,
                'reviews_count' => $reviewsCount,
                'avg_rating' => $avgRating,
                'conversations_count' => $conversationsCount,
                'views_count' => $viewsCount,
                'team_count' => $teamCount,
                'rating_distribution' => $ratingDistribution,
                'recommendation_badge' => $recommendationBadge,
                'top_posts' => $topPosts,
                'top_reviews' => $topReviews,
                'device_breakdown' => $deviceBreakdown,
            ]);
        }

        return view('member.business-pages.analytics.index', compact(
            'businessPage',
            'period',
            'scores',
            'followersCount',
            'postsCount',
            'reviewsCount',
            'avgRating',
            'conversationsCount',
            'viewsCount',
            'teamCount',
            'ratingDistribution',
            'recommendationBadge',
            'topPosts',
            'topReviews',
            'deviceBreakdown'
        ));
    }

    public function data(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->canAccessInbox($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $period = $request->query('period', '30days');
        $scores = $businessPage->calculateHealthScores($period);

        return response()->json([
            'success' => true,
            'period' => $period,
            'scores' => $scores,
            'followers_count' => $businessPage->followersCount(),
            'posts_count' => Post::where('business_page_id', $businessPage->id)->count(),
            'reviews_count' => $businessPage->reviewsCount(),
            'average_rating' => $businessPage->averageRating(),
            'views_count' => $businessPage->analyticsViews()->count(),
        ]);
    }
}
