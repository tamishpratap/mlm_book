<?php

namespace Tests\Feature;

use App\Models\AdRewardRule;
use App\Models\Follower;
use App\Models\Friendship;
use App\Models\Member;
use App\Services\RewardRuleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RewardRuleResolverTest extends TestCase
{
    use RefreshDatabase;

    protected RewardRuleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new RewardRuleResolver();

        // Ensure 0-5 slab has $0.025 configured for test baseline
        $r1 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 0],
            ['max_referrals' => 5, 'reward_amount' => 0.0250, 'is_active' => true]
        );
        $r1->update(['reward_amount' => 0.0250, 'is_active' => true]);

        // Ensure 6-14 slab has $0.035
        $r2 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 6],
            ['max_referrals' => 14, 'reward_amount' => 0.0350, 'is_active' => true]
        );
        $r2->update(['reward_amount' => 0.0350, 'is_active' => true]);

        // Ensure 15+ slab has $0.050
        $r3 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 15],
            ['max_referrals' => null, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r3->update(['reward_amount' => 0.0500, 'is_active' => true]);

        AdRewardRule::clearCache();
    }

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 1;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "user_{$unique}_" . Str::random(5),
            'email' => "user_{$unique}_" . Str::random(5) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Test Member A (0 verified referrals) -> $0.025.
     */
    public function test_member_with_zero_verified_referrals_resolves_first_tier(): void
    {
        $member = $this->createMember([
            'user_id' => 'member_a',
        ]);

        $res = $this->resolver->resolveForMember($member);

        $this->assertTrue($res['success']);
        $this->assertEquals(0, $res['direct_verified_referral_count']);
        $this->assertEquals(0.025, $res['reward_amount_usd']);
        $this->assertEquals('0.0250', $res['reward_amount_exact']);
        $this->assertEquals(0, $res['matched_range']['min']);
        $this->assertEquals(5, $res['matched_range']['max']);
    }

    /**
     * Test Member B (5 verified referrals) -> $0.025.
     */
    public function test_member_with_five_verified_referrals_resolves_first_tier_upper_bound(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_b',
        ]);

        // Create 5 verified direct referrals
        for ($i = 1; $i <= 5; $i++) {
            $this->createMember([
                'user_id' => "ref_b_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        $this->assertEquals(5, $res['direct_verified_referral_count']);
        $this->assertEquals(0.025, $res['reward_amount_usd']);
        $this->assertEquals('0.0250', $res['reward_amount_exact']);
    }

    /**
     * Test Member C (6 verified referrals) -> $0.035.
     */
    public function test_member_with_six_verified_referrals_resolves_second_tier_lower_bound(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_c',
        ]);

        for ($i = 1; $i <= 6; $i++) {
            $this->createMember([
                'user_id' => "ref_c_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        $this->assertEquals(6, $res['direct_verified_referral_count']);
        $this->assertEquals(0.035, $res['reward_amount_usd']);
        $this->assertEquals('0.0350', $res['reward_amount_exact']);
        $this->assertEquals(6, $res['matched_range']['min']);
        $this->assertEquals(14, $res['matched_range']['max']);
    }

    /**
     * Test Member D (14 verified referrals) -> $0.035.
     */
    public function test_member_with_fourteen_verified_referrals_resolves_second_tier_upper_bound(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_d',
        ]);

        for ($i = 1; $i <= 14; $i++) {
            $this->createMember([
                'user_id' => "ref_d_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        $this->assertEquals(14, $res['direct_verified_referral_count']);
        $this->assertEquals(0.035, $res['reward_amount_usd']);
    }

    /**
     * Test Member E (15 verified referrals) -> $0.050.
     */
    public function test_member_with_fifteen_verified_referrals_resolves_third_tier_lower_bound(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_e',
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $this->createMember([
                'user_id' => "ref_e_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        $this->assertEquals(15, $res['direct_verified_referral_count']);
        $this->assertEquals(0.050, $res['reward_amount_usd']);
        $this->assertEquals('0.0500', $res['reward_amount_exact']);
        $this->assertTrue($res['matched_range']['is_unlimited']);
    }

    /**
     * Test Member F (50 verified referrals) -> $0.050.
     */
    public function test_member_with_fifty_verified_referrals_resolves_unlimited_third_tier(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_f',
        ]);

        for ($i = 1; $i <= 50; $i++) {
            $this->createMember([
                'user_id' => "ref_f_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        $this->assertEquals(50, $res['direct_verified_referral_count']);
        $this->assertEquals(0.050, $res['reward_amount_usd']);
    }

    /**
     * Test Member G (10 direct referrals: 7 verified, 3 unverified) -> resolves count 7 -> $0.035.
     */
    public function test_unverified_referrals_are_strictly_excluded_from_count(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_g',
        ]);

        // 7 verified direct referrals
        for ($i = 1; $i <= 7; $i++) {
            $this->createMember([
                'user_id' => "ref_g_ver_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        // 3 unverified direct referrals (mobile_verified_at IS NULL)
        for ($j = 1; $j <= 3; $j++) {
            $this->createMember([
                'user_id' => "ref_g_unver_{$j}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => null,
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        // Must count 7, NOT 10!
        $this->assertEquals(7, $res['direct_verified_referral_count']);
        $this->assertEquals(0.035, $res['reward_amount_usd']);
        $this->assertEquals(6, $res['matched_range']['min']);
        $this->assertEquals(14, $res['matched_range']['max']);
    }

    /**
     * Test Indirect Referrals are strictly excluded (3 direct verified + 100 indirect).
     */
    public function test_indirect_descendants_are_strictly_excluded(): void
    {
        $sponsor = $this->createMember([
            'user_id' => 'member_root',
        ]);

        // 3 direct verified referrals (Level 1)
        $directRefs = [];
        for ($i = 1; $i <= 3; $i++) {
            $directRefs[] = $this->createMember([
                'user_id' => "direct_ref_{$i}",
                'introducer_id' => $sponsor->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        // 100 indirect descendants under direct_ref_0 (Level 2)
        for ($k = 1; $k <= 100; $k++) {
            $this->createMember([
                'user_id' => "indirect_ref_{$k}",
                'introducer_id' => $directRefs[0]->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($sponsor);

        $this->assertTrue($res['success']);
        // Must count 3, NOT 103!
        $this->assertEquals(3, $res['direct_verified_referral_count']);
        $this->assertEquals(0.025, $res['reward_amount_usd']);
    }

    /**
     * Test Followers and Connections do NOT increase referral count.
     */
    public function test_followers_and_connections_do_not_count(): void
    {
        $member = $this->createMember([
            'user_id' => 'member_social',
        ]);

        // Create 20 followers
        for ($i = 1; $i <= 20; $i++) {
            $other = $this->createMember(['user_id' => "follower_{$i}"]);
            Follower::create([
                'follower_id' => $other->id,
                'following_id' => $member->id,
            ]);
        }

        // Create 10 accepted friendships
        for ($j = 1; $j <= 10; $j++) {
            $friend = $this->createMember(['user_id' => "friend_{$j}"]);
            Friendship::create([
                'member_one_id' => $member->id,
                'member_two_id' => $friend->id,
                'requested_by_id' => $member->id,
                'status' => 'accepted',
            ]);
        }

        $res = $this->resolver->resolveForMember($member);

        $this->assertTrue($res['success']);
        $this->assertEquals(0, $res['direct_verified_referral_count']);
        $this->assertEquals(0.025, $res['reward_amount_usd']);
    }

    /**
     * Test unconfigured 0-5 slab returns reward_rule_not_configured.
     */
    public function test_unconfigured_slab_returns_configuration_error(): void
    {
        // Unset 0-5 reward
        $r1 = AdRewardRule::where('min_referrals', 0)->first();
        $r1->update(['reward_amount' => null]);
        AdRewardRule::clearCache();

        $member = $this->createMember([
            'user_id' => 'member_unconf',
        ]);

        $res = $this->resolver->resolveForMember($member);

        $this->assertFalse($res['success']);
        $this->assertEquals('reward_rule_not_configured', $res['status']);
        $this->assertFalse($res['eligible']);
    }

    /**
     * Test Member HTTP endpoint with anti-tampering protection.
     */
    public function test_member_endpoint_anti_tampering(): void
    {
        $member = $this->createMember([
            'user_id' => 'member_legit',
        ]);

        // Create 6 verified direct referrals
        for ($i = 1; $i <= 6; $i++) {
            $this->createMember([
                'user_id' => "legit_ref_{$i}",
                'introducer_id' => $member->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        // Malicious client sends fake tampered params
        $response = $this->actingAs($member, 'member')->getJson('/api/member/ad-rewards/eligibility?verified_referrals=100&reward_amount=0.050&verified=true');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        // Must reflect actual 6, not client's 100!
        $this->assertEquals(6, $data['direct_verified_referral_count']);
        // Must reflect $0.035, not client's $0.050!
        $this->assertEquals(0.035, $data['reward_amount_usd']);
        $this->assertEquals('0.0350', $data['reward_amount_exact']);
        $this->assertEquals('6–14', $data['matched_range']['label']);
    }

    /**
     * Test unauthenticated access to member endpoint is blocked.
     */
    public function test_unauthenticated_access_is_blocked(): void
    {
        $response = $this->getJson('/api/member/ad-rewards/eligibility');
        $response->assertStatus(401);
    }

    /**
     * Test snapshot generation for future Phase 4 audit trail.
     */
    public function test_snapshot_payload_structure(): void
    {
        $member = $this->createMember([
            'user_id' => 'member_snap',
        ]);

        for ($i = 1; $i <= 15; $i++) {
            $this->createMember([
                'user_id' => "ref_snap_{$i}",
                'introducer_id' => $member->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $res = $this->resolver->resolveForMember($member);

        $this->assertArrayHasKey('snapshot', $res);
        $snapshot = $res['snapshot'];

        $this->assertEquals(15, $snapshot['direct_verified_referral_count']);
        $this->assertEquals(0.050, $snapshot['reward_amount_usd']);
        $this->assertEquals('0.0500', $snapshot['reward_amount_exact']);
        $this->assertEquals('USD', $snapshot['currency']);
        $this->assertNotEmpty($snapshot['resolved_at']);
    }
}
