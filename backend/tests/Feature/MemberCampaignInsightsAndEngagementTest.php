<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\Admin;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCampaignInsightsAndEngagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Campaign owner can view own campaign analytics with exact financial reconciliation.
     */
    public function test_campaign_owner_can_view_own_campaign_analytics(): void
    {
        $owner = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'spent_amount' => 8.50,
            'remaining_amount' => 21.50,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $this->actingAs($owner, 'member');

        $res = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/analytics");

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'metrics' => [
                    'budget' => 30.00,
                    'fee_percent' => 2.50,
                    'fee_amount' => 0.75,
                    'wallet_debit' => 30.75,
                    'spent_amount' => 8.50,
                    'rewards_paid' => 8.50,
                    'remaining_amount' => 21.50,
                    'remaining_campaign_budget' => 21.50,
                    'financial_reconciliation' => [
                        'initial_campaign_budget' => 30.00,
                        'rewards_paid' => 8.50,
                        'remaining_campaign_budget' => 21.50,
                        'reconciles_exactly' => true,
                    ],
                ],
            ]);
    }

    /**
     * TEST 2: Cross-member access blocked: Member A cannot access Member B's campaign analytics.
     */
    public function test_cross_member_cannot_access_other_member_campaign_analytics(): void
    {
        $ownerB = $this->createMember(['name' => 'Owner B']);
        $pageB = $this->createBusinessPage($ownerB);
        $campaignB = $this->createActiveCampaign($pageB, $ownerB);

        $memberA = $this->createMember(['name' => 'Attacker A']);
        $this->actingAs($memberA, 'member');

        $res = $this->getJson("/api/member/business-pages/{$pageB->slug}/ad-campaigns/{$campaignB->id}/analytics");

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * TEST 3: Campaign owner can view itemized user engagements with privacy protection (NO email, NO phone).
     */
    public function test_campaign_owner_can_view_itemized_user_engagements_with_privacy_safe_fields(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'spent_amount' => 0.05, 'remaining_amount' => 29.95]);

        $engagerUser = $this->createMember([
            'name' => 'John Doe',
            'email' => 'john.private@example.com',
            'phone' => '+1234567890',
            'user_id' => 'johndoe',
            'mobile_verified_at' => now(),
        ]);

        // Create 1 Click
        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $engagerUser->id,
            'placement' => 'social_feed',
            'click_key' => 'click_token_123',
        ]);

        // Create 1 Reward
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $engagerUser->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'reward_token_456',
            'status' => 'credited',
        ]);

        $this->actingAs($owner, 'member');

        $res = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/engagements");

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'campaign_id' => $campaign->id,
                'summary' => [
                    'total_engagements' => 2,
                    'total_rewards_count' => 1,
                    'total_rewards_paid' => 0.05,
                    'total_clicks_count' => 1,
                ],
            ]);

        $data = $res->json('engagements.data');
        $this->assertCount(2, $data);

        // Verify privacy: email and phone MUST NOT be present anywhere in the engagement payload
        $responseContent = $res->getContent();
        $this->assertStringNotContainsString('john.private@example.com', $responseContent);
        $this->assertStringNotContainsString('+1234567890', $responseContent);

        // Verify public safe user fields
        $this->assertSame('John Doe', $data[0]['user']['name']);
        $this->assertSame('johndoe', $data[0]['user']['username']);
        $this->assertTrue($data[0]['user']['is_verified']);
        $this->assertNotEmpty($data[0]['user']['avatar_url']);
        $this->assertArrayHasKey('profile_photo', $data[0]['user']);
    }

    /**
     * TEST 4: Cross-member access blocked: Member A cannot access Member B's engagements.
     */
    public function test_cross_member_cannot_access_other_member_campaign_engagements(): void
    {
        $ownerB = $this->createMember(['name' => 'Owner B']);
        $pageB = $this->createBusinessPage($ownerB);
        $campaignB = $this->createActiveCampaign($pageB, $ownerB);

        $memberA = $this->createMember(['name' => 'Attacker A']);
        $this->actingAs($memberA, 'member');

        $res = $this->getJson("/api/member/business-pages/{$pageB->slug}/ad-campaigns/{$campaignB->id}/engagements");

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * TEST 5: Search and Pagination on engagements.
     */
    public function test_search_and_pagination_on_engagements(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $engager1 = $this->createMember(['name' => 'Alice Wonder', 'user_id' => 'alice123']);
        $engager2 = $this->createMember(['name' => 'Bob Builder', 'user_id' => 'bobbuilder']);

        AdClick::create(['ad_campaign_id' => $campaign->id, 'member_id' => $engager1->id]);
        AdClick::create(['ad_campaign_id' => $campaign->id, 'member_id' => $engager2->id]);

        $this->actingAs($owner, 'member');

        // Search for Alice
        $searchRes = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/engagements?q=Alice");
        $searchRes->assertOk();
        $this->assertCount(1, $searchRes->json('engagements.data'));
        $this->assertSame('Alice Wonder', $searchRes->json('engagements.data.0.user.name'));

        // Pagination with per_page=1
        $pageRes = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/engagements?per_page=1&page=1");
        $pageRes->assertOk();
        $this->assertCount(1, $pageRes->json('engagements.data'));
        $this->assertEquals(2, $pageRes->json('engagements.total'));
        $this->assertEquals(2, $pageRes->json('engagements.last_page'));
    }

    /**
     * TEST 5: Filter engagements by action (rewards vs clicks).
     */
    public function test_filter_engagements_by_action(): void
    {
        $owner = $this->createMember();
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);
        $visitor = $this->createMember();

        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor->id,
        ]);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $visitor->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'q_token_1',
            'status' => 'credited',
        ]);

        $this->actingAs($owner, 'member');

        // 1. Rewards only
        $resRewards = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/engagements?action=rewards");
        $resRewards->assertOk();
        $this->assertCount(1, $resRewards->json('engagements.data'));
        $this->assertSame('reward', $resRewards->json('engagements.data.0.type'));

        // 2. Clicks only
        $resClicks = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/engagements?action=clicks");
        $resClicks->assertOk();
        $this->assertCount(1, $resClicks->json('engagements.data'));
        $this->assertSame('click', $resClicks->json('engagements.data.0.type'));
    }

    /**
     * TEST 6: Financial reconciliation between Member analytics and Admin analytics for the same campaign.
     */
    public function test_member_and_admin_analytics_for_same_campaign_match_exactly(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $owner = $this->createMember(['ad_balance' => 100.00]);
        $page = $this->createBusinessPage($owner);

        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'fee_percent' => 2.50,
            'fee_amount' => 0.75,
            'wallet_debit' => 30.75,
            'spent_amount' => 12.00,
            'remaining_amount' => 18.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        // 1. Member Analytics Request
        $this->actingAs($owner, 'member');
        $memberRes = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/analytics");
        $memberRes->assertOk();

        // 2. Admin Show Request
        $this->actingAs($admin, 'admin');
        $adminRes = $this->getJson("/api/admin/ad-campaigns/{$campaign->id}");
        $adminRes->assertOk();

        $mMetrics = $memberRes->json('metrics');
        $aSummary = $adminRes->json('campaign.financial_summary');

        $this->assertEquals($mMetrics['budget'], $aSummary['campaign_budget']);
        $this->assertEquals($mMetrics['fee_amount'], $aSummary['admin_fee_amount']);
        $this->assertEquals($mMetrics['wallet_debit'], $aSummary['wallet_debit']);
        $this->assertEquals($mMetrics['rewards_paid'], $aSummary['rewards_paid']);
        $this->assertEquals($mMetrics['remaining_campaign_budget'], $aSummary['remaining_campaign_budget']);
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
            'page_name' => 'Biz Page ' . uniqid(),
            'page_username' => 'biz_' . strtolower(uniqid()),
            'slug' => 'biz-slug-' . strtolower(uniqid()),
            'category' => 'Crypto & FinTech',
            'description' => 'Business page for testing.',
            'status' => 'active',
            'visibility' => 'public',
        ], $attributes));
    }

    private function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Member Test Campaign ' . uniqid(),
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
