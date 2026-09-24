<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\Community;
use App\Models\CommunityInvitation;
use App\Models\CommunityMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityInviteController extends Controller
{
    public function show(Request $request, string $code)
    {
        $invitation = CommunityInvitation::where('invite_code', $code)->first();
        $community = $invitation ? $invitation->community : Community::where('invite_code', $code)->first();

        if (! $community || $community->status !== 'active') {
            return response()->view('member.community.invalid-invite', [
                'errorTitle' => 'Invalid Community Invite',
                'errorMessage' => 'The community invite link you followed is invalid or the community is no longer active.',
            ], 440);
        }

        if ($invitation && ! $invitation->isValid()) {
            return response()->view('member.community.invalid-invite', [
                'errorTitle' => 'Invite Link Expired or Disabled',
                'errorMessage' => 'This invitation link has expired, reached its usage limit, or was revoked by an admin.',
            ], 410);
        }

        $currentMember = auth('member')->user();

        if (! $currentMember) {
            // Store intended URL for seamless post-login redirection
            session(['url.intended' => route('member.community.invite.show', $code)]);
            session(['pending_invite_code' => $code]);

            return view('member.community.invite-landing', [
                'community' => $community,
                'invitation' => $invitation,
                'isLoggedIn' => false,
                'isMember' => false,
                'isPending' => false,
            ]);
        }

        $isMember = $community->isMember($currentMember->id);
        $isPending = $community->isPending($currentMember->id);

        if ($isMember) {
            return redirect()->route('member.community.show', $community)
                ->with('info', 'You are already a member of ' . $community->name . '.');
        }

        return view('member.community.invite-landing', [
            'community' => $community,
            'invitation' => $invitation,
            'isLoggedIn' => true,
            'isMember' => false,
            'isPending' => $isPending,
        ]);
    }

    public function processJoin(Request $request, string $code)
    {
        $currentMember = auth('member')->user();
        if (! $currentMember) {
            session(['url.intended' => route('member.community.invite.show', $code)]);

            return redirect()->route('member.login')->with('info', 'Please log in to accept the community invitation.');
        }

        if (! $currentMember->isMobileVerified()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
                ], 403);
            }
            return redirect()->route('member.community.index')
                ->with('error', EnsureMemberMobileVerified::UNVERIFIED_MESSAGE);
        }

        $invitation = CommunityInvitation::where('invite_code', $code)->first();
        $community = $invitation ? $invitation->community : Community::where('invite_code', $code)->first();

        if (! $community || $community->status !== 'active') {
            return redirect()->route('member.community.index')
                ->with('error', 'The community invite is no longer valid.');
        }

        if ($invitation && ! $invitation->isValid()) {
            return redirect()->route('member.community.index')
                ->with('error', 'This invite link has expired or been revoked.');
        }

        if ($community->isMember($currentMember->id)) {
            return redirect()->route('member.community.show', $community)
                ->with('info', 'You are already a member of ' . $community->name . '.');
        }

        $isPublic = $community->isPublic();

        CommunityMember::updateOrCreate(
            [
                'community_id' => $community->id,
                'member_id' => $currentMember->id,
            ],
            [
                'role' => 'member',
                'status' => $isPublic ? 'accepted' : 'pending',
                'joined_at' => $isPublic ? now() : null,
            ]
        );

        if ($invitation) {
            $invitation->incrementUsage($currentMember->id);
        }

        // Sync member count
        $community->syncMemberCount();

        if ($isPublic) {
            return redirect()->route('member.community.show', $community)
                ->with('success', 'Welcome to ' . $community->name . '! You have joined via invitation.');
        }

        return redirect()->route('member.community.show', $community)
            ->with('info', 'Join request submitted for ' . $community->name . '. Pending admin approval.');
    }

    public function generate(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if (! $community->isAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only admins can generate invite links.'], 403);
        }

        $code = Str::random(12);

        $invitation = CommunityInvitation::create([
            'community_id' => $community->id,
            'inviter_id' => $member->id,
            'invite_code' => $code,
            'status' => 'pending',
            'type' => 'unlimited',
            'source' => 'dashboard',
        ]);

        $community->update(['invite_code' => $code]);

        return response()->json([
            'success' => true,
            'message' => 'New invite link generated!',
            'invite_code' => $code,
            'invite_url' => url('/community/invite/' . $code),
        ]);
    }

    public function revoke(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if (! $community->isAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only admins can revoke invite links.'], 403);
        }

        if ($community->invite_code) {
            CommunityInvitation::where('community_id', $community->id)
                ->where('invite_code', $community->invite_code)
                ->update(['is_revoked' => true, 'status' => 'rejected']);

            $community->update(['invite_code' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invite link revoked successfully.',
        ]);
    }

    public function getFriends(Request $request, Community $community)
    {
        $member = auth('member')->user();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $friendIds = $member->acceptedFriendIds();
        $friends = \App\Models\Member::sociallyEligible()
            ->whereIn('id', $friendIds)
            ->select(['id', 'name', 'user_id', 'email', 'profile_photo'])
            ->get();

        // Get members of this community
        $memberIdsInCommunity = CommunityMember::where('community_id', $community->id)
            ->where('status', 'accepted')
            ->pluck('member_id')
            ->toArray();

        if (! in_array($community->owner_id, $memberIdsInCommunity)) {
            $memberIdsInCommunity[] = $community->owner_id;
        }

        // Get pending invited invitee_ids
        $invitedMemberIds = CommunityInvitation::where('community_id', $community->id)
            ->where('status', 'pending')
            ->whereNotNull('invitee_id')
            ->pluck('invitee_id')
            ->toArray();

        $friendData = $friends->map(function (\App\Models\Member $friend) use ($memberIdsInCommunity, $invitedMemberIds) {
            $isMember = in_array($friend->id, $memberIdsInCommunity);
            $isInvited = ! $isMember && in_array($friend->id, $invitedMemberIds);
            $username = $friend->user_id ? '@' . $friend->user_id : ($friend->email ? '@' . explode('@', $friend->email)[0] : '');

            return [
                'id' => $friend->id,
                'name' => $friend->name,
                'username' => $username,
                'avatar_url' => $friend->avatar_url,
                'is_member' => $isMember,
                'is_invited' => $isInvited,
            ];
        });

        return response()->json([
            'success' => true,
            'friends' => $friendData,
        ]);
    }

    public function sendInvites(Request $request, Community $community)
    {
        $inviter = auth('member')->user();
        if (! $inviter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'invitee_ids' => 'required|array',
            'invitee_ids.*' => 'integer|exists:members,id',
        ]);

        $inviteeIds = $request->input('invitee_ids', []);
        $sentCount = 0;

        foreach ($inviteeIds as $inviteeId) {
            if ($community->isMember($inviteeId) || (int) $community->owner_id === (int) $inviteeId) {
                continue;
            }

            $existingInvite = CommunityInvitation::where('community_id', $community->id)
                ->where('invitee_id', $inviteeId)
                ->where('status', 'pending')
                ->first();

            if ($existingInvite) {
                continue;
            }

            CommunityInvitation::create([
                'community_id' => $community->id,
                'inviter_id' => $inviter->id,
                'invitee_id' => $inviteeId,
                'invite_code' => Str::random(12),
                'status' => 'pending',
                'type' => 'direct',
                'source' => 'friend_invite',
            ]);

            $sentCount++;
        }

        return response()->json([
            'success' => true,
            'message' => $sentCount > 0
                ? "Community invitation sent successfully!"
                : "No new invitations sent.",
            'sent_count' => $sentCount,
        ]);
    }
}
