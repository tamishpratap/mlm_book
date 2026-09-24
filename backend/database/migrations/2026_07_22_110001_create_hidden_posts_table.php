<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hidden_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['member_id', 'post_id'], 'hidden_posts_member_post_unique');
            $table->index('member_id', 'hidden_posts_member_id_index');
            $table->index('post_id', 'hidden_posts_post_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hidden_posts');
    }
};
