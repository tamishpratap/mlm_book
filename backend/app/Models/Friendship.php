<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'member_one_id',
        'member_two_id',
        'requested_by_id',
        'status',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function memberOne(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_one_id');
    }

    public function memberTwo(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_two_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'requested_by_id');
    }

    public function otherMember(int $currentMemberId): Member
    {
        return $currentMemberId === $this->member_one_id
            ? $this->memberTwo
            : $this->memberOne;
    }

    public function stateFor(int $currentMemberId): string
    {
        if ($this->status === self::STATUS_ACCEPTED) {
            return 'friends';
        }

        if ($this->status !== self::STATUS_PENDING) {
            return 'none';
        }

        return $this->requested_by_id === $currentMemberId
            ? 'pending_sent'
            : 'pending_received';
    }

    public function receiverId(): int
    {
        return $this->requested_by_id === $this->member_one_id
            ? $this->member_two_id
            : $this->member_one_id;
    }

    public function scopeBetween(Builder $query, int $firstMemberId, int $secondMemberId): Builder
    {
        [$memberOneId, $memberTwoId] = self::normalizePair($firstMemberId, $secondMemberId);

        return $query
            ->where('member_one_id', $memberOneId)
            ->where('member_two_id', $memberTwoId);
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where(function (Builder $query) use ($memberId) {
            $query->where('member_one_id', $memberId)
                ->orWhere('member_two_id', $memberId);
        });
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public static function normalizePair(int $firstMemberId, int $secondMemberId): array
    {
        return $firstMemberId < $secondMemberId
            ? [$firstMemberId, $secondMemberId]
            : [$secondMemberId, $firstMemberId];
    }
}
