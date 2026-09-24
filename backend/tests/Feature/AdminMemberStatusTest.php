<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class AdminMemberStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);
    }

    public function test_active_members_page_is_accessible(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.members.index'));
        $response->assertStatus(200);
        $response->assertSee('Active Members Management');
    }

    public function test_pending_requests_page_is_accessible(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.members.pending'));
        $response->assertStatus(200);
        $response->assertSee('Pending Member Requests');
    }

    public function test_blocked_members_page_is_accessible(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.members.blocked'));
        $response->assertStatus(200);
        $response->assertSee('Blocked Member Accounts');
    }

    public function test_admin_can_block_and_unblock_member(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test2@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'John Doe',
            'user_id' => 'johndoe_1',
            'email' => 'john@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        // 1. Block
        $response = $this->actingAs($admin, 'admin')->post(route('admin.members.block', $member));
        $response->assertSessionHas('success');
        $member->refresh();
        $this->assertNotNull($member->blocked_at);
        $this->assertTrue($member->isBlocked());

        // 2. Unblock
        $response = $this->actingAs($admin, 'admin')->post(route('admin.members.unblock', $member));
        $response->assertSessionHas('success');
        $member->refresh();
        $this->assertNull($member->blocked_at);
        $this->assertFalse($member->isBlocked());
    }

    public function test_admin_can_approve_and_reject_request(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test3@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Jane Doe',
            'user_id' => 'janedoe_1',
            'email' => 'jane@test.com',
            'password' => 'password123',
            'mobile_verified_at' => null,
        ]);

        // 1. Approve
        $response = $this->actingAs($admin, 'admin')->post(route('admin.members.approve', $member));
        $response->assertSessionHas('success');
        $member->refresh();
        $this->assertNotNull($member->mobile_verified_at);

        // 2. Reject
        $response = $this->actingAs($admin, 'admin')->post(route('admin.members.reject', $member));
        $response->assertSessionHas('info');
        $member->refresh();
        $this->assertNull($member->mobile_verified_at);
    }

    public function test_blocked_member_cannot_login(): void
    {
        $member = Member::create([
            'name' => 'Blocked Member',
            'user_id' => 'blocked_user',
            'email' => 'blocked@test.com',
            'password' => 'password123',
            'blocked_at' => now(),
        ]);

        // 1. Web login attempt fails with exact message
        $response = $this->post(route('member.login.submit'), [
            'email' => 'blocked@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Your account has been blocked. You cannot log in.',
        ]);
        $this->assertGuest('member');

        // 2. JSON login attempt fails with 403 and exact message
        $jsonResponse = $this->postJson(route('member.login.submit'), [
            'email' => 'blocked@test.com',
            'password' => 'password123',
        ]);

        $jsonResponse->assertStatus(403);
        $jsonResponse->assertJson([
            'message' => 'Your account has been blocked. You cannot log in.',
        ]);
        $this->assertGuest('member');
    }

    public function test_unblocking_member_restores_login_ability(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_restore@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Restored Member',
            'user_id' => 'restored_user',
            'email' => 'restored@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'blocked_at' => now(),
        ]);

        // Cannot login while blocked
        $resBlocked = $this->postJson(route('member.login.submit'), [
            'email' => 'restored@test.com',
            'password' => 'password123',
        ]);
        $resBlocked->assertStatus(403);

        // Admin unblocks member
        $unblockRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/members/{$member->id}/unblock");
        $unblockRes->assertStatus(200);

        // Member can now login successfully
        $loginRes = $this->post(route('member.login.submit'), [
            'email' => 'restored@test.com',
            'password' => 'password123',
        ]);
        $loginRes->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_active_session_is_terminated_by_middleware_if_member_is_blocked(): void
    {
        $member = Member::create([
            'name' => 'Active To Blocked',
            'user_id' => 'active_to_blocked',
            'email' => 'session_test@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'blocked_at' => now(), // marked as blocked
        ]);

        // Attempting to access protected member route while blocked
        $response = $this->actingAs($member, 'member')->get(route('member.dashboard'));

        $response->assertRedirect(route('member.login'));
        $response->assertSessionHasErrors([
            'email' => 'Your account has been blocked. You cannot log in.',
        ]);
        $this->assertGuest('member');
    }

    public function test_active_pending_blocked_datasets_are_mutually_exclusive(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test4@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $activeMember = Member::create([
            'name' => 'Active Person',
            'user_id' => 'active_person_1',
            'email' => 'active_person@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'blocked_at' => null,
        ]);

        $pendingMember = Member::create([
            'name' => 'Pending Person',
            'user_id' => 'pending_person_1',
            'email' => 'pending_person@test.com',
            'password' => 'password123',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
            'blocked_at' => null,
        ]);

        $blockedMember = Member::create([
            'name' => 'Blocked Person',
            'user_id' => 'blocked_person_1',
            'email' => 'blocked_person@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'blocked_at' => now(),
        ]);

        // 1. Active Page must contain ONLY Active member
        $resActive = $this->actingAs($admin, 'admin')->get(route('admin.members.index'));
        $resActive->assertSee($activeMember->user_id);
        $resActive->assertDontSee($pendingMember->user_id);
        $resActive->assertDontSee($blockedMember->user_id);

        // 2. Pending Page must contain ONLY Pending member
        $resPending = $this->actingAs($admin, 'admin')->get(route('admin.members.pending'));
        $resPending->assertSee($pendingMember->user_id);
        $resPending->assertDontSee($activeMember->user_id);
        $resPending->assertDontSee($blockedMember->user_id);

        // 3. Blocked Page must contain ONLY Blocked member
        $resBlocked = $this->actingAs($admin, 'admin')->get(route('admin.members.blocked'));
        $resBlocked->assertSee($blockedMember->user_id);
        $resBlocked->assertDontSee($activeMember->user_id);
        $resBlocked->assertDontSee($pendingMember->user_id);
    }

    public function test_admin_can_view_member_detail_with_all_tabs(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test5@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Detailed User',
            'user_id' => 'detailed_user',
            'email' => 'detailed@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.members.show', $member));
        $response->assertStatus(200);
        $response->assertSee('Overview & Info');
        $response->assertSee('Posts (0)');
        $response->assertSee('Stories (0)');
        $response->assertSee('Communities (0)');
        $response->assertSee('Marketplace (0)');
        $response->assertSee('Events (0)');
        $response->assertSee('Reports (0)');
        $response->assertSee('id="overview"', false);
        $response->assertSee('id="posts"', false);
        $response->assertSee('id="stories"', false);
        $response->assertSee('id="communities"', false);
        $response->assertSee('id="marketplace"', false);
        $response->assertSee('id="events"', false);
        $response->assertSee('id="reports"', false);
    }

    public function test_admin_can_update_post_content(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test6@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Author User',
            'user_id' => 'author_user',
            'email' => 'author@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $post = \App\Models\Post::create([
            'member_id' => $member->id,
            'body' => 'Original post body',
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.posts.update', $post), [
            'body' => 'Updated by Admin',
        ]);

        $response->assertSessionHas('success');
        $post->refresh();
        $this->assertEquals('Updated by Admin', $post->body);
    }

    public function test_admin_can_toggle_hide_post(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test7@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Author User 2',
            'user_id' => 'author_user_2',
            'email' => 'author2@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $post = \App\Models\Post::create([
            'member_id' => $member->id,
            'body' => 'Post to hide',
        ]);

        // 1. Hide
        $response = $this->actingAs($admin, 'admin')->post(route('admin.posts.toggle-hide', $post));
        $response->assertSessionHas('warning');
        $this->assertTrue(\App\Models\HiddenPost::where('post_id', $post->id)->where('member_id', $member->id)->exists());

        // 2. Unhide
        $response = $this->actingAs($admin, 'admin')->post(route('admin.posts.toggle-hide', $post));
        $response->assertSessionHas('success');
        $this->assertFalse(\App\Models\HiddenPost::where('post_id', $post->id)->where('member_id', $member->id)->exists());
    }

    public function test_admin_can_update_post_with_image_upload(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test8@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Author User 3',
            'user_id' => 'author_user_3',
            'email' => 'author3@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $post = \App\Models\Post::create([
            'member_id' => $member->id,
            'body' => 'Post to receive image',
        ]);

        $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('admin_upload.png', 400, 300);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.posts.update', $post), [
            'body' => 'Post with new admin image',
            'media' => $fakeImage,
        ]);

        $response->assertSessionHas('success');
        $post->refresh();
        $this->assertEquals('Post with new admin image', $post->body);
        $this->assertEquals('image', $post->media_type);
        $this->assertNotNull($post->media_path);
        $this->assertTrue(\Illuminate\Support\Facades\File::exists(public_path($post->media_path)));

        // Clean up uploaded test file
        if (\Illuminate\Support\Facades\File::exists(public_path($post->media_path))) {
            \Illuminate\Support\Facades\File::delete(public_path($post->media_path));
        }
    }

    public function test_admin_can_remove_post_media_attachment(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_test9@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Author User 4',
            'user_id' => 'author_user_4',
            'email' => 'author4@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $post = \App\Models\Post::create([
            'member_id' => $member->id,
            'body' => 'Post with media to remove',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/images/dummy.jpg',
        ]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.posts.update', $post), [
            'body' => 'Post after media removal',
            'remove_media' => '1',
        ]);

        $response->assertSessionHas('success');
        $post->refresh();
        $this->assertEquals('Post after media removal', $post->body);
        $this->assertNull($post->media_path);
        $this->assertNull($post->media_type);
    }

    public function test_admin_can_block_and_unblock_member_via_json_api(): void
    {
        $admin = Admin::create([
            'name' => 'API Admin',
            'email' => 'admin_api@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'API Member',
            'user_id' => 'api_member_1',
            'email' => 'api_member@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'blocked_at' => null,
        ]);

        // 1. Initial State
        $this->assertFalse($member->is_blocked);
        $this->assertEquals('active', $member->status);

        // 2. Block Member via JSON API
        $blockRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/block");

        $blockRes->assertStatus(200);
        $blockRes->assertJson([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'is_blocked' => true,
                'status' => 'blocked',
            ],
        ]);
        $this->assertNotNull($blockRes->json('member.blocked_at'));

        $member->refresh();
        $this->assertNotNull($member->blocked_at);
        $this->assertTrue($member->is_blocked);
        $this->assertEquals('blocked', $member->status);

        // 3. Unblock Member via JSON API
        $unblockRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/unblock");

        $unblockRes->assertStatus(200);
        $unblockRes->assertJson([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'is_blocked' => false,
                'status' => 'active',
            ],
        ]);
        $this->assertNull($unblockRes->json('member.blocked_at'));

        $member->refresh();
        $this->assertNull($member->blocked_at);
        $this->assertFalse($member->is_blocked);
        $this->assertEquals('active', $member->status);
    }

    public function test_member_details_endpoint_returns_accurate_block_state_independent_of_verification(): void
    {
        $admin = Admin::create([
            'name' => 'Details Admin',
            'email' => 'details_admin@test.com',
            'password' => '123456',
            'status' => 'active',
        ]);

        // Verified member
        $member = Member::create([
            'name' => 'Tamish Pratap',
            'user_id' => 'tamish_test',
            'email' => 'tamish@test.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'blocked_at' => null,
        ]);

        // Check active details
        $resActive = $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/members/{$member->id}");

        $resActive->assertStatus(200);
        $resActive->assertJsonPath('member.is_verified', true);
        $resActive->assertJsonPath('member.is_blocked', false);
        $resActive->assertJsonPath('member.status', 'active');

        // Block member
        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/block")
            ->assertStatus(200);

        // Check blocked details: verification status must REMAIN independent (true) while is_blocked is true!
        $resBlocked = $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/members/{$member->id}");

        $resBlocked->assertStatus(200);
        $resBlocked->assertJsonPath('member.is_verified', true);
        $resBlocked->assertJsonPath('member.is_blocked', true);
        $resBlocked->assertJsonPath('member.status', 'blocked');

        // Unblock member
        $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/unblock")
            ->assertStatus(200);

        // Check unblocked details
        $resRestored = $this->actingAs($admin, 'admin')
            ->getJson("/api/admin/members/{$member->id}");

        $resRestored->assertStatus(200);
        $resRestored->assertJsonPath('member.is_verified', true);
        $resRestored->assertJsonPath('member.is_blocked', false);
        $resRestored->assertJsonPath('member.status', 'active');
    }
}
