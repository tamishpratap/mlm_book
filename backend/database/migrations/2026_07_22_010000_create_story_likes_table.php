<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['story_id', 'member_id'], 'story_likes_story_member_unique');
            $table->index('story_id', 'story_likes_story_id_index');
            $table->index('member_id', 'story_likes_member_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_likes');
    }
};
