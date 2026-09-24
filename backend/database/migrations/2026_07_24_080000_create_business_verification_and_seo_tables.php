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
        Schema::dropIfExists('business_verifications');

        Schema::create('business_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_page_id')->constrained('business_pages')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('pending')->comment('pending, verified, rejected, expired');
            $table->string('document_type')->comment('business_registration, gst, license, govt_id, other');
            $table->string('document_number')->nullable();
            $table->string('document_path');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['business_page_id', 'status']);
        });

        Schema::table('business_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('business_pages', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('is_featured');
            }
            if (! Schema::hasColumn('business_pages', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('seo_title');
            }
            if (! Schema::hasColumn('business_pages', 'social_links')) {
                $table->json('social_links')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('business_pages', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('social_links');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_pages', function (Blueprint $table) {
            if (Schema::hasColumn('business_pages', 'business_hours')) {
                $table->dropColumn('business_hours');
            }
            if (Schema::hasColumn('business_pages', 'social_links')) {
                $table->dropColumn('social_links');
            }
            if (Schema::hasColumn('business_pages', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
            if (Schema::hasColumn('business_pages', 'seo_title')) {
                $table->dropColumn('seo_title');
            }
        });

        Schema::dropIfExists('business_verifications');
    }
};
