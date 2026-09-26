<?php

namespace App\Services;

use App\Models\Friendship;
use App\Models\Member;
use App\Models\RewardRankRule;
use Illuminate\Support\Facades\DB;

class RewardRankResolver
{
    /**
     * Request-level memoized cache for team/connections counts to prevent redundant queries.
     *
     * @var array<int, int>
     */
    protected array $teamCountMemory = [];

    /**
     * Get the authoritative DIRECT VERIFIED referral count for a member.
     * Strictly:
     * 1. Direct introducer relationship (introducer_id = member.user_id).
     * 2. Referred member has completed mobile/WhatsApp verification (mobile_verified_at is not null).
     */
    public function getVerifiedDirectReferralCount(Member $member): int
    {
        if (empty($member->user_id)) {
            return 0;
        }

        return (int) Member::query()
            ->where('introducer_id', $member->user_id)
            ->whereNotNull('mobile_verified_at')
            ->count();
    }

    /**
     * Get the authoritative VERIFIED CONNECTIONS count for a member.
     * Strictly:
     * 1. Friendship status is 'accepted'.
     * 2. The connected member has completed mobile/WhatsApp verification (mobile_verified_at is not null).
     * 3. The connected member is not blocked.
     * 4. Unverified members, pending, rejected, or blocked connections MUST NOT contribute to count.
     */
    public function getVerifiedConnectionsCount(Member $member): int
    {
        if (empty($member->id)) {
            return 0;
        }

        if (isset($this->teamCountMemory[$member->id])) {
            return $this->teamCountMemory[$member->id];
        }

        $count = count($member->acceptedConnectionIds(true));
        $this->teamCountMemory[$member->id] = $count;

        return $count;
    }

    /**
     * Get the authoritative TOTAL TEAM count for rank calculation.
     * VERY IMPORTANT: For rank calculation: Team Count = Verified Connections.
     * teamCount = count(member's verified connections).
     * Unverified members MUST NOT contribute to Team Count.
     */
    public function getVerifiedTeamCount(Member $member): int
    {
        return $this->getVerifiedConnectionsCount($member);
    }

    /**
     * Resolve the eligible rank and reward for a given set of referral and team counts.
     * Pure resolver: evaluates against active Admin-configured rules in descending priority order.
     * Highest qualifying rank wins!
     *
     * @param int $referrals Direct verified referral count
     * @param int $team Total verified connections (Team Count = Verified Connections)
     */
    public function resolveForMetrics(int $referrals, int $team): array
    {
        $safeReferrals = max(0, $referrals);
        $safeTeam = max(0, $team);

        $activeRules = RewardRankRule::getActiveRules();

        if ($activeRules->isEmpty()) {
            return [
                'success' => true,
                'status' => 'no_active_rules',
                'eligible' => false,
                'rank' => null,
                'current_rank' => 'No Rank',
                'rank_key' => null,
                'priority' => null,
                'referral_requirement' => null,
                'team_requirement' => null,
                'user_referrals' => $safeReferrals,
                'user_team' => $safeTeam,
                'verified_connections' => $safeTeam,
                'reward' => 0.0000,
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'rule_id' => null,
                'next_rank' => null,
                'message' => 'No active reward rank rules are configured in the system.',
            ];
        }

        // Sort descending by priority so the highest rank is evaluated first
        $sortedDesc = $activeRules->sortByDesc('priority');

        $matchedRule = null;
        foreach ($sortedDesc as $rule) {
            $reqRef = (int) $rule->referral_requirement;
            $reqTeam = (int) $rule->team_requirement;

            if ($safeReferrals >= $reqRef && $safeTeam >= $reqTeam) {
                $matchedRule = $rule;
                break; // Highest qualifying rank wins
            }
        }

        $activeRulesAsc = $activeRules->sortBy('priority');

        if (!$matchedRule) {
            $nextRule = $activeRulesAsc->first();
            $nextRankInfo = $nextRule ? [
                'rank' => $nextRule->rank_name,
                'rank_key' => $nextRule->rank_key,
                'priority' => (int) $nextRule->priority,
                'referral_requirement' => (int) $nextRule->referral_requirement,
                'team_requirement' => (int) $nextRule->team_requirement,
                'reward' => (float) $nextRule->reward_amount,
                'reward_amount_usd' => (float) $nextRule->reward_amount,
                'reward_amount_exact' => number_format((float) $nextRule->reward_amount, 4, '.', ''),
                'referrals_needed' => max(0, (int) $nextRule->referral_requirement - $safeReferrals),
                'team_needed' => max(0, (int) $nextRule->team_requirement - $safeTeam),
                'connections_needed' => max(0, (int) $nextRule->team_requirement - $safeTeam),
            ] : null;

            return [
                'success' => true,
                'status' => 'no_qualifying_rank',
                'eligible' => false,
                'rank' => null,
                'current_rank' => 'No Rank',
                'rank_key' => null,
                'priority' => null,
                'referral_requirement' => null,
                'team_requirement' => null,
                'user_referrals' => $safeReferrals,
                'user_team' => $safeTeam,
                'verified_connections' => $safeTeam,
                'reward' => 0.0000,
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'rule_id' => null,
                'next_rank' => $nextRankInfo,
                'message' => 'User metrics do not qualify for any active rank.',
            ];
        }

        $rewardAmt = (float) $matchedRule->reward_amount;
        $nextRule = $activeRulesAsc->first(fn ($r) => (int) $r->priority > (int) $matchedRule->priority);
        $nextRankInfo = $nextRule ? [
            'rank' => $nextRule->rank_name,
            'rank_key' => $nextRule->rank_key,
            'priority' => (int) $nextRule->priority,
            'referral_requirement' => (int) $nextRule->referral_requirement,
            'team_requirement' => (int) $nextRule->team_requirement,
            'reward' => (float) $nextRule->reward_amount,
            'reward_amount_usd' => (float) $nextRule->reward_amount,
            'reward_amount_exact' => number_format((float) $nextRule->reward_amount, 4, '.', ''),
            'referrals_needed' => max(0, (int) $nextRule->referral_requirement - $safeReferrals),
            'team_needed' => max(0, (int) $nextRule->team_requirement - $safeTeam),
            'connections_needed' => max(0, (int) $nextRule->team_requirement - $safeTeam),
        ] : null;

        return [
            'success' => true,
            'status' => 'eligible',
            'eligible' => true,
            'rank' => $matchedRule->rank_name,
            'current_rank' => $matchedRule->rank_name,
            'rank_key' => $matchedRule->rank_key,
            'priority' => (int) $matchedRule->priority,
            'referral_requirement' => (int) $matchedRule->referral_requirement,
            'team_requirement' => (int) $matchedRule->team_requirement,
            'user_referrals' => $safeReferrals,
            'user_team' => $safeTeam,
            'verified_connections' => $safeTeam,
            'reward' => $rewardAmt,
            'reward_amount_usd' => $rewardAmt,
            'reward_amount_exact' => number_format($rewardAmt, 4, '.', ''),
            'currency' => 'USD',
            'currency_symbol' => '$',
            'rule_id' => $matchedRule->id,
            'next_rank' => $nextRankInfo,
            'message' => "Qualified for {$matchedRule->rank_name}.",
        ];
    }

    /**
     * Resolve the eligible rank and reward dynamically for an authenticated Member.
     * Gated by mobile verification.
     */
    public function resolveForMember(Member $member): array
    {
        if (empty($member->id) || empty($member->user_id)) {
            return [
                'success' => false,
                'status' => 'invalid_member',
                'eligible' => false,
                'rank' => null,
                'current_rank' => 'No Rank',
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'next_rank' => null,
                'message' => 'Invalid member.',
            ];
        }

        // Mobile verification gate
        if (!$member->isMobileVerified()) {
            return [
                'success' => false,
                'status' => 'unverified_mobile',
                'eligible' => false,
                'rank' => null,
                'current_rank' => 'No Rank',
                'rank_key' => null,
                'priority' => null,
                'referral_requirement' => null,
                'team_requirement' => null,
                'user_referrals' => 0,
                'user_team' => 0,
                'verified_connections' => 0,
                'reward' => 0.0000,
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'rule_id' => null,
                'next_rank' => null,
                'message' => 'Member must complete mobile verification to qualify for rank rewards.',
            ];
        }

        $referrals = $this->getVerifiedDirectReferralCount($member);
        $team = $this->getVerifiedConnectionsCount($member);

        $result = $this->resolveForMetrics($referrals, $team);
        $result['member_id'] = $member->id;
        $result['user_id'] = $member->user_id;

        return $result;
    }
}
