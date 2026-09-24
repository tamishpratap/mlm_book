<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessReviewReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_review_id',
        'reporter_id',
        'reason',
        'details',
        'status',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(BusinessReview::class, 'business_review_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reporter_id');
    }
}
