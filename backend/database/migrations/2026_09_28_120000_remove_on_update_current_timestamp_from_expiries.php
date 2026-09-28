<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the automatic ON UPDATE CURRENT_TIMESTAMP behavior from
     * stories.expires_at and community_mutes.expires_at so that manual
     * or application-calculated expiration timestamps are not overwritten
     * during subsequent unrelated model updates.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `stories` MODIFY `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE `community_mutes` MODIFY `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `stories` MODIFY `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE `community_mutes` MODIFY `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
