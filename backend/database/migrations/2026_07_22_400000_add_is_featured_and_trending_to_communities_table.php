<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->unsignedInteger('trending_score')->default(0)->after('is_featured');

            $table->index(['visibility', 'is_featured', 'member_count']);
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropIndex(['visibility', 'is_featured', 'member_count']);
            $table->dropColumn(['is_featured', 'trending_score']);
        });
    }
};
