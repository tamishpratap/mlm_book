<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('accepted')->comment('accepted, pending, blocked');
            $table->timestamp('followed_at')->nullable();
            $table->timestamps();

            $table->unique(['business_page_id', 'member_id']);
            $table->index(['business_page_id', 'status']);
            $table->index(['member_id', 'status']);
        });

        Schema::create('business_follower_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('invitee_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('pending')->comment('pending, accepted, declined');
            $table->timestamps();

            $table->unique(['business_page_id', 'invitee_id']);
            $table->index(['business_page_id', 'status']);
            $table->index(['invitee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_follower_invitations');
        Schema::dropIfExists('business_followers');
    }
};
