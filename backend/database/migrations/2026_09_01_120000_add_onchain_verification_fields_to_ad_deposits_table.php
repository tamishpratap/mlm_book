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
                if (!Schema::hasColumn('ad_deposits', 'sender_address')) {
                    $table->string('sender_address', 255)->nullable()->after('wallet_address');
                }
                if (!Schema::hasColumn('ad_deposits', 'block_number')) {
                    $table->unsignedBigInteger('block_number')->nullable()->after('sender_address');
                }
                if (!Schema::hasColumn('ad_deposits', 'verification_error')) {
                    $table->text('verification_error')->nullable()->after('verification_source');
                }
                if (!Schema::hasColumn('ad_deposits', 'verification_payload')) {
                    $table->json('verification_payload')->nullable()->after('verification_error');
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
                $columns = ['sender_address', 'block_number', 'verification_error', 'verification_payload'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ad_deposits', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};