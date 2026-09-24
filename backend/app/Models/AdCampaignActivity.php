<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaignActivity extends Model
{
    use HasFactory;

    public const ACTION_INTERESTED = 'interested';
    public const ACTION_CLICKED = 'clicked';
    public const ACTION_VISITED_LANDING_PAGE = 'visited_landing_page';
    public const ACTION_REWARDED = 'rewarded';
    public const ACTION_FAILED = 'failed';
    public const ACTION_FUND_ADDED = 'fund_added';

    protected $table = 'ad_campaign_activities';

    protected $fillable = [
        'ad_campaign_id',
        'business_page_id',
        'member_id',
        'action',
        'action_label',
        'qualifying_event_id',
        'ad_reward_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The advertising campaign this activity belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    /**
     * The business page hosting the campaign.
     */
    public function businessPage(): BelongsTo
    {
        return $this->belongsTo(BusinessPage::class, 'business_page_id');
    }

    /**
     * The member who performed the engagement activity (nullable for guests).
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * The linked successful reward transaction record, if applicable.
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(AdReward::class, 'ad_reward_id');
    }

    /**
     * Scope to filter activities for a specific campaign.
     */
    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('ad_campaign_id', $campaignId);
    }

    /**
     * Scope to filter activities by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
