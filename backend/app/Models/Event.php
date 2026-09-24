<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_DRAFT = 'draft';

    /**
     * Determine whether the event is in an active or published state.
     */
    public function isActive(): bool
    {
        return in_array(strtolower((string) $this->status), [self::STATUS_ACTIVE, self::STATUS_PUBLISHED], true);
    }

    /**
     * Scope query to events in an active or published state.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PUBLISHED]);
    }

    protected $fillable = [
        'organizer_id',
        'title',
        'slug',
        'short_description',
        'description',
        'category',
        'event_type',
        'privacy',
        'cover_photo',
        'banner',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',
        'max_guests',
        'location_address',
        'location_city',
        'location_state',
        'location_country',
        'google_maps_link',
        'meeting_link',
        'meeting_password',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected $appends = [
        'cover_photo_url',
    ];

    public function getCoverPhotoUrlAttribute(): ?string
    {
        if (!$this->cover_photo) {
            return null;
        }

        return str_starts_with($this->cover_photo, 'http')
            ? $this->cover_photo
            : asset($this->cover_photo);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'organizer_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EventResponse::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function userResponse(?int $memberId): ?string
    {
        if (! $memberId) return null;
        return $this->responses()->where('member_id', $memberId)->value('response');
    }

    public function goingCount(): int
    {
        return $this->responses()->where('response', 'going')->count();
    }

    public function interestedCount(): int
    {
        return $this->responses()->where('response', 'interested')->count();
    }

    public function isOrganizer(?int $memberId): bool
    {
        return $memberId && (int) $this->organizer_id === (int) $memberId;
    }

    public function campaign(): HasOne
    {
        return $this->hasOne(AdCampaign::class, 'event_id');
    }

    public function paidCampaign(): HasOne
    {
        return $this->hasOne(AdCampaign::class, 'event_id')->where('campaign_type', AdCampaign::TYPE_EVENT);
    }

    public function isPaidCampaign(): bool
    {
        return $this->campaign()->exists();
    }

    /**
     * Determine authoritatively whether this Event's scheduled date/time has passed.
     */
    public function hasPassed(): bool
    {
        $now = now();
        $effectiveEndDate = $this->end_date ?? $this->start_date;
        if (!$effectiveEndDate) {
            return true;
        }

        $tz = $this->timezone ?: config('app.timezone', 'UTC');

        $endDateStr = $effectiveEndDate instanceof \Carbon\CarbonInterface
            ? $effectiveEndDate->toDateString()
            : (string) $effectiveEndDate;

        if (!empty($this->end_time)) {
            try {
                $endDateTime = \Carbon\Carbon::parse("{$endDateStr} {$this->end_time}", $tz);
                return $endDateTime->isPast();
            } catch (\Throwable $e) {
                // Fall back to date comparison on parsing exception
            }
        }

        try {
            $endOfDay = \Carbon\Carbon::parse($endDateStr, $tz)->endOfDay();
            return $endOfDay->isPast();
        } catch (\Throwable $e) {
            return $endDateStr < $now->toDateString();
        }
    }

    /**
     * Scope query to events whose scheduled date/time has not passed.
     */
    public function scopeNotPassed($query)
    {
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        return $query->where(function ($q) use ($today, $currentTime) {
            // Case 1: Event has end_date specified
            $q->where(function ($eq) use ($today, $currentTime) {
                $eq->whereNotNull('end_date')
                    ->where(function ($sub) use ($today, $currentTime) {
                        $sub->whereDate('end_date', '>', $today)
                            ->orWhere(function ($todayQ) use ($today, $currentTime) {
                                $todayQ->whereDate('end_date', '=', $today)
                                    ->where(function ($timeQ) use ($currentTime) {
                                        $timeQ->whereNull('end_time')
                                            ->orWhere('end_time', '>=', $currentTime);
                                    });
                            });
                    });
            })
            // Case 2: Event has no end_date, evaluate by start_date
            ->orWhere(function ($sq) use ($today, $currentTime) {
                $sq->whereNull('end_date')
                    ->where(function ($sub) use ($today, $currentTime) {
                        $sub->whereDate('start_date', '>', $today)
                            ->orWhere(function ($todayQ) use ($today, $currentTime) {
                                $todayQ->whereDate('start_date', '=', $today)
                                    ->where(function ($timeQ) use ($currentTime) {
                                        $timeQ->whereNull('end_time')
                                            ->orWhere('end_time', '>=', $currentTime);
                                    });
                            });
                    });
            });
        });
    }
}
