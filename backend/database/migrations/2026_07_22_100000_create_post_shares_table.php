<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('shared_post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('shared_by')->constrained('members')->cascadeOnDelete();
            $table->text('share_message')->nullable();
            $table->timestamps();

            $table->index('original_post_id', 'post_shares_original_post_id_index');
            $table->index('shared_post_id', 'post_shares_shared_post_id_index');
            $table->index('shared_by', 'post_shares_shared_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_shares');
    }
};
