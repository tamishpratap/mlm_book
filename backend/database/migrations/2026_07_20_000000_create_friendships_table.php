<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_one_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('member_two_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('requested_by_id')->constrained('members')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['member_one_id', 'member_two_id'],
                'friendships_unique_member_pair',
            );
            $table->index('member_one_id', 'friendships_member_one_index');
            $table->index('member_two_id', 'friendships_member_two_index');
            $table->index('requested_by_id', 'friendships_requested_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
