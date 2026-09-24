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
                $table->unique(['ad_campaign_id', 'member_id'], 'ad_rewards_campaign_member_unique');
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
                $table->dropUnique('ad_rewards_campaign_member_unique');
            });
        }
    }
};