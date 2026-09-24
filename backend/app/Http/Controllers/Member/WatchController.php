<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\HiddenPost;
use App\Models\Member;
use App\Models\Post;
use App\Models\SavedPost;
use App\Services\AdDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WatchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $currentMember = auth('member')->user();
        $filter = $request->query('filter', 'all');

        // Maintain campaign lifecycles
        app(AdDeliveryService::class)->maintainCampaignLifecycles(now());

        // 1. Resolve eligible member IDs (current user + accepted, verified, non-blocked connections)
        $eligibleMemberIds = $currentMember->watchEligibleMemberIds();

        // 2. Resolve blocked members in either direction
        $blockedMemberIds = BlockedUser::query()
            ->where('member_id', $currentMember->id)
            ->pluck('blocked_member_id')
            ->concat(
                BlockedUser::query()
                    ->where('blocked_member_id', $currentMember->id)
                    ->pluck('member_id')
            )
            ->unique()
            ->values();

        // 3. Hidden posts
        $hiddenPostIds = HiddenPost::query()
            ->where('member_id', $currentMember->id)
            ->pluck('post_id');

        // 4. Base query for video posts: MUST be Business Page video with eligible active Business Ad Campaign
        $query = Post::query()
            ->with([
                'member',
                'originalPost' => fn ($q) => $q->with(['member', 'businessPage'])->withCount([
                    'likes',
                    'reactions',
                    'shares',
                    'savedPosts',
                    'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                ]),
                'businessPage',
                'community',
            ])
            ->withCount([
                'likes',
                'reactions',
                'shares',
                'savedPosts',
                'comments' => fn ($q) => $q->whereNull('parent_id'),
            ]);

        $this->applyWatchEligibility($query, $hiddenPostIds, $blockedMemberIds);

        if ($filter === 'my_videos') {
            // My Videos: Show the current authenticated user's own business page videos with eligible active ad campaign
            $query->where(function ($mq) use ($currentMember) {
                $mq->where('member_id', $currentMember->id)
                   ->orWhereHas('businessPage', fn ($bq) => $bq->where('member_id', $currentMember->id));
            })
            ->latest('created_at')
            ->orderByDesc('id');
        } elseif ($filter === 'saved') {
            // Saved Videos: Show videos saved by the current authenticated user
            $savedPostIds = SavedPost::query()
                ->where('member_id', $currentMember->id)
                ->pluck('post_id');

            $query->whereIn('id', $savedPostIds)
                  ->latest('created_at')
                  ->orderByDesc('id');
        } elseif ($filter === 'trending') {
            // Trending Videos: Public trending videos from socially eligible (verified, active) creators
            $query->orderByRaw('(likes_count + (reactions_count * 1.5) + (comments_count * 2) + (shares_count * 2)) DESC')
                  ->orderByDesc('created_at')
                  ->orderByDesc('id');
        } else {
            // Default "all": Strictly connection-based Watch feed
            // ELIGIBLE ONLY IF creator is current user OR has a valid accepted connection with current user
            $query->whereIn('member_id', $eligibleMemberIds)
                  ->latest('created_at')
                  ->orderByDesc('id');
        }

        $posts = $query->paginate(8)->withQueryString();

        // Sidebar Data
        // 1. Suggested Creators:
        // Socially eligible (verified, active) video creators who have active eligible Business Page ad videos,
        // are NOT already connected, do NOT have pending requests, are NOT blocked/disconnected, and NOT self.
        $pendingFriendshipMemberIds = Friendship::query()
            ->forMember($currentMember->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->get(['member_one_id', 'member_two_id'])
            ->toBase()
            ->map(fn (Friendship $f) => $f->member_one_id === $currentMember->id ? $f->member_two_id : $f->member_one_id)
            ->unique()
            ->values();

        $suggestedCreatorsExcludeIds = collect($eligibleMemberIds)
            ->concat($pendingFriendshipMemberIds)
            ->concat($blockedMemberIds)
            ->push($currentMember->id)
            ->unique()
            ->values();

        $suggestedCreators = Member::query()
            ->sociallyEligible()
            ->whereNotIn('id', $suggestedCreatorsExcludeIds)
            ->whereHas('posts', function ($pq) use ($hiddenPostIds, $blockedMemberIds) {
                $this->applyWatchEligibility($pq, $hiddenPostIds, $blockedMemberIds);
            })
            ->latest('id')
            ->take(5)
            ->get();

        // 2. Trending Videos:
        $trendingQuery = Post::query()
            ->with(['member', 'businessPage'])
            ->withCount(['likes', 'reactions', 'comments']);
        $this->applyWatchEligibility($trendingQuery, $hiddenPostIds, $blockedMemberIds);
        $trendingVideos = $trendingQuery
            ->where(function ($tq) use ($currentMember) {
                $tq->where('member_id', $currentMember->id)
                   ->orWhereHas('member', fn ($mq) => $mq->sociallyEligible());
            })
            ->orderByRaw('(likes_count + reactions_count + comments_count) DESC')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(3)
            ->get();

        // 3. Recent Videos:
        $recentQuery = Post::query()
            ->with(['member', 'businessPage']);
        $this->applyWatchEligibility($recentQuery, $hiddenPostIds, $blockedMemberIds);
        $recentVideos = $recentQuery
            ->whereIn('member_id', $eligibleMemberIds)
            ->latest('created_at')
            ->orderByDesc('id')
            ->take(3)
            ->get();

        $myVideosCountQuery = Post::query()
            ->where(function ($mq) use ($currentMember) {
                $mq->where('member_id', $currentMember->id)
                   ->orWhereHas('businessPage', fn ($bq) => $bq->where('member_id', $currentMember->id));
            });
        $this->applyWatchEligibility($myVideosCountQuery, $hiddenPostIds, $blockedMemberIds);
        $myVideosCount = $myVideosCountQuery->count();

        $savedVideosCountQuery = Post::query()
            ->whereHas('savedPosts', fn ($sq) => $sq->where('member_id', $currentMember->id));
        $this->applyWatchEligibility($savedVideosCountQuery, $hiddenPostIds, $blockedMemberIds);
        $savedVideosCount = $savedVideosCountQuery->count();

        if ($request->ajax() || $request->expectsJson() || $request->is('api/*')) {
            $html = '';
            foreach ($posts as $post) {
                $html .= view('member.watch.partials.card', compact('post'))->render();
            }

            return response()->json([
                'success' => true,
                'has_more' => $posts->hasMorePages(),
                'current_page' => $posts->currentPage(),
                'next_page' => $posts->currentPage() + 1,
                'total' => $posts->total(),
                'posts' => $posts->items(),
                'suggested_creators' => $suggestedCreators,
                'trending_videos' => $trendingVideos,
                'recent_videos' => $recentVideos,
                'my_videos_count' => $myVideosCount,
                'saved_videos_count' => $savedVideosCount,
                'filter' => $filter,
                'html' => $html,
            ]);
        }

        return view('member.watch.index', compact(
            'currentMember',
            'posts',
            'filter',
            'suggestedCreators',
            'trendingVideos',
            'recentVideos',
            'myVideosCount',
            'savedVideosCount'
        ));
        } catch (Throwable $e) {
            Log::error('Watch feed loading failed: ' . $e->getMessage(), [
                'exception' => $e,
                'filter' => $request->query('filter', 'all'),
                'member_id' => auth('member')->id(),
            ]);

            if ($request->ajax() || $request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'Unable to load videos right now. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Authoritative Watch Eligibility Rule:
     * A video is visible on Watch ONLY IF:
     * 1. It is a Business Page video (business_page_id is NOT NULL and matches an active business page)
     * 2. It has an active, approved, eligible Business Ad Campaign:
     *    - ad_campaigns.business_page_id == posts.business_page_id
     *    - campaign_type in ('business_ad', null)
     *    - matches eligibleForDelivery()
     */
    private function applyWatchEligibility($query, $hiddenPostIds = [], $blockedMemberIds = [])
    {
        return $query
            ->where('media_type', 'video')
            ->whereNotNull('media_path')
            ->where('media_path', '!=', '')
            ->whereNotNull('business_page_id')
            ->whereHas('businessPage', function ($bq) {
                $bq->where('status', 'active');
            })
            ->whereHas('adCampaigns', function ($cq) {
                $cq->where(function ($sub) {
                    $sub->where('campaign_type', AdCampaign::TYPE_BUSINESS_AD)
                        ->orWhereNull('campaign_type');
                })
                ->whereColumn('ad_campaigns.business_page_id', 'posts.business_page_id')
                ->eligibleForDelivery();
            })
            ->when(!empty($hiddenPostIds), fn ($q) => $q->whereNotIn('id', $hiddenPostIds))
            ->when(!empty($blockedMemberIds), function ($q) use ($blockedMemberIds) {
                $q->whereNotIn('member_id', $blockedMemberIds)
                  ->whereDoesntHave('originalPost', fn ($oq) => $oq->whereIn('member_id', $blockedMemberIds));
            });
    }
}
