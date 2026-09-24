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
        Schema::create('business_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('active')->comment('active, pending_request, archived, closed');
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['business_page_id', 'customer_id']);
            $table->index(['business_page_id', 'status']);
            $table->index(['business_page_id', 'last_message_at']);
        });

        Schema::create('business_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_conversation_id')->constrained('business_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('members')->cascadeOnDelete();
            $table->string('sender_type')->default('customer')->comment('customer, business');
            $table->text('message')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_type')->nullable()->comment('image, document, audio, video, location');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['business_conversation_id', 'created_at']);
            $table->index(['business_conversation_id', 'is_read']);
        });

        Schema::create('business_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->string('title');
            $table->string('shortcut')->nullable();
            $table->text('message');
            $table->timestamps();

            $table->index(['business_page_id']);
        });

        Schema::create('business_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('type')->comment('new_message, new_follower, new_review, new_team_invite, new_mention, new_comment');
            $table->string('title');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['business_page_id', 'is_read']);
            $table->index(['member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_notifications');
        Schema::dropIfExists('business_quick_replies');
        Schema::dropIfExists('business_messages');
        Schema::dropIfExists('business_conversations');
    }
};
