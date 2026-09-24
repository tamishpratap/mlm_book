<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingMemberRegistration extends Model
{
    public const MAX_ATTEMPTS = 5;
    public const EXPIRY_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $fillable = [
        'token',
        'name',
        'user_id',
        'introducer_id',
        'email',
        'phone',
        'password_hash',
        'otp_hash',
        'expires_at',
        'attempts',
        'last_resend_at',
    ];

    protected $hidden = [
        'password_hash',
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_resend_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at ? $this->expires_at->isPast() : true;
    }

    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    public function canResend(int $cooldownSeconds = self::RESEND_COOLDOWN_SECONDS): bool
    {
        if (! $this->last_resend_at) {
            return true;
        }

        return $this->last_resend_at->addSeconds($cooldownSeconds)->isPast();
    }

    public function resendCooldownRemaining(int $cooldownSeconds = self::RESEND_COOLDOWN_SECONDS): int
    {
        if (! $this->last_resend_at) {
            return 0;
        }

        $resendAvailableAt = $this->last_resend_at->copy()->addSeconds($cooldownSeconds);

        return $resendAvailableAt->isPast() ? 0 : (int) now()->diffInSeconds($resendAvailableAt, false);
    }
}
