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
        // 1. Create ad_rewards table for reward ledger
        if (!Schema::hasTable('ad_rewards')) {
            Schema::create('ad_rewards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ad_campaign_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->decimal('reward_amount_usd', 12, 2)->unsigned()->default(0.05);
                $table->string('qualifying_event_id', 100)->nullable()->index();
                $table->string('landing_page_url', 2048)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('status', 30)->default('credited')->index();
                $table->timestamps();

                $table->foreign('ad_campaign_id')->references('id')->on('ad_campaigns')->cascadeOnDelete();
                $table->foreign('member_id')->references('id')->on('members')->cascadeOnDelete();

                $table->index(['ad_campaign_id', 'member_id']);
                $table->unique(['ad_campaign_id', 'member_id', 'qualifying_event_id'], 'ad_reward_unique_event');
            });
        }

        // 2. Add reward_balance to members table if not present
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (!Schema::hasColumn('members', 'reward_balance')) {
                    $table->decimal('reward_balance', 12, 2)->unsigned()->default(0.00)->after('ad_balance');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (Schema::hasColumn('members', 'reward_balance')) {
                    $table->dropColumn('reward_balance');
                }
            });
        }

        Schema::dropIfExists('ad_rewards');
    }
};
