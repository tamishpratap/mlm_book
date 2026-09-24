<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Post;
use App\Models\ReportedPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class AdminPostsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);

        $this->admin = Admin::first() ?? Admin::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $this->member = Member::create([
            'name' => 'Test Author',
            'user_id' => 'testauthor_' . uniqid(),
            'email' => 'author_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_01_admin_can_fetch_unfiltered_posts_list(): void
    {
        Post::create([
            'member_id' => $this->member->id,
            'body' => 'Post One Content',
            'media_type' => null,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'posts' => [
                'data',
                'total',
                'current_page',
            ],
            'totalCount',
            'imageCount',
            'videoCount',
            'reportedCount',
        ]);
    }

    public function test_02_admin_can_filter_posts_by_search_keyword(): void
    {
        Post::create([
            'member_id' => $this->member->id,
            'body' => 'TargetKeyword special announcement',
        ]);
        Post::create([
            'member_id' => $this->member->id,
            'body' => 'Completely unrelated text',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts?q=TargetKeyword');

        $response->assertStatus(200);
        $data = $response->json('posts.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('TargetKeyword', $data[0]['body']);
    }

    public function test_03_admin_can_filter_posts_by_media_type(): void
    {
        Post::create([
            'member_id' => $this->member->id,
            'body' => 'Post with image',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/test.jpg',
        ]);
        Post::create([
            'member_id' => $this->member->id,
            'body' => 'Post with video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/test.mp4',
        ]);
        Post::create([
            'member_id' => $this->member->id,
            'body' => 'Text only post',
            'media_type' => null,
        ]);

        // Filter image
        $resImage = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts?media_type=image');
        $resImage->assertStatus(200);
        $this->assertCount(1, $resImage->json('posts.data'));
        $this->assertSame('image', $resImage->json('posts.data.0.media_type'));

        // Filter video
        $resVideo = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts?media_type=video');
        $resVideo->assertStatus(200);
        $this->assertCount(1, $resVideo->json('posts.data'));
        $this->assertSame('video', $resVideo->json('posts.data.0.media_type'));

        // Filter text only
        $resText = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts?media_type=text');
        $resText->assertStatus(200);
        $this->assertCount(1, $resText->json('posts.data'));
    }

    public function test_04_admin_can_filter_posts_by_reported_status(): void
    {
        $reportedPost = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Problematic post',
        ]);
        ReportedPost::create([
            'member_id' => $this->member->id,
            'post_id' => $reportedPost->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $cleanPost = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Clean post',
        ]);

        $resReported = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts?has_reports=yes');
        $resReported->assertStatus(200);
        $this->assertCount(1, $resReported->json('posts.data'));
        $this->assertEquals($reportedPost->id, $resReported->json('posts.data.0.id'));

        $resClean = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/posts?has_reports=no');
        $resClean->assertStatus(200);
        $this->assertCount(1, $resClean->json('posts.data'));
        $this->assertEquals($cleanPost->id, $resClean->json('posts.data.0.id'));
    }
}
