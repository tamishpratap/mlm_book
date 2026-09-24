<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('members')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->string('privacy')->default('public');
            $table->string('cover_photo')->nullable();
            $table->string('logo')->nullable();
            $table->text('rules')->nullable();
            $table->text('tags')->nullable();
            $table->timestamps();

            $table->index(['privacy', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
