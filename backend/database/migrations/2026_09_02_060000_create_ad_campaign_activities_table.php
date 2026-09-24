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
        if (!Schema::hasTable('ad_campaign_activities')) {
            Schema::create('ad_campaign_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
                $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->string('action', 50)->index();
                $table->string('action_label', 100)->nullable();
                $table->string('qualifying_event_id', 100)->nullable()->index();
                $table->foreignId('ad_reward_id')->nullable()->constrained('ad_rewards')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                // Composite performance indexes for high-volume reporting and filtering
                $table->index(['ad_campaign_id', 'created_at']);
                $table->index(['ad_campaign_id', 'action']);
                $table->index(['ad_campaign_id', 'member_id']);
                $table->index(['business_page_id', 'created_at']);
                $table->index(['member_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaign_activities');
    }
};
