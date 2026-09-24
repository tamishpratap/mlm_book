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
        if (!Schema::hasTable('ad_deposits')) {
            Schema::create('ad_deposits', function (Blueprint $table) {
                $table->id();
                $table->string('deposit_id', 32)->unique();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->foreignId('business_page_id')->nullable()->constrained('business_pages')->nullOnDelete();
                $table->decimal('amount_inr', 14, 2);
                $table->string('currency_in', 8)->default('INR');
                $table->string('currency_out', 8)->default('USD');
                $table->decimal('exchange_rate', 12, 4);
                $table->decimal('expected_usd_amount', 14, 2);
                $table->string('transaction_reference', 100)->index();
                $table->string('status', 20)->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_deposits');
    }
};
