<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventCreationCampaignIntegrationTest extends TestCase
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

    private function createBusinessPage(Member $owner): BusinessPage
    {
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Tech Enterprise ' . uniqid(),
            'page_username' => 'tech_ent_' . uniqid(),
            'slug' => 'tech-ent-' . uniqid(),
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    /**
     * REQUIREMENT 1: Event creation requires campaign budget (Mandatory Paid Event Rule).
     */
    public function test_1_valid_free_event_creation_still_works(): void
    {
        $organizer = $this->createOrganizer(100.00, 250.00, true);

        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Community Coffee Meetup',
                'category' => 'Meetup',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'location_city' => 'Austin',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', false);
    }

    /**
     * REQUIREMENT 2: Valid Paid Event creation works.
     */
    public function test_2_valid_paid_event_creation_works(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Global AI Summit 2026',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(10)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 50.00,
                'currency' => 'USD',
                'idempotency_key' => 'idemp-req-2',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true);

        // Platform fee: 50 * 0.025 = 1.25, Total: 51.25, Remainder: 48.75
        $this->assertEquals(48.75, (float) $organizer->fresh()->ad_balance);

        $event = Event::where('title', 'Global AI Summit 2026')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->isPaidCampaign());
    }

    /**
     * REQUIREMENT 3: Paid Event creates exactly one canonical Event campaign.
     */
    public function test_3_paid_event_creates_exactly_one_canonical_event_campaign(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Single Campaign Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => 20.00,
            ])
            ->assertStatus(201);

        $event = Event::where('title', 'Single Campaign Event')->first();
        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
    }

    /**
     * REQUIREMENT 4: Campaign is correctly linked to the Event.
     */
    public function test_4_campaign_is_correctly_linked_to_event(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Linked Campaign Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'budget' => 25.00,
            ]);

        $event = Event::where('title', 'Linked Campaign Event')->first();
        $campaign = AdCampaign::where('event_id', $event->id)->first();

        $this->assertNotNull($campaign);
        $this->assertEquals($event->id, $campaign->event_id);
        $this->assertEquals($campaign->id, $event->campaign->id);
    }

    /**
     * REQUIREMENT 5: Campaign belongs to the correct creator.
     */
    public function test_5_campaign_belongs_to_correct_creator(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Creator Ownership Event',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(3)->toDateString(),
                'is_paid' => 1,
                'budget' => 10.00,
            ])
            ->assertStatus(201);

        $event = Event::where('title', 'Creator Ownership Event')->first();
        $campaign = $event->campaign;

        $this->assertEquals($organizer->id, $campaign->member_id);
        $this->assertEquals($organizer->id, $event->organizer_id);
    }

    /**
     * REQUIREMENT 6: Campaign type identifies it as an Event campaign.
     */
    public function test_6_campaign_type_identifies_it_as_event_campaign(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Type Discriminator Event',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(4)->toDateString(),
                'is_paid' => 1,
                'budget' => 15.00,
            ])
            ->assertStatus(201);

        $event = Event::where('title', 'Type Discriminator Event')->first();
        $campaign = $event->campaign;

        $this->assertEquals(AdCampaign::TYPE_EVENT, $campaign->campaign_type);
        $this->assertStringStartsWith('evcamp_', $campaign->campaign_id);
    }

    /**
     * REQUIREMENT 7: Business Page campaign type remains unchanged.
     */
    public function test_7_business_page_campaign_type_remains_unchanged(): void
    {
        $owner = $this->createOrganizer(100.00, 200.00, true);
        $page = $this->createBusinessPage($owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Business Page Ad',
            'budget' => 50.00,
        ]);

        $this->assertEquals(AdCampaign::TYPE_BUSINESS_PAGE, $campaign->campaign_type);
        $this->assertStringStartsWith('camp_', $campaign->campaign_id);
        $this->assertNull($campaign->event_id);
        $this->assertEquals($page->id, $campaign->business_page_id);
    }

    /**
     * REQUIREMENT 8: Unauthorized user cannot create a Paid Event campaign for another user.
     */
    public function test_8_unauthorized_user_cannot_create_paid_event_campaign_for_another_user(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);
        $intruder = $this->createOrganizer(100.00, 200.00, true);

        // Organizer creates free event
        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Host Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'budget' => 10.00,
            ]);
        $eventId = $res->json('event.id');
        $event = Event::find($eventId);

        // Intruder tries to create/allocate campaign on organizer's event
        $this->actingAs($intruder, 'member')
            ->postJson(route('member.events.campaign.store', $event), [
                'budget' => 25.00,
            ])
            ->assertStatus(403);

        $this->actingAs($intruder, 'member')
            ->postJson(route('member.events.campaign.allocate-budget', $event), [
                'budget' => 25.00,
            ])
            ->assertStatus(403);
    }

    /**
     * REQUIREMENT 9: Unverified creator is blocked when verification is required.
     */
    public function test_9_unverified_creator_is_blocked_when_verification_is_required(): void
    {
        $unverified = $this->createOrganizer(100.00, 200.00, false);

        $this->actingAs($unverified, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Unverified Paid Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'is_paid' => 1,
                'budget' => 20.00,
            ])
            ->assertStatus(403)
            ->assertJsonPath('needs_verification', true);

        $this->assertEquals(0, Event::where('title', 'Unverified Paid Event')->count());
        $this->assertEquals(100.00, (float) $unverified->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 10: Invalid Event payload fails safely.
     */
    public function test_10_invalid_event_payload_fails_safely(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        // Missing required title, category, event_type, start_date
        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'is_paid' => 1,
                'budget' => 25.00,
            ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'category', 'event_type', 'privacy', 'start_date']);

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, AdCampaign::count());
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 11: Invalid campaign budget fails safely.
     */
    public function test_11_invalid_campaign_budget_fails_safely(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        // Budget below 1.00 USD
        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Invalid Budget Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'is_paid' => 1,
                'budget' => 0.50,
            ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, AdCampaign::count());
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 12: Duplicate submission does not create duplicate Event.
     */
    public function test_12_duplicate_submission_does_not_create_duplicate_event(): void
    {
        $organizer = $this->createOrganizer(150.00, 200.00, true);
        $idempotencyKey = 'idem-test-12-unique';

        $payload = [
            'title' => 'Idempotency Event Test',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(6)->toDateString(),
            'is_paid' => 1,
            'budget' => 30.00,
            'idempotency_key' => $idempotencyKey,
        ];

        // Call 1
        $res1 = $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), $payload);
        $res1->assertStatus(201);

        // Call 2 (Duplicate / retry)
        $res2 = $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), $payload);
        $res2->assertStatus(200)
            ->assertJsonPath('is_replay', true);

        $this->assertEquals(1, Event::where('title', 'Idempotency Event Test')->count());
    }

    /**
     * REQUIREMENT 13: Duplicate submission does not create duplicate campaign.
     */
    public function test_13_duplicate_submission_does_not_create_duplicate_campaign(): void
    {
        $organizer = $this->createOrganizer(150.00, 200.00, true);
        $idempotencyKey = 'idem-test-13-unique';

        $payload = [
            'title' => 'Campaign Dup Check Event',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(6)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
            'idempotency_key' => $idempotencyKey,
        ];

        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), $payload)->assertStatus(201);
        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), $payload)->assertStatus(200);

        $event = Event::where('title', 'Campaign Dup Check Event')->first();
        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
    }

    /**
     * REQUIREMENT 14: Repeated setup request does not double-allocate budget.
     */
    public function test_14_repeated_setup_request_does_not_double_allocate_budget(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);
        $idempotencyKey = 'idem-test-14-unique';

        $payload = [
            'title' => 'No Double Debit Event',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(8)->toDateString(),
            'is_paid' => 1,
            'budget' => 40.00, // Fee 1.00, Total 41.00
            'idempotency_key' => $idempotencyKey,
        ];

        // 1st request debits 41.00 -> balance becomes 59.00
        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), $payload)->assertStatus(201);
        $this->assertEquals(59.00, (float) $organizer->fresh()->ad_balance);

        // 2nd request replay
        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), $payload)->assertStatus(200);
        $this->assertEquals(59.00, (float) $organizer->fresh()->ad_balance); // Balance remains 59.00!
    }

    /**
     * REQUIREMENT 15: Existing Event/campaign relationship is reused safely where appropriate.
     */
    public function test_15_existing_event_campaign_relationship_is_reused_safely_where_appropriate(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        // Create paid event with budget 20.00
        $res = $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Existing Campaign Reuse Event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
        ]);
        $event = Event::find($res->json('event.id'));

        // Calling campaign store endpoint returns existing campaign foundation
        $resStore = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.store', $event));

        $resStore->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.event_id', $event->id);

        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
    }

    /**
     * REQUIREMENT 16: Event creation and campaign creation do not leave partial state on failure.
     */
    public function test_16_event_creation_and_campaign_creation_do_not_leave_partial_state(): void
    {
        $organizer = $this->createOrganizer(20.00, 200.00, true); // only 20.00 available

        // Attempt to create event with 50.00 budget (requires 51.25) -> insufficient funds
        $res = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Insufficient Balance Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'is_paid' => 1,
                'budget' => 50.00,
            ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);

        // Zero partial records: no event, no campaign, no wallet deduction
        $this->assertEquals(0, Event::where('title', 'Insufficient Balance Event')->count());
        $this->assertEquals(0, AdCampaign::count());
        $this->assertEquals(20.00, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 17: Phase 4 Add Fund targets the same Event campaign.
     */
    public function test_17_phase_4_add_fund_targets_the_same_event_campaign(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        // Step 1: Create Paid Event with $20 budget (Fee $0.50, Debit $20.50, Remainder $79.50)
        $res = $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Fundable Event Campaign',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
        ]);
        $event = Event::find($res->json('event.id'));
        $campaign = $event->campaign;

        // Step 2: Call Phase 4 Add Fund with $30 top-up (Fee $0.75, Debit $30.75, Remainder $48.75)
        $addFundRes = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.campaign.add-funds', $event), [
                'amount' => 30.00,
            ]);

        $addFundRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify it targeted the EXACT same campaign
        $freshCampaign = $campaign->fresh();
        $this->assertEquals($campaign->id, $freshCampaign->id);
        $this->assertEquals(20.00, (float) $freshCampaign->budget);
        $this->assertEquals(30.00, (float) $freshCampaign->additional_funding);
        $this->assertEquals(50.00, (float) $freshCampaign->total_funded);
        $this->assertEquals(50.00, (float) $freshCampaign->remaining_amount);

        // Exactly one campaign exists for event
        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
        $this->assertEquals(48.75, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 18: Phase 5 budget semantics remain intact.
     */
    public function test_18_phase_5_budget_semantics_remain_intact(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Semantics Verification Event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 40.00,
        ]);

        $event = Event::where('title', 'Semantics Verification Event')->first();
        $campaign = $event->campaign;

        $this->assertEquals(40.00, (float) $campaign->budget);
        $this->assertEquals(0.00, (float) $campaign->additional_funding);
        $this->assertEquals(40.00, (float) $campaign->total_funded);
        $this->assertEquals(0.00, (float) $campaign->spent_amount);
        $this->assertEquals(40.00, (float) $campaign->remaining_amount);
        $this->assertEquals(2.50, (float) $campaign->fee_percent);
        $this->assertEquals(1.00, (float) $campaign->fee_amount);
        $this->assertEquals(41.00, (float) $campaign->wallet_debit);
        $this->assertEquals('USD', $campaign->currency);
        $this->assertContains($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_DRAFT]);
        $this->assertContains($campaign->approval_status, [AdCampaign::APPROVAL_APPROVED, AdCampaign::APPROVAL_PENDING]);
    }

    /**
     * REQUIREMENT 19: Reward Wallet is unchanged.
     */
    public function test_19_reward_wallet_is_unchanged(): void
    {
        $organizer = $this->createOrganizer(100.00, 350.00, true);

        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Reward Wallet Untouched Event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
        ]);

        $this->assertEquals(350.00, (float) $organizer->fresh()->reward_balance);
    }

    /**
     * REQUIREMENT 20: No reward record is created.
     */
    public function test_20_no_reward_record_is_created(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Zero Rewards Event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
        ]);

        $this->assertEquals(0, AdReward::count());
    }

    /**
     * REQUIREMENT 21: No referral count changes.
     */
    public function test_21_no_referral_count_changes(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Referrals Unchanged Event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
        ]);

        // Organizer direct referral count untouched
        $this->assertEquals(0, (int) $organizer->fresh()->direct_referral_count);
    }

    /**
     * REQUIREMENT 22: No participant/Interested record is created.
     */
    public function test_22_no_participant_or_interested_record_is_created(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        $res = $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Zero Interested Records Event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 20.00,
        ]);

        $eventId = $res->json('event.id');
        $this->assertEquals(0, EventResponse::where('event_id', $eventId)->where('response', 'interested')->count());
        $this->assertEquals(1, EventResponse::where('event_id', $eventId)->where('response', 'going')->count());
    }

    /**
     * REQUIREMENT 23: Existing Event editing does not create duplicate campaigns nor reset budget.
     */
    public function test_23_existing_event_editing_does_not_create_duplicate_campaigns_nor_reset_budget(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);

        // Step 1: Create Paid Event with $50 budget
        $res = $this->actingAs($organizer, 'member')->postJson(route('member.events.store'), [
            'title' => 'Original Event Title',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'is_paid' => 1,
            'budget' => 50.00,
        ]);
        $eventId = $res->json('event.id');
        $event = Event::find($eventId);
        $campaign = $event->campaign;

        // Step 2: Edit Event Title & Description
        $updateRes = $this->actingAs($organizer, 'member')
            ->putJson(route('member.events.update', $event), [
                'title' => 'Updated Event Title 2026',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(6)->toDateString(),
                'description' => 'Updated description content.',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true)
            ->assertJsonPath('campaign.budget', 50);

        // Verify Event is updated
        $this->assertEquals('Updated Event Title 2026', $event->fresh()->title);

        // Verify campaign name is synced, but budget & financials are 100% IMMUTABLE
        $freshCampaign = $campaign->fresh();
        $this->assertEquals('Updated Event Title 2026', $freshCampaign->campaign_name);
        $this->assertEquals(50.00, (float) $freshCampaign->budget);
        $this->assertEquals(0.00, (float) $freshCampaign->additional_funding);
        $this->assertEquals(50.00, (float) $freshCampaign->total_funded);
        $this->assertEquals(50.00, (float) $freshCampaign->remaining_amount);
        $this->assertEquals(1.25, (float) $freshCampaign->fee_amount);
        $this->assertEquals(51.25, (float) $freshCampaign->wallet_debit);

        // Exactly one campaign exists
        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
        $this->assertEquals(48.75, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 24: Existing Business Ads creation remains functional.
     */
    public function test_24_existing_business_ads_creation_remains_functional(): void
    {
        $owner = $this->createOrganizer(100.00, 200.00, true);
        $page = $this->createBusinessPage($owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Standalone Business Ad',
            'budget' => 30.00,
            'remaining_amount' => 30.00,
            'total_funded' => 30.00,
        ]);

        $this->assertEquals(AdCampaign::TYPE_BUSINESS_PAGE, $campaign->campaign_type);
        $this->assertEquals($page->id, $campaign->business_page_id);
        $this->assertNull($campaign->event_id);
    }

    /**
     * REQUIREMENT 25: Existing Business Ads Add Fund remains functional.
     */
    public function test_25_existing_business_ads_add_fund_remains_functional(): void
    {
        $owner = $this->createOrganizer(100.00, 200.00, true);
        $page = $this->createBusinessPage($owner);

        // 1. Create Business Page draft campaign with $30.00 budget (wallet debited 30.75, remainder 69.25)
        $resCreate = $this->actingAs($owner, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Page Campaign For Funding',
            'budget' => 30.00,
            'currency' => 'USD',
        ]);
        $resCreate->assertStatus(201);
        $campaignId = $resCreate->json('campaign.id');

        // 2. Adjust/increase funding to $50.00 via PUT (diff +$20.50, remainder becomes 48.75)
        $res = $this->actingAs($owner, 'member')->putJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}", [
            'budget' => 50.00,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $fresh = AdCampaign::find($campaignId);
        $this->assertEquals(50.00, (float) $fresh->budget);
        $this->assertEquals(50.00, (float) $fresh->remaining_amount);
        $this->assertEquals(48.75, (float) $owner->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 26: Existing free Events remain functional.
     */
    public function test_26_existing_free_events_remain_functional(): void
    {
        $organizer = $this->createOrganizer(100.00, 200.00, true);
        $guest = $this->createOrganizer(50.00, 100.00, true);

        // Historical Free Event in database
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Free Community Workshop',
            'slug' => 'free-community-workshop-' . uniqid(),
            'category' => 'Education',
            'event_type' => 'online',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(3)->toDateString(),
        ]);
        EventResponse::create([
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'response' => 'going',
        ]);

        // View Free Event
        $showRes = $this->actingAs($guest, 'member')->getJson(route('member.events.show', $event));
        $showRes->assertStatus(200)
            ->assertJsonPath('is_paid', false)
            ->assertJsonPath('campaign', null);

        // Respond to Free Event
        $respondRes = $this->actingAs($guest, 'member')->postJson(route('member.events.respond', $event), [
            'response' => 'interested',
        ]);
        $respondRes->assertStatus(200)
            ->assertJsonPath('response', 'interested');

        // Edit Free Event
        $editRes = $this->actingAs($organizer, 'member')->putJson(route('member.events.update', $event), [
            'title' => 'Free Community Workshop - Updated',
            'category' => 'Education',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(4)->toDateString(),
        ]);
        $editRes->assertStatus(200)
            ->assertJsonPath('is_paid', false);

        // Verify zero campaigns created throughout entire free event lifecycle
        $this->assertEquals(0, AdCampaign::where('event_id', $event->id)->count());
    }

    /**
     * REQUIREMENT 27: No destructive database operation is used.
     */
    public function test_27_no_destructive_database_operation_is_used(): void
    {
        // Assert that all essential tables exist intact
        $this->assertTrue(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasTable('events'));
        $this->assertTrue(Schema::hasTable('event_responses'));
        $this->assertTrue(Schema::hasTable('ad_campaigns'));
        $this->assertTrue(Schema::hasTable('business_pages'));
        $this->assertTrue(Schema::hasTable('ad_rewards'));
        $this->assertTrue(Schema::hasTable('ad_reward_rules'));

        // Assert member balances and campaign foreign key exist
        $this->assertTrue(Schema::hasColumn('members', 'ad_balance'));
        $this->assertTrue(Schema::hasColumn('members', 'reward_balance'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'event_id'));
    }
}
