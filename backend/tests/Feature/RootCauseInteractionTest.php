<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use App\Models\Post;
use App\Models\Product;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RootCauseInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_post_story_marketplace_group_and_event_ajax_interactions_reach_controllers(): void
    {
        $author = $this->createMember('Flow Author');
        $actor = $this->createMember('Flow Actor');
        $invitee = $this->createMember('Flow Invitee');
        $this->createFriendship($author, $actor, Friendship::STATUS_ACCEPTED);

        $post = $author->posts()->create(['body' => 'Interaction post']);

        $this->actingAs($actor, 'member')
            ->postJson(route('member.posts.react', $post), ['reaction' => 'love'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_reaction', 'love');
        $this->assertDatabaseHas('post_reactions', [
            'post_id' => $post->id,
            'member_id' => $actor->id,
            'reaction' => 'love',
        ]);

        $this->postJson(route('member.posts.comments.store', $post), ['comment' => 'AJAX comment'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('post_comments', [
            'post_id' => $post->id,
            'member_id' => $actor->id,
            'comment' => 'AJAX comment',
        ]);

        $this->postJson(route('member.posts.save', $post))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_saved', true)
            ->assertJsonPath('saves_count', 1);
        $this->assertDatabaseHas('saved_posts', [
            'post_id' => $post->id,
            'member_id' => $actor->id,
        ]);

        $this->postJson(route('member.posts.share', $post), ['share_message' => 'Shared by AJAX'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('shares_count', 1);
        $this->assertDatabaseHas('post_shares', [
            'original_post_id' => $post->id,
            'shared_by' => $actor->id,
            'share_message' => 'Shared by AJAX',
        ]);

        $story = $author->stories()->create([
            'caption' => 'Interaction story',
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/interaction-story.jpg',
            'expires_at' => now()->addDay(),
        ]);

        $this->getJson(route('member.stories.show', $story))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('story_views', [
            'story_id' => $story->id,
            'viewer_member_id' => $actor->id,
        ]);

        $this->postJson(route('member.stories.react', $story), ['reaction' => 'haha'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_reaction', 'haha');
        $this->assertDatabaseHas('story_reactions', [
            'story_id' => $story->id,
            'member_id' => $actor->id,
            'reaction' => 'haha',
        ]);

        $this->postJson(route('member.stories.reply', $story), ['message' => 'Story reply'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('story_replies', [
            'story_id' => $story->id,
            'sender_id' => $actor->id,
            'receiver_id' => $author->id,
            'message' => 'Story reply',
        ]);

        $this->actingAs($author, 'member')
            ->getJson(route('member.stories.viewers', $story))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('views_count', 1);

        $this->deleteJson(route('member.stories.destroy', $story))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('stories', ['id' => $story->id]);

        $category = Category::query()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        $product = Product::query()->create([
            'member_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'AJAX Product',
            'slug' => 'ajax-product',
            'description' => 'Product saved through AJAX.',
            'price' => 100,
            'condition' => 'new',
            'status' => 'available',
        ]);

        $this->actingAs($actor, 'member')
            ->postJson(route('member.marketplace.save', $product))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_saved', true);
        $this->assertDatabaseHas('saved_products', [
            'member_id' => $actor->id,
            'product_id' => $product->id,
        ]);

        $group = Community::query()->create([
            'community_id' => 'COMM-FLOW-101',
            'owner_id' => $author->id,
            'name' => 'Private Flow Group',
            'slug' => 'private-flow-group',
            'category' => 'Business',
            'privacy' => 'private',
        ]);
        $authorNotificationCount = $author->notifications()->count();

        $this->postJson(route('member.community.join', ['community' => $group->slug]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'accepted');
        $this->assertDatabaseHas('community_members', [
            'community_id' => $group->id,
            'member_id' => $actor->id,
            'status' => 'accepted',
        ]);

        CommunityMember::query()->create([
            'community_id' => $group->id,
            'member_id' => $author->id,
            'role' => 'owner',
            'status' => 'accepted',
            'joined_at' => now(),
        ]);

        $this->actingAs($author, 'member')
            ->postJson(route('member.community.invites.generate', ['community' => $group->slug]))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('community_invitations', [
            'community_id' => $group->id,
            'inviter_id' => $author->id,
        ]);

        $event = Event::query()->create([
            'organizer_id' => $author->id,
            'title' => 'AJAX Event',
            'slug' => 'ajax-event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDay(),
            'status' => 'published',
        ]);

        $this->actingAs($actor, 'member')
            ->postJson(route('member.events.respond', $event), ['response' => 'going'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('response', 'going');
        $this->assertDatabaseHas('event_responses', [
            'event_id' => $event->id,
            'member_id' => $actor->id,
            'response' => 'going',
        ]);

        $this->actingAs($author, 'member')
            ->postJson(route('member.events.invite', $event), ['invited_id' => $invitee->id])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('event_invitations', [
            'event_id' => $event->id,
            'invited_id' => $invitee->id,
            'inviter_id' => $author->id,
        ]);
        $this->assertSame(1, $invitee->notifications()->count());
    }

    public function test_social_javascript_files_parse_and_post_handlers_are_not_composer_gated(): void
    {
        $storiesScript = file_get_contents(public_path('member_assets/js/member-stories.js'));
        $postsScript = file_get_contents(public_path('member_assets/js/member-posts.js'));

        $this->assertStringNotContainsString("if (! form || form.dataset.initialized === 'true') {\n            return;\n        }", $postsScript);
        $this->assertStringContainsString("if (form && form.dataset.initialized !== 'true')", $postsScript);
        $this->assertStringContainsString('const getCsrfToken = function ()', $postsScript);
        $this->assertStringContainsString('let commentReactionHoverTimer = null;', $postsScript);
        $this->assertStringContainsString('const initScrollPreservation = function ()', $postsScript);
        $this->assertStringContainsString('const openPostShareModal = function', $postsScript);
        $this->assertStringContainsString('const submitPostShare = async function', $postsScript);
        $this->assertStringContainsString('const openPostSharersModal = async function', $postsScript);
        $this->assertStringContainsString("const closeViewersModal = function ()", $storiesScript);
        $this->assertStringContainsString("document.addEventListener('click'", $storiesScript);
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
        ]);
    }

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'email' => str($name)->slug().'-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'secure-password',
            'mobile_verified_at' => now(),
            'phone' => '+1555'.random_int(1000000, 9999999),
        ]);
    }
}
