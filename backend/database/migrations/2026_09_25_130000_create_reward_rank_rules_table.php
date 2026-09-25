<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create dedicated reward_rank_rules table
        if (!Schema::hasTable('reward_rank_rules')) {
            Schema::create('reward_rank_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rank_key', 32)->unique();
                $table->string('rank_name', 50);
                $table->unsignedInteger('priority')->default(1)->index();
                $table->unsignedInteger('referral_requirement')->default(0)->index();
                $table->unsignedInteger('team_requirement')->default(0)->index();
                $table->decimal('reward_amount', 10, 4)->default(0.0000);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('admins')->nullOnDelete();
            });

            // Seed the 5 canonical rank rules safely
            $now = now();
            DB::table('reward_rank_rules')->insert([
                [
                    'rank_key' => 'advertiser',
                    'rank_name' => 'Advertiser',
                    'priority' => 1,
                    'referral_requirement' => 0,
                    'team_requirement' => 1,
                    'reward_amount' => 0.0250,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rank_key' => 'influencer',
                    'rank_name' => 'Influencer',
                    'priority' => 2,
                    'referral_requirement' => 6,
                    'team_requirement' => 15,
                    'reward_amount' => 0.0350,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rank_key' => 'leaders',
                    'rank_name' => 'Leaders',
                    'priority' => 3,
                    'referral_requirement' => 15,
                    'team_requirement' => 50,
                    'reward_amount' => 0.0500,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rank_key' => 'pro_leaders',
                    'rank_name' => 'Pro Leaders',
                    'priority' => 4,
                    'referral_requirement' => 30,
                    'team_requirement' => 150,
                    'reward_amount' => 0.0750,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'rank_key' => 'master_leaders',
                    'rank_name' => 'Master Leaders',
                    'priority' => 5,
                    'referral_requirement' => 50,
                    'team_requirement' => 500,
                    'reward_amount' => 0.1000,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // 2. Add rank tracking columns to ad_rewards safely without destroying existing historical data
        if (Schema::hasTable('ad_rewards')) {
            Schema::table('ad_rewards', function (Blueprint $table) {
                if (!Schema::hasColumn('ad_rewards', 'rank_at_reward')) {
                    $table->string('rank_at_reward', 50)->nullable()->after('rule_version');
                }
                if (!Schema::hasColumn('ad_rewards', 'team_count')) {
                    $table->unsignedInteger('team_count')->nullable()->after('direct_verified_referral_count');
                }
                if (!Schema::hasColumn('ad_rewards', 'reward_rank_rule_id')) {
                    $table->unsignedBigInteger('reward_rank_rule_id')->nullable()->after('ad_reward_rule_id');
                    $table->foreign('reward_rank_rule_id')->references('id')->on('reward_rank_rules')->nullOnDelete();
                    $table->index('reward_rank_rule_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_rewards')) {
            Schema::table('ad_rewards', function (Blueprint $table) {
                if (Schema::hasColumn('ad_rewards', 'reward_rank_rule_id')) {
                    $table->dropForeign(['reward_rank_rule_id']);
                    $table->dropIndex(['reward_rank_rule_id']);
                    $table->dropColumn('reward_rank_rule_id');
                }
                if (Schema::hasColumn('ad_rewards', 'team_count')) {
                    $table->dropColumn('team_count');
                }
                if (Schema::hasColumn('ad_rewards', 'rank_at_reward')) {
                    $table->dropColumn('rank_at_reward');
                }
            });
        }

        Schema::dropIfExists('reward_rank_rules');
    }
};
