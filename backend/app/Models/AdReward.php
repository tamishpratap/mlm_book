<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdReward extends Model
{
    use HasFactory;

    public const STATUS_CREDITED = 'credited';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'ad_rewards';

    protected $fillable = [
        'ad_campaign_id',
        'member_id',
        'ad_reward_rule_id',
        'reward_rank_rule_id',
        'direct_verified_referral_count',
        'team_count',
        'rule_min_referrals',
        'rule_max_referrals',
        'rule_version',
        'rank_at_reward',
        'reward_amount_usd',
        'qualifying_event_id',
        'landing_page_url',
        'ip_address',
        'user_agent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount_usd' => 'decimal:4',
            'direct_verified_referral_count' => 'integer',
            'rule_min_referrals' => 'integer',
            'rule_max_referrals' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The ad campaign that funded this reward.
     */
    public function adCampaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    /**
     * The verified member who earned this reward.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * The ad reward rule snapshot applied at the time of qualification.
     */
    public function adRewardRule(): BelongsTo
    {
        return $this->belongsTo(AdRewardRule::class, 'ad_reward_rule_id');
    }

    /**
     * The rank reward rule snapshot applied at the time of qualification.
     */
    public function rewardRankRule(): BelongsTo
    {
        return $this->belongsTo(RewardRankRule::class, 'reward_rank_rule_id');
    }

    /**
     * Get the human-readable tier label for this reward snapshot.
     * e.g. "0–5", "6–14", "15+"
     */
    public function getTierLabelAttribute(): string
    {
        if ($this->rule_min_referrals === null) {
            return 'Default';
        }

        if ($this->rule_max_referrals === null) {
            return "{$this->rule_min_referrals}+";
        }

        return "{$this->rule_min_referrals}–{$this->rule_max_referrals}";
    }

    /**
     * Get formatted reward amount with 4-decimal currency formatting.
     */
    public function getRewardFormattedAttribute(): string
    {
        $amount = (float) ($this->reward_amount_usd ?? 0.00);
        return '+$' . number_format($amount, 4, '.', '') . ' USD';
    }

    /**
     * Scope query to only successfully credited rewards.
     */
    public function scopeCredited($query)
    {
        return $query->where('status', self::STATUS_CREDITED);
    }

    /**
     * Scope query to a specific member.
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Scope query to a specific campaign.
     */
    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('ad_campaign_id', $campaignId);
    }
}
