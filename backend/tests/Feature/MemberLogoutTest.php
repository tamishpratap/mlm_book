<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_can_logout_from_the_dashboard(): void
    {
        $member = Member::create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => 'secure-password',
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee(route('member.logout'), false)
            ->assertSee('<strong>Logout</strong>', false);

        $this->post(route('member.logout'))
            ->assertRedirect(route('member.login'));

        $this->assertGuest('member');
    }
}
