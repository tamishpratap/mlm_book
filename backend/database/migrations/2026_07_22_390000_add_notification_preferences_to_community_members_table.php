<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_members', function (Blueprint $table) {
            $table->string('notification_level')->default('all')->after('status'); // all, important_only, announcements_only, posts_only, muted
            $table->timestamp('muted_until')->nullable()->after('notification_level');
        });
    }

    public function down(): void
    {
        Schema::table('community_members', function (Blueprint $table) {
            $table->dropColumn(['notification_level', 'muted_until']);
        });
    }
};
