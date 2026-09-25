<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Member;
use App\Services\EventRewardResolver;
use App\Services\RewardRuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralizedRewardManagementTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Reward Super Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $this->member = Member::create([
            'name' => 'Verified Test Member',
            'user_id' => 'member_' . uniqid(),
            'email' => 'member_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);
    }

    /**
     * Helper to seed standard central rules:
     * 0-5: 0.0250
     * 6-14: 0.0350
     * 15+: 0.0500
     */
    private function seedCentralRules(): void
    {
        AdRewardRule::query()->delete();

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_CENTRAL,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_CENTRAL,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_CENTRAL,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();
    }

    /**
     * Test 01: Non-admin is rejected with 401 Unauthorized across all centralized reward endpoints.
     */
    public function test_01_non_admin_cannot_access_central_reward_management(): void
    {
        $this->getJson('/api/admin/reward-rules')->assertStatus(401);
        $this->getJson('/api/admin/reward-rules/validate-active-set')->assertStatus(401);
        $this->postJson('/api/admin/reward-rules', [])->assertStatus(401);
        $this->postJson('/api/admin/reward-rules/preview', ['referral_count' => 5])->assertStatus(401);
        $this->getJson('/api/admin/reward-history/events')->assertStatus(401);
        $this->getJson('/api/admin/reward-history/ads')->assertStatus(401);
        $this->getJson('/api/admin/reward-history/metrics')->assertStatus(401);
    }

    /**
     * Test 02: Authorized admin can list central reward rules.
     */
    public function test_02_authorized_admin_can_list_central_reward_rules(): void
    {
        $this->seedCentralRules();

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/reward-rules');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'rules',
            'is_configuration_complete',
            'continuity_errors',
            'previews',
            'currency',
            'max_permissible_reward_usd',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('rules'));
        $this->assertEquals(AdRewardRule::TYPE_CENTRAL, $response->json('rules.0.rule_type'));
    }

    /**
     * Test 03: Active set structural validation reports valid deterministic coverage.
     */
    public function test_03_active_set_validation_reports_valid_coverage(): void
    {
        $this->seedCentralRules();

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/reward-rules/validate-active-set');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('validation.valid'));
        $this->assertEquals(0.0250, (float) $response->json('validation.minimum_active_reward_usd'));
    }

    /**
     * Test 04: Admin can create a central reward rule.
     */
    public function test_04_admin_can_create_central_reward_rule(): void
    {
        AdRewardRule::query()->delete();
        AdRewardRule::clearCache();

        $payload = [
            'min_referrals' => 0,
            'max_referrals' => 10,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/reward-rules', $payload);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(AdRewardRule::TYPE_CENTRAL, $response->json('rule.rule_type'));
        $this->assertEquals(0.0300, (float) $response->json('rule.reward_amount'));

        $this->assertDatabaseHas('ad_reward_rules', [
            'rule_type' => AdRewardRule::TYPE_CENTRAL,
            'min_referrals' => 0,
            'max_referrals' => 10,
            'reward_amount' => 0.0300,
            'is_active' => 1,
        ]);
    }

    /**
     * Test 05: Rule creation rejects invalid invariants.
     */
    public function test_05_rule_creation_rejects_invalid_invariants(): void
    {
        $this->seedCentralRules();

        // 1. Negative min_referrals
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/reward-rules', [
            'min_referrals' => -1,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ])->assertStatus(422);

        // 2. max_referrals less than min_referrals
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/reward-rules', [
            'min_referrals' => 10,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ])->assertStatus(422);

        // 3. reward_amount exceeding max permissible ($0.050 USD)
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/reward-rules', [
            'min_referrals' => 20,
            'max_referrals' => 30,
            'reward_amount' => 0.0600,
            'is_active' => true,
        ])->assertStatus(422);

        // 4. Overlap with existing active rule (e.g. 2 to 4 overlaps with 0 to 5)
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/reward-rules', [
            'min_referrals' => 2,
            'max_referrals' => 4,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ])->assertStatus(422);
    }

    /**
     * Test 06: Admin can update a central reward rule.
     */
    public function test_06_admin_can_update_central_rule(): void
    {
        $this->seedCentralRules();

        $rule = AdRewardRule::ofType(AdRewardRule::TYPE_CENTRAL)->where('min_referrals', 6)->firstOrFail();

        $response = $this->actingAs($this->admin, 'admin')->putJson("/api/admin/reward-rules/{$rule->id}", [
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0400,
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(0.0400, (float) $response->json('rule.reward_amount'));

        $this->assertDatabaseHas('ad_reward_rules', [
            'id' => $rule->id,
            'reward_amount' => 0.0400,
        ]);
    }

    /**
     * Test 07: Admin can toggle status of central rule.
     */
    public function test_07_admin_can_toggle_central_rule_status(): void
    {
        $this->seedCentralRules();

        $rule = AdRewardRule::ofType(AdRewardRule::TYPE_CENTRAL)->where('min_referrals', 15)->firstOrFail();
        $this->assertTrue((bool) $rule->is_active);

        $response = $this->actingAs($this->admin, 'admin')->postJson("/api/admin/reward-rules/{$rule->id}/toggle-status");

        $response->assertStatus(200);
        $this->assertFalse((bool) $response->json('rule.is_active'));
        $this->assertDatabaseHas('ad_reward_rules', [
            'id' => $rule->id,
            'is_active' => 0,
        ]);
    }

    /**
     * Test 08: Admin can preview referral count resolution.
     */
    public function test_08_admin_can_preview_referral_resolution(): void
    {
        $this->seedCentralRules();

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/reward-rules/preview', [
            'referral_count' => 8,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(0.0350, (float) $response->json('reward_amount_usd'));
        $this->assertEquals('6-14', $response->json('tier_label'));
        $this->assertEquals(6, $response->json('matched_rule.min_referrals'));
        $this->assertEquals(14, $response->json('matched_rule.max_referrals'));
    }

    /**
     * Test 09: Cross-Domain Dynamic Behavior (Section 31 verification):
     * A single central rule modification changes reward calculation dynamically for BOTH:
     * - Events (EventRewardResolver)
     * - Ads (RewardRuleResolver)
     */
    public function test_09_cross_domain_dynamic_behavior_for_events_and_ads(): void
    {
        $this->seedCentralRules();

        $eventResolver = app(EventRewardResolver::class);
        $adResolver = app(RewardRuleResolver::class);

        // Step 1: Initial state check (8 referrals matches slab 6-14 -> 0.0350 USD)
        $eventResolutionInitial = $eventResolver->resolveForCount(8);
        $adResolutionInitial = $adResolver->resolveForCount(8);

        $this->assertEquals(0.0350, $eventResolutionInitial['reward_amount_usd']);
        $this->assertEquals(0.0350, $adResolutionInitial['reward_amount_usd']);
        $this->assertEquals('6–14', $eventResolutionInitial['matched_range']['label']);
        $this->assertEquals('6–14', $adResolutionInitial['matched_range']['label']);

        // Step 2: Update the central rule for 6-14 from 0.0350 to 0.0425
        $rule = AdRewardRule::ofType(AdRewardRule::TYPE_CENTRAL)->where('min_referrals', 6)->firstOrFail();

        $updateResponse = $this->actingAs($this->admin, 'admin')->putJson("/api/admin/reward-rules/{$rule->id}", [
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0425,
            'is_active' => true,
        ]);
        $updateResponse->assertStatus(200);

        // Step 3: Test A (Section 31) - Verify Event reward resolver uses new rate
        $eventResolutionUpdated = $eventResolver->resolveForCount(8);
        $this->assertEquals(0.0425, $eventResolutionUpdated['reward_amount_usd'], 'Event resolver must reflect updated central rate');
        $this->assertEquals($rule->id, $eventResolutionUpdated['matched_rule_id']);

        // Step 4: Test B (Section 31) - Verify Ad reward resolver uses same new rate
        $adResolutionUpdated = $adResolver->resolveForCount(8);
        $this->assertEquals(0.0425, $adResolutionUpdated['reward_amount_usd'], 'Ad resolver must reflect exact same updated central rate');
        $this->assertEquals($rule->id, $adResolutionUpdated['matched_rule_id']);

        // Step 5: Test C (Section 31) - Check cross-tier consistency (0 referrals resolves 0.0250 for both)
        $eventZero = $eventResolver->resolveForCount(0);
        $adZero = $adResolver->resolveForCount(0);
        $this->assertEquals(0.0250, $eventZero['reward_amount_usd']);
        $this->assertEquals(0.0250, $adZero['reward_amount_usd']);

        // 20 referrals resolves 0.0500 for both
        $eventTwenty = $eventResolver->resolveForCount(20);
        $adTwenty = $adResolver->resolveForCount(20);
        $this->assertEquals(0.0500, $eventTwenty['reward_amount_usd']);
        $this->assertEquals(0.0500, $adTwenty['reward_amount_usd']);
    }

    /**
     * Test 10: Reward History Events Tab returns only Event rewards.
     */
    public function test_10_reward_history_events_tab_isolation(): void
    {
        $this->seedCentralRules();

        $event = Event::create([
            'organizer_id' => $this->member->id,
            'title' => 'Tech Summit 2026',
            'slug' => 'tech-summit-' . uniqid(),
            'description' => 'Annual Summit',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'status' => 'published',
            'is_paid' => true,
            'ticket_price' => 25.00,
        ]);

        $eventCampaign = AdCampaign::create([
            'campaign_id' => 'EVT-' . strtoupper(uniqid()),
            'campaign_name' => 'Event Campaign for Tech Summit',
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $this->member->id,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 100.00,
            'remaining_amount' => 90.00,
            'spent_amount' => 10.00,
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => 'AD-' . strtoupper(uniqid()),
            'campaign_name' => 'Ad Campaign for Shoes',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'member_id' => $this->member->id,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 100.00,
            'remaining_amount' => 90.00,
            'spent_amount' => 10.00,
        ]);

        $rule = AdRewardRule::firstOrFail();

        // Event reward record
        $eventReward = AdReward::create([
            'ad_campaign_id' => $eventCampaign->id,
            'member_id' => $this->member->id,
            'ad_reward_rule_id' => $rule->id,
            'reward_amount_usd' => 0.0250,
            'status' => 'credited',
            'direct_verified_referral_count' => 2,
            'tier_label' => '0-5',
            'qualifying_event_id' => 'EVENT-QUALIFY-' . uniqid(),
            'created_at' => now(),
        ]);

        // Ad reward record
        $adReward = AdReward::create([
            'ad_campaign_id' => $adCampaign->id,
            'member_id' => $this->member->id,
            'ad_reward_rule_id' => $rule->id,
            'reward_amount_usd' => 0.0250,
            'status' => 'credited',
            'direct_verified_referral_count' => 2,
            'tier_label' => '0-5',
            'qualifying_event_id' => 'AD-VISIT-' . uniqid(),
            'landing_page_url' => 'https://example.com/shoes',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/reward-history/events');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $data = $response->json('rewards.data');
        $this->assertCount(1, $data);
        $this->assertEquals($eventReward->id, $data[0]['id']);
        $this->assertEquals('Tech Summit 2026', $data[0]['ad_campaign']['event']['title']);
    }

    /**
     * Test 11: Reward History Ads Tab returns only Ad rewards.
     */
    public function test_11_reward_history_ads_tab_isolation(): void
    {
        $this->seedCentralRules();

        $event = Event::create([
            'organizer_id' => $this->member->id,
            'title' => 'Webinar 2026',
            'slug' => 'webinar-' . uniqid(),
            'description' => 'Online webinar',
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
            'status' => 'published',
            'is_paid' => true,
            'ticket_price' => 15.00,
        ]);

        $eventCampaign = AdCampaign::create([
            'campaign_id' => 'EVT-' . strtoupper(uniqid()),
            'campaign_name' => 'Event Campaign',
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $this->member->id,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 50.00,
            'remaining_amount' => 45.00,
            'spent_amount' => 5.00,
        ]);

        $adCampaign = AdCampaign::create([
            'campaign_id' => 'AD-' . strtoupper(uniqid()),
            'campaign_name' => 'Fashion Store Promo',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'member_id' => $this->member->id,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 50.00,
            'remaining_amount' => 45.00,
            'spent_amount' => 5.00,
        ]);

        $rule = AdRewardRule::firstOrFail();

        AdReward::create([
            'ad_campaign_id' => $eventCampaign->id,
            'member_id' => $this->member->id,
            'ad_reward_rule_id' => $rule->id,
            'reward_amount_usd' => 0.0250,
            'status' => 'credited',
            'direct_verified_referral_count' => 0,
            'tier_label' => '0-5',
            'qualifying_event_id' => 'EVENT-QUALIFY-' . uniqid(),
            'created_at' => now(),
        ]);

        $adReward = AdReward::create([
            'ad_campaign_id' => $adCampaign->id,
            'member_id' => $this->member->id,
            'ad_reward_rule_id' => $rule->id,
            'reward_amount_usd' => 0.0250,
            'status' => 'credited',
            'direct_verified_referral_count' => 0,
            'tier_label' => '0-5',
            'qualifying_event_id' => 'AD-VISIT-' . uniqid(),
            'landing_page_url' => 'https://fashion.example.com',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/reward-history/ads');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $data = $response->json('rewards.data');
        $this->assertCount(1, $data);
        $this->assertEquals($adReward->id, $data[0]['id']);
        $this->assertEquals('Fashion Store Promo', $data[0]['ad_campaign']['campaign_name']);
    }

    /**
     * Test 12: Reward history metrics breakdown.
     */
    public function test_12_reward_history_metrics_breakdown(): void
    {
        $this->seedCentralRules();

        $adCampaign = AdCampaign::create([
            'campaign_id' => 'AD-' . strtoupper(uniqid()),
            'campaign_name' => 'Brand Campaign',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'member_id' => $this->member->id,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 50.00,
            'remaining_amount' => 45.00,
            'spent_amount' => 5.00,
        ]);

        $rule = AdRewardRule::firstOrFail();

        AdReward::create([
            'ad_campaign_id' => $adCampaign->id,
            'member_id' => $this->member->id,
            'ad_reward_rule_id' => $rule->id,
            'reward_amount_usd' => 0.0350,
            'status' => 'credited',
            'direct_verified_referral_count' => 7,
            'tier_label' => '6-14',
            'qualifying_event_id' => 'AD-VISIT-' . uniqid(),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/reward-history/metrics');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('overall', $response->json('metrics'));
        $this->assertArrayHasKey('events', $response->json('metrics'));
        $this->assertArrayHasKey('ads', $response->json('metrics'));
        $this->assertEquals(1, $response->json('metrics.ads.total_rewards_count'));
        $this->assertEquals(0.0350, (float) $response->json('metrics.ads.total_rewards_paid'));
    }

    /**
     * Test 13: Historical reward records remain immutable when rules are changed or deactivated.
     */
    public function test_13_historical_rewards_remain_immutable(): void
    {
        $this->seedCentralRules();

        $adCampaign = AdCampaign::create([
            'campaign_id' => 'AD-' . strtoupper(uniqid()),
            'campaign_name' => 'Historical Test',
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'member_id' => $this->member->id,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'budget' => 50.00,
            'remaining_amount' => 45.00,
            'spent_amount' => 5.00,
        ]);

        $rule = AdRewardRule::firstOrFail();

        $reward = AdReward::create([
            'ad_campaign_id' => $adCampaign->id,
            'member_id' => $this->member->id,
            'ad_reward_rule_id' => $rule->id,
            'rule_min_referrals' => 0,
            'rule_max_referrals' => 5,
            'reward_amount_usd' => 0.0250,
            'status' => 'credited',
            'direct_verified_referral_count' => 3,
            'qualifying_event_id' => 'VISIT-HISTORICAL',
            'created_at' => now()->subDays(10),
        ]);

        // Now modify the rule
        $rule->update(['reward_amount' => 0.0300]);

        // Verify that the historical record is unchanged
        $reward->refresh();
        $this->assertEquals(0.0250, (float) $reward->reward_amount_usd);
        $this->assertEquals('0–5', $reward->tier_label);
        $this->assertEquals(3, $reward->direct_verified_referral_count);
    }
}
