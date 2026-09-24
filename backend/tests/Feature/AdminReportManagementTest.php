<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BusinessReview;
use App\Models\BusinessReviewReport;
use App\Models\Community;
use App\Models\CommunityReport;
use App\Models\Member;
use App\Models\Post;
use App\Models\Product;
use App\Models\ReportedPost;
use App\Models\ReportedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $author;
    protected Member $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->author = Member::create([
            'name' => 'John Author',
            'email' => 'author@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $this->reporter = Member::create([
            'name' => 'Jane Reporter',
            'email' => 'reporter@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);
    }

    public function test_can_list_reports_with_reason_filter(): void
    {
        $post1 = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Spam content here',
            'media_type' => 'image',
            'media_path' => 'posts/spam.jpg',
        ]);

        $post2 = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Harassment content here',
        ]);

        ReportedPost::create([
            'post_id' => $post1->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        ReportedPost::create([
            'post_id' => $post2->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Harassment',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/reports?reason=Spam');

        $response->assertStatus(200);
        $data = $response->json('reports');
        $this->assertCount(1, $data);
        $this->assertEquals('Spam', $data[0]['reason']);
        $this->assertEquals($post1->id, $data[0]['target_id']);
        $this->assertNotNull($data[0]['target_media_url']);
    }

    public function test_can_view_post_report_details(): void
    {
        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Detailed test post',
            'media_type' => 'image',
            'media_path' => 'posts/sample.jpg',
        ]);

        $report = ReportedPost::create([
            'post_id' => $post->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'description' => 'Repeated unsolicited advertising',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/reports/post/{$report->id}");

        $response->assertStatus(200);
        $json = $response->json('reportData');
        $this->assertEquals($report->id, $json['id']);
        $this->assertEquals('post', $json['type']);
        $this->assertEquals('Spam', $json['reason']);
        $this->assertNotNull($json['target']);
        $this->assertEquals($this->author->id, $json['target']['member']['id']);
        $this->assertNotNull($json['target']['media_url']);
        $this->assertEquals('posts/sample.jpg', $json['target']['media_path']);
    }

    public function test_can_view_post_report_details_for_reshared_post_with_original_media(): void
    {
        $originalPost = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Original post with image',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/original_test.png',
        ]);

        $resharedPost = Post::create([
            'member_id' => $this->author->id,
            'original_post_id' => $originalPost->id,
            'body' => 'Check out this reshared image',
            'media_type' => null,
            'media_path' => null,
        ]);

        $report = ReportedPost::create([
            'post_id' => $resharedPost->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Inappropriate Content',
            'description' => 'Image in shared post is offensive',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/reports/post/{$report->id}");

        $response->assertStatus(200);
        $json = $response->json('reportData');
        $this->assertEquals($report->id, $json['id']);
        $this->assertTrue($json['target']['is_shared']);
        $this->assertEquals('image', $json['target']['media_type']);
        $this->assertEquals('uploads/posts/images/original_test.png', $json['target']['media_path']);
        $this->assertNotNull($json['target']['media_url']);
        $this->assertNotNull($json['target']['original_post']);
        $this->assertEquals($originalPost->id, $json['target']['original_post']['id']);
        $this->assertEquals('uploads/posts/images/original_test.png', $json['target']['original_post']['media_path']);
    }

    public function test_can_update_report_status_to_resolved_or_dismissed(): void
    {
        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Test post for status update',
        ]);

        $report = ReportedPost::create([
            'post_id' => $post->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        // Resolve
        $res = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/reports/post/{$report->id}/status", [
                'status' => 'resolved',
            ]);
        $res->assertStatus(200);
        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertNotNull(Post::find($post->id)); // Post is NOT deleted!

        // Dismiss
        $res = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/reports/post/{$report->id}/status", [
                'status' => 'dismissed',
            ]);
        $res->assertStatus(200);
        $this->assertEquals('dismissed', $report->fresh()->status);
    }

    public function test_can_delete_single_report_ticket_without_deleting_target(): void
    {
        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Post remains intact after ticket deleted',
        ]);

        $report = ReportedPost::create([
            'post_id' => $post->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/reports/post/{$report->id}");

        $res->assertStatus(200);
        $this->assertNull(ReportedPost::find($report->id)); // Report ticket is deleted
        $this->assertNotNull(Post::find($post->id)); // Post is preserved!
        $this->assertNotNull(Member::find($this->author->id)); // Author preserved!
    }

    public function test_can_delete_reported_target_post(): void
    {
        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Harmful post to delete',
        ]);

        $report = ReportedPost::create([
            'post_id' => $post->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/reports/post/{$report->id}/target");

        $res->assertStatus(200);
        $this->assertNull(Post::find($post->id)); // Target post is deleted
        $this->assertNull(ReportedPost::find($report->id)); // Cascaded
        $this->assertNotNull(Member::find($this->author->id)); // Author account is SAFE!
        $this->assertNotNull(Member::find($this->reporter->id)); // Reporter account is SAFE!
    }

    public function test_bulk_action_supports_composite_keys_and_actions(): void
    {
        $post1 = Post::create(['member_id' => $this->author->id, 'body' => 'Post 1']);
        $post2 = Post::create(['member_id' => $this->author->id, 'body' => 'Post 2']);

        $report1 = ReportedPost::create([
            'post_id' => $post1->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $report2 = ReportedPost::create([
            'post_id' => $post2->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        // Bulk resolve
        $res = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/reports/bulk-action', [
                'action' => 'resolve',
                'items' => ["post_{$report1->id}"],
            ]);
        $res->assertStatus(200);
        $this->assertEquals('resolved', $report1->fresh()->status);

        // Bulk dismiss
        $res = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/reports/bulk-action', [
                'action' => 'dismiss',
                'items' => ["post_{$report1->id}"],
            ]);
        $res->assertStatus(200);
        $this->assertEquals('dismissed', $report1->fresh()->status);

        // Bulk delete tickets
        $res = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/reports/bulk-action', [
                'action' => 'delete',
                'items' => ["post_{$report2->id}"],
            ]);
        $res->assertStatus(200);
        $this->assertNull(ReportedPost::find($report2->id));
        $this->assertNotNull(Post::find($post2->id)); // Content preserved
    }

    public function test_can_export_reports(): void
    {
        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Post for export',
        ]);

        ReportedPost::create([
            'post_id' => $post->id,
            'member_id' => $this->reporter->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'admin')
            ->get('/api/admin/reports/export');

        $res->assertStatus(200);
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
    }
}
