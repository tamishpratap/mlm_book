<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ad_rewards')) {
            Schema::table('ad_rewards', function (Blueprint $table) {
                if (!Schema::hasColumn('ad_rewards', 'ad_reward_rule_id')) {
                    $table->unsignedBigInteger('ad_reward_rule_id')->nullable()->after('member_id');
                    $table->foreign('ad_reward_rule_id')->references('id')->on('ad_reward_rules')->nullOnDelete();
                    $table->index('ad_reward_rule_id');
                }

                if (!Schema::hasColumn('ad_rewards', 'direct_verified_referral_count')) {
                    $table->unsignedInteger('direct_verified_referral_count')->nullable()->after('ad_reward_rule_id');
                }

                if (!Schema::hasColumn('ad_rewards', 'rule_min_referrals')) {
                    $table->unsignedInteger('rule_min_referrals')->nullable()->after('direct_verified_referral_count');
                }

                if (!Schema::hasColumn('ad_rewards', 'rule_max_referrals')) {
                    $table->unsignedInteger('rule_max_referrals')->nullable()->after('rule_min_referrals');
                }

                if (!Schema::hasColumn('ad_rewards', 'rule_version')) {
                    $table->string('rule_version', 50)->nullable()->after('rule_max_referrals');
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
                if (Schema::hasColumn('ad_rewards', 'ad_reward_rule_id')) {
                    $table->dropForeign(['ad_reward_rule_id']);
                    $table->dropIndex(['ad_reward_rule_id']);
                    $table->dropColumn('ad_reward_rule_id');
                }

                if (Schema::hasColumn('ad_rewards', 'direct_verified_referral_count')) {
                    $table->dropColumn('direct_verified_referral_count');
                }

                if (Schema::hasColumn('ad_rewards', 'rule_min_referrals')) {
                    $table->dropColumn('rule_min_referrals');
                }

                if (Schema::hasColumn('ad_rewards', 'rule_max_referrals')) {
                    $table->dropColumn('rule_max_referrals');
                }

                if (Schema::hasColumn('ad_rewards', 'rule_version')) {
                    $table->dropColumn('rule_version');
                }
            });
        }
    }
};
