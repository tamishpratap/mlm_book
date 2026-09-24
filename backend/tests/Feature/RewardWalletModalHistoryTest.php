<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RewardWalletModalHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, float $rewardBalance = 0.00): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'phone' => '+9198' . random_int(10000000, 99999999),
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'reward_balance' => $rewardBalance,
        ]);
    }

    private function createCampaignWithBusinessPage(Member $owner): AdCampaign
    {
        $bizPage = BusinessPage::create([
            'page_id' => 'BP_' . strtoupper(Str::random(8)),
            'member_id' => $owner->id,
            'page_name' => 'Apex Crypto Network',
            'page_username' => 'apexcrypto_' . random_int(100, 999),
            'slug' => 'apex-crypto-' . uniqid(),
            'category' => 'Technology & IT Services',
            'description' => 'A crypto network description with over twenty characters.',
            'country' => 'India',
            'visibility' => 'public',
            'status' => 'active',
            'logo' => 'uploads/business_pages/logos/apex_logo.png',
        ]);

        return AdCampaign::create([
            'campaign_id' => 'camp_' . Str::random(12),
            'business_page_id' => $bizPage->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Apex Launch Promotion',
            'budget' => 100.00,
            'remaining_budget' => 95.00,
            'daily_budget' => 10.00,
            'reward_per_interaction' => 0.05,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);
    }

    public function test_reward_wallet_endpoints_require_authentication(): void
    {
        $this->getJson('/api/member/rewards/wallet')->assertUnauthorized();
        $this->getJson('/api/member/rewards/history')->assertUnauthorized();
    }

    public function test_member_with_zero_rewards_loads_successfully_without_sql_errors(): void
    {
        $member = $this->createMember('New Member', 0.15);
        $this->actingAs($member, 'member');

        $walletRes = $this->getJson('/api/member/rewards/wallet');
        $walletRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('wallet.reward_balance', 0.15)
            ->assertJsonPath('wallet.total_reward_count', 0);

        $historyRes = $this->getJson('/api/member/rewards/history');
        $historyRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rewards.total', 0)
            ->assertJsonCount(0, 'rewards.data');
    }

    public function test_member_with_rewards_loads_history_with_business_page_without_column_errors(): void
    {
        $owner = $this->createMember('Campaign Owner');
        $earner = $this->createMember('Tamish Pratap Singh', 0.15);

        // Create 3 credited rewards across 3 distinct campaigns
        for ($i = 1; $i <= 3; $i++) {
            $campaign = $this->createCampaignWithBusinessPage($owner);
            AdReward::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $earner->id,
                'ad_reward_rule_id' => null,
                'direct_verified_referral_count' => 1,
                'rule_min_referrals' => 0,
                'rule_max_referrals' => 5,
                'rule_version' => '1788327810',
                'reward_amount_usd' => 0.05,
                'qualifying_event_id' => 'event_' . $i . '_' . Str::random(8),
                'landing_page_url' => 'https://example.com/landing',
                'status' => AdReward::STATUS_CREDITED,
            ]);
        }

        $this->actingAs($earner, 'member');

        $response = $this->getJson('/api/member/rewards/history');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('rewards.total', 3)
            ->assertJsonCount(3, 'rewards.data');

        $firstReward = $response->json('rewards.data.0');
        $this->assertEquals(0.05, $firstReward['reward_amount_usd']);
        $this->assertSame('+$0.0500 USD', $firstReward['reward_formatted']);
        $this->assertSame('credited', $firstReward['status']);
        $this->assertSame('Apex Launch Promotion', $firstReward['campaign']['campaign_name']);
        $this->assertSame('Apex Crypto Network', $firstReward['campaign']['business_page']['name']);
        $this->assertNotNull($firstReward['campaign']['business_page']['slug']);
    }

    public function test_history_filters_only_credited_rewards(): void
    {
        $owner = $this->createMember('Campaign Owner');
        $campaign1 = $this->createCampaignWithBusinessPage($owner);
        $campaign2 = $this->createCampaignWithBusinessPage($owner);

        $earner = $this->createMember('Reward Earner', 0.05);

        // Credited reward on campaign 1
        AdReward::create([
            'ad_campaign_id' => $campaign1->id,
            'member_id' => $earner->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'evt_credited',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Pending / rejected reward on campaign 2
        AdReward::create([
            'ad_campaign_id' => $campaign2->id,
            'member_id' => $earner->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'evt_pending',
            'status' => AdReward::STATUS_PENDING,
        ]);

        $this->actingAs($earner, 'member');

        $response = $this->getJson('/api/member/rewards/history');
        $response->assertOk()
            ->assertJsonPath('rewards.total', 1)
            ->assertJsonCount(1, 'rewards.data')
            ->assertJsonPath('rewards.data.0.qualifying_event_id', 'evt_credited');
    }

    public function test_user_isolation_cannot_access_other_member_rewards(): void
    {
        $owner = $this->createMember('Campaign Owner');
        $campaign = $this->createCampaignWithBusinessPage($owner);

        $memberA = $this->createMember('Member A', 1.00);
        $memberB = $this->createMember('Member B', 5.00);

        // Create reward for Member B
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $memberB->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'evt_member_b',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Act as Member A and try to pass member_id of Member B
        $this->actingAs($memberA, 'member');

        $response = $this->getJson("/api/member/rewards/history?member_id={$memberB->id}");
        $response->assertOk()
            ->assertJsonPath('rewards.total', 0)
            ->assertJsonCount(0, 'rewards.data');

        $walletRes = $this->getJson("/api/member/rewards/wallet?member_id={$memberB->id}");
        $walletRes->assertOk()
            ->assertJsonPath('wallet.reward_balance', 1)
            ->assertJsonPath('wallet.total_reward_count', 0);
    }

    public function test_historical_values_not_recalculated_when_rules_change(): void
    {
        $owner = $this->createMember('Campaign Owner');
        $campaign = $this->createCampaignWithBusinessPage($owner);

        $earner = $this->createMember('Member Earner', 0.04);

        // Historical reward created with fixed snapshot of 0.0400
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $earner->id,
            'ad_reward_rule_id' => 1,
            'direct_verified_referral_count' => 2,
            'rule_min_referrals' => 0,
            'rule_max_referrals' => 5,
            'rule_version' => 'historical_v1',
            'reward_amount_usd' => 0.0400,
            'qualifying_event_id' => 'evt_historical',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Even if active reward rules change
        AdRewardRule::query()->update(['reward_amount' => 0.0800]);
        AdRewardRule::clearCache();

        $this->actingAs($earner, 'member');

        $response = $this->getJson('/api/member/rewards/history');
        $response->assertOk()
            ->assertJsonPath('rewards.data.0.reward_amount_usd', 0.04)
            ->assertJsonPath('rewards.data.0.reward_formatted', '+$0.0400 USD');
    }
}
