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
        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('viewer_member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['story_id', 'viewer_member_id'], 'story_views_story_viewer_unique');
            $table->index('story_id', 'story_views_story_id_index');
            $table->index('viewer_member_id', 'story_views_viewer_member_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_views');
    }
};
