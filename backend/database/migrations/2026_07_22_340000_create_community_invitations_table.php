<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('invitee_id')->nullable()->constrained('members')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('invite_code')->unique();
            $table->string('status')->default('pending'); // pending, accepted, rejected, expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['community_id', 'status']);
            $table->index(['inviter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_invitations');
    }
};
