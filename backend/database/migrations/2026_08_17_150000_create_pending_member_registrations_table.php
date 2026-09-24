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
        if (!Schema::hasTable('pending_member_registrations')) {
            Schema::create('pending_member_registrations', function (Blueprint $table) {
                $table->id();
                $table->string('token', 64)->unique();
                $table->string('name');
                $table->string('user_id')->index();
                $table->string('email')->index();
                $table->string('password_hash');
                $table->string('otp_hash');
                $table->dateTime('expires_at')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->dateTime('last_resend_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_member_registrations');
    }
};
