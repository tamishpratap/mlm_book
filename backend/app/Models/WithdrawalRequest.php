<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'member_id',
        'memberid',
        'request_id',
        'request_date',
        'payment_date',
        'txnid',
        'name',
        'remarks',
        'admin_notes',
        'wallet_address',
        'gross_amount',
        'service_charge',
        'net_amount',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'datetime',
            'payment_date' => 'datetime',
            'gross_amount' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public const STATUS_PENDING = 'Pending';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_VERIFIED = 'Verified';

    public const TYPE_USER = 'User';
    public const TYPE_AUTO = 'Auto';

    /**
     * Relationship with Member.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
