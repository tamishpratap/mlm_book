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
        if (Schema::hasTable('pending_member_registrations')) {
            Schema::table('pending_member_registrations', function (Blueprint $table) {
                if (! Schema::hasColumn('pending_member_registrations', 'phone')) {
                    $table->string('phone', 25)->nullable()->after('email');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pending_member_registrations')) {
            Schema::table('pending_member_registrations', function (Blueprint $table) {
                if (Schema::hasColumn('pending_member_registrations', 'phone')) {
                    $table->dropColumn('phone');
                }
            });
        }
    }
};
