<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ImportFund extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified'; // Verified on-chain, awaiting admin approval
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const LEGACY_PENDING = 'Pending';
    public const LEGACY_APPROVED = 'Approved';
    public const LEGACY_CANCELLED = 'Cancelled';
    public const LEGACY_REJECTED = 'Cancelled';

    protected $table = 'import_funds';

    protected $fillable = [
        'memberid',
        'user_id',
        'member_id',
        'txnid',
        'transaction_hash',
        'orderid',
        'amount',
        'type',
        'wallet_type',
        'wallet_address',
        'network',
        'token',
        'contract_address',
        'added_by',
        'status',
        'verification_status',
        'deposit_status',
        'verification_payload',
        'admin_notes',
        'rejection_reason',
        'verified_at',
        'verified_by',
        'mode',
    ];

    protected $casts = [
        'amount' => 'float',
        'verification_payload' => 'array',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ImportFund $fund) {
            if (empty($fund->orderid)) {
                $fund->orderid = 'DEP-' . strtoupper(Str::random(10));
            }
            if (empty($fund->type)) {
                $fund->type = 'Add';
            }
            if (empty($fund->wallet_type)) {
                $fund->wallet_type = 'USDT';
            }
            if (empty($fund->network)) {
                $fund->network = 'BEP-20';
            }
            if (empty($fund->token)) {
                $fund->token = 'USDT';
            }
            if (empty($fund->deposit_status)) {
                $fund->deposit_status = self::STATUS_PENDING;
            }
            if (empty($fund->status)) {
                $fund->status = self::LEGACY_PENDING;
            }
            if (empty($fund->txnid) && !empty($fund->transaction_hash)) {
                $fund->txnid = substr($fund->transaction_hash, 0, 100);
            }
            if (empty($fund->transaction_hash) && !empty($fund->txnid)) {
                $fund->transaction_hash = $fund->txnid;
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->deposit_status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->deposit_status === self::STATUS_VERIFIED;
    }

    public function isApproved(): bool
    {
        return $this->deposit_status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->deposit_status === self::STATUS_REJECTED;
    }
}
