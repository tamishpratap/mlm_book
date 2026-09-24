<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'actor_id');
    }

    public static function record(int $communityId, int $actorId, string $action, ?string $targetType = null, ?int $targetId = null, ?array $metadata = null): self
    {
        return static::create([
            'community_id' => $communityId,
            'actor_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
