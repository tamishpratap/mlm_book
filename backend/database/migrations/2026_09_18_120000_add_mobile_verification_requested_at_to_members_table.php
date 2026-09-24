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
        if (Schema::hasTable('members') && ! Schema::hasColumn('members', 'mobile_verification_requested_at')) {
            Schema::table('members', function (Blueprint $table) {
                $table->timestamp('mobile_verification_requested_at')->nullable()->after('mobile_verified_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('members') && Schema::hasColumn('members', 'mobile_verification_requested_at')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('mobile_verification_requested_at');
            });
        }
    }
};
