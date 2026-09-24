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
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('business_page_id')
                ->nullable()
                ->after('community_id')
                ->constrained('business_pages')
                ->nullOnDelete();
            $table->boolean('is_featured')->default(false)->after('is_pinned');

            $table->index(['business_page_id', 'is_pinned', 'created_at']);
            $table->index(['business_page_id', 'is_featured', 'created_at']);
            $table->index(['business_page_id', 'is_announcement', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['business_page_id']);
            $table->dropColumn(['business_page_id', 'is_featured']);
        });
    }
};
