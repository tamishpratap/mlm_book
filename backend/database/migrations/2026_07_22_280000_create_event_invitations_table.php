<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('invited_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['event_id', 'invited_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invitations');
    }
};
