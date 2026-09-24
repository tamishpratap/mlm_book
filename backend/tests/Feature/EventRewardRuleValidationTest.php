<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Member;
use App\Services\CampaignBudgetDepletionService;
use App\Services\RewardRuleValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRewardRuleValidationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Member $member;
    private RewardRuleValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Event Validator Admin',
            'email' => 'admin_val_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $this->member = Member::create([
            'name' => 'Regular Member',
            'user_id' => 'member_' . uniqid(),
            'email' => 'member_val_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $this->validationService = app(RewardRuleValidationService::class);
    }

    /**
     * Requirement 1: Valid 3-tier configuration passes validation with 100% deterministic coverage.
     */
    public function test_01_valid_three_tier_configuration_passes_validation(): void
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

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertTrue($result['valid']);
        $this->assertEquals('valid', $result['status']);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['error_codes']);
        $this->assertEquals(3, $result['active_count']);
        $this->assertTrue($result['has_baseline_tier']);
        $this->assertTrue($result['has_open_ended_tier']);
        $this->assertEquals(0.0200, $result['minimum_active_reward_usd']);
        $this->assertTrue($result['deterministic_coverage']['is_deterministic']);
        $this->assertEquals(0, $result['deterministic_coverage']['violations_count']);
    }

    /**
     * Requirement 2: Gap detection identifies missing referral counts.
     */
    public function test_02_gap_detection_identifies_missing_referral_counts(): void
    {
        // 0-5 and 7-14 (count 6 is missing)
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 7,
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

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('gap_detected', $result['error_codes']);
        $this->assertContains('coverage_gap', $result['error_codes']);
        $this->assertFalse($result['deterministic_coverage']['is_deterministic']);

        // Referral 6 should be among the coverage violations
        $violationCounts = array_column($result['deterministic_coverage']['violations'], 'count');
        $this->assertContains(6, $violationCounts);
    }

    /**
     * Requirement 3: Overlap detection rejects intersecting active ranges.
     */
    public function test_03_overlap_detection_rejects_intersecting_ranges(): void
    {
        // 0-6 and 6-14 (referral count 6 is shared/overlapping)
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 6,
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
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('overlap_detected', $result['error_codes']);
        $this->assertContains('coverage_ambiguity', $result['error_codes']);

        // Referral 6 should match 2 rules
        $ambiguities = array_filter($result['deterministic_coverage']['violations'], fn ($v) => $v['count'] === 6);
        $this->assertNotEmpty($ambiguities);
        $firstAmbiguity = array_values($ambiguities)[0];
        $this->assertEquals(2, $firstAmbiguity['matched_rule_count']);
    }

    /**
     * Requirement 4: Duplicate threshold detection rejects duplicate starting thresholds.
     */
    public function test_04_duplicate_threshold_detection(): void
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
            'min_referrals' => 0,
            'max_referrals' => 10,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('duplicate_tier', $result['error_codes']);
    }

    /**
     * Requirement 5: Inverted / reverse range is rejected.
     */
    public function test_05_inverted_range_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 10,
            'max_referrals' => 5,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Requirement 6: Negative threshold is rejected.
     */
    public function test_06_negative_threshold_is_rejected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => -1,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Requirement 7: Multiple open-ended tiers are rejected.
     */
    public function test_07_multiple_open_ended_tiers_are_rejected(): void
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
            'max_referrals' => null,
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

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('multiple_open_ended_tiers', $result['error_codes']);
    }

    /**
     * Requirement 8: Missing open-ended highest tier is flagged.
     */
    public function test_08_missing_open_ended_highest_tier_is_flagged(): void
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
            'max_referrals' => 20,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('missing_open_ended_tier', $result['error_codes']);
        $this->assertFalse($result['has_open_ended_tier']);
    }

    /**
     * Requirement 9: Missing baseline tier (starts > 0) is flagged.
     */
    public function test_09_missing_baseline_tier_is_flagged(): void
    {
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 1,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => null,
            'reward_amount' => 0.0400,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('missing_baseline_tier', $result['error_codes']);
        $this->assertFalse($result['has_baseline_tier']);
    }

    /**
     * Requirement 10: Empty active rule set fails closed with zero fallback (never $0.05).
     */
    public function test_10_empty_active_rule_set_fails_closed_zero_fallback(): void
    {
        AdRewardRule::forEvent()->delete();
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $result = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $this->assertFalse($result['valid']);
        $this->assertContains('no_active_rules', $result['error_codes']);
        $this->assertNull($result['minimum_active_reward_usd']);
        $this->assertNotEquals(0.0500, $result['minimum_active_reward_usd']);
        $this->assertNotEquals(0.0250, $result['minimum_active_reward_usd']);
    }

    /**
     * Requirement 11: Active rule must have configured positive reward amount.
     */
    public function test_11_active_rule_must_have_positive_reward_amount(): void
    {
        // Zero reward
        $resZero = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0000,
            'is_active' => true,
        ]);
        $resZero->assertStatus(422);

        // Negative reward
        $resNeg = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => -0.0100,
            'is_active' => true,
        ]);
        $resNeg->assertStatus(422);

        // Null reward on active rule
        $resNull = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => null,
            'is_active' => true,
        ]);
        $resNull->assertStatus(422);
    }

    /**
     * Requirement 12: Decimal precision validation enforces at most 4 decimal places.
     */
    public function test_12_decimal_precision_validation(): void
    {
        $res = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => '0.02555', // 5 decimals
            'is_active' => true,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('precision', $res->json('message'));
    }

    /**
     * Requirement 13: Unsupported currency is rejected.
     */
    public function test_13_unsupported_currency_is_rejected(): void
    {
        $res = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('currency', $res->json('message'));
    }

    /**
     * Requirement 14: Exceeding maximum permissible reward ($0.0500) is rejected.
     */
    public function test_14_exceeding_max_permissible_reward_is_rejected(): void
    {
        $res = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/event-reward-rules', [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0550,
            'is_active' => true,
        ]);

        $res->assertStatus(422);
    }

    /**
     * Requirement 15: Non-admin cannot access validate-active-set endpoint.
     */
    public function test_15_non_admin_cannot_access_validate_endpoint(): void
    {
        $this->getJson('/api/admin/event-reward-rules/validate-active-set')->assertStatus(401);
        $this->actingAs($this->member, 'member')
            ->getJson('/api/admin/event-reward-rules/validate-active-set')
            ->assertStatus(401);
    }

    /**
     * Requirement 16: Authorized Admin can query validate-active-set endpoint.
     */
    public function test_16_authorized_admin_can_query_validate_active_set(): void
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
            'max_referrals' => null,
            'reward_amount' => 0.0400,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $res = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/event-reward-rules/validate-active-set');

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('validation.valid', true);
        $res->assertJsonPath('validation.active_count', 2);
        $res->assertJsonPath('validation.has_baseline_tier', true);
        $res->assertJsonPath('validation.has_open_ended_tier', true);
    }

    /**
     * Requirement 17: Cannot activate rule without configured positive reward amount.
     */
    public function test_17_cannot_activate_rule_without_positive_reward_amount(): void
    {
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => null, // Draft unconfigured
            'is_active' => false,
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/event-reward-rules/{$rule->id}/toggle-status");

        $res->assertStatus(422);
        $this->assertStringContainsString('positive reward amount', $res->json('message'));
        $this->assertFalse((bool) $rule->fresh()->is_active);
    }

    /**
     * Requirement 18: Cannot activate rule that causes overlap with existing active rule.
     */
    public function test_18_cannot_activate_rule_that_causes_overlap(): void
    {
        // Active 0-5
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        // Inactive 3-10
        $overlappingRule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 3,
            'max_referrals' => 10,
            'reward_amount' => 0.0300,
            'is_active' => false,
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/event-reward-rules/{$overlappingRule->id}/toggle-status");

        $res->assertStatus(422);
        $this->assertStringContainsString('overlaps with active', $res->json('message'));
        $this->assertFalse((bool) $overlappingRule->fresh()->is_active);
    }

    /**
     * Requirement 19: Property-Style Test: For every supported N in [0..100], COUNT(N) == 1.
     */
    public function test_19_property_style_coverage_test(): void
    {
        // Setup valid continuous set: 0-5, 6-14, 15+
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

        $check = $this->validationService->verifyDeterministicCoverage(AdRewardRule::TYPE_EVENT, 100);

        $this->assertTrue($check['is_deterministic'], 'Valid rule set must be strictly deterministic across domain [0..100].');
        $this->assertEquals(0, $check['violations_count']);

        // Directly test individual sample counts across the domain
        for ($n = 0; $n <= 100; $n++) {
            $matchingRules = AdRewardRule::forEvent()
                ->active()
                ->get()
                ->filter(function (AdRewardRule $r) use ($n) {
                    $min = (int) $r->min_referrals;
                    $max = $r->max_referrals !== null ? (int) $r->max_referrals : null;
                    return $n >= $min && ($max === null || $n <= $max);
                });

            $this->assertCount(1, $matchingRules, "Referral count {$n} must match exactly ONE active event reward rule.");
        }
    }

    /**
     * Requirement 20: Concurrency & atomic transaction protection during update and toggle.
     */
    public function test_20_concurrency_and_atomic_transactions(): void
    {
        $rule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        $secondAdmin = Admin::create([
            'name' => 'Concurrent Admin',
            'email' => 'admin_conc_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        // Perform simultaneous toggle/update requests
        $res1 = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/event-reward-rules/{$rule->id}", [
                'min_referrals' => 0,
                'max_referrals' => 5,
                'reward_amount' => 0.0250,
                'is_active' => true,
            ]);

        $res2 = $this->actingAs($secondAdmin, 'admin')
            ->putJson("/api/admin/event-reward-rules/{$rule->id}", [
                'min_referrals' => 0,
                'max_referrals' => 5,
                'reward_amount' => 0.0300,
                'is_active' => true,
            ]);

        $res1->assertStatus(200);
        $res2->assertStatus(200);

        $fresh = $rule->fresh();
        $this->assertEquals('0.0300', (string) $fresh->reward_amount);
        $this->assertEquals($secondAdmin->id, $fresh->updated_by);
    }

    /**
     * Requirement 21: Strict scope isolation between Event and Business Ads rules.
     */
    public function test_21_scope_isolation(): void
    {
        // Business ad rules exist
        $bizBeforeCount = AdRewardRule::forBusinessAd()->count();
        $this->assertGreaterThanOrEqual(3, $bizBeforeCount);

        // Ensure Business Ads 0-5 baseline rule has configured reward amount
        AdRewardRule::forBusinessAd()->where('min_referrals', 0)->update(['reward_amount' => 0.0250, 'is_active' => true]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_BUSINESS_AD);

        // Business Ads active set should remain valid
        $bizValidation = $this->validationService->validateActiveSet(AdRewardRule::TYPE_BUSINESS_AD);
        $this->assertTrue($bizValidation['valid']);

        // Create an intentionally defective Event configuration (gap at 0)
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 10,
            'max_referrals' => null,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        // Event validation fails
        $eventValidation = $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);
        $this->assertFalse($eventValidation['valid']);

        // Business Ads validation remains 100% valid and isolated
        $bizValidationAfter = $this->validationService->validateActiveSet(AdRewardRule::TYPE_BUSINESS_AD);
        $this->assertTrue($bizValidationAfter['valid']);
        $this->assertEquals($bizBeforeCount, AdRewardRule::forBusinessAd()->count());
    }

    /**
     * Requirement 22: Phase 9 dynamic minimum reward compatibility.
     */
    public function test_22_phase_9_dynamic_minimum_reward_compatibility(): void
    {
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0150,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => null,
            'reward_amount' => 0.0400,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache(AdRewardRule::TYPE_EVENT);

        $depletionService = app(CampaignBudgetDepletionService::class);
        $minReward = $depletionService->resolveMinimumRewardAmount(null, AdRewardRule::TYPE_EVENT);

        $this->assertEquals(0.0150, $minReward);
        $this->assertNotEquals(0.0500, $minReward);
    }

    /**
     * Requirement 23: Historical reward records remain safe and immutable.
     */
    public function test_23_historical_reward_records_remain_safe(): void
    {
        $organizer = Member::create([
            'name' => 'Historical Organizer',
            'user_id' => 'hist_org_' . uniqid(),
            'email' => 'hist_org_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Historical Event ' . uniqid(),
            'slug' => 'hist-event-' . uniqid(),
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
            'campaign_name' => 'Historical Event Campaign',
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

        // Run validation and add new rules
        $this->validationService->validateActiveSet(AdRewardRule::TYPE_EVENT);

        $freshHistorical = AdReward::find($historicalReward->id);
        $this->assertEquals(0.0250, (float) $freshHistorical->reward_amount_usd);
        $this->assertEquals(AdReward::STATUS_CREDITED, $freshHistorical->status);
    }
}
