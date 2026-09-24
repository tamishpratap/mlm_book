<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Member;

class CommunityPolicy
{
    public function view(?Member $member, Community $community): bool
    {
        if ($community->isPublic() || $community->isPrivate() || $community->isInviteOnly()) {
            return true;
        }

        return $community->isOwner($member?->id) || $community->isMember($member?->id);
    }

    public function create(Member $member): bool
    {
        return true;
    }

    public function update(Member $member, Community $community): bool
    {
        return $community->isOwner($member->id);
    }

    public function delete(Member $member, Community $community): bool
    {
        return $community->isOwner($member->id);
    }

    public function join(Member $member, Community $community): bool
    {
        // Secret communities cannot be joined directly
        if ($community->isSecret()) {
            return false;
        }

        // Already an active member or owner
        if ($community->isMember($member->id)) {
            return false;
        }

        // Already pending
        if ($community->isPending($member->id)) {
            return false;
        }

        return true;
    }

    public function leave(Member $member, Community $community): bool
    {
        // Owner cannot leave without transferring ownership
        if ($community->isOwner($member->id)) {
            return false;
        }

        return $community->isMember($member->id);
    }

    public function manageRequests(Member $member, Community $community): bool
    {
        return $community->isAdmin($member->id);
    }

    public function updateRole(Member $member, Community $community, CommunityMember $targetMember): bool
    {
        // Only owner can promote/demote admins or moderators
        if (! $community->isOwner($member->id)) {
            return false;
        }

        // Target must belong to the same community
        if ((int) $targetMember->community_id !== (int) $community->id) {
            return false;
        }

        // Cannot change owner role
        if ($targetMember->isOwner() || (int) $targetMember->member_id === (int) $community->owner_id) {
            return false;
        }

        return true;
    }

    public function removeMember(Member $member, Community $community, CommunityMember $targetMember): bool
    {
        // Target must belong to the same community
        if ((int) $targetMember->community_id !== (int) $community->id) {
            return false;
        }

        // Cannot remove owner
        if ($targetMember->isOwner() || (int) $targetMember->member_id === (int) $community->owner_id) {
            return false;
        }

        // Admin can remove normal members, but only Owner can remove Admins
        if ($community->isOwner($member->id)) {
            return true;
        }

        if ($community->isAdmin($member->id)) {
            return ! $targetMember->isAdmin();
        }

        return false;
    }

    public function createPost(Member $member, Community $community): bool
    {
        return $community->isMember($member->id);
    }

    public function pinPost(Member $member, Community $community): bool
    {
        return $community->isAdmin($member->id);
    }

    public function announcePost(Member $member, Community $community): bool
    {
        return $community->isAdmin($member->id);
    }

    public function deleteCommunityPost(Member $member, Community $community, Post $post): bool
    {
        if ((int) $post->community_id !== (int) $community->id) {
            return false;
        }

        if ((int) $post->member_id === (int) $member->id) {
            return true;
        }

        return $community->isModerator($member->id);
    }

    public function moderate(Member $member, Community $community): bool
    {
        return $community->isModerator($member->id);
    }

    public function manageSettings(Member $member, Community $community): bool
    {
        return $community->isAdmin($member->id);
    }

    public function banMember(Member $member, Community $community, CommunityMember $targetMember): bool
    {
        if ((int) $targetMember->community_id !== (int) $community->id) return false;
        if ($targetMember->isOwner() || (int) $targetMember->member_id === (int) $community->owner_id) return false;

        if ($community->isOwner($member->id)) return true;

        if ($community->isAdmin($member->id)) {
            return ! $targetMember->isAdmin();
        }

        return false;
    }

    public function muteMember(Member $member, Community $community, CommunityMember $targetMember): bool
    {
        if ((int) $targetMember->community_id !== (int) $community->id) return false;
        if ($targetMember->isOwner() || (int) $targetMember->member_id === (int) $community->owner_id) return false;

        return $community->isModerator($member->id);
    }

    public function warnMember(Member $member, Community $community, CommunityMember $targetMember): bool
    {
        if ((int) $targetMember->community_id !== (int) $community->id) return false;
        if ($targetMember->isOwner() || (int) $targetMember->member_id === (int) $community->owner_id) return false;

        return $community->isModerator($member->id);
    }

    public function transferOwnership(Member $member, Community $community): bool
    {
        return $community->isOwner($member->id);
    }

    public function viewAnalytics(Member $member, Community $community): bool
    {
        return $community->isModerator($member->id);
    }
}
