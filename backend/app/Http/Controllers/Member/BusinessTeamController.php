<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\BusinessInvitation;
use App\Models\BusinessPage;
use App\Models\BusinessTeamMember;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessTeamController extends Controller
{
    public function index(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action. Only page owner or admins can access Team Management.'], 403);
            }
            abort(403, 'Unauthorized action. Only page owner or admins can access Team Management.');
        }

        $businessPage->load('owner');

        $activeMembers = BusinessTeamMember::query()
            ->where('business_page_id', $businessPage->id)
            ->where('status', 'active')
            ->with('member')
            ->latest('joined_at')
            ->get();

        $pendingInvitations = BusinessInvitation::query()
            ->where('business_page_id', $businessPage->id)
            ->where('status', 'pending')
            ->with(['inviter', 'invitee'])
            ->latest()
            ->get();

        // Get owner info
        $owner = $businessPage->owner;

        // Get friend list for easy invitation selector
        $friendIds = $currentMember->acceptedFriendIds();
        $friends = Member::whereIn('id', $friendIds)
            ->where('id', '!=', $businessPage->member_id)
            ->whereNotIn('id', $activeMembers->pluck('member_id')->toArray())
            ->whereNotIn('id', $pendingInvitations->pluck('invitee_id')->toArray())
            ->get();

        $roles = BusinessTeamMember::ROLES;
        $isOwner = $businessPage->isOwner($currentMember->id);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'business_page' => $businessPage,
                'active_members' => $activeMembers,
                'pending_invitations' => $pendingInvitations,
                'owner' => $owner,
                'friends' => $friends,
                'roles' => $roles,
                'is_owner' => $isOwner,
            ]);
        }

        return view('member.business-pages.team.index', compact(
            'businessPage',
            'activeMembers',
            'pendingInvitations',
            'owner',
            'friends',
            'roles',
            'isOwner'
        ));
    }

    public function invite(Request $request, BusinessPage $businessPage)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Only page owner or admins can invite team members.'], 403);
        }

        $validated = $request->validate([
            'invitee_id' => ['required', 'integer', 'exists:members,id'],
            'role' => ['required', 'string', 'in:admin,editor,moderator,analyst'],
        ]);

        $invitee = Member::findOrFail($validated['invitee_id']);

        // Admins cannot promote/invite another Admin (only Owner can invite Admins)
        if ($validated['role'] === 'admin' && ! $businessPage->isOwner($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Only the Page Owner can assign Admin roles.'], 403);
        }

        // Cannot invite owner
        if ($businessPage->isOwner($invitee->id)) {
            return response()->json(['success' => false, 'message' => 'The page owner is already on the team.'], 422);
        }

        // Cannot invite existing active member
        if ($businessPage->isTeamMember($invitee->id)) {
            return response()->json(['success' => false, 'message' => $invitee->name . ' is already a team member.'], 422);
        }

        // Cannot invite if pending invitation exists
        $existingInvite = BusinessInvitation::where('business_page_id', $businessPage->id)
            ->where('invitee_id', $invitee->id)
            ->where('status', 'pending')
            ->first();

        if ($existingInvite) {
            return response()->json(['success' => false, 'message' => 'An invitation is already pending for ' . $invitee->name . '.'], 422);
        }

        $invitation = BusinessInvitation::create([
            'business_page_id' => $businessPage->id,
            'inviter_id' => $currentMember->id,
            'invitee_id' => $invitee->id,
            'role' => $validated['role'],
            'status' => 'pending',
            'invite_code' => Str::random(12),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Team invitation sent to ' . $invitee->name . '!',
            'invitation' => $invitation->load(['invitee', 'inviter']),
        ]);
    }

    public function cancelInvite(Request $request, BusinessPage $businessPage, BusinessInvitation $invitation)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $invitation->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid invitation.'], 422);
        }

        $invitation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled successfully.',
        ]);
    }

    public function changeRole(Request $request, BusinessPage $businessPage, BusinessTeamMember $teamMember)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $teamMember->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid team member.'], 422);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,editor,moderator,analyst'],
        ]);

        // Cannot change owner role
        if ($teamMember->isOwner()) {
            return response()->json(['success' => false, 'message' => 'Cannot change the Page Owner role.'], 403);
        }

        // Admins cannot change another Admin or promote to Admin
        if (! $businessPage->isOwner($currentMember->id)) {
            if ($teamMember->role === 'admin' || $validated['role'] === 'admin') {
                return response()->json(['success' => false, 'message' => 'Only the Page Owner can manage Admin roles.'], 403);
            }
        }

        $teamMember->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated to ' . BusinessTeamMember::ROLES[$validated['role']] . ' successfully!',
            'role_label' => BusinessTeamMember::ROLES[$validated['role']],
        ]);
    }

    public function removeMember(Request $request, BusinessPage $businessPage, BusinessTeamMember $teamMember)
    {
        $currentMember = auth('member')->user();

        if (! $businessPage->isTeamAdmin($currentMember->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ((int) $teamMember->business_page_id !== (int) $businessPage->id) {
            return response()->json(['success' => false, 'message' => 'Invalid team member.'], 422);
        }

        // Cannot remove owner
        if ($teamMember->isOwner() || $businessPage->isOwner($teamMember->member_id)) {
            return response()->json(['success' => false, 'message' => 'The Page Owner cannot be removed from the team.'], 403);
        }

        // Admins cannot remove another Admin
        if (! $businessPage->isOwner($currentMember->id) && $teamMember->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Only the Page Owner can remove an Admin.'], 403);
        }

        $memberName = $teamMember->member->name ?? 'Member';
        $teamMember->delete();

        return response()->json([
            'success' => true,
            'message' => $memberName . ' removed from team.',
        ]);
    }

    public function acceptInvite(Request $request, BusinessInvitation $invitation)
    {
        $currentMember = auth('member')->user();

        if (! $currentMember || ! $currentMember->isMobileVerified()) {
            return response()->json([
                'success' => false,
                'message' => EnsureMemberMobileVerified::UNVERIFIED_MESSAGE,
            ], 403);
        }

        if ((int) $invitation->invitee_id !== (int) $currentMember->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized invitation response.'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This invitation is no longer active.'], 422);
        }

        BusinessTeamMember::updateOrCreate(
            [
                'business_page_id' => $invitation->business_page_id,
                'member_id' => $currentMember->id,
            ],
            [
                'role' => $invitation->role,
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        $invitation->update(['status' => 'accepted']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted! You are now on the team for ' . ($invitation->businessPage->page_name ?? 'this page') . '.',
        ]);
    }

    public function rejectInvite(Request $request, BusinessInvitation $invitation)
    {
        $currentMember = auth('member')->user();

        if ((int) $invitation->invitee_id !== (int) $currentMember->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized invitation response.'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This invitation is no longer active.'], 422);
        }

        $invitation->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation declined.',
        ]);
    }
}
