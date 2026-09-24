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
        if (Schema::hasTable('ad_campaigns') && !Schema::hasColumn('ad_campaigns', 'currency')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $table->string('currency', 8)->default('USD')->after('budget');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ad_campaigns') && Schema::hasColumn('ad_campaigns', 'currency')) {
            Schema::table('ad_campaigns', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};
