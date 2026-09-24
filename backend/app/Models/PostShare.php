<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostShare extends Model
{
    protected $fillable = [
        'original_post_id',
        'shared_post_id',
        'shared_by',
        'share_message',
    ];

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }

    public function sharedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'shared_by');
    }
}
