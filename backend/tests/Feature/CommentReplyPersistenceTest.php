<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReplyPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, string $userId, bool $verified = true): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => $userId,
            'email' => "{$userId}@example.com",
            'password' => 'password123',
            'mobile_verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_comment_reply_persistence_and_retrieval_flow(): void
    {
        $author = $this->createMember('Alice Author', 'alice1');
        $replier = $this->createMember('Bob Replier', 'bob1');

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Post to test comment reply persistence',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Initial comment by author',
        ]);

        // 1. Submit reply via API
        $replyResponse = $this->actingAs($replier, 'member')
            ->postJson("/api/member/comments/{$comment->id}/replies", [
                'comment' => 'This is a test reply from Bob',
            ]);

        $replyResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('parent_id', $comment->id)
            ->assertJsonPath('replies_count', 1);

        $replyId = $replyResponse->json('reply_id');
        $this->assertNotNull($replyId);

        // 2. Verify database persistence
        $this->assertDatabaseHas('post_comments', [
            'id' => $replyId,
            'post_id' => $post->id,
            'member_id' => $replier->id,
            'parent_id' => $comment->id,
            'comment' => 'This is a test reply from Bob',
        ]);

        // 3. Verify comments API returns replies on fetch (simulating page reload)
        $commentsResponse = $this->actingAs($author, 'member')
            ->getJson("/api/member/posts/{$post->id}/comments");

        $commentsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('comments_count', 1);

        $comments = $commentsResponse->json('comments');
        $this->assertCount(1, $comments);
        $this->assertEquals(1, $comments[0]['replies_count']);
        $this->assertCount(1, $comments[0]['replies']);
        $this->assertEquals($replyId, $comments[0]['replies'][0]['id']);
        $this->assertEquals('This is a test reply from Bob', $comments[0]['replies'][0]['comment']);
        $this->assertEquals($replier->id, $comments[0]['replies'][0]['member_id']);
        $this->assertEquals($replier->name, $comments[0]['replies'][0]['member']['name']);
    }

    public function test_multiple_replies_under_same_comment_chronological_order(): void
    {
        $author = $this->createMember('Alice Author', 'alice2');
        $replier1 = $this->createMember('Bob Replier', 'bob2');
        $replier2 = $this->createMember('Charlie Replier', 'charlie2');

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Post with multiple replies',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Root discussion thread',
        ]);

        $this->actingAs($replier1, 'member')
            ->postJson("/api/member/comments/{$comment->id}/replies", [
                'comment' => 'First reply',
            ])->assertOk();

        $this->actingAs($replier2, 'member')
            ->postJson("/api/member/comments/{$comment->id}/replies", [
                'comment' => 'Second reply',
            ])->assertOk();

        $this->actingAs($author, 'member')
            ->postJson("/api/member/comments/{$comment->id}/replies", [
                'comment' => 'Third reply by author',
            ])->assertOk();

        $commentsResponse = $this->actingAs($author, 'member')
            ->getJson("/api/member/posts/{$post->id}/comments");

        $commentsResponse->assertOk();
        $comments = $commentsResponse->json('comments');
        $this->assertCount(1, $comments);
        $this->assertEquals(3, $comments[0]['replies_count']);
        $this->assertCount(3, $comments[0]['replies']);
        $this->assertEquals('First reply', $comments[0]['replies'][0]['comment']);
        $this->assertEquals('Second reply', $comments[0]['replies'][1]['comment']);
        $this->assertEquals('Third reply by author', $comments[0]['replies'][2]['comment']);
    }

    public function test_multiple_comments_each_with_replies(): void
    {
        $author = $this->createMember('Alice Author', 'alice3');
        $user1 = $this->createMember('User One', 'user1');

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Post with two comment threads',
        ]);

        $comment1 = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Comment 1',
        ]);

        $comment2 = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $user1->id,
            'comment' => 'Comment 2',
        ]);

        $this->actingAs($user1, 'member')
            ->postJson("/api/member/comments/{$comment1->id}/replies", [
                'comment' => 'Reply to Comment 1',
            ])->assertOk();

        $this->actingAs($author, 'member')
            ->postJson("/api/member/comments/{$comment2->id}/replies", [
                'comment' => 'Reply to Comment 2',
            ])->assertOk();

        $commentsResponse = $this->actingAs($author, 'member')
            ->getJson("/api/member/posts/{$post->id}/comments");

        $commentsResponse->assertOk()
            ->assertJsonPath('comments_count', 2);

        $comments = collect($commentsResponse->json('comments'))->keyBy('id');
        $this->assertEquals(1, $comments[$comment1->id]['replies_count']);
        $this->assertEquals('Reply to Comment 1', $comments[$comment1->id]['replies'][0]['comment']);
        $this->assertEquals(1, $comments[$comment2->id]['replies_count']);
        $this->assertEquals('Reply to Comment 2', $comments[$comment2->id]['replies'][0]['comment']);
    }

    public function test_single_post_detail_endpoint_includes_comments_and_replies(): void
    {
        $author = $this->createMember('Alice Author', 'alice4');
        $replier = $this->createMember('Bob Replier', 'bob4');

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Single post view check',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Root comment',
        ]);

        $this->actingAs($replier, 'member')
            ->postJson("/api/member/comments/{$comment->id}/replies", [
                'comment' => 'Nested reply',
            ])->assertOk();

        $postDetailResponse = $this->actingAs($author, 'member')
            ->getJson("/api/member/posts/{$post->id}");

        $postDetailResponse->assertOk()
            ->assertJsonPath('success', true);

        $loadedPost = $postDetailResponse->json('post');
        $this->assertNotEmpty($loadedPost['comments']);
        $this->assertEquals(1, $loadedPost['comments'][0]['replies_count']);
        $this->assertCount(1, $loadedPost['comments'][0]['replies']);
        $this->assertEquals('Nested reply', $loadedPost['comments'][0]['replies'][0]['comment']);
    }

    public function test_unverified_member_cannot_post_reply(): void
    {
        $author = $this->createMember('Alice Author', 'alice5');
        $unverified = $this->createMember('Unverified User', 'unverified1', verified: false);

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Post to test unverified restrictions',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Initial comment',
        ]);

        $response = $this->actingAs($unverified, 'member')
            ->postJson("/api/member/comments/{$comment->id}/replies", [
                'comment' => 'Unauthorized reply attempt',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('post_comments', [
            'comment' => 'Unauthorized reply attempt',
        ]);
    }

    public function test_reply_to_reply_is_flattened_to_root_parent(): void
    {
        $author = $this->createMember('Alice Author', 'alice6');
        $user1 = $this->createMember('Bob Replier', 'bob6');
        $user2 = $this->createMember('Charlie Replier', 'charlie6');

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Post to test reply flattening',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Root thread',
        ]);

        // Reply 1 under Root
        $reply1 = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $user1->id,
            'parent_id' => $comment->id,
            'comment' => 'First level reply',
        ]);

        // Reply to Reply 1 via API
        $response = $this->actingAs($user2, 'member')
            ->postJson("/api/member/comments/{$reply1->id}/replies", [
                'comment' => 'Replying to first level reply',
            ]);

        $response->assertOk()
            ->assertJsonPath('parent_id', $comment->id);

        $reply2Id = $response->json('reply_id');
        $this->assertDatabaseHas('post_comments', [
            'id' => $reply2Id,
            'parent_id' => $comment->id, // Flattened to root comment
            'comment' => 'Replying to first level reply',
        ]);
    }

    public function test_replies_pagination_endpoint(): void
    {
        $author = $this->createMember('Alice Author', 'alice7');
        $replier = $this->createMember('Bob Replier', 'bob7');

        $post = Post::create([
            'member_id' => $author->id,
            'body' => 'Post to test replies endpoint',
        ]);

        $comment = PostComment::create([
            'post_id' => $post->id,
            'member_id' => $author->id,
            'comment' => 'Discussion topic',
        ]);

        for ($i = 1; $i <= 12; $i++) {
            PostComment::create([
                'post_id' => $post->id,
                'member_id' => $replier->id,
                'parent_id' => $comment->id,
                'comment' => "Reply #{$i}",
            ]);
        }

        $response = $this->actingAs($author, 'member')
            ->getJson("/api/member/comments/{$comment->id}/replies?offset=0");

        $response->assertOk()
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('replies_count', 12);

        $this->assertCount(10, $response->json('replies'));
    }
}