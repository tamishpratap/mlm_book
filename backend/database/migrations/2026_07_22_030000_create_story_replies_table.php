<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('members')->cascadeOnDelete();
            $table->string('message', 500);
            $table->boolean('is_seen')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('story_id', 'story_replies_story_id_index');
            $table->index('sender_id', 'story_replies_sender_id_index');
            $table->index('receiver_id', 'story_replies_receiver_id_index');
            $table->index('is_seen', 'story_replies_is_seen_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_replies');
    }
};
