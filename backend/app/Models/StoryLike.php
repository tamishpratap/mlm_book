<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryLike extends Model
{
    protected $fillable = [
        'story_id',
        'member_id',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
