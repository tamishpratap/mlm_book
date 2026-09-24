<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use Illuminate\Http\Request;

class MemberDirectoryProfileController extends Controller
{
    public function show(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();
        $isSelf = $currentMember && $currentMember->is($member);

        if (! $isSelf && ! $member->isSociallyEligible()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member profile not found or is currently unavailable.',
                ], 404);
            }

            abort(404, 'Member profile not found or is currently unavailable.');
        }

        if ($isSelf && ! ($request->expectsJson() || $request->ajax() || $request->is('api/*'))) {
            return redirect()->route('member.profile.show');
        }

        if (! $isSelf && $currentMember) {
            \App\Models\ProfileVisit::query()->create([
                'profile_owner_id' => $member->id,
                'visitor_id' => $currentMember->id,
                'visited_at' => now(),
            ]);
        }

        $friendship = (! $isSelf && $currentMember) ? Friendship::between($currentMember->id, $member->id)->first() : null;
        $friendshipState = $isSelf ? 'self' : ($friendship?->stateFor($currentMember->id) ?? 'none');
        $activeTab = $request->query('tab', 'timeline');

        // Statistics
        $postsCount = $member->posts()->count();
        $storiesCount = $member->stories()->count();

        $friendshipsQuery = Friendship::query()
            ->forMember($member->id)
            ->accepted()
            ->with(['memberOne', 'memberTwo'])
            ->orderByDesc('accepted_at');
        $friendsCount = (clone $friendshipsQuery)->count();
        $friendsList = $friendshipsQuery->get()->map(fn (Friendship $item) => $item->otherMember($member->id));

        $photosCount = $member->posts()->where('media_type', 'image')->whereNotNull('media_path')->count();
        $videosCount = $member->posts()->where('media_type', 'video')->whereNotNull('media_path')->count();
        $sharesCount = PostShare::query()->where('shared_by', $member->id)->count();

        $postIds = $member->posts()->pluck('id');
        $reactionsReceivedCount = PostReaction::query()->whereIn('post_id', $postIds)->count();
        $commentsReceivedCount = PostComment::query()->whereIn('post_id', $postIds)->count();

        // Timeline Feed for Member (visible if own profile or friends)
        $canViewTimeline = $isSelf || $friendshipState === 'friends';
        $posts = $canViewTimeline
            ? Post::query()
                ->with([
                    'member',
                    'originalPost' => fn ($q) => $q->with(['member', 'businessPage'])->withCount([
                        'likes',
                        'reactions',
                        'shares',
                        'savedPosts',
                        'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                    ]),
                ])
                ->withCount(['likes', 'reactions', 'shares', 'savedPosts', 'comments' => fn ($q) => $q->whereNull('parent_id')])
                ->where('member_id', $member->id)
                ->orderByDesc('is_pinned')
                ->latest('created_at')
                ->paginate(10)
                ->withQueryString()
            : collect();

        // Gallery items
        $photos = $canViewTimeline
            ? Post::query()->where('member_id', $member->id)->where('media_type', 'image')->whereNotNull('media_path')->latest('created_at')->get()
            : collect();
        $videos = $canViewTimeline
            ? Post::query()->where('member_id', $member->id)->where('media_type', 'video')->whereNotNull('media_path')->latest('created_at')->get()
            : collect();
        $stories = $canViewTimeline
            ? ($isSelf
                ? $member->stories()->withCount(['views', 'likes', 'reactions', 'replies'])->latest('created_at')->get()
                : $member->stories()->active()->latest('created_at')->get())
            : collect();

        $isFollowing = (! $isSelf && $currentMember)
            ? \App\Models\Follower::query()
                ->where('follower_id', $currentMember->id)
                ->where('following_id', $member->id)
                ->exists()
            : false;
        $isBlocked = (! $isSelf && $currentMember)
            ? \App\Models\BlockedUser::query()
                ->where('member_id', $currentMember->id)
                ->where('blocked_member_id', $member->id)
                ->exists()
            : false;
        $followersCount = $member->followers()->count();
        $followingCount = $member->following()->count();

        // Referral Data
        $introducer = $member->introducer_id
            ? Member::where('user_id', $member->introducer_id)
                ->select(['id', 'name', 'user_id', 'profile_photo', 'mobile_verified_at', 'city', 'country', 'created_at'])
                ->first()
            : null;

        $directReferrals = $member->user_id
            ? Member::where('introducer_id', $member->user_id)
                ->select(['id', 'name', 'user_id', 'profile_photo', 'mobile_verified_at', 'city', 'country', 'created_at'])
                ->latest('created_at')
                ->get()
            : collect();

        $directReferralCount = $member->direct_referral_count ?? $directReferrals->count();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            $viewName = match ($activeTab) {
                'about' => 'member.profile.partials.about',
                'photos' => 'member.profile.partials.photos',
                'videos' => 'member.profile.partials.videos',
                'friends' => 'member.profile.partials.friends',
                'stories' => 'member.profile.partials.stories',
                'activity' => 'member.profile.partials.activity',
                default => 'member.profile.partials.timeline',
            };

            return response()->json([
                'success' => true,
                'member' => $member,
                'friendship' => $friendship,
                'friendship_state' => $friendshipState,
                'is_self' => $isSelf,
                'is_following' => $isFollowing,
                'is_blocked' => $isBlocked,
                'tab' => $activeTab,
                'active_tab' => $activeTab,
                'counts' => [
                    'posts' => $postsCount,
                    'stories' => $storiesCount,
                    'friends' => $friendsCount,
                    'followers' => $followersCount,
                    'following' => $followingCount,
                    'photos' => $photosCount,
                    'videos' => $videosCount,
                    'shares' => $sharesCount,
                    'reactions' => $reactionsReceivedCount,
                    'comments' => $commentsReceivedCount,
                    'direct_referrals' => $directReferralCount,
                ],
                'posts' => $posts,
                'photos' => $photos,
                'videos' => $videos,
                'friends_list' => $friendsList,
                'stories' => $stories,
                'introducer' => $introducer,
                'direct_referrals' => $directReferrals,
                'direct_referral_count' => $directReferralCount,
                'html' => view($viewName, compact(
                    'member',
                    'posts',
                    'photos',
                    'videos',
                    'friendsList',
                    'friendsCount',
                    'stories'
                ))->render(),
            ]);
        }

        return view('member.people.show', compact(
            'member',
            'friendship',
            'friendshipState',
            'activeTab',
            'postsCount',
            'storiesCount',
            'friendsCount',
            'photosCount',
            'videosCount',
            'sharesCount',
            'reactionsReceivedCount',
            'commentsReceivedCount',
            'posts',
            'photos',
            'videos',
            'friendsList',
            'stories'
        ));
    }
}
