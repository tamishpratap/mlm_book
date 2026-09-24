<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();

            $table->index('post_id', 'post_comments_post_id_index');
            $table->index('member_id', 'post_comments_member_id_index');
            $table->index('created_at', 'post_comments_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
