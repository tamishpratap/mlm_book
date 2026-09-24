<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('response')->default('going');
            $table->timestamps();

            $table->unique(['event_id', 'member_id']);
            $table->index(['event_id', 'response']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_responses');
    }
};
