<?php

namespace App\Policies;

use App\Models\AdCampaign;
use App\Models\BusinessPage;
use App\Models\Member;

class AdCampaignPolicy
{
    /**
     * Determine whether the member can view the campaign.
     */
    public function view(Member $member, AdCampaign $campaign): bool
    {
        if ((int) $campaign->member_id === (int) $member->id) {
            return true;
        }

        $page = $campaign->businessPage;
        if ($page && ($page->isOwner($member->id) || $page->isTeamAdmin($member->id))) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the member can create campaigns for the business page.
     */
    public function create(Member $member, BusinessPage $businessPage): bool
    {
        return $businessPage->isOwner($member->id) || $businessPage->isTeamAdmin($member->id);
    }

    /**
     * Determine whether the member can update the campaign.
     */
    public function update(Member $member, AdCampaign $campaign): bool
    {
        if ((int) $campaign->member_id === (int) $member->id) {
            return true;
        }

        $page = $campaign->businessPage;
        return $page && ($page->isOwner($member->id) || $page->isTeamAdmin($member->id));
    }

    /**
     * Determine whether the member can submit the campaign for review.
     */
    public function submit(Member $member, AdCampaign $campaign): bool
    {
        if ($campaign->status !== AdCampaign::STATUS_DRAFT) {
            return false;
        }

        return $this->update($member, $campaign);
    }

    /**
     * Determine whether the member can pause the campaign.
     */
    public function pause(Member $member, AdCampaign $campaign): bool
    {
        if (!in_array($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_APPROVED], true)) {
            return false;
        }

        return $this->update($member, $campaign);
    }

    /**
     * Determine whether the member can resume the campaign.
     */
    public function resume(Member $member, AdCampaign $campaign): bool
    {
        if ($campaign->status !== AdCampaign::STATUS_PAUSED || $campaign->approval_status !== AdCampaign::APPROVAL_APPROVED) {
            return false;
        }

        return $this->update($member, $campaign);
    }

    /**
     * Determine whether the member can stop the campaign.
     */
    public function stop(Member $member, AdCampaign $campaign): bool
    {
        if (in_array($campaign->status, [AdCampaign::STATUS_STOPPED, AdCampaign::STATUS_CANCELLED, AdCampaign::STATUS_COMPLETED], true)) {
            return false;
        }

        return $this->update($member, $campaign);
    }
}
