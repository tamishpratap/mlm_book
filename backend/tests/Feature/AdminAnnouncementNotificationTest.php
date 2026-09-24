<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Category;
use App\Models\Member;
use App\Models\Post;
use App\Models\Product;
use App\Notifications\AdminBroadcastNotification;
use App\Notifications\PostCommentNotification;
use App\Notifications\PostReactionNotification;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);

        $this->admin = Admin::create([
            'name' => 'Platform Administrator',
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->member = Member::create([
            'name' => 'Alice Member',
            'user_id' => 'MEMB001',
            'email' => 'alice_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_admin_announcement_creates_database_record_with_authoritative_metadata(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Scheduled System Upgrade',
                'message' => 'The platform will undergo maintenance on Sunday.',
            ]);

        $response->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\\Models\\Member',
            'notifiable_id' => $this->member->id,
            'type' => 'App\\Notifications\\AdminBroadcastNotification',
        ]);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $this->member->id)
            ->first();

        $this->assertNotNull($notification);
        $data = json_decode($notification->data, true);

        $this->assertSame('ADMIN_ANNOUNCEMENT', $data['type']);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $data['notification_type']);
        $this->assertSame('ADMIN', $data['source']);
        $this->assertSame('ADMIN', $data['source_type']);
        $this->assertSame('system', $data['category']);
        $this->assertSame('Scheduled System Upgrade', $data['title']);
        $this->assertSame('The platform will undergo maintenance on Sunday.', $data['message']);
        $this->assertSame('Admin Announcement', $data['actor_name']);
        $this->assertSame('Admin Broadcast', $data['sender']);
        $this->assertSame('admin_announcement', $data['reference_type']);
    }

    public function test_member_notifications_api_exposes_authoritative_admin_announcement_type_and_source(): void
    {
        // Dispatch announcement
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Policy Update 2026',
                'message' => 'Please review our terms of service update.',
            ]);

        $response = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('notifications.data');
        $this->assertNotEmpty($items);

        $announcement = $items[0];

        // Authoritative top-level identification
        $this->assertSame('ADMIN_ANNOUNCEMENT', $announcement['notification_type']);
        $this->assertSame('ADMIN', $announcement['source']);
        $this->assertSame('ADMIN', $announcement['source_type']);
        $this->assertSame('App\\Notifications\\AdminBroadcastNotification', $announcement['type']);

        // Authoritative payload identification
        $this->assertSame('ADMIN_ANNOUNCEMENT', $announcement['data']['type']);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $announcement['data']['notification_type']);
        $this->assertSame('ADMIN', $announcement['data']['source']);
        $this->assertSame('ADMIN', $announcement['data']['source_type']);
        $this->assertSame('system', $announcement['data']['category']);
        $this->assertSame('Policy Update 2026', $announcement['data']['title']);
        $this->assertSame('Admin Announcement', $announcement['data']['actor_name']);
    }

    public function test_member_dropdown_and_poll_endpoints_expose_authoritative_type_and_source(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Flash Event Announcement',
                'message' => 'Join our live webinar tonight!',
            ]);

        // Dropdown endpoint
        $dropdownRes = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications/dropdown');

        $dropdownRes->assertOk()
            ->assertJsonPath('success', true);

        $dropdownItems = $dropdownRes->json('notifications');
        $this->assertNotEmpty($dropdownItems);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $dropdownItems[0]['notification_type']);
        $this->assertSame('ADMIN', $dropdownItems[0]['source']);

        // Poll endpoint
        $pollRes = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications/poll');

        $pollRes->assertOk()
            ->assertJsonPath('success', true);

        $pollItems = $pollRes->json('notifications');
        $this->assertNotEmpty($pollItems);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $pollItems[0]['notification_type']);
        $this->assertSame('ADMIN', $pollItems[0]['source']);
    }

    public function test_historical_admin_announcement_without_new_metadata_is_authoritatively_resolved_by_api(): void
    {
        // Insert legacy record lacking new metadata keys
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\AdminBroadcastNotification',
            'notifiable_type' => 'App\\Models\\Member',
            'notifiable_id' => $this->member->id,
            'data' => json_encode([
                'title' => 'Legacy Announcement',
                'message' => 'Old message before system upgrade.',
                'sender' => 'Admin Broadcast',
                'sent_at' => '2026-01-01 10:00:00',
            ]),
            'created_at' => now()->subMonths(2),
            'updated_at' => now()->subMonths(2),
        ]);

        $response = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications');

        $response->assertOk();

        $items = $response->json('notifications.data');
        $legacyItem = collect($items)->firstWhere('data.title', 'Legacy Announcement');

        $this->assertNotNull($legacyItem);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $legacyItem['notification_type']);
        $this->assertSame('ADMIN', $legacyItem['source']);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $legacyItem['data']['notification_type']);
        $this->assertSame('ADMIN', $legacyItem['data']['source']);
        $this->assertSame('system', $legacyItem['data']['category']);
        $this->assertSame('Admin Announcement', $legacyItem['data']['actor_name']);
    }

    public function test_other_notification_types_retain_distinct_types_and_member_sources(): void
    {
        $sender = Member::create([
            'name' => 'Bob Member',
            'user_id' => 'MEMB002',
            'email' => 'bob_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Post for notifications test.',
            'type' => 'text',
            'privacy' => 'public',
        ]);

        // 1. Post Reaction Notification
        $this->member->notify(new PostReactionNotification($sender, $post, 'like'));

        // 2. Post Comment Notification
        $this->member->notify(new PostCommentNotification($sender, $post, 'Great post!'));

        // 3. System Notification
        $this->member->notify(new SystemNotification('System Maintenance', 'System will be updated.'));

        $response = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications');

        $response->assertOk();

        $items = collect($response->json('notifications.data'));

        $reactionItem = $items->firstWhere('notification_type', 'POST_REACTION');
        $this->assertNotNull($reactionItem);
        $this->assertSame('MEMBER', $reactionItem['source']);

        $commentItem = $items->firstWhere('notification_type', 'POST_COMMENT');
        $this->assertNotNull($commentItem);
        $this->assertSame('MEMBER', $commentItem['source']);

        $systemItem = $items->firstWhere('notification_type', 'SYSTEM');
        $this->assertNotNull($systemItem);
        $this->assertSame('SYSTEM', $systemItem['source']);
    }

    public function test_mixed_feed_contains_admin_announcement_and_other_notifications_without_cross_contamination(): void
    {
        $sender = Member::create([
            'name' => 'Charlie Member',
            'user_id' => 'MEMB003',
            'email' => 'charlie_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Mixed feed post.',
            'type' => 'text',
            'privacy' => 'public',
        ]);

        // Send Member notification
        $this->member->notify(new PostReactionNotification($sender, $post, 'love'));

        // Send Admin Announcement
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Important Community Guidelines Update',
                'message' => 'Please read our updated community standards.',
            ]);

        $response = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications');

        $response->assertOk();
        $items = collect($response->json('notifications.data'));

        $this->assertCount(2, $items);

        $announcement = $items->firstWhere('notification_type', 'ADMIN_ANNOUNCEMENT');
        $this->assertNotNull($announcement);
        $this->assertSame('ADMIN', $announcement['source']);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $announcement['data']['type']);

        $reaction = $items->firstWhere('notification_type', 'POST_REACTION');
        $this->assertNotNull($reaction);
        $this->assertSame('MEMBER', $reaction['source']);
        $this->assertSame('posts', $reaction['data']['category']);
    }

    public function test_system_filter_includes_admin_announcements(): void
    {
        $sender = Member::create([
            'name' => 'Dave Member',
            'user_id' => 'MEMB004',
            'email' => 'dave_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Filtered post.',
            'type' => 'text',
            'privacy' => 'public',
        ]);

        $this->member->notify(new PostCommentNotification($sender, $post, 'Nice work!'));

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'System Announcement for Filter',
                'message' => 'System wide alert.',
            ]);

        // Filter: system
        $systemRes = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications?filter=system');

        $systemRes->assertOk();
        $systemItems = collect($systemRes->json('notifications.data'));
        $this->assertCount(1, $systemItems);
        $this->assertSame('ADMIN_ANNOUNCEMENT', $systemItems->first()['notification_type']);

        // Filter: comments
        $commentsRes = $this->actingAs($this->member, 'member')
            ->getJson('/api/member/notifications?filter=comments');

        $commentsRes->assertOk();
        $commentItems = collect($commentsRes->json('notifications.data'));
        $this->assertCount(1, $commentItems);
        $this->assertSame('POST_COMMENT', $commentItems->first()['notification_type']);
    }

    public function test_admin_announcement_participates_in_read_and_clear_lifecycle(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Lifecycle Announcement',
                'message' => 'Testing read/clear lifecycle.',
            ]);

        $notification = $this->member->unreadNotifications()->firstOrFail();

        // 1. Mark as read
        $readRes = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/notifications/{$notification->id}/read");

        $readRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);

        // 2. Clear all
        $clearRes = $this->actingAs($this->member, 'member')
            ->deleteJson('/api/member/notifications/clear-all');

        $clearRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, $this->member->notifications()->count());
    }

    public function test_audience_targeting_is_strictly_preserved(): void
    {
        $activeMember = Member::create([
            'name' => 'Active Only Member',
            'user_id' => 'ACTIVE01',
            'email' => 'active_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $seller = Member::create([
            'name' => 'Seller Member',
            'user_id' => 'SELLER01',
            'email' => 'seller_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Digital Goods',
            'slug' => 'digital-goods-' . uniqid(),
        ]);

        Product::create([
            'member_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'E-Book Guide',
            'slug' => 'ebook-guide-' . uniqid(),
            'description' => 'Guide description',
            'price' => 19.99,
            'condition' => 'brand_new',
            'status' => 'available',
        ]);

        // Send announcement to sellers only
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'sellers',
                'title' => 'Seller Only Alert',
                'message' => 'Exclusive to sellers.',
            ]);

        // Seller receives it
        $this->assertSame(1, $seller->notifications()->count());
        $this->assertSame('ADMIN_ANNOUNCEMENT', $seller->notifications()->first()->data['notification_type']);

        // Active non-seller member does NOT receive it
        $this->assertSame(0, $activeMember->notifications()->count());
    }

    public function test_member_can_retrieve_single_admin_announcement_by_id_with_authorization(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Single Announcement Test',
                'message' => 'Complete detailed text of announcement.',
            ]);

        $notification = $this->member->notifications()->firstOrFail();

        // 1. Authorized member gets notification detail
        $res = $this->actingAs($this->member, 'member')
            ->getJson("/api/member/notifications/{$notification->id}");

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notification.notification_type', 'ADMIN_ANNOUNCEMENT')
            ->assertJsonPath('notification.source', 'ADMIN')
            ->assertJsonPath('notification.data.title', 'Single Announcement Test')
            ->assertJsonPath('notification.data.message', 'Complete detailed text of announcement.');

        // 2. Another member cannot view this member's notification (404)
        $otherMember = Member::create([
            'name' => 'Other Member',
            'user_id' => 'OTHER01',
            'email' => 'other_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $otherRes = $this->actingAs($otherMember, 'member')
            ->getJson("/api/member/notifications/{$notification->id}");

        $otherRes->assertNotFound();

        // 3. Unauthenticated request is rejected (401)
        auth('member')->logout();
        $unauthRes = $this->getJson("/api/member/notifications/{$notification->id}");
        $unauthRes->assertUnauthorized();
    }

    public function test_admin_announcement_preserves_complete_unabridged_long_content(): void
    {
        $paragraphs = [
            "Paragraph 1: Welcome to our comprehensive network upgrade announcement.",
            "Paragraph 2: Starting at midnight UTC, our infrastructure will undergo scheduled maintenance.",
            "Paragraph 3: All account data, balances, commissions, and networks remain 100% secure and backed up.",
            "Paragraph 4: For any assistance, reach out to our 24/7 dedicated support team.",
        ];
        $longBody = implode("\n\n", $paragraphs);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Important Maintenance Notice — Extended Details',
                'message' => $longBody,
            ]);

        $notification = $this->member->notifications()->firstOrFail();

        $res = $this->actingAs($this->member, 'member')
            ->getJson("/api/member/notifications/{$notification->id}");

        $res->assertOk()
            ->assertJsonPath('success', true);

        $returnedBody = $res->json('notification.data.message');
        $this->assertSame($longBody, $returnedBody);
        $this->assertStringContainsString('Paragraph 1', $returnedBody);
        $this->assertStringContainsString('Paragraph 4', $returnedBody);
    }

    public function test_multiple_admin_announcements_remain_distinct_and_isolated(): void
    {
        // Announcement A
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Announcement Alpha',
                'message' => 'Alpha content body.',
            ]);

        // Announcement B
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.notifications.broadcast.send'), [
                'audience' => 'all',
                'title' => 'Announcement Beta',
                'message' => 'Beta content body.',
            ]);

        $notifications = $this->member->notifications()->get();
        $this->assertCount(2, $notifications);

        $alphaNotif = $notifications->first(fn($n) => (is_array($n->data) ? $n->data['title'] : json_decode($n->data, true)['title']) === 'Announcement Alpha');
        $betaNotif = $notifications->first(fn($n) => (is_array($n->data) ? $n->data['title'] : json_decode($n->data, true)['title']) === 'Announcement Beta');

        $this->assertNotNull($alphaNotif);
        $this->assertNotNull($betaNotif);
        $this->assertNotSame($betaNotif->id, $alphaNotif->id);

        // Fetch Alpha
        $alphaRes = $this->actingAs($this->member, 'member')
            ->getJson("/api/member/notifications/{$alphaNotif->id}");
        $alphaRes->assertOk()
            ->assertJsonPath('notification.data.title', 'Announcement Alpha')
            ->assertJsonPath('notification.data.message', 'Alpha content body.');

        // Fetch Beta
        $betaRes = $this->actingAs($this->member, 'member')
            ->getJson("/api/member/notifications/{$betaNotif->id}");
        $betaRes->assertOk()
            ->assertJsonPath('notification.data.title', 'Announcement Beta')
            ->assertJsonPath('notification.data.message', 'Beta content body.');
    }
}
