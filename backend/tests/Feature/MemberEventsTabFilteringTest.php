<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberEventsTabFilteringTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'phone' => '+9198' . random_int(10000000, 99999999),
            'password' => 'secret123',
            'mobile_verified_at' => now(),
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
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00',
        ], $attributes));
    }

    public function test_events_api_defaults_to_all_tab(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        $this->createEvent($memberB, ['title' => 'Public Tech Event']);

        $this->actingAs($memberA, 'member');
        $response = $this->getJson('/api/member/events');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tab', 'all')
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Public Tech Event');
    }

    public function test_all_tab_does_not_return_my_events_list_as_primary_dataset(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        // Member A's own event
        $this->createEvent($memberA, ['title' => 'My Secret Workshop', 'privacy' => 'private']);
        // Member B's public event
        $this->createEvent($memberB, ['title' => 'Public Tech Event', 'privacy' => 'public']);

        $this->actingAs($memberA, 'member');

        // All/Discover tab only shows discoverable public events
        $response = $this->getJson('/api/member/events?tab=all');

        $response->assertOk()
            ->assertJsonPath('tab', 'all')
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Public Tech Event')
            ->assertJsonPath('my_events_count', 1); // Badge count is available
    }

    public function test_my_events_tab_returns_only_authenticated_user_events_and_rsvps(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        // Member A's created event
        $myEvent = $this->createEvent($memberA, ['title' => 'My Organized Event']);
        // Member B's event where Member A RSVP'd 'going'
        $rsvpdEvent = $this->createEvent($memberB, ['title' => 'B Event I Am Going To']);
        EventResponse::create([
            'event_id' => $rsvpdEvent->id,
            'member_id' => $memberA->id,
            'response' => 'going',
        ]);

        // Member B's other event where Member A has NOT RSVP'd
        $otherEvent = $this->createEvent($memberB, ['title' => 'Unrelated B Event']);

        $this->actingAs($memberA, 'member');

        $response = $this->getJson('/api/member/events?tab=my');

        $response->assertOk()
            ->assertJsonPath('tab', 'my')
            ->assertJsonPath('my_events_count', 2)
            ->assertJsonCount(2, 'events.data');

        $titles = collect($response->json('events.data'))->pluck('title')->all();
        $this->assertContains('My Organized Event', $titles);
        $this->assertContains('B Event I Am Going To', $titles);
        $this->assertNotContains('Unrelated B Event', $titles);
    }

    public function test_filters_apply_to_active_tab(): void
    {
        $memberA = $this->createMember('Member A');

        // Create 2 events for Member A: one online, one offline
        $this->createEvent($memberA, [
            'title' => 'Online Coding Workshop',
            'event_type' => 'online',
            'category' => 'Technology',
        ]);
        $this->createEvent($memberA, [
            'title' => 'In-Person Networking Meetup',
            'event_type' => 'offline',
            'category' => 'Business',
        ]);

        $this->actingAs($memberA, 'member');

        // Filter My Events by online
        $responseOnline = $this->getJson('/api/member/events?tab=my&type=online');
        $responseOnline->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Online Coding Workshop');

        // Filter My Events by offline
        $responseOffline = $this->getJson('/api/member/events?tab=my&type=offline');
        $responseOffline->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'In-Person Networking Meetup');

        // Filter My Events by search query
        $responseSearch = $this->getJson('/api/member/events?tab=my&search=Networking');
        $responseSearch->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'In-Person Networking Meetup');
    }

    public function test_empty_state_when_user_has_no_events(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        // B has public events, but A has none
        $this->createEvent($memberB, ['title' => 'B Event']);

        $this->actingAs($memberA, 'member');

        $response = $this->getJson('/api/member/events?tab=my');
        $response->assertOk()
            ->assertJsonPath('tab', 'my')
            ->assertJsonPath('my_events_count', 0)
            ->assertJsonCount(0, 'events.data');
    }

    public function test_blade_view_renders_tab_navigation_and_single_active_section(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        $this->createEvent($memberA, ['title' => 'Member A Private Gathering', 'privacy' => 'private']);
        $this->createEvent($memberB, ['title' => 'Member B Public Summit', 'privacy' => 'public']);

        $this->actingAs($memberA, 'member');

        // Request All Events tab
        $responseAll = $this->get('/member/events?tab=all');
        $responseAll->assertOk()
            ->assertSee('events-nav-tabs', false)
            ->assertSee('Discover Upcoming Events')
            ->assertDontSee('My Events &amp; RSVPs (', false)
            ->assertSee('Member B Public Summit')
            ->assertDontSee('Member A Private Gathering');

        // Request My Events tab
        $responseMy = $this->get('/member/events?tab=my');
        $responseMy->assertOk()
            ->assertSee('events-nav-tabs', false)
            ->assertSee('My Events & RSVPs (', false)
            ->assertDontSee('Discover Upcoming Events (')
            ->assertSee('Member A Private Gathering')
            ->assertDontSee('Member B Public Summit');
    }

    public function test_unauthenticated_user_cannot_access_events_api(): void
    {
        $response = $this->getJson('/api/member/events');
        $response->assertUnauthorized();
    }

    public function test_rsvp_action_adds_event_to_my_events(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        $event = $this->createEvent($memberB, ['title' => 'Community Tech Conference']);

        $this->actingAs($memberA, 'member');

        // Initially Member A has 0 events in my_events
        $before = $this->getJson('/api/member/events?tab=my');
        $before->assertOk()
            ->assertJsonPath('my_events_count', 0)
            ->assertJsonCount(0, 'events.data');

        // RSVP 'interested'
        $rsvpResponse = $this->postJson("/api/member/events/{$event->id}/respond", [
            'response' => 'interested',
        ]);
        $rsvpResponse->assertOk()
            ->assertJsonPath('success', true);

        // Now Member A has 1 event in my_events
        $after = $this->getJson('/api/member/events?tab=my');
        $after->assertOk()
            ->assertJsonPath('my_events_count', 1)
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Community Tech Conference');
    }

    public function test_discover_tab_filters_by_category_and_date(): void
    {
        $memberA = $this->createMember('Member A');
        $memberB = $this->createMember('Member B');

        $techToday = $this->createEvent($memberB, [
            'title' => 'Today Tech Meetup',
            'category' => 'Technology',
            'start_date' => now()->toDateString(),
        ]);
        $musicTomorrow = $this->createEvent($memberB, [
            'title' => 'Tomorrow Music Jam',
            'category' => 'Music',
            'start_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($memberA, 'member');

        // Filter by category Technology
        $resCat = $this->getJson('/api/member/events?tab=all&category=Technology');
        $resCat->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Today Tech Meetup');

        // Filter by timeframe tomorrow
        $resTime = $this->getJson('/api/member/events?tab=all&timeframe=tomorrow');
        $resTime->assertOk()
            ->assertJsonCount(1, 'events.data')
            ->assertJsonPath('events.data.0.title', 'Tomorrow Music Jam');
    }
}

