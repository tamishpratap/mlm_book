<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampaignDynamicRewardProcessingTest extends TestCase
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
        static $seq = 100;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "usr_{$unique}_" . Str::random(5),
            'email' => "usr_{$unique}_" . Str::random(5) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    protected function createActiveCampaign(Member $owner, float $budget = 50.00): AdCampaign
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
            'budget' => $budget,
            'fee_percent' => 2.50,
            'fee_amount' => round($budget * 0.025, 2),
            'wallet_debit' => round($budget * 1.025, 2),
            'spent_amount' => 0.00,
            'remaining_amount' => $budget,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);
    }

    /**
     * TEST 1: 0–5 direct verified referrals -> reward comes from configured 0–5 rule ($0.025 USD).
     */
    public function test_0_to_5_referrals_credits_exact_first_tier_reward(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(10),
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertTrue($data['rewarded']);
        $this->assertEquals(0.025, $data['reward_amount_usd']);
        $this->assertEquals('0.0250', $data['reward_amount_exact']);

        $viewer->refresh();
        $campaign->refresh();

        $this->assertEquals(0.0250, (float) $viewer->reward_balance);
        $this->assertEquals(9.9750, (float) $campaign->remaining_amount);
        $this->assertEquals(0.0250, (float) $campaign->spent_amount);

        // Snapshot verification
        $rewardRecord = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        $this->assertNotNull($rewardRecord);
        $this->assertEquals(0.0250, (float) $rewardRecord->reward_amount_usd);
        $this->assertEquals(0, $rewardRecord->direct_verified_referral_count);
        $this->assertEquals(0, $rewardRecord->rule_min_referrals);
        $this->assertEquals(5, $rewardRecord->rule_max_referrals);
    }

    /**
     * TEST 2: 6–14 direct verified referrals -> reward = configured 6–14 value ($0.035 USD).
     */
    public function test_6_to_14_referrals_credits_exact_second_tier_reward(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 1.00]);

        // Create 8 direct verified referrals
        for ($i = 1; $i <= 8; $i++) {
            $this->createMember([
                'user_id' => "ref_tier2_{$i}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(10),
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(0.035, $data['reward_amount_usd']);
        $this->assertEquals('0.0350', $data['reward_amount_exact']);

        $viewer->refresh();
        $campaign->refresh();

        $this->assertEquals(1.0350, (float) $viewer->reward_balance);
        $this->assertEquals(9.9650, (float) $campaign->remaining_amount);
        $this->assertEquals(0.0350, (float) $campaign->spent_amount);

        $rewardRecord = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        $this->assertEquals(8, $rewardRecord->direct_verified_referral_count);
        $this->assertEquals(6, $rewardRecord->rule_min_referrals);
        $this->assertEquals(14, $rewardRecord->rule_max_referrals);
    }

    /**
     * TEST 3: 15+ direct verified referrals -> reward = configured 15+ value ($0.050 USD).
     */
    public function test_15_plus_referrals_credits_exact_third_tier_reward(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        // Create 16 direct verified referrals
        for ($i = 1; $i <= 16; $i++) {
            $this->createMember([
                'user_id' => "ref_tier3_{$i}",
                'introducer_id' => $viewer->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(10),
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(0.050, $data['reward_amount_usd']);
        $this->assertEquals('0.0500', $data['reward_amount_exact']);

        $viewer->refresh();
        $campaign->refresh();

        $this->assertEquals(0.0500, (float) $viewer->reward_balance);
        $this->assertEquals(9.9500, (float) $campaign->remaining_amount);

        $rewardRecord = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        $this->assertEquals(16, $rewardRecord->direct_verified_referral_count);
        $this->assertEquals(15, $rewardRecord->rule_min_referrals);
        $this->assertNull($rewardRecord->rule_max_referrals);
    }

    /**
     * TEST 4: Unverified member cannot earn (HTTP 403), zero credit, zero budget deduction.
     */
    public function test_unverified_member_cannot_qualify_or_earn(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $unverifiedViewer = $this->createMember(['mobile_verified_at' => null, 'reward_balance' => 0.00]);

        $response = $this->actingAs($unverifiedViewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(10),
            ]);

        $response->assertStatus(403);
        $this->assertTrue($response->json('verified_required'));

        $unverifiedViewer->refresh();
        $campaign->refresh();

        $this->assertEquals(0.00, (float) $unverifiedViewer->reward_balance);
        $this->assertEquals(10.00, (float) $campaign->remaining_amount);
        $this->assertEquals(0, AdReward::where('member_id', $unverifiedViewer->id)->count());
    }

    /**
     * TEST 5: Indirect referrals only must NOT increase direct verified referral count.
     */
    public function test_indirect_referrals_do_not_increase_tier(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        // 2 direct verified
        $d1 = $this->createMember(['user_id' => 'dir_1', 'introducer_id' => $viewer->user_id, 'mobile_verified_at' => now()]);
        $d2 = $this->createMember(['user_id' => 'dir_2', 'introducer_id' => $viewer->user_id, 'mobile_verified_at' => now()]);

        // 50 indirect referrals under d1
        for ($i = 1; $i <= 50; $i++) {
            $this->createMember(['user_id' => "indir_{$i}", 'introducer_id' => $d1->user_id, 'mobile_verified_at' => now()]);
        }

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(10),
            ]);

        $response->assertStatus(200);
        // Direct count is 2 (0-5 tier = $0.025), NOT 52!
        $this->assertEquals(0.025, $response->json('reward_amount_usd'));
        $this->assertEquals(2, $response->json('direct_verified_referrals'));
    }

    /**
     * TEST 6: Repeated same campaign by same member -> only one successful reward.
     */
    public function test_repeated_same_campaign_by_same_member_yields_one_reward(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        // Call 1 -> Succeeds
        $res1 = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_1',
            ]);
        $res1->assertStatus(200);

        // Call 2 -> Rejected as already rewarded
        $res2 = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_2',
            ]);
        $res2->assertStatus(422);
        $this->assertTrue($res2->json('already_rewarded'));

        $viewer->refresh();
        $campaign->refresh();

        $this->assertEquals(0.0250, (float) $viewer->reward_balance);
        $this->assertEquals(9.9750, (float) $campaign->remaining_amount);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->count());
    }

    /**
     * TEST 7 & TEST 20: Double API request / duplicate event -> one wallet credit only.
     */
    public function test_duplicate_qualification_event_does_not_double_credit(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        $eventId = 'same_evt_id_' . Str::random(8);

        $res1 = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => $eventId,
            ]);
        $res1->assertStatus(200);

        $res2 = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => $eventId,
            ]);
        $res2->assertStatus(422);

        $viewer->refresh();
        $this->assertEquals(0.0250, (float) $viewer->reward_balance);
    }

    /**
     * TEST 9: Budget $0.040 and member reward $0.035 -> reward succeeds, remaining = $0.005.
     */
    public function test_sufficient_budget_at_exact_threshold(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 0.040);
        $viewer = $this->createMember();

        // 7 verified referrals -> tier $0.035
        for ($i = 1; $i <= 7; $i++) {
            $this->createMember(['user_id' => "ref_7_{$i}", 'introducer_id' => $viewer->user_id, 'mobile_verified_at' => now()]);
        }

        $res = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(8),
            ]);

        $res->assertStatus(200);
        $this->assertEquals(0.035, $res->json('reward_amount_usd'));

        $campaign->refresh();
        $this->assertEquals(0.0050, (float) $campaign->remaining_amount);
        // Since $0.005 is < minimum active reward ($0.025), campaign is auto-stopped
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->status);
    }

    /**
     * TEST 10: Budget $0.040 and member reward $0.050 -> reward rejected for insufficient budget.
     */
    public function test_insufficient_budget_for_higher_tier_member(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 0.040);
        $viewer = $this->createMember();

        // 18 verified referrals -> tier $0.050
        for ($i = 1; $i <= 18; $i++) {
            $this->createMember(['user_id' => "ref_18_{$i}", 'introducer_id' => $viewer->user_id, 'mobile_verified_at' => now()]);
        }

        $res = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(8),
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('insufficient', strtolower($res->json('message')));

        $viewer->refresh();
        $campaign->refresh();
        $this->assertEquals(0.00, (float) $viewer->reward_balance);
        $this->assertEquals(0.040, (float) $campaign->remaining_amount);
    }

    /**
     * TEST 11: Budget below minimum configured reward -> campaign becomes stopped/exhausted.
     */
    public function test_budget_below_min_reward_stops_campaign(): void
    {
        $owner = $this->createMember();
        // Campaign with remaining budget $0.020 (< $0.025 min reward)
        $campaign = $this->createActiveCampaign($owner, 0.020);
        $viewer = $this->createMember();

        $res = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(8),
            ]);

        $res->assertStatus(422);
        $this->assertTrue($res->json('exhausted'));

        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->status);
    }

    /**
     * TEST 12 & TEST 13: Top-up after exhaustion and previously rewarded member immutability.
     */
    public function test_topup_reactivates_campaign_and_preserves_reward_history(): void
    {
        $owner = $this->createMember(['ad_balance' => 50.00]);
        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp_' . Str::random(5),
            'slug' => 'acme-corp-' . Str::random(5),
            'category' => 'Technology',
            'status' => 'active',
        ]);
        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Ad Post',
            'type' => 'post',
        ]);
        $campaign = AdCampaign::create([
            'campaign_id' => 'CAMP_TOPUP_' . Str::random(5),
            'campaign_name' => 'Topup Test',
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'post_id' => $post->id,
            'budget' => 0.025,
            'spent_amount' => 0.00,
            'remaining_amount' => 0.025,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $viewerA = $this->createMember(['user_id' => 'viewer_a']);
        $viewerB = $this->createMember(['user_id' => 'viewer_b']);

        // 1. Viewer A qualifies -> spends the $0.025 -> campaign stops
        $this->actingAs($viewerA, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_a'])
            ->assertStatus(200);

        $campaign->refresh();
        $this->assertLessThan(0.025, (float) $campaign->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->status);

        // 2. Owner tops up campaign by $10
        $topupRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->id}/add-funds", [
                'amount' => 10.00,
            ]);
        $topupRes->assertStatus(200);

        $campaign->refresh();
        $this->assertGreaterThanOrEqual(10.00, (float) $campaign->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->status);

        // 3. Viewer A tries to earn again -> BLOCKED as already rewarded
        $resA2 = $this->actingAs($viewerA, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_a2']);
        $resA2->assertStatus(422);
        $this->assertTrue($resA2->json('already_rewarded'));

        // 4. Viewer B qualifies -> SUCCEEDS
        $resB = $this->actingAs($viewerB, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_b']);
        $resB->assertStatus(200);
    }

    /**
     * TEST 14: Admin changes reward rule after historical reward -> historical reward remains unchanged.
     */
    public function test_admin_rule_changes_do_not_mutate_historical_rewards(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember();

        // 8 direct verified referrals (6-14 tier = $0.035)
        for ($i = 1; $i <= 8; $i++) {
            $this->createMember(['user_id' => "hist_ref_{$i}", 'introducer_id' => $viewer->user_id, 'mobile_verified_at' => now()]);
        }

        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_hist'])
            ->assertStatus(200);

        $rewardRecord = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        $this->assertEquals(0.0350, (float) $rewardRecord->reward_amount_usd);

        // Admin updates 6-14 tier to $0.0450
        $rule2 = AdRewardRule::where('min_referrals', 6)->first();
        $rule2->update(['reward_amount' => 0.0450]);
        AdRewardRule::clearCache();

        // Check historical record -> MUST REMAIN $0.0350!
        $rewardRecord->refresh();
        $this->assertEquals(0.0350, (float) $rewardRecord->reward_amount_usd);
        $this->assertEquals(8, $rewardRecord->direct_verified_referral_count);
    }

    /**
     * TEST 15 & TEST 16: Client sends fake reward_amount or verified_referral_count -> backend ignores.
     */
    public function test_tampered_payload_inputs_are_strictly_ignored(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $viewer = $this->createMember(['reward_balance' => 0.00]);

        // Client attempts to spoof 500 referrals and $0.500 reward
        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . Str::random(10),
                'reward_amount' => 0.500,
                'reward_amount_usd' => 0.500,
                'verified_referral_count' => 500,
                'direct_verified_referral_count' => 500,
            ]);

        $response->assertStatus(200);
        // Must be calculated as 0 referrals -> 0.025 USD!
        $this->assertEquals(0.025, $response->json('reward_amount_usd'));
        $this->assertEquals(0, $response->json('direct_verified_referrals'));

        $viewer->refresh();
        $this->assertEquals(0.0250, (float) $viewer->reward_balance);
    }

    /**
     * TEST 8: Budget depletion across multiple members never becomes negative.
     */
    public function test_budget_depletion_across_multiple_members_never_becomes_negative(): void
    {
        $owner = $this->createMember();
        // Budget exactly $0.050 -> can afford 2 members at $0.025 each
        $campaign = $this->createActiveCampaign($owner, 0.050);

        $m1 = $this->createMember(['user_id' => 'm1_05']);
        $m2 = $this->createMember(['user_id' => 'm2_05']);
        $m3 = $this->createMember(['user_id' => 'm3_05']);

        // Member 1 -> succeeds, remaining = 0.025
        $res1 = $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_m1']);
        $res1->assertStatus(200);

        // Member 2 -> succeeds, remaining = 0.000, stops campaign
        $res2 = $this->actingAs($m2, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_m2']);
        $res2->assertStatus(200);

        // Member 3 -> rejected, campaign is stopped
        $res3 = $this->actingAs($m3, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_m3']);
        $res3->assertStatus(422);

        $campaign->refresh();
        $this->assertEquals(0.00, (float) $campaign->remaining_amount);
        $this->assertGreaterThanOrEqual(0.00, (float) $campaign->remaining_amount);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->status);
    }

    /**
     * TEST 17: Platform fee changes later -> historical reward accounting unaffected.
     */
    public function test_platform_fee_independence(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 100.00);
        $viewer = $this->createMember();

        $initialFee = (float) $campaign->fee_amount;

        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_fee'])
            ->assertStatus(200);

        $campaign->refresh();
        $this->assertEquals($initialFee, (float) $campaign->fee_amount);
        $this->assertEquals(99.9750, (float) $campaign->remaining_amount);
    }
}
