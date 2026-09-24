<?php

namespace Tests\Feature;

use App\Models\AdDeposit;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Tests\TestCase;

class AdDepositSystemTest extends TestCase
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
        BusinessPage::query()->where('page_name', 'LIKE', 'Deposit Page_%')->delete();
        Member::query()->where('email', 'LIKE', '%_deposit@test.com')->delete();

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

    protected function createVerifiedMember(string $prefix = 'member'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_deposit@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('dep_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Deposit Page ' . $unique,
            'page_username' => 'dep_' . $unique,
            'slug' => 'dep-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Admin can configure deposit settings with Crypto Wallet Address, USD currency, and 0% fee.
     */
    public function test_admin_can_update_and_fetch_deposit_settings_crypto_wallet_and_zero_fee(): void
    {
        $admin = $this->getOrCreateAdmin();

        $response = $this->actingAs($admin, 'admin')->postJson('/api/admin/funds/deposit-settings', [
            'deposit_crypto_wallet_address' => '0x71C8363837932170823293847983274982743847',
            'deposit_instructions' => 'Transfer payment in USD to the crypto wallet address or scan QR.',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $settings = $response->json('settings');
        $this->assertEquals('0x71C8363837932170823293847983274982743847', $settings['deposit_crypto_wallet_address']);
        $this->assertEquals('USD', $settings['deposit_currency']);
        $this->assertEquals(0.00, (float) $settings['deposit_fee_percent']);

        // Verify GET returns updated settings
        $getRes = $this->actingAs($admin, 'admin')->getJson('/api/admin/funds/deposit-settings');
        $getRes->assertStatus(200);
        $this->assertEquals('0x71C8363837932170823293847983274982743847', $getRes->json('settings.deposit_crypto_wallet_address'));
        $this->assertEquals('USD', $getRes->json('settings.deposit_currency'));
        $this->assertEquals(0.00, (float) $getRes->json('settings.deposit_fee_percent'));
    }

    /**
     * 2. Unauthorized member cannot modify deposit settings.
     */
    public function test_unauthorized_member_cannot_update_deposit_settings(): void
    {
        $member = $this->createVerifiedMember('unauth');

        $response = $this->actingAs($member, 'member')->postJson('/api/admin/funds/deposit-settings', [
            'deposit_crypto_wallet_address' => '0xhackerwallet',
        ]);

        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    /**
     * 3. Member can fetch public deposit configuration including Crypto Wallet Address.
     */
    public function test_member_can_fetch_public_deposit_config_including_fee(): void
    {
        $member = $this->createVerifiedMember('viewer');
        Setting::set('deposit_crypto_wallet_address', '0x1234567890abcdef1234567890abcdef12345678', 'funds');
        Setting::set('deposit_upi_id', 'ads@upi', 'funds');
        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');
        Setting::set('deposit_min_inr', '100.00', 'funds');

        $response = $this->actingAs($member, 'member')->getJson('/api/member/funds/deposit-settings');
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $config = $response->json('config');
        $this->assertEquals('0x1234567890abcdef1234567890abcdef12345678', $config['crypto_wallet_address']);
        $this->assertEquals('USD', $config['currency']);
        $this->assertEquals('ads@upi', $config['upi_id']);
        $this->assertEquals(83.50, $config['exchange_rate']);
        $this->assertEquals(10.00, $config['fee_percent']);
        $this->assertEquals('INR', $config['currency_in']);
        $this->assertEquals('USD', $config['currency_out']);
        $this->assertTrue($config['is_available']);
    }

    /**
     * 4. Member deposit submission calculates 10% fee and converts NET INR to USD advertising credit.
     * Example: ₹1000 gross -> 10% fee (₹100) -> ₹900 net -> ₹900 / 83.50 = $10.78 USD.
     */
    public function test_member_deposit_submission_calculates_10_percent_fee_and_net_usd_credit(): void
    {
        $member = $this->createVerifiedMember('depositor');
        $page = $this->createBusinessPage($member);
        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');
        Setting::set('deposit_min_inr', '100.00', 'funds');

        $txRef = 'UTR_' . uniqid();

        $response = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 1000.00,
            'transaction_reference' => $txRef,
            'business_page_slug' => $page->slug,
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        $deposit = $response->json('deposit');
        $this->assertEquals(1000.00, (float) $deposit['amount_inr']); // Gross INR
        $this->assertEquals(10.00, (float) $deposit['fee_percent']); // 10% fee
        $this->assertEquals(100.00, (float) $deposit['fee_amount_inr']); // ₹100 fee
        $this->assertEquals(900.00, (float) $deposit['net_amount_inr']); // ₹900 net
        $this->assertEquals(83.50, (float) $deposit['exchange_rate']); // Snapshot rate
        $this->assertEquals(10.78, (float) $deposit['expected_usd_amount']); // 900 / 83.50 = 10.778... -> 10.78
        $this->assertEquals('INR', $deposit['currency_in']);
        $this->assertEquals('USD', $deposit['currency_out']);
        $this->assertEquals(AdDeposit::STATUS_PENDING, $deposit['status']);
        $this->assertEquals($txRef, $deposit['transaction_reference']);

        // Verify DB persistence
        $dbDeposit = AdDeposit::where('transaction_reference', $txRef)->first();
        $this->assertNotNull($dbDeposit);
        $this->assertEquals(1000.00, (float) $dbDeposit->amount_inr);
        $this->assertEquals(10.00, (float) $dbDeposit->fee_percent);
        $this->assertEquals(100.00, (float) $dbDeposit->fee_amount_inr);
        $this->assertEquals(900.00, (float) $dbDeposit->net_amount_inr);
        $this->assertEquals(83.50, (float) $dbDeposit->exchange_rate);
        $this->assertEquals(10.78, (float) $dbDeposit->expected_usd_amount);
        $this->assertEquals(AdDeposit::STATUS_PENDING, $dbDeposit->status);
    }

    /**
     * 5. Calculations for various standard test amounts (₹100, ₹500, ₹1000, ₹10000, ₹1000.50).
     */
    public function test_various_deposit_amounts_calculate_exact_fee_and_net_usd(): void
    {
        $member = $this->createVerifiedMember('calc_tester');
        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');
        Setting::set('deposit_min_inr', '10.00', 'funds');

        $testCases = [
            // [Gross, Expected Fee, Expected Net, Expected USD @ 83.50]
            [100.00, 10.00, 90.00, 1.08], // 90 / 83.50 = 1.0778 -> 1.08
            [500.00, 50.00, 450.00, 5.39], // 450 / 83.50 = 5.389 -> 5.39
            [10000.00, 1000.00, 9000.00, 107.78], // 9000 / 83.50 = 107.784 -> 107.78
            [1000.50, 100.05, 900.45, 10.78], // 900.45 / 83.50 = 10.7838 -> 10.78
        ];

        foreach ($testCases as [$gross, $expectedFee, $expectedNet, $expectedUsd]) {
            $txRef = 'UTR_' . uniqid();
            $res = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
                'amount_inr' => $gross,
                'transaction_reference' => $txRef,
            ]);

            $res->assertStatus(201);
            $deposit = $res->json('deposit');
            $this->assertEquals($gross, (float) $deposit['amount_inr']);
            $this->assertEquals($expectedFee, (float) $deposit['fee_amount_inr']);
            $this->assertEquals($expectedNet, (float) $deposit['net_amount_inr']);
            $this->assertEquals($expectedUsd, (float) $deposit['expected_usd_amount']);
        }
    }

    /**
     * 5b. Member can submit USD deposit with Transaction Hash and 0% fee.
     */
    public function test_member_can_submit_usd_deposit_with_transaction_hash_and_zero_fee(): void
    {
        $member = $this->createVerifiedMember('usd_depositor', 0.00);
        $page = $this->createBusinessPage($member);

        Setting::set('deposit_currency', 'USD', 'funds');
        Setting::set('deposit_fee_percent', '0.00', 'funds');

        $txHash = '0x' . bin2hex(random_bytes(32));

        $res = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usd' => 100.00,
            'currency' => 'USD',
            'transaction_hash' => $txHash,
            'business_page_slug' => $page->slug,
        ]);

        $res->assertStatus(201);
        $this->assertTrue($res->json('success'));

        $deposit = $res->json('deposit');
        $this->assertEquals(100.00, (float) $deposit['amount_inr']);
        $this->assertEquals(0.00, (float) $deposit['fee_percent']);
        $this->assertEquals(0.00, (float) $deposit['fee_amount_inr']);
        $this->assertEquals(100.00, (float) $deposit['net_amount_inr']);
        $this->assertEquals(100.00, (float) $deposit['expected_usd_amount']);
        $this->assertEquals('USD', $deposit['currency_in']);
        $this->assertEquals('USD', $deposit['currency_out']);
        $this->assertEquals(AdDeposit::STATUS_PENDING, $deposit['status']);
        $this->assertEquals($txHash, $deposit['transaction_reference']);

        // Member balance MUST remain unchanged upon submission (0.00)
        $this->assertEquals(0.00, (float) $member->fresh()->ad_balance);
    }

    /**
     * 6. Admin saving deposit settings enforces 0% fee on active configuration.
     */
    public function test_admin_saving_deposit_settings_enforces_zero_fee(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('fee_change_mem');
        Setting::set('deposit_exchange_rate', '83.50', 'funds');

        // Admin saves deposit settings
        $this->actingAs($admin, 'admin')->postJson('/api/admin/funds/deposit-settings', [
            'deposit_crypto_wallet_address' => '0x9999999999999999999999999999999999999999',
        ])->assertStatus(200);

        // Setting resolves to 0% fee
        $this->assertEquals(0.00, (float) Setting::get('deposit_fee_percent', 0.00));
    }

    /**
     * 7. Subsequent fee or rate change does NOT mutate historical deposit snapshot.
     */
    public function test_subsequent_fee_or_rate_change_does_not_mutate_historical_deposit_snapshot(): void
    {
        $member = $this->createVerifiedMember('snap_mem');
        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');

        $tx = 'UTR_HIST_' . uniqid();

        // Submit deposit today
        $res = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 1000.00,
            'transaction_reference' => $tx,
        ]);
        $res->assertStatus(201);
        $depositId = $res->json('deposit.id');

        // Tomorrow: Admin changes exchange rate to 95.00 and fee to 5.00%
        Setting::set('deposit_exchange_rate', '95.00', 'funds');
        Setting::set('deposit_fee_percent', '5.00', 'funds');

        // Verify existing deposit record still preserves original 10% fee, ₹900 net, 83.50 rate, and $10.78 USD
        $saved = AdDeposit::find($depositId);
        $this->assertEquals(1000.00, (float) $saved->amount_inr);
        $this->assertEquals(10.00, (float) $saved->fee_percent);
        $this->assertEquals(100.00, (float) $saved->fee_amount_inr);
        $this->assertEquals(900.00, (float) $saved->net_amount_inr);
        $this->assertEquals(83.50, (float) $saved->exchange_rate);
        $this->assertEquals(10.78, (float) $saved->expected_usd_amount);
    }

    /**
     * 8. Duplicate pending deposit transaction reference is rejected.
     */
    public function test_duplicate_pending_deposit_reference_is_rejected(): void
    {
        $member = $this->createVerifiedMember('dup_member');
        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');
        Setting::set('deposit_min_inr', '100.00', 'funds');

        $txRef = 'DUP_' . uniqid();

        // First submission
        $res1 = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 500.00,
            'transaction_reference' => $txRef,
        ]);
        $res1->assertStatus(201);

        // Duplicate submission
        $res2 = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 500.00,
            'transaction_reference' => $txRef,
        ]);
        $res2->assertStatus(422);
    }

    /**
     * 9. Submitting below minimum deposit is rejected.
     */
    public function test_submitting_below_minimum_inr_is_rejected(): void
    {
        $member = $this->createVerifiedMember('min_member');
        Setting::set('deposit_min_inr', '500.00', 'funds');

        $response = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_inr' => 100.00, // below 500
            'transaction_reference' => 'UTR_' . uniqid(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount_inr']);
    }

    /**
     * 10. Member can view own deposit history and cannot view other members' deposits.
     */
    public function test_member_can_view_own_deposit_history_and_cannot_view_others(): void
    {
        $memberA = $this->createVerifiedMember('member_a');
        $memberB = $this->createVerifiedMember('member_b');
        Setting::set('deposit_exchange_rate', '83.50', 'funds');
        Setting::set('deposit_fee_percent', '10.00', 'funds');

        $txA = 'UTR_A_' . uniqid();
        $txB = 'UTR_B_' . uniqid();

        AdDeposit::create([
            'member_id' => $memberA->id,
            'amount_inr' => 500.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 50.00,
            'net_amount_inr' => 450.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 5.39,
            'transaction_reference' => $txA,
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        AdDeposit::create([
            'member_id' => $memberB->id,
            'amount_inr' => 1500.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 150.00,
            'net_amount_inr' => 1350.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 16.17,
            'transaction_reference' => $txB,
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        // Member A queries history
        $responseA = $this->actingAs($memberA, 'member')->getJson('/api/member/funds/deposits');
        $responseA->assertStatus(200);

        $depositsA = $responseA->json('deposits.data');
        $this->assertCount(1, $depositsA);
        $this->assertEquals($txA, $depositsA[0]['transaction_reference']);
    }

    /**
     * 11. Admin can view all deposit requests across the platform including gross, fee, and net metrics.
     */
    public function test_admin_can_view_all_deposit_requests_and_metrics(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('admin_view_mem');

        $tx = 'UTR_ADM_' . uniqid();

        AdDeposit::create([
            'member_id' => $member->id,
            'amount_inr' => 2000.00,
            'fee_percent' => 10.00,
            'fee_amount_inr' => 200.00,
            'net_amount_inr' => 1800.00,
            'currency_in' => 'INR',
            'currency_out' => 'USD',
            'exchange_rate' => 83.50,
            'expected_usd_amount' => 21.56,
            'transaction_reference' => $tx,
            'status' => AdDeposit::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/funds/deposits');
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $this->assertGreaterThanOrEqual(1, $response->json('metrics.total_deposits'));
        $this->assertGreaterThanOrEqual(1, $response->json('metrics.pending_count'));
        $this->assertGreaterThanOrEqual(2000.00, (float) $response->json('metrics.total_pending_inr'));
        $this->assertGreaterThanOrEqual(200.00, (float) $response->json('metrics.total_pending_fee_inr'));
        $this->assertGreaterThanOrEqual(1800.00, (float) $response->json('metrics.total_pending_net_inr'));
    }
}
