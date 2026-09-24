<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPageAnalyticsView extends Model
{
    use HasFactory;

    protected $table = 'business_analytics_views';

    public $timestamps = false;

    protected $fillable = [
        'business_page_id',
        'member_id',
        'ip_address',
        'user_agent',
        'device_type',
        'country',
        'city',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
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
