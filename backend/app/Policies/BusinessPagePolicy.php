<?php

namespace App\Policies;

use App\Models\BusinessPage;
use App\Models\Member;

class BusinessPagePolicy
{
    public function view(?Member $member, BusinessPage $businessPage): bool
    {
        if ($businessPage->isPublic()) {
            return true;
        }

        return $businessPage->isOwner($member?->id);
    }

    public function create(Member $member): bool
    {
        return true;
    }

    public function update(Member $member, BusinessPage $businessPage): bool
    {
        return $businessPage->isOwner($member->id);
    }

    public function delete(Member $member, BusinessPage $businessPage): bool
    {
        return $businessPage->isOwner($member->id);
    }
}
