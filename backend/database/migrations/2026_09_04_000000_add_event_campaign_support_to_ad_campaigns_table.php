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
        if (Schema::hasTable('ad_campaigns')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('ad_campaigns', 'campaign_type')) {
                    $table->string('campaign_type', 32)->default('business_ad')->after('campaign_id')->index();
                }

                if (!Schema::hasColumn('ad_campaigns', 'event_id')) {
                    $table->foreignId('event_id')->nullable()->after('post_id')->constrained('events')->nullOnDelete();
                }
            });

            // Modify business_page_id to be nullable so event campaigns do not require a business page
            try {
                Schema::table('ad_campaigns', function (Blueprint $table) {
                    $table->unsignedBigInteger('business_page_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Fallback raw statement for MySQL if change() needs direct alter
                try {
                    DB::statement('ALTER TABLE ad_campaigns MODIFY business_page_id BIGINT UNSIGNED NULL');
                } catch (\Throwable $ex) {
                    // Ignore if already nullable
                }
            }

            // Enforce 1:1 unique constraint on event_id (nullable column permits multiple NULLs in MySQL/SQLite)
            try {
                Schema::table('ad_campaigns', function (Blueprint $table) {
                    $table->unique('event_id', 'ad_campaigns_event_id_unique');
                });
            } catch (\Throwable $e) {
                // Ignore if unique index already exists
            }

            // Backfill existing campaigns to have campaign_type = 'business_ad'
            DB::table('ad_campaigns')->whereNull('campaign_type')->orWhere('campaign_type', '')->update([
                'campaign_type' => 'business_ad',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_campaigns')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                try {
                    $table->dropUnique('ad_campaigns_event_id_unique');
                } catch (\Throwable $e) {}

                if (Schema::hasColumn('ad_campaigns', 'event_id')) {
                    try {
                        $table->dropForeign(['event_id']);
                    } catch (\Throwable $e) {}
                    $table->dropColumn('event_id');
                }

                if (Schema::hasColumn('ad_campaigns', 'campaign_type')) {
                    $table->dropColumn('campaign_type');
                }
            });
        }
    }
};
