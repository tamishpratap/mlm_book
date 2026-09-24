<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessReviewReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_review_id',
        'member_id',
        'reply',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(BusinessReview::class, 'business_review_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
