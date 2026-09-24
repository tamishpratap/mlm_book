<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventInvitation extends Model
{
    protected $fillable = [
        'event_id',
        'inviter_id',
        'invited_id',
        'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'inviter_id');
    }

    public function invited(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'invited_id');
    }
}
