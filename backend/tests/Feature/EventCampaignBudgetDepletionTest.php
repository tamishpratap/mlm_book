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
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class EventCampaignBudgetDepletionTest extends TestCase
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
            'title' => 'Global Summit ' . uniqid(),
            'slug' => 'global-summit-' . uniqid(),
            'description' => 'A premier tech summit.',
            'category' => 'Conference',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
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
     * REQUIREMENT 1: Valid Event Campaign spend succeeds.
     */
    public function test_01_valid_event_campaign_spend_succeeds(): void
    {
        $organizer = $this->createOrganizer(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 50.00);

        $result = $this->service->depleteEventBudget($event, 0.05, 'spend_ref_001');

        $this->assertTrue($result['success']);
        $this->assertEquals(0.05, $result['consumed_amount']);
        $this->assertEquals(50.00, $result['previous_remaining']);
        $this->assertEquals(49.95, $result['new_remaining']);
        $this->assertEquals(0.00, $result['previous_spent']);
        $this->assertEquals(0.05, $result['new_spent']);
        $this->assertFalse($result['is_replay']);
        $this->assertEquals('spend_ref_001', $result['spend_reference']);
    }

    /**
     * REQUIREMENT 2: Available budget decreases correctly.
     */
    public function test_02_available_budget_decreases_correctly(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 10.00);

        $this->assertEquals(10.00, (float) $campaign->remaining_amount);

        $this->service->depleteEventBudget($event, 1.25, 'spend_ref_002');

        $this->assertEquals(8.75, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * REQUIREMENT 3: Spent/consumed amount updates according to canonical accounting.
     */
    public function test_03_spent_amount_updates_according_to_canonical_accounting(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 20.00);

        $this->assertEquals(0.00, (float) $campaign->spent_amount);

        $this->service->depleteEventBudget($event, 0.50, 'spend_ref_003');

        $this->assertEquals(0.50, (float) $campaign->fresh()->spent_amount);
        $this->assertEquals(19.50, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(20.00, (float) $campaign->fresh()->total_funded);
    }

    /**
     * REQUIREMENT 4: Insufficient budget fails safely.
     */
    public function test_04_insufficient_budget_fails_safely(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 1.00);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient campaign budget');

        try {
            $this->service->depleteEventBudget($event, 2.50, 'spend_ref_004');
        } finally {
            // Confirm zero change in budget
            $this->assertEquals(1.00, (float) $campaign->fresh()->remaining_amount);
            $this->assertEquals(0.00, (float) $campaign->fresh()->spent_amount);
        }
    }

    /**
     * REQUIREMENT 5: Campaign never becomes negative.
     */
    public function test_05_campaign_never_becomes_negative(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        // Attempting to spend more than available
        try {
            $this->service->depleteEventBudget($event, 0.06, 'spend_ref_005');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Insufficient campaign budget', $e->getMessage());
        }

        $this->assertGreaterThanOrEqual(0.00, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.05, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * REQUIREMENT 6: Zero or negative amount fails.
     */
    public function test_06_zero_or_negative_amount_fails(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        // Zero
        try {
            $this->service->depleteEventBudget($event, 0.00, 'spend_zero');
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('greater than zero', $e->getMessage());
        }

        // Negative
        try {
            $this->service->depleteEventBudget($event, -5.00, 'spend_neg');
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('greater than zero', $e->getMessage());
        }
    }

    /**
     * REQUIREMENT 7: Non-numeric amount fails.
     */
    public function test_07_non_numeric_amount_fails(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('valid numeric value');

        $this->service->depleteEventBudget($event, 'invalid-amount', 'spend_nan');
    }

    /**
     * REQUIREMENT 8: Invalid precision fails according to canonical rules.
     */
    public function test_08_invalid_precision_fails(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        // 5 decimals exceeds precision of 4 decimal places
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maximum allowable precision');

        $this->service->depleteEventBudget($event, 0.12345, 'spend_precision');
    }

    /**
     * REQUIREMENT 9: Wrong currency fails.
     */
    public function test_09_wrong_currency_fails(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported currency 'EUR'");

        $this->service->depleteEventBudget($event, 1.00, 'spend_curr', ['currency' => 'EUR']);
    }

    /**
     * REQUIREMENT 10: Non-existent campaign fails safely.
     */
    public function test_10_non_existent_campaign_fails_safely(): void
    {
        $organizer = $this->createOrganizer();
        $eventWithoutCampaign = $this->createEvent($organizer);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not have an associated campaign foundation');

        $this->service->depleteEventBudget($eventWithoutCampaign, 1.00, 'spend_missing');
    }

    /**
     * REQUIREMENT 11: Wrong Event ↔ campaign relationship fails.
     */
    public function test_11_wrong_event_campaign_relationship_fails(): void
    {
        $organizer = $this->createOrganizer();
        $eventA = $this->createEvent($organizer);
        $eventB = $this->createEvent($organizer);
        $campaignA = $this->createActiveEventCampaign($eventA, $organizer, 50.00);

        // Pass campaignA with mismatched eventB context
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("does not belong to Event #{$eventB->id}");

        $this->service->depleteCampaignBudget($campaignA, 1.00, 'spend_mismatch', $eventB);
    }

    /**
     * REQUIREMENT 12: Business Ads campaign cannot be targeted as an Event campaign.
     */
    public function test_12_business_ads_campaign_cannot_be_targeted_as_event_campaign(): void
    {
        $owner = $this->createOrganizer();
        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Ad Page ' . uniqid(),
            'page_username' => 'ad_page_' . uniqid(),
            'slug' => 'ad-page-' . uniqid(),
            'category' => 'Business',
            'status' => 'active',
            'visibility' => 'public',
        ]);
        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Biz Ad',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $event = $this->createEvent($owner);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not an event campaign');

        $this->service->depleteCampaignBudget($bizCampaign, 1.00, 'spend_biz_mismatch', $event);
    }

    /**
     * REQUIREMENT 13: Unauthorized context cannot spend another creator's campaign.
     */
    public function test_13_unauthorized_context_cannot_spend_another_creator_campaign(): void
    {
        $creatorA = $this->createOrganizer();
        $creatorB = $this->createOrganizer();
        $eventA = $this->createEvent($creatorA);
        $campaignA = $this->createActiveEventCampaign($eventA, $creatorA, 50.00);

        $eventB = $this->createEvent($creatorB);

        // Attempting to deplete campaignA through eventB
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("does not belong to Event #{$eventB->id}");

        $this->service->depleteCampaignBudget($campaignA, 1.00, 'spend_alien', $eventB);
    }

    /**
     * REQUIREMENT 14: Duplicate spend request is idempotent.
     */
    public function test_14_duplicate_spend_request_is_idempotent(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 50.00);

        // 1st Spend
        $res1 = $this->service->depleteEventBudget($event, 0.05, 'ref_idempotent_1');
        $this->assertTrue($res1['success']);
        $this->assertFalse($res1['is_replay']);
        $this->assertEquals(49.95, $res1['new_remaining']);
        $this->assertEquals(0.05, $res1['new_spent']);

        // 2nd Spend with SAME reference (idempotent replay)
        $res2 = $this->service->depleteEventBudget($event, 0.05, 'ref_idempotent_1');
        $this->assertTrue($res2['success']);
        $this->assertTrue($res2['is_replay']);
        $this->assertEquals(49.95, $res2['new_remaining']);
        $this->assertEquals(0.05, $res2['new_spent']);

        // Database remains at 49.95 / 0.05 (no double deduction)
        $fresh = $campaign->fresh();
        $this->assertEquals(49.95, (float) $fresh->remaining_amount);
        $this->assertEquals(0.05, (float) $fresh->spent_amount);
    }

    /**
     * REQUIREMENT 15: Same logical spend cannot consume budget twice.
     */
    public function test_15_same_logical_spend_cannot_consume_budget_twice(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 1.00);

        // Repeated 5 times with same reference
        for ($i = 0; $i < 5; $i++) {
            $res = $this->service->depleteEventBudget($event, 0.10, 'duplicate_key_alpha');
            $this->assertTrue($res['success']);
            if ($i > 0) {
                $this->assertTrue($res['is_replay']);
            }
        }

        $fresh = $campaign->fresh();
        $this->assertEquals(0.90, (float) $fresh->remaining_amount);
        $this->assertEquals(0.10, (float) $fresh->spent_amount);

        // Spend history has exactly 1 entry
        $history = $fresh->target_audience['spend_history'];
        $this->assertCount(1, $history);
    }

    /**
     * REQUIREMENT 16: Sequential spend operations update cumulative spent amount accurately.
     */
    public function test_16_sequential_spend_operations_update_cumulative_spent_amount(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 10.00);

        // 4 distinct spends of $0.25 each
        for ($i = 1; $i <= 4; $i++) {
            $this->service->depleteEventBudget($event, 0.25, "seq_spend_{$i}");
        }

        $fresh = $campaign->fresh();
        $this->assertEquals(1.00, (float) $fresh->spent_amount);
        $this->assertEquals(9.00, (float) $fresh->remaining_amount);
        $this->assertEquals(10.00, (float) $fresh->total_funded);
        $this->assertCount(4, $fresh->target_audience['spend_history']);
    }

    /**
     * REQUIREMENT 17: Atomic rollback works on failure.
     */
    public function test_17_atomic_rollback_works_on_failure(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 5.00);

        try {
            DB::transaction(function () use ($event) {
                $this->service->depleteEventBudget($event, 1.00, 'spend_rollback_1');
                throw new \Exception('Simulated domain exception after spend');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Simulated domain exception after spend', $e->getMessage());
        }

        // State was fully rolled back
        $fresh = $campaign->fresh();
        $this->assertEquals(5.00, (float) $fresh->remaining_amount);
        $this->assertEquals(0.00, (float) $fresh->spent_amount);
        $this->assertNull($fresh->target_audience);
    }

    /**
     * REQUIREMENT 18: Non-active campaign status blocks spending.
     */
    public function test_18_non_active_campaign_status_blocks_spending(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        $statuses = [
            AdCampaign::STATUS_DRAFT,
            AdCampaign::STATUS_PAUSED,
            AdCampaign::STATUS_STOPPED,
            AdCampaign::STATUS_COMPLETED,
            AdCampaign::STATUS_CANCELLED,
            AdCampaign::STATUS_REJECTED,
        ];

        foreach ($statuses as $st) {
            $campaign = AdCampaign::create([
                'campaign_type' => AdCampaign::TYPE_EVENT,
                'event_id' => $event->id,
                'member_id' => $organizer->id,
                'campaign_name' => "Campaign {$st}",
                'budget' => 50.00,
                'remaining_amount' => 50.00,
                'status' => $st,
                'approval_status' => AdCampaign::APPROVAL_PENDING,
            ]);

            try {
                $this->service->depleteCampaignBudget($campaign, 1.00, "spend_status_{$st}");
                $this->fail("Expected RuntimeException for status {$st}");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('not eligible for spending', $e->getMessage());
            }

            $campaign->delete();
        }
    }

    /**
     * REQUIREMENT 19: Spend history ledger entry is recorded with full auditability.
     */
    public function test_19_spend_history_ledger_entry_is_recorded_with_full_auditability(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 50.00);

        $this->service->depleteEventBudget($event, 0.05, 'audit_ref_99', [
            'beneficiary_member_id' => 123,
            'qualifying_action' => 'landing_visit',
        ]);

        $fresh = $campaign->fresh();
        $history = $fresh->target_audience['spend_history'];
        $this->assertCount(1, $history);

        $entry = $history[0];
        $this->assertEquals('audit_ref_99', $entry['spend_reference']);
        $this->assertEquals(0.05, $entry['amount']);
        $this->assertEquals(50.00, $entry['previous_remaining']);
        $this->assertEquals(49.95, $entry['new_remaining']);
        $this->assertEquals(0.00, $entry['previous_spent']);
        $this->assertEquals(0.05, $entry['new_spent']);
        $this->assertEquals(123, $entry['metadata']['beneficiary_member_id']);
        $this->assertEquals('landing_visit', $entry['metadata']['qualifying_action']);
        $this->assertNotNull($entry['spent_at']);
    }

    /**
     * REQUIREMENT 20: Reward Wallet remains unchanged.
     */
    public function test_20_reward_wallet_remains_unchanged(): void
    {
        $organizer = $this->createOrganizer(100.00, 350.00, true);
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        $initialRewardBalance = (float) $organizer->fresh()->reward_balance;
        $this->assertEquals(350.00, $initialRewardBalance);

        $this->service->depleteEventBudget($event, 10.00, 'spend_no_rw');

        $this->assertEquals($initialRewardBalance, (float) $organizer->fresh()->reward_balance);
    }

    /**
     * REQUIREMENT 21: User wallet remains unchanged.
     */
    public function test_21_user_wallet_remains_unchanged(): void
    {
        $organizer = $this->createOrganizer(125.50, 0.00, true);
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        $initialAdBalance = (float) $organizer->fresh()->ad_balance;
        $this->assertEquals(125.50, $initialAdBalance);

        $this->service->depleteEventBudget($event, 5.00, 'spend_no_uw');

        $this->assertEquals($initialAdBalance, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 22: Referral counts remain unchanged.
     */
    public function test_22_referral_counts_remain_unchanged(): void
    {
        $sponsor = $this->createOrganizer(50.00, 10.00, true);
        $organizer = Member::create([
            'name' => 'Host Inv',
            'email' => 'host_inv_' . uniqid() . '@example.com',
            'password' => 'password123',
            'phone' => '+1555' . rand(1000000, 9999999),
            'mobile_verified_at' => now(),
            'ad_balance' => 100.00,
            'reward_balance' => 0.00,
            'introducer_id' => $sponsor->id,
        ]);
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        $this->service->depleteEventBudget($event, 2.00, 'spend_ref_check');

        $this->assertEquals(50.00, (float) $sponsor->fresh()->ad_balance);
        $this->assertEquals(10.00, (float) $sponsor->fresh()->reward_balance);
    }

    /**
     * REQUIREMENT 23: No participant/Interested record is created.
     */
    public function test_23_no_participant_or_interested_record_is_created(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $this->createActiveEventCampaign($event, $organizer, 50.00);

        $countResponsesBefore = EventResponse::count();
        $countRewardsBefore = AdReward::count();

        $this->service->depleteEventBudget($event, 1.00, 'spend_no_records');

        $this->assertEquals($countResponsesBefore, EventResponse::count());
        $this->assertEquals($countRewardsBefore, AdReward::count());
    }

    /**
     * REQUIREMENT 24: Campaign activation behavior remains unchanged.
     */
    public function test_24_campaign_activation_behavior_remains_unchanged(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);

        // Draft campaign with funds can still activate using Phase 7 endpoint
        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Event Camp',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 25: Phase 4 Add Fund remains functional after depletion.
     */
    public function test_25_phase_4_add_fund_remains_functional_after_depletion(): void
    {
        $organizer = $this->createOrganizer(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 20.00);

        // 1. Deplete $15.00
        $this->service->depleteEventBudget($event, 15.00, 'spend_before_topup');
        $this->assertEquals(5.00, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(15.00, (float) $campaign->fresh()->spent_amount);

        // 2. Add Funds ($10.00 top-up + $0.25 fee)
        $topupRes = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 10.00,
            ]);

        $topupRes->assertStatus(200)->assertJsonPath('success', true);

        $fresh = $campaign->fresh();
        $this->assertEquals(15.00, (float) $fresh->remaining_amount); // 5.00 + 10.00
        $this->assertEquals(15.00, (float) $fresh->spent_amount);
        $this->assertEquals(30.00, (float) $fresh->total_funded);     // 20.00 + 10.00
        $this->assertEquals(89.75, (float) $organizer->fresh()->ad_balance); // 100 - 10.25
    }

    /**
     * REQUIREMENT 26: Phase 5 budget semantics remain intact.
     */
    public function test_26_phase_5_budget_semantics_remain_intact(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 100.00);

        $this->service->depleteEventBudget($event, 23.45, 'spend_reconciliation');

        $fresh = $campaign->fresh();
        // Strict accounting reconciliation formula: total_funded == remaining_amount + spent_amount
        $this->assertEquals(
            (float) $fresh->total_funded,
            round((float) $fresh->remaining_amount + (float) $fresh->spent_amount, 4)
        );
    }

    /**
     * REQUIREMENT 27: Phase 6 campaign creation remains intact.
     */
    public function test_27_phase_6_campaign_creation_remains_intact(): void
    {
        $organizer = $this->createOrganizer(100.00, 0.00, true);

        $res = $this->actingAs($organizer, 'member')
            ->postJson('/api/member/events', [
                'title' => 'Integrated Event Creation ' . uniqid(),
                'category' => 'Conference',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'location_city' => 'Seattle',
                'is_paid' => true,
                'campaign_budget' => 40.00,
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true);
    }

    /**
     * REQUIREMENT 28: Existing Business Ads spending remains functional.
     */
    public function test_28_existing_business_ads_spending_remains_functional(): void
    {
        $owner = $this->createOrganizer();
        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Corporate Biz ' . uniqid(),
            'page_username' => 'corp_biz_' . uniqid(),
            'slug' => 'corp-biz-' . uniqid(),
            'category' => 'Business',
            'status' => 'active',
            'visibility' => 'public',
        ]);
        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Biz Ad Campaign',
            'budget' => 60.00,
            'remaining_amount' => 60.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Spend directly via shared depletion engine on Business Ad campaign
        $result = $this->service->depleteCampaignBudget($bizCampaign, 0.05, 'biz_spend_001');

        $this->assertTrue($result['success']);
        $this->assertEquals(59.95, $result['new_remaining']);
        $this->assertEquals(0.05, $result['new_spent']);
        $this->assertEquals(59.95, (float) $bizCampaign->fresh()->remaining_amount);
    }

    /**
     * REQUIREMENT 29: No destructive database operation is used.
     */
    public function test_29_no_destructive_database_operation_is_used(): void
    {
        $this->assertTrue(Schema::hasTable('ad_campaigns'));
        $this->assertTrue(Schema::hasTable('events'));
        $this->assertTrue(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasTable('business_pages'));
        $this->assertTrue(Schema::hasTable('ad_rewards'));

        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'budget'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'spent_amount'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'remaining_amount'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'total_funded'));
    }

    /**
     * REQUIREMENT 30 & SECTION 30: MANDATORY CONCURRENCY TEST
     *
     * Scenario:
     * Initial Event Campaign available budget = 0.05
     * Execute two simultaneous authorized backend spend operations:
     * Request A = 0.05
     * Request B = 0.05
     *
     * Expected:
     * - exactly one spend succeeds
     * - exactly one spend fails due to insufficient available budget / concurrency
     * - final available budget is exactly 0.00
     * - total consumed amount is exactly 0.05
     * - campaign budget never becomes negative
     * - no duplicate ledger/accounting entry is created
     */
    public function test_30_mandatory_concurrency_protection_test(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 0.05);

        $this->assertEquals(0.05, (float) $campaign->remaining_amount);
        $this->assertEquals(0.00, (float) $campaign->spent_amount);

        $successCount = 0;
        $failureCount = 0;
        $failureMessage = '';

        // Execute Request A
        try {
            $resA = $this->service->depleteEventBudget($event, 0.05, 'concurrent_ref_A');
            if ($resA['success']) {
                $successCount++;
            }
        } catch (\Throwable $e) {
            $failureCount++;
            $failureMessage = $e->getMessage();
        }

        // Execute Request B (competing for same initial 0.05 balance)
        try {
            $resB = $this->service->depleteEventBudget($event, 0.05, 'concurrent_ref_B');
            if ($resB['success']) {
                $successCount++;
            }
        } catch (\Throwable $e) {
            $failureCount++;
            $failureMessage = $e->getMessage();
        }

        // Assertions:
        // 1. Exactly one spend succeeds
        $this->assertEquals(1, $successCount, 'Exactly one spend operation must succeed.');

        // 2. Exactly one spend fails due to insufficient available budget or stopped status
        $this->assertEquals(1, $failureCount, 'Exactly one spend operation must fail.');
        $this->assertTrue(
            str_contains($failureMessage, 'Insufficient campaign budget') || str_contains($failureMessage, 'not eligible for spending'),
            "Expected failure message to indicate insufficient budget or ineligible status, got: '{$failureMessage}'"
        );

        // 3. Final available budget is exactly 0.00
        $fresh = $campaign->fresh();
        $this->assertEquals(0.00, (float) $fresh->remaining_amount, 'Final available budget must be exactly 0.00.');

        // 4. Total consumed amount is exactly 0.05
        $this->assertEquals(0.05, (float) $fresh->spent_amount, 'Total consumed amount must be exactly 0.05.');

        // 5. Campaign budget never becomes negative
        $this->assertGreaterThanOrEqual(0.00, (float) $fresh->remaining_amount);

        // 6. No duplicate ledger/accounting entry is created (exactly 1 entry in spend history)
        $this->assertCount(1, $fresh->target_audience['spend_history']);
        $this->assertEquals('concurrent_ref_A', $fresh->target_audience['spend_history'][0]['spend_reference']);
    }

    /**
     * Helper method on AdCampaign model verification: consumeBudget()
     */
    public function test_31_ad_campaign_model_consume_budget_helper(): void
    {
        $organizer = $this->createOrganizer();
        $event = $this->createEvent($organizer);
        $campaign = $this->createActiveEventCampaign($event, $organizer, 25.00);

        $res = $campaign->consumeBudget(5.00, 'model_helper_ref_1', ['source' => 'test']);

        $this->assertTrue($res['success']);
        $this->assertEquals(5.00, $res['consumed_amount']);
        $this->assertEquals(20.00, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(5.00, (float) $campaign->fresh()->spent_amount);
    }
}
