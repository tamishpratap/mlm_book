<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\BusinessPageCategory;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBusinessPageCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

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
    }

    public function test_admin_can_list_categories(): void
    {
        BusinessPageCategory::create([
            'name' => 'Finance & FinTech',
            'slug' => 'finance-fintech',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/business-pages/categories');

        $response->assertOk();
        $response->assertJsonStructure([
            'categories' => ['data'],
            'categoryPageCounts',
            'totalCategories',
            'activeCategories',
            'inactiveCategories',
            'totalPagesLinked',
        ]);
        $this->assertCount(1, $response->json('categories.data'));
        $this->assertEquals('Finance & FinTech', $response->json('categories.data.0.name'));
    }

    public function test_admin_can_create_category(): void
    {
        $payload = [
            'name' => 'Health & Wellness',
            'description' => 'Nutritional and wellness products',
            'order' => 2,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/business-pages/categories', $payload);

        $response->assertSuccessful();
        $this->assertDatabaseHas('business_page_categories', [
            'name' => 'Health & Wellness',
            'slug' => 'health-wellness',
            'order' => 2,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_duplicate_category(): void
    {
        BusinessPageCategory::create([
            'name' => 'Education & Courses',
            'slug' => 'education-courses',
            'order' => 1,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'education & courses', // case-insensitive duplicate
            'order' => 2,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/business-pages/categories', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = BusinessPageCategory::create([
            'name' => 'Original Name',
            'slug' => 'original-name',
            'order' => 1,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'order' => 5,
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/business-pages/categories/{$category->id}", $payload);

        $response->assertSuccessful();
        $this->assertDatabaseHas('business_page_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
            'order' => 5,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $category = BusinessPageCategory::create([
            'name' => 'Active Category',
            'slug' => 'active-category',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/business-pages/categories/{$category->id}/toggle-status");

        $response->assertSuccessful();
        $this->assertFalse($category->fresh()->is_active);

        // Toggle back to active
        $response2 = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/business-pages/categories/{$category->id}/toggle-status");

        $response2->assertSuccessful();
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_admin_can_delete_unused_category(): void
    {
        $category = BusinessPageCategory::create([
            'name' => 'Temporary Category',
            'slug' => 'temporary-category',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/business-pages/categories/{$category->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('business_page_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_admin_cannot_delete_category_with_linked_business_pages(): void
    {
        $category = BusinessPageCategory::create([
            'name' => 'Used Category',
            'slug' => 'used-category',
            'order' => 1,
            'is_active' => true,
        ]);

        $member = Member::create([
            'name' => 'Merchant Member',
            'user_id' => 'user_' . uniqid(),
            'email' => 'merchant_' . uniqid() . '@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'My Business Page',
            'page_username' => 'mybusinesspage',
            'slug' => 'my-business-page',
            'category' => 'Used Category',
            'is_active' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/business-pages/categories/{$category->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('business_page_categories', [
            'id' => $category->id,
        ]);
    }
}
