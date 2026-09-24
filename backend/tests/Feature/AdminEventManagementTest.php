<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Event;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $organizer;
    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin->roles()->sync([$superRole->id]);

        $this->organizer = Member::create([
            'name' => 'Event Organizer',
            'user_id' => 'org_' . uniqid(),
            'email' => 'organizer_' . uniqid() . '@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'MLM Leadership Summit 2026',
            'slug' => 'mlm-leadership-summit-2026-' . uniqid(),
            'description' => 'Annual leadership convention.',
            'category' => 'business',
            'event_type' => 'in_person',
            'location_address' => '123 Grand Ballroom',
            'location_city' => 'Dallas',
            'location_country' => 'USA',
            'start_date' => now()->addDays(7)->toDateString(),
            'start_time' => '10:00:00',
            'status' => 'active',
            'privacy' => 'public',
        ]);
    }

    public function test_admin_can_list_events(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/events');

        $response->assertOk()
            ->assertJsonStructure([
                'events',
                'totalCount',
                'upcomingCount',
                'ongoingCount',
                'totalResponsesCount',
                'categories',
            ]);
    }

    public function test_admin_can_view_single_event_details(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/events/{$this->event->id}");

        $response->assertOk()
            ->assertJsonPath('event.id', $this->event->id)
            ->assertJsonPath('event.title', 'MLM Leadership Summit 2026')
            ->assertJsonPath('event.organizer.id', $this->organizer->id)
            ->assertJsonStructure([
                'event',
                'goingCount',
                'interestedCount',
                'posts',
                'postsCount',
            ]);
    }

    public function test_admin_viewing_nonexistent_event_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/events/99999999');

        $response->assertNotFound();
    }

    public function test_admin_can_toggle_event_status(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/events/{$this->event->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('events', [
            'id' => $this->event->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_delete_event(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/events/{$this->event->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('events', [
            'id' => $this->event->id,
        ]);
    }

    public function test_activating_cancelled_event_succeeds(): void
    {
        $this->event->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/events/{$this->event->id}/status", [
                'status' => 'active',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Event activated successfully.');

        $this->assertTrue($this->event->fresh()->isActive());
    }

    public function test_activating_already_active_event_returns_409_conflict(): void
    {
        // $this->event is already active from setUp()
        $this->assertTrue($this->event->isActive());

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/events/{$this->event->id}/status", [
                'status' => 'active',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'EVENT_ALREADY_ACTIVE')
            ->assertJsonPath('message', 'This event is already active.');
    }

    public function test_bulk_activate_when_all_events_are_already_active_returns_409(): void
    {
        $secondEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Second Leadership Summit',
            'slug' => 'second-summit-' . uniqid(),
            'category' => 'business',
            'event_type' => 'online',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/events/bulk-action', [
                'action' => 'activate',
                'ids' => [$this->event->id, $secondEvent->id],
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ALL_EVENTS_ALREADY_ACTIVE')
            ->assertJsonPath('message', 'All selected events are already active.');
    }

    public function test_bulk_activate_partial_activates_only_inactive_events(): void
    {
        $cancelledEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Cancelled Workshop',
            'slug' => 'cancelled-workshop-' . uniqid(),
            'category' => 'business',
            'event_type' => 'online',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'cancelled',
            'privacy' => 'public',
        ]);

        // $this->event is active, $cancelledEvent is cancelled
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/events/bulk-action', [
                'action' => 'activate',
                'ids' => [$this->event->id, $cancelledEvent->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'PARTIAL_EVENTS_ACTIVATED')
            ->assertJsonPath('activated_count', 1)
            ->assertJsonPath('already_active_count', 1)
            ->assertJsonPath('message', '1 event activated. 1 event was already active.');

        $this->assertTrue($cancelledEvent->fresh()->isActive());
        $this->assertTrue($this->event->fresh()->isActive());
    }

    public function test_bulk_activate_on_all_inactive_events_activates_all(): void
    {
        $this->event->update(['status' => 'cancelled']);
        $secondCancelled = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Second Cancelled Workshop',
            'slug' => 'second-cancelled-' . uniqid(),
            'category' => 'business',
            'event_type' => 'online',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'cancelled',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/events/bulk-action', [
                'action' => 'activate',
                'ids' => [$this->event->id, $secondCancelled->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 'EVENTS_ACTIVATED')
            ->assertJsonPath('activated_count', 2)
            ->assertJsonPath('message', '2 events activated successfully.');

        $this->assertTrue($this->event->fresh()->isActive());
        $this->assertTrue($secondCancelled->fresh()->isActive());
    }
}
