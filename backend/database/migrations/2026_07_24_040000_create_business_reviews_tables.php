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
        Schema::create('business_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->default(5)->comment('1 to 5 stars');
            $table->string('recommendation')->default('recommend')->comment('recommend, not_recommend');
            $table->string('title')->nullable();
            $table->text('body');
            $table->json('photos')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);
            $table->timestamps();

            $table->unique(['business_page_id', 'member_id']);
            $table->index(['business_page_id', 'rating']);
            $table->index(['business_page_id', 'is_hidden']);
        });

        Schema::create('business_review_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_review_id')->constrained('business_reviews')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->text('reply');
            $table->timestamps();

            $table->unique('business_review_id');
        });

        Schema::create('business_review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_review_id')->constrained('business_reviews')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('vote_type')->default('helpful')->comment('helpful, unhelpful');
            $table->timestamps();

            $table->unique(['business_review_id', 'member_id']);
        });

        Schema::create('business_review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_review_id')->constrained('business_reviews')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('members')->cascadeOnDelete();
            $table->string('reason')->comment('spam, fake_review, harassment, offensive, other');
            $table->text('details')->nullable();
            $table->string('status')->default('pending')->comment('pending, reviewed, dismissed');
            $table->timestamps();

            $table->index(['business_review_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_review_reports');
        Schema::dropIfExists('business_review_votes');
        Schema::dropIfExists('business_review_replies');
        Schema::dropIfExists('business_reviews');
    }
};
