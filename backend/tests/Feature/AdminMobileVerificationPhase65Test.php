<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureMemberMobileVerified;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMobileVerificationPhase65Test extends TestCase
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
            'name' => 'Super Admin',
            'email' => 'admin_phase65@mlmbook.test',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    public function test_admin_members_api_returns_verified_and_unverified_metrics(): void
    {
        // 2 verified, 1 unverified, 1 blocked
        Member::create([
            'name' => 'Verified User One',
            'user_id' => 'verified_1',
            'email' => 'v1@test.com',
            'password' => 'password123',
            'phone' => '+919999900001',
            'mobile_verified_at' => now(),
        ]);

        Member::create([
            'name' => 'Verified User Two',
            'user_id' => 'verified_2',
            'email' => 'v2@test.com',
            'password' => 'password123',
            'phone' => '+919999900002',
            'mobile_verified_at' => now(),
        ]);

        Member::create([
            'name' => 'Unverified User Three',
            'user_id' => 'unverified_3',
            'email' => 'uv3@test.com',
            'password' => 'password123',
            'phone' => '+919999900003',
            'mobile_verified_at' => null,
            'mobile_verification_requested_at' => now(),
        ]);

        Member::create([
            'name' => 'Blocked User Four',
            'user_id' => 'blocked_4',
            'email' => 'b4@test.com',
            'password' => 'password123',
            'phone' => '+919999900004',
            'mobile_verified_at' => now(),
            'blocked_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/members/active');

        $response->assertOk()
            ->assertJsonPath('totalCount', 4)
            ->assertJsonPath('verifiedCount', 3)
            ->assertJsonPath('unverifiedCount', 1)
            ->assertJsonPath('activeCount', 2)
            ->assertJsonPath('pendingCount', 1)
            ->assertJsonPath('blockedCount', 1);

        $data = $response->json('members.data');
        $this->assertCount(2, $data);
        $userIds = array_column($data, 'user_id');
        $this->assertContains('verified_1', $userIds);
        $this->assertContains('verified_2', $userIds);
    }

    public function test_admin_members_api_filters_by_verified_status(): void
    {
        $vMember = Member::create([
            'name' => 'Verified User',
            'user_id' => 'v_user',
            'email' => 'v@test.com',
            'password' => 'password',
            'phone' => '+919000000001',
            'mobile_verified_at' => now(),
        ]);

        $uvMember = Member::create([
            'name' => 'Unverified User',
            'user_id' => 'uv_user',
            'email' => 'uv@test.com',
            'password' => 'password',
            'phone' => '+919000000002',
            'mobile_verified_at' => null,
        ]);

        // Filter: status=verified
        $resVerified = $this->actingAs($this->admin, 'admin')->getJson('/admin/members/active?status=verified');
        $resVerified->assertOk();
        $this->assertCount(1, $resVerified->json('members.data'));
        $this->assertEquals('v_user', $resVerified->json('members.data.0.user_id'));
        $this->assertTrue($resVerified->json('members.data.0.is_verified'));

        // Filter: status=unverified
        $resUnverified = $this->actingAs($this->admin, 'admin')->getJson('/admin/members/active?status=unverified');
        $resUnverified->assertOk();
        $this->assertCount(1, $resUnverified->json('members.data'));
        $this->assertEquals('uv_user', $resUnverified->json('members.data.0.user_id'));
        $this->assertFalse($resUnverified->json('members.data.0.is_verified'));

        // Filter: status=all
        $resAll = $this->actingAs($this->admin, 'admin')->getJson('/admin/members/active?status=all');
        $resAll->assertOk();
        $this->assertCount(2, $resAll->json('members.data'));
    }

    public function test_search_works_across_verified_and_unverified_members(): void
    {
        Member::create([
            'name' => 'Rohit Sharma',
            'user_id' => 'rohit_45',
            'email' => 'rohit@test.com',
            'password' => 'password',
            'phone' => '+919876543210',
            'mobile_verified_at' => now(),
        ]);

        Member::create([
            'name' => 'Rohit Verma',
            'user_id' => 'rohit_99',
            'email' => 'rohitverma@test.com',
            'password' => 'password',
            'phone' => '+919876543299',
            'mobile_verified_at' => null,
        ]);

        $res = $this->actingAs($this->admin, 'admin')->getJson('/admin/members/active?status=all&q=Rohit');
        $res->assertOk();
        $this->assertCount(2, $res->json('members.data'));
    }

    public function test_unverified_member_cannot_refer_and_referral_count_only_increments_after_mobile_verification(): void
    {
        // 1. Create a verified sponsor
        $sponsor = Member::create([
            'name' => 'Sponsor Leader',
            'user_id' => 'leader_1',
            'email' => 'leader@test.com',
            'password' => 'password',
            'phone' => '+919876500001',
            'mobile_verified_at' => now(),
            'direct_referral_count' => 0,
        ]);

        $this->assertTrue($sponsor->isEligibleToRefer());
        $this->assertSame(0, $sponsor->direct_referral_count);

        // 2. Create an unverified member referred by sponsor
        $newMember = Member::create([
            'name' => 'Referred Candidate',
            'user_id' => 'candidate_1',
            'introducer_id' => 'leader_1',
            'email' => 'candidate@test.com',
            'password' => 'password',
            'phone' => '+919876500002',
            'mobile_verified_at' => null,
            'referral_counted_at' => null,
        ]);

        // Unverified member cannot refer
        $this->assertFalse($newMember->isEligibleToRefer());
        $this->assertFalse($newMember->isMobileVerified());

        // Sponsor count must NOT increase upon registration alone
        $sponsor->refresh();
        $this->assertSame(0, $sponsor->direct_referral_count);

        // 3. Complete Mobile Verification for the referred member
        $newMember->update(['mobile_verified_at' => now()]);
        $verifyResult = $newMember->qualifyReferral();
        $this->assertTrue($verifyResult);

        // Sponsor count must increase by exactly 1
        $sponsor->refresh();
        $newMember->refresh();
        $this->assertSame(1, $sponsor->direct_referral_count);
        $this->assertNotNull($newMember->referral_counted_at);

        // 4. Repeated qualification attempt must be strictly idempotent (+0)
        $repeatResult = $newMember->qualifyReferral();
        $this->assertFalse($repeatResult);
        $sponsor->refresh();
        $this->assertSame(1, $sponsor->direct_referral_count);
    }

    public function test_unverified_sponsor_cannot_qualify_referrals_until_sponsor_verifies(): void
    {
        // 1. Unverified sponsor
        $unverifiedSponsor = Member::create([
            'name' => 'Unverified Sponsor',
            'user_id' => 'unverified_sponsor',
            'email' => 'uv_sponsor@test.com',
            'password' => 'password',
            'phone' => '+919876500010',
            'mobile_verified_at' => null,
            'direct_referral_count' => 0,
        ]);

        $this->assertFalse($unverifiedSponsor->isEligibleToRefer());

        // 2. Member referred by unverified sponsor attempts qualification
        $downline = Member::create([
            'name' => 'Downline Member',
            'user_id' => 'downline_1',
            'introducer_id' => 'unverified_sponsor',
            'email' => 'downline@test.com',
            'password' => 'password',
            'phone' => '+919876500011',
            'mobile_verified_at' => now(),
            'referral_counted_at' => null,
        ]);

        $result = $downline->qualifyReferral();
        $this->assertFalse($result);

        $unverifiedSponsor->refresh();
        $this->assertSame(0, $unverifiedSponsor->direct_referral_count);
    }

    public function test_admin_can_verify_and_revoke_member_mobile_status(): void
    {
        $member = Member::create([
            'name' => 'Test Member',
            'user_id' => 'test_member',
            'email' => 'tm@test.com',
            'password' => 'password',
            'phone' => '+919876500020',
            'mobile_verified_at' => null,
        ]);

        // 1. Admin verifies member
        $verifyRes = $this->actingAs($this->admin, 'admin')->postJson(route('admin.members.status', $member), [
            'action' => 'verify',
        ]);
        $verifyRes->assertOk()->assertJsonPath('success', true);
        $member->refresh();
        $this->assertTrue($member->isMobileVerified());

        // 2. Admin revokes verification
        $revokeRes = $this->actingAs($this->admin, 'admin')->postJson(route('admin.members.status', $member), [
            'action' => 'unverify',
        ]);
        $revokeRes->assertOk()->assertJsonPath('success', true);
        $member->refresh();
        $this->assertFalse($member->isMobileVerified());
    }

    public function test_admin_bulk_actions_support_verification_and_blocking_separately(): void
    {
        $m1 = Member::create([
            'name' => 'Bulk One',
            'user_id' => 'bulk_1',
            'email' => 'b1@test.com',
            'password' => 'password',
            'mobile_verified_at' => null,
        ]);

        $m2 = Member::create([
            'name' => 'Bulk Two',
            'user_id' => 'bulk_2',
            'email' => 'b2@test.com',
            'password' => 'password',
            'mobile_verified_at' => null,
        ]);

        // Bulk verify
        $resVerify = $this->actingAs($this->admin, 'admin')->postJson(route('admin.members.bulk-action'), [
            'action' => 'verify',
            'ids' => [$m1->id, $m2->id],
        ]);
        $resVerify->assertOk()->assertJsonPath('success', true);
        $m1->refresh();
        $m2->refresh();
        $this->assertTrue($m1->isMobileVerified());
        $this->assertTrue($m2->isMobileVerified());

        // Bulk block
        $resBlock = $this->actingAs($this->admin, 'admin')->postJson(route('admin.members.bulk-action'), [
            'action' => 'block',
            'ids' => [$m1->id, $m2->id],
        ]);
        $resBlock->assertOk()->assertJsonPath('success', true);
        $m1->refresh();
        $m2->refresh();
        $this->assertTrue($m1->isBlocked());
        $this->assertTrue($m2->isBlocked());
    }
}
