<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminMemberWhatsAppVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);

        $this->admin = Admin::create([
            'name' => 'System Admin',
            'email' => 'admin_test@mlmbook.test',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    /**
     * 1. Unauthenticated user cannot access pending queue.
     */
    public function test_unauthenticated_user_cannot_access_pending_queue(): void
    {
        $response = $this->getJson('/api/admin/members/pending');
        $response->assertStatus(401);
    }

    /**
     * 2. Ordinary member cannot access admin pending queue.
     */
    public function test_ordinary_member_cannot_access_admin_pending_queue(): void
    {
        $member = Member::create([
            'name' => 'Ordinary Member',
            'user_id' => 'ordinary_member',
            'email' => 'ordinary@test.com',
            'password' => 'password',
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($member, 'web')->getJson('/api/admin/members/pending');
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    /**
     * 3. Ordinary member cannot call admin approval endpoint.
     */
    public function test_ordinary_member_cannot_call_admin_approval_endpoint(): void
    {
        $member = Member::create([
            'name' => 'Ordinary Member',
            'user_id' => 'ordinary_member_2',
            'email' => 'ordinary2@test.com',
            'password' => 'password',
            'phone' => '+919876543210',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($member, 'web')->postJson("/api/admin/members/{$member->id}/approve");
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    /**
     * 4. Authorized Admin can retrieve pending verification queue.
     */
    public function test_authorized_admin_can_retrieve_pending_queue(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/pending');
        $response->assertOk()
            ->assertJsonStructure([
                'members',
                'totalCount',
                'verifiedCount',
                'unverifiedCount',
                'activeCount',
                'pendingCount',
            ]);
    }

    /**
     * 5. Pending query contains ONLY unverified members who requested verification.
     */
    public function test_pending_queue_filters_only_unverified_members_with_verification_request(): void
    {
        // Member 1: Pending (unverified WITH request) -> MUST appear
        $pendingMember = Member::create([
            'name' => 'Pending Verification Member',
            'user_id' => 'pending_user_1',
            'email' => 'pending1@test.com',
            'password' => 'password',
            'phone' => '+919876543211',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        // Member 2: Unverified WITHOUT request -> MUST NOT appear
        $unverifiedNoRequest = Member::create([
            'name' => 'Unverified No Request',
            'user_id' => 'unverified_no_req',
            'email' => 'noreq@test.com',
            'password' => 'password',
            'phone' => '+919876543212',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ]);

        // Member 3: Already Verified -> MUST NOT appear
        $verifiedMember = Member::create([
            'name' => 'Already Verified User',
            'user_id' => 'verified_user',
            'email' => 'verified@test.com',
            'password' => 'password',
            'phone' => '+919876543213',
            'mobile_verified_at' => now(),
            'mobile_verification_requested_at' => null,
        ]);

        // Member 4: Blocked Member -> MUST NOT appear
        $blockedMember = Member::create([
            'name' => 'Blocked Member',
            'user_id' => 'blocked_user',
            'email' => 'blocked@test.com',
            'password' => 'password',
            'phone' => '+919876543214',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
            'blocked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/pending');

        $response->assertOk();
        $data = $response->json('members.data');
        $this->assertCount(1, $data);
        $this->assertSame('pending_user_1', $data[0]['user_id']);
        $this->assertSame(1, $response->json('pendingCount'));
    }

    /**
     * 6. Pending queue orders oldest-first by mobile_verification_requested_at.
     */
    public function test_pending_queue_orders_oldest_first(): void
    {
        $recentMember = Member::create([
            'name' => 'Recent Requester',
            'user_id' => 'recent_user',
            'email' => 'recent@test.com',
            'password' => 'password',
            'phone' => '+919876543220',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $oldestMember = Member::create([
            'name' => 'Oldest Requester',
            'user_id' => 'oldest_user',
            'email' => 'oldest@test.com',
            'password' => 'password',
            'phone' => '+919876543221',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now()->subHours(5),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/pending');

        $response->assertOk();
        $data = $response->json('members.data');
        $this->assertCount(2, $data);
        $this->assertSame('oldest_user', $data[0]['user_id']);
        $this->assertSame('recent_user', $data[1]['user_id']);
    }

    /**
     * 7. Pending queue returns unmasked registered members.phone.
     */
    public function test_pending_queue_returns_unmasked_registered_phone(): void
    {
        Member::create([
            'name' => 'Phone Check Member',
            'user_id' => 'phone_check_user',
            'email' => 'phone_check@test.com',
            'password' => 'password',
            'phone' => '+919876543230',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/pending');

        $response->assertOk();
        $data = $response->json('members.data');
        $this->assertSame('+919876543230', $data[0]['phone']);
    }

    /**
     * 8. Admin can approve a valid pending member -> mobile_verified_at is set.
     */
    public function test_admin_can_approve_valid_pending_member(): void
    {
        $member = Member::create([
            'name' => 'Approve Me',
            'user_id' => 'approve_me',
            'email' => 'approve_me@test.com',
            'password' => 'password',
            'phone' => '+919876543240',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now()->subMinutes(10),
        ]);

        $this->assertFalse($member->isMobileVerified());

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $member->refresh();
        $this->assertTrue($member->isMobileVerified());
        $this->assertNotNull($member->mobile_verified_at);
    }

    /**
     * 9. Approval preserves registered members.phone unchanged.
     */
    public function test_approval_preserves_registered_phone_unchanged(): void
    {
        $member = Member::create([
            'name' => 'Phone Preserved',
            'user_id' => 'phone_preserved',
            'email' => 'phone_preserved@test.com',
            'password' => 'password',
            'phone' => '+919876543250',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");

        $response->assertOk();
        $member->refresh();
        $this->assertSame('+919876543250', $member->phone);
    }

    /**
     * 10. Approval preserves historical mobile_verification_requested_at.
     */
    public function test_approval_preserves_requested_at_timestamp(): void
    {
        $requestedAt = Carbon::parse('2026-09-18 10:00:00');

        $member = Member::create([
            'name' => 'Timestamp Preserved',
            'user_id' => 'ts_preserved',
            'email' => 'ts_preserved@test.com',
            'password' => 'password',
            'phone' => '+919876543260',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => $requestedAt,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");

        $response->assertOk();
        $member->refresh();
        $this->assertEquals($requestedAt->timestamp, $member->mobile_verification_requested_at->timestamp);
    }

    /**
     * 11. Approval qualifies introducer referral.
     */
    public function test_approval_qualifies_introducer_referral(): void
    {
        $sponsor = Member::create([
            'name' => 'Verified Sponsor',
            'user_id' => 'sponsor_verified',
            'email' => 'sponsor@test.com',
            'password' => 'password',
            'phone' => '+919876543270',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $newMember = Member::create([
            'name' => 'Downline Member',
            'user_id' => 'downline_member',
            'introducer_id' => 'sponsor_verified',
            'email' => 'downline@test.com',
            'password' => 'password',
            'phone' => '+919876543271',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$newMember->id}/approve");

        $response->assertOk();
        $sponsor->refresh();
        $newMember->refresh();

        $this->assertSame(1, $sponsor->direct_referral_count);
        $this->assertNotNull($newMember->referral_counted_at);
    }

    /**
     * 12. Approval dispatches system notification to member.
     */
    public function test_approval_dispatches_system_notification_to_member(): void
    {
        $member = Member::create([
            'name' => 'Notify Member',
            'user_id' => 'notify_member',
            'email' => 'notify@test.com',
            'password' => 'password',
            'phone' => '+919876543280',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");

        $response->assertOk();
        $this->assertCount(1, $member->notifications);
        $this->assertSame('WhatsApp Verification Approved', $member->notifications->first()->data['title']);
    }

    /**
     * 13. Approval is strictly idempotent (duplicate clicks do not overwrite timestamp or duplicate notifications).
     */
    public function test_approval_is_idempotent(): void
    {
        $member = Member::create([
            'name' => 'Idempotent User',
            'user_id' => 'idempotent_user',
            'email' => 'idempotent@test.com',
            'password' => 'password',
            'phone' => '+919876543290',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        // First approval
        $firstRes = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");
        $firstRes->assertOk();
        $member->refresh();
        $firstVerifiedAt = $member->mobile_verified_at;

        // Travel forward 2 hours
        $this->travel(2)->hours();

        // Second approval click
        $secondRes = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");
        $secondRes->assertOk()
            ->assertJsonPath('already_verified', true);

        $member->refresh();
        // Timestamp must NOT have changed
        $this->assertEquals($firstVerifiedAt->timestamp, $member->mobile_verified_at->timestamp);
        // Only 1 notification dispatched (no duplicates)
        $this->assertCount(1, $member->notifications);
    }

    /**
     * 14. Approval fails cleanly if member has no pending request.
     */
    public function test_approval_fails_cleanly_if_member_has_no_pending_request(): void
    {
        $member = Member::create([
            'name' => 'No Request Member',
            'user_id' => 'no_req_member',
            'email' => 'noreq_member@test.com',
            'password' => 'password',
            'phone' => '+919876543300',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null, // No request!
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Member has not submitted a WhatsApp verification request.');

        $member->refresh();
        $this->assertNull($member->mobile_verified_at);
    }

    /**
     * 15. Approval fails cleanly if member has no registered phone.
     */
    public function test_approval_fails_cleanly_if_member_has_no_registered_phone(): void
    {
        $member = Member::create([
            'name' => 'No Phone Member',
            'user_id' => 'no_phone_member',
            'email' => 'nophone@test.com',
            'password' => 'password',
            'phone' => null, // No phone!
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/approve");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Member does not have a registered WhatsApp/mobile number.');

        $member->refresh();
        $this->assertNull($member->mobile_verified_at);
    }

    /**
     * 16. Admin can reject/dismiss pending request -> request cleared, mobile_verified_at remains null.
     */
    public function test_admin_can_reject_or_dismiss_pending_request(): void
    {
        $member = Member::create([
            'name' => 'Reject Me',
            'user_id' => 'reject_me',
            'email' => 'reject_me@test.com',
            'password' => 'password',
            'phone' => '+919876543310',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/reject");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $member->refresh();
        $this->assertNull($member->mobile_verification_requested_at);
        $this->assertNull($member->mobile_verified_at);
        $this->assertFalse($member->isMobileVerified());
    }

    /**
     * 17. Rejection dispatches system notification to member.
     */
    public function test_rejection_dispatches_notification_to_member(): void
    {
        $member = Member::create([
            'name' => 'Reject Notify Member',
            'user_id' => 'reject_notify',
            'email' => 'reject_notify@test.com',
            'password' => 'password',
            'phone' => '+919876543320',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member->id}/reject");

        $response->assertOk();
        $this->assertCount(1, $member->notifications);
        $this->assertSame('WhatsApp Verification Update', $member->notifications->first()->data['title']);
    }

    /**
     * 18. Approval and rejection via alias routes work identically.
     */
    public function test_approval_and_rejection_via_alias_routes(): void
    {
        // Test approve-mobile-verification alias
        $member1 = Member::create([
            'name' => 'Alias Approve Member',
            'user_id' => 'alias_approve',
            'email' => 'alias_approve@test.com',
            'password' => 'password',
            'phone' => '+919876543330',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $approveRes = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member1->id}/approve-mobile-verification");
        $approveRes->assertOk()->assertJsonPath('success', true);
        $member1->refresh();
        $this->assertTrue($member1->isMobileVerified());

        // Test reject-mobile-verification alias
        $member2 = Member::create([
            'name' => 'Alias Reject Member',
            'user_id' => 'alias_reject',
            'email' => 'alias_reject@test.com',
            'password' => 'password',
            'phone' => '+919876543331',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        $rejectRes = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/members/{$member2->id}/reject-mobile-verification");
        $rejectRes->assertOk()->assertJsonPath('success', true);
        $member2->refresh();
        $this->assertNull($member2->mobile_verification_requested_at);
        $this->assertNull($member2->mobile_verified_at);
    }

    /**
     * 19. Verify active() and pending() endpoints have aligned pendingCount and proper segregation.
     */
    public function test_active_and_pending_endpoints_have_consistent_pending_count(): void
    {
        // Member A: unverified WITH request (pending WhatsApp queue)
        $requester = Member::create([
            'name' => 'Verification Requester',
            'user_id' => 'verif_requester',
            'email' => 'requester@test.com',
            'password' => 'password',
            'phone' => '+919876543340',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        // Member B: unverified WITHOUT request (general unverified, not in queue)
        $nonRequester = Member::create([
            'name' => 'No Request Member',
            'user_id' => 'no_request_user',
            'email' => 'norequest@test.com',
            'password' => 'password',
            'phone' => '+919876543341',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ]);

        // Member C: Verified member
        $verified = Member::create([
            'name' => 'Verified Member',
            'user_id' => 'verified_user_c',
            'email' => 'verified_c@test.com',
            'password' => 'password',
            'phone' => '+919876543342',
            'mobile_verified_at' => now(),
            'mobile_verification_requested_at' => null,
        ]);

        // Query active directory (Home)
        $activeRes = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/active');
        $activeRes->assertOk();
        $this->assertSame(1, $activeRes->json('pendingCount'), 'Home pendingCount must only count members who submitted verification requests');
        $this->assertSame(2, $activeRes->json('unverifiedCount'), 'Home unverifiedCount must count all unverified accounts');
        $this->assertSame(1, $activeRes->json('verifiedCount'));

        // Query pending queue (Unverified Members page)
        $pendingRes = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/pending');
        $pendingRes->assertOk();
        $this->assertSame(1, $pendingRes->json('pendingCount'), 'Pending queue pendingCount must match Home pendingCount');
        $pendingData = $pendingRes->json('members.data');
        $this->assertCount(1, $pendingData);
        $this->assertSame('verif_requester', $pendingData[0]['user_id']);

        // Query active directory filtered by status=unverified (All unverified accounts)
        $unverifiedFilterRes = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/members/active?status=unverified');
        $unverifiedFilterRes->assertOk();
        $unverifiedData = $unverifiedFilterRes->json('members.data');
        $this->assertCount(2, $unverifiedData, 'status=unverified must return both members with and without requests');
    }
}
