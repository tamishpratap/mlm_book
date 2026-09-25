<?php

namespace App\Services;

use App\Models\Member;
use App\Models\RewardRankRule;
use Illuminate\Support\Facades\DB;

class RewardRankResolver
{
    /**
     * Request-level memoized cache for team counts to prevent redundant tree traversals.
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
     * Get the authoritative TOTAL VERIFIED DOWNLINE TEAM count for a member.
     * Traverses the recursive tree of all verified members introduced under this member's referral ancestry.
     */
    public function getVerifiedTeamCount(Member $member): int
    {
        if (empty($member->user_id) || empty($member->id)) {
            return 0;
        }

        if (isset($this->teamCountMemory[$member->id])) {
            return $this->teamCountMemory[$member->id];
        }

        $userId = (string) $member->user_id;

        // Attempt recursive CTE first (fast and single-query in MySQL 8.0+ / MariaDB 10.2+)
        try {
            $count = (int) DB::selectOne("
                WITH RECURSIVE downline AS (
                    SELECT user_id, introducer_id, mobile_verified_at
                    FROM members
                    WHERE introducer_id = :userId
                    UNION ALL
                    SELECT m.user_id, m.introducer_id, m.mobile_verified_at
                    FROM members m
                    INNER JOIN downline d ON m.introducer_id = d.user_id
                )
                SELECT COUNT(*) as total_count FROM downline WHERE mobile_verified_at IS NOT NULL
            ", ['userId' => $userId])->total_count;

            $this->teamCountMemory[$member->id] = $count;
            return $count;
        } catch (\Throwable $e) {
            // Robust iterative fallback if recursive CTE is not supported by database engine
            $count = $this->calculateTeamIteratively($userId);
            $this->teamCountMemory[$member->id] = $count;
            return $count;
        }
    }

    /**
     * Iterative BFS traversal fallback for calculating verified downline team count.
     */
    protected function calculateTeamIteratively(string $rootUserId): int
    {
        $currentLevelUserIds = [$rootUserId];
        $visited = [strtolower($rootUserId) => true];
        $totalVerifiedCount = 0;
        $maxDepth = 50;
        $depth = 0;

        while (!empty($currentLevelUserIds) && $depth < $maxDepth) {
            $depth++;

            $nextLevelMembers = Member::query()
                ->whereIn('introducer_id', $currentLevelUserIds)
                ->get(['id', 'user_id', 'mobile_verified_at']);

            if ($nextLevelMembers->isEmpty()) {
                break;
            }

            $nextLevelUserIds = [];
            foreach ($nextLevelMembers as $descendant) {
                $uid = strtolower((string) $descendant->user_id);
                if (isset($visited[$uid])) {
                    // Prevent cycle loop in corrupt legacy data
                    continue;
                }
                $visited[$uid] = true;

                if ($descendant->mobile_verified_at !== null) {
                    $totalVerifiedCount++;
                }

                $nextLevelUserIds[] = (string) $descendant->user_id;
            }

            $currentLevelUserIds = $nextLevelUserIds;
        }

        return $totalVerifiedCount;
    }

    /**
     * Resolve the eligible rank and reward for a given set of referral and team counts.
     * Pure resolver: evaluates against active Admin-configured rules in descending priority order.
     * Highest qualifying rank wins!
     *
     * @param int $referrals Direct verified referral count
     * @param int $team Total verified downline team count
     */
    public function resolveForMetrics(int $referrals, int $team): array
    {
        $safeReferrals = max(0, $referrals);
        $safeTeam = max(0, $team);

        $activeRules = RewardRankRule::getActiveRules();

        if ($activeRules->isEmpty()) {
            return [
                'success' => false,
                'status' => 'no_active_rules',
                'eligible' => false,
                'rank' => null,
                'rank_key' => null,
                'priority' => null,
                'referral_requirement' => null,
                'team_requirement' => null,
                'user_referrals' => $safeReferrals,
                'user_team' => $safeTeam,
                'reward' => 0.0000,
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'rule_id' => null,
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

        if (!$matchedRule) {
            return [
                'success' => true,
                'status' => 'no_qualifying_rank',
                'eligible' => false,
                'rank' => null,
                'rank_key' => null,
                'priority' => null,
                'referral_requirement' => null,
                'team_requirement' => null,
                'user_referrals' => $safeReferrals,
                'user_team' => $safeTeam,
                'reward' => 0.0000,
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'rule_id' => null,
                'message' => 'User metrics do not qualify for any active rank.',
            ];
        }

        $rewardAmt = (float) $matchedRule->reward_amount;

        return [
            'success' => true,
            'status' => 'eligible',
            'eligible' => true,
            'rank' => $matchedRule->rank_name,
            'rank_key' => $matchedRule->rank_key,
            'priority' => (int) $matchedRule->priority,
            'referral_requirement' => (int) $matchedRule->referral_requirement,
            'team_requirement' => (int) $matchedRule->team_requirement,
            'user_referrals' => $safeReferrals,
            'user_team' => $safeTeam,
            'reward' => $rewardAmt,
            'reward_amount_usd' => $rewardAmt,
            'reward_amount_exact' => number_format($rewardAmt, 4, '.', ''),
            'currency' => 'USD',
            'currency_symbol' => '$',
            'rule_id' => $matchedRule->id,
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
                'reward_amount_usd' => 0.0000,
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
                'rank_key' => null,
                'priority' => null,
                'referral_requirement' => null,
                'team_requirement' => null,
                'user_referrals' => 0,
                'user_team' => 0,
                'reward' => 0.0000,
                'reward_amount_usd' => 0.0000,
                'reward_amount_exact' => '0.0000',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'rule_id' => null,
                'message' => 'Member must complete mobile verification to qualify for rank rewards.',
            ];
        }

        $referrals = $this->getVerifiedDirectReferralCount($member);
        $team = $this->getVerifiedTeamCount($member);

        $result = $this->resolveForMetrics($referrals, $team);
        $result['member_id'] = $member->id;
        $result['user_id'] = $member->user_id;

        return $result;
    }
}
