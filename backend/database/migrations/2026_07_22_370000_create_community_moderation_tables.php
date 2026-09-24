<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('banned_by_id')->constrained('members')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_permanent')->default(true);
            $table->timestamps();

            $table->unique(['community_id', 'member_id']);
        });

        Schema::create('community_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('muted_by_id')->constrained('members')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['community_id', 'member_id']);
        });

        Schema::create('community_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('warned_by_id')->constrained('members')->cascadeOnDelete();
            $table->string('reason');
            $table->timestamps();

            $table->index(['community_id', 'member_id']);
        });

        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('members')->cascadeOnDelete();
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, resolved
            $table->foreignId('resolved_by_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();

            $table->index(['community_id', 'status']);
        });

        Schema::create('community_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('members')->cascadeOnDelete();
            $table->string('action'); // member.banned, member.muted, member.warned, member.promoted, etc.
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['community_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_audit_logs');
        Schema::dropIfExists('community_reports');
        Schema::dropIfExists('community_warnings');
        Schema::dropIfExists('community_mutes');
        Schema::dropIfExists('community_bans');
    }
};
