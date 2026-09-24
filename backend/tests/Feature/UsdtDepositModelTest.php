<?php

namespace Tests\Feature;

use App\Models\AdDeposit;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Tests\TestCase;

class UsdtDepositModelTest extends TestCase
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
        AdDeposit::query()->where('transaction_reference', 'LIKE', '0xTEST_%')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'USDT Page_%')->delete();
        Member::query()->where('email', 'LIKE', '%_usdt@test.com')->delete();

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

    protected function createVerifiedMember(string $prefix = 'usdt_mem', float $initialAdBalance = 0.00): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_usdt@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'ad_balance' => $initialAdBalance,
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('usdt_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'USDT Page ' . $unique,
            'page_username' => 'usdt_' . $unique,
            'slug' => 'usdt-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Member deposit settings returns USDT BEP-20 configuration and mandatory disclaimer.
     */
    public function test_member_receives_usdt_bep20_deposit_configuration_and_disclaimer(): void
    {
        $member = $this->createVerifiedMember('cfg_user', 25.00);

        Setting::set('deposit_crypto_wallet_address', '0x1234567890abcdef1234567890abcdef12345678', 'funds');
        Setting::set('deposit_currency', 'USDT', 'funds');
        Setting::set('deposit_network', 'BEP-20', 'funds');

        $res = $this->actingAs($member, 'member')->getJson('/api/member/funds/deposit-settings');

        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
            'config' => [
                'currency' => 'USDT',
                'network' => 'BEP-20',
                'token' => 'USDT',
                'currency_label' => 'USDT (BEP-20)',
                'fee_percent' => 0,
                'member_ad_balance' => 25.00,
                'crypto_wallet_address' => '0x1234567890abcdef1234567890abcdef12345678',
            ],
        ]);

        $this->assertStringContainsString('USDT (BEP-20)', $res->json('config.disclaimer'));
    }

    /**
     * 2. Member can submit USDT BEP-20 deposit request with 0% fee and pending status.
     */
    public function test_member_submits_usdt_deposit_with_zero_fee_and_pending_status(): void
    {
        $member = $this->createVerifiedMember('depositor', 0.00);
        $page = $this->createBusinessPage($member);

        $txHash = '0xTEST_' . uniqid();
        $subRes = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usdt' => 50.00,
            'transaction_hash' => $txHash,
            'business_page_slug' => $page->slug,
        ]);

        $subRes->assertStatus(201);
        $this->assertTrue($subRes->json('success'));

        $deposit = AdDeposit::where('transaction_reference', $txHash)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(AdDeposit::STATUS_PENDING, $deposit->status);
        $this->assertEquals('USDT', $deposit->currency_in);
        $this->assertEquals('USDT', $deposit->currency_out);
        $this->assertEquals('BEP-20', $deposit->network);
        $this->assertEquals('USDT', $deposit->token);
        $this->assertEquals(0.00, (float) $deposit->fee_percent);
        $this->assertEquals(50.00, (float) $deposit->submitted_amount);
        $this->assertEquals(50.00, (float) $deposit->expected_usd_amount);

        // Crucial: Member balance is NOT auto-credited during Phase 1 submission
        $this->assertEquals(0.00, (float) $member->fresh()->ad_balance);
    }

    /**
     * 3. Duplicate transaction hash submission is rejected.
     */
    public function test_duplicate_transaction_hash_is_rejected(): void
    {
        $member = $this->createVerifiedMember('dup_user', 0.00);
        $txHash = '0xTEST_DUP_' . uniqid();

        // First submission
        $res1 = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usdt' => 25.00,
            'transaction_hash' => $txHash,
        ]);
        $res1->assertStatus(201);

        // Second submission with identical transaction hash
        $res2 = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usdt' => 25.00,
            'transaction_hash' => $txHash,
        ]);
        $res2->assertStatus(422);
        $this->assertFalse($res2->json('success'));
    }

    /**
     * 4. Admin deposit dashboard returns USDT volume metrics and BEP-20 deposit listings.
     */
    public function test_admin_dashboard_returns_usdt_metrics_and_listings(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('dash_user', 0.00);

        $txHash = '0xTEST_DASH_' . uniqid();
        $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usdt' => 100.00,
            'transaction_hash' => $txHash,
        ]);

        $res = $this->actingAs($admin, 'admin')->getJson('/api/admin/funds/deposits');

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'success',
            'deposits',
            'metrics' => [
                'total_requests',
                'pending_count',
                'approved_count',
                'rejected_count',
                'total_volume_usdt',
            ],
        ]);

        $this->assertGreaterThanOrEqual(1, $res->json('metrics.pending_count'));
        $this->assertGreaterThanOrEqual(100.00, (float) $res->json('metrics.total_volume_usdt'));
    }

    /**
     * 5. Admin can update USDT deposit settings.
     */
    public function test_admin_can_update_deposit_settings(): void
    {
        $admin = $this->getOrCreateAdmin();

        $res = $this->actingAs($admin, 'admin')->postJson('/api/admin/funds/deposit-settings', [
            'deposit_crypto_wallet_address' => '0x9999999999999999999999999999999999999999',
            'deposit_instructions' => 'Custom USDT BEP-20 transfer instructions.',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('0x9999999999999999999999999999999999999999', Setting::get('deposit_crypto_wallet_address'));
        $this->assertEquals('USDT', Setting::get('deposit_currency'));
        $this->assertEquals('BEP-20', Setting::get('deposit_network'));
        $this->assertEquals('0.00', Setting::get('deposit_fee_percent'));
    }
}