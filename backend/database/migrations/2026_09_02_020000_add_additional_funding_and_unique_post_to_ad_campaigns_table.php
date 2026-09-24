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
        if (Schema::hasTable('ad_campaigns')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('ad_campaigns', 'additional_funding')) {
                    $table->decimal('additional_funding', 12, 2)->unsigned()->default(0.00)->after('budget');
                }
                if (!Schema::hasColumn('ad_campaigns', 'total_funded')) {
                    $table->decimal('total_funded', 12, 2)->unsigned()->default(0.00)->after('additional_funding');
                }
            });

            // Populate total_funded for existing records
            DB::table('ad_campaigns')->where('total_funded', 0.00)->update([
                'total_funded' => DB::raw('budget'),
            ]);

            // Add unique index on post_id if not already indexed as unique
            // Note: In MySQL and SQLite, unique constraint on nullable column permits multiple NULLs,
            // but guarantees uniqueness for all non-null post_id entries.
            try {
                Schema::table('ad_campaigns', function (Blueprint $table) {
                    $table->unique('post_id', 'ad_campaigns_post_id_unique');
                });
            } catch (\Throwable $e) {
                // Ignore if unique index already exists
            }
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
                    $table->dropUnique('ad_campaigns_post_id_unique');
                } catch (\Throwable $e) {
                    // Ignore
                }

                $cols = [];
                if (Schema::hasColumn('ad_campaigns', 'total_funded')) {
                    $cols[] = 'total_funded';
                }
                if (Schema::hasColumn('ad_campaigns', 'additional_funding')) {
                    $cols[] = 'additional_funding';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};