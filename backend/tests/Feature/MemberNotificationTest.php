<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use App\Notifications\FriendCreatedPostNotification;
use App\Notifications\FriendRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_routes_require_member_authentication(): void
    {
        $this->get(route('member.notifications.index'))->assertRedirect(route('member.login'));
        $this->get(route('member.notifications.dropdown'))->assertRedirect(route('member.login'));
        $this->post(route('member.notifications.read-all'))->assertRedirect(route('member.login'));
    }

    public function test_friend_request_acceptance_and_rejection_create_one_database_notification(): void
    {
        $sender = $this->createMember('Sender');
        $receiver = $this->createMember('Receiver');

        $this->actingAs($sender, 'member')
            ->postJson(route('member.friends.request', $receiver))
            ->assertOk();
        $this->assertSame('New friend request', $receiver->notifications()->first()->data['title']);

        $this->postJson(route('member.friends.request', $receiver))->assertOk();
        $this->assertSame(1, $receiver->notifications()->count());

        $friendship = Friendship::query()->firstOrFail();
        $this->actingAs($receiver, 'member')
            ->postJson(route('member.friend-requests.accept', $friendship))
            ->assertOk();
        $this->assertSame('Friend request accepted', $sender->notifications()->first()->data['title']);

        $rejectSender = $this->createMember('Reject Sender');
        $rejectReceiver = $this->createMember('Reject Receiver');
        $this->actingAs($rejectSender, 'member')
            ->postJson(route('member.friends.request', $rejectReceiver))
            ->assertOk();
        $rejectedFriendship = Friendship::query()->between($rejectSender->id, $rejectReceiver->id)->firstOrFail();
        $this->actingAs($rejectReceiver, 'member')
            ->postJson(route('member.friend-requests.reject', $rejectedFriendship))
            ->assertOk();
        $this->assertSame('Friend request declined', $rejectSender->notifications()->first()->data['title']);
    }

    public function test_new_post_notifies_accepted_friends_only(): void
    {
        $author = $this->createMember('Author');
        $accepted = $this->createMember('Accepted');
        $pending = $this->createMember('Pending');
        $rejected = $this->createMember('Rejected');
        $this->createFriendship($author, $accepted, Friendship::STATUS_ACCEPTED);
        $this->createFriendship($author, $pending, Friendship::STATUS_PENDING);
        $this->createFriendship($author, $rejected, Friendship::STATUS_REJECTED);

        $this->actingAs($author, 'member')
            ->postJson(route('member.posts.store'), ['body' => 'Notify accepted friends.'])
            ->assertOk();

        $this->assertSame(0, $author->notifications()->count());
        $this->assertSame(1, $accepted->notifications()->count());
        $this->assertSame('New post from a Friend', $accepted->notifications()->first()->data['title']);
        $this->assertSame(0, $pending->notifications()->count());
        $this->assertSame(0, $rejected->notifications()->count());
    }

    public function test_dropdown_returns_latest_eight_and_real_unread_count(): void
    {
        $member = $this->createMember('Member');
        $actor = $this->createMember('Actor');

        for ($index = 0; $index < 10; $index++) {
            $friendship = $this->createFriendship(
                $actor,
                $this->createMember('Target '.$index),
                Friendship::STATUS_PENDING,
            );
            $member->notify(new FriendRequestReceivedNotification($actor, $friendship));
        }

        $this->actingAs($member, 'member')
            ->getJson(route('member.notifications.dropdown'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 10)
            ->assertSeeInOrder(['Notifications', 'Mark all as read', 'See all notifications']);

        $this->assertSame(8, substr_count(
            $this->getJson(route('member.notifications.dropdown'))->json('html'),
            'data-notification-read-form',
        ));
    }

    public function test_member_can_mark_own_notification_or_all_notifications_as_read(): void
    {
        $member = $this->createMember('Member');
        $otherMember = $this->createMember('Other');
        $actor = $this->createMember('Actor');
        $friendship = $this->createFriendship($actor, $member, Friendship::STATUS_PENDING);
        $member->notify(new FriendRequestReceivedNotification($actor, $friendship));
        $member->notify(new FriendRequestReceivedNotification($actor, $friendship));
        $otherMember->notify(new FriendRequestReceivedNotification($actor, $friendship));
        $firstNotification = $member->notifications()->firstOrFail();
        $otherNotification = $otherMember->notifications()->firstOrFail();

        $this->actingAs($member, 'member')
            ->postJson(route('member.notifications.read', $firstNotification->id))
            ->assertOk()
            ->assertJsonPath('message', 'Notification marked as read.')
            ->assertJsonPath('unread_count', 1);
        $this->assertNotNull($firstNotification->fresh()->read_at);

        $this->postJson(route('member.notifications.read', $otherNotification->id))->assertNotFound();
        $this->assertNull($otherNotification->fresh()->read_at);

        $this->postJson(route('member.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('message', 'All notifications have been marked as read.')
            ->assertJsonPath('unread_count', 0);
        $this->assertSame(0, $member->unreadNotifications()->count());
    }

    public function test_notifications_page_and_header_show_only_real_member_data(): void
    {
        $member = $this->createMember('Member');
        $otherMember = $this->createMember('Other');
        $actor = $this->createMember('Actor');
        $post = $actor->posts()->create(['body' => 'Notification source post']);
        $member->notify(new FriendCreatedPostNotification($actor, $post));
        $otherMember->notify(new FriendCreatedPostNotification($actor, $post));

        $this->actingAs($member, 'member')
            ->get(route('member.notifications.index'))
            ->assertOk()
            ->assertSee('New post from a Friend')
            ->assertSee('shared a new post')
            ->assertDontSee($otherMember->email);

        $this->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('data-notification-badge', false)
            ->assertSee('>1</span>', false)
            ->assertDontSee('notification-badge">3', false);
    }

    private function createFriendship(Member $first, Member $second, string $status): Friendship
    {
        [$memberOneId, $memberTwoId] = Friendship::normalizePair($first->id, $second->id);

        return Friendship::create([
            'member_one_id' => $memberOneId,
            'member_two_id' => $memberTwoId,
            'requested_by_id' => $first->id,
            'status' => $status,
            'accepted_at' => $status === Friendship::STATUS_ACCEPTED ? now() : null,
            'rejected_at' => $status === Friendship::STATUS_REJECTED ? now() : null,
        ]);
    }

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'email' => str($name)->slug().'-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'secure-password',
        ]);
    }
}
