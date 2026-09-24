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
                if (!Schema::hasColumn('ad_campaigns', 'fee_percent')) {
                    $table->decimal('fee_percent', 5, 2)->unsigned()->default(2.50)->after('currency');
                }
                if (!Schema::hasColumn('ad_campaigns', 'fee_amount')) {
                    $table->decimal('fee_amount', 12, 2)->unsigned()->default(0.00)->after('fee_percent');
                }
                if (!Schema::hasColumn('ad_campaigns', 'wallet_debit')) {
                    $table->decimal('wallet_debit', 12, 2)->unsigned()->default(0.00)->after('fee_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_campaigns')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('ad_campaigns', 'wallet_debit')) {
                    $columnsToDrop[] = 'wallet_debit';
                }
                if (Schema::hasColumn('ad_campaigns', 'fee_amount')) {
                    $columnsToDrop[] = 'fee_amount';
                }
                if (Schema::hasColumn('ad_campaigns', 'fee_percent')) {
                    $columnsToDrop[] = 'fee_percent';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
