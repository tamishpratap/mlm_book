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
                if (!Schema::hasColumn('ad_deposits', 'network')) {
                    $table->string('network', 50)->nullable()->default('BEP-20')->after('currency_out');
                }
                if (!Schema::hasColumn('ad_deposits', 'token')) {
                    $table->string('token', 50)->nullable()->default('USDT')->after('network');
                }
                if (!Schema::hasColumn('ad_deposits', 'wallet_address')) {
                    $table->string('wallet_address', 255)->nullable()->after('token');
                }
                if (!Schema::hasColumn('ad_deposits', 'submitted_amount')) {
                    $table->decimal('submitted_amount', 16, 4)->nullable()->after('wallet_address');
                }
                if (!Schema::hasColumn('ad_deposits', 'verified_amount')) {
                    $table->decimal('verified_amount', 16, 4)->nullable()->after('submitted_amount');
                }
                if (!Schema::hasColumn('ad_deposits', 'verification_status')) {
                    $table->string('verification_status', 50)->nullable()->default('unverified')->after('status');
                }
                if (!Schema::hasColumn('ad_deposits', 'verification_source')) {
                    $table->string('verification_source', 50)->nullable()->after('verification_status');
                }
                if (!Schema::hasColumn('ad_deposits', 'transaction_hash')) {
                    $table->string('transaction_hash', 255)->nullable()->after('transaction_reference');
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
                $columns = ['network', 'token', 'wallet_address', 'submitted_amount', 'verified_amount', 'verification_status', 'verification_source', 'transaction_hash'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ad_deposits', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};