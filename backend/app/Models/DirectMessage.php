<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'attachment',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'receiver_id');
    }

    public function scopeBetween($query, int $memberOneId, int $memberTwoId)
    {
        return $query->where(function ($q) use ($memberOneId, $memberTwoId) {
            $q->where('sender_id', $memberOneId)->where('receiver_id', $memberTwoId);
        })->orWhere(function ($q) use ($memberOneId, $memberTwoId) {
            $q->where('sender_id', $memberTwoId)->where('receiver_id', $memberOneId);
        });
    }

    public function scopeUnread($query, int $receiverId)
    {
        return $query->where('receiver_id', $receiverId)->where('is_read', false);
    }
}
