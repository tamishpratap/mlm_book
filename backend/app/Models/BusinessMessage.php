<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'attachment_path',
        'attachment_type',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BusinessConversation::class, 'business_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sender_id');
    }
}
