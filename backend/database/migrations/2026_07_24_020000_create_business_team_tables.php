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
        Schema::create('business_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('role')->default('editor')->comment('owner, admin, editor, moderator, analyst');
            $table->string('status')->default('active')->comment('active, suspended');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['business_page_id', 'member_id']);
            $table->index(['business_page_id', 'role']);
            $table->index(['member_id', 'status']);
        });

        Schema::create('business_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('invitee_id')->constrained('members')->cascadeOnDelete();
            $table->string('role')->default('editor');
            $table->string('status')->default('pending')->comment('pending, accepted, rejected, cancelled, expired');
            $table->string('invite_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['business_page_id', 'status']);
            $table->index(['invitee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_invitations');
        Schema::dropIfExists('business_team_members');
    }
};
