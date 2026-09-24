<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCampaignFundingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('campaign_platform_fee_percent', 2.50);
    }

    private function createOrganizer(float $balance = 100.00, bool $verified = true): Member
    {
        return Member::create([
            'name' => 'Event Host',
            'email' => 'host_' . uniqid() . '@example.com',
            'password' => 'password123',
            'phone' => '+1555' . rand(1000000, 9999999),
            'mobile_verified_at' => $verified ? now() : null,
            'ad_balance' => $balance,
        ]);
    }

    private function createEvent(Member $organizer): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Web3 Blockchain Expo',
            'slug' => 'web3-blockchain-expo-' . uniqid(),
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(7)->toDateString(),
            'status' => 'published',
        ]);
    }

    /**
     * TEST 1: Unverified creator cannot add funds to event campaign.
     */
    public function test_unverified_creator_cannot_add_funds(): void
    {
        $unverified = $this->createOrganizer(100.00, false);
        $event = $this->createEvent($unverified);

        $response = $this->actingAs($unverified, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('needs_verification', true);

        // Member balance must be untouched
        $this->assertEquals(100.00, (float) $unverified->fresh()->ad_balance);
    }

    /**
     * TEST 2: Non-organizer cannot fund another user's event campaign.
     */
    public function test_non_organizer_cannot_add_funds(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $intruder = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        $response = $this->actingAs($intruder, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        // Neither user's balance should be modified
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
        $this->assertEquals(100.00, (float) $intruder->fresh()->ad_balance);
    }

    /**
     * TEST 3: Invalid amounts (zero, negative, malformed) are rejected with 422.
     */
    public function test_invalid_funding_amounts_are_rejected(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        // Zero
        $resZero = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 0]);
        $resZero->assertStatus(422);

        // Negative
        $resNeg = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => -10.00]);
        $resNeg->assertStatus(422);

        // Non-numeric
        $resStr = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 'invalid_amount']);
        $resStr->assertStatus(422);

        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * TEST 4: Insufficient ad balance returns 422 with shortfall details.
     */
    public function test_insufficient_funds_returns_validation_error(): void
    {
        $organizer = $this->createOrganizer(10.00, true); // only $10 balance
        $event = $this->createEvent($organizer);

        // Request $50 + 2.5% fee = $51.25 (shortfall $41.25)
        $response = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $this->assertStringContainsString('Insufficient advertising funds', $response->json('errors.amount.0'));
        $this->assertEquals(10.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * TEST 5: Successful initial campaign funding.
     */
    public function test_successful_initial_campaign_funding(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        // Amount: $50.00, Fee (2.5%): $1.25, Total Debit: $51.25
        $response = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
                'idempotency_key' => 'idem-init-1',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_replay', false);

        $this->assertEquals(50.00, (float) $response->json('campaign.budget'));
        $this->assertEquals(50.00, (float) $response->json('campaign.total_funded'));
        $this->assertEquals(50.00, (float) $response->json('campaign.remaining_amount'));
        $this->assertEquals(2.50, (float) $response->json('campaign.fee_percent'));
        $this->assertEquals(1.25, (float) $response->json('campaign.fee_amount'));
        $this->assertEquals(51.25, (float) $response->json('campaign.wallet_debit'));
        $this->assertEquals(48.75, (float) $response->json('available_ad_funds'));

        // Check advertiser ad_balance in DB: 100.00 - 51.25 = 48.75
        $this->assertEquals(48.75, (float) $organizer->fresh()->ad_balance);

        // Check ad_campaigns record
        $this->assertDatabaseHas('ad_campaigns', [
            'event_id' => $event->id,
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'budget' => 50.00,
            'additional_funding' => 0.00,
            'total_funded' => 50.00,
            'remaining_amount' => 50.00,
            'wallet_debit' => 51.25,
        ]);

        // Check funding history traceability
        $campaign = $event->campaign()->first();
        $history = (array) ($campaign->target_audience['funding_history'] ?? []);
        $this->assertCount(1, $history);
        $this->assertEquals(50.00, (float) $history[0]['top_up_amount']);
        $this->assertEquals('idem-init-1', $history[0]['idempotency_key']);
    }

    /**
     * TEST 6: Subsequent top-up to an already funded event campaign.
     */
    public function test_subsequent_campaign_topup(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        // Initial funding: $50.00 ($51.25 debit, balance becomes $48.75)
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
                'idempotency_key' => 'idem-sub-1',
            ])
            ->assertStatus(200);

        // Subsequent top-up: $20.00
        // Top-up: $20.00, Fee (2.5%): $0.50, Debit: $20.50.
        // Balance becomes: 48.75 - 20.50 = 28.25.
        // Campaign budget: 50.00, additional_funding: 20.00, total_funded: 70.00, remaining_amount: 70.00.
        $resTopup = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/top-up", [
                'amount' => 20.00,
                'idempotency_key' => 'idem-sub-2',
            ]);

        $resTopup->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(50.00, (float) $resTopup->json('campaign.budget'));
        $this->assertEquals(20.00, (float) $resTopup->json('campaign.additional_funding'));
        $this->assertEquals(70.00, (float) $resTopup->json('campaign.total_funded'));
        $this->assertEquals(70.00, (float) $resTopup->json('campaign.remaining_amount'));
        $this->assertEquals(1.75, (float) $resTopup->json('campaign.fee_amount'));
        $this->assertEquals(71.75, (float) $resTopup->json('campaign.wallet_debit'));
        $this->assertEquals(28.25, (float) $resTopup->json('available_ad_funds'));

        $this->assertEquals(28.25, (float) $organizer->fresh()->ad_balance);

        $history = (array) ($event->campaign()->first()->target_audience['funding_history'] ?? []);
        $this->assertCount(2, $history);
    }

    /**
     * TEST 7: Idempotency protection prevents double-debiting on retry or double-click.
     */
    public function test_idempotency_prevents_duplicate_wallet_debit(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        $idempotencyKey = 'unique-key-' . uniqid();

        // First attempt: succeeds
        $res1 = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 30.00,
                'idempotency_key' => $idempotencyKey,
            ]);

        $res1->assertStatus(200)
            ->assertJsonPath('is_replay', false);

        $balanceAfterFirst = (float) $organizer->fresh()->ad_balance;
        // 100 - (30 + 0.75) = 69.25
        $this->assertEquals(69.25, $balanceAfterFirst);

        // Immediate duplicate retry with identical idempotency_key
        $res2 = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 30.00,
                'idempotency_key' => $idempotencyKey,
            ]);

        $res2->assertStatus(200)
            ->assertJsonPath('is_replay', true);

        $this->assertEquals(30.00, (float) $res2->json('campaign.remaining_amount'));

        // Member balance MUST NOT be debited a second time
        $this->assertEquals(69.25, (float) $organizer->fresh()->ad_balance);

        // Only ONE history entry should exist for this idempotency key
        $history = (array) ($event->campaign()->first()->target_audience['funding_history'] ?? []);
        $matching = array_filter($history, fn($item) => ($item['idempotency_key'] ?? null) === $idempotencyKey);
        $this->assertCount(1, $matching);
    }

    /**
     * TEST 8: Zero financial side-effects (no rewards, no referral payout, no participant credits).
     */
    public function test_zero_financial_side_effects_on_funding(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
            ])
            ->assertStatus(200);

        // ZERO ad rewards created
        $this->assertEquals(0, AdReward::count());

        // Campaign spent amount remains strictly 0.00
        $campaign = $event->campaign()->first();
        $this->assertEquals(0.00, (float) $campaign->spent_amount);
        $this->assertEquals(50.00, (float) $campaign->remaining_amount);
    }

    /**
     * TEST 9: Event campaign funding and Business Ad campaign funding remain segregated.
     */
    public function test_event_and_business_ad_campaign_balance_isolation(): void
    {
        $organizer = $this->createOrganizer(200.00, true);
        $event = $this->createEvent($organizer);

        $businessPage = BusinessPage::create([
            'member_id' => $organizer->id,
            'page_name' => 'Acme Tech',
            'page_username' => 'acmetech_' . uniqid(),
            'slug' => 'acme-tech-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $businessCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Acme Promo',
            'budget' => 40.00,
            'additional_funding' => 0.00,
            'total_funded' => 40.00,
            'spent_amount' => 10.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Fund event campaign with $50
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 50.00,
            ])
            ->assertStatus(200);

        // Business Ad campaign remaining amount must remain strictly unchanged at $30.00
        $this->assertEquals(30.00, (float) $businessCampaign->fresh()->remaining_amount);

        // Event campaign has its own remaining amount of $50.00
        $eventCampaign = $event->campaign()->first();
        $this->assertEquals(50.00, (float) $eventCampaign->remaining_amount);
    }

    /**
     * TEST 10: Show campaign endpoint returns financial metrics and available advertiser funds.
     */
    public function test_show_endpoint_returns_financial_and_balance_metrics(): void
    {
        $organizer = $this->createOrganizer(88.50, true);
        $event = $this->createEvent($organizer);

        // Create campaign foundation with initial funding
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 20.00,
            ])
            ->assertStatus(200);

        // Show endpoint
        $response = $this->actingAs($organizer, 'member')
            ->getJson("/api/member/events/{$event->id}/campaign");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(20.00, (float) $response->json('campaign.budget'));
        $this->assertEquals(20.00, (float) $response->json('campaign.remaining_amount'));
        $this->assertEquals(68.00, (float) $response->json('available_ad_funds'));
        $this->assertEquals(2.50, (float) $response->json('platform_fee_percent'));
    }

    /**
     * TEST 11: Non-existent Event returns 404.
     */
    public function test_non_existent_event_returns_404(): void
    {
        $organizer = $this->createOrganizer(100.00, true);

        $response = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/999999/campaign/add-funds", [
                'amount' => 50.00,
            ]);

        $response->assertStatus(404);
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * TEST 12: Campaign mismatch or non-existent campaign ID is rejected with 422.
     */
    public function test_campaign_mismatch_is_rejected(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $eventA = $this->createEvent($organizer);
        $eventB = $this->createEvent($organizer);

        // Fund event A
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$eventA->id}/campaign/add-funds", ['amount' => 20.00])
            ->assertStatus(200);

        $campaignA = $eventA->campaign()->first();

        // Attempt to fund event B while specifying campaign A's ID
        $response = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$eventB->id}/campaign/add-funds", [
                'amount' => 20.00,
                'campaign_id' => $campaignA->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertStringContainsString('does not exist or does not belong to this event', $response->json('message'));
    }

    /**
     * TEST 13: Member Reward Wallet remains strictly untouched.
     */
    public function test_reward_wallet_remains_strictly_unchanged(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        // Explicitly set reward balance
        $organizer->reward_balance = 15.75;
        $organizer->save();

        $event = $this->createEvent($organizer);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 40.00])
            ->assertStatus(200);

        // Member's reward balance must remain strictly identical
        $this->assertEquals(15.75, (float) $organizer->fresh()->reward_balance);
    }

    /**
     * TEST 14: No participant, attendee, or interested records created during funding.
     */
    public function test_no_participant_or_interested_records_created(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 30.00])
            ->assertStatus(200);

        $this->assertDatabaseMissing('event_responses', [
            'event_id' => $event->id,
        ]);

        $this->assertDatabaseMissing('ad_campaign_activities', [
            'member_id' => $organizer->id,
        ]);
    }

    /**
     * TEST 15: Refresh after successful funding retrieves authoritative persisted values.
     */
    public function test_refresh_after_success_shows_authoritative_values(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 35.00])
            ->assertStatus(200);

        // Emulate fresh page refresh
        $refreshRes = $this->actingAs($organizer, 'member')
            ->getJson("/api/member/events/{$event->id}/campaign");

        $refreshRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(35.00, (float) $refreshRes->json('campaign.budget'));
        $this->assertEquals(35.00, (float) $refreshRes->json('campaign.remaining_amount'));
        $this->assertEquals(64.12, (float) $refreshRes->json('available_ad_funds')); // 100 - (35 + 0.88) = 64.12
    }

    /**
     * TEST 16: Transaction failure rolls back atomically leaving no partial debit or credit.
     */
    public function test_transaction_rollback_leaves_no_partial_state(): void
    {
        $organizer = $this->createOrganizer(100.00, true);
        $event = $this->createEvent($organizer);

        // Pre-fund with $10
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 10.00])
            ->assertStatus(200);

        $initialBalance = (float) $organizer->fresh()->ad_balance;
        $initialBudget = (float) $event->campaign()->first()->budget;

        // Force a runtime exception during campaign updating
        \Illuminate\Support\Facades\Event::listen('eloquent.updating: App\Models\AdCampaign', function ($model) {
            throw new \RuntimeException('Simulated database deadlock during campaign update');
        });

        $resFail = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 25.00]);

        $resFail->assertStatus(500);

        // Balance and campaign budget MUST be untouched by the aborted transaction
        $this->assertEquals($initialBalance, (float) $organizer->fresh()->ad_balance);
        $this->assertEquals($initialBudget, (float) $event->campaign()->first()->budget);
    }
}
