<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BusinessConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_page_id',
        'customer_id',
        'status',
        'is_starred',
        'is_pinned',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_starred' => 'boolean',
            'is_pinned' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'customer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BusinessMessage::class, 'business_conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(BusinessMessage::class, 'business_conversation_id')->latestOfMany();
    }

    public function unreadCountForBusiness(): int
    {
        return $this->messages()
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->count();
    }

    public function unreadCountForCustomer(): int
    {
        return $this->messages()
            ->where('sender_type', 'business')
            ->where('is_read', false)
            ->count();
    }
}
