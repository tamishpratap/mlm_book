<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberFriendshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_friendship_routes_require_member_authentication(): void
    {
        $member = $this->createMember('Target Member');

        $this->get(route('member.friends.index'))->assertRedirect(route('member.login'));
        $this->get(route('member.friend-requests.index'))->assertRedirect(route('member.login'));
        $this->get(route('member.people.show', $member))->assertRedirect(route('member.login'));
        $this->post(route('member.friends.request', $member))->assertRedirect(route('member.login'));
    }

    public function test_member_can_send_one_normalized_friend_request_and_ajax_returns_new_actions(): void
    {
        $target = $this->createMember('Target Member');
        $sender = $this->createMember('Sender Member');

        $response = $this->actingAs($sender, 'member')
            ->postJson(route('member.friends.request', $target));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Friend request sent successfully.')
            ->assertJsonPath('state', 'pending_sent')
            ->assertJsonPath('member_id', $target->id)
            ->assertSee('Request Sent', false);

        $this->assertDatabaseHas('friendships', [
            'member_one_id' => min($sender->id, $target->id),
            'member_two_id' => max($sender->id, $target->id),
            'requested_by_id' => $sender->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        $this->postJson(route('member.friends.request', $target))
            ->assertOk()
            ->assertJsonPath('message', 'Friend request has already been sent.')
            ->assertJsonPath('state', 'pending_sent');

        $this->assertDatabaseCount('friendships', 1);
    }

    public function test_reverse_request_is_not_duplicated_and_shows_incoming_actions(): void
    {
        $first = $this->createMember('First Member');
        $second = $this->createMember('Second Member');

        $this->actingAs($first, 'member')
            ->post(route('member.friends.request', $second))
            ->assertSessionHas('success');

        $this->actingAs($second, 'member')
            ->postJson(route('member.friends.request', $first))
            ->assertOk()
            ->assertJsonPath(
                'message',
                'This Member has already sent you a friend request. You can accept or reject it.',
            )
            ->assertJsonPath('state', 'pending_received')
            ->assertSee('Accept', false)
            ->assertSee('Reject', false);

        $this->assertDatabaseCount('friendships', 1);
    }

    public function test_member_cannot_send_a_request_to_themselves(): void
    {
        $member = $this->createMember('Solo Member');

        $this->actingAs($member, 'member')
            ->postJson(route('member.friends.request', $member))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot send a friend request to yourself.');

        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_only_the_receiver_can_accept_a_pending_request(): void
    {
        [$sender, $receiver, $friendship] = $this->pendingFriendship();
        $thirdMember = $this->createMember('Third Member');

        $this->actingAs($sender, 'member')
            ->postJson(route('member.friend-requests.accept', $friendship))
            ->assertForbidden();

        $this->actingAs($thirdMember, 'member')
            ->postJson(route('member.friend-requests.accept', $friendship))
            ->assertForbidden();

        $this->actingAs($receiver, 'member')
            ->postJson(route('member.friend-requests.accept', $friendship))
            ->assertOk()
            ->assertJsonPath('message', 'Friend request accepted successfully.')
            ->assertJsonPath('state', 'friends')
            ->assertJsonPath('member_id', $sender->id)
            ->assertSee('Friends', false);

        $friendship->refresh();
        $this->assertSame(Friendship::STATUS_ACCEPTED, $friendship->status);
        $this->assertNotNull($friendship->accepted_at);
        $this->assertNull($friendship->rejected_at);

        $this->postJson(route('member.friend-requests.accept', $friendship))->assertForbidden();
        $this->assertDatabaseCount('friendships', 1);
    }

    public function test_receiver_can_reject_and_sender_can_reuse_the_same_row(): void
    {
        [$sender, $receiver, $friendship] = $this->pendingFriendship();

        $this->actingAs($receiver, 'member')
            ->postJson(route('member.friend-requests.reject', $friendship))
            ->assertOk()
            ->assertJsonPath('message', 'Friend request rejected.')
            ->assertJsonPath('state', 'none');

        $friendship->refresh();
        $this->assertSame(Friendship::STATUS_REJECTED, $friendship->status);
        $this->assertNotNull($friendship->rejected_at);
        $this->assertNull($friendship->accepted_at);

        $this->actingAs($sender, 'member')
            ->get(route('member.search', ['q' => 'Receiver', 'type' => 'members']))
            ->assertOk()
            ->assertSee('Add Friend');

        $this->postJson(route('member.friends.request', $receiver))
            ->assertOk()
            ->assertJsonPath('state', 'pending_sent');

        $friendship->refresh();
        $this->assertSame(Friendship::STATUS_PENDING, $friendship->status);
        $this->assertSame($sender->id, $friendship->requested_by_id);
        $this->assertNull($friendship->accepted_at);
        $this->assertNull($friendship->rejected_at);
        $this->assertDatabaseCount('friendships', 1);
    }

    public function test_friend_lists_include_both_sides_of_accepted_rows_only(): void
    {
        $first = $this->createMember('First Member');
        $second = $this->createMember('Second Member');
        $pending = $this->createMember('Pending Member');
        $rejected = $this->createMember('Rejected Member');

        $this->createFriendship($first, $second, Friendship::STATUS_ACCEPTED, $first);
        $this->createFriendship($first, $pending, Friendship::STATUS_PENDING, $pending);
        $this->createFriendship($first, $rejected, Friendship::STATUS_REJECTED, $first);

        $this->actingAs($first, 'member')
            ->get(route('member.friends.index'))
            ->assertOk()
            ->assertViewHas('friends', fn ($friends) => $friends->total() === 1
                && $friends->first()->is($second))
            ->assertSee('Second Member')
            ->assertDontSee('Pending Member')
            ->assertDontSee('Rejected Member');

        $this->actingAs($second, 'member')
            ->get(route('member.friends.index'))
            ->assertOk()
            ->assertViewHas('friends', fn ($friends) => $friends->total() === 1
                && $friends->first()->is($first))
            ->assertSee('First Member');

        $this->get(route('member.people.friends', $first))
            ->assertOk()
            ->assertSee("First Member's Friends")
            ->assertSee('Second Member')
            ->assertDontSee('Pending Member');

        $this->assertDatabaseCount('friendships', 3);
    }

    public function test_request_pages_search_and_profiles_render_real_states_without_private_details(): void
    {
        [$sender, $receiver, $friendship] = $this->pendingFriendship();
        $sender->update([
            'bio' => 'A public biography.',
            'phone' => '+919999999999',
        ]);

        $this->actingAs($receiver, 'member')
            ->get(route('member.friend-requests.index'))
            ->assertOk()
            ->assertSee('Incoming Requests')
            ->assertSee('Sender Member')
            ->assertSee('Accept')
            ->assertSee('Reject')
            ->assertDontSee('+919999999999');

        $this->get(route('member.people.show', $sender))
            ->assertOk()
            ->assertSee('A public biography.')
            ->assertSee('Accept')
            ->assertDontSee($sender->email)
            ->assertDontSee('+919999999999');

        $this->actingAs($sender, 'member')
            ->get(route('member.search', ['q' => 'Receiver', 'type' => 'members']))
            ->assertOk()
            ->assertSee('Request Sent')
            ->assertDontSee($receiver->email);

        $this->get(route('member.profile.show', ['tab' => 'friends']))
            ->assertOk()
            ->assertSee('Friends')
            ->assertSee('<strong>0</strong>', false);

        $this->actingAs($receiver, 'member')
            ->post(route('member.friend-requests.accept', $friendship))
            ->assertSessionHas('success', 'Friend request accepted successfully.');

        $this->get(route('member.profile.show', ['tab' => 'friends']))
            ->assertOk()
            ->assertSee('Friends')
            ->assertSee('Sender Member');
    }

    private function pendingFriendship(): array
    {
        $sender = $this->createMember('Sender Member');
        $receiver = $this->createMember('Receiver Member');
        $friendship = $this->createFriendship(
            $sender,
            $receiver,
            Friendship::STATUS_PENDING,
            $sender,
        );

        return [$sender, $receiver, $friendship];
    }

    private function createFriendship(
        Member $first,
        Member $second,
        string $status,
        Member $requestedBy,
    ): Friendship {
        [$memberOneId, $memberTwoId] = Friendship::normalizePair($first->id, $second->id);

        return Friendship::create([
            'member_one_id' => $memberOneId,
            'member_two_id' => $memberTwoId,
            'requested_by_id' => $requestedBy->id,
            'status' => $status,
            'accepted_at' => $status === Friendship::STATUS_ACCEPTED ? now() : null,
            'rejected_at' => $status === Friendship::STATUS_REJECTED ? now() : null,
        ]);
    }

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'email' => str($name)->slug().'-'.str()->random(8).'@example.com',
            'phone' => '+9198765' . random_int(10000, 99999),
            'mobile_verified_at' => now(),
            'password' => 'secure-password',
        ]);
    }
}
