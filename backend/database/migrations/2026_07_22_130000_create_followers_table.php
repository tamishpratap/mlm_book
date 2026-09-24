<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('accepted');
            $table->timestamps();

            $table->unique(['follower_id', 'following_id']);
            $table->index(['following_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};
