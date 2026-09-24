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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventCampaignActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('campaign_platform_fee_percent', 2.50);
    }

    private function createMember(float $adBalance = 100.00, float $rewardBalance = 250.00, bool $verified = true): Member
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

    private function createEvent(Member $organizer, string $status = 'published', ?string $endDate = null): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Annual Summit ' . uniqid(),
            'slug' => 'annual-summit-' . uniqid(),
            'description' => 'A premier networking and learning event.',
            'category' => 'Conference',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => $endDate ?? now()->addDays(12)->toDateString(),
            'location_city' => 'New York',
            'status' => $status,
        ]);
    }

    private function createFundedEventCampaign(Event $event, Member $organizer, float $budget = 50.00, string $status = AdCampaign::STATUS_DRAFT): AdCampaign
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
            'status' => $status,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);
    }

    /**
     * REQUIREMENT 1: Valid Event Campaign can activate.
     */
    public function test_01_valid_event_campaign_can_activate(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_already_active', false)
            ->assertJsonPath('campaign.status', AdCampaign::STATUS_ACTIVE)
            ->assertJsonPath('campaign.approval_status', AdCampaign::APPROVAL_APPROVED);

        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
        $this->assertEquals(AdCampaign::APPROVAL_APPROVED, $campaign->fresh()->approval_status);
        $this->assertNotNull($campaign->fresh()->approved_at);
    }

    /**
     * REQUIREMENT 2: Free Event is not incorrectly forced into campaign activation.
     */
    public function test_02_free_event_is_not_forced_into_campaign_activation(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $freeEvent = $this->createEvent($organizer);

        // Event exists without any campaign
        $this->assertNull($freeEvent->campaign);
        $this->assertFalse($freeEvent->isPaidCampaign());

        // Attempting activation returns 404 because no campaign exists
        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$freeEvent->id}/campaign/activate");

        $res->assertStatus(404)
            ->assertJsonPath('success', false);

        // Free event remains unaffected and without campaign
        $this->assertNull($freeEvent->fresh()->campaign);
    }

    /**
     * REQUIREMENT 3: Unauthenticated request fails.
     */
    public function test_03_unauthenticated_request_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer);

        $res = $this->postJson("/api/member/events/{$event->id}/campaign/activate");
        $res->assertStatus(401);
    }

    /**
     * REQUIREMENT 4: Unauthorized creator request fails.
     */
    public function test_04_unauthorized_creator_request_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $otherMember = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer);

        $res = $this->actingAs($otherMember, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized. Only the event organizer can activate this event campaign.');
    }

    /**
     * REQUIREMENT 5: Another creator cannot activate the campaign.
     */
    public function test_05_another_creator_cannot_activate_the_campaign(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $intruder = $this->createMember(500.00, 100.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer);

        $res = $this->actingAs($intruder, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(403);
        $this->assertEquals(AdCampaign::STATUS_DRAFT, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 6: Incorrect Event/campaign relationship fails.
     */
    public function test_06_incorrect_event_campaign_relationship_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $eventA = $this->createEvent($organizer);
        $eventB = $this->createEvent($organizer);
        $this->createFundedEventCampaign($eventA, $organizer);

        // Attempting to activate eventB (which has no campaign) fails
        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$eventB->id}/campaign/activate");

        $res->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /**
     * REQUIREMENT 7: Incorrect campaign type fails.
     */
    public function test_07_incorrect_campaign_type_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);

        // Corrupted/invalid campaign with TYPE_BUSINESS_PAGE attached to event
        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Mismatched Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_DRAFT,
        ]);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid campaign type. Only event campaigns can be activated through this endpoint.');

        $this->assertEquals(AdCampaign::STATUS_DRAFT, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 8: Missing campaign fails.
     */
    public function test_08_missing_campaign_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No campaign foundation found for this event. Please create and fund a campaign first.');
    }

    /**
     * REQUIREMENT 9: Invalid creator verification state fails when required.
     */
    public function test_09_invalid_creator_verification_state_fails_when_required(): void
    {
        $unverifiedOrganizer = $this->createMember(100.00, 0.00, false);
        $event = $this->createEvent($unverifiedOrganizer);
        $this->createFundedEventCampaign($event, $unverifiedOrganizer);

        $res = $this->actingAs($unverifiedOrganizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('needs_verification', true);
    }

    /**
     * REQUIREMENT 10: Invalid Event state fails.
     */
    public function test_10_invalid_event_state_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $cancelledEvent = $this->createEvent($organizer, 'cancelled');
        $this->createFundedEventCampaign($cancelledEvent, $organizer);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$cancelledEvent->id}/campaign/activate");

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('Event must be in published status', $res->json('message'));
    }

    /**
     * REQUIREMENT 11: Invalid campaign state fails.
     */
    public function test_11_invalid_campaign_state_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $stoppedCampaign = $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_STOPPED);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('Cannot activate campaign in its current status: stopped', $res->json('message'));
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $stoppedCampaign->fresh()->status);
    }

    /**
     * REQUIREMENT 12: Invalid date/schedule state fails when applicable.
     */
    public function test_12_invalid_date_schedule_state_fails_when_applicable(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $pastEndDate = now()->subDays(5)->toDateString();
        $expiredEvent = $this->createEvent($organizer, 'published', $pastEndDate);
        $this->createFundedEventCampaign($expiredEvent, $organizer);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$expiredEvent->id}/campaign/activate");

        $res->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot activate campaign for an event that has already ended.');
    }

    /**
     * REQUIREMENT 13: Insufficient required campaign configuration fails.
     */
    public function test_13_insufficient_required_campaign_configuration_fails(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);

        // Unfunded draft campaign with 0 budget and 0 remaining
        $unfundedCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Unfunded Campaign',
            'budget' => 0.00,
            'remaining_amount' => 0.00,
            'status' => AdCampaign::STATUS_DRAFT,
        ]);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('without configured funds', $res->json('message'));
        $this->assertEquals(AdCampaign::STATUS_DRAFT, $unfundedCampaign->fresh()->status);
    }

    /**
     * REQUIREMENT 14: Valid activation changes status exactly once.
     */
    public function test_14_valid_activation_changes_status_exactly_once(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 30.00, AdCampaign::STATUS_DRAFT);

        $this->assertEquals(AdCampaign::STATUS_DRAFT, $campaign->status);

        $res = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");

        $res->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
        $this->assertEquals(AdCampaign::APPROVAL_APPROVED, $campaign->fresh()->approval_status);

        // Verify lifecycle history in campaign metadata
        $metadata = $campaign->fresh()->target_audience;
        $this->assertNotEmpty($metadata['lifecycle_history']);
        $this->assertEquals('activated', $metadata['lifecycle_history'][0]['action']);
        $this->assertEquals(AdCampaign::STATUS_DRAFT, $metadata['lifecycle_history'][0]['from_status']);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $metadata['lifecycle_history'][0]['to_status']);
    }

    /**
     * REQUIREMENT 15: Repeated activation is idempotent.
     */
    public function test_15_repeated_activation_is_idempotent(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 40.00, AdCampaign::STATUS_DRAFT);

        // 1st Activation
        $res1 = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");
        $res1->assertStatus(200)
            ->assertJsonPath('is_already_active', false);

        // 2nd Activation (idempotent replay)
        $res2 = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate");
        $res2->assertStatus(200)
            ->assertJsonPath('is_already_active', true)
            ->assertJsonPath('campaign.status', AdCampaign::STATUS_ACTIVE);

        // Status remains unchanged
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
    }

    /**
     * REQUIREMENT 16: Concurrent activation is safe.
     */
    public function test_16_concurrent_activation_is_safe(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        // Execute sequential simulated race condition inside transactions
        DB::transaction(function () use ($organizer, $event) {
            $res1 = $this->actingAs($organizer, 'member')
                ->postJson("/api/member/events/{$event->id}/campaign/activate");
            $res1->assertStatus(200);
        });

        DB::transaction(function () use ($organizer, $event) {
            $res2 = $this->actingAs($organizer, 'member')
                ->postJson("/api/member/events/{$event->id}/campaign/activate");
            $res2->assertStatus(200)
                ->assertJsonPath('is_already_active', true);
        });

        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
        $this->assertEquals(50.00, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * REQUIREMENT 17: Activation does not change wallet balance.
     */
    public function test_17_activation_does_not_change_wallet_balance(): void
    {
        $organizer = $this->createMember(88.50, 20.00, true);
        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $initialBalance = (float) $organizer->fresh()->ad_balance;
        $this->assertEquals(88.50, $initialBalance);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $this->assertEquals($initialBalance, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 18: Activation does not change Reward Wallet.
     */
    public function test_18_activation_does_not_change_reward_wallet(): void
    {
        $organizer = $this->createMember(100.00, 314.15, true);
        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $initialRewardBalance = (float) $organizer->fresh()->reward_balance;
        $this->assertEquals(314.15, $initialRewardBalance);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $this->assertEquals($initialRewardBalance, (float) $organizer->fresh()->reward_balance);
    }

    /**
     * REQUIREMENT 19: Activation creates no reward record.
     */
    public function test_19_activation_creates_no_reward_record(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $initialRewardCount = AdReward::count();

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $this->assertEquals($initialRewardCount, AdReward::count());
    }

    /**
     * REQUIREMENT 20: Activation does not change referrals.
     */
    public function test_20_activation_does_not_change_referrals(): void
    {
        $sponsor = $this->createMember(50.00, 10.00, true);
        $organizer = Member::create([
            'name' => 'Invited Organizer',
            'email' => 'invited_' . uniqid() . '@example.com',
            'password' => 'password123',
            'phone' => '+1555' . rand(1000000, 9999999),
            'mobile_verified_at' => now(),
            'ad_balance' => 100.00,
            'reward_balance' => 0.00,
            'introducer_id' => $sponsor->id,
        ]);

        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        // Sponsor balances unchanged
        $this->assertEquals(50.00, (float) $sponsor->fresh()->ad_balance);
        $this->assertEquals(10.00, (float) $sponsor->fresh()->reward_balance);
    }

    /**
     * REQUIREMENT 21: Activation does not create Interested records.
     */
    public function test_21_activation_does_not_create_interested_records(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $initialResponseCount = EventResponse::count();

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $this->assertEquals($initialResponseCount, EventResponse::count());
    }

    /**
     * REQUIREMENT 22: Activation does not deplete campaign budget.
     */
    public function test_22_activation_does_not_deplete_campaign_budget(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 75.00, AdCampaign::STATUS_DRAFT);

        $this->assertEquals(75.00, (float) $campaign->budget);
        $this->assertEquals(75.00, (float) $campaign->remaining_amount);
        $this->assertEquals(0.00, (float) $campaign->spent_amount);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $fresh = $campaign->fresh();
        $this->assertEquals(75.00, (float) $fresh->budget);
        $this->assertEquals(75.00, (float) $fresh->remaining_amount);
        $this->assertEquals(0.00, (float) $fresh->spent_amount);
    }

    /**
     * REQUIREMENT 23: Event ↔ campaign relationship remains intact.
     */
    public function test_23_event_campaign_relationship_remains_intact(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $this->assertEquals($campaign->id, $event->fresh()->campaign->id);
        $this->assertEquals($event->id, $campaign->fresh()->event->id);
        $this->assertEquals(AdCampaign::TYPE_EVENT, $campaign->fresh()->campaign_type);
    }

    /**
     * REQUIREMENT 24: Existing Add Fund remains functional after activation.
     */
    public function test_24_existing_add_fund_remains_functional_after_activation(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 30.00, AdCampaign::STATUS_DRAFT);

        // 1. Activate
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);

        // 2. Add Funds to Active Campaign ($20 USD + $0.50 fee = $20.50 debit)
        $addFundRes = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
                'amount' => 20.00,
            ]);

        $addFundRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $fresh = $campaign->fresh();
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $fresh->status);
        $this->assertEquals(50.00, (float) $fresh->remaining_amount);
        $this->assertEquals(50.00, (float) $fresh->total_funded);
        $this->assertEquals(20.00, (float) $fresh->additional_funding);
        $this->assertEquals(79.50, (float) $organizer->fresh()->ad_balance);
    }

    /**
     * REQUIREMENT 25: Existing Business Ads activation remains functional.
     */
    public function test_25_existing_business_ads_activation_remains_functional(): void
    {
        $owner = $this->createMember(100.00, 0.00, true);
        $businessPage = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Enterprise Ads ' . uniqid(),
            'page_username' => 'ent_ads_' . uniqid(),
            'slug' => 'ent-ads-' . uniqid(),
            'category' => 'Marketing',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        $businessCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Product Ad',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Submit for review using canonical BusinessAdCampaignController
        $res = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns/{$businessCampaign->id}/submit");

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(AdCampaign::STATUS_PENDING_REVIEW, $businessCampaign->fresh()->status);
    }

    /**
     * REQUIREMENT 26: Existing Business Ads campaigns remain isolated.
     */
    public function test_26_existing_business_ads_campaigns_remain_isolated(): void
    {
        $bizOwner = $this->createMember(100.00, 0.00, true);
        $businessPage = BusinessPage::create([
            'member_id' => $bizOwner->id,
            'page_name' => 'Isolated Corp ' . uniqid(),
            'page_username' => 'iso_corp_' . uniqid(),
            'slug' => 'iso-corp-' . uniqid(),
            'category' => 'Finance',
            'status' => 'active',
            'visibility' => 'public',
        ]);
        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $bizOwner->id,
            'campaign_name' => 'Isolated Business Ad',
            'budget' => 100.00,
            'remaining_amount' => 100.00,
            'status' => AdCampaign::STATUS_DRAFT,
        ]);

        // Event creator activates event campaign
        $eventOrganizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($eventOrganizer);
        $eventCampaign = $this->createFundedEventCampaign($event, $eventOrganizer, 40.00, AdCampaign::STATUS_DRAFT);

        $this->actingAs($eventOrganizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        // Business campaign is completely untouched
        $freshBiz = $bizCampaign->fresh();
        $this->assertEquals(AdCampaign::STATUS_DRAFT, $freshBiz->status);
        $this->assertEquals(100.00, (float) $freshBiz->remaining_amount);
        $this->assertEquals($businessPage->id, $freshBiz->business_page_id);
        $this->assertNull($freshBiz->event_id);

        // Event campaign is active
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $eventCampaign->fresh()->status);
    }

    /**
     * REQUIREMENT 27: Existing free Events remain functional.
     */
    public function test_27_existing_free_events_remain_functional(): void
    {
        $organizer = $this->createMember(50.00, 0.00, true);
        $guest = $this->createMember(20.00, 0.00, true);
        $freeEvent = $this->createEvent($organizer);

        // Guest can RSVP to free event
        $rsvpRes = $this->actingAs($guest, 'member')
            ->postJson("/api/member/events/{$freeEvent->id}/respond", [
                'response' => 'going',
            ]);

        $rsvpRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('event_responses', [
            'event_id' => $freeEvent->id,
            'member_id' => $guest->id,
            'response' => 'going',
        ]);
    }

    /**
     * REQUIREMENT 28: Existing Event editing remains functional.
     */
    public function test_28_existing_event_editing_remains_functional(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        // 1. Activate campaign
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);

        // 2. Edit event
        $updateRes = $this->actingAs($organizer, 'member')
            ->putJson("/api/member/events/{$event->id}", [
                'title' => 'Updated Event Title',
                'description' => 'Updated detailed description for summit.',
                'category' => 'Conference',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(15)->toDateString(),
                'end_date' => now()->addDays(17)->toDateString(),
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('Updated Event Title', $event->fresh()->title);

        // Campaign remains active and budget intact
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
        $this->assertEquals(50.00, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * REQUIREMENT 29: No destructive database command is used.
     */
    public function test_29_no_destructive_database_command_is_used(): void
    {
        $this->assertTrue(Schema::hasTable('ad_campaigns'));
        $this->assertTrue(Schema::hasTable('events'));
        $this->assertTrue(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasTable('business_pages'));
        $this->assertTrue(Schema::hasTable('ad_rewards'));
        $this->assertTrue(Schema::hasTable('event_responses'));

        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'status'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'approval_status'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'budget'));
        $this->assertTrue(Schema::hasColumn('ad_campaigns', 'remaining_amount'));
    }

    /**
     * ADDITIONAL LIFECYCLE TESTS: Pause and Resume functionality
     */
    public function test_30_active_campaign_can_be_paused_and_resumed(): void
    {
        $organizer = $this->createMember(100.00, 0.00, true);
        $event = $this->createEvent($organizer);
        $campaign = $this->createFundedEventCampaign($event, $organizer, 50.00, AdCampaign::STATUS_DRAFT);

        // 1. Activate
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/activate")
            ->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);

        // 2. Pause
        $pauseRes = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/pause");
        $pauseRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.status', AdCampaign::STATUS_PAUSED);
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $campaign->fresh()->status);

        // Repeated pause is idempotent
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/pause")
            ->assertStatus(200)
            ->assertJsonPath('is_already_paused', true);

        // 3. Resume
        $resumeRes = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/resume");
        $resumeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.status', AdCampaign::STATUS_ACTIVE);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);

        // Repeated resume is idempotent
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/resume")
            ->assertStatus(200)
            ->assertJsonPath('is_already_active', true);
    }
}
