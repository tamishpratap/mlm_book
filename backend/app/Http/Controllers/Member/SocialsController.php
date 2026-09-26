<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\HiddenPost;
use App\Models\Member;
use App\Models\Post;
use App\Models\Story;
use App\Services\AdDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SocialsController extends Controller
{
    public function index(Request $request)
    {
        $currentMember = auth('member')->user();
        $isVerified = $currentMember && $currentMember->isMobileVerified();
        $perPage = min(50, max(5, (int) $request->input('per_page', 10)));
        $page = max(1, (int) $request->input('page', 1));

        $friendIds = collect();
        $feedMemberIds = collect([$currentMember->id]);
        $hiddenPostIds = collect();

        if ($isVerified) {
            $friendIds = Friendship::query()
                ->forMember($currentMember->id)
                ->accepted()
                ->get(['member_one_id', 'member_two_id'])
                ->map(fn (Friendship $friendship) => $friendship->member_one_id === $currentMember->id
                    ? $friendship->member_two_id
                    : $friendship->member_one_id);

            $feedMemberIds = collect($friendIds->all())
                ->push($currentMember->id)
                ->unique()
                ->values();

            $hiddenPostIds = HiddenPost::query()
                ->where('member_id', $currentMember->id)
                ->pluck('post_id');

            $activeAdPostIds = \App\Models\AdCampaign::query()
                ->where(function ($q) {
                    $q->where('campaign_type', \App\Models\AdCampaign::TYPE_BUSINESS_AD)
                        ->orWhereNull('campaign_type');
                })
                ->where('approval_status', \App\Models\AdCampaign::APPROVAL_APPROVED)
                ->whereIn('status', [\App\Models\AdCampaign::STATUS_APPROVED, \App\Models\AdCampaign::STATUS_ACTIVE])
                ->pluck('post_id')
                ->filter();

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
                ])
                ->withCount([
                    'likes',
                    'reactions',
                    'shares',
                    'savedPosts',
                    'comments' => fn ($q) => $q->whereNull('parent_id'),
                ])
                ->whereIn('member_id', $feedMemberIds)
                ->whereNotIn('id', $hiddenPostIds)
                ->whereNotIn('id', $activeAdPostIds)
                ->where(function ($q) {
                    $q->where('media_type', '!=', 'video')
                        ->orWhereNull('media_type');
                })
                ->whereDoesntHave('originalPost', function ($oq) {
                    $oq->where('media_type', 'video');
                })
                ->orderByDesc('is_pinned')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            // Two-tier feed ranking:
            // Tier 1: Eligible Active Paid Campaigns (Business Ads + Event Campaigns) ranked by remaining_amount DESC, created_at DESC, id DESC
            // Tier 2: Eligible Organic Posts in existing organic order (is_pinned DESC, created_at DESC, id DESC)
            $rankedPaidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($currentMember);
            $totalPaid = $rankedPaidItems->count();
            $totalOrganic = (clone $query)->count();
            $totalFeed = $totalPaid + $totalOrganic;

            $globalOffset = ($page - 1) * $perPage;

            if ($globalOffset < $totalPaid) {
                // Page starts inside paid tier
                $paidSlice = $rankedPaidItems->slice($globalOffset, $perPage)->values();
                $remainingSlots = $perPage - $paidSlice->count();

                $organicSlice = $remainingSlots > 0
                    ? (clone $query)->skip(0)->take($remainingSlots)->get()
                    : collect();

                $pageItems = $paidSlice->concat($organicSlice);
            } else {
                // Page starts after paid tier
                $organicOffset = $globalOffset - $totalPaid;
                $organicSlice = (clone $query)->skip($organicOffset)->take($perPage)->get();
                $pageItems = $organicSlice;
            }

            $posts = new LengthAwarePaginator(
                $pageItems,
                $totalFeed,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            // Unverified members receive ZERO standard/organic posts in Socials feed.
            // Visible ONLY to unverified members: eligible Business Page Paid Ads and Paid Events from common ranking pool.
            $rankedPaidItems = app(AdDeliveryService::class)->getRankedPaidFeedItems($currentMember);
            $total = $rankedPaidItems->count();
            $slicedItems = $rankedPaidItems->slice(($page - 1) * $perPage, $perPage)->values();

            $posts = new LengthAwarePaginator(
                $slicedItems,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        if ($request->ajax() || $request->expectsJson() || $request->is('api/*')) {
            $formattedItems = collect($posts->items())->map(function ($post) {
                if (empty($post->content_type)) {
                    if ($post->type === 'sponsored_event' || $post->type === 'event' || ($post->ad_campaign['campaign_type'] ?? null) === 'event' || !empty($post->event) || !empty($post->event_id)) {
                        $post->content_type = 'PAID_EVENT';
                    } elseif (!empty($post->is_sponsored)) {
                        $post->content_type = 'PAID_AD';
                    } else {
                        $post->content_type = 'ORGANIC_POST';
                        $post->is_sponsored = false;
                    }
                }
                return $post;
            });

            return response()->json([
                'success' => true,
                'has_more' => $posts->hasMorePages(),
                'current_page' => $posts->currentPage(),
                'next_page' => $posts->currentPage() + 1,
                'total' => max($posts->total(), $posts->count()),
                'posts' => $formattedItems->values()->all(),
            ]);
        }

        $suggestedMembers = collect();
        if ($posts->isEmpty()) {
            $suggestedMembers = Member::query()
                ->sociallyEligible()
                ->where('id', '!=', $currentMember->id)
                ->whereNotIn('id', $feedMemberIds)
                ->inRandomOrder()
                ->take(4)
                ->get();
        }

        $allActiveStories = Story::query()
            ->with(['member', 'views'])
            ->active()
            ->whereIn('member_id', $feedMemberIds)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $groupedStories = $allActiveStories->groupBy('member_id');

        $unreadMemberStories = collect();
        $seenMemberStories = collect();
        $ownMemberStory = null;

        foreach ($groupedStories as $mId => $mStories) {
            $mIdInt = (int) $mId;

            // Find first unread story for current member
            $firstUnread = $mStories->first(function ($s) use ($currentMember) {
                return ! $s->views->contains('viewer_member_id', $currentMember->id);
            });

            // Target story: first unread story if exists, otherwise oldest story
            $targetStory = $firstUnread ?? $mStories->first();

            if ($mIdInt === (int) $currentMember->id) {
                $ownMemberStory = $targetStory;
            } elseif ($firstUnread) {
                $unreadMemberStories->push($targetStory);
            } else {
                $seenMemberStories->push($targetStory);
            }
        }

        $stories = collect();
        if ($ownMemberStory) {
            $stories->push($ownMemberStory);
        }
        foreach ($unreadMemberStories as $storyItem) {
            $stories->push($storyItem);
        }
        foreach ($seenMemberStories as $storyItem) {
            $stories->push($storyItem);
        }

        $hasProfilePhoto = $currentMember->profile_photo
            && str_starts_with($currentMember->profile_photo, 'uploads/profile/')
            && ! str_contains($currentMember->profile_photo, '..')
            && $currentMember->profile_photo === 'uploads/profile/'.basename($currentMember->profile_photo)
            && file_exists(public_path($currentMember->profile_photo));
        $composerAvatar = $hasProfilePhoto
            ? asset($currentMember->profile_photo).'?v='.($currentMember->updated_at?->timestamp ?? now()->timestamp)
            : asset('member_assets/images/dashboard/image/profile.png');

        return view('member.socials', compact('currentMember', 'posts', 'stories', 'composerAvatar', 'suggestedMembers'));
    }
}
