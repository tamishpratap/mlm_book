<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BusinessReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_page_id',
        'member_id',
        'rating',
        'recommendation',
        'title',
        'body',
        'photos',
        'is_hidden',
        'helpful_count',
        'unhelpful_count',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'photos' => 'array',
            'is_hidden' => 'boolean',
            'helpful_count' => 'integer',
            'unhelpful_count' => 'integer',
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

    public function officialReply(): HasOne
    {
        return $this->hasOne(BusinessReviewReply::class, 'business_review_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(BusinessReviewVote::class, 'business_review_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(BusinessReviewReport::class, 'business_review_id');
    }

    public function isRecommended(): bool
    {
        return $this->recommendation === 'recommend';
    }

    public function userVote(?int $memberId): ?string
    {
        if (! $memberId) {
            return null;
        }

        $vote = $this->votes()->where('member_id', $memberId)->first();

        return $vote?->vote_type;
    }
}
