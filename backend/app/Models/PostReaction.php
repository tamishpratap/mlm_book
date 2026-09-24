<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    public const REACTION_LIKE = 'like';
    public const REACTION_LOVE = 'love';
    public const REACTION_HAHA = 'haha';
    public const REACTION_WOW = 'wow';
    public const REACTION_SAD = 'sad';
    public const REACTION_ANGRY = 'angry';

    public const EMOJI_MAP = [
        'like' => '👍',
        'love' => '❤️',
        'haha' => '😂',
        'wow' => '😮',
        'sad' => '😢',
        'angry' => '😡',
    ];

    public const LABEL_MAP = [
        'like' => 'Like',
        'love' => 'Love',
        'haha' => 'Haha',
        'wow' => 'Wow',
        'sad' => 'Sad',
        'angry' => 'Angry',
    ];

    public const COLOR_MAP = [
        'like' => '#2563eb',
        'love' => '#ef4444',
        'haha' => '#f59e0b',
        'wow' => '#f59e0b',
        'sad' => '#f59e0b',
        'angry' => '#ea580c',
    ];

    protected $fillable = [
        'post_id',
        'member_id',
        'reaction',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function emoji(): string
    {
        return self::EMOJI_MAP[$this->reaction] ?? '👍';
    }

    public function label(): string
    {
        return self::LABEL_MAP[$this->reaction] ?? 'Like';
    }

    public function color(): string
    {
        return self::COLOR_MAP[$this->reaction] ?? '#2563eb';
    }
}
