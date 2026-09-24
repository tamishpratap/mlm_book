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
        Schema::dropIfExists('business_analytics_snapshots');
        Schema::dropIfExists('business_analytics_views');

        Schema::create('business_analytics_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->default('desktop')->comment('desktop, mobile, tablet');
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['business_page_id', 'viewed_at']);
        });

        Schema::create('business_analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->integer('followers_count')->default(0);
            $table->integer('posts_count')->default(0);
            $table->integer('reviews_count')->default(0);
            $table->float('average_rating')->default(0.0);
            $table->integer('messages_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->float('health_score')->default(0.0);
            $table->timestamps();

            $table->unique(['business_page_id', 'snapshot_date'], 'biz_snap_page_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_analytics_snapshots');
        Schema::dropIfExists('business_analytics_views');
    }
};
