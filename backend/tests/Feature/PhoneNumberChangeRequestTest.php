<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\PhoneNumberChangeRequest;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneNumberChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $unverifiedMember;
    protected Member $verifiedMember;

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

        $this->unverifiedMember = Member::create([
            'name' => 'Unverified User',
            'user_id' => 'unverified_user',
            'email' => 'unverified@test.com',
            'password' => 'password',
            'phone' => '+919876543201',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => null,
        ]);

        $this->verifiedMember = Member::create([
            'name' => 'Verified User',
            'user_id' => 'verified_user',
            'email' => 'verified@test.com',
            'password' => 'password',
            'phone' => '+919876543202',
            'mobile_verified_at' => now()->subDays(10),
            'mobile_verification_requested_at' => now()->subDays(10),
        ]);
    }

    /**
     * 1. Unverified member can update phone number.
     */
    public function test_unverified_member_can_update_phone_number(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->putJson('/api/member/account/unverified-phone', [
                'phone' => '+919876543299',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('phone', '+919876543299');

        $this->unverifiedMember->refresh();
        $this->assertSame('+919876543299', $this->unverifiedMember->phone);
        $this->assertNull($this->unverifiedMember->mobile_verified_at);
        $this->assertNull($this->unverifiedMember->mobile_verification_requested_at);
    }

    /**
     * 2. Verified member cannot update phone directly via unverified endpoint.
     */
    public function test_verified_member_cannot_update_phone_directly_via_unverified_endpoint(): void
    {
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->putJson('/api/member/account/unverified-phone', [
                'phone' => '+919876543299',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->verifiedMember->refresh();
        $this->assertSame('+919876543202', $this->verifiedMember->phone);
    }

    /**
     * 3. Unverified phone update validates format and uniqueness.
     */
    public function test_unverified_phone_update_validates_format_and_uniqueness(): void
    {
        // Duplicate phone
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->putJson('/api/member/account/unverified-phone', [
                'phone' => $this->verifiedMember->phone,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        // Invalid regex format
        $response2 = $this->actingAs($this->unverifiedMember, 'member')
            ->putJson('/api/member/account/unverified-phone', [
                'phone' => 'abc',
            ]);

        $response2->assertStatus(422);
    }

    /**
     * 4. Verified member can request phone number change without altering active phone.
     */
    public function test_verified_member_can_request_phone_number_change(): void
    {
        $oldPhone = $this->verifiedMember->phone;
        $newPhone = '+919988776655';

        $response = $this->actingAs($this->verifiedMember, 'member')
            ->postJson('/api/member/account/phone-change-request', [
                'new_phone' => $newPhone,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('change_request.old_phone', $oldPhone)
            ->assertJsonPath('change_request.new_phone', $newPhone)
            ->assertJsonPath('change_request.status', 'pending');

        // Check WhatsApp message contains PHONE NUMBER CHANGE REQUEST
        $whatsappUrl = $response->json('whatsapp_url');
        $this->assertStringContainsString('PHONE%20NUMBER%20CHANGE%20REQUEST', $whatsappUrl);
        $this->assertStringContainsString(rawurlencode($oldPhone), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode($newPhone), $whatsappUrl);

        // CRITICAL INVARIANT: members.phone MUST NOT change until admin approves!
        $this->verifiedMember->refresh();
        $this->assertSame($oldPhone, $this->verifiedMember->phone);
        $this->assertNotNull($this->verifiedMember->mobile_verified_at);

        $this->assertDatabaseHas('phone_number_change_requests', [
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $oldPhone,
            'new_phone' => $newPhone,
            'status' => 'pending',
        ]);
    }

    /**
     * 5. Cannot create duplicate active pending phone change request.
     */
    public function test_cannot_create_duplicate_active_pending_change_request(): void
    {
        PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->verifiedMember, 'member')
            ->postJson('/api/member/account/phone-change-request', [
                'new_phone' => '+919988776611',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * 6. Cannot request same phone as current verified number.
     */
    public function test_cannot_request_same_phone_as_current_verified_number(): void
    {
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->postJson('/api/member/account/phone-change-request', [
                'new_phone' => $this->verifiedMember->phone,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * 7. Cannot request phone already used by another member.
     */
    public function test_cannot_request_phone_already_used_by_another_member(): void
    {
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->postJson('/api/member/account/phone-change-request', [
                'new_phone' => $this->unverifiedMember->phone,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * 8. Member can confirm sending WhatsApp message for change request.
     */
    public function test_member_can_confirm_whatsapp_sent_for_change_request(): void
    {
        $changeRequest = PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->verifiedMember, 'member')
            ->postJson("/api/member/account/phone-change-request/{$changeRequest->id}/confirm-whatsapp");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('change_request.is_whatsapp_verified', true);

        $changeRequest->refresh();
        $this->assertNotNull($changeRequest->whatsapp_verified_at);
        $this->assertSame('pending', $changeRequest->status);
    }

    /**
     * 9. Member can cancel pending change request.
     */
    public function test_member_can_cancel_pending_change_request(): void
    {
        $changeRequest = PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->verifiedMember, 'member')
            ->deleteJson("/api/member/account/phone-change-request/{$changeRequest->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('phone_number_change_requests', [
            'id' => $changeRequest->id,
        ]);
    }

    /**
     * 10. Admin can view change requests and filter by status.
     */
    public function test_admin_can_view_change_requests_and_filter(): void
    {
        PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/phone-change-requests?status=pending');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pendingCount', 1)
            ->assertJsonPath('totalCount', 1);

        $data = $response->json('requests.data');
        $this->assertCount(1, $data);
        $this->assertSame('+919988776655', $data[0]['new_phone']);
    }

    /**
     * 11. Admin can atomically approve change request.
     */
    public function test_admin_can_atomically_approve_change_request(): void
    {
        $changeRequest = PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/phone-change-requests/{$changeRequest->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Verification of Member update
        $this->verifiedMember->refresh();
        $this->assertSame('+919988776655', $this->verifiedMember->phone);
        $this->assertNotNull($this->verifiedMember->mobile_verified_at);

        // Verification of Change Request update
        $changeRequest->refresh();
        $this->assertSame('approved', $changeRequest->status);
        $this->assertSame($this->admin->id, $changeRequest->approved_by);
        $this->assertNotNull($changeRequest->approved_at);
    }

    /**
     * 12. Admin cannot approve an already approved request (idempotency/guard).
     */
    public function test_admin_cannot_approve_already_approved_request(): void
    {
        $changeRequest = PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/phone-change-requests/{$changeRequest->id}/approve");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * 13. Admin can reject change request, preserving old verified phone.
     */
    public function test_admin_can_reject_change_request_preserving_old_phone(): void
    {
        $oldPhone = $this->verifiedMember->phone;
        $changeRequest = PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $oldPhone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/phone-change-requests/{$changeRequest->id}/reject", [
                'reason' => 'Invalid document proof provided.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        // CRITICAL INVARIANT: Member phone is still OLD phone!
        $this->verifiedMember->refresh();
        $this->assertSame($oldPhone, $this->verifiedMember->phone);

        $changeRequest->refresh();
        $this->assertSame('rejected', $changeRequest->status);
        $this->assertSame($this->admin->id, $changeRequest->rejected_by);
        $this->assertSame('Invalid document proof provided.', $changeRequest->rejection_reason);
        $this->assertNotNull($changeRequest->rejected_at);
    }

    /**
     * 14. Verification status endpoint returns pending phone change info.
     */
    public function test_verification_status_endpoint_returns_pending_phone_change_info(): void
    {
        PhoneNumberChangeRequest::create([
            'member_id' => $this->verifiedMember->id,
            'old_phone' => $this->verifiedMember->phone,
            'new_phone' => '+919988776655',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->verifiedMember, 'member')
            ->getJson('/api/member/account/verification-status');

        $response->assertOk()
            ->assertJsonPath('has_pending_phone_change', true)
            ->assertJsonPath('pending_phone_change_request.new_phone', '+919988776655')
            ->assertJsonPath('pending_phone_change_request.status', 'pending');
    }
}
