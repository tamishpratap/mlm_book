<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberVerificationOtp;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberOtpService
{
    public const PURPOSE_MOBILE_CHANGE = 'mobile_change';

    public const PURPOSE_EMAIL_CHANGE = 'email_change';

    public const PURPOSE_REWARD_WALLET_CHANGE = 'reward_wallet_change';

    public const PURPOSE_WEB3_WALLET_VERIFICATION = 'web3_wallet_verification';

    public const EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public static function sendRateKey(string $purpose, int $memberId): string
    {
        return "member-otp-send:{$purpose}:{$memberId}";
    }

    public static function cooldownRateKey(string $purpose, int $memberId): string
    {
        return "member-otp-cooldown:{$purpose}:{$memberId}";
    }

    public static function verifyRateKey(string $purpose, int $memberId): string
    {
        return "member-otp-verify:{$purpose}:{$memberId}";
    }

    /**
     * @return array{record: MemberVerificationOtp, code: string}
     */
    public function createOtp(Member $member, string $purpose, string $destination, string $pendingValue): array
    {
        return DB::transaction(function () use ($member, $purpose, $destination, $pendingValue): array {
            Member::query()->whereKey($member->getKey())->lockForUpdate()->firstOrFail();

            $this->invalidate($member, $purpose);

            $code = (string) random_int(100000, 999999);
            $record = MemberVerificationOtp::query()->create([
                'member_id' => $member->getKey(),
                'purpose' => $purpose,
                'destination' => $destination,
                'pending_value' => $pendingValue,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            ]);

            return ['record' => $record, 'code' => $code];
        });
    }

    /**
     * The callback and OTP consumption run in one database transaction.
     */
    public function verifyOtp(
        Member $member,
        string $purpose,
        string $code,
        string $errorField,
        Closure $onVerified,
    ): void {
        $result = DB::transaction(function () use ($member, $purpose, $code, $onVerified): string {
            $record = MemberVerificationOtp::query()
                ->where('member_id', $member->getKey())
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return 'missing';
            }

            if ($record->expires_at->isPast()) {
                $record->delete();

                return 'expired';
            }

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                return 'attempts_exceeded';
            }

            if (! Hash::check($code, $record->code_hash)) {
                $record->increment('attempts');

                return $record->attempts >= self::MAX_ATTEMPTS
                    ? 'attempts_exceeded'
                    : 'invalid';
            }

            $onVerified($record);

            $record->forceFill(['verified_at' => now()])->save();

            return 'verified';
        });

        $message = match ($result) {
            'missing' => 'No active verification code was found. Please request a new code.',
            'expired' => 'This verification code has expired. Please request a new code.',
            'attempts_exceeded' => 'Too many incorrect attempts. Please request a new code.',
            'invalid' => 'The verification code is incorrect.',
            default => null,
        };

        if ($message) {
            throw ValidationException::withMessages([$errorField => $message]);
        }
    }

    public function invalidate(Member $member, string $purpose): void
    {
        MemberVerificationOtp::query()
            ->where('member_id', $member->getKey())
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();
    }
}
