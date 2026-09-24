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
        if (!Schema::hasTable('ad_impressions')) {
            Schema::create('ad_impressions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
                $table->string('placement', 50)->default('social_feed')->index();
                $table->string('impression_key', 64)->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                // Performance composite indexes
                $table->index(['ad_campaign_id', 'created_at']);
                $table->index(['ad_campaign_id', 'member_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_impressions');
    }
};
