<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessFollowerInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_page_id',
        'inviter_id',
        'invitee_id',
        'status',
    ];

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'invitee_id');
    }
}
