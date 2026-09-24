<?php

namespace Tests\Feature;

use App\Mail\MemberWeb3WalletVerificationOtp;
use App\Models\AdReward;
use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Services\MemberOtpService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MemberRewardWalletAddressVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Member ' . uniqid(),
            'user_id' => 'MBR' . strtoupper(substr(uniqid(), -6)),
            'email' => 'user' . uniqid() . '@example.com',
            'phone' => '+1555' . random_int(100000, 999999),
            'mobile_verified_at' => now(),
            'reward_balance' => 0.15,
            'ad_balance' => 10.00,
            'password' => bcrypt('Password123!'),
        ], $attributes));
    }

    public function test_unauthenticated_user_cannot_access_wallet_endpoints(): void
    {
        $this->getJson('/api/member/rewards/wallet')->assertStatus(401);
        $this->postJson('/api/member/rewards/wallet/send-otp', ['wallet_address' => '0x71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3'])->assertStatus(401);
        $this->postJson('/api/member/rewards/wallet/verify-otp', ['otp' => '123456'])->assertStatus(401);
    }

    public function test_member_without_email_cannot_send_otp_for_reward_wallet(): void
    {
        $member = $this->createMember(['email' => 'temp@test.com']);
        // Temporarily mutate in-memory property to empty
        $member->email = '';

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => '0x71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_mail_delivery_failure_does_not_activate_wallet(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new Exception('SMTP transport error'));

        $member = $this->createMember(['email' => 'bob@testmail.com']);
        $targetAddress = '0x71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3';

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => $targetAddress,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertNull($member->fresh()->reward_wallet_address);
    }

    public function test_invalid_wallet_address_format_is_rejected(): void
    {
        $member = $this->createMember();

        // Invalid: missing 0x prefix
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => '71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3',
            ])->assertStatus(422);

        // Invalid: wrong length
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => '0x123456',
            ])->assertStatus(422);

        // Invalid: non-hex characters
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => '0xZZZZ4d28430b8e7C9fAcAbF571aF57F26E63F8B3',
            ])->assertStatus(422);
    }

    public function test_valid_bep20_address_creates_email_otp_challenge_and_dispatches_mail(): void
    {
        Mail::fake();

        $member = $this->createMember(['email' => 'alice@testmail.com']);
        RateLimiter::clear(MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION, $member->id));
        $targetAddress = '0x71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3';

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => $targetAddress,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'wallet_address' => $targetAddress,
                'channel' => 'email',
            ]);

        // Assert mail was sent to registered member email
        Mail::assertSent(MemberWeb3WalletVerificationOtp::class, function ($mail) use ($member, $targetAddress) {
            return $mail->hasTo($member->email) && $mail->walletAddress === $targetAddress;
        });

        $this->assertDatabaseHas('member_verification_otps', [
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'pending_value' => $targetAddress,
            'destination' => $member->email,
        ]);
    }

    public function test_otp_sent_to_registered_member_email_and_ignores_frontend_email(): void
    {
        Mail::fake();

        $member = $this->createMember(['email' => 'canonical@testmail.com']);
        RateLimiter::clear(MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION, $member->id));
        $targetAddress = '0x71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3';

        // Attacker passes an external email in payload
        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', [
                'wallet_address' => $targetAddress,
                'email' => 'attacker@malicious.com',
            ]);

        $response->assertStatus(200);

        // Mail must ONLY be sent to member's canonical email
        Mail::assertSent(MemberWeb3WalletVerificationOtp::class, function ($mail) use ($member) {
            return $mail->hasTo('canonical@testmail.com') && !$mail->hasTo('attacker@malicious.com');
        });
    }

    public function test_otp_cooldown_rate_limit_prevents_spam(): void
    {
        Mail::fake();
        $member = $this->createMember();
        RateLimiter::clear(MemberOtpService::cooldownRateKey(MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION, $member->id));
        $targetAddress = '0x71C84d28430b8e7C9fAcAbF571aF57F26E63F8B3';

        // 1st request succeeds
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', ['wallet_address' => $targetAddress])
            ->assertStatus(200);

        // Immediate 2nd request is rate limited (422)
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/send-otp', ['wallet_address' => $targetAddress])
            ->assertStatus(422);
    }

    public function test_correct_otp_verifies_and_activates_reward_wallet_address(): void
    {
        $member = $this->createMember([
            'reward_balance' => 0.25,
            'ad_balance' => 50.00,
        ]);
        $targetAddress = '0x90F79bf6EB2c4f870365E785982E1f101E93b906';

        // Create OTP challenge
        $otpCode = '849201';
        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $targetAddress,
            'code_hash' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', [
                'otp' => $otpCode,
                'wallet_address' => $targetAddress,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'wallet' => [
                    'wallet_address' => $targetAddress,
                    'wallet_network' => 'BEP-20',
                    'wallet_currency' => 'USDT',
                    'wallet_status' => 'verified',
                    'has_verified_wallet' => true,
                    'reward_balance' => 0.25,
                ],
            ]);

        $freshMember = $member->fresh();
        $this->assertEquals($targetAddress, $freshMember->reward_wallet_address);
        $this->assertEquals('BEP-20', $freshMember->reward_wallet_network);
        $this->assertEquals('USDT', $freshMember->reward_wallet_currency);
        $this->assertNotNull($freshMember->reward_wallet_verified_at);
        $this->assertTrue($freshMember->hasVerifiedRewardWallet());
        // Balances remain completely unchanged
        $this->assertEquals(0.25, (float) $freshMember->reward_balance);
        $this->assertEquals(50.00, (float) $freshMember->ad_balance);
    }

    public function test_wrong_otp_does_not_activate_wallet_address(): void
    {
        $member = $this->createMember([
            'reward_wallet_address' => null,
            'reward_wallet_verified_at' => null,
        ]);
        $targetAddress = '0x90F79bf6EB2c4f870365E785982E1f101E93b906';

        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $targetAddress,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', [
                'otp' => '999999', // wrong OTP
            ]);

        $response->assertStatus(422);

        $freshMember = $member->fresh();
        $this->assertNull($freshMember->reward_wallet_address);
        $this->assertNull($freshMember->reward_wallet_verified_at);
    }

    public function test_expired_otp_does_not_activate_wallet_address(): void
    {
        $member = $this->createMember([
            'reward_wallet_address' => null,
            'reward_wallet_verified_at' => null,
        ]);
        $targetAddress = '0x90F79bf6EB2c4f870365E785982E1f101E93b906';

        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $targetAddress,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->subMinutes(5), // Expired!
            'attempts' => 0,
        ]);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', [
                'otp' => '654321',
            ]);

        $response->assertStatus(422);

        $freshMember = $member->fresh();
        $this->assertNull($freshMember->reward_wallet_address);
        $this->assertNull($freshMember->reward_wallet_verified_at);
    }

    public function test_replayed_otp_is_rejected(): void
    {
        $member = $this->createMember();
        $targetAddress = '0x90F79bf6EB2c4f870365E785982E1f101E93b906';

        $otp = MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $targetAddress,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => now()->subMinute(), // already consumed
            'attempts' => 1,
        ]);

        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', ['otp' => '123456'])
            ->assertStatus(422);
    }

    public function test_otp_issued_for_wallet_a_cannot_verify_wallet_b(): void
    {
        $member = $this->createMember();
        $walletA = '0xAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        $walletB = '0xBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';

        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $walletA,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Attempting to verify wallet B using OTP issued for wallet A must fail
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', [
                'otp' => '123456',
                'wallet_address' => $walletB,
            ])
            ->assertStatus(422);

        $this->assertNull($member->fresh()->reward_wallet_address);
    }

    public function test_too_many_attempts_invalidates_otp_challenge(): void
    {
        $member = $this->createMember();
        $targetAddress = '0x90F79bf6EB2c4f870365E785982E1f101E93b906';

        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $targetAddress,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => MemberOtpService::MAX_ATTEMPTS, // Exceeded!
        ]);

        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', ['otp' => '123456'])
            ->assertStatus(422);

        $this->assertNull($member->fresh()->reward_wallet_address);
    }

    public function test_changing_wallet_address_keeps_old_address_active_until_new_otp_succeeds(): void
    {
        $oldAddress = '0x1111111111111111111111111111111111111111';
        $newAddress = '0x2222222222222222222222222222222222222222';

        $member = $this->createMember([
            'reward_wallet_address' => $oldAddress,
            'reward_wallet_network' => 'BEP-20',
            'reward_wallet_currency' => 'USDT',
            'reward_wallet_verified_at' => now()->subDays(5),
            'reward_balance' => 0.40,
        ]);

        // Request OTP for new address
        $otpCode = '777888';
        MemberVerificationOtp::create([
            'member_id' => $member->id,
            'purpose' => MemberOtpService::PURPOSE_WEB3_WALLET_VERIFICATION,
            'destination' => $member->email,
            'pending_value' => $newAddress,
            'code_hash' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Try wrong OTP
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', ['otp' => '000000'])
            ->assertStatus(422);

        // Old address MUST still be active
        $this->assertEquals($oldAddress, $member->fresh()->reward_wallet_address);
        $this->assertEquals(0.40, (float) $member->fresh()->reward_balance);

        // Now submit correct OTP
        $this->actingAs($member, 'member')
            ->postJson('/api/member/rewards/wallet/verify-otp', ['otp' => $otpCode])
            ->assertStatus(200);

        // New address is now active
        $this->assertEquals($newAddress, $member->fresh()->reward_wallet_address);
        $this->assertEquals(0.40, (float) $member->fresh()->reward_balance);
    }

    public function test_cross_member_reward_wallet_isolation(): void
    {
        $memberA = $this->createMember([
            'reward_wallet_address' => '0xAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            'reward_wallet_verified_at' => now(),
            'reward_balance' => 1.50,
        ]);

        $memberB = $this->createMember([
            'reward_wallet_address' => '0xBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
            'reward_wallet_verified_at' => now(),
            'reward_balance' => 0.50,
        ]);

        // Member A retrieves wallet
        $resA = $this->actingAs($memberA, 'member')->getJson('/api/member/rewards/wallet');
        $resA->assertStatus(200)
            ->assertJson([
                'wallet' => [
                    'wallet_address' => '0xAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
                    'reward_balance' => 1.50,
                ],
            ]);

        // Member B retrieves wallet
        $resB = $this->actingAs($memberB, 'member')->getJson('/api/member/rewards/wallet');
        $resB->assertStatus(200)
            ->assertJson([
                'wallet' => [
                    'wallet_address' => '0xBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
                    'reward_balance' => 0.50,
                ],
            ]);
    }
}