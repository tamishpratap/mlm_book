<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class FeedbackSuggestion extends Model
{
    use HasFactory;

    protected $table = 'feedback_suggestions';

    protected $fillable = [
        'member_id',
        'type',
        'subject',
        'message',
        'status',
        'admin_response',
        'admin_id',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    protected $appends = [
        'type_label',
        'status_label',
        'created_at_human',
        'responded_at_human',
    ];

    /**
     * Relationship: The member who submitted this feedback or suggestion.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Relationship: The admin who responded to this feedback.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Accessor: Human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match (strtolower((string) $this->type)) {
            'suggestion' => 'Suggestion',
            'idea' => 'Idea / Proposal',
            'complaint' => 'Complaint',
            'bug_report' => 'Bug Report',
            'other' => 'Other Inquiry',
            default => 'General Feedback',
        };
    }

    /**
     * Accessor: Human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match (strtolower((string) $this->status)) {
            'in_review' => 'In Review',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => 'New',
        };
    }

    /**
     * Accessor: Human-readable created time.
     */
    public function getCreatedAtHumanAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    /**
     * Accessor: Human-readable responded time.
     */
    public function getRespondedAtHumanAttribute(): ?string
    {
        return $this->responded_at ? $this->responded_at->diffForHumans() : null;
    }

    /**
     * Scope: Search query across member name, user_id, subject, and message.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $term = '%' . trim($search) . '%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('subject', 'like', $term)
                ->orWhere('message', 'like', $term)
                ->orWhereHas('member', function (Builder $mq) use ($term) {
                    $mq->where('name', 'like', $term)
                        ->orWhere('user_id', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
        });
    }

    /**
     * Scope: Categorical and date filtering.
     */
    public function scopeFilterBy(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if (!empty($dateFrom) && !empty($dateTo)) {
            $start = Carbon::parse($dateFrom)->startOfDay();
            $end = Carbon::parse($dateTo)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        } elseif (!empty($dateFrom)) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        return $query;
    }
}
