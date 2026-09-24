<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('blocked_member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_id', 'blocked_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_users');
    }
};
