<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Member;

class EventRewardResolver
{
    public function __construct(
        protected RewardRuleResolver $ruleResolver
    ) {}

    /**
     * Get the authoritative DIRECT VERIFIED referral count for a member.
     * Strictly:
     * 1. Direct introducer relationship (introducer_id = member.user_id).
     * 2. Referred member has completed mobile/WhatsApp verification (mobile_verified_at is not null).
     * Excludes: unverified registrations, indirect downline, followers, connections.
     */
    public function getVerifiedDirectReferralCount(Member $member): int
    {
        return $this->ruleResolver->getVerifiedDirectReferralCount($member);
    }

    /**
     * Resolve the authoritative reward amount for a member within an Event context.
     * Validates member and event contexts, verifies campaign linkage if provided,
     * derives verified direct referral count server-side, and resolves matching active Event rule.
     *
     * Pure reader / resolver: Strictly ZERO side effects.
     * Does NOT credit Reward Wallet.
     * Does NOT debit campaign budget.
     * Does NOT create participant or Interested records.
     * Does NOT mutate referral or verification data.
     */
    public function resolveForEventMember(
        Member|int|string $member,
        Event|int|string $event,
        AdCampaign|int|string|null $campaign = null
    ): array {
        // 1. Member Context Validation
        $resolvedMember = $member instanceof Member ? $member : Member::find($member);
        if (!$resolvedMember || empty($resolvedMember->id) || empty($resolvedMember->user_id)) {
            return [
                'success' => false,
                'status' => 'invalid_member',
                'rule_type' => AdRewardRule::TYPE_EVENT,
                'message' => 'The specified member does not exist or is invalid.',
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ];
        }

        // Verified Audience Gate: Member must have completed mobile/WhatsApp verification
        if (!$resolvedMember->isMobileVerified()) {
            return [
                'success' => false,
                'status' => 'unverified_member',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'rule_type' => AdRewardRule::TYPE_EVENT,
                'message' => 'Please verify your phone number first before proceeding.',
                'verified_required' => true,
                'needs_verification' => true,
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ];
        }

        // 2. Event Context Validation
        $resolvedEvent = $event instanceof Event ? $event : Event::find($event);
        if (!$resolvedEvent || empty($resolvedEvent->id)) {
            return [
                'success' => false,
                'status' => 'invalid_event',
                'rule_type' => AdRewardRule::TYPE_EVENT,
                'message' => 'The specified event does not exist.',
                'eligible' => false,
                'reward_amount_usd' => 0.00,
            ];
        }

        // 3. Campaign Context Validation (if provided or exists on event)
        $resolvedCampaign = null;
        if ($campaign !== null) {
            $resolvedCampaign = $campaign instanceof AdCampaign ? $campaign : AdCampaign::find($campaign);
            if (!$resolvedCampaign) {
                return [
                    'success' => false,
                    'status' => 'campaign_not_found',
                    'rule_type' => AdRewardRule::TYPE_EVENT,
                    'message' => 'The specified campaign does not exist.',
                    'eligible' => false,
                    'event_id' => $resolvedEvent->id,
                    'reward_amount_usd' => 0.00,
                ];
            }

            // Scope & Association Check
            if ($resolvedCampaign->campaign_type !== AdCampaign::TYPE_EVENT || (int) $resolvedCampaign->event_id !== (int) $resolvedEvent->id) {
                return [
                    'success' => false,
                    'status' => 'campaign_context_mismatch',
                    'rule_type' => AdRewardRule::TYPE_EVENT,
                    'message' => 'The specified campaign does not belong to this event or is not an event campaign.',
                    'eligible' => false,
                    'event_id' => $resolvedEvent->id,
                    'campaign_id' => $resolvedCampaign->id,
                    'reward_amount_usd' => 0.00,
                ];
            }
        } else {
            // Check if event has a campaign associated
            $resolvedCampaign = $resolvedEvent->campaign()->first();
        }

        // Authoritative Organizer / Owner Exclusion
        if ((int) $resolvedEvent->organizer_id === (int) $resolvedMember->id || ($resolvedCampaign && (int) $resolvedCampaign->member_id === (int) $resolvedMember->id)) {
            return [
                'success' => true,
                'status' => 'organizer_self_reward_forbidden',
                'rule_type' => AdRewardRule::TYPE_EVENT,
                'is_organizer' => true,
                'message' => 'Event organizers cannot earn rewards from their own event campaigns.',
                'eligible' => false,
                'reward_amount_usd' => 0.00,
                'event_id' => $resolvedEvent->id,
                'event_title' => $resolvedEvent->title,
                'campaign_id' => $resolvedCampaign?->id,
            ];
        }

        // 4. Retrieve Authoritative Verified Direct Referral Count (Server-side)
        $verifiedCount = $this->getVerifiedDirectReferralCount($resolvedMember);

        // 5. Resolve against Active Event Reward Rules
        $resolution = $this->resolveForCount($verifiedCount, $resolvedMember);

        // Enrich resolution payload with event and campaign context
        $resolution['event_id'] = $resolvedEvent->id;
        $resolution['event_title'] = $resolvedEvent->title;
        $resolution['campaign_id'] = $resolvedCampaign?->id;

        if (!empty($resolution['snapshot'])) {
            $resolution['snapshot']['event_id'] = $resolvedEvent->id;
            $resolution['snapshot']['campaign_id'] = $resolvedCampaign?->id;
        }

        return $resolution;
    }

    /**
     * Resolve reward tier given a verified direct referral count against active Event rules.
     * Fails closed if 0 matches, > 1 matches, unconfigured reward, or invalid active set.
     */
    public function resolveForCount(int $count, ?Member $member = null): array
    {
        return $this->ruleResolver->resolveForCount($count, $member, AdRewardRule::TYPE_EVENT);
    }
}
