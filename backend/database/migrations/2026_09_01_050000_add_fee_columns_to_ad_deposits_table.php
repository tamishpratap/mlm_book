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
        if (Schema::hasTable('ad_deposits')) {
            Schema::table('ad_deposits', function (Blueprint $table) {
                if (!Schema::hasColumn('ad_deposits', 'fee_percent')) {
                    $table->decimal('fee_percent', 5, 2)->default(0.00)->after('amount_inr');
                }
                if (!Schema::hasColumn('ad_deposits', 'fee_amount_inr')) {
                    $table->decimal('fee_amount_inr', 14, 2)->default(0.00)->after('fee_percent');
                }
                if (!Schema::hasColumn('ad_deposits', 'net_amount_inr')) {
                    $table->decimal('net_amount_inr', 14, 2)->default(0.00)->after('fee_amount_inr');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_deposits')) {
            Schema::table('ad_deposits', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('ad_deposits', 'net_amount_inr')) {
                    $columnsToDrop[] = 'net_amount_inr';
                }
                if (Schema::hasColumn('ad_deposits', 'fee_amount_inr')) {
                    $columnsToDrop[] = 'fee_amount_inr';
                }
                if (Schema::hasColumn('ad_deposits', 'fee_percent')) {
                    $columnsToDrop[] = 'fee_percent';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
