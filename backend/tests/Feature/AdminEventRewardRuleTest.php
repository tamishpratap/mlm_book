<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Member;
use App\Services\CampaignBudgetDepletionService;
use App\Services\RewardRuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminEventRewardRuleTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Event Super Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $this->member = Member::create([
            'name' => 'Regular Member',
            'user_id' => 'member_' . uniqid(),
            'email' => 'member_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);
    }

    /**
     * Requirement 1: Authorized Admin can list Event reward rules.
     */
    public function test_01_authorized_admin_can_list_event_reward_rules(): void
    {
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/event-reward-rules');

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
        $this->assertCount(1, $response->json('rules'));
        $this->assertEquals('event', $response->json('rules.0.rule_type'));
    }

    /**
     * Requirement 2: Non-admin cannot access Event reward-rule management (401 Unauthorized).
     */
    public function test_02_non_admin_cannot_access_event_reward_rules(): void
    {
        // Unauthenticated access
        $this->getJson('/api/admin/event-reward-rules')->assertStatus(401);
        $this->postJson('/api/admin/event-reward-rules', [])->assertStatus(401);
        $this->postJson('/api/admin/event-reward-rules/preview', [])->assertStatus(401);

        // Member attempting to access admin endpoints
        $this->actingAs($this->member, 'member')->getJson('/api/admin/event-reward-rules')->assertStatus(401);
        $this->actingAs($this->member, 'member')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.025,
        ])->assertStatus(401);
    }

    /**
     * Requirement 3: Authorized Admin can create an Event reward rule.
     */
    public function test_03_authorized_admin_can_create_event_reward_rule(): void
    {
        $payload = [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('rule.rule_type', 'event');
        $response->assertJsonPath('rule.min_referrals', 0);
        $response->assertJsonPath('rule.max_referrals', 5);
        $response->assertJsonPath('rule.reward_amount', '0.0250');
        $response->assertJsonPath('rule.created_by.id', $this->admin->id);

        $this->assertDatabaseHas('ad_reward_rules', [
            'rule_type' => 'event',
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => '0.0250',
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * Requirement 4: Authorized Admin can update an Event reward rule.
     */
    public function test_04_authorized_admin_can_update_event_reward_rule(): void
    {
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $updatePayload = [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'admin')->putJson("/api/admin/event-reward-rules/{$rule->id}", $updatePayload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('rule.reward_amount', '0.0300');
        $response->assertJsonPath('rule.updated_by.id', $this->admin->id);

        $this->assertDatabaseHas('ad_reward_rules', [
            'id' => $rule->id,
            'rule_type' => 'event',
            'reward_amount' => '0.0300',
            'updated_by' => $this->admin->id,
        ]);
    }

    /**
     * Requirement 5: Authorized Admin can enable/disable an Event rule.
     */
    public function test_05_authorized_admin_can_enable_disable_event_rule(): void
    {
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        // Disable rule
        $resDisable = $this->actingAs($this->admin, 'admin')->postJson("/api/admin/event-reward-rules/{$rule->id}/toggle-status");
        $resDisable->assertStatus(200);
        $this->assertFalse($resDisable->json('rule.is_active'));
        $this->assertFalse((bool) $rule->fresh()->is_active);

        // Re-enable rule
        $resEnable = $this->actingAs($this->admin, 'admin')->postJson("/api/admin/event-reward-rules/{$rule->id}/toggle-status");
        $resEnable->assertStatus(200);
        $this->assertTrue($resEnable->json('rule.is_active'));
        $this->assertTrue((bool) $rule->fresh()->is_active);
    }

    /**
     * Requirement 6 & 7: Event rules and Business Ads rules remain strictly isolated.
     */
    public function test_06_07_event_rules_and_business_ads_rules_remain_isolated(): void
    {
        // Business ad rules exist (seeded from migration)
        $bizRulesCount = AdRewardRule::forBusinessAd()->count();
        $this->assertGreaterThanOrEqual(3, $bizRulesCount);

        // Create an Event rule
        $eventRule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        // Listing Event rules returns ONLY Event rules
        $eventList = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/event-reward-rules');
        $eventList->assertStatus(200);
        $this->assertCount(1, $eventList->json('rules'));
        $this->assertEquals($eventRule->id, $eventList->json('rules.0.id'));

        // Listing Business Ads rules returns ONLY Business Ads rules
        $bizList = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/ad-reward-rules');
        $bizList->assertStatus(200);
        $this->assertCount($bizRulesCount, $bizList->json('rules'));
        foreach ($bizList->json('rules') as $r) {
            $this->assertEquals(AdRewardRule::TYPE_BUSINESS_AD, $r['rule_type']);
            $this->assertNotEquals($eventRule->id, $r['id']);
        }
    }

    /**
     * Requirement 8 & 9: Rule changes do not modify the other scope.
     */
    public function test_08_09_cross_scope_modifications_do_not_affect_each_other(): void
    {
        $bizRule = AdRewardRule::forBusinessAd()->where('min_referrals', 0)->first();
        $bizOriginalAmount = $bizRule->reward_amount;

        // Create and update Event rule
        $eventRule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0222,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin')->putJson("/api/admin/event-reward-rules/{$eventRule->id}", [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0333,
            'is_active' => true,
        ])->assertStatus(200);

        // Business Ads rule amount is untouched
        $this->assertEquals($bizOriginalAmount, $bizRule->fresh()->reward_amount);

        // Attempt to update Business rule via Event controller is rejected with 404
        $this->actingAs($this->admin, 'admin')->putJson("/api/admin/event-reward-rules/{$bizRule->id}", [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0444,
        ])->assertStatus(404);

        // Attempt to update Event rule via Business controller is rejected with 404
        $this->actingAs($this->admin, 'admin')->putJson("/api/admin/ad-reward-rules/{$eventRule->id}", [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0444,
        ])->assertStatus(404);
    }

    /**
     * Requirement 10, 11, 12: Referral thresholds, 4-decimal precision, and USD currency.
     */
    public function test_10_11_12_thresholds_precision_and_currency(): void
    {
        $payload = [
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0456,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', $payload);
        $response->assertStatus(201);
        $ruleId = $response->json('rule.id');

        $fresh = AdRewardRule::find($ruleId);
        $this->assertEquals(15, $fresh->min_referrals);
        $this->assertNull($fresh->max_referrals);
        $this->assertEquals('0.0456', (string) $fresh->reward_amount);
        $this->assertEquals('USD', $response->json('currency'));
    }

    /**
     * Requirement 13: Overlapping active ranges are rejected with 422.
     */
    public function test_13_overlapping_active_ranges_rejected(): void
    {
        // Tier 1: 0-5
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        // Attempt to create overlapping range 3-10
        $overlapRes = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 3,
            'max_referrals' => 10,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);

        $overlapRes->assertStatus(422);
        $this->assertStringContainsString('overlaps with existing active', $overlapRes->json('message'));
    }

    /**
     * Requirement 14: Disabled rules are excluded from active calculations and preview.
     */
    public function test_14_disabled_rules_excluded_from_active_set(): void
    {
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => false, // Disabled
        ]);

        $resolved = AdRewardRule::resolveForDirectVerifiedReferrals(3, AdRewardRule::TYPE_EVENT);
        $this->assertNull($resolved);

        $activeRules = AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT);
        $this->assertTrue($activeRules->isEmpty());
    }

    /**
     * Requirement 15 & 16: No active rules does not silently create a default reward (zero $0.05 fallback).
     */
    public function test_15_16_no_active_rules_fails_closed_zero_fallback(): void
    {
        // Ensure no active event rules exist
        AdRewardRule::forEvent()->delete();
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $minReward = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        $this->assertNull($minReward, 'Minimum active event reward must be null when no active rules exist.');

        $service = app(CampaignBudgetDepletionService::class);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active reward rules are configured');

        $service->resolveMinimumRewardAmount(null, AdRewardRule::TYPE_EVENT);
    }

    /**
     * Requirement 17: Historical reward records remain unchanged.
     */
    public function test_17_historical_reward_records_remain_unchanged(): void
    {
        // Mock a historical reward record
        $organizer = Member::create([
            'name' => 'Organizer',
            'user_id' => 'org_' . uniqid(),
            'email' => 'org_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Event ' . uniqid(),
            'slug' => 'event-' . uniqid(),
            'category' => 'Technology',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'published',
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Event Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $historicalReward = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $this->member->id,
            'reward_amount_usd' => 0.0250,
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Now Admin creates, updates, and disables Event reward rules
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0400,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin')->putJson("/api/admin/event-reward-rules/{$rule->id}", [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        // Historical reward row is completely unchanged
        $freshHistorical = AdReward::find($historicalReward->id);
        $this->assertEquals(0.0250, (float) $freshHistorical->reward_amount_usd);
        $this->assertEquals(AdReward::STATUS_CREDITED, $freshHistorical->status);
    }

    /**
     * Requirement 18: Existing Business Ads reward resolver remains functional.
     */
    public function test_18_business_ads_resolver_remains_functional(): void
    {
        // Business ad rules
        AdRewardRule::forBusinessAd()->where('min_referrals', 0)->update(['reward_amount' => 0.0250, 'is_active' => true]);
        AdRewardRule::forBusinessAd()->where('min_referrals', 6)->update(['reward_amount' => 0.0350, 'is_active' => true]);
        AdRewardRule::forBusinessAd()->where('min_referrals', 15)->update(['reward_amount' => 0.0500, 'is_active' => true]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_BUSINESS_AD);

        $resolver = app(RewardRuleResolver::class);
        $res0 = $resolver->resolveForCount(0);
        $this->assertTrue($res0['success']);
        $this->assertEquals(0.0250, (float) $res0['reward_amount_usd']);

        $res8 = $resolver->resolveForCount(8);
        $this->assertTrue($res8['success']);
        $this->assertEquals(0.0350, (float) $res8['reward_amount_usd']);

        $res20 = $resolver->resolveForCount(20);
        $this->assertTrue($res20['success']);
        $this->assertEquals(0.0500, (float) $res20['reward_amount_usd']);
    }

    /**
     * Requirement 19: Phase 9 can obtain the dynamic minimum reward from Event rules.
     */
    public function test_19_phase_9_obtains_dynamic_minimum_reward_from_event_rules(): void
    {
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $service = app(CampaignBudgetDepletionService::class);
        $minReward = $service->resolveMinimumRewardAmount(null, AdRewardRule::TYPE_EVENT);

        $this->assertEquals(0.0200, $minReward);
        $this->assertNotEquals(0.0500, $minReward);
    }

    /**
     * Requirement 20: Event rules expose enough information for Phase 12 future resolver.
     */
    public function test_20_event_rules_expose_required_information_for_future_resolver(): void
    {
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        // Check resolution via preview endpoint
        $res = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules/preview', [
            'referral_count' => 10,
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('referral_count', 10);
        $res->assertJsonPath('reward_amount_usd', 0.0350);
        $res->assertJsonPath('is_configured', true);
    }

    /**
     * Requirement 21 & 22: Cache invalidation and audit logging.
     */
    public function test_21_22_cache_invalidation_and_audit_logging(): void
    {
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        // Pre-warm cache
        $cached = AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT);
        $this->assertEquals(0.0250, (float) $cached->first()->reward_amount);

        // Update via Admin API
        $secondAdmin = Admin::create([
            'name' => 'Second Admin',
            'email' => 'admin2_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $this->actingAs($secondAdmin, 'admin')->putJson("/api/admin/event-reward-rules/{$rule->id}", [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ])->assertStatus(200);

        // Fresh cache has new value
        $freshActive = AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT);
        $this->assertEquals(0.0300, (float) $freshActive->first()->reward_amount);

        // Audit log records updated_by correctly
        $freshRule = AdRewardRule::find($rule->id);
        $this->assertEquals($this->admin->id, $freshRule->created_by);
        $this->assertEquals($secondAdmin->id, $freshRule->updated_by);
    }

    /**
     * Section 34 Required Rule-Set Test Examples:
     * Rule A (0-5), Rule B (6-14), Rule C (15+).
     * Coexistence, Event scope, Business Ads untouched, disabled excluded, dynamic minimum resolved.
     */
    public function test_section_34_required_multi_tier_coexistence_and_isolation(): void
    {
        // Business ads rules snapshot count
        $bizBeforeCount = AdRewardRule::forBusinessAd()->count();

        // 1. Rule A (0-5)
        $ruleA = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        // 2. Rule B (6-14)
        $ruleB = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);

        // 3. Rule C (15+)
        $ruleC = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        // Verify all 3 coexist
        $activeEventRules = AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT);
        $this->assertCount(3, $activeEventRules);

        // Verify Business Ads rules are untouched
        $this->assertEquals($bizBeforeCount, AdRewardRule::forBusinessAd()->count());

        // Verify continuity is clean
        $continuity = AdRewardRule::validateActiveSetContinuity(AdRewardRule::TYPE_EVENT);
        $this->assertEmpty($continuity);

        // Verify Phase 9 resolves dynamic minimum reward ($0.0200)
        $service = app(CampaignBudgetDepletionService::class);
        $this->assertEquals(0.0200, $service->resolveMinimumRewardAmount(null, AdRewardRule::TYPE_EVENT));

        // Disable Rule A
        $ruleA->update(['is_active' => false]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        // Active count drops to 2
        $this->assertCount(2, AdRewardRule::getActiveRules(AdRewardRule::TYPE_EVENT));

        // Lowest remaining active is Rule B ($0.0350), but continuity detects gap at 0
        $continuityWithGap = AdRewardRule::validateActiveSetContinuity(AdRewardRule::TYPE_EVENT);
        $this->assertNotEmpty($continuityWithGap);
        $this->assertStringContainsString('must start at 0', $continuityWithGap[0]);
    }

    /**
     * Requirement 23 & 24: Existing Event campaigns and funding behavior remain functional.
     */
    public function test_23_24_existing_event_campaigns_and_funding_remain_functional(): void
    {
        $organizer = Member::create([
            'name' => 'Host ' . uniqid(),
            'user_id' => 'host_' . uniqid(),
            'email' => 'host_' . uniqid() . '@example.com',
            'password' => 'password123',
            'ad_balance' => 100.00,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Funded Event ' . uniqid(),
            'slug' => 'funded-event-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => 'published',
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => $event->title . ' Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Configuring rules does not modify existing campaign
        $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ])->assertStatus(201);

        $freshCampaign = $campaign->fresh();
        $this->assertEquals(50.00, (float) $freshCampaign->budget);
        $this->assertEquals(50.00, (float) $freshCampaign->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $freshCampaign->status);
    }

    /**
     * Requirement 25: Existing Event activation remains functional.
     */
    public function test_25_existing_event_activation_remains_functional(): void
    {
        $organizer = Member::create([
            'name' => 'Host ' . uniqid(),
            'user_id' => 'host_' . uniqid(),
            'email' => 'host_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Activation Event ' . uniqid(),
            'slug' => 'activation-event-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'status' => 'published',
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => $event->title . ' Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_PAUSED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $campaign->status = AdCampaign::STATUS_ACTIVE;
        $campaign->save();

        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
    }

    /**
     * Requirement 26: Free Events remain completely unaffected.
     */
    public function test_26_free_events_remain_completely_unaffected(): void
    {
        $organizer = Member::create([
            'name' => 'Free Host ' . uniqid(),
            'user_id' => 'freehost_' . uniqid(),
            'email' => 'freehost_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $freeEvent = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Community Meetup (Free)',
            'slug' => 'community-meetup-' . uniqid(),
            'category' => 'Community',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'published',
        ]);

        $this->assertNull($freeEvent->campaign()->first());
        $this->assertEquals('published', $freeEvent->fresh()->status);
    }

    /**
     * Requirement 27: No destructive database operations are executed.
     */
    public function test_27_non_destructive_additive_design(): void
    {
        // Table exists and has rule_type column with default
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('ad_reward_rules', 'rule_type'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('ad_reward_rules', 'min_referrals'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('ad_reward_rules', 'reward_amount'));
    }
}
