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
        Schema::table('business_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('business_pages', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_verified')->index();
            }
            if (! Schema::hasColumn('business_pages', 'trending_score')) {
                $table->float('trending_score')->default(0.0)->after('is_featured')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_pages', function (Blueprint $table) {
            if (Schema::hasColumn('business_pages', 'trending_score')) {
                $table->dropColumn('trending_score');
            }
            if (Schema::hasColumn('business_pages', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};
