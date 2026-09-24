<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedUser extends Model
{
    protected $fillable = [
        'member_id',
        'blocked_member_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function blockedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'blocked_member_id');
    }
}
