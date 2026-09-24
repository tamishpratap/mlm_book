<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\Community;
use App\Models\CommunityMember;
use Illuminate\Http\Request;

class CommunityMembershipController extends Controller
{
    public function join(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if (! $member || ! $member->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if ($community->isSecret()) {
            return response()->json([
                'success' => false,
                'message' => 'Secret communities cannot be joined directly.',
            ], 403);
        }

        if ($community->isMember($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already a member of this community.',
            ], 422);
        }

        if ($community->isPending($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Your join request is already pending approval.',
            ], 422);
        }

        $membership = CommunityMember::updateOrCreate(
            [
                'community_id' => $community->id,
                'member_id' => $member->id,
            ],
            [
                'role' => 'member',
                'status' => $community->isPublic() ? 'accepted' : 'pending',
                'joined_at' => $community->isPublic() ? now() : null,
            ]
        );

        $this->syncMemberCount($community);

        $html = view('member.community.partials.join-button', compact('community'))->render();

        return response()->json([
            'success' => true,
            'status' => $membership->status,
            'message' => $community->isPublic()
                ? 'Successfully joined ' . $community->name . '!'
                : 'Join request submitted. Pending admin approval.',
            'html' => $html,
            'member_count' => $community->fresh()->member_count,
        ]);
    }

    public function leave(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if ($community->isOwner($member->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Community owners cannot leave without transferring ownership first.',
            ], 422);
        }

        $membership = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $member->id)
            ->first();

        if (! $membership || $membership->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'You are not an active member of this community.',
            ], 422);
        }

        $membership->update(['status' => 'left']);
        $this->syncMemberCount($community);

        $html = view('member.community.partials.join-button', compact('community'))->render();

        return response()->json([
            'success' => true,
            'message' => 'You have left ' . $community->name . '.',
            'html' => $html,
            'member_count' => $community->fresh()->member_count,
        ]);
    }

    public function handleRequest(Request $request, Community $community, CommunityMember $membership)
    {
        $member = auth('member')->user();

        if (! $community->isAdmin($member->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $membership->community_id !== (int) $community->id) {
            return response()->json(['success' => false, 'message' => 'Invalid membership request.'], 400);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:accept,reject'],
        ]);

        if ($validated['action'] === 'accept') {
            $membership->update([
                'status' => 'accepted',
                'joined_at' => now(),
            ]);
            $msg = 'Join request accepted.';
        } else {
            $membership->update([
                'status' => 'rejected',
            ]);
            $msg = 'Join request rejected.';
        }

        $this->syncMemberCount($community);

        return response()->json([
            'success' => true,
            'message' => $msg,
            'action' => $validated['action'],
            'member_count' => $community->fresh()->member_count,
            'pending_count' => $community->pendingMembers()->count(),
        ]);
    }

    public function updateRole(Request $request, Community $community, CommunityMember $membership)
    {
        $member = auth('member')->user();

        if (! $community->isOwner($member->id)) {
            return response()->json(['success' => false, 'message' => 'Only community owners can change roles.'], 403);
        }

        if ((int) $membership->community_id !== (int) $community->id) {
            return response()->json(['success' => false, 'message' => 'Invalid membership record.'], 400);
        }

        if ($membership->isOwner() || (int) $membership->member_id === (int) $community->owner_id) {
            return response()->json(['success' => false, 'message' => 'Cannot modify owner role.'], 422);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,moderator,member'],
        ]);

        $membership->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated to ' . ucfirst($validated['role']) . '.',
            'role' => $validated['role'],
        ]);
    }

    public function removeMember(Request $request, Community $community, CommunityMember $membership)
    {
        $member = auth('member')->user();

        if ((int) $membership->community_id !== (int) $community->id) {
            return response()->json(['success' => false, 'message' => 'Invalid membership record.'], 400);
        }

        if ($membership->isOwner() || (int) $membership->member_id === (int) $community->owner_id) {
            return response()->json(['success' => false, 'message' => 'Cannot remove community owner.'], 422);
        }

        if (! $community->isOwner($member->id)) {
            if (! $community->isAdmin($member->id) || $membership->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized to remove this member.'], 403);
            }
        }

        $membership->update(['status' => 'removed']);
        $this->syncMemberCount($community);

        return response()->json([
            'success' => true,
            'message' => 'Member removed from community.',
            'member_count' => $community->fresh()->member_count,
        ]);
    }

    private function syncMemberCount(Community $community): void
    {
        $community->syncMemberCount();
    }
}
