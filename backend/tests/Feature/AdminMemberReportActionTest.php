<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Models\ReportedPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMemberReportActionTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $member;
    protected Member $otherMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $this->member = Member::create([
            'name' => 'Target Member',
            'user_id' => 'target_member',
            'email' => 'target@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);

        $this->otherMember = Member::create([
            'name' => 'Other Member',
            'user_id' => 'other_member',
            'email' => 'other@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_admin_can_fetch_member_details_with_enriched_reports(): void
    {
        // 1. Post authored by target member that got reported by otherMember
        $post = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Post by target member',
        ]);

        $reportOnTarget = ReportedPost::create([
            'member_id' => $this->otherMember->id,
            'post_id' => $post->id,
            'reason' => ReportedPost::REASON_SPAM,
            'description' => 'Suspected promotional spam',
            'status' => 'pending',
        ]);

        // 2. Post authored by otherMember that was reported by target member
        $otherPost = Post::create([
            'member_id' => $this->otherMember->id,
            'body' => 'Post by other member',
        ]);

        $reportByTarget = ReportedPost::create([
            'member_id' => $this->member->id,
            'post_id' => $otherPost->id,
            'reason' => ReportedPost::REASON_HARASSMENT,
            'description' => 'Offensive comment',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/members/{$this->member->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('reports', $data);
        $this->assertCount(2, $data['reports']);

        $firstReport = $data['reports'][0];
        $this->assertEquals('post', $firstReport['type']);
        $this->assertEquals('Post', $firstReport['type_label']);
        $this->assertArrayHasKey('target_id', $firstReport);
        $this->assertArrayHasKey('created_at_human', $firstReport);
    }

    public function test_member_details_does_not_crash_when_friend_account_is_null_or_deleted(): void
    {
        $tempMember = Member::create([
            'name' => 'Temp Friend',
            'user_id' => 'temp_friend',
            'email' => 'temp_friend@test.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        Friendship::create([
            'member_one_id' => $this->member->id,
            'member_two_id' => $tempMember->id,
            'requested_by_id' => $this->member->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Delete the friend to simulate an orphaned connection
        $tempMember->delete();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/members/{$this->member->id}");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('connections', $data);
        $this->assertCount(0, $data['connections']);
    }

    public function test_can_update_report_status_via_api(): void
    {
        $post = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Some post',
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->otherMember->id,
            'post_id' => $post->id,
            'reason' => ReportedPost::REASON_SPAM,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/reports/post/{$report->id}/status", [
                'status' => 'resolved',
                'notes' => 'Reviewed and approved',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals('resolved', $report->fresh()->status);
    }

    public function test_invalid_report_id_returns_clean_404_not_500_type_error(): void
    {
        // Simulates previously broken call: POST /reports/1/resolved/status
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/reports/1/resolved/status", [
                'status' => 'resolved',
            ]);

        // Must return 404 cleanly, NOT 500 fatal TypeError
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_non_existent_report_id_returns_404(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/reports/post/999999/status", [
                'status' => 'resolved',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_delete_report_ticket_via_api(): void
    {
        $post = Post::create([
            'member_id' => $this->member->id,
            'body' => 'Post to keep',
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->otherMember->id,
            'post_id' => $post->id,
            'reason' => ReportedPost::REASON_OTHER,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/admin/reports/post/{$report->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('reported_posts', ['id' => $report->id]);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
