<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PendingMemberRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberEmailAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }
        if (class_exists(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        }
    }

    public function test_member_registration_requires_email_otp_verification(): void
    {
        $this->post(route('member.register.submit'), [
            'name' => 'Email Member',
            'user_id' => 'EMAL123456',
            'email' => 'email-member@example.com',
            'phone' => '+919876543210',
            'password' => 'secure-password',
        ])->assertRedirect(route('member.register.verify'));

        // Member must NOT exist before OTP verification
        $this->assertDatabaseMissing('members', ['email' => 'email-member@example.com']);

        // Pending record exists
        $pending = PendingMemberRegistration::where('email', 'email-member@example.com')->sole();
        $this->assertSame('EMAL123456', $pending->user_id);

        // Update OTP to known value for verification test
        $pending->update(['otp_hash' => Hash::make('654321')]);

        // Submit OTP verification
        $this->withSession(['pending_registration_token' => $pending->token])
            ->post(route('member.register.verify.submit'), [
                'otp' => '654321',
            ])->assertRedirect(route('member.dashboard'));

        $member = Member::where('email', 'email-member@example.com')->sole();
        $this->assertSame('EMAL123456', $member->user_id);
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_member_can_login_with_email_and_password(): void
    {
        $member = Member::create([
            'name' => 'Email Member',
            'user_id' => 'email_login_user',
            'email' => 'email-login@example.com',
            'password' => Hash::make('secure-password'),
        ]);

        $this->post(route('member.login.submit'), [
            'email' => 'email-login@example.com',
            'password' => 'secure-password',
        ])->assertRedirect(route('member.dashboard'));

        $this->assertAuthenticatedAs($member, 'member');
    }
}
