<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('reaction', 20);
            $table->timestamps();

            $table->unique(['post_id', 'member_id'], 'post_reactions_post_member_unique');
            $table->index('post_id', 'post_reactions_post_id_index');
            $table->index('member_id', 'post_reactions_member_id_index');
            $table->index('reaction', 'post_reactions_reaction_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};
