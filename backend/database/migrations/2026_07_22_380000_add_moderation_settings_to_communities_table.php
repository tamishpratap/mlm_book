<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->string('posting_permissions')->default('everyone')->after('status'); // everyone, members_only, admins_only, moderators_admins, owner_only
            $table->string('join_approval_mode')->default('instant')->after('posting_permissions'); // instant, approval_required, invite_only
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn(['posting_permissions', 'join_approval_mode']);
        });
    }
};
