<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdDeposit;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Tests\TestCase;

class AdDepositApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate');
        }
    }

    protected function tearDown(): void
    {
        AdDeposit::query()->where('transaction_reference', 'LIKE', 'UTR_%')->orWhere('transaction_reference', 'LIKE', 'DUP_%')->delete();
        AdCampaign::query()->where('campaign_name', 'LIKE', 'Test Camp %')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'Approval Page_%')->delete();
        Member::query()->where('email', 'LIKE', '%_approval@test.com')->delete();

        parent::tearDown();
    }

    protected function getOrCreateAdmin(): Admin
    {
        $admin = Admin::first();
        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Super Admin',
                'email' => 'pro@cpanel.com',
                'password' => bcrypt('123456'),
            ]);
        }
        return $admin;
    }

    protected function createVerifiedMember(string $prefix = 'mem', float $initialAdBalance = 0.00): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_approval@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'ad_balance' => $initialAdBalance,
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('appr_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Approval Page ' . $unique,
            'page_username' => 'appr_' . $unique,
            'slug' => 'appr-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Admin can approve a pending deposit and atomically credit USD advertising funds to Member's ad_balance.
     */
    public function test_admin_can_approve_pending_deposit_and_atomically_credits_usd_to_member(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('depositor', 0.00);
        $page = $this->createBusinessPage($member);

        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');

        // Member submits ₹1000 @ 10% fee (₹100 fee, ₹900 net) @ 83.50 -> $10.78 USD
        $txRef = 'UTR_' . uniqid();
        $subRes = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 1000.00,
            'transaction_reference' => $txRef,
            'business_page_slug' => $page->slug,
        ]);
        $subRes->assertStatus(201);
        $depositId = $subRes->json('deposit.id');

        // Verify initial state
        $this->assertEquals(0.00, (float) $member->fresh()->ad_balance);
        $deposit = AdDeposit::find($depositId);
        $this->assertEquals(AdDeposit::STATUS_PENDING, $deposit->status);
        $this->assertEquals(10.78, (float) $deposit->expected_usd_amount);

        // Admin approves deposit
        $apprRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$depositId}/approve", [
            'admin_notes' => 'Verified on bank portal.',
        ]);

        $apprRes->assertStatus(200);
        $this->assertTrue($apprRes->json('success'));
        $this->assertEquals(10.78, (float) $apprRes->json('credited_usd_amount'));
        $this->assertEquals(10.78, (float) $apprRes->json('new_ad_balance'));

        // Verify DB persistence
        $freshDeposit = $deposit->fresh();
        $this->assertEquals(AdDeposit::STATUS_APPROVED, $freshDeposit->status);
        $this->assertNotNull($freshDeposit->verified_at);
        $this->assertEquals($admin->id, $freshDeposit->verified_by);
        $this->assertEquals('Verified on bank portal.', $freshDeposit->admin_notes);

        // Verify Member balance updated in DB
        $freshMember = $member->fresh();
        $this->assertEquals(10.78, (float) $freshMember->ad_balance);
    }

    /**
     * 2. Approval strictly respects stored rate and fee snapshots (Example: ₹1000 @ 10% fee & 95.00 rate = $9.47 USD).
     * Even if Admin changes active rate/fee today, the existing deposit credits $9.47 USD.
     */
    public function test_stored_rate_and_fee_snapshots_are_strictly_used_during_approval(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('snapshot_tester', 50.00); // Starting with $50.00
        $page = $this->createBusinessPage($member);

        // Rate at submission time: 1 USD = 95.00 INR, Fee = 10%
        Setting::set('deposit_exchange_rate', '95.00', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');

        $txRef = 'UTR_' . uniqid();
        $subRes = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 1000.00,
            'transaction_reference' => $txRef,
            'business_page_slug' => $page->slug,
        ]);
        $subRes->assertStatus(201);
        $depositId = $subRes->json('deposit.id');

        // Expected USD snapshot: 900 / 95.00 = 9.4736... -> $9.47 USD
        $deposit = AdDeposit::find($depositId);
        $this->assertEquals(9.47, (float) $deposit->expected_usd_amount);

        // Later: Admin changes active settings to 5% fee and 80.00 exchange rate
        Setting::set('deposit_exchange_rate', '80.00', 'funds');
        Setting::set('deposit_fee_percent', '5.00', 'funds');

        // Admin approves old deposit
        $apprRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$depositId}/approve");
        $apprRes->assertStatus(200);

        // Member must receive exactly $9.47 USD (starting $50.00 + $9.47 = $59.47)
        $this->assertEquals(9.47, (float) $apprRes->json('credited_usd_amount'));
        $this->assertEquals(59.47, (float) $apprRes->json('new_ad_balance'));

        $this->assertEquals(59.47, (float) $member->fresh()->ad_balance);
    }

    /**
     * 3. Idempotency test: Double approval or rapid clicking MUST reject second request and prevent double credit.
     */
    public function test_approval_is_strictly_idempotent_and_prevents_double_credit(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('idempotency_tester', 0.00);

        $txRef = 'UTR_' . uniqid();
        $deposit = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 1000.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 100.00,
            'net_amount_inr' => 900.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 10.78,
            'transaction_reference' => $txRef,
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        // First approval: Success
        $res1 = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$deposit->id}/approve");
        $res1->assertStatus(200);
        $this->assertEquals(10.78, (float) $member->fresh()->ad_balance);

        // Second approval attempt: Rejected
        $res2 = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$deposit->id}/approve");
        $res2->assertStatus(422);
        $this->assertFalse($res2->json('success'));

        // Member balance MUST remain $10.78 (NO DOUBLE CREDIT)
        $this->assertEquals(10.78, (float) $member->fresh()->ad_balance);
    }

    /**
     * 4. Unauthorized user cannot approve deposits.
     */
    public function test_unauthorized_member_cannot_approve_deposits(): void
    {
        $member = $this->createVerifiedMember('hacker');
        $victim = $this->createVerifiedMember('victim');

        $deposit = AdDeposit::create([
            'member_id' => $victim->id,
            'amount_inr' => 1000.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 100.00,
            'net_amount_inr' => 900.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 10.78,
            'transaction_reference' => 'UTR_' . uniqid(),
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        $res = $this->actingAs($member, 'member')->postJson("/api/admin/funds/deposits/{$deposit->id}/approve");
        $this->assertTrue(in_array($res->status(), [401, 403]));

        // Status remains pending, zero credit
        $this->assertEquals(AdDeposit::STATUS_PENDING, $deposit->fresh()->status);
        $this->assertEquals(0.00, (float) $victim->fresh()->ad_balance);
    }

    /**
     * 5. Admin can reject a deposit request and NO balance is credited.
     */
    public function test_admin_can_reject_deposit_without_crediting_balance(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('rejected_mem', 15.00);

        $deposit = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 1000.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 100.00,
            'net_amount_inr' => 900.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 10.78,
            'transaction_reference' => 'UTR_' . uniqid(),
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        $res = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$deposit->id}/reject", [
            'rejection_reason' => 'Invalid UTR reference not found on bank statement.',
        ]);

        $res->assertStatus(200);
        $this->assertTrue($res->json('success'));

        $freshDeposit = $deposit->fresh();
        $this->assertEquals(AdDeposit::STATUS_REJECTED, $freshDeposit->status);
        $this->assertEquals('Invalid UTR reference not found on bank statement.', $freshDeposit->rejection_reason);

        // Member balance MUST remain exactly $15.00 (Zero Credit)
        $this->assertEquals(15.00, (float) $member->fresh()->ad_balance);
    }

    /**
     * 6. Rejected deposit cannot be approved later.
     */
    public function test_rejected_deposit_cannot_be_approved(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('reject_then_approve');

        $deposit = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 500.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 50.00,
            'net_amount_inr' => 450.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 5.39,
            'transaction_reference' => 'UTR_' . uniqid(),
            'status' => AdDeposit::STATUS_REJECTED,
            'rejection_reason' => 'Duplicate transaction ID',
        ]);

        $res = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$deposit->id}/approve");
        $res->assertStatus(422);

        // Status remains rejected, zero credit
        $this->assertEquals(AdDeposit::STATUS_REJECTED, $deposit->fresh()->status);
        $this->assertEquals(0.00, (float) $member->fresh()->ad_balance);
    }

    /**
     * 7. Existing Ad Campaign budgets, spend amounts, and remaining balances remain untouched when approving deposit.
     */
    public function test_campaign_budgets_and_spent_amounts_remain_untouched_on_deposit_approval(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('camp_owner', 10.00);
        $page = $this->createBusinessPage($member);

        // Existing campaign with $50.00 budget, $12.50 spent, $37.50 remaining
        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $member->id,
            'campaign_name' => 'Test Camp ' . uniqid(),
            'budget' => 50.00,
            'currency' => 'USD',
            'spent_amount' => 12.50,
            'remaining_amount' => 37.50,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Submit and approve a $10.78 deposit
        $deposit = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 1000.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 100.00,
            'net_amount_inr' => 900.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 10.78,
            'transaction_reference' => 'UTR_' . uniqid(),
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$deposit->id}/approve")->assertStatus(200);

        // Member available ad_balance increases by $10.78 ($10.00 + $10.78 = $20.78)
        $this->assertEquals(20.78, (float) $member->fresh()->ad_balance);

        // Campaign metrics MUST be completely untouched
        $freshCamp = $campaign->fresh();
        $this->assertEquals(50.00, (float) $freshCamp->budget);
        $this->assertEquals(12.50, (float) $freshCamp->spent_amount);
        $this->assertEquals(37.50, (float) $freshCamp->remaining_amount);
    }

    /**
     * 8. Sequential deposit approvals accumulate accurate balances.
     */
    public function test_multiple_deposits_approved_sequentially_accumulate_balance_accurately(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('multi_depositor', 0.00);

        // Deposit 1: ₹1000 -> $10.78
        $dep1 = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 1000.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 100.00,
            'net_amount_inr' => 900.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 10.78,
            'transaction_reference' => 'UTR_' . uniqid(),
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        // Deposit 2: ₹500 -> $5.39
        $dep2 = AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 500.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 50.00,
            'net_amount_inr' => 450.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 5.39,
            'transaction_reference' => 'UTR_' . uniqid(),
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$dep1->id}/approve")->assertStatus(200);
        $this->assertEquals(10.78, (float) $member->fresh()->ad_balance);

        $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$dep2->id}/approve")->assertStatus(200);
        $this->assertEquals(16.17, (float) $member->fresh()->ad_balance); // 10.78 + 5.39 = 16.17
    }

    /**
     * 9. Phase 10.3: New USD deposits have ZERO deduction and credit EXACT deposited amount upon approval.
     * Tests: $10.00 -> $10.00, $50.00 -> $50.00, $100.00 -> $100.00, $500.00 -> $500.00, $10.50 -> $10.50.
     */
    public function test_new_usd_deposits_have_zero_deduction_and_credit_exact_amount_upon_approval(): void
    {
        $admin = $this->getOrCreateAdmin();

        $testAmounts = [10.00, 50.00, 100.00, 500.00, 10.50];

        foreach ($testAmounts as $amount) {
            $member = $this->createVerifiedMember('usd_exact_' . (int)$amount, 0.00);

            // Member submits USD deposit
            $txHash = '0x' . bin2hex(random_bytes(32));
            $subRes = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
                'amount_usd' => $amount,
                'currency' => 'USD',
                'transaction_hash' => $txHash,
            ]);

            $subRes->assertStatus(201);
            $depositId = $subRes->json('deposit.id');

            // Verify submission has 0% fee and exact expected USD
            $deposit = AdDeposit::find($depositId);
            $this->assertEquals($amount, (float) $deposit->expected_usd_amount);
            $this->assertEquals(0.00, (float) ($deposit->fee_percent ?? 0.00));
            $this->assertEquals(0.00, (float) ($deposit->fee_amount_inr ?? 0.00));

            // Admin approves deposit
            $apprRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$depositId}/approve");
            $apprRes->assertStatus(200);

            // EXACT CREDIT: Member receives full deposited amount, zero deduction
            $this->assertEquals($amount, (float) $apprRes->json('credited_usd_amount'));
            $this->assertEquals($amount, (float) $member->fresh()->ad_balance);
        }
    }

    /**
     * 10. Phase 10.3: Second approval attempt on approved USD deposit is blocked.
     */
    public function test_duplicate_approval_on_usd_deposit_is_blocked(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('dup_usd_tester', 0.00);

        $txHash = '0x' . bin2hex(random_bytes(32));
        $subRes = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usd' => 50.00,
            'currency' => 'USD',
            'transaction_hash' => $txHash,
        ]);
        $depositId = $subRes->json('deposit.id');

        // First approval: OK, +$50.00
        $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$depositId}/approve")->assertStatus(200);
        $this->assertEquals(50.00, (float) $member->fresh()->ad_balance);

        // Second approval: BLOCKED (422), balance remains $50.00
        $dupRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$depositId}/approve");
        $dupRes->assertStatus(422);
        $this->assertEquals(50.00, (float) $member->fresh()->ad_balance);
    }
}
