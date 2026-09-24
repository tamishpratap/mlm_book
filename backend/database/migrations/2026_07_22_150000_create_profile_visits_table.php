<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_owner_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('members')->cascadeOnDelete();
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->index(['profile_owner_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_visits');
    }
};
