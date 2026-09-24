<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryView extends Model
{
    protected $fillable = [
        'story_id',
        'viewer_member_id',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'viewer_member_id');
    }
}
