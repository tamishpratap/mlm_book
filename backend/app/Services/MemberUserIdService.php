<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PendingMemberRegistration;
use Illuminate\Support\Str;
use RuntimeException;

class MemberUserIdService
{
    public const FORMAT_PATTERN = '/^[a-zA-Z]{4}[0-9]{6}$/';

    public function normalize(string $value): string
    {
        $value = trim($value);

        if (str_starts_with($value, '@')) {
            $value = substr($value, 1);
        }

        // For the 10-character canonical format (4 letters + 6 digits), enforce uppercase
        if (preg_match('/^[a-z]{4}[0-9]{6}$/i', $value)) {
            return Str::upper($value);
        }

        // For existing historical IDs (e.g., lowercase usernames), preserve lowercase compatibility
        return Str::lower($value);
    }

    public function isReserved(string $value): bool
    {
        $normalized = $this->normalize($value);

        return in_array(
            Str::lower($normalized),
            array_map('strtolower', config('member.reserved_user_ids', [])),
            true,
        );
    }

    public function isAvailable(string $value, ?int $ignoreMemberId = null): bool
    {
        $userId = $this->normalize($value);

        if (! $this->isValid($userId) || $this->isReserved($userId)) {
            return false;
        }

        $memberExists = Member::query()
            ->when($ignoreMemberId, fn ($query) => $query->whereKeyNot($ignoreMemberId))
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereRaw('UPPER(user_id) = ?', [strtoupper($userId)]);
            })
            ->exists();

        if ($memberExists) {
            return false;
        }

        $pendingExists = PendingMemberRegistration::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereRaw('UPPER(user_id) = ?', [strtoupper($userId)]);
            })
            ->where('expires_at', '>', now())
            ->exists();

        return ! $pendingExists;
    }

    public function namePrefix(string $name): string
    {
        // 1. Transliterate unicode / accented characters to ASCII
        $ascii = Str::ascii(trim($name));

        // 2. Extract strictly alphabetic characters (A-Z, a-z)
        $letters = preg_replace('/[^A-Za-z]/', '', $ascii) ?? '';

        // 3. Convert to lowercase
        $letters = strtolower($letters);

        // 4. Handle cases shorter than 4 characters
        if (strlen($letters) === 0) {
            // Documented canonical fallback: application default 'member' -> 'memb'
            return 'memb';
        }

        if (strlen($letters) < 4) {
            // Documented padding rule: pad right with 'x' to ensure exactly 4 characters
            return str_pad($letters, 4, 'x');
        }

        return substr($letters, 0, 4);
    }

    public function randomSuffix(): string
    {
        return sprintf('%06d', random_int(0, 999999));
    }

    public function generateFromName(string $name): string
    {
        $prefix = strtolower($this->namePrefix($name));

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $candidate = strtolower($prefix . $this->randomSuffix());

            if ($this->isAvailable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('A unique Member User ID could not be generated after 100 attempts.');
    }

    public function isValid(string $value): bool
    {
        return preg_match(self::FORMAT_PATTERN, $this->normalize($value)) === 1;
    }
}
