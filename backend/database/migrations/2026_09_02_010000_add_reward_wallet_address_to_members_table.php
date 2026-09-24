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
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'reward_wallet_address')) {
                $table->string('reward_wallet_address', 100)->nullable()->after('reward_balance');
            }
            if (!Schema::hasColumn('members', 'reward_wallet_network')) {
                $table->string('reward_wallet_network', 30)->default('BEP-20')->nullable()->after('reward_wallet_address');
            }
            if (!Schema::hasColumn('members', 'reward_wallet_currency')) {
                $table->string('reward_wallet_currency', 20)->default('USDT')->nullable()->after('reward_wallet_network');
            }
            if (!Schema::hasColumn('members', 'reward_wallet_verified_at')) {
                $table->timestamp('reward_wallet_verified_at')->nullable()->after('reward_wallet_currency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'reward_wallet_verified_at')) {
                $table->dropColumn('reward_wallet_verified_at');
            }
            if (Schema::hasColumn('members', 'reward_wallet_currency')) {
                $table->dropColumn('reward_wallet_currency');
            }
            if (Schema::hasColumn('members', 'reward_wallet_network')) {
                $table->dropColumn('reward_wallet_network');
            }
            if (Schema::hasColumn('members', 'reward_wallet_address')) {
                $table->dropColumn('reward_wallet_address');
            }
        });
    }
};