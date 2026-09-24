<?php

namespace Tests\Feature;

use App\Models\AdDeposit;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use App\Services\BscTransactionVerifierService;
use Tests\TestCase;

class UsdtOnChainVerificationTest extends TestCase
{
    protected Member $member;
    protected string $adminWallet = '0x1234567890123456789012345678901234567890';

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate');
        }

        // Configure destination wallet in settings
        Setting::set('deposit_crypto_wallet_address', $this->adminWallet);
        Setting::set('deposit_currency', 'USDT');
        Setting::set('deposit_network', 'BEP-20');
        Setting::set('deposit_fee_percent', 0.00);

        // Create test member with $0.00 initial ad balance
        $unique = uniqid('onchain_');
        $this->member = Member::create([
            'name' => 'Crypto Advertiser ' . $unique,
            'email' => $unique . '_crypto@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'ad_balance' => 0.00,
            'mobile_verified_at' => now(),
        ]);

        // Enable mock verification driver
        BscTransactionVerifierService::fake();
    }

    protected function tearDown(): void
    {
        BscTransactionVerifierService::clearFake();
        AdDeposit::query()->where('transaction_hash', 'LIKE', '0x%')->where('member_id', $this->member->id)->delete();
        Member::query()->where('email', 'LIKE', '%_crypto@test.com')->delete();

        parent::tearDown();
    }

    protected function getOrCreateAdmin(): Admin
    {
        $admin = Admin::first();
        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Super Admin',
                'email' => 'admin_test@cpanel.com',
                'password' => bcrypt('123456'),
            ]);
        }
        return $admin;
    }

    public function test_verified_usdt_bep20_transaction_is_approved_and_auto_credited_immediately(): void
    {
        $validHash = '0x' . str_repeat('a', 64);

        $response = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/funds/deposits', [
                'amount_usdt' => 50.00,
                'transaction_hash' => $validHash,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'verified' => true,
                'status' => 'approved',
                'credited_amount' => 50.00,
                'member_ad_balance' => 50.00,
            ]);

        // Verify deposit record in database
        $this->assertDatabaseHas('ad_deposits', [
            'member_id' => $this->member->id,
            'transaction_hash' => $validHash,
            'status' => AdDeposit::STATUS_APPROVED,
            'verification_status' => 'verified',
            'currency_in' => 'USDT',
            'network' => 'BEP-20',
            'submitted_amount' => 50.00,
            'verified_amount' => 50.00,
            'fee_percent' => 0.00,
        ]);

        // Verify member ad balance was atomically credited
        $this->assertEquals(50.00, (float) $this->member->fresh()->ad_balance);
    }

    public function test_reverted_onchain_transaction_is_rejected_and_balance_is_not_credited(): void
    {
        $revertedHash = '0xREVERTED' . str_repeat('1', 54);

        $response = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/funds/deposits', [
                'amount_usdt' => 100.00,
                'transaction_hash' => $revertedHash,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'verified' => false,
                'status' => 'reverted',
            ]);

        // Ensure zero records created and balance remains 0
        $this->assertDatabaseMissing('ad_deposits', [
            'transaction_hash' => $revertedHash,
        ]);
        $this->assertEquals(0.00, (float) $this->member->fresh()->ad_balance);
    }

    public function test_wrong_recipient_wallet_is_rejected_onchain(): void
    {
        $wrongRecipientHash = '0xWRONGRECIPIENT' . str_repeat('2', 48);

        $response = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/funds/deposits', [
                'amount_usdt' => 50.00,
                'transaction_hash' => $wrongRecipientHash,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'verified' => false,
                'status' => 'transfer_mismatch',
            ]);

        $this->assertEquals(0.00, (float) $this->member->fresh()->ad_balance);
    }

    public function test_amount_below_minimum_is_rejected_onchain(): void
    {
        $lowAmountHash = '0xLOWAMOUNT' . str_repeat('3', 53);

        $response = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/funds/deposits', [
                'amount_usdt' => 1.00,
                'transaction_hash' => $lowAmountHash,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'amount_too_low',
            ]);

        $this->assertEquals(0.00, (float) $this->member->fresh()->ad_balance);
    }

    public function test_strict_hash_uniqueness_prevents_replay_attacks_and_double_credits(): void
    {
        $uniqueHash = '0x' . str_repeat('b', 64);

        // 1st submission -> Success & auto-credited
        $first = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/funds/deposits', [
                'amount_usdt' => 25.00,
                'transaction_hash' => $uniqueHash,
            ]);

        $first->assertStatus(201)
            ->assertJson(['success' => true, 'status' => 'approved']);

        $this->assertEquals(25.00, (float) $this->member->fresh()->ad_balance);

        // 2nd submission with SAME hash -> Blocked by strict uniqueness check
        $second = $this->actingAs($this->member, 'member')
            ->postJson('/api/member/funds/deposits', [
                'amount_usdt' => 25.00,
                'transaction_hash' => $uniqueHash,
            ]);

        $second->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Balance remains strictly $25.00 (NO double credit)
        $this->assertEquals(25.00, (float) $this->member->fresh()->ad_balance);
    }

    public function test_admin_can_reverify_pending_deposit_onchain(): void
    {
        $admin = $this->getOrCreateAdmin();

        // Create a deposit in pending state
        $pendingHash = '0x' . str_repeat('c', 64);
        $deposit = AdDeposit::create([
            'member_id' => $this->member->id,
            'amount_inr' => 100.00,
            'submitted_amount' => 100.00,
            'net_amount_inr' => 100.00,
            'fee_percent' => 0.00,
            'fee_amount_inr' => 0.00,
            'exchange_rate' => 1.00,
            'expected_usd_amount' => 100.00,
            'currency_in' => 'USDT',
            'currency_out' => 'USDT',
            'network' => 'BEP-20',
            'token' => 'USDT',
            'wallet_address' => $this->adminWallet,
            'transaction_hash' => $pendingHash,
            'status' => AdDeposit::STATUS_PENDING,
            'verification_status' => 'pending',
        ]);

        $this->assertEquals(0.00, (float) $this->member->fresh()->ad_balance);

        // Admin triggers re-verification
        $response = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/funds/deposits/{$deposit->id}/reverify");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'verified' => true,
                'credited_amount' => 100.00,
            ]);

        $this->assertEquals(AdDeposit::STATUS_APPROVED, $deposit->fresh()->status);
        $this->assertEquals('verified', $deposit->fresh()->verification_status);
        $this->assertEquals(100.00, (float) $this->member->fresh()->ad_balance);
    }
}