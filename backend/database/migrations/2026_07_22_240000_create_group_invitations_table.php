<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('invited_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['group_id', 'invited_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_invitations');
    }
};
