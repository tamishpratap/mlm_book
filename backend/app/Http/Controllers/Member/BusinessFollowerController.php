<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\BusinessFollower;
use App\Models\BusinessFollowerInvitation;
use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Http\Request;

class BusinessFollowerController extends Controller
{
    public function toggleFollow(Request $request, BusinessPage $businessPage)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if ($businessPage->isOwner($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Page owners cannot follow their own business page.',
            ], 403);
        }

        $existing = BusinessFollower::where('business_page_id', $businessPage->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existing) {
            // Unfollow or Cancel Request
            $wasAccepted = $existing->isAccepted();
            $existing->delete();

            return response()->json([
                'success' => true,
                'status' => 'none',
                'label' => 'Follow',
                'is_following' => false,
                'is_pending' => false,
                'message' => $wasAccepted ? 'Unfollowed ' . $businessPage->page_name . '.' : 'Follow request cancelled.',
                'followers_count' => $businessPage->followersCount(),
            ]);
        }

        // New Follow or Follow Request
        if ($businessPage->visibility === 'private') {
            $follower = BusinessFollower::create([
                'business_page_id' => $businessPage->id,
                'member_id' => $member->id,
                'status' => 'pending',
                'followed_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'status' => 'pending',
                'label' => 'Requested',
                'is_following' => false,
                'is_pending' => true,
                'message' => 'Follow request sent to ' . $businessPage->page_name . '.',
                'followers_count' => $businessPage->followersCount(),
            ]);
        }

        $follower = BusinessFollower::create([
            'business_page_id' => $businessPage->id,
            'member_id' => $member->id,
            'status' => 'accepted',
            'followed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status' => 'accepted',
            'label' => 'Following',
            'is_following' => true,
            'is_pending' => false,
            'message' => 'You are now following ' . $businessPage->page_name . '!',
            'followers_count' => $businessPage->followersCount(),
        ]);
    }

    public function handleRequest(Request $request, BusinessPage $businessPage, BusinessFollower $follower)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners or admins can manage follow requests.'], 403);
        }

        if ((int) $follower->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid follow request.'], 422);
        }

        $action = $request->input('action', 'accept');

        if ($action === 'accept') {
            $follower->update([
                'status' => 'accepted',
                'followed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Follow request accepted!',
                'followers_count' => $businessPage->followersCount(),
            ]);
        }

        $follower->delete();

        return response()->json([
            'success' => true,
            'message' => 'Follow request declined.',
            'followers_count' => $businessPage->followersCount(),
        ]);
    }

    public function removeFollower(Request $request, BusinessPage $businessPage, BusinessFollower $follower)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners or admins can remove followers.'], 403);
        }

        if ((int) $follower->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid follower.'], 422);
        }

        $memberName = $follower->member->name ?? 'Member';
        $follower->delete();

        return response()->json([
            'success' => true,
            'message' => $memberName . ' removed from followers.',
            'followers_count' => $businessPage->followersCount(),
        ]);
    }

    public function inviteToFollow(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owners or admins can invite followers.'], 403);
        }

        $validated = $request->validate([
            'invitee_id' => ['required', 'integer', 'exists:members,id'],
        ]);

        $invitee = Member::findOrFail($validated['invitee_id']);

        if ($businessPage->isFollowedBy($invitee->id)) {
            return response()->json(['success' => false, 'message' => $invitee->name . ' is already following this page.'], 422);
        }

        $existing = BusinessFollowerInvitation::where('business_page_id', $businessPage->id)
            ->where('invitee_id', $invitee->id)
            ->first();

        if ($existing && $existing->status === 'pending') {
            return response()->json(['success' => false, 'message' => 'An invitation to follow has already been sent to ' . $invitee->name . '.'], 422);
        }

        BusinessFollowerInvitation::updateOrCreate(
            [
                'business_page_id' => $businessPage->id,
                'invitee_id' => $invitee->id,
            ],
            [
                'inviter_id' => $currentMember->id,
                'status' => 'pending',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Follow invitation sent to ' . $invitee->name . '!',
        ]);
    }
}
