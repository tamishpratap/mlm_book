<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdRewardRule;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdRewardRuleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test initial seeded slabs in database.
     */
    public function test_initial_slabs_structure(): void
    {
        // 3 rules should exist
        $rules = AdRewardRule::orderBy('min_referrals', 'asc')->get();
        $this->assertGreaterThanOrEqual(3, $rules->count());

        // Rule 1: 0-5
        $r1 = $rules->firstWhere('min_referrals', 0);
        $this->assertNotNull($r1);
        $this->assertEquals(5, $r1->max_referrals);

        // Rule 2: 6-14
        $r2 = $rules->firstWhere('min_referrals', 6);
        $this->assertNotNull($r2);
        $this->assertEquals(14, $r2->max_referrals);
        $this->assertEquals('0.0350', (string) $r2->reward_amount);

        // Rule 3: 15+ (unlimited)
        $r3 = $rules->firstWhere('min_referrals', 15);
        $this->assertNotNull($r3);
        $this->assertNull($r3->max_referrals);
        $this->assertEquals('0.0500', (string) $r3->reward_amount);
    }

    /**
     * Test conceptual reward resolution across referral counts.
     */
    public function test_rule_resolution_boundaries(): void
    {
        // Set 0-5 rule to 0.0250 for resolution testing
        $r1 = AdRewardRule::where('min_referrals', 0)->first();
        $r1->update(['reward_amount' => 0.0250, 'is_active' => true]);
        AdRewardRule::clearCache();

        // 0 referrals -> 0.0250
        $res0 = AdRewardRule::resolveForDirectVerifiedReferrals(0);
        $this->assertEquals('0.0250', (string) $res0->reward_amount);

        // 5 referrals -> 0.0250
        $res5 = AdRewardRule::resolveForDirectVerifiedReferrals(5);
        $this->assertEquals('0.0250', (string) $res5->reward_amount);

        // 6 referrals -> 0.0350
        $res6 = AdRewardRule::resolveForDirectVerifiedReferrals(6);
        $this->assertEquals('0.0350', (string) $res6->reward_amount);

        // 14 referrals -> 0.0350
        $res14 = AdRewardRule::resolveForDirectVerifiedReferrals(14);
        $this->assertEquals('0.0350', (string) $res14->reward_amount);

        // 15 referrals -> 0.0500
        $res15 = AdRewardRule::resolveForDirectVerifiedReferrals(15);
        $this->assertEquals('0.0500', (string) $res15->reward_amount);

        // 100 referrals -> 0.0500
        $res100 = AdRewardRule::resolveForDirectVerifiedReferrals(100);
        $this->assertEquals('0.0500', (string) $res100->reward_amount);
    }

    /**
     * Test overlap validation blocks conflicting active ranges.
     */
    public function test_overlap_validation_blocks_conflicting_ranges(): void
    {
        // Attempting to check an overlapping range 0-10 with existing 0-5 and 6-14
        $overlap = AdRewardRule::checkOverlap(0, 10);
        $this->assertNotNull($overlap);

        $overlap2 = AdRewardRule::checkOverlap(5, 15);
        $this->assertNotNull($overlap2);

        // Non-overlapping range against an empty/disabled rule should pass
        $overlap3 = AdRewardRule::checkOverlap(50, 100);
        // Existing 15+ is unlimited, so 50-100 overlaps with 15+
        $this->assertNotNull($overlap3);
        $this->assertEquals(15, $overlap3->min_referrals);
    }

    /**
     * Test continuity check detects gaps and invalid configurations.
     */
    public function test_continuity_validation(): void
    {
        $errors = AdRewardRule::validateActiveSetContinuity();
        $this->assertEmpty($errors);
    }

    /**
     * Test admin authorization required and member access blocked.
     */
    public function test_authorization_protections(): void
    {
        // Unauthenticated access
        $response = $this->getJson('/api/admin/ad-reward-rules');
        $response->assertStatus(401);

        // Member attempting to access admin endpoints
        $member = Member::first() ?? Member::create([
            'name' => 'Test Member',
            'user_id' => 'testmember01',
            'email' => 'testmember@example.com',
            'password' => 'password123',
        ]);

        $response = $this->actingAs($member, 'member')->getJson('/api/admin/ad-reward-rules');
        $response->assertStatus(401);

        // Admin access allowed
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/ad-reward-rules');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'rules',
            'is_configuration_complete',
            'previews',
            'max_permissible_reward_usd',
        ]);
    }

    /**
     * Test server validation for invalid inputs.
     */
    public function test_server_validation_rejects_invalid_inputs(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        // Negative referrals
        $res = $this->actingAs($admin, 'admin')->postJson('/api/admin/ad-reward-rules', [
            'min_referrals' => -1,
            'max_referrals' => 5,
            'reward_amount' => 0.025,
        ]);
        $res->assertStatus(422);

        // Negative reward
        $res = $this->actingAs($admin, 'admin')->postJson('/api/admin/ad-reward-rules', [
            'min_referrals' => 20,
            'max_referrals' => 30,
            'reward_amount' => -0.025,
        ]);
        $res->assertStatus(422);

        // Reward exceeding max $0.050
        $res = $this->actingAs($admin, 'admin')->postJson('/api/admin/ad-reward-rules', [
            'min_referrals' => 20,
            'max_referrals' => 30,
            'reward_amount' => 0.080,
        ]);
        $res->assertStatus(422);

        // Min > Max
        $res = $this->actingAs($admin, 'admin')->postJson('/api/admin/ad-reward-rules', [
            'min_referrals' => 10,
            'max_referrals' => 5,
            'reward_amount' => 0.025,
        ]);
        $res->assertStatus(422);
    }

    /**
     * Test exact decimal precision preservation for 0.025, 0.035, 0.050.
     */
    public function test_exact_decimal_precision(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $r1 = AdRewardRule::where('min_referrals', 0)->first();
        $this->actingAs($admin, 'admin')->putJson("/api/admin/ad-reward-rules/{$r1->id}", [
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.025,
            'is_active' => true,
        ])->assertStatus(200);

        $fresh = AdRewardRule::find($r1->id);
        $this->assertEquals('0.0250', (string) $fresh->reward_amount);
        $this->assertNotEquals('0.03', (string) $fresh->reward_amount);
    }

    /**
     * Test safe disable preserves row and excludes from active cache.
     */
    public function test_safe_disable_and_cache_invalidation(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $r2 = AdRewardRule::where('min_referrals', 6)->first();
        $this->assertTrue($r2->is_active);

        // Toggle status to disabled
        $res = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-reward-rules/{$r2->id}/toggle-status");
        $res->assertStatus(200);

        $r2Fresh = AdRewardRule::find($r2->id);
        $this->assertFalse($r2Fresh->is_active);

        // Row is still preserved in DB
        $this->assertDatabaseHas('ad_reward_rules', ['id' => $r2->id]);

        // Referral 8 should not resolve to disabled rule
        $resolved8 = AdRewardRule::resolveForDirectVerifiedReferrals(8);
        $this->assertNull($resolved8);
    }

    /**
     * Test gap detection when an intermediate tier is missing.
     */
    public function test_gap_detection(): void
    {
        // Disable 6-14 rule -> gap between 0-5 and 15+
        $r2 = AdRewardRule::where('min_referrals', 6)->first();
        $r2->update(['is_active' => false]);
        AdRewardRule::clearCache();

        $errors = AdRewardRule::validateActiveSetContinuity();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Gap detected', $errors[0]);
    }
}
