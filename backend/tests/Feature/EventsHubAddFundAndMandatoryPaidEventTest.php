<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsHubAddFundAndMandatoryPaidEventTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, float $adBalance = 50.00): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'phone' => '+9198' . random_int(10000000, 99999999),
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'ad_balance' => $adBalance,
        ]);
    }

    private function createEvent(Member $organizer, array $attributes = []): Event
    {
        $title = $attributes['title'] ?? ('Test Event ' . uniqid());

        return Event::create(array_merge([
            'organizer_id' => $organizer->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString() . '-' . uniqid(),
            'description' => 'Event description',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(3)->toDateString(),
            'start_time' => '18:00',
        ], $attributes));
    }

    private function attachCampaignToEvent(Event $event, Member $member, float $budget = 10.00, float $spent = 0.00): AdCampaign
    {
        return AdCampaign::create([
            'campaign_id' => 'CAMP-EV-' . strtoupper(uniqid()),
            'member_id' => $member->id,
            'event_id' => $event->id,
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'campaign_name' => $event->title . ' Promotion',
            'budget' => $budget,
            'additional_funding' => 0.00,
            'spent_amount' => $spent,
            'remaining_amount' => round($budget - $spent, 2),
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);
    }

    public function test_add_fund_tab_requires_authentication(): void
    {
        $response = $this->getJson('/api/member/events?tab=add-fund');
        $response->assertStatus(401);
    }

    public function test_events_api_supports_add_fund_tab_for_authenticated_creator(): void
    {
        $creator = $this->createMember('Creator Alice', 100.00);
        $event = $this->createEvent($creator, ['title' => 'Alice Global Summit']);
        $this->attachCampaignToEvent($event, $creator, 25.00, 5.00);

        $this->actingAs($creator, 'member');

        $response = $this->getJson('/api/member/events?tab=add-fund');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tab', 'add-fund')
            ->assertJsonPath('fundable_events_count', 1)
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Alice Global Summit');

        $this->assertEquals(100.00, (float) $response->json('available_ad_funds'));
        $this->assertEquals(2.5, (float) $response->json('platform_fee_percent'));
        $this->assertEquals(25.00, (float) $response->json('events.data.0.campaign_details.budget'));
        $this->assertEquals(5.00, (float) $response->json('events.data.0.campaign_details.spent_amount'));
        $this->assertEquals(20.00, (float) $response->json('events.data.0.campaign_details.remaining_amount'));
    }

    public function test_add_fund_tab_strictly_isolates_creator_event_campaigns(): void
    {
        $memberA = $this->createMember('Member A', 100.00);
        $memberB = $this->createMember('Member B', 100.00);

        // Member A's event campaign
        $eventA = $this->createEvent($memberA, ['title' => 'Event of Member A']);
        $this->attachCampaignToEvent($eventA, $memberA, 15.00);

        // Member B's event campaign
        $eventB = $this->createEvent($memberB, ['title' => 'Event of Member B']);
        $this->attachCampaignToEvent($eventB, $memberB, 20.00);

        // Member A's Business Page Ad Campaign (MUST NOT show in Events Add Fund tab)
        $businessPage = BusinessPage::create([
            'member_id' => $memberA->id,
            'page_name' => 'Biz Corp',
            'page_username' => 'bizcorp' . random_int(100, 999),
            'category' => 'Technology',
            'slug' => 'biz-corp-' . uniqid(),
            'status' => 'approved',
        ]);
        AdCampaign::create([
            'campaign_id' => 'CAMP-BIZ-' . strtoupper(uniqid()),
            'member_id' => $memberA->id,
            'business_page_id' => $businessPage->id,
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'campaign_name' => 'Biz Page Ad',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $this->actingAs($memberA, 'member');

        $response = $this->getJson('/api/member/events?tab=add-fund');

        $response->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Event of Member A')
            ->assertJsonPath('fundable_events_count', 1);
    }

    public function test_all_tabs_provide_authoritative_tab_counters(): void
    {
        $creator = $this->createMember('Event Host', 80.00);
        $event1 = $this->createEvent($creator, ['title' => 'Event One']);
        $this->attachCampaignToEvent($event1, $creator, 10.00);

        $event2 = $this->createEvent($creator, ['title' => 'Event Two']);
        $this->attachCampaignToEvent($event2, $creator, 10.00);

        $this->actingAs($creator, 'member');

        // Check on All tab
        $resAll = $this->getJson('/api/member/events?tab=all');
        $resAll->assertOk()
            ->assertJsonPath('my_events_count', 2)
            ->assertJsonPath('fundable_events_count', 2);

        // Check on My tab
        $resMy = $this->getJson('/api/member/events?tab=my');
        $resMy->assertOk()
            ->assertJsonPath('my_events_count', 2)
            ->assertJsonPath('fundable_events_count', 2);

        // Check on Add Fund tab
        $resFund = $this->getJson('/api/member/events?tab=add-fund');
        $resFund->assertOk()
            ->assertJsonPath('my_events_count', 2)
            ->assertJsonPath('fundable_events_count', 2);
    }

    public function test_store_event_validates_budget_and_supports_free_events(): void
    {
        $creator = $this->createMember('Creator Host', 50.00);
        $this->actingAs($creator, 'member');

        // Missing budget creates organic free event
        $resNoBudget = $this->postJson('/api/member/events', [
            'title' => 'No Budget Event',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(4)->toDateString(),
        ]);
        $resNoBudget->assertStatus(201)
            ->assertJsonPath('is_paid', false);

        // Budget below $1.00 USD
        $resLowBudget = $this->postJson('/api/member/events', [
            'title' => 'Low Budget Event',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(4)->toDateString(),
            'budget' => 0.50,
        ]);
        $resLowBudget->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);
    }

    public function test_store_event_successfully_creates_paid_event_and_active_campaign(): void
    {
        $creator = $this->createMember('Creator Bob', 50.00);
        $this->actingAs($creator, 'member');

        $budget = 10.00;
        $fee = round($budget * 0.025, 2); // 0.25
        $totalDebit = $budget + $fee;     // 10.25

        $response = $this->postJson('/api/member/events', [
            'title' => 'Crypto Leaders Conference',
            'category' => 'Crypto',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '15:00',
            'budget' => $budget,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true);

        $eventId = $response->json('event.id');
        $event = Event::with('campaign')->find($eventId);

        $this->assertNotNull($event);
        $this->assertEquals('Crypto Leaders Conference', $event->title);
        $this->assertTrue($event->isPaidCampaign());

        // Assert Campaign was created and properly linked
        $campaign = $event->campaign;
        $this->assertNotNull($campaign);
        $this->assertEquals(AdCampaign::TYPE_EVENT, $campaign->campaign_type);
        $this->assertContains($campaign->status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_DRAFT]);
        $this->assertEquals(10.00, (float) $campaign->budget);
        $this->assertEquals(10.00, (float) $campaign->remaining_amount);

        // Assert creator ad_balance was debited
        $creator->refresh();
        $this->assertEquals(round(50.00 - $totalDebit, 2), (float) $creator->ad_balance);
    }

    public function test_store_event_rejects_when_ad_balance_is_insufficient(): void
    {
        $creator = $this->createMember('Broke Creator', 5.00);
        $this->actingAs($creator, 'member');

        $budget = 10.00; // Requires $10.25 but only has $5.00

        $response = $this->postJson('/api/member/events', [
            'title' => 'Mega Budget Summit',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'budget' => $budget,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);
    }

    public function test_add_funds_to_event_campaign_via_alias_route(): void
    {
        $creator = $this->createMember('Top Up Host', 40.00);
        $event = $this->createEvent($creator, ['title' => 'Top Up Event']);
        $this->attachCampaignToEvent($event, $creator, 10.00, 2.00);

        $this->actingAs($creator, 'member');

        $topUpAmount = 15.00;
        $fee = round($topUpAmount * 0.025, 2); // 0.38
        $totalDebit = $topUpAmount + $fee;     // 15.38

        // Test alias route POST /api/member/events/{id}/add-funds
        $response = $this->postJson('/api/member/events/' . $event->id . '/add-funds', [
            'amount' => $topUpAmount,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(23.00, (float) $response->json('campaign.remaining_amount')); // 8.00 prev remaining + 15.00 = 23.00

        $creator->refresh();
        $this->assertEquals(round(40.00 - $totalDebit, 2), (float) $creator->ad_balance);
    }

    public function test_historical_free_events_remain_safely_viewable(): void
    {
        $creator = $this->createMember('Old School Host', 10.00);
        // Historical event with no campaign
        $freeEvent = $this->createEvent($creator, ['title' => 'Legacy Community Meetup']);

        $this->actingAs($creator, 'member');

        // View single event
        $showRes = $this->getJson('/api/member/events/' . $freeEvent->id);
        $showRes->assertOk()
            ->assertJsonPath('event.title', 'Legacy Community Meetup');

        // Appears in all events
        $indexRes = $this->getJson('/api/member/events?tab=all');
        $indexRes->assertOk()
            ->assertJsonPath('events.data.0.title', 'Legacy Community Meetup');

        // Does NOT appear in Add Fund tab because it has no campaign
        $fundRes = $this->getJson('/api/member/events?tab=add-fund');
        $fundRes->assertOk()
            ->assertJsonCount(0, 'events.data');
    }
}
