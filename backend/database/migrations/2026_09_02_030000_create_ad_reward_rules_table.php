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
        if (!Schema::hasTable('ad_reward_rules')) {
            Schema::create('ad_reward_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('min_referrals')->default(0)->index();
                $table->unsignedInteger('max_referrals')->nullable()->index();
                $table->decimal('reward_amount', 10, 4)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('admins')->nullOnDelete();
            });

            // Seed initial requested reward tiers safely if table is newly created
            $now = now();
            DB::table('ad_reward_rules')->insert([
                [
                    'min_referrals' => 0,
                    'max_referrals' => 5,
                    'reward_amount' => null, // Requires Admin-configured value (e.g. $0.025)
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'min_referrals' => 6,
                    'max_referrals' => 14,
                    'reward_amount' => 0.0350,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'min_referrals' => 15,
                    'max_referrals' => null, // Unlimited
                    'reward_amount' => 0.0500,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_reward_rules');
    }
};
