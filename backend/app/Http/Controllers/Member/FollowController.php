<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\Follower;
use App\Models\Member;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggleFollow(Request $request, Member $member)
    {
        $currentMember = auth('member')->user();

        if (! $currentMember || ! $currentMember->isMobileVerified()) {
            $message = EnsureMemberMobileVerified::UNVERIFIED_MESSAGE;
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->with('error', $message);
        }

        abort_if($currentMember->is($member), 400, 'Cannot follow yourself.');

        $existing = Follower::query()
            ->where('follower_id', $currentMember->id)
            ->where('following_id', $member->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFollowing = false;
            $message = 'Unfollowed '.$member->name;
        } else {
            Follower::query()->create([
                'follower_id' => $currentMember->id,
                'following_id' => $member->id,
                'status' => 'accepted',
            ]);
            $isFollowing = true;
            $message = 'Following '.$member->name;
        }

        $followersCount = $member->followers()->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_following' => $isFollowing,
                'followers_count' => $followersCount,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function followers(Request $request, Member $member)
    {
        $followers = Member::query()
            ->whereIn('id', $member->followers()->pluck('follower_id'))
            ->paginate(15);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
                'followers' => $followers,
                'members' => $followers,
                'total' => $followers->total(),
                'html' => view('member.people.partials.follow_modal', [
                    'title' => 'Followers',
                    'members' => $followers,
                ])->render(),
            ]);
        }

        return view('member.people.followers', compact('member', 'followers'));
    }

    public function following(Request $request, Member $member)
    {
        $following = Member::query()
            ->whereIn('id', $member->following()->pluck('following_id'))
            ->paginate(15);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $member,
                'following' => $following,
                'members' => $following,
                'total' => $following->total(),
                'html' => view('member.people.partials.follow_modal', [
                    'title' => 'Following',
                    'members' => $following,
                ])->render(),
            ]);
        }

        return view('member.people.following', compact('member', 'following'));
    }
}
