<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminBroadcastMarketplaceSellersTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    public function test_admin_broadcast_form_is_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.notifications.broadcast'));

        $response->assertOk()
            ->assertSee('Broadcast Announcement Form')
            ->assertSee('Marketplace Sellers');
    }

    public function test_broadcast_to_marketplace_sellers_succeeds_when_zero_sellers_exist(): void
    {
        // Member with no products
        Member::create([
            'name' => 'Regular Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'sellers',
                'title' => 'Important Seller Update',
                'message' => 'Marketplace fee reduction starting next week.',
            ]);

        $response->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Broadcast announcement sent to 0 members successfully.');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_broadcast_to_marketplace_sellers_dispatches_to_sellers_only(): void
    {
        $seller = Member::create([
            'name' => 'Active Seller',
            'email' => 'seller@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $nonSeller = Member::create([
            'name' => 'Regular Member',
            'email' => 'regular@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        Product::create([
            'member_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Smartphone XYZ',
            'slug' => 'smartphone-xyz',
            'description' => 'Great condition',
            'price' => 299.99,
            'condition' => 'like_new',
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'sellers',
                'title' => 'Seller Exclusive Notice',
                'message' => 'Your products are trending!',
            ]);

        $response->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Broadcast announcement sent to 1 members successfully.');

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\\Models\\Member',
            'notifiable_id' => $seller->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => 'App\\Models\\Member',
            'notifiable_id' => $nonSeller->id,
        ]);
    }

    public function test_broadcast_to_marketplace_sellers_does_not_duplicate_notifications_for_seller_with_multiple_products(): void
    {
        $seller = Member::create([
            'name' => 'Power Seller',
            'email' => 'power_seller@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Appliances',
            'slug' => 'appliances',
        ]);

        // Seller has 3 products
        for ($i = 1; $i <= 3; $i++) {
            Product::create([
                'member_id' => $seller->id,
                'category_id' => $category->id,
                'title' => "Product {$i}",
                'slug' => "product-{$i}",
                'description' => "Description {$i}",
                'price' => 100 * $i,
                'condition' => 'brand_new',
                'status' => 'available',
            ]);
        }

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'sellers',
                'title' => 'Multi-Product Seller Announcement',
                'message' => 'Discount promotion for all listings.',
            ]);

        $response->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Broadcast announcement sent to 1 members successfully.');

        // Verify exactly 1 notification is stored for this seller
        $notificationCount = DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\Member')
            ->where('notifiable_id', $seller->id)
            ->count();

        $this->assertSame(1, $notificationCount);
    }

    public function test_other_broadcast_audiences_continue_to_function(): void
    {
        $activeMember = Member::create([
            'name' => 'Active Member',
            'email' => 'active@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $businessOwner = Member::create([
            'name' => 'Business Owner',
            'email' => 'bizowner@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        BusinessPage::create([
            'member_id' => $businessOwner->id,
            'page_name' => 'Tech Solutions Corp',
            'page_username' => 'techsolutions',
            'slug' => 'tech-solutions-corp',
            'category' => 'Binary MLM Plan',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        // Test All Audience
        $responseAll = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'General Notice',
                'message' => 'Platform wide update.',
            ]);
        $responseAll->assertRedirect(route('admin.notifications.index'));

        // Test Business Owners Audience
        $responseBiz = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'business_owners',
                'title' => 'Business Owners Notice',
                'message' => 'New business tools are available.',
            ]);
        $responseBiz->assertRedirect(route('admin.notifications.index'));
    }
}
