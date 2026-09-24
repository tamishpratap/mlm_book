<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommunityNotificationCenterTest extends TestCase
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

    public function test_community_notifications_requires_auth(): void
    {
        $this->getJson('/api/member/community/notifications')->assertUnauthorized();
        $this->get('/member/community/notifications')->assertRedirect('/member/login');
    }

    public function test_community_notifications_empty_state(): void
    {
        $member = $this->createMember('Test Member');
        $this->actingAs($member, 'member');

        $response = $this->getJson('/api/member/community/notifications');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications.data');
    }

    public function test_blade_view_renders_unified_card_and_empty_state(): void
    {
        $member = $this->createMember('Test Member');
        $this->actingAs($member, 'member');

        $response = $this->get('/member/community/notifications');
        $response->assertOk()
            ->assertSee('community-notifications-card', false)
            ->assertSee('Community Notification Center')
            ->assertSee('Back to Communities')
            ->assertSee('community-notifications-tabs', false)
            ->assertSee('No Community Notifications')
            ->assertSee('You are all caught up with your communities.');
    }

    public function test_mark_as_read_actions_work(): void
    {
        $member = $this->createMember('Test Member');
        $this->actingAs($member, 'member');

        // Create a test database notification
        $notificationId = (string) Str::uuid();
        $member->notifications()->create([
            'id' => $notificationId,
            'type' => 'App\\Notifications\\CommunityAnnouncementNotification',
            'data' => [
                'category' => 'community',
                'action' => 'announcement',
                'title' => 'Important Community Update',
                'message' => 'New rules have been published.',
                'community_slug' => 'tech-innovators',
                'community_name' => 'Tech Innovators',
            ],
            'read_at' => null,
        ]);

        $this->assertSame(1, $member->unreadNotifications()->where('data->category', 'community')->count());

        // View notifications API
        $res = $this->getJson('/api/member/community/notifications');
        $res->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications.data');

        // Mark single as read
        $markRes = $this->postJson("/api/member/community/notifications/{$notificationId}/read");
        $markRes->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, $member->fresh()->unreadNotifications()->where('data->category', 'community')->count());

        // Create another unread notification
        $notificationId2 = (string) Str::uuid();
        $member->notifications()->create([
            'id' => $notificationId2,
            'type' => 'App\\Notifications\\CommunityModerationNotification',
            'data' => [
                'category' => 'community',
                'action' => 'moderation_action',
                'title' => 'Moderation Notice',
                'message' => 'Post was approved.',
            ],
            'read_at' => null,
        ]);

        $this->assertSame(1, $member->fresh()->unreadNotifications()->where('data->category', 'community')->count());

        // Mark all as read
        $markAllRes = $this->postJson('/api/member/community/notifications/mark-all-read');
        $markAllRes->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, $member->fresh()->unreadNotifications()->where('data->category', 'community')->count());
    }
}
