<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('role')->default('member'); // owner, admin, moderator, member
            $table->string('status')->default('accepted'); // accepted, pending, rejected, blocked, left, removed
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['community_id', 'member_id']);
            $table->index(['community_id', 'status']);
            $table->index(['member_id', 'status']);
            $table->index(['community_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_members');
    }
};
