<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\BusinessPageCategory;
use App\Models\Event;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryUnificationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin->roles()->sync([$superRole->id]);

        $this->member = Member::create([
            'name' => 'Community Member',
            'user_id' => 'mem_' . uniqid(),
            'email' => 'member_' . uniqid() . '@example.com',
            'password' => 'password123',
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);
    }

    private function validEventData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'MLM Leadership Summit 2026',
            'category' => 'Technology & Software',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00',
            'meeting_link' => 'https://zoom.us/j/123456789',
        ], $overrides);
    }

    public function test_admin_created_category_is_available_in_business_page_and_event_create_endpoints(): void
    {
        // Admin creates a new category
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/business-pages/categories', [
                'name' => 'Crypto & Web3 MLM',
                'description' => 'Decentralized networking and crypto projects',
                'order' => 1,
                'is_active' => true,
            ]);
        $response->assertSuccessful();

        $this->assertDatabaseHas('business_page_categories', [
            'name' => 'Crypto & Web3 MLM',
            'is_active' => true,
        ]);

        // Verify it appears in active category names
        $activeNames = BusinessPageCategory::getActiveCategoryNames();
        $this->assertContains('Crypto & Web3 MLM', $activeNames);

        // Verify it appears in Event create endpoint
        $eventCreateRes = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/events/create');
        $eventCreateRes->assertOk();
        $this->assertContains('Crypto & Web3 MLM', $eventCreateRes->json('categories'));

        // Verify BusinessPage::categories() delegates to active category names
        $this->assertContains('Crypto & Web3 MLM', BusinessPage::categories());
    }

    public function test_admin_deactivated_category_is_excluded_from_event_and_business_page_creation(): void
    {
        BusinessPageCategory::create([
            'name' => 'Active Networking Category',
            'slug' => 'active-networking-category',
            'order' => 1,
            'is_active' => true,
        ]);

        $cat = BusinessPageCategory::create([
            'name' => 'Deactivated MLM Group',
            'slug' => 'deactivated-mlm-group',
            'order' => 2,
            'is_active' => false,
        ]);

        $activeNames = BusinessPageCategory::getActiveCategoryNames();
        $this->assertNotContains('Deactivated MLM Group', $activeNames);

        // Verify Event create endpoint excludes it
        $eventCreateRes = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/events/create');
        $eventCreateRes->assertOk();
        $this->assertNotContains('Deactivated MLM Group', $eventCreateRes->json('categories'));

        // Attempting to create an event with deactivated category fails validation
        $createRes = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/events', $this->validEventData([
                'category' => 'Deactivated MLM Group',
            ]));

        $createRes->assertStatus(422);
        $createRes->assertJsonValidationErrors(['category']);
    }

    public function test_member_can_create_event_with_active_admin_category(): void
    {
        BusinessPageCategory::create([
            'name' => 'Health & Wellness MLM',
            'slug' => 'health-wellness-mlm',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/events', $this->validEventData([
                'title' => 'Annual Wellness Gala',
                'category' => 'Health & Wellness MLM',
            ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'title' => 'Annual Wellness Gala',
            'category' => 'Health & Wellness MLM',
            'organizer_id' => $this->member->id,
        ]);
    }

    public function test_member_cannot_create_event_with_invalid_category(): void
    {
        BusinessPageCategory::create([
            'name' => 'Official Category',
            'slug' => 'official-category',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/events', $this->validEventData([
                'category' => 'Completely Bogus Category Not In Admin',
            ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);
    }

    public function test_category_rename_in_admin_cascades_to_both_business_pages_and_events(): void
    {
        $cat = BusinessPageCategory::create([
            'name' => 'Original Category Name',
            'slug' => 'original-category-name',
            'order' => 1,
            'is_active' => true,
        ]);

        $page = BusinessPage::create([
            'member_id' => $this->member->id,
            'page_name' => 'Apex Enterprises',
            'page_username' => 'apexenterprises',
            'slug' => 'apex-enterprises',
            'category' => 'Original Category Name',
            'is_active' => true,
            'status' => 'active',
        ]);

        $event = Event::create([
            'organizer_id' => $this->member->id,
            'title' => 'Apex Launch Conference',
            'slug' => 'apex-launch-conference-' . uniqid(),
            'description' => 'Launch event description.',
            'category' => 'Original Category Name',
            'event_type' => 'online',
            'start_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00',
            'status' => 'active',
            'privacy' => 'public',
        ]);

        // Admin renames the category
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/business-pages/categories/{$cat->id}", [
                'name' => 'Renamed Authority Category',
                'order' => 1,
                'is_active' => true,
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('business_page_categories', [
            'id' => $cat->id,
            'name' => 'Renamed Authority Category',
        ]);

        // Verify Business Page category cascaded
        $this->assertDatabaseHas('business_pages', [
            'id' => $page->id,
            'category' => 'Renamed Authority Category',
        ]);

        // Verify Event category cascaded
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'category' => 'Renamed Authority Category',
        ]);
    }

    public function test_admin_cannot_delete_category_when_used_by_events(): void
    {
        $cat = BusinessPageCategory::create([
            'name' => 'Event Bound Category',
            'slug' => 'event-bound-category',
            'order' => 1,
            'is_active' => true,
        ]);

        Event::create([
            'organizer_id' => $this->member->id,
            'title' => 'Exclusive Member Gathering',
            'slug' => 'exclusive-member-gathering-' . uniqid(),
            'description' => 'Gathering details.',
            'category' => 'Event Bound Category',
            'event_type' => 'online',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '11:00',
            'status' => 'active',
            'privacy' => 'public',
        ]);

        // Admin attempts deletion
        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/business-pages/categories/{$cat->id}");

        $response->assertStatus(422);
        $this->assertStringContainsString('event', strtolower($response->json('message')));

        $this->assertDatabaseHas('business_page_categories', [
            'id' => $cat->id,
        ]);
    }

    public function test_admin_reassign_migrates_both_business_pages_and_events(): void
    {
        $sourceCat = BusinessPageCategory::create([
            'name' => 'Source Category',
            'slug' => 'source-category',
            'order' => 1,
            'is_active' => true,
        ]);

        $targetCat = BusinessPageCategory::create([
            'name' => 'Target Category',
            'slug' => 'target-category',
            'order' => 2,
            'is_active' => true,
        ]);

        $page = BusinessPage::create([
            'member_id' => $this->member->id,
            'page_name' => 'Reassign Test Page',
            'page_username' => 'reassigntestpage',
            'slug' => 'reassign-test-page',
            'category' => 'Source Category',
            'is_active' => true,
            'status' => 'active',
        ]);

        $event = Event::create([
            'organizer_id' => $this->member->id,
            'title' => 'Reassign Test Event',
            'slug' => 'reassign-test-event-' . uniqid(),
            'description' => 'Reassign event description.',
            'category' => 'Source Category',
            'event_type' => 'online',
            'start_date' => now()->addDays(3)->toDateString(),
            'start_time' => '12:00',
            'status' => 'active',
            'privacy' => 'public',
        ]);

        // Admin reassigns Source to Target
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/business-pages/categories/{$sourceCat->id}/reassign", [
                'target_category_id' => $targetCat->id,
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('business_pages', [
            'id' => $page->id,
            'category' => 'Target Category',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'category' => 'Target Category',
        ]);
    }

    public function test_existing_event_with_legacy_category_can_be_edited_without_validation_error(): void
    {
        // Category 'Legacy Business' is NOT in business_page_categories
        $event = Event::create([
            'organizer_id' => $this->member->id,
            'title' => 'Legacy Category Event',
            'slug' => 'legacy-category-event-' . uniqid(),
            'description' => 'Legacy category event description.',
            'category' => 'Legacy Business',
            'event_type' => 'online',
            'start_date' => now()->addDays(4)->toDateString(),
            'start_time' => '15:00',
            'status' => 'active',
            'privacy' => 'public',
        ]);

        // Member visits edit page
        $editDataRes = $this->actingAs($this->member, 'member')
            ->getJson("/api/member/events/{$event->id}/edit");

        $editDataRes->assertOk();
        $this->assertContains('Legacy Business', $editDataRes->json('categories'));

        // Member updates event without changing legacy category
        $updateRes = $this->actingAs($this->member, 'member')
            ->putJson("/api/member/events/{$event->id}", [
                'title' => 'Updated Legacy Category Event Title',
                'category' => 'Legacy Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(4)->toDateString(),
                'start_time' => '15:00',
            ]);

        $updateRes->assertSuccessful();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Legacy Category Event Title',
            'category' => 'Legacy Business',
        ]);
    }

    public function test_business_page_categories_constant_is_deprecated_and_categories_method_delegates(): void
    {
        $this->assertEmpty(BusinessPage::CATEGORIES);

        BusinessPageCategory::create([
            'name' => 'Delegation Category Check',
            'slug' => 'delegation-category-check',
            'order' => 1,
            'is_active' => true,
        ]);

        $this->assertContains('Delegation Category Check', BusinessPage::categories());
    }
}
