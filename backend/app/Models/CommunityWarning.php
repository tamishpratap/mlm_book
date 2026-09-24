<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'member_id',
        'warned_by_id',
        'reason',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function warnedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'warned_by_id');
    }
}
