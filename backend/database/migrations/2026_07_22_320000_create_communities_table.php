<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('community_id')->unique();
            $table->foreignId('owner_id')->constrained('members')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('Technology');
            $table->string('visibility')->default('public');
            $table->string('status')->default('active');
            $table->string('cover_photo')->nullable();
            $table->string('logo')->nullable();
            $table->text('rules')->nullable();
            $table->text('tags')->nullable();
            $table->string('invite_code')->unique()->nullable();
            $table->unsignedInteger('member_count')->default(1);
            $table->unsignedInteger('post_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'category']);
            $table->index(['owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
