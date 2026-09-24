<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessQuickReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_page_id',
        'title',
        'shortcut',
        'message',
    ];

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }
}
