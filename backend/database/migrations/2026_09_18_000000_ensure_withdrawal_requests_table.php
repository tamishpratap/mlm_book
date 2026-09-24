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
        if (!Schema::hasTable('withdrawal_requests')) {
            Schema::create('withdrawal_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->string('memberid', 255)->index();
                $table->string('request_id', 255)->unique();
                $table->dateTime('request_date');
                $table->dateTime('payment_date')->nullable();
                $table->string('txnid', 255)->nullable();
                $table->string('name', 255)->nullable();
                $table->text('remarks')->nullable();
                $table->string('wallet_address', 255)->nullable();
                $table->decimal('gross_amount', 14, 2)->default(0.00);
                $table->decimal('service_charge', 14, 2)->default(0.00);
                $table->decimal('net_amount', 14, 2)->default(0.00);
                $table->enum('type', ['Auto', 'User'])->default('User');
                $table->enum('status', ['Pending', 'Cancelled', 'Approved', 'Verified'])->default('Pending');
                $table->timestamps();

                $table->index('status');
                $table->index('request_date');
            });
        } else {
            Schema::table('withdrawal_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('withdrawal_requests', 'member_id')) {
                    $table->foreignId('member_id')->nullable()->after('id')->constrained('members')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep table intact to prevent accidental financial data loss
    }
};
