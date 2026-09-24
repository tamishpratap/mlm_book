<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Str;

class MemberPhoneNumberService
{
    /**
     * Standard ITU E.164 pattern: + followed by 1 to 3 digits country code, total 7-15 digits.
     */
    public const E164_PATTERN = '/^\+[1-9]\d{6,14}$/';

    /**
     * Normalize a phone number with country code into canonical E.164 format.
     * Examples:
     * - ('9876543210', '+91') => '+919876543210'
     * - ('09876543210', '+91') => '+919876543210'
     * - ('+91 98765-43210', '+91') => '+919876543210'
     * - ('+1 (202) 555-0123', '+91') => '+12025550123'
     * - ('00447123456789', '+91') => '+447123456789'
     */
    public function normalize(?string $phone, string $countryCode = '+91'): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $trimmed = trim((string) $phone);

        // Normalize country code (e.g. '91' -> '+91', '+91' -> '+91')
        $cleanCountryCode = preg_replace('/[^\d]/', '', $countryCode);
        $canonicalCountryCode = ! empty($cleanCountryCode) ? ('+' . $cleanCountryCode) : '+91';

        // Check if caller supplied explicit '+' or '00' international prefix
        if (str_starts_with($trimmed, '+')) {
            $digitsOnly = preg_replace('/\D/', '', $trimmed);
            $canonical = '+' . $digitsOnly;
        } elseif (str_starts_with($trimmed, '00')) {
            $digitsOnly = preg_replace('/\D/', '', substr($trimmed, 2));
            $canonical = '+' . $digitsOnly;
        } else {
            // Strip any leading national trunk prefix zero (e.g. 09876543210 -> 9876543210)
            $digits = preg_replace('/\D/', '', $trimmed);
            if (str_starts_with($digits, '0') && strlen($digits) > 8) {
                $digits = ltrim($digits, '0');
            }

            // If the user already typed their full country code without '+' (e.g., 919876543210 with country code +91)
            $codeDigits = ltrim($canonicalCountryCode, '+');
            if (str_starts_with($digits, $codeDigits) && strlen($digits) > (strlen($codeDigits) + 7)) {
                $canonical = '+' . $digits;
            } else {
                $canonical = $canonicalCountryCode . $digits;
            }
        }

        if (! preg_match(self::E164_PATTERN, $canonical)) {
            return null;
        }

        // Indian mobile numbers (+91) must have exactly 10 national digits (total +91 + 10 digits = 13 chars)
        if (str_starts_with($canonical, '+91')) {
            $national = substr($canonical, 3);
            if (strlen($national) !== 10) {
                return null;
            }
        } else {
            // General international numbers: total digits excluding '+' must be at least 8 digits
            $totalDigits = strlen($canonical) - 1;
            if ($totalDigits < 8 || $totalDigits > 15) {
                return null;
            }
        }

        return $canonical;
    }

    /**
     * Check if a phone string can be normalized into a valid E.164 phone number.
     */
    public function isValid(?string $phone, string $countryCode = '+91'): bool
    {
        return $this->normalize($phone, $countryCode) !== null;
    }

    /**
     * Authoritatively check whether the normalized phone is available (not claimed by another member).
     */
    public function isAvailable(string $normalizedPhone, ?int $ignoreMemberId = null): bool
    {
        return ! Member::query()
            ->when($ignoreMemberId, fn ($query) => $query->whereKeyNot($ignoreMemberId))
            ->where('phone', $normalizedPhone)
            ->exists();
    }

    /**
     * Safely mask a phone number for logging and privacy (e.g. +9198******10).
     */
    public function mask(?string $phone): string
    {
        if (blank($phone)) {
            return '[empty]';
        }

        $length = strlen($phone);
        if ($length <= 5) {
            return str_repeat('*', $length);
        }

        $prefix = substr($phone, 0, min(5, (int) floor($length / 2)));
        $suffix = substr($phone, -2);
        $maskLength = max(1, $length - strlen($prefix) - strlen($suffix));

        return $prefix . str_repeat('*', $maskLength) . $suffix;
    }
}
