<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminNotificationAutoReadTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $email = 'admin_test@example.com'): Admin
    {
        return Admin::create([
            'name' => 'Admin Tester',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
    }

    private function createNotificationForAdmin(Admin $admin, bool $isRead = false, string $title = 'System Alert'): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\SystemNotification',
            'notifiable_type' => Admin::class,
            'notifiable_id' => $admin->id,
            'data' => json_encode([
                'title' => $title,
                'message' => 'Details for ' . $title,
                'icon' => 'bell',
            ]),
            'read_at' => $isRead ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_admin_mark_all_read_marks_notifications_and_returns_correct_json(): void
    {
        $admin = $this->createAdmin('admin1@example.com');
        $this->createNotificationForAdmin($admin, false, 'Notification 1');
        $this->createNotificationForAdmin($admin, false, 'Notification 2');
        $this->createNotificationForAdmin($admin, false, 'Notification 3');

        $this->assertEquals(3, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notifications marked as read',
                'unread_count' => 0,
            ]);

        $this->assertEquals(0, $admin->fresh()->unreadNotifications()->count());
        $this->assertEquals(3, $admin->notifications()->whereNotNull('read_at')->count());
    }

    public function test_admin_mark_all_read_when_already_read_is_idempotent(): void
    {
        $admin = $this->createAdmin('admin_idempotent@example.com');
        $this->createNotificationForAdmin($admin, true, 'Already read notification');

        $this->assertEquals(0, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No unread notifications',
                'unread_count' => 0,
            ]);
    }

    public function test_multi_admin_isolation_marking_read_does_not_affect_other_admins(): void
    {
        $adminA = $this->createAdmin('adminA@example.com');
        $adminB = $this->createAdmin('adminB@example.com');

        $this->createNotificationForAdmin($adminA, false, 'Admin A unread 1');
        $this->createNotificationForAdmin($adminA, false, 'Admin A unread 2');

        $this->createNotificationForAdmin($adminB, false, 'Admin B unread 1');
        $this->createNotificationForAdmin($adminB, false, 'Admin B unread 2');

        $this->assertEquals(2, $adminA->unreadNotifications()->count());
        $this->assertEquals(2, $adminB->unreadNotifications()->count());

        // Admin A marks all as read
        $response = $this->actingAs($adminA, 'admin')
            ->postJson('/api/admin/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notifications marked as read',
                'unread_count' => 0,
            ]);

        // Admin A now has 0 unread
        $this->assertEquals(0, $adminA->fresh()->unreadNotifications()->count());

        // Admin B STILL has 2 unread (isolation strictly maintained)
        $this->assertEquals(2, $adminB->fresh()->unreadNotifications()->count());
    }

    public function test_admin_unread_count_endpoint(): void
    {
        $admin = $this->createAdmin('admin_count@example.com');
        $this->createNotificationForAdmin($admin, false, 'Count unread 1');
        $this->createNotificationForAdmin($admin, false, 'Count unread 2');

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
                'unread_count' => 2,
            ]);
    }

    public function test_admin_dropdown_endpoint_returns_admin_notifications(): void
    {
        $admin = $this->createAdmin('admin_dropdown@example.com');
        $this->createNotificationForAdmin($admin, false, 'Dropdown unread');
        $this->createNotificationForAdmin($admin, true, 'Dropdown read');

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/notifications/dropdown');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 1,
                'count' => 1,
            ]);

        $data = $response->json('notifications');
        $this->assertCount(2, $data);
        $this->assertEquals('Dropdown unread', $data[0]['title']);
        $this->assertFalse($data[0]['read']);
        $this->assertEquals('Dropdown read', $data[1]['title']);
        $this->assertTrue($data[1]['read']);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/admin/notifications/mark-all-read');
        $response->assertStatus(401);
    }
}
