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
        Schema::create('business_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_id')->nullable()->unique()->comment('Unique string ID e.g. biz_xxxx');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('page_name');
            $table->string('page_username')->unique();
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_photo')->nullable();
            $table->string('visibility')->default('public')->index()->comment('public, private, draft');
            $table->string('status')->default('active')->index()->comment('active, inactive');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_pages');
    }
};
