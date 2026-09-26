<?php

namespace App\Console\Commands;

use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\RewardRankRule;
use Illuminate\Console\Command;

class RewardRankGen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reward-rank-gen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 0. Fetch Dynamic Rules from reward_rank_rules table
        $influencerRule = RewardRankRule::where('rank_key', 'influencer')->orWhere('rank_name', 'Influencer')->first();
        $leaderRule = RewardRankRule::whereIn('rank_key', ['leaders', 'leader'])->orWhere('rank_name', 'like', 'Leader%')->first();
        $proLeaderRule = RewardRankRule::whereIn('rank_key', ['pro_leaders', 'pro_leader'])->orWhere('rank_name', 'like', 'Pro Leader%')->first();
        $masterLeaderRule = RewardRankRule::whereIn('rank_key', ['master_leaders', 'master_leader'])->orWhere('rank_name', 'like', 'Master Leader%')->first();

        $influencerDirects = $influencerRule?->referral_requirement;

        $leaderDirects = $leaderRule?->referral_requirement;
        $leaderTeam = $leaderRule?->team_requirement;

        $proLeaderDirects = $proLeaderRule?->referral_requirement;
        $proLeaderTeam = $proLeaderRule?->team_requirement;

        $masterLeaderDirects = $masterLeaderRule?->referral_requirement;
        $masterLeaderTeam = $masterLeaderRule?->team_requirement;

        // 1. Advertiser to Influencer
        $members = Member::where('reward_rank', 'Advertiser')->orWhereNull('reward_rank')->get();
        foreach ($members as $member) {
            $memberid = $member->user_id;
            $memData = Member::where('user_id', $memberid)->first();

            if ($memData) {
                // Mobile verified directs only
                $verifiedDirects = Member::where('introducer_id', $memData->user_id)
                    ->whereNotNull('mobile_verified_at')
                    ->count();

                if ($verifiedDirects >= $influencerDirects) {
                    $memData->reward_rank = 'Influencer';
                    $memData->save();
                }
            }
        }

        // 2. Influencer to Leader
        $members = Member::where('reward_rank', 'Influencer')->get();
        foreach ($members as $member) {
            $memberid = $member->user_id;
            $memData = Member::where('user_id', $memberid)->first();

            if ($memData) {
                // Mobile verified directs only
                $verifiedDirects = Member::where('introducer_id', $memData->user_id)
                    ->whereNotNull('mobile_verified_at')
                    ->count();

                $friendsCount = Friendship::forMember($memData->id)->accepted()->count();
                $ownedCommunityIds = Community::where('owner_id', $memData->id)->pluck('id');
                $communityCount = $ownedCommunityIds->isNotEmpty()
                    ? CommunityMember::whereIn('community_id', $ownedCommunityIds)->where('status', 'accepted')->count()
                    : 0;

                if ($verifiedDirects >= $leaderDirects && ($friendsCount >= $leaderTeam || $communityCount >= $leaderTeam)) {
                    $memData->reward_rank = 'Leader';
                    $memData->save();
                }
            }
        }

        // 3. Leader to Pro Leader
        $members = Member::where('reward_rank', 'Leader')->get();
        foreach ($members as $member) {
            $memberid = $member->user_id;
            $memData = Member::where('user_id', $memberid)->first();

            if ($memData) {
                // Mobile verified directs only
                $verifiedDirects = Member::where('introducer_id', $memData->user_id)
                    ->whereNotNull('mobile_verified_at')
                    ->count();

                $friendsCount = Friendship::forMember($memData->id)->accepted()->count();
                $ownedCommunityIds = Community::where('owner_id', $memData->id)->pluck('id');
                $communityCount = $ownedCommunityIds->isNotEmpty()
                    ? CommunityMember::whereIn('community_id', $ownedCommunityIds)->where('status', 'accepted')->count()
                    : 0;

                if ($verifiedDirects >= $proLeaderDirects && ($friendsCount >= $proLeaderTeam || $communityCount >= $proLeaderTeam)) {
                    $memData->reward_rank = 'Pro Leader';
                    $memData->save();
                }
            }
        }

        // 4. Pro Leader to Master Leader
        $members = Member::where('reward_rank', 'Pro Leader')->get();
        foreach ($members as $member) {
            $memberid = $member->user_id;
            $memData = Member::where('user_id', $memberid)->first();

            if ($memData) {
                // Mobile verified directs only
                $verifiedDirects = Member::where('introducer_id', $memData->user_id)
                    ->whereNotNull('mobile_verified_at')
                    ->count();

                $friendsCount = Friendship::forMember($memData->id)->accepted()->count();
                $ownedCommunityIds = Community::where('owner_id', $memData->id)->pluck('id');
                $communityCount = $ownedCommunityIds->isNotEmpty()
                    ? CommunityMember::whereIn('community_id', $ownedCommunityIds)->where('status', 'accepted')->count()
                    : 0;

                if ($verifiedDirects >= $masterLeaderDirects && ($friendsCount >= $masterLeaderTeam || $communityCount >= $masterLeaderTeam)) {
                    $memData->reward_rank = 'Master Leader';
                    $memData->save();
                }
            }
        }
    }
}
