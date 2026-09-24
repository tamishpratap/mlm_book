<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\BusinessPageCategory;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPageCategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Member $member;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = Member::create([
            'name' => 'John Networker',
            'email' => 'john.' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin.' . uniqid() . '@example.com',
            'password' => 'secret123',
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin->roles()->sync([$superRole->id]);
    }

    private function validPageData(array $overrides = []): array
    {
        return array_merge([
            'page_name' => 'Apex Global Solutions',
            'category' => 'Health, Nutrition & Wellness MLM',
            'description' => 'A comprehensive business page description with more than twenty characters.',
            'email' => 'info@apexsolutions.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => 'India',
            'visibility' => 'public',
        ], $overrides);
    }

    /**
     * TEST 1 — EXISTING DEFAULT CATEGORY
     * Member selects an existing default MLM category; creation succeeds.
     */
    public function test_existing_default_category_succeeds(): void
    {
        $this->actingAs($this->member, 'member');

        $response = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Wellness Enterprise',
            'category' => 'Health, Nutrition & Wellness MLM',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page created successfully!',
            ]);

        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Wellness Enterprise',
            'category' => 'Health, Nutrition & Wellness MLM',
        ]);
    }

    /**
     * TEST 2 — ADMIN-CREATED CATEGORY ("Gaming")
     * Admin creates "Gaming".
     * Member gets create data, sees "Gaming", and successfully creates Business Page with "Gaming".
     */
    public function test_admin_created_gaming_category_succeeds(): void
    {
        // Admin creates "Gaming"
        $adminRes = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/business-pages/categories', [
                'name' => 'Gaming',
                'description' => 'Gaming guilds and esport communities',
                'order' => 1,
                'is_active' => true,
            ]);
        $adminRes->assertSuccessful();

        $this->assertDatabaseHas('business_page_categories', [
            'name' => 'Gaming',
            'slug' => 'gaming',
            'is_active' => true,
        ]);

        // Member fetches creation metadata
        $this->actingAs($this->member, 'member');
        $createDataRes = $this->getJson('/api/member/business-pages/create');
        $createDataRes->assertOk();
        $this->assertContains('Gaming', $createDataRes->json('categories'));

        // Member creates page with "Gaming"
        $response = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Epic Gaming League',
            'category' => 'Gaming',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page created successfully!',
            ]);

        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Epic Gaming League',
            'category' => 'Gaming',
        ]);
    }

    /**
     * TEST 3 — MULTIPLE ADMIN CATEGORIES
     * Multiple dynamic categories created by Admin appear in Member create endpoint and succeed.
     */
    public function test_multiple_admin_categories_appear_and_succeed(): void
    {
        $categoriesToCreate = ['Robotics & AI', 'Renewable Energy', 'Automotive MLM'];
        foreach ($categoriesToCreate as $catName) {
            BusinessPageCategory::create([
                'name' => $catName,
                'slug' => \Illuminate\Support\Str::slug($catName),
                'order' => 5,
                'is_active' => true,
            ]);
        }

        $this->actingAs($this->member, 'member');
        $res = $this->getJson('/api/member/business-pages/create');
        $res->assertOk();
        $categoriesList = $res->json('categories');

        foreach ($categoriesToCreate as $catName) {
            $this->assertContains($catName, $categoriesList);

            $createRes = $this->postJson('/api/member/business-pages', $this->validPageData([
                'page_name' => "Company for {$catName}",
                'category' => $catName,
            ]));
            $createRes->assertCreated();
            $this->assertDatabaseHas('business_pages', [
                'page_name' => "Company for {$catName}",
                'category' => $catName,
            ]);
        }
    }

    /**
     * TEST 4 — CATEGORY WITH SPACES ("Digital Marketing")
     * Validates that categories with spaces display and persist accurately.
     */
    public function test_category_with_spaces_succeeds(): void
    {
        BusinessPageCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'is_active' => true,
        ]);

        $this->actingAs($this->member, 'member');
        $response = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Apex Digital Marketing',
            'category' => 'Digital Marketing',
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Apex Digital Marketing',
            'category' => 'Digital Marketing',
        ]);
    }

    /**
     * TEST 5 — CASE SENSITIVITY AND CANONICAL NORMALIZATION
     * Submitting category with case variation or surrounding whitespace resolves to canonical name.
     */
    public function test_case_and_whitespace_normalization_resolves_to_canonical_name(): void
    {
        BusinessPageCategory::create([
            'name' => 'FinTech & Banking',
            'slug' => 'fintech-banking',
            'is_active' => true,
        ]);

        $this->actingAs($this->member, 'member');

        // Submit with lowercase and trailing whitespace
        $response = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'NextGen Payments',
            'category' => '  fintech & banking  ',
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'NextGen Payments',
            'category' => 'FinTech & Banking', // Stored in canonical casing
        ]);
    }

    /**
     * TEST 6 — INVALID CATEGORY VIA DIRECT API
     * Submitting a nonexistent fake category is rejected by backend with 422.
     */
    public function test_invalid_category_direct_api_is_rejected(): void
    {
        $this->actingAs($this->member, 'member');

        $response = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Fake Category Corp',
            'category' => 'Totally Fake Category That Does Not Exist',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);

        $this->assertEquals(
            'The selected category is invalid.',
            $response->json('errors.category.0')
        );

        $this->assertDatabaseMissing('business_pages', [
            'page_name' => 'Fake Category Corp',
        ]);
    }

    /**
     * TEST 7 — INACTIVE CATEGORY CANNOT BE USED
     * When an Admin deactivates a category, it cannot be used for new Business Pages.
     */
    public function test_inactive_category_cannot_be_used(): void
    {
        $category = BusinessPageCategory::create([
            'name' => 'Legacy Discontinued Plan',
            'slug' => 'legacy-discontinued-plan',
            'is_active' => false,
        ]);

        $this->actingAs($this->member, 'member');

        // Verify inactive category is NOT in the create categories list
        $createDataRes = $this->getJson('/api/member/business-pages/create');
        $this->assertNotContains('Legacy Discontinued Plan', $createDataRes->json('categories'));

        // Attempting to submit inactive category directly
        $response = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Attempted Legacy Page',
            'category' => 'Legacy Discontinued Plan',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category']);

        $this->assertDatabaseMissing('business_pages', [
            'page_name' => 'Attempted Legacy Page',
        ]);
    }

    /**
     * TEST 8 — MEMBER CATEGORY REFRESH
     * Creating a category in Admin immediately makes it retrievable by Member.
     */
    public function test_new_category_immediately_retrievable_by_member(): void
    {
        $this->actingAs($this->member, 'member');
        $resBefore = $this->getJson('/api/member/business-pages/create');
        $this->assertNotContains('Solar & Cleantech', $resBefore->json('categories'));

        // Admin creates category
        $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/business-pages/categories', [
                'name' => 'Solar & Cleantech',
                'is_active' => true,
            ])
            ->assertSuccessful();

        // Member requests create metadata again
        $this->actingAs($this->member, 'member');
        $resAfter = $this->getJson('/api/member/business-pages/create');
        $this->assertContains('Solar & Cleantech', $resAfter->json('categories'));
    }

    /**
     * TEST 9 — EXISTING BUSINESS PAGE VIEW AND EDIT COMPATIBILITY
     * An existing Business Page retains its category display and can be edited.
     */
    public function test_existing_business_page_retains_category_and_edit_succeeds(): void
    {
        $page = BusinessPage::create([
            'member_id' => $this->member->id,
            'page_name' => 'Existing Herbal Page',
            'page_username' => 'existing_herbal',
            'slug' => 'existing-herbal-page',
            'category' => 'Health, Nutrition & Wellness MLM',
            'description' => 'Original description for existing business page with sufficient length.',
            'email' => 'herbal@example.com',
            'phone' => '+919876543210',
            'country' => 'India',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $this->actingAs($this->member, 'member');

        // View edit metadata
        $editRes = $this->getJson("/api/member/business-pages/{$page->slug}/edit");
        $editRes->assertOk();
        $this->assertEquals('Health, Nutrition & Wellness MLM', $editRes->json('business_page.category'));
        $this->assertContains('Health, Nutrition & Wellness MLM', $editRes->json('categories'));

        // Update page category to an admin-created category
        $newCat = BusinessPageCategory::create([
            'name' => 'Biotech Innovations',
            'slug' => 'biotech-innovations',
            'is_active' => true,
        ]);

        $updateRes = $this->putJson("/api/member/business-pages/{$page->slug}", [
            'page_name' => 'Existing Herbal Page Updated',
            'page_username' => 'existing_herbal',
            'category' => 'Biotech Innovations',
            'description' => 'Updated description for existing business page with sufficient length.',
            'email' => 'herbal@example.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => 'India',
            'visibility' => 'public',
        ]);

        $updateRes->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('business_pages', [
            'id' => $page->id,
            'category' => 'Biotech Innovations',
        ]);
    }

    /**
     * TEST 10 — SLUG OR ID ACCEPTANCE IN MEMBER CONTROLLER
     * When category slug or ID is submitted, it resolves to canonical name.
     */
    public function test_category_slug_or_id_resolves_to_canonical_name(): void
    {
        $cat = BusinessPageCategory::create([
            'name' => 'Aerospace & Aviation',
            'slug' => 'aerospace-aviation',
            'is_active' => true,
        ]);

        $this->actingAs($this->member, 'member');

        // Submit using slug
        $slugRes = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Sky High Aero',
            'category' => 'aerospace-aviation',
        ]));
        $slugRes->assertCreated();
        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Sky High Aero',
            'category' => 'Aerospace & Aviation',
        ]);

        // Submit using category_id parameter
        $idRes = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Orbital Dynamics',
            'category' => null,
            'category_id' => $cat->id,
        ]));
        $idRes->assertCreated();
        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Orbital Dynamics',
            'category' => 'Aerospace & Aviation',
        ]);
    }

    /**
     * TEST 11 — INVALID FORM REJECTS AND STORES NO BUSINESS PAGE
     */
    public function test_invalid_form_with_invalid_category_creates_no_record(): void
    {
        $this->actingAs($this->member, 'member');

        $res = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Unauthorized Category Attempt',
            'category' => 'Invalid Nonexistent Category 999',
        ]));

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['category']);

        $this->assertDatabaseMissing('business_pages', [
            'page_name' => 'Unauthorized Category Attempt',
        ]);
    }

    /**
     * TEST 12 — DUPLICATE SUBMIT PROTECTION
     * Unique username or slug constraint prevents duplicate page creation.
     */
    public function test_duplicate_submission_is_prevented(): void
    {
        $this->actingAs($this->member, 'member');

        $firstRes = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Unique Brand Enterprise',
            'page_username' => 'unique_brand',
            'category' => 'Health, Nutrition & Wellness MLM',
        ]));
        $firstRes->assertCreated();

        // Second submission with same unique username
        $secondRes = $this->postJson('/api/member/business-pages', $this->validPageData([
            'page_name' => 'Unique Brand Enterprise',
            'page_username' => 'unique_brand',
            'category' => 'Health, Nutrition & Wellness MLM',
        ]));
        $secondRes->assertStatus(422)
            ->assertJsonValidationErrors(['page_username']);

        $this->assertEquals(1, BusinessPage::where('page_username', 'unique_brand')->count());
    }
}
