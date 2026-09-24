<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('post_comments')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('reaction', 20);
            $table->timestamps();

            $table->unique(['comment_id', 'member_id'], 'comment_reactions_comment_member_unique');
            $table->index('comment_id', 'comment_reactions_comment_id_index');
            $table->index('member_id', 'comment_reactions_member_id_index');
            $table->index('reaction', 'comment_reactions_reaction_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
