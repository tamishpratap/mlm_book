<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileVisit extends Model
{
    protected $fillable = [
        'profile_owner_id',
        'visitor_id',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'profile_owner_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'visitor_id');
    }
}
