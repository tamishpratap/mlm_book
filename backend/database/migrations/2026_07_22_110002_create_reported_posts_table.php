<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reported_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('reason', 50);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index('member_id', 'reported_posts_member_id_index');
            $table->index('post_id', 'reported_posts_post_id_index');
            $table->index('status', 'reported_posts_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reported_posts');
    }
};
