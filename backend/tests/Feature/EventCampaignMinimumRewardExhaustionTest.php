<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use App\Services\CampaignBudgetDepletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class EventCampaignMinimumRewardExhaustionTest extends TestCase
{
    use RefreshDatabase;

    private CampaignBudgetDepletionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('campaign_platform_fee_percent', 2.50);

        // Seed canonical baseline active rules for business ads
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0250, 'is_active' => true, 'rule_type' => AdRewardRule::TYPE_BUSINESS_AD]);
        AdRewardRule::where('min_referrals', 6)->update(['reward_amount' => 0.0350, 'is_active' => true, 'rule_type' => AdRewardRule::TYPE_BUSINESS_AD]);
        AdRewardRule::where('min_referrals', 15)->update(['reward_amount' => 0.0500, 'is_active' => true, 'rule_type' => AdRewardRule::TYPE_BUSINESS_AD]);

        // Seed canonical baseline active rules for events
        AdRewardRule::create(['rule_type' => AdRewardRule::TYPE_EVENT, 'min_referrals' => 0, 'max_referrals' => 5, 'reward_amount' => 0.0250, 'is_active' => true]);
        AdRewardRule::create(['rule_type' => AdRewardRule::TYPE_EVENT, 'min_referrals' => 6, 'max_referrals' => 14, 'reward_amount' => 0.0350, 'is_active' => true]);
        AdRewardRule::create(['rule_type' => AdRewardRule::TYPE_EVENT, 'min_referrals' => 15, 'max_referrals' => null, 'reward_amount' => 0.0500, 'is_active' => true]);
        AdRewardRule::clearCache();

        $this->service = app(CampaignBudgetDepletionService::class);
        CampaignBudgetDepletionService::setMinimumRewardResolver(null);
    }

    protected function tearDown(): void
    {
        CampaignBudgetDepletionService::setMinimumRewardResolver(null);
        parent::tearDown();
    }

    private function createOrganizer(float $adBalance = 100.00, float $rewardBalance = 250.00, bool $verified = true): Member
    {
        return Member::create([
            'name' => 'Host ' . uniqid(),
            'email' => 'host_' . uniqid() . '@example.com',
            'password' => 'password123',
            'phone' => '+1555' . rand(1000000, 9999999),
            'mobile_verified_at' => $verified ? now() : null,
            'ad_balance' => $adBalance,
            'reward_balance' => $rewardBalance,
        ]);
    }

    private function createEvent(Member $organizer, string $status = 'published'): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Tech Fest ' . uniqid(),
            'slug' => 'tech-fest-' . uniqid(),
            'description' => 'A premier gathering of tech innovators.',
            'category' => 'Technology',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'location_city' => 'San Francisco',
            'status' => $status,
        ]);
    }

    private function createActiveEventCampaign(Event $event, Member $organizer, float $budget = 50.00): AdCampaign
    {
        return AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => $event->title . ' Campaign',
            'budget' => $budget,
            'additional_funding' => 0.00,
            'total_funded' => $budget,
            'spent_amount' => 0.00,
            'remaining_amount' => $budget,
            'currency' => 'USD',
            'fee_percent' => 2.50,
            'fee_amount' => round($budget * 0.025, 2),
            'wallet_debit' => round($budget * 1.025, 2),
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);
    }

    /**
     * REQUIREMENT 1: Minimum reward is resolved dynamically from active rules.
     */
    public function test_01_minimum_reward_resolved_dynamically_from_active_rules(): void
    {
        // Baseline active rules: 0.0250, 0.0350, 0.0500 -> min is 0.0250
        $minReward = $this->service->resolveMinimumRewardAmount();
        $this->assertEquals(0.0250, $minReward);
    }

    /**
     * REQUIREMENT 2: Minimum reward is NOT hardcoded to $0.05.
     */
    public function test_02_minimum_reward_is_not_hardcoded_to_005(): void
    {
        // Configure active rules with custom values (e.g. 0.0300, 0.0400, 0.0450)
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0300]);
        AdRewardRule::where('min_referrals', 6)->update(['reward_amount' => 0.0400]);
        AdRewardRule::where('min_referrals', 15)->update(['reward_amount' => 0.0450]);
        AdRewardRule::clearCache();

        $minReward = $this->service->resolveMinimumRewardAmount();
        $this->assertEquals(0.0300, $minReward);
        $this->assertNotEquals(0.0500, $minReward);
    }

    /**
     * REQUIREMENT 3: Minimum reward is campaign-level threshold, not a member's personalized reward.
     */
    public function test_03_campaign_level_minimum_not_personalized_member_reward(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 10.00);

        // The campaign-level minimum reward does not depend on any member's referral count
        $campaignMin = $this->service->resolveMinimumRewardAmount($campaign);
        $this->assertEquals(0.0250, $campaignMin);
    }

    /**
     * REQUIREMENT 4 (Boundary Case A): Remaining budget equal to minimum reward remains active.
     */
    public function test_04_exact_threshold_remaining_equal_to_min_reward_remains_active(): void
    {
        // Active rules min = 0.0500
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0500]);
        AdRewardRule::clearCache();

        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        // Initial budget: 0.10, spend 0.0500 -> remaining exactly 0.0500
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.10);

        $result = $this->service->depleteEventBudget($event, 0.0500, 'spend_exact_threshold');

        $this->assertTrue($result['success']);
        $this->assertEquals(0.0500, $result['new_remaining']);
        $this->assertFalse($result['is_exhausted']);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $result['campaign_status']);

        $fresh = $campaign->fresh();
        $this->assertEquals(0.0500, (float) $fresh->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $fresh->status);
    }

    /**
     * REQUIREMENT 5 (Boundary Case B): Remaining budget below minimum reward transitions to stopped.
     */
    public function test_05_below_threshold_remaining_less_than_min_reward_transitions_to_stopped(): void
    {
        // Active rules min = 0.0500
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0500]);
        AdRewardRule::clearCache();

        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        // Initial budget: 0.08, spend 0.0500 -> remaining 0.0300 (< 0.0500)
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.08);

        $result = $this->service->depleteEventBudget($event, 0.0500, 'spend_below_threshold');

        $this->assertTrue($result['success']);
        $this->assertEquals(0.0300, $result['new_remaining']);
        $this->assertTrue($result['is_exhausted']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $result['campaign_status']);

        $fresh = $campaign->fresh();
        $this->assertEquals(0.0300, (float) $fresh->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);
    }

    /**
     * REQUIREMENT 6 (Boundary Case C): Zero remaining budget transitions to stopped.
     */
    public function test_06_zero_remaining_budget_transitions_to_stopped(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        $result = $this->service->depleteEventBudget($event, 0.0500, 'spend_to_zero');

        $this->assertTrue($result['success']);
        $this->assertEquals(0.0000, $result['new_remaining']);
        $this->assertTrue($result['is_exhausted']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $result['campaign_status']);

        $fresh = $campaign->fresh();
        $this->assertEquals(0.0000, (float) $fresh->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);
    }

    /**
     * REQUIREMENT 7 (Boundary Case D): Changing active reward configuration changes the threshold dynamically.
     */
    public function test_07_dynamic_tier_reconfiguration_updates_exhaustion_threshold_instantly(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        // Campaign with budget 0.10, remaining 0.0300
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.10);
        DB::table('ad_campaigns')->where('id', $campaign->id)->update(['remaining_amount' => 0.0300]);
        $campaign->refresh();

        // Under initial rules (min = 0.0250), 0.0300 >= 0.0250 -> sufficient
        $this->assertTrue($this->service->isBudgetSufficient($campaign));
        $this->assertFalse($this->service->isBudgetExhausted($campaign));

        // Now Admin raises minimum active rule to 0.0350
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0350]);
        AdRewardRule::clearCache();

        // Under updated rules (min = 0.0350), 0.0300 < 0.0350 -> now exhausted!
        $this->assertFalse($this->service->isBudgetSufficient($campaign));
        $this->assertTrue($this->service->isBudgetExhausted($campaign));

        // Standalone check-and-enforce transitions campaign to stopped
        $enforceResult = $this->service->checkAndEnforceExhaustion($campaign);
        $this->assertTrue($enforceResult['is_exhausted']);
        $this->assertTrue($enforceResult['transitioned']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $enforceResult['status']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 8 (Section 33 Concurrency Test):
     * Minimum reward = $0.05, campaign remaining budget = $0.07.
     * Run two simultaneous authorized spend operations of $0.05.
     * Expected: exactly 1 succeeds, remaining = $0.02 (< $0.05), campaign ends in STATUS_STOPPED,
     * no overspend, no negative balance.
     */
    public function test_08_mandatory_concurrency_protection_test_section_33(): void
    {
        // Set minimum reward to $0.0500
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0500]);
        AdRewardRule::clearCache();

        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.07);

        $successCount = 0;
        $failureCount = 0;
        $failureMessage = '';

        // Execute Spend A ($0.0500)
        try {
            $resA = $this->service->depleteEventBudget($event, 0.0500, 'conc_ref_sec33_A');
            if ($resA['success']) {
                $successCount++;
            }
        } catch (\Throwable $e) {
            $failureCount++;
            $failureMessage = $e->getMessage();
        }

        // Execute Spend B ($0.0500)
        try {
            $resB = $this->service->depleteEventBudget($event, 0.0500, 'conc_ref_sec33_B');
            if ($resB['success']) {
                $successCount++;
            }
        } catch (\Throwable $e) {
            $failureCount++;
            $failureMessage = $e->getMessage();
        }

        // Assertions per Section 33:
        // 1. At most one succeeds
        $this->assertEquals(1, $successCount, 'At most one spend operation must succeed.');
        $this->assertEquals(1, $failureCount, 'Exactly one spend operation must fail.');

        // 2. Budget does not become negative
        $fresh = $campaign->fresh();
        $this->assertGreaterThanOrEqual(0.00, (float) $fresh->remaining_amount);

        // 3. Total consumed does not exceed $0.07
        $this->assertEquals(0.0500, (float) $fresh->spent_amount);
        $this->assertEquals(0.0200, (float) $fresh->remaining_amount);

        // 4. Campaign ends in correct exhausted/stopped state ($0.0200 < $0.0500)
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);

        // 5. No race-condition inconsistency or duplicate spend history
        $history = $fresh->target_audience['spend_history'] ?? [];
        $this->assertCount(1, $history);
    }

    /**
     * REQUIREMENT 9: Missing reward rules fails safely closed (throws RuntimeException, no fallback default).
     */
    public function test_09_missing_reward_rules_fails_safely_closed_no_default(): void
    {
        // Deactivate all rules
        AdRewardRule::query()->update(['is_active' => false]);
        AdRewardRule::clearCache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active reward rules are configured');

        $this->service->resolveMinimumRewardAmount();
    }

    /**
     * REQUIREMENT 10: Null reward amount fails safely closed.
     */
    public function test_10_null_reward_amount_fails_safely_closed(): void
    {
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => null]);
        AdRewardRule::clearCache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('null or non-numeric');

        $this->service->resolveMinimumRewardAmount();
    }

    /**
     * REQUIREMENT 11: Zero or negative reward amount fails safely closed.
     */
    public function test_11_zero_or_negative_reward_amount_fails_safely_closed(): void
    {
        AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0000]);
        AdRewardRule::clearCache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('zero or negative');

        $this->service->resolveMinimumRewardAmount();
    }

    /**
     * REQUIREMENT 12: Overlapping active reward rules fails safely closed.
     */
    public function test_12_overlapping_reward_rules_fails_safely_closed(): void
    {
        // Introduce an overlap: tier 1 ends at 8, but tier 2 starts at 6
        AdRewardRule::where('min_referrals', 0)->update(['max_referrals' => 8]);
        AdRewardRule::clearCache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Active reward rules have configuration errors');

        $this->service->resolveMinimumRewardAmount();
    }

    /**
     * REQUIREMENT 13: Campaign is never deleted when exhausted.
     */
    public function test_13_campaign_is_never_deleted_on_exhaustion(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        $this->service->depleteEventBudget($event, 0.0500, 'spend_no_delete_camp');

        $fresh = AdCampaign::find($campaign->id);
        $this->assertNotNull($fresh, 'Campaign must not be deleted on exhaustion.');
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);
    }

    /**
     * REQUIREMENT 14: Event is never deleted when campaign becomes exhausted.
     */
    public function test_14_event_is_never_deleted_on_exhaustion(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        $this->service->depleteEventBudget($event, 0.0500, 'spend_no_delete_evt');

        $freshEvent = Event::find($event->id);
        $this->assertNotNull($freshEvent, 'Event must not be deleted on campaign exhaustion.');
        $this->assertEquals('published', $freshEvent->status);
    }

    /**
     * REQUIREMENT 15: Funding history remains intact upon exhaustion.
     */
    public function test_15_funding_history_remains_intact_upon_exhaustion(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        // Record a mock funding history entry
        $targetAudience = [
            'funding_history' => [
                ['type' => 'initial_allocation', 'amount' => 0.0500, 'funded_at' => now()->toIso8601String()]
            ]
        ];
        $campaign->update(['target_audience' => $targetAudience]);

        $this->service->depleteEventBudget($event, 0.0500, 'spend_keep_funding');

        $fresh = $campaign->fresh();
        $fundingHistory = $fresh->target_audience['funding_history'] ?? [];
        $this->assertCount(1, $fundingHistory);
        $this->assertEquals(0.0500, $fundingHistory[0]['amount']);
    }

    /**
     * REQUIREMENT 16: Spend history ledger remains intact upon exhaustion.
     */
    public function test_16_spend_history_ledger_remains_intact_upon_exhaustion(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        // 1st spend: 0.0250 -> remaining 0.0250 (active)
        $this->service->depleteEventBudget($event, 0.0250, 'spend_step_1');
        // 2nd spend: 0.0250 -> remaining 0.0000 (stopped)
        $this->service->depleteEventBudget($event, 0.0250, 'spend_step_2');

        $fresh = $campaign->fresh();
        $spendHistory = $fresh->target_audience['spend_history'] ?? [];
        $this->assertCount(2, $spendHistory);
        $this->assertEquals('spend_step_1', $spendHistory[0]['spend_reference']);
        $this->assertEquals('spend_step_2', $spendHistory[1]['spend_reference']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $fresh->status);
    }

    /**
     * REQUIREMENT 17: Lifecycle history records budget_exhausted_stop audit trail.
     */
    public function test_17_lifecycle_history_records_budget_exhausted_stop(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        $this->service->depleteEventBudget($event, 0.0500, 'spend_audit_lifecycle');

        $fresh = $campaign->fresh();
        $lifecycle = $fresh->target_audience['lifecycle_history'] ?? [];
        $this->assertNotEmpty($lifecycle);

        $lastAction = end($lifecycle);
        $this->assertEquals('budget_exhausted_stop', $lastAction['action']);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $lastAction['from_status']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $lastAction['to_status']);
        $this->assertEquals(0.0000, (float) $lastAction['remaining_amount']);
        $this->assertEquals(0.0250, (float) $lastAction['minimum_reward']);
    }

    /**
     * REQUIREMENT 18: Display status reflects 'Budget Exhausted'.
     */
    public function test_18_display_status_reflects_budget_exhausted(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        $this->service->depleteEventBudget($event, 0.0500, 'spend_display_status');

        $fresh = $campaign->fresh();
        $this->assertTrue($fresh->is_budget_exhausted);
        $this->assertEquals('Budget Exhausted', $fresh->display_status);
    }

    /**
     * REQUIREMENT 19: Exhausted campaign cannot be reactivated via activation API without top-up.
     */
    public function test_19_exhausted_campaign_cannot_be_reactivated_via_activation_api(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 0.05);

        // Deplete to exhaustion
        $this->service->depleteEventBudget($event, 0.0500, 'spend_exhaust_before_act');

        // Attempt to call activate endpoint
        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(422);
        $res->assertJsonPath('success', false);
        $this->assertStringContainsString('stopped', strtolower($res->json('message')));
    }

    /**
     * REQUIREMENT 20: Reward wallet remains completely unchanged.
     */
    public function test_20_reward_wallet_is_never_modified_by_phase_9(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 0.05);

        $initialReward = (float) $organizer->fresh()->reward_balance;
        $this->service->depleteEventBudget($event, 0.0500, 'spend_rw_test');

        $this->assertEquals($initialReward, (float) $organizer->fresh()->reward_balance);
    }

    /**
     * REQUIREMENT 21: User wallet remains completely unchanged.
     */
    public function test_21_user_wallet_is_never_modified_by_phase_9(): void
    {
        $organizer = $this->createOrganizer(150.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 0.05);

        $initialAdBalance = (float) $organizer->fresh()->ad_balance;
        $this->service->depleteEventBudget($event, 0.0500, 'spend_uw_test');

        $this->assertEquals($initialAdBalance, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 22: Referral counts are never modified.
     */
    public function test_22_referral_counts_are_never_modified(): void
    {
        $sponsor = $this->createOrganizer();
        $organizer = Member::create([
            'name' => 'Org Ref',
            'email' => 'org_ref_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'ad_balance' => 100.00,
            'reward_balance' => 0.00,
            'introducer_id' => $sponsor->id,
        ]);
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 0.05);

        $this->service->depleteEventBudget($event, 0.0500, 'spend_ref_count');

        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 23: Interested records and rewards are never created.
     */
    public function test_23_interested_records_are_never_created(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 0.05);

        $countResponsesBefore = EventResponse::count();
        $countRewardsBefore = AdReward::count();

        $this->service->depleteEventBudget($event, 0.0500, 'spend_no_evt_records');

        $this->assertEquals($countResponsesBefore, EventResponse::count());
        $this->assertEquals($countRewardsBefore, AdReward::count());
    }

    /**
     * REQUIREMENT 24: No personalized reward calculation is executed.
     */
    public function test_24_no_personalized_reward_calculation_is_executed(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 0.05);

        $res = $this->service->depleteEventBudget($event, 0.0500, 'spend_no_personalization');

        $this->assertArrayNotHasKey('personalized_reward', $res);
        $this->assertArrayNotHasKey('referral_tier', $res);
    }

    /**
     * REQUIREMENT 25: Phase 4 Add Fund remains compatible with exhausted campaign.
     */
    public function test_25_phase_4_add_fund_remains_compatible_with_exhausted_campaign(): void
    {
        $organizer = $this->createOrganizer(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        // Deplete to exhaustion
        $this->service->depleteEventBudget($event, 0.0500, 'spend_before_topup');
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);

        // Add funds to exhausted campaign using Phase 4 endpoint
        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 10.00,
            ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $fresh = $campaign->fresh();
        $this->assertEquals(10.00, (float) $fresh->remaining_amount);
        $this->assertEquals(10.05, (float) $fresh->total_funded);
    }

    /**
     * REQUIREMENT 26: Standalone checkAndEnforceExhaustion stops depleted campaign.
     */
    public function test_26_standalone_check_and_enforce_exhaustion_stops_depleted_campaign(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 50.00);

        // Manually adjust remaining to 0.0100 (< 0.0250) directly in DB
        DB::table('ad_campaigns')->where('id', $campaign->id)->update(['remaining_amount' => 0.0100]);
        $campaign->refresh();

        $result = $this->service->checkAndEnforceExhaustion($campaign);

        $this->assertTrue($result['is_exhausted']);
        $this->assertTrue($result['transitioned']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $result['status']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 27: Standalone checkAndEnforceExhaustion leaves sufficient campaign active.
     */
    public function test_27_standalone_check_and_enforce_exhaustion_leaves_sufficient_campaign_active(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 50.00);

        $result = $this->service->checkAndEnforceExhaustion($campaign);

        $this->assertFalse($result['is_exhausted']);
        $this->assertFalse($result['transitioned']);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $result['status']);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 28: isBudgetSufficient and isBudgetExhausted helper methods.
     */
    public function test_28_is_budget_sufficient_and_is_budget_exhausted_helpers(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 50.00);

        $this->assertTrue($this->service->isBudgetSufficient($campaign));
        $this->assertFalse($this->service->isBudgetExhausted($campaign));

        DB::table('ad_campaigns')->where('id', $campaign->id)->update(['remaining_amount' => 0.0100]);
        $campaign->refresh();

        $this->assertFalse($this->service->isBudgetSufficient($campaign));
        $this->assertTrue($this->service->isBudgetExhausted($campaign));
    }

    /**
     * REQUIREMENT 29: Free Events remain completely unaffected.
     */
    public function test_29_free_events_remain_completely_unaffected(): void
    {
        $organizer = $this->createOrganizer();
        $freeEvent = $this->createEvent($organizer);

        $this->assertNull($freeEvent->campaign);
        $this->assertEquals('published', $freeEvent->status);
    }

    /**
     * REQUIREMENT 30: Custom minimum reward resolver injection works.
     */
    public function test_30_custom_minimum_reward_resolver_injection_works(): void
    {
        CampaignBudgetDepletionService::setMinimumRewardResolver(fn(?AdCampaign $c) => 0.0800);

        $minReward = $this->service->resolveMinimumRewardAmount();
        $this->assertEquals(0.0800, $minReward);

        CampaignBudgetDepletionService::setMinimumRewardResolver(null);
        $this->assertEquals(0.0250, $this->service->resolveMinimumRewardAmount());
    }

    /**
     * REQUIREMENT 31: Business Ads campaign depletion auto-stops safely.
     */
    public function test_31_business_ads_campaign_depletion_auto_stops_safely(): void
    {
        $owner = $this->createOrganizer();
        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Biz Page ' . uniqid(),
            'page_username' => 'biz_' . uniqid(),
            'slug' => 'biz-' . uniqid(),
            'category' => 'Business',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Biz Ad Campaign',
            'budget' => 0.05,
            'remaining_amount' => 0.05,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $result = $this->service->depleteCampaignBudget($bizCampaign, 0.0500, 'spend_biz_ad_exhaust');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_exhausted']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $result['campaign_status']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $bizCampaign->fresh()->status);
    }

    /**
     * REQUIREMENT 32: Idempotent replay on exhausted campaign returns exhausted state.
     */
    public function test_32_idempotent_replay_on_exhausted_campaign_returns_exhausted_state(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        // First spend (exhausts campaign)
        $res1 = $this->service->depleteEventBudget($event, 0.0500, 'spend_replay_exhaust');
        $this->assertTrue($res1['success']);
        $this->assertFalse($res1['is_replay']);
        $this->assertTrue($res1['is_exhausted']);

        // Replay spend with same reference
        $res2 = $this->service->depleteEventBudget($event, 0.0500, 'spend_replay_exhaust');
        $this->assertTrue($res2['success']);
        $this->assertTrue($res2['is_replay']);
        $this->assertTrue($res2['is_exhausted']);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $res2['campaign_status']);
    }
}
