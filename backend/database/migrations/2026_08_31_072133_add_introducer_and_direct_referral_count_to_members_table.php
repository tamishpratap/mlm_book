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
            if (! Schema::hasColumn('members', 'introducer_id')) {
                $table->string('introducer_id', 30)->nullable()->after('user_id')->index();
            }
            if (! Schema::hasColumn('members', 'direct_referral_count')) {
                $table->unsignedInteger('direct_referral_count')->default(0)->after('introducer_id')->index();
            }
        });

        if (Schema::hasTable('pending_member_registrations')) {
            Schema::table('pending_member_registrations', function (Blueprint $table) {
                if (! Schema::hasColumn('pending_member_registrations', 'introducer_id')) {
                    $table->string('introducer_id', 30)->nullable()->after('user_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'introducer_id')) {
                $table->dropIndex(['introducer_id']);
                $table->dropColumn('introducer_id');
            }
            if (Schema::hasColumn('members', 'direct_referral_count')) {
                $table->dropIndex(['direct_referral_count']);
                $table->dropColumn('direct_referral_count');
            }
        });

        if (Schema::hasTable('pending_member_registrations')) {
            Schema::table('pending_member_registrations', function (Blueprint $table) {
                if (Schema::hasColumn('pending_member_registrations', 'introducer_id')) {
                    $table->dropColumn('introducer_id');
                }
            });
        }
    }
};
