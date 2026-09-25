<?php

namespace Database\Seeders;

use App\Models\AdRewardRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RewardRuleSeeder extends Seeder
{
    /**
     * Seed centralized reward rules.
     */
    public function run(): void
    {
        $hasCentral = DB::table('ad_reward_rules')
            ->where('rule_type', 'central')
            ->exists();

        if (!$hasCentral) {
            DB::table('ad_reward_rules')->insert([
                [
                    'rule_type' => 'central',
                    'min_referrals' => 0,
                    'max_referrals' => 5,
                    'reward_amount' => 0.0250,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'rule_type' => 'central',
                    'min_referrals' => 6,
                    'max_referrals' => 14,
                    'reward_amount' => 0.0350,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'rule_type' => 'central',
                    'min_referrals' => 15,
                    'max_referrals' => null,
                    'reward_amount' => 0.0500,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (class_exists(AdRewardRule::class)) {
            AdRewardRule::clearCache();
        }
    }
}
