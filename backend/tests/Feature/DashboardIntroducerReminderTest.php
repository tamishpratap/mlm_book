<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardIntroducerReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_endpoint_returns_null_introducer_id_when_unassigned(): void
    {
        $member = Member::create([
            'name' => 'Unassigned User',
            'user_id' => 'unassigned_user',
            'email' => 'unassigned@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/dashboard');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'member' => [
                    'id' => $member->id,
                    'user_id' => 'unassigned_user',
                    'introducer_id' => null,
                ],
            ]);
    }

    public function test_dashboard_endpoint_returns_assigned_introducer_id(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $member = Member::create([
            'name' => 'Assigned User',
            'user_id' => 'assigned_user',
            'email' => 'assigned@example.com',
            'password' => 'secret123',
            'introducer_id' => 'sponsor_alice',
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/dashboard');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'member' => [
                    'id' => $member->id,
                    'user_id' => 'assigned_user',
                    'introducer_id' => 'sponsor_alice',
                ],
            ]);
    }

    public function test_unauthenticated_request_to_dashboard_is_rejected(): void
    {
        $response = $this->getJson('/api/member/dashboard');

        $response->assertStatus(401);
    }

    public function test_after_claiming_introducer_dashboard_reflects_updated_state(): void
    {
        $sponsor = Member::create([
            'name' => 'Sponsor Alice',
            'user_id' => 'sponsor_alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);

        $member = Member::create([
            'name' => 'Claiming User',
            'user_id' => 'claiming_user',
            'email' => 'claiming@example.com',
            'password' => 'secret123',
            'introducer_id' => null,
            'mobile_verified_at' => now(),
        ]);

        // Step 1: Initial dashboard returns null
        $initialDash = $this->actingAs($member, 'member')
            ->getJson('/api/member/dashboard');
        $initialDash->assertOk();
        $this->assertNull($initialDash->json('member.introducer_id'));

        // Step 2: Claim introducer via Account Settings endpoint
        $claimResponse = $this->actingAs($member, 'member')
            ->postJson('/api/member/account/introducer', [
                'introducer_id' => 'sponsor_alice',
            ]);
        $claimResponse->assertOk()
            ->assertJson(['success' => true]);

        // Step 3: Subsequent dashboard request reflects assigned introducer
        $afterDash = $this->actingAs($member->fresh(), 'member')
            ->getJson('/api/member/dashboard');
        $afterDash->assertOk();
        $this->assertSame('sponsor_alice', $afterDash->json('member.introducer_id'));
    }
}
