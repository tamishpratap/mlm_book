<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('user_id', 30)->nullable()->after('name');
        });

        $generatedUserIds = [];

        DB::table('members')
            ->select(['id', 'name', 'user_id'])
            ->orderBy('id')
            ->chunkById(500, function ($members) use (&$generatedUserIds): void {
                foreach ($members as $member) {
                    if (filled($member->user_id)) {
                        $generatedUserIds[$member->user_id] = true;

                        continue;
                    }

                    $base = $this->userIdBase((string) $member->name);
                    $attempts = 0;

                    do {
                        $candidate = $base.'_'.random_int(100000, 999999);
                        $attempts++;

                        if ($attempts > 1000) {
                            throw new RuntimeException('A unique Member User ID could not be generated during migration.');
                        }
                    } while (
                        isset($generatedUserIds[$candidate])
                        || DB::table('members')->where('user_id', $candidate)->exists()
                    );

                    DB::table('members')
                        ->where('id', $member->id)
                        ->update(['user_id' => $candidate]);

                    $generatedUserIds[$candidate] = true;
                }
            });

        Schema::table('members', function (Blueprint $table) {
            $table->unique('user_id');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('members', function (Blueprint $table) {
                $table->string('user_id', 30)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    private function userIdBase(string $name): string
    {
        $base = Str::lower(Str::ascii(trim($name)));
        $base = preg_replace('/\s+/', '_', $base) ?? '';
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?? '';
        $base = preg_replace('/_+/', '_', $base) ?? '';
        $base = trim($base, '_');

        if (! preg_match('/^[a-z]/', $base)) {
            $base = 'member';
        }

        return Str::limit($base, 23, '');
    }
};
