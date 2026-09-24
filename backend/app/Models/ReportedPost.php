<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportedPost extends Model
{
    public const REASON_SPAM = 'Spam';
    public const REASON_FAKE_NEWS = 'Fake News';
    public const REASON_HARASSMENT = 'Harassment';
    public const REASON_VIOLENCE = 'Violence';
    public const REASON_ADULT_CONTENT = 'Adult Content';
    public const REASON_HATE_SPEECH = 'Hate Speech';
    public const REASON_OTHER = 'Other';

    protected $fillable = [
        'member_id',
        'post_id',
        'reason',
        'description',
        'status',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
