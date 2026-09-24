<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_invitations', function (Blueprint $table) {
            $table->unsignedInteger('max_uses')->nullable()->after('status');
            $table->unsignedInteger('use_count')->default(0)->after('max_uses');
            $table->string('type')->default('unlimited')->after('use_count'); // unlimited, temporary, one_time, limited
            $table->boolean('is_revoked')->default(false)->after('type');
            $table->string('source')->default('direct')->after('is_revoked');
            $table->text('qr_data')->nullable()->after('source');

            $table->index(['invite_code', 'is_revoked']);
        });
    }

    public function down(): void
    {
        Schema::table('community_invitations', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'use_count', 'type', 'is_revoked', 'source', 'qr_data']);
        });
    }
};
