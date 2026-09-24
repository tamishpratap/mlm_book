<?php

namespace Tests\Feature;

use App\Mail\MemberResetPasswordMail;
use App\Models\Member;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberPasswordResetFlowTest extends TestCase
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

    public function test_forgot_password_sends_email_with_token_and_reset_url(): void
    {
        Mail::fake();

        $member = Member::create([
            'name' => 'Reset Test Member',
            'user_id' => 'RSTT' . rand(100000, 999999),
            'email' => 'reset_target_' . Str::random(8) . '@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->post(route('member.forgot-password.send'), [
            'email' => $member->email,
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $member->email,
        ]);

        Mail::assertSent(MemberResetPasswordMail::class, function ($mail) use ($member) {
            return $mail->hasTo($member->email) && $mail->member->id === $member->id;
        });
    }

    public function test_forgot_password_cleans_up_and_returns_error_when_mail_fails(): void
    {
        $member = Member::create([
            'name' => 'Reset Fail Member',
            'user_id' => 'RFAL' . rand(100000, 999999),
            'email' => 'reset_fail_' . Str::random(8) . '@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP host unavailable'));

        $response = $this->post(route('member.forgot-password.send'), [
            'email' => $member->email,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $member->email,
        ]);
    }

    public function test_reset_password_with_valid_token_updates_password(): void
    {
        $member = Member::create([
            'name' => 'Token Test Member',
            'user_id' => 'TOKN' . rand(100000, 999999),
            'email' => 'tokentest_' . Str::random(8) . '@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $plainToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $member->email,
            'token' => Hash::make($plainToken),
            'created_at' => now(),
        ]);

        $response = $this->post(route('member.password.update'), [
            'token' => $plainToken,
            'email' => $member->email,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertRedirect(route('member.login'));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $member->email]);

        $freshMember = Member::find($member->id);
        $this->assertTrue(Hash::check('BrandNewPassword123!', $freshMember->password));
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $member = Member::create([
            'name' => 'Bad Token Member',
            'user_id' => 'BTKN' . rand(100000, 999999),
            'email' => 'badtoken_' . Str::random(8) . '@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $member->email,
            'token' => Hash::make('correct_token'),
            'created_at' => now(),
        ]);

        $response = $this->post(route('member.password.update'), [
            'token' => 'completely_wrong_token',
            'email' => $member->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_fails_with_expired_token(): void
    {
        $member = Member::create([
            'name' => 'Expired Member',
            'user_id' => 'EXPR' . rand(100000, 999999),
            'email' => 'expired_' . Str::random(8) . '@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $plainToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $member->email,
            'token' => Hash::make($plainToken),
            'created_at' => Carbon::now()->subMinutes(90),
        ]);

        $response = $this->post(route('member.password.update'), [
            'token' => $plainToken,
            'email' => $member->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $member->email]);
    }
}
