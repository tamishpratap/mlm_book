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
        if (Schema::hasTable('ad_reward_rules')) {
            Schema::table('ad_reward_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('ad_reward_rules', 'rule_type')) {
                    $table->string('rule_type', 32)->default('business_ad')->index()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_reward_rules')) {
            Schema::table('ad_reward_rules', function (Blueprint $table) {
                if (Schema::hasColumn('ad_reward_rules', 'rule_type')) {
                    $table->dropIndex(['rule_type']);
                    $table->dropColumn('rule_type');
                }
            });
        }
    }
};
