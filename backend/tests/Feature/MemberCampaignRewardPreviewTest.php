<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Follower;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberCampaignRewardPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Baseline Active Admin Reward Rules
        $r1 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 0],
            ['max_referrals' => 5, 'reward_amount' => 0.0250, 'is_active' => true]
        );
        $r1->update(['reward_amount' => 0.0250, 'is_active' => true]);

        $r2 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 6],
            ['max_referrals' => 14, 'reward_amount' => 0.0350, 'is_active' => true]
        );
        $r2->update(['reward_amount' => 0.0350, 'is_active' => true]);

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
            'user_id' => "usr_{$unique}_" . Str::random(5),
            'email' => "usr_{$unique}_" . Str::random(5) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
        ], $attributes));
    }

    protected function createActiveCampaign(Member $owner): AdCampaign
    {
        $businessPage = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp_' . Str::random(5),
            'slug' => 'acme-corp-' . Str::random(5),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Check out our new products!',
            'type' => 'post',
        ]);

        return AdCampaign::create([
            'campaign_id' => 'CAMP_' . strtoupper(Str::random(10)),
            'campaign_name' => 'Summer Promo',
            'member_id' => $owner->id,
            'business_page_id' => $businessPage->id,
            'post_id' => $post->id,
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);
    }

    /**
     * Test verified member receives reward preview with direct verified referral count and applicable reward.
     */
    public function test_verified_member_receives_correct_reward_preview(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);

        $viewer = $this->createMember([
            'user_id' => 'viewer_8_refs',
        ]);

        // Create 8 verified direct referrals for viewer
        for ($i = 1; $i <= 8; $i++) {
            $this->createMember([
                'user_id' => "ref_8_{$i}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertTrue($data['is_verified']);
        $this->assertFalse($data['already_rewarded']);
        $this->assertTrue($data['eligible_to_earn']);
        $this->assertEquals(8, $data['member_reward']['direct_verified_referral_count']);
        $this->assertEquals(0.035, $data['member_reward']['applicable_reward_usd']);
        $this->assertEquals('0.0350', $data['member_reward']['applicable_reward_exact']);
        $this->assertEquals('6–14', $data['member_reward']['matched_range']['label']);
        $this->assertNotEmpty($data['all_active_rules']);
    }

    /**
     * Test unverified member is blocked from reward preview (HTTP 403).
     */
    public function test_unverified_member_is_blocked_from_reward_preview(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);

        $unverifiedViewer = $this->createMember([
            'mobile_verified_at' => null,
        ]);

        $response = $this->actingAs($unverifiedViewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(403);
        $this->assertTrue($response->json('verified_required'));
    }

    /**
     * Test Member with 0 verified direct referrals gets 0-5 slab ($0.025).
     */
    public function test_zero_verified_referrals_resolves_first_tier(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);
        $viewer = $this->createMember();

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(0, $data['member_reward']['direct_verified_referral_count']);
        $this->assertEquals(0.025, $data['member_reward']['applicable_reward_usd']);
        $this->assertEquals('0.0250', $data['member_reward']['applicable_reward_exact']);
    }

    /**
     * Test Member with 15 verified direct referrals gets 15+ slab ($0.050).
     */
    public function test_fifteen_verified_referrals_resolves_third_tier(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);
        $viewer = $this->createMember();

        for ($i = 1; $i <= 15; $i++) {
            $this->createMember([
                'user_id' => "ref_15_{$i}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(15, $data['member_reward']['direct_verified_referral_count']);
        $this->assertEquals(0.050, $data['member_reward']['applicable_reward_usd']);
        $this->assertEquals('0.0500', $data['member_reward']['applicable_reward_exact']);
        $this->assertTrue($data['member_reward']['matched_range']['is_unlimited']);
    }

    /**
     * Test Member with 10 direct referrals (4 verified, 6 unverified) -> tier count 4 -> $0.025.
     */
    public function test_unverified_referrals_are_strictly_excluded(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);
        $viewer = $this->createMember();

        // 4 verified
        for ($i = 1; $i <= 4; $i++) {
            $this->createMember([
                'user_id' => "ref_ver_{$i}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        // 6 unverified
        for ($j = 1; $j <= 6; $j++) {
            $this->createMember([
                'user_id' => "ref_unver_{$j}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => null,
            ]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(200);
        $data = $response->json();

        // Must be 4, NOT 10!
        $this->assertEquals(4, $data['member_reward']['direct_verified_referral_count']);
        $this->assertEquals(0.025, $data['member_reward']['applicable_reward_usd']);
    }

    /**
     * Test Indirect referrals are excluded (3 direct verified + 100 indirect -> count 3).
     */
    public function test_indirect_referrals_are_excluded(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);
        $viewer = $this->createMember();

        $directRefs = [];
        for ($i = 1; $i <= 3; $i++) {
            $directRefs[] = $this->createMember([
                'user_id' => "dir_ref_{$i}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        for ($k = 1; $k <= 100; $k++) {
            $this->createMember([
                'user_id' => "indir_ref_{$k}",
                'introducer_id' => $directRefs[0]->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(200);
        $data = $response->json();

        // Must be 3, NOT 103!
        $this->assertEquals(3, $data['member_reward']['direct_verified_referral_count']);
        $this->assertEquals(0.025, $data['member_reward']['applicable_reward_usd']);
    }

    /**
     * Test Already Rewarded member has already_rewarded = true and eligible_to_earn = false.
     */
    public function test_already_rewarded_member_status(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);
        $viewer = $this->createMember();

        // Record a past credited reward
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $viewer->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'past_reward_123',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['already_rewarded']);
        $this->assertFalse($data['eligible_to_earn']);
    }

    /**
     * Test preview and interest recording do NOT credit wallet balance or deduct campaign budget.
     */
    public function test_preview_and_interest_do_not_credit_money(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        $initialRemaining = (float) $campaign->remaining_amount;

        // 1. Call Preview
        $this->actingAs($viewer, 'member')
            ->getJson("/api/member/ad-campaigns/{$campaign->id}/reward-preview")
            ->assertStatus(200);

        // 2. Call Interest
        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/interest")
            ->assertStatus(200);

        // Verify NO changes to financial balances
        $viewer->refresh();
        $campaign->refresh();

        $this->assertEquals(0.00, (float) $viewer->reward_balance);
        $this->assertEquals($initialRemaining, (float) $campaign->remaining_amount);
        $this->assertEquals(0, AdReward::where('member_id', $viewer->id)->count());
    }
}
