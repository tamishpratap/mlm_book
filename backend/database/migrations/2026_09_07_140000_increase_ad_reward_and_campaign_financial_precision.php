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
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasTable('ad_rewards') && Schema::hasColumn('ad_rewards', 'reward_amount_usd')) {
                DB::statement('ALTER TABLE `ad_rewards` MODIFY `reward_amount_usd` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0.0500');
            }

            if (Schema::hasTable('ad_campaigns')) {
                if (Schema::hasColumn('ad_campaigns', 'budget')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `budget` DECIMAL(14, 4) UNSIGNED NOT NULL');
                }
                if (Schema::hasColumn('ad_campaigns', 'additional_funding')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `additional_funding` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0.0000');
                }
                if (Schema::hasColumn('ad_campaigns', 'total_funded')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `total_funded` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0.0000');
                }
                if (Schema::hasColumn('ad_campaigns', 'fee_amount')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `fee_amount` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0.0000');
                }
                if (Schema::hasColumn('ad_campaigns', 'wallet_debit')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `wallet_debit` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0.0000');
                }
                if (Schema::hasColumn('ad_campaigns', 'spent_amount')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `spent_amount` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000');
                }
                if (Schema::hasColumn('ad_campaigns', 'remaining_amount')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `remaining_amount` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000');
                }
            }

            if (Schema::hasTable('members') && Schema::hasColumn('members', 'reward_balance')) {
                DB::statement('ALTER TABLE `members` MODIFY `reward_balance` DECIMAL(14, 4) UNSIGNED NOT NULL DEFAULT 0.0000');
            }
        } else {
            // For SQLite and other drivers
            try {
                if (Schema::hasTable('ad_rewards') && Schema::hasColumn('ad_rewards', 'reward_amount_usd')) {
                    Schema::table('ad_rewards', function (Blueprint $table) {
                        $table->decimal('reward_amount_usd', 14, 4)->unsigned()->default(0.0500)->change();
                    });
                }
                if (Schema::hasTable('ad_campaigns')) {
                    Schema::table('ad_campaigns', function (Blueprint $table) {
                        $table->decimal('budget', 14, 4)->unsigned()->change();
                        $table->decimal('additional_funding', 14, 4)->unsigned()->default(0.0000)->change();
                        $table->decimal('total_funded', 14, 4)->unsigned()->default(0.0000)->change();
                        $table->decimal('fee_amount', 14, 4)->unsigned()->default(0.0000)->change();
                        $table->decimal('wallet_debit', 14, 4)->unsigned()->default(0.0000)->change();
                        $table->decimal('spent_amount', 14, 4)->default(0.0000)->change();
                        $table->decimal('remaining_amount', 14, 4)->default(0.0000)->change();
                    });
                }
                if (Schema::hasTable('members') && Schema::hasColumn('members', 'reward_balance')) {
                    Schema::table('members', function (Blueprint $table) {
                        $table->decimal('reward_balance', 14, 4)->unsigned()->default(0.0000)->change();
                    });
                }
            } catch (\Throwable $e) {
                // SQLite in-memory or fallback
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasTable('ad_rewards') && Schema::hasColumn('ad_rewards', 'reward_amount_usd')) {
                DB::statement('ALTER TABLE `ad_rewards` MODIFY `reward_amount_usd` DECIMAL(12, 2) UNSIGNED NOT NULL DEFAULT 0.05');
            }

            if (Schema::hasTable('ad_campaigns')) {
                if (Schema::hasColumn('ad_campaigns', 'budget')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `budget` DECIMAL(12, 2) UNSIGNED NOT NULL');
                }
                if (Schema::hasColumn('ad_campaigns', 'additional_funding')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `additional_funding` DECIMAL(12, 2) UNSIGNED NOT NULL DEFAULT 0.00');
                }
                if (Schema::hasColumn('ad_campaigns', 'total_funded')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `total_funded` DECIMAL(12, 2) UNSIGNED NOT NULL DEFAULT 0.00');
                }
                if (Schema::hasColumn('ad_campaigns', 'fee_amount')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `fee_amount` DECIMAL(12, 2) UNSIGNED NOT NULL DEFAULT 0.00');
                }
                if (Schema::hasColumn('ad_campaigns', 'wallet_debit')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `wallet_debit` DECIMAL(12, 2) UNSIGNED NOT NULL DEFAULT 0.00');
                }
                if (Schema::hasColumn('ad_campaigns', 'spent_amount')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `spent_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00');
                }
                if (Schema::hasColumn('ad_campaigns', 'remaining_amount')) {
                    DB::statement('ALTER TABLE `ad_campaigns` MODIFY `remaining_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00');
                }
            }

            if (Schema::hasTable('members') && Schema::hasColumn('members', 'reward_balance')) {
                DB::statement('ALTER TABLE `members` MODIFY `reward_balance` DECIMAL(12, 2) UNSIGNED NOT NULL DEFAULT 0.00');
            }
        }
    }
};
