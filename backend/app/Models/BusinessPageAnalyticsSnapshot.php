<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPageAnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $table = 'business_analytics_snapshots';

    protected $fillable = [
        'business_page_id',
        'snapshot_date',
        'followers_count',
        'posts_count',
        'reviews_count',
        'average_rating',
        'messages_count',
        'views_count',
        'health_score',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'average_rating' => 'float',
            'health_score' => 'float',
            'followers_count' => 'integer',
            'posts_count' => 'integer',
            'reviews_count' => 'integer',
            'messages_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }
}
