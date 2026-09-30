<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mobile_access_tokens')) {
            Schema::create('mobile_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('audience', 20)->default('member'); // 'member' or 'admin'
                $table->unsignedBigInteger('actor_id');
                $table->string('token_hash', 64)->unique();
                $table->string('device_name', 120)->nullable();
                $table->string('last_ip', 45)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['audience', 'actor_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_access_tokens');
    }
};
