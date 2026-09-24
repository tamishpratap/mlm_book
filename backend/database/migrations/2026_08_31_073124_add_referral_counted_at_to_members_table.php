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
            if (! Schema::hasColumn('members', 'referral_counted_at')) {
                $table->timestamp('referral_counted_at')->nullable()->after('direct_referral_count')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'referral_counted_at')) {
                $table->dropIndex(['referral_counted_at']);
                $table->dropColumn('referral_counted_at');
            }
        });
    }
};
