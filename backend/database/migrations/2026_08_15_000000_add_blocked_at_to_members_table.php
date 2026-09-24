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
        if (Schema::hasTable('members') && ! Schema::hasColumn('members', 'blocked_at')) {
            Schema::table('members', function (Blueprint $table) {
                $table->timestamp('blocked_at')->nullable()->after('last_seen_at')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('members') && Schema::hasColumn('members', 'blocked_at')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('blocked_at');
            });
        }
    }
};
