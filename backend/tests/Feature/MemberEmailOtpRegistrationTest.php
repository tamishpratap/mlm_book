<?php

namespace Tests\Feature;

use App\Mail\MemberRegistrationOtpMail;
use App\Models\Member;
use App\Models\PendingMemberRegistration;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberEmailOtpRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(ValidateCsrfToken::class)) {
            $this->withoutMiddleware(ValidateCsrfToken::class);
        }
        if (class_exists(VerifyCsrfToken::class)) {
            $this->withoutMiddleware(VerifyCsrfToken::class);
        }
    }

    public function test_registration_page_is_accessible(): void
    {
        $response = $this->get(route('member.register'));

        $response->assertStatus(200);
        $response->assertSee('Create your account');
        $response->assertSee('Full name');
        $response->assertSee('User ID');
        $response->assertSee('Email address');
    }

    public function test_submitting_registration_creates_pending_record_and_does_not_create_member(): void
    {
        Mail::fake();

        $email = 'newuser_' . Str::random(8) . '@example.com';
        $userId = 'TEST' . rand(100000, 999999);

        $response = $this->post(route('member.register.submit'), [
            'name' => 'Jane Tester',
            'user_id' => $userId,
            'email' => $email,
            'phone' => '+9198' . rand(10000000, 99999999),
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
        ]);

        $response->assertRedirect(route('member.register.verify'));
        $response->assertSessionHas('pending_registration_token');

        // Verify Member table DOES NOT contain the new user
        $this->assertDatabaseMissing('members', [
            'email' => $email,
            'user_id' => $userId,
        ]);

        // Verify pending_member_registrations table DOES contain the record
        $this->assertDatabaseHas('pending_member_registrations', [
            'email' => $email,
            'user_id' => $userId,
            'name' => 'Jane Tester',
        ]);

        $pending = PendingMemberRegistration::where('email', $email)->first();
        $this->assertNotNull($pending);
        $this->assertNotEquals('SecretPass123!', $pending->password_hash);
        $this->assertTrue(Hash::check('SecretPass123!', $pending->password_hash));

        // Verify email was sent
        Mail::assertSent(MemberRegistrationOtpMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email) && strlen($mail->otpCode) === 6;
        });
    }

    public function test_verify_email_page_accessible_with_pending_session(): void
    {
        $token = Str::random(64);
        $email = 'verify_' . Str::random(8) . '@example.com';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Verify User',
            'user_id' => 'vuser_' . Str::lower(Str::random(6)),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->get(route('member.register.verify'));

        $response->assertStatus(200);
        $response->assertSee('Verify Your Email');
        $response->assertSee($email);
        $response->assertSee('Verification Code');
    }

    public function test_verify_otp_fails_with_wrong_code_and_increments_attempts(): void
    {
        $token = Str::random(64);
        $email = 'wrongotp_' . Str::random(8) . '@example.com';

        $pending = PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Wrong OTP User',
            'user_id' => 'wuser_' . Str::lower(Str::random(6)),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->from(route('member.register.verify'))
            ->post(route('member.register.verify.submit'), [
                'otp' => '111111',
            ]);

        $response->assertRedirect(route('member.register.verify'));
        $response->assertSessionHasErrors('otp');

        // Member must NOT be created
        $this->assertDatabaseMissing('members', ['email' => $email]);

        // Attempts incremented
        $pending->refresh();
        $this->assertEquals(1, $pending->attempts);
    }

    public function test_verify_otp_blocks_when_max_attempts_exceeded(): void
    {
        $token = Str::random(64);
        $email = 'maxatt_' . Str::random(8) . '@example.com';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Max Attempts User',
            'user_id' => 'muser_' . Str::lower(Str::random(6)),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 5,
            'last_resend_at' => now(),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->from(route('member.register.verify'))
            ->post(route('member.register.verify.submit'), [
                'otp' => '654321', // even if code is right, attempts exceeded
            ]);

        $response->assertRedirect(route('member.register.verify'));
        $response->assertSessionHasErrors('otp');
        $this->assertDatabaseMissing('members', ['email' => $email]);
    }

    public function test_verify_otp_fails_when_expired(): void
    {
        $token = Str::random(64);
        $email = 'expired_' . Str::random(8) . '@example.com';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Expired User',
            'user_id' => 'expuser_' . Str::lower(Str::random(6)),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(1),
            'attempts' => 0,
            'last_resend_at' => now()->subMinutes(11),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->from(route('member.register.verify'))
            ->post(route('member.register.verify.submit'), [
                'otp' => '123456',
            ]);

        $response->assertRedirect(route('member.register.verify'));
        $response->assertSessionHasErrors('otp');
        $this->assertDatabaseMissing('members', ['email' => $email]);
    }

    public function test_resend_otp_enforces_cooldown_and_generates_new_otp(): void
    {
        Mail::fake();

        $token = Str::random(64);
        $email = 'resend_' . Str::random(8) . '@example.com';

        $pending = PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Resend User',
            'user_id' => 'rsuser_' . Str::lower(Str::random(6)),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 2,
            'last_resend_at' => now()->subSeconds(30), // 30s ago, still in 60s cooldown
        ]);

        // Attempt resend inside cooldown
        $response = $this->withSession(['pending_registration_token' => $token])
            ->from(route('member.register.verify'))
            ->post(route('member.register.resend-otp'));

        $response->assertRedirect(route('member.register.verify'));
        $response->assertSessionHasErrors('otp');

        // Fast-forward cooldown to 65 seconds ago
        $pending->update(['last_resend_at' => now()->subSeconds(65)]);

        $response2 = $this->withSession(['pending_registration_token' => $token])
            ->from(route('member.register.verify'))
            ->post(route('member.register.resend-otp'));

        $response2->assertRedirect(route('member.register.verify'));
        $response2->assertSessionHas('status');

        $pending->refresh();
        $this->assertEquals(0, $pending->attempts);
        $this->assertFalse(Hash::check('111111', $pending->otp_hash)); // Old OTP invalidated

        Mail::assertSent(MemberRegistrationOtpMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    public function test_successful_otp_creates_member_authenticates_and_cleans_up_pending(): void
    {
        $token = Str::random(64);
        $email = 'success_' . Str::random(8) . '@example.com';
        $userId = 'succ_' . Str::lower(Str::random(8));
        $otp = '789012';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Success Tester',
            'user_id' => $userId,
            'email' => $email,
            'password_hash' => Hash::make('SecurePassword123!'),
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->post(route('member.register.verify.submit'), [
                'otp' => $otp,
            ]);

        $response->assertRedirect(route('member.dashboard'));

        // Member record MUST exist in database
        $this->assertDatabaseHas('members', [
            'email' => $email,
            'user_id' => $userId,
            'name' => 'Success Tester',
        ]);

        // Pending record MUST be deleted
        $this->assertDatabaseMissing('pending_member_registrations', [
            'token' => $token,
        ]);

        // User must be authenticated with member guard
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertEquals($email, Auth::guard('member')->user()->email);
    }

    public function test_registration_validation_prevents_duplicate_email(): void
    {
        $existing = $this->createMember(['email' => 'duplicate@example.com']);

        $response = $this->from(route('member.register'))
            ->post(route('member.register.submit'), [
                'name' => 'Duplicate Email Tester',
                'user_id' => 'dupuser_' . Str::lower(Str::random(6)),
                'email' => $existing->email,
                'phone' => '+9198' . rand(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertRedirect(route('member.register'));
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_validation_prevents_duplicate_user_id(): void
    {
        $existing = $this->createMember(['user_id' => 'unique_user_id']);

        $response = $this->from(route('member.register'))
            ->post(route('member.register.submit'), [
                'name' => 'Duplicate UserId Tester',
                'user_id' => $existing->user_id,
                'email' => 'unique_' . Str::random(8) . '@example.com',
                'phone' => '+9198' . rand(10000000, 99999999),
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertRedirect(route('member.register'));
        $response->assertSessionHasErrors('user_id');
    }

    public function test_existing_member_login_remains_functional(): void
    {
        $email = 'login_test_' . Str::random(8) . '@example.com';
        $password = 'MySecretPass123!';

        $member = Member::create([
            'name' => 'Existing Member Login',
            'user_id' => 'exist_' . Str::lower(Str::random(8)),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $response = $this->post(route('member.login.submit'), [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertTrue(Auth::guard('member')->check());
        $this->assertEquals($member->id, Auth::guard('member')->id());
    }

    public function test_cancel_registration_cleans_up_and_redirects(): void
    {
        $token = Str::random(64);
        $email = 'cancel_' . Str::random(8) . '@example.com';

        PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Cancel User',
            'user_id' => 'canc_' . Str::lower(Str::random(8)),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now(),
        ]);

        $response = $this->withSession(['pending_registration_token' => $token])
            ->post(route('member.register.cancel'));

        $response->assertRedirect(route('member.register'));
        $response->assertSessionMissing('pending_registration_token');
        $this->assertDatabaseMissing('pending_member_registrations', ['token' => $token]);
    }

    public function test_registration_returns_error_and_cleans_up_when_mail_delivery_fails(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP server connection failed'));

        $email = 'fail_' . Str::random(8) . '@example.com';
        $userId = 'FAIL' . rand(100000, 999999);

        $response = $this->post(route('member.register.submit'), [
            'name' => 'Failing User',
            'user_id' => $userId,
            'email' => $email,
            'phone' => '+9198' . rand(10000000, 99999999),
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('pending_registration_token');
        $this->assertDatabaseMissing('pending_member_registrations', ['email' => $email]);
    }

    public function test_resend_otp_returns_error_when_mail_delivery_fails(): void
    {
        $token = Str::random(64);
        $email = 'resendfail_' . Str::random(8) . '@example.com';

        $pending = PendingMemberRegistration::create([
            'token' => $token,
            'name' => 'Resend Fail',
            'user_id' => 'RFAL' . rand(100000, 999999),
            'email' => $email,
            'password_hash' => Hash::make('Password123!'),
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_resend_at' => now()->subMinutes(2),
        ]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP server down'));

        $response = $this->withSession(['pending_registration_token' => $token])
            ->post(route('member.register.resend-otp'));

        $response->assertSessionHasErrors('otp');
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'user_id' => 'test_member_' . Str::lower(Str::random(6)),
            'email' => 'member_' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
        ], $attributes));
    }
}
