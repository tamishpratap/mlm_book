<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\BusinessFollower;
use App\Models\BusinessInvitation;
use App\Models\BusinessPage;
use App\Models\BusinessReview;
use App\Models\BusinessReviewVote;
use App\Models\BusinessTeamMember;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Story;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberMobileVerificationRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private const EXACT_MESSAGE = EnsureMemberMobileVerified::UNVERIFIED_MESSAGE;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ValidateCsrfToken::class]);
    }

    public function test_unverified_member_cannot_create_post(): void
    {
        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.posts.store'), [
                'body' => 'Attempting post creation',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('posts', [
            'member_id' => $unverified->id,
            'body' => 'Attempting post creation',
        ]);
    }

    public function test_unverified_member_cannot_create_story(): void
    {
        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.stories.store'), [
                'caption' => 'Attempting story upload',
                'media' => UploadedFile::fake()->image('story.jpg', 600, 800),
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('stories', [
            'member_id' => $unverified->id,
            'caption' => 'Attempting story upload',
        ]);
    }

    public function test_unverified_member_cannot_like_post(): void
    {
        $verifiedAuthor = $this->createVerifiedMember();
        $post = Post::create([
            'member_id' => $verifiedAuthor->id,
            'body' => 'Author post',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.posts.like', $post));

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('post_likes', [
            'post_id' => $post->id,
            'member_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_cannot_react_to_post(): void
    {
        $verifiedAuthor = $this->createVerifiedMember();
        $post = Post::create([
            'member_id' => $verifiedAuthor->id,
            'body' => 'Author post',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.posts.react', $post), [
                'reaction' => 'love',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('post_reactions', [
            'post_id' => $post->id,
            'member_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_cannot_comment_on_post(): void
    {
        $verifiedAuthor = $this->createVerifiedMember();
        $post = Post::create([
            'member_id' => $verifiedAuthor->id,
            'body' => 'Author post',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.posts.comments.store', $post), [
                'comment' => 'Attempting a comment',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('post_comments', [
            'post_id' => $post->id,
            'member_id' => $unverified->id,
            'comment' => 'Attempting a comment',
        ]);
    }

    public function test_unverified_member_cannot_reply_to_comment(): void
    {
        $verifiedAuthor = $this->createVerifiedMember();
        $post = Post::create([
            'member_id' => $verifiedAuthor->id,
            'body' => 'Author post',
        ]);
        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $verifiedAuthor->id,
            'comment' => 'Initial comment',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.comments.replies.store', $comment), [
                'comment' => 'Attempting a reply',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('post_comments', [
            'post_id' => $post->id,
            'member_id' => $unverified->id,
            'comment' => 'Attempting a reply',
        ]);
    }

    public function test_unverified_member_cannot_follow_or_send_connection_request(): void
    {
        $verifiedTarget = $this->createVerifiedMember();
        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.friends.request', $verifiedTarget));

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('friendships', [
            'requested_by_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_can_view_content_normally(): void
    {
        $verifiedAuthor = $this->createVerifiedMember();
        $unverified = $this->createUnverifiedMember();

        // Establish friendship to view stories
        Friendship::create([
            'member_one_id' => min($verifiedAuthor->id, $unverified->id),
            'member_two_id' => max($verifiedAuthor->id, $unverified->id),
            'requested_by_id' => $verifiedAuthor->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $post = Post::create([
            'member_id' => $verifiedAuthor->id,
            'body' => 'Public post to view',
        ]);
        $story = Story::create([
            'member_id' => $verifiedAuthor->id,
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/test.jpg',
            'expires_at' => now()->addHours(24),
        ]);

        // Dashboard/Home
        $this->actingAs($unverified, 'member')
            ->get(route('member.dashboard'))
            ->assertOk();

        // View Post
        $this->actingAs($unverified, 'member')
            ->get(route('member.posts.show', $post))
            ->assertOk();

        // View Story (as accepted connection)
        $this->actingAs($unverified, 'member')
            ->get(route('member.stories.show', $story))
            ->assertOk();

        // View Profile
        $this->actingAs($unverified, 'member')
            ->get(route('member.people.show', $verifiedAuthor))
            ->assertOk();

        // View Socials
        $this->actingAs($unverified, 'member')
            ->get(route('member.socials'))
            ->assertOk();

        // View Watch
        $this->actingAs($unverified, 'member')
            ->get(route('member.watch.index'))
            ->assertOk();
    }

    public function test_verified_member_can_perform_all_actions_successfully(): void
    {
        $verifiedAuthor = $this->createVerifiedMember();
        $otherMember = $this->createVerifiedMember();

        // 1. Create Post
        $postResponse = $this->actingAs($verifiedAuthor, 'member')
            ->postJson(route('member.posts.store'), [
                'body' => 'Verified member post content',
            ]);

        $postResponse->assertOk()
            ->assertJsonPath('success', true);

        $post = Post::query()->where('member_id', $verifiedAuthor->id)->firstOrFail();

        // 2. Like Post
        $likeResponse = $this->actingAs($otherMember, 'member')
            ->postJson(route('member.posts.like', $post));

        $likeResponse->assertOk()
            ->assertJsonPath('success', true);

        // 3. Comment on Post
        $commentResponse = $this->actingAs($otherMember, 'member')
            ->postJson(route('member.posts.comments.store', $post), [
                'comment' => 'Great post by verified author!',
            ]);

        $commentResponse->assertOk()
            ->assertJsonPath('success', true);

        // 4. Follow / Send connection request
        $followResponse = $this->actingAs($verifiedAuthor, 'member')
            ->postJson(route('member.friends.request', $otherMember));

        $followResponse->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_unverified_member_cannot_create_community(): void
    {
        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.community.store'), [
                'name' => 'Unverified Community Attempt',
                'category' => 'Technology',
                'visibility' => 'public',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('communities', [
            'name' => 'Unverified Community Attempt',
        ]);
    }

    public function test_unverified_member_cannot_join_community(): void
    {
        $verifiedOwner = $this->createVerifiedMember();
        $community = Community::create([
            'community_id' => 'comm_' . Str::random(10),
            'owner_id' => $verifiedOwner->id,
            'name' => 'Verified Community',
            'slug' => 'verified-community-' . Str::random(5),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.community.join', $community));

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('community_members', [
            'community_id' => $community->id,
            'member_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_cannot_create_business_page(): void
    {
        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.business-pages.store'), [
                'page_name' => 'Unverified Business Attempt',
                'category' => 'Binary MLM Plan',
                'visibility' => 'public',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('business_pages', [
            'page_name' => 'Unverified Business Attempt',
        ]);
    }

    public function test_unverified_member_cannot_follow_business_page(): void
    {
        $verifiedOwner = $this->createVerifiedMember();
        $businessPage = BusinessPage::create([
            'page_id' => 'bp_' . Str::random(10),
            'member_id' => $verifiedOwner->id,
            'page_name' => 'Verified Business Page',
            'page_username' => 'verifiedbiz_' . Str::random(5),
            'slug' => 'verified-biz-' . Str::random(5),
            'category' => 'Binary MLM Plan',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.business-pages.follow.toggle', $businessPage));

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('business_followers', [
            'business_page_id' => $businessPage->id,
            'member_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_cannot_join_business_page_team(): void
    {
        $verifiedOwner = $this->createVerifiedMember();
        $businessPage = BusinessPage::create([
            'page_id' => 'bp_' . Str::random(10),
            'member_id' => $verifiedOwner->id,
            'page_name' => 'Verified Business Page Team',
            'page_username' => 'verifiedteam_' . Str::random(5),
            'slug' => 'verified-team-' . Str::random(5),
            'category' => 'Binary MLM Plan',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $unverified = $this->createUnverifiedMember();

        $invitation = BusinessInvitation::create([
            'business_page_id' => $businessPage->id,
            'inviter_id' => $verifiedOwner->id,
            'invitee_id' => $unverified->id,
            'role' => 'editor',
            'status' => 'pending',
            'invite_code' => Str::random(12),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.business-invitations.accept', $invitation));

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('business_team_members', [
            'business_page_id' => $businessPage->id,
            'member_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_can_view_community_and_business_page(): void
    {
        $verifiedOwner = $this->createVerifiedMember();
        $unverified = $this->createUnverifiedMember();

        $community = Community::create([
            'community_id' => 'comm_' . Str::random(10),
            'owner_id' => $verifiedOwner->id,
            'name' => 'Viewable Community',
            'slug' => 'viewable-community-' . Str::random(5),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $businessPage = BusinessPage::create([
            'page_id' => 'bp_' . Str::random(10),
            'member_id' => $verifiedOwner->id,
            'page_name' => 'Viewable Business Page',
            'page_username' => 'viewbiz_' . Str::random(5),
            'slug' => 'viewable-biz-' . Str::random(5),
            'category' => 'Binary MLM Plan',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        // Unverified member can view Community index and details
        $this->actingAs($unverified, 'member')
            ->get(route('member.community.index'))
            ->assertOk();

        $this->actingAs($unverified, 'member')
            ->get(route('member.community.show', $community))
            ->assertOk();

        // Unverified member can view Business Page index and details
        $this->actingAs($unverified, 'member')
            ->get(route('member.business-pages.index'))
            ->assertOk();

        $this->actingAs($unverified, 'member')
            ->get(route('member.business-pages.show', $businessPage))
            ->assertOk();
    }

    public function test_verified_member_can_create_and_join_community(): void
    {
        $verifiedCreator = $this->createVerifiedMember();
        $verifiedJoiner = $this->createVerifiedMember();

        $createResponse = $this->actingAs($verifiedCreator, 'member')
            ->postJson(route('member.community.store'), [
                'name' => 'Verified Allowed Community',
                'category' => 'Technology',
                'visibility' => 'public',
            ]);

        $createResponse->assertOk()
            ->assertJsonPath('success', true);

        $community = Community::where('owner_id', $verifiedCreator->id)->firstOrFail();

        $joinResponse = $this->actingAs($verifiedJoiner, 'member')
            ->postJson(route('member.community.join', $community));

        $joinResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('community_members', [
            'community_id' => $community->id,
            'member_id' => $verifiedJoiner->id,
            'status' => 'accepted',
        ]);
    }

    public function test_verified_member_can_create_and_follow_business_page(): void
    {
        $verifiedCreator = $this->createVerifiedMember();
        $verifiedFollower = $this->createVerifiedMember();

        $createResponse = $this->actingAs($verifiedCreator, 'member')
            ->postJson(route('member.business-pages.store'), [
                'page_name' => 'Verified Allowed Biz',
                'category' => 'Binary MLM Plan',
                'visibility' => 'public',
                'description' => 'A valid description for the business page.',
                'email' => 'biz' . random_int(1000, 9999) . '@example.com',
                'phone_country_code' => '+91',
                'phone_number' => '9876543210',
                'country' => 'India',
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true);

        $businessPage = BusinessPage::where('member_id', $verifiedCreator->id)->firstOrFail();

        $followResponse = $this->actingAs($verifiedFollower, 'member')
            ->postJson(route('member.business-pages.follow.toggle', $businessPage));

        $followResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_following', true);

        $this->assertDatabaseHas('business_followers', [
            'business_page_id' => $businessPage->id,
            'member_id' => $verifiedFollower->id,
            'status' => 'accepted',
        ]);
    }

    public function test_unverified_member_cannot_write_business_review(): void
    {
        $verifiedOwner = $this->createVerifiedMember();
        $businessPage = BusinessPage::create([
            'page_id' => 'bp_' . Str::random(10),
            'member_id' => $verifiedOwner->id,
            'page_name' => 'Verified Business Page Review',
            'page_username' => 'verifiedreview_' . Str::random(5),
            'slug' => 'verified-review-' . Str::random(5),
            'category' => 'Binary MLM Plan',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.business-pages.reviews.store', $businessPage), [
                'rating' => 5,
                'recommendation' => 'recommend',
                'title' => 'Great Company',
                'body' => 'Attempting unverified review.',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('business_reviews', [
            'business_page_id' => $businessPage->id,
            'member_id' => $unverified->id,
        ]);
    }

    public function test_unverified_member_cannot_vote_on_business_review(): void
    {
        $verifiedOwner = $this->createVerifiedMember();
        $reviewer = $this->createVerifiedMember();
        $businessPage = BusinessPage::create([
            'page_id' => 'bp_' . Str::random(10),
            'member_id' => $verifiedOwner->id,
            'page_name' => 'Verified Business Page Review Vote',
            'page_username' => 'verifiedvote_' . Str::random(5),
            'slug' => 'verified-vote-' . Str::random(5),
            'category' => 'Binary MLM Plan',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $review = BusinessReview::create([
            'business_page_id' => $businessPage->id,
            'member_id' => $reviewer->id,
            'rating' => 5,
            'recommendation' => 'recommend',
            'title' => 'Top notch service',
            'body' => 'High quality review body content.',
            'status' => 'approved',
        ]);

        $unverified = $this->createUnverifiedMember();

        $response = $this->actingAs($unverified, 'member')
            ->postJson(route('member.business-pages.reviews.vote', [$businessPage, $review]), [
                'vote_type' => 'helpful',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::EXACT_MESSAGE);

        $this->assertDatabaseMissing('business_review_votes', [
            'business_review_id' => $review->id,
            'member_id' => $unverified->id,
        ]);
    }

    private function createUnverifiedMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Unverified User',
            'user_id' => 'unverified_' . random_int(1000, 9999),
            'email' => 'unverified' . random_int(1000, 9999) . '@example.com',
            'password' => 'password',
            'phone' => null,
            'mobile_verified_at' => null,
        ], $attributes));
    }

    private function createVerifiedMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Verified User',
            'user_id' => 'verified_' . random_int(1000, 9999),
            'email' => 'verified' . random_int(1000, 9999) . '@example.com',
            'password' => 'password',
            'phone' => '+91987654' . random_int(1000, 9999),
            'mobile_verified_at' => now(),
        ], $attributes));
    }
}
