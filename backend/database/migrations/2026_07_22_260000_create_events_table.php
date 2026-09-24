<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('members')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->string('event_type')->default('offline');
            $table->string('privacy')->default('public');
            $table->string('cover_photo')->nullable();
            $table->string('banner')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('timezone')->default('UTC');
            $table->integer('max_guests')->nullable();
            $table->text('location_address')->nullable();
            $table->string('location_city')->nullable();
            $table->string('location_state')->nullable();
            $table->string('location_country')->nullable();
            $table->text('google_maps_link')->nullable();
            $table->text('meeting_link')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('status')->default('published');
            $table->timestamps();

            $table->index(['start_date', 'event_type', 'privacy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
