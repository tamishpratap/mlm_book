<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventOutreachAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_member_cannot_create_event(): void
    {
        $unverified = Member::create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => 'password123',
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($unverified, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Test Event',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(2)->toDateString(),
            ])
            ->assertStatus(403)
            ->assertJsonPath('needs_verification', true);
    }

    public function test_unverified_member_cannot_respond_to_event(): void
    {
        $host = Member::create([
            'name' => 'Host User',
            'email' => 'host@example.com',
            'password' => 'password123',
            'phone' => '+15551112233',
            'mobile_verified_at' => now(),
        ]);

        $event = Event::create([
            'organizer_id' => $host->id,
            'title' => 'Sample Summit',
            'slug' => 'sample-summit',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'status' => 'published',
        ]);

        $unverified = Member::create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => 'password123',
            'mobile_verified_at' => null,
        ]);

        $this->actingAs($unverified, 'member')
            ->postJson(route('member.events.respond', $event), [
                'response' => 'going',
            ])
            ->assertStatus(403)
            ->assertJsonPath('needs_verification', true);
    }

    public function test_verified_member_can_create_and_respond_to_event(): void
    {
        $host = Member::create([
            'name' => 'Verified Host',
            'email' => 'vhost@example.com',
            'password' => 'password123',
            'phone' => '+15554445566',
            'mobile_verified_at' => now(),
        ]);

        $createRes = $this->actingAs($host, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Verified Event',
                'category' => 'Technology',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(3)->toDateString(),
                'location_city' => 'New York',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $eventId = $createRes->json('event.id');
        $event = Event::find($eventId);

        $attendee = Member::create([
            'name' => 'Verified Attendee',
            'email' => 'vattendee@example.com',
            'password' => 'password123',
            'phone' => '+15557778899',
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($attendee, 'member')
            ->postJson(route('member.events.respond', $event), [
                'response' => 'going',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('event_responses', [
            'event_id' => $event->id,
            'member_id' => $attendee->id,
            'response' => 'going',
        ]);
    }

    public function test_host_outreach_center_returns_attendee_contact_info_for_organizer_only(): void
    {
        $host = Member::create([
            'name' => 'Organizing Host',
            'email' => 'org@example.com',
            'password' => 'password123',
            'phone' => '+15559990011',
            'mobile_verified_at' => now(),
        ]);

        $event = Event::create([
            'organizer_id' => $host->id,
            'title' => 'Grand Summit',
            'slug' => 'grand-summit',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'status' => 'published',
        ]);

        $interestedLead = Member::create([
            'name' => 'Lead Person',
            'email' => 'lead@example.com',
            'password' => 'password123',
            'phone' => '+19876543210',
            'city' => 'Chicago',
            'mobile_verified_at' => now(),
        ]);

        EventResponse::create([
            'event_id' => $event->id,
            'member_id' => $interestedLead->id,
            'response' => 'interested',
        ]);

        // Non-host forbidden
        $stranger = Member::create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($stranger, 'member')
            ->getJson("/api/member/events/{$event->id}/outreach")
            ->assertStatus(403);

        // Host allowed and receives contact details including phone
        $response = $this->actingAs($host, 'member')
            ->getJson("/api/member/events/{$event->id}/outreach")
            ->assertOk()
            ->assertJsonPath('success', true);

        $response->assertJsonStructure([
            'event',
            'stats' => ['total_attendees', 'going_count', 'interested_count', 'with_phone_count', 'with_email_count', 'verified_members_count'],
            'going_members',
            'interested_members',
            'all_members',
        ]);

        $this->assertSame(1, $response->json('stats.interested_count'));
        $this->assertSame('+19876543210', $response->json('interested_members.0.phone'));
    }
}
