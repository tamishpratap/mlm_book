<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignActivity;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCampaignBudgetAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('campaign_platform_fee_percent', 2.50);
    }

    private function createOrganizer(float $adBalance = 100.00, float $rewardBalance = 250.00, bool $verified = true): Member
    {
        return Member::create([
            'name' => 'Event Host ' . uniqid(),
            'email' => 'host_' . uniqid() . '@example.com',
            'password' => 'password123',
            'phone' => '+1555' . rand(1000000, 9999999),
            'mobile_verified_at' => $verified ? now() : null,
            'ad_balance' => $adBalance,
            'reward_balance' => $rewardBalance,
        ]);
    }

    private function createEvent(Member $organizer): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Web3 Leaders Summit',
            'slug' => 'web3-leaders-summit-' . uniqid(),
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(7)->toDateString(),
            'status' => 'published',
        ]);
    }

    /**
     * TEST 1: Paid Event creation with initial budget allocates budget, computes fee, and debits wallet atomically.
     */
    public function test_paid_event_creation_with_initial_budget_allocates_budget_and_debits_wallet(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);

        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Crypto Expo 2026',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(14)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 40.00,
                'currency' => 'USD',
                'idempotency_key' => 'idemp-init-101',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true);

        // Platform fee: 40 * 0.025 = 1.00 USD, Total Debit: 41.00 USD
        // Wallet: 100.00 - 41.00 = 59.00 USD
        $this->assertEquals(59.00, (float) $organizer->fresh()->ad_balance);

        $event = Event::where('title', 'Crypto Expo 2026')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->isPaidCampaign());

        $campaign = $event->campaign;
        $this->assertNotNull($campaign);
        $this->assertEquals(AdCampaign::TYPE_EVENT, $campaign->campaign_type);
        $this->assertEquals($event->id, $campaign->event_id);
        $this->assertEquals($organizer->id, $campaign->member_id);
        $this->assertNull($campaign->business_page_id);
        $this->assertEquals(40.00, (float) $campaign->budget);
        $this->assertEquals(0.00, (float) $campaign->additional_funding);
        $this->assertEquals(40.00, (float) $campaign->total_funded);
        $this->assertEquals(0.00, (float) $campaign->spent_amount);
        $this->assertEquals(40.00, (float) $campaign->remaining_amount);
        $this->assertEquals(2.50, (float) $campaign->fee_percent);
        $this->assertEquals(1.00, (float) $campaign->fee_amount);
        $this->assertEquals(41.00, (float) $campaign->wallet_debit);
        $this->assertEquals('USD', $campaign->currency);
        $this->assertEquals(AdCampaign::STATUS_DRAFT, $campaign->status);

        // Traceability in funding history
        $targetAudience = (array) $campaign->target_audience;
        $this->assertNotEmpty($targetAudience['funding_history']);
        $this->assertEquals(40.00, (float) $targetAudience['funding_history'][0]['amount']);
        $this->assertEquals(1.00, (float) $targetAudience['funding_history'][0]['fee_amount']);
    }

    /**
     * TEST 2: Free Event creation does not require campaign funding, creates no campaign, and causes zero wallet deduction.
     */
    public function test_free_event_creation_requires_no_budget_and_leaves_wallet_untouched(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);

        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Community Chess Meetup',
                'category' => 'Sports',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', false);

        // Organizer ad_balance remains completely untouched
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);

        $event = Event::where('title', 'Community Chess Meetup')->first();
        $this->assertNotNull($event);
        $this->assertFalse($event->isPaidCampaign());
        $this->assertNull($event->campaign);
        $this->assertEquals(0, AdCampaign::where('event_id', $event->id)->count());
    }

    /**
     * TEST 3: Draft Paid Event creation with zero/omitted budget creates unfunded draft foundation without wallet debit.
     */
    public function test_draft_paid_event_creation_with_zero_budget_creates_unfunded_draft(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);

        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Draft Paid Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(10)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true);

        // Zero wallet debit
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);

        $event = Event::where('title', 'Draft Paid Event')->first();
        $campaign = $event->campaign;
        $this->assertNotNull($campaign);
        $this->assertEquals(0.00, (float) $campaign->budget);
        $this->assertEquals(0.00, (float) $campaign->remaining_amount);
        $this->assertEquals(0.00, (float) $campaign->wallet_debit);
    }

    /**
     * TEST 4: Insufficient ad balance blocks event creation with 422 and causes zero wallet deduction.
     */
    public function test_insufficient_ad_balance_blocks_event_creation_with_422_and_zero_deduction(): void
    {
        $organizer = $this->createOrganizer(10.00, 300.00, true);

        // Budget 50.00 requires 50 + 1.25 = 51.25 USD, but user only has 10.00 USD
        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Mega Conference',
                'category' => 'Conference',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(12)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 50.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);

        // Zero wallet deduction
        $this->assertEquals(10.00, (float) $organizer->fresh()->ad_balance);

        // Atomic rollback: no event and no campaign created
        $this->assertNull(Event::where('title', 'Mega Conference')->first());
        $this->assertEquals(0, AdCampaign::count());
    }

    /**
     * TEST 5: Unverified creator cannot create paid event with initial budget.
     */
    public function test_unverified_creator_cannot_create_paid_event(): void
    {
        $unverified = $this->createOrganizer(100.00, 300.00, false);

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Unverified Paid Event',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => 20.00,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('needs_verification', true);

        $this->assertEquals(100.00, (float) $unverified->fresh()->ad_balance);
        $this->assertNull(Event::where('title', 'Unverified Paid Event')->first());
    }

    /**
     * TEST 6: Invalid budget amounts are rejected with 422.
     */
    public function test_invalid_budget_amounts_are_rejected(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);

        // Amount below minimum $1.00
        $res1 = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Invalid Event 1',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => 0.50,
            ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['budget']);

        // Negative amount
        $res2 = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Invalid Event 2',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => -20.00,
            ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['budget']);

        // Non-numeric
        $res3 = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Invalid Event 3',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => 'invalid-dollar',
            ]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['budget']);

        // Balance untouched
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * TEST 7: Unsupported currency fails safely.
     */
    public function test_unsupported_currency_fails_safely(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);

        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Euro Event',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => 25.00,
                'currency' => 'EUR',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['currency']);
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * TEST 8: Allocating initial budget to existing unfunded draft campaign via EventCampaignController.
     */
    public function test_allocating_initial_budget_to_existing_unfunded_draft_campaign(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);
        $event = $this->createEvent($organizer);

        // Initialize draft campaign foundation (budget = 0)
        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.store', $event), [
                'campaign_name' => 'Initial Draft',
            ])
            ->assertStatus(201);

        $campaign = $event->campaign()->first();
        $this->assertNotNull($campaign);
        $this->assertEquals(0.00, (float) $campaign->budget);

        // Now allocate initial budget of $30.00 USD
        // Fee = 30 * 0.025 = 0.75 USD, Total Debit = 30.75 USD
        $allocResponse = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.allocate-budget', $event), [
                'budget' => 30.00,
                'currency' => 'USD',
                'idempotency_key' => 'alloc-tx-201',
            ]);

        $allocResponse->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertEquals(30.00, (float) $allocResponse->json('campaign.budget'));
        $this->assertEquals(30.00, (float) $allocResponse->json('campaign.remaining_amount'));
        $this->assertEquals(69.25, (float) $allocResponse->json('available_ad_funds'));

        // Wallet: 100.00 - 30.75 = 69.25 USD
        $this->assertEquals(69.25, (float) $organizer->fresh()->ad_balance);

        $updatedCampaign = $campaign->fresh();
        $this->assertEquals(30.00, (float) $updatedCampaign->budget);
        $this->assertEquals(0.00, (float) $updatedCampaign->additional_funding);
        $this->assertEquals(30.00, (float) $updatedCampaign->total_funded);
        $this->assertEquals(30.00, (float) $updatedCampaign->remaining_amount);
        $this->assertEquals(0.75, (float) $updatedCampaign->fee_amount);
        $this->assertEquals(30.75, (float) $updatedCampaign->wallet_debit);
    }

    /**
     * TEST 9: Allocating budget to already funded campaign prevents double allocation.
     */
    public function test_allocating_budget_to_already_funded_campaign_prevents_double_allocation(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);
        $event = $this->createEvent($organizer);

        // Allocate first time with 20.00 USD (fee 0.50, debit 20.50)
        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.store', $event), [
                'budget' => 20.00,
            ])
            ->assertStatus(201);

        $this->assertEquals(79.50, (float) $organizer->fresh()->ad_balance);

        // Attempting to allocate budget again to already funded campaign
        $secondAlloc = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.allocate-budget', $event), [
                'budget' => 30.00,
            ]);

        $secondAlloc->assertStatus(422)
            ->assertJsonPath('success', false);

        // Zero additional debit: balance remains 79.50 USD
        $this->assertEquals(79.50, (float) $organizer->fresh()->ad_balance);
        $this->assertEquals(20.00, (float) $event->campaign()->first()->budget);
    }

    /**
     * TEST 10: Duplicate submission with same idempotency key is idempotent.
     */
    public function test_duplicate_submission_with_idempotency_key_is_idempotent(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);
        $event = $this->createEvent($organizer);

        $idempotencyKey = 'unique-key-xyz-789';

        // First call allocates 25.00 USD (fee 0.63, debit 25.63)
        $res1 = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.allocate-budget', $event), [
                'budget' => 25.00,
                'idempotency_key' => $idempotencyKey,
            ]);

        $res1->assertStatus(201);
        $this->assertEquals(74.37, (float) $organizer->fresh()->ad_balance);

        // Replay with exact same idempotency key
        $res2 = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.allocate-budget', $event), [
                'budget' => 25.00,
                'idempotency_key' => $idempotencyKey,
            ]);

        $res2->assertStatus(200)
            ->assertJsonPath('is_replay', true);

        // Balance remains exactly 74.37 USD (no second debit!)
        $this->assertEquals(74.37, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * TEST 11: Event campaign belongs strictly to correct event and organizer.
     */
    public function test_event_campaign_belongs_to_correct_event_and_creator(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);
        $event = $this->createEvent($organizer);

        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.store', $event), [
                'budget' => 20.00,
            ])
            ->assertStatus(201);

        $campaign = $event->campaign()->first();
        $this->assertEquals($event->id, $campaign->event_id);
        $this->assertEquals($organizer->id, $campaign->member_id);
        $this->assertTrue($event->isOrganizer($organizer->id));
    }

    /**
     * TEST 12: Unauthorized user cannot allocate budget to another user's event.
     */
    public function test_unauthorized_user_cannot_allocate_budget(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);
        $intruder = $this->createOrganizer(100.00, 300.00, true);
        $event = $this->createEvent($organizer);

        $response = $this->actingAs($intruder, 'member')
            ->postJson(route('member.events.campaign.allocate-budget', $event), [
                'budget' => 20.00,
            ]);

        $response->assertStatus(403);
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
        $this->assertEquals(100.00, (float) $intruder->fresh()->ad_balance);
    }

    /**
     * TEST 13: Initial budget and later Add Funds reconcile perfectly without double counting ($X + $Y).
     */
    public function test_initial_budget_and_later_add_funds_reconcile_without_double_counting(): void
    {
        $organizer = $this->createOrganizer(150.00, 300.00, true);

        // Step 1: Create event with initial budget X = 50.00 USD
        // Fee = 50 * 0.025 = 1.25 USD, Wallet Debit = 51.25 USD
        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Fintech Summit',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(10)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 50.00,
            ]);

        $res->assertStatus(201);
        $this->assertEquals(98.75, (float) $organizer->fresh()->ad_balance); // 150 - 51.25 = 98.75

        $event = Event::where('title', 'Fintech Summit')->first();
        $campaign = $event->campaign;
        $this->assertEquals(50.00, (float) $campaign->budget);
        $this->assertEquals(0.00, (float) $campaign->additional_funding);
        $this->assertEquals(50.00, (float) $campaign->total_funded);
        $this->assertEquals(50.00, (float) $campaign->remaining_amount);

        // Step 2: Phase 4 Add Funds Y = 30.00 USD
        // Fee = 30 * 0.025 = 0.75 USD, Wallet Debit = 30.75 USD
        $addFundRes = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.add-funds', $event), [
                'amount' => 30.00,
            ]);

        $addFundRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Organizer ad_balance: 98.75 - 30.75 = 68.00 USD
        $this->assertEquals(68.00, (float) $organizer->fresh()->ad_balance);

        // Campaign financial reconciliation: X + Y
        $campaign = $campaign->fresh();
        $this->assertEquals(50.00, (float) $campaign->budget); // Initial budget remains X = 50.00
        $this->assertEquals(30.00, (float) $campaign->additional_funding); // Additional funding = Y = 30.00
        $this->assertEquals(80.00, (float) $campaign->total_funded); // Total funded = X + Y = 80.00
        $this->assertEquals(80.00, (float) $campaign->remaining_amount); // Remaining = 80.00
        $this->assertEquals(2.00, (float) $campaign->fee_amount); // 1.25 + 0.75 = 2.00
        $this->assertEquals(82.00, (float) $campaign->wallet_debit); // 51.25 + 30.75 = 82.00

        // Only ONE canonical campaign exists for this event
        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
    }

    /**
     * TEST 14: Member Reward Wallet and referrals are strictly untouched.
     */
    public function test_zero_financial_side_effects_on_reward_wallet_and_referrals(): void
    {
        $organizer = $this->createOrganizer(100.00, 500.00, true);

        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Reward Safety Test Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 25.00,
            ])
            ->assertStatus(201);

        // Member Reward Wallet MUST be untouched
        $this->assertEquals(500.00, (float) $organizer->fresh()->reward_balance);

        // Zero reward records created
        $this->assertEquals(0, AdReward::count());
    }

    /**
     * TEST 15: Event campaigns and Business Page campaigns remain strictly isolated.
     */
    public function test_campaign_isolation_between_events_and_business_ads(): void
    {
        $organizer = $this->createOrganizer(200.00, 300.00, true);

        // Create Business Page
        $businessPage = BusinessPage::create([
            'member_id' => $organizer->id,
            'page_name' => 'Acme Corporation',
            'page_username' => 'acmecorp' . rand(1000, 9999),
            'slug' => 'acme-corp-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        // Create Business Page Ad Campaign with budget 50.00
        $businessCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Acme Product Launch',
            'budget' => 50.00,
            'additional_funding' => 0.00,
            'total_funded' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'currency' => 'USD',
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Create Event with budget 40.00
        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Acme Developer Conference',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 40.00,
            ])
            ->assertStatus(201);

        $event = Event::where('title', 'Acme Developer Conference')->first();
        $eventCampaign = $event->campaign;

        // Scopes & Relations Isolation
        $this->assertEquals(AdCampaign::TYPE_BUSINESS_PAGE, $businessCampaign->fresh()->campaign_type);
        $this->assertNull($businessCampaign->fresh()->event_id);
        $this->assertEquals(50.00, (float) $businessCampaign->fresh()->budget);

        $this->assertEquals(AdCampaign::TYPE_EVENT, $eventCampaign->campaign_type);
        $this->assertNull($eventCampaign->business_page_id);
        $this->assertEquals($event->id, $eventCampaign->event_id);
        $this->assertEquals(40.00, (float) $eventCampaign->budget);

        // Query Scopes cleanly separate them
        $this->assertEquals(1, AdCampaign::businessAds()->count());
        $this->assertEquals(1, AdCampaign::events()->count());
    }

    /**
     * TEST 16: No participant or interested records are created during budget allocation.
     */
    public function test_no_participant_or_interested_records_created_on_budget_allocation(): void
    {
        $organizer = $this->createOrganizer(100.00, 300.00, true);

        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Participant Test Event',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 20.00,
            ])
            ->assertStatus(201);

        $event = Event::where('title', 'Participant Test Event')->first();

        // Only organizer's auto "going" RSVP exists; zero "interested" responses
        $this->assertEquals(1, EventResponse::where('event_id', $event->id)->count());
        $this->assertEquals(0, EventResponse::where('event_id', $event->id)->where('response', 'interested')->count());
    }
}
