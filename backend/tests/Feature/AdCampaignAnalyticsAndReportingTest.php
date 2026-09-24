<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignAnalyticsAndReportingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Admin analytics endpoint returns accurate platform-wide financial totals.
     */
    public function test_admin_analytics_endpoint_returns_accurate_totals_and_metrics(): void
    {
        Setting::set('campaign_platform_fee_percent', 2.50);

        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $owner = $this->createMember(['ad_balance' => 200.00]);
        $page = $this->createBusinessPage($owner);

        // Campaign 1: $30 budget, $12 rewards paid (240 visits), $18 remaining
        $c1 = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'spent_amount' => 12.00,
            'remaining_amount' => 18.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        // Campaign 2: $50 budget, $50 rewards paid (1000 visits), $0 remaining, stopped
        $c2 = $this->createActiveCampaign($page, $owner, [
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'spent_amount' => 50.00,
            'remaining_amount' => 0.00,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        // Add 5 reward records (distinct members for 1-reward-per-member-per-campaign constraint)
        for ($i = 1; $i <= 5; $i++) {
            $visitor = $this->createMember(['email' => "analytics_visitor_{$i}@example.com"]);
            AdReward::create([
                'ad_campaign_id' => $c1->id,
                'member_id' => $visitor->id,
                'reward_amount_usd' => 0.05,
                'qualifying_event_id' => "analytics_event_{$i}",
                'status' => AdReward::STATUS_CREDITED,
            ]);
        }

        $response = $this->getJson('/api/admin/ad-campaigns/analytics');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'metrics' => [
                    'total_campaigns' => 2,
                    'active_campaigns' => 1,
                    'stopped' => 1,
                    'total_budget' => 80.00,
                    'total_platform_fees' => 2.00,
                    'total_wallet_debits' => 82.00,
                    'total_rewards_paid' => 0.25, // 5 rewards x $0.05
                    'total_spent' => 62.00,
                    'total_remaining' => 18.00,
                    'total_verified_visits' => 5,
                ],
            ]);
    }

    /**
     * TEST 2: Admin rewards history endpoint with search, status filtering, and date presets.
     */
    public function test_admin_reward_history_ledger_endpoint_with_filtering_and_search(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $owner = $this->createMember(['name' => 'Advertiser One']);
        $visitor1 = $this->createMember(['name' => 'Alice Verified', 'email' => 'alice@verified.com', 'mobile_verified_at' => now()]);
        $visitor2 = $this->createMember(['name' => 'Alice Two', 'email' => 'alicetwo@verified.com', 'mobile_verified_at' => now()]);
        $page = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($page, $owner, ['campaign_name' => 'Target Alpha']);

        // Create reward records
        $r1 = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor1->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'alpha_token_101',
            'status' => 'credited',
        ]);

        $r2 = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor2->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'beta_token_202',
            'status' => 'rejected',
        ]);

        // 1. All rewards
        $resAll = $this->getJson('/api/admin/ad-campaigns/rewards');
        $resAll->assertOk()
            ->assertJson([
                'success' => true,
                'metrics' => [
                    'total_rewards_count' => 2,
                    'total_rewards_paid' => 0.05, // only credited
                    'total_verified_members' => 1,
                    'total_rewarded_campaigns' => 1,
                ],
            ]);

        // 2. Filter by status: credited
        $resCredited = $this->getJson('/api/admin/ad-campaigns/rewards?status=credited');
        $resCredited->assertOk();
        $this->assertCount(1, $resCredited->json('rewards.data'));
        $this->assertSame('alpha_token_101', $resCredited->json('rewards.data.0.qualifying_event_id'));

        // 3. Search by event ID
        $resSearch = $this->getJson('/api/admin/ad-campaigns/rewards?q=beta_token_202');
        $resSearch->assertOk();
        $this->assertCount(1, $resSearch->json('rewards.data'));
        $this->assertSame('beta_token_202', $resSearch->json('rewards.data.0.qualifying_event_id'));

        // 4. Search by member name
        $resMemberSearch = $this->getJson('/api/admin/ad-campaigns/rewards?q=Alice');
        $resMemberSearch->assertOk();
        $this->assertCount(2, $resMemberSearch->json('rewards.data'));
    }

    /**
     * TEST 3: Single campaign reward history endpoint.
     */
    public function test_single_campaign_reward_history_endpoint(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $owner = $this->createMember();
        $visitor1 = $this->createMember();
        $visitor2 = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 29.90, 'spent_amount' => 0.10]);

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor1->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'camp_token_1',
            'status' => 'credited',
        ]);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor2->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'camp_token_2',
            'status' => 'credited',
        ]);

        $res = $this->getJson("/api/admin/ad-campaigns/{$campaign->id}/rewards");
        $res->assertOk()
            ->assertJson([
                'success' => true,
                'campaign_id' => $campaign->id,
                'summary' => [
                    'total_rewards_count' => 2,
                    'total_rewards_paid' => 0.10,
                    'remaining_budget' => 29.90,
                ],
            ]);
    }

    /**
     * TEST 4: Admin campaign detail returns exact financial reconciliation.
     */
    public function test_admin_campaign_show_endpoint_contains_exact_financial_reconciliation(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'remaining_amount' => 18.00,
            'spent_amount' => 12.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $res = $this->getJson("/api/admin/ad-campaigns/{$campaign->id}");

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'campaign' => [
                    'financial_summary' => [
                        'campaign_budget' => 30.00,
                        'admin_fee_amount' => 0.75,
                        'wallet_debit' => 30.75,
                        'rewards_paid' => 12.00,
                        'remaining_campaign_budget' => 18.00,
                        'reconciles_exactly' => true,
                    ],
                ],
            ]);
    }

    /**
     * TEST 5: Admin export endpoint outputs structured campaign financial data.
     */
    public function test_admin_export_endpoint_returns_structured_financial_records(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'fee_amount' => 0.75, 'wallet_debit' => 30.75]);

        $res = $this->getJson('/api/admin/ad-campaigns/export');

        $res->assertOk()
            ->assertJsonStructure([
                'success',
                'campaigns' => [
                    '*' => [
                        'id',
                        'campaign_id',
                        'campaign_name',
                        'business_page',
                        'advertiser',
                        'budget',
                        'fee_percent',
                        'fee_amount',
                        'wallet_debit',
                        'rewards_paid',
                        'remaining_budget',
                        'verified_visits',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * TEST 6: Authorization & Privacy: Normal member is blocked from Admin endpoints (401/403).
     */
    public function test_member_cannot_access_admin_analytics_or_reward_history(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        // Admin analytics blocked
        $res1 = $this->getJson('/api/admin/ad-campaigns/analytics');
        $res1->assertStatus(401);

        // Admin rewards history blocked
        $res2 = $this->getJson('/api/admin/ad-campaigns/rewards');
        $res2->assertStatus(401);

        // Admin export blocked
        $res3 = $this->getJson('/api/admin/ad-campaigns/export');
        $res3->assertStatus(401);
    }

    private function createAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'name' => 'Super Admin',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => 'secret123',
        ], $attributes));
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Member ' . uniqid(),
            'email' => 'member-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'ad_balance' => 0.00,
            'reward_balance' => 0.00,
        ], $attributes));
    }

    private function createBusinessPage(Member $owner, array $attributes = []): BusinessPage
    {
        return BusinessPage::create(array_merge([
            'member_id' => $owner->id,
            'page_name' => 'Biz Corp ' . uniqid(),
            'page_username' => 'bizcorp_' . strtolower(uniqid()),
            'slug' => 'biz-corp-' . strtolower(uniqid()),
            'category' => 'Crypto, Forex & FinTech MLM',
            'description' => 'Business page for ad reporting testing.',
            'status' => 'active',
            'visibility' => 'public',
        ], $attributes));
    }

    private function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Active Report Campaign ' . uniqid(),
            'budget' => 30.00,
            'currency' => 'USD',
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'spent_amount' => 0.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attributes));
    }
}
