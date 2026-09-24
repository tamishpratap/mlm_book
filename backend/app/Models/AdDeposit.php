<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AdDeposit extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'ad_deposits';

    protected $fillable = [
        'deposit_id',
        'member_id',
        'business_page_id',
        'amount_inr',
        'fee_percent',
        'fee_amount_inr',
        'net_amount_inr',
        'currency_in',
        'currency_out',
        'network',
        'token',
        'wallet_address',
        'sender_address',
        'block_number',
        'submitted_amount',
        'verified_amount',
        'verification_status',
        'verification_source',
        'verification_error',
        'verification_payload',
        'exchange_rate',
        'expected_usd_amount',
        'transaction_reference',
        'transaction_hash',
        'status',
        'admin_notes',
        'rejection_reason',
        'submitted_at',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'amount_inr' => 'float',
        'fee_percent' => 'float',
        'fee_amount_inr' => 'float',
        'net_amount_inr' => 'float',
        'submitted_amount' => 'float',
        'verified_amount' => 'float',
        'block_number' => 'integer',
        'verification_payload' => 'array',
        'exchange_rate' => 'float',
        'expected_usd_amount' => 'float',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'amount_usdt',
    ];

    protected static function booted(): void
    {
        static::creating(function (AdDeposit $deposit) {
            if (empty($deposit->deposit_id)) {
                $deposit->deposit_id = 'DEP-' . strtoupper(Str::random(10));
            }
            if (empty($deposit->status)) {
                $deposit->status = self::STATUS_PENDING;
            }
            if (empty($deposit->submitted_at)) {
                $deposit->submitted_at = now();
            }
            if (empty($deposit->currency_in)) {
                $deposit->currency_in = 'USDT';
            }
            if (empty($deposit->currency_out)) {
                $deposit->currency_out = 'USDT';
            }
            if (empty($deposit->network)) {
                $deposit->network = 'BEP-20';
            }
            if (empty($deposit->token)) {
                $deposit->token = 'USDT';
            }
            if (empty($deposit->transaction_hash) && !empty($deposit->transaction_reference)) {
                $deposit->transaction_hash = $deposit->transaction_reference;
            }
            if (empty($deposit->transaction_reference) && !empty($deposit->transaction_hash)) {
                $deposit->transaction_reference = $deposit->transaction_hash;
            }
            if ($deposit->exchange_rate === null) {
                $deposit->exchange_rate = 1.00;
            }
            if ($deposit->expected_usd_amount === null && $deposit->submitted_amount !== null) {
                $deposit->expected_usd_amount = $deposit->submitted_amount;
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getApprovedAtAttribute()
    {
        return $this->verified_at;
    }

    public function getApprovedByAttribute()
    {
        return $this->verified_by;
    }

    public function getAmountUsdtAttribute(): float
    {
        return (float) ($this->submitted_amount ?: ($this->expected_usd_amount ?: $this->amount_inr));
    }
}
