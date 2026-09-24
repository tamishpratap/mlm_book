<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_page_id',
        'member_id',
        'type',
        'title',
        'data',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
        ];
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
