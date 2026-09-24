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
        if (!Schema::hasTable('ad_campaigns')) {
            Schema::create('ad_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('campaign_id', 32)->unique()->index();
                $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
                $table->string('campaign_name');
                $table->decimal('budget', 12, 2)->unsigned();
                $table->decimal('spent_amount', 12, 2)->default(0.00);
                $table->decimal('remaining_amount', 12, 2)->default(0.00);
                $table->json('target_audience')->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->string('approval_status', 32)->default('pending')->index();
                $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['business_page_id', 'status']);
                $table->index(['member_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
