<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoryReply extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'story_id',
        'sender_id',
        'receiver_id',
        'message',
        'is_seen',
    ];

    protected function casts(): array
    {
        return [
            'is_seen' => 'boolean',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'receiver_id');
    }
}
