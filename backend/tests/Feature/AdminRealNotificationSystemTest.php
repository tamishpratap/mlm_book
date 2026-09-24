<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRealNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $email = 'admin_tester@example.com', string $status = 'active'): Admin
    {
        return Admin::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => $status,
        ]);
    }

    private function createMember(string $email = 'member_tester@example.com'): Member
    {
        return Member::create([
            'name' => 'John Doe',
            'user_id' => 'ABCD123456',
            'email' => $email,
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
            'phone' => '+1234567890',
        ]);
    }

    public function test_admin_notification_service_creates_real_database_records_for_active_admins(): void
    {
        $admin1 = $this->createAdmin('admin1@example.com', 'active');
        $admin2 = $this->createAdmin('admin2@example.com', 'active');
        $inactiveAdmin = $this->createAdmin('inactive@example.com', 'inactive');

        $count = AdminNotificationService::notify(
            title: 'New Member Verification',
            message: 'John Doe submitted identity documents.',
            icon: 'shield',
            sourceType: 'member_verification',
            sourceId: '10',
            actionUrl: '/admin/members/10',
            metadata: ['member_id' => 10]
        );

        $this->assertEquals(2, $count);

        // Check database records
        $admin1Notes = $admin1->notifications()->get();
        $this->assertCount(1, $admin1Notes);
        $note = $admin1Notes->first();
        $this->assertEquals('App\Notifications\AdminAlertNotification', $note->type);
        $this->assertEquals('New Member Verification', $note->data['title']);
        $this->assertEquals('/admin/members/10', $note->data['action_url']);
        $this->assertEquals('shield', $note->data['icon']);
        $this->assertEquals('member_verification', $note->data['source_type']);

        $admin2Notes = $admin2->notifications()->get();
        $this->assertCount(1, $admin2Notes);

        // Inactive admin should not receive notification when active admins exist
        $inactiveNotes = $inactiveAdmin->notifications()->get();
        $this->assertCount(0, $inactiveNotes);
    }

    public function test_post_report_triggers_real_admin_notification(): void
    {
        $admin = $this->createAdmin('admin_post_report@example.com');
        $author = $this->createMember('author@example.com');
        $reporter = Member::create([
            'name' => 'Reporter Jane',
            'user_id' => 'WXYZ654321',
            'email' => 'reporter@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
            'phone' => '+1987654321',
        ]);

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'This is a test post that will be reported.',
            'type' => 'text',
            'privacy' => 'public',
        ]);

        $this->assertEquals(0, $admin->notifications()->count());

        $response = $this->actingAs($reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Spam',
                'description' => 'Unwanted commercial promotional content.',
            ]);

        $response->assertStatus(200);

        // Verify admin notification was created
        $adminNotes = $admin->fresh()->notifications()->get();
        $this->assertCount(1, $adminNotes);
        $noteData = $adminNotes->first()->data;
        $this->assertEquals('Post Flagged for Review', $noteData['title']);
        $this->assertStringContainsString('Spam', $noteData['message']);
        $this->assertEquals('alert-circle', $noteData['icon']);
        $this->assertEquals('post_report', $noteData['source_type']);
        $this->assertStringStartsWith('/admin/reports/post/', $noteData['action_url']);
    }

    public function test_community_report_triggers_real_admin_notification(): void
    {
        $admin = $this->createAdmin('admin_comm_report@example.com');
        $owner = $this->createMember('owner@example.com');
        $reporter = Member::create([
            'name' => 'Reporter Alice',
            'user_id' => 'COMM654321',
            'email' => 'alice@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
            'phone' => '+1987654322',
        ]);

        $community = Community::create([
            'community_id' => 'comm_1234567890',
            'owner_id' => $owner->id,
            'name' => 'Tech Innovators',
            'slug' => 'tech-innovators',
            'description' => 'A community for technology enthusiasts.',
            'privacy' => 'public',
            'status' => 'active',
        ]);

        $response = $this->actingAs($reporter, 'member')
            ->postJson("/api/member/community/{$community->slug}/reports", [
                'reportable_type' => 'community',
                'reportable_id' => $community->id,
                'reason' => 'Misleading content',
                'details' => 'Fake information shared in community.',
            ]);

        $response->assertStatus(200);

        $adminNotes = $admin->fresh()->notifications()->get();
        $this->assertCount(1, $adminNotes);
        $noteData = $adminNotes->first()->data;
        $this->assertEquals('Community Content Reported', $noteData['title']);
        $this->assertStringContainsString('Tech Innovators', $noteData['message']);
        $this->assertEquals('flag', $noteData['icon']);
        $this->assertEquals('community_report', $noteData['source_type']);
    }

    public function test_business_page_creation_triggers_real_admin_notification(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin('admin_biz_page@example.com');
        $member = $this->createMember('bizowner@example.com');

        $categories = BusinessPage::categories();
        $validCategory = !empty($categories) ? $categories[0] : 'Technology & Software';

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/business-pages', [
                'page_name' => 'Apex Global Ventures',
                'page_username' => 'apex_global',
                'category' => $validCategory,
                'description' => 'We build next-generation enterprise technology solutions.',
                'email' => 'contact@apexglobal.com',
                'phone_country_code' => '+1',
                'phone_number' => '5551234567',
                'country' => 'United States',
                'visibility' => 'public',
            ]);

        $response->assertStatus(201);

        $adminNotes = $admin->fresh()->notifications()->get();
        $this->assertCount(1, $adminNotes);
        $noteData = $adminNotes->first()->data;
        $this->assertEquals('New Business Page Registered', $noteData['title']);
        $this->assertStringContainsString('Apex Global Ventures', $noteData['message']);
        $this->assertEquals('building', $noteData['icon']);
        $this->assertEquals('/admin/business-pages', $noteData['action_url']);
    }

    public function test_business_verification_submission_triggers_real_admin_notification(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin('admin_biz_verify@example.com');
        $member = $this->createMember('biz_verify_owner@example.com');

        $businessPage = BusinessPage::create([
            'member_id' => $member->id,
            'page_id' => 'biz_zenith_123',
            'page_name' => 'Zenith Health Corp',
            'page_username' => 'zenith_health',
            'slug' => 'zenith-health',
            'category' => 'Health & Wellness',
            'description' => 'Holistic wellness products and enterprise healthcare solutions.',
            'status' => 'active',
            'is_verified' => false,
        ]);

        $file = UploadedFile::fake()->create('company_registration.pdf', 250, 'application/pdf');

        $response = $this->actingAs($member, 'member')
            ->postJson("/api/member/business-pages/{$businessPage->slug}/verification", [
                'document_type' => 'business_registration',
                'document_number' => 'REG-998877',
                'document_file' => $file,
            ]);

        $response->assertStatus(200);

        $adminNotes = $admin->fresh()->notifications()->get();
        $this->assertCount(1, $adminNotes);
        $noteData = $adminNotes->first()->data;
        $this->assertEquals('Business Verification Request', $noteData['title']);
        $this->assertStringContainsString('Zenith Health Corp', $noteData['message']);
        $this->assertEquals('shield', $noteData['icon']);
        $this->assertEquals('/admin/business-pages', $noteData['action_url']);
    }

    public function test_dropdown_endpoint_returns_real_notifications_and_correct_counts(): void
    {
        $admin = $this->createAdmin('admin_dropdown_real@example.com');

        // Initially no notifications
        $emptyRes = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/notifications/dropdown');

        $emptyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
                'count' => 0,
                'notifications' => [],
            ]);

        // Trigger real notifications
        AdminNotificationService::notify(
            title: 'Event Created',
            message: 'Global Leadership Summit was scheduled.',
            icon: 'calendar',
            sourceType: 'event',
            sourceId: '55',
            actionUrl: '/admin/events/55'
        );

        AdminNotificationService::notify(
            title: 'Ad Campaign Created',
            message: 'Campaign "Summer Blast" submitted for review.',
            icon: 'megaphone',
            sourceType: 'ad_campaign',
            sourceId: '77',
            actionUrl: '/admin/ad-campaigns'
        );

        $res = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/notifications/dropdown');

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 2,
                'count' => 2,
            ]);

        $items = $res->json('notifications');
        $this->assertCount(2, $items);
        
        $titles = array_column($items, 'title');
        $this->assertContains('Ad Campaign Created', $titles);
        $this->assertContains('Event Created', $titles);

        $adItem = collect($items)->firstWhere('title', 'Ad Campaign Created');
        $this->assertEquals('/admin/ad-campaigns', $adItem['action_url']);
        $this->assertEquals('megaphone', $adItem['icon']);
        $this->assertFalse($adItem['read']);

        $eventItem = collect($items)->firstWhere('title', 'Event Created');
        $this->assertEquals('/admin/events/55', $eventItem['action_url']);
        $this->assertEquals('calendar', $eventItem['icon']);
        $this->assertFalse($eventItem['read']);
    }

    public function test_mark_all_read_clears_unread_status(): void
    {
        $admin = $this->createAdmin('admin_mark_read@example.com');

        AdminNotificationService::notify(
            title: 'Test Notification',
            message: 'Details here',
            icon: 'bell'
        );

        $this->assertEquals(1, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin, 'admin')
            ->postJson('/api/admin/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);

        $this->assertEquals(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_index_and_show_safely_handles_admin_recipient(): void
    {
        $admin = $this->createAdmin('admin_inspect@example.com');

        AdminNotificationService::notify(
            title: 'Admin Specific Alert',
            message: 'System alert message for inspection',
            icon: 'alert-circle'
        );

        $indexRes = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/notifications');

        $indexRes->assertStatus(200);

        $note = $admin->notifications()->first();
        $this->assertNotNull($note);

        $showRes = $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/notifications/{$note->id}");

        $showRes->assertStatus(200)
            ->assertJson([
                'notification' => [
                    'id' => $note->id,
                    'title' => 'Admin Specific Alert',
                ],
            ]);
    }
}
