<?php

namespace Tests\Feature;

use App\Mail\MemberEmailVerificationOtp;
use App\Models\Member;
use App\Models\MemberVerificationOtp;
use App\Services\MemberOtpService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MemberAccountVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_otp_verification_updates_member_phone(): void
    {
        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->post(route('member.account.mobile.send-otp'), [
                'mobile_number' => '+919876543210',
            ])
            ->assertSessionHas('success');

        $otp = MemberVerificationOtp::query()->sole();
        $this->assertSame('+919876543210', $otp->pending_value);

        $this->actingAs($member, 'member')
            ->post(route('member.account.mobile.verify-otp'), ['mobile_otp' => '123456'])
            ->assertSessionHasErrors('mobile_otp');

        $otp->update(['code_hash' => Hash::make('654321')]);

        $this->actingAs($member, 'member')
            ->post(route('member.account.mobile.verify-otp'), ['mobile_otp' => '654321'])
            ->assertSessionHas('success');

        $this->assertSame('+919876543210', $member->fresh()->phone);
        $this->assertNotNull($member->fresh()->mobile_verified_at);
    }

    public function test_email_otp_verification_updates_member_email(): void
    {
        Mail::fake();

        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->post(route('member.account.email.send-otp'), [
                'new_email' => 'new-address@example.com',
            ])
            ->assertSessionHas('success');

        $otp = MemberVerificationOtp::query()->sole();
        $this->assertSame('new-address@example.com', $otp->destination);
        $this->assertSame('new-address@example.com', $otp->pending_value);

        $otp->update(['code_hash' => Hash::make('123456')]);

        $this->actingAs($member, 'member')
            ->post(route('member.account.email.verify-otp'), ['email_otp' => '123456'])
            ->assertSessionHas('success');

        $this->assertSame('new-address@example.com', $member->fresh()->email);
    }

    public function test_test_a_new_email_request_generates_otp_and_dispatches_mail_to_new_recipient(): void
    {
        Mail::fake();

        $member = $this->createMember(['email' => 'old.address@example.com']);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/send-otp', [
                'new_email' => 'new.address@example.com',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Verification code sent to new.address@example.com',
            'destination' => 'new.address@example.com',
        ]);

        $otp = MemberVerificationOtp::query()->sole();
        $this->assertSame($member->id, $otp->member_id);
        $this->assertSame(MemberOtpService::PURPOSE_EMAIL_CHANGE, $otp->purpose);
        $this->assertSame('new.address@example.com', $otp->destination);
        $this->assertSame('new.address@example.com', $otp->pending_value);

        Mail::assertSent(MemberEmailVerificationOtp::class, function ($mail) {
            return $mail->hasTo('new.address@example.com')
                && strlen($mail->otpCode) === 6
                && ! empty($mail->memberName);
        });
    }

    public function test_test_b_invalid_email_fails_validation_and_does_not_send_mail(): void
    {
        Mail::fake();

        $member = $this->createMember(['email' => 'current@example.com']);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/send-otp', [
                'new_email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('new_email');

        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('member_verification_otps');
    }

    public function test_test_c_existing_email_fails_validation_and_does_not_send_mail(): void
    {
        Mail::fake();

        $this->createMember(['email' => 'taken@example.com']);
        $member = $this->createMember(['email' => 'current@example.com']);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/send-otp', [
                'new_email' => 'taken@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('new_email');

        Mail::assertNothingSent();
        $this->assertDatabaseEmpty('member_verification_otps');
    }

    public function test_test_d_otp_verification_updates_email_and_invalidates_otp_for_reuse(): void
    {
        $member = $this->createMember(['email' => 'current@example.com']);
        $otp = $this->createOtp($member, MemberOtpService::PURPOSE_EMAIL_CHANGE, 'brandnew@example.com', '654321');

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/verify-otp', [
                'email_otp' => '654321',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Email address updated successfully.',
        ]);

        $this->assertSame('brandnew@example.com', $member->fresh()->email);
        $this->assertNotNull($otp->fresh()->verified_at);

        // Subsequent attempt with same code must fail
        $secondResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/verify-otp', [
                'email_otp' => '654321',
            ]);

        $secondResponse->assertStatus(422);
    }

    public function test_test_e_wrong_otp_fails_and_does_not_update_email(): void
    {
        $member = $this->createMember(['email' => 'current@example.com']);
        $otp = $this->createOtp($member, MemberOtpService::PURPOSE_EMAIL_CHANGE, 'brandnew@example.com', '654321');

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/verify-otp', [
                'email_otp' => '999999',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email_otp');

        $this->assertSame('current@example.com', $member->fresh()->email);
        $this->assertNull($otp->fresh()->verified_at);
        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_test_f_expired_otp_fails_and_does_not_update_email(): void
    {
        $member = $this->createMember(['email' => 'current@example.com']);
        $otp = $this->createOtp($member, MemberOtpService::PURPOSE_EMAIL_CHANGE, 'brandnew@example.com', '654321');
        $otp->forceFill(['expires_at' => now()->subMinute()])->save();

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/verify-otp', [
                'email_otp' => '654321',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email_otp');

        $this->assertSame('current@example.com', $member->fresh()->email);
    }

    public function test_test_g_mail_failure_returns_clean_error_and_invalidates_otp(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new Exception('Simulated SMTP connection timeout or auth failure'));

        $member = $this->createMember(['email' => 'current@example.com']);

        $response = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/send-otp', [
                'new_email' => 'new.valid@example.com',
            ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Unable to send verification code. Please try again.',
            'errors' => [
                'new_email' => ['Unable to send verification code. Please try again.'],
            ],
        ]);

        // Asserts no dangling unverified OTP was left behind
        $this->assertDatabaseEmpty('member_verification_otps');
        $this->assertSame('current@example.com', $member->fresh()->email);
    }

    public function test_test_h_refresh_account_settings_loads_correct_pending_destination(): void
    {
        Mail::fake();

        $member = $this->createMember(['email' => 'current@example.com']);

        $this->actingAs($member, 'member')
            ->postJson('/api/member/account/email/send-otp', [
                'new_email' => 'pending.new@example.com',
            ])
            ->assertOk();

        $settingsResponse = $this->actingAs($member, 'member')
            ->getJson('/api/member/account/settings');

        $settingsResponse->assertOk();
        $settingsResponse->assertJson([
            'success' => true,
            'member' => ['email' => 'current@example.com'],
            'email_otp_pending' => [
                'destination' => 'pending.new@example.com',
                'pending_value' => 'pending.new@example.com',
            ],
        ]);
    }

    public function test_wrong_or_expired_otp_never_updates_the_member(): void
    {
        $member = $this->createMember();
        $otp = $this->createOtp($member, MemberOtpService::PURPOSE_MOBILE_CHANGE, '+919876543210', '123456');

        $this->actingAs($member, 'member')
            ->post(route('member.account.mobile.verify-otp'), ['mobile_otp' => '999999'])
            ->assertSessionHasErrors('mobile_otp');

        $this->assertSame(1, $otp->fresh()->attempts);

        $otp->forceFill(['expires_at' => now()->subSecond()])->save();
        $this->post(route('member.account.mobile.verify-otp'), ['mobile_otp' => '123456'])
            ->assertSessionHasErrors('mobile_otp');
    }

    public function test_contact_verification_routes_require_member_authentication(): void
    {
        $this->post(route('member.account.mobile.send-otp'))->assertRedirect(route('member.login'));
        $this->post(route('member.account.mobile.verify-otp'))->assertRedirect(route('member.login'));
        $this->post(route('member.account.email.send-otp'))->assertRedirect(route('member.login'));
        $this->post(route('member.account.email.verify-otp'))->assertRedirect(route('member.login'));
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'user_id' => 'test_member_' . random_int(100, 999),
            'email' => 'member' . random_int(100, 999) . '@example.com',
            'password' => 'old-password',
        ], $attributes));
    }

    private function createOtp(Member $member, string $purpose, string $pendingValue, string $plainCode): MemberVerificationOtp
    {
        return MemberVerificationOtp::create([
            'member_id' => $member->getKey(),
            'purpose' => $purpose,
            'destination' => $pendingValue,
            'pending_value' => $pendingValue,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(10),
        ]);
    }
}

