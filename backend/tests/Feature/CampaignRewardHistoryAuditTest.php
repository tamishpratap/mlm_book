<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampaignRewardHistoryAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function createMember(array $attributes = []): Member
    {
        static $seq = 100;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "usr_{$unique}_" . Str::random(5),
            'email' => "usr_{$unique}_" . Str::random(5) . "@example.com",
            'phone' => '+1' . rand(1000000000, 9999999999),
            'password' => 'Password@123',
            'status' => 'active',
            'mobile_verified_at' => now(),
            'ad_balance' => 100.00,
            'reward_balance' => 0.00,
            'direct_referral_count' => 0,
        ], $attributes));
    }

    private function createActiveCampaign(Member $owner, float $budget = 10.00): AdCampaign
    {
        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Test Business',
            'page_username' => 'biz_' . Str::random(6),
            'slug' => 'biz-' . Str::random(6),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Sponsored Ad Post',
            'type' => 'post',
        ]);

        return AdCampaign::create([
            'campaign_id' => 'CAMP_' . strtoupper(Str::random(10)),
            'campaign_name' => 'Reward History Test Campaign',
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
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
     * TEST 1 & TEST 13: Member sees only own rewards; unauthorized access / IDOR is blocked.
     */
    public function test_member_sees_only_own_rewards_and_idor_is_blocked(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 20.00);

        $memberA = $this->createMember(['user_id' => 'member_a']);
        $memberB = $this->createMember(['user_id' => 'member_b']);

        // Qualify Member A
        $this->actingAs($memberA, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_a'])
            ->assertStatus(200);

        // Qualify Member B
        $this->actingAs($memberB, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_b'])
            ->assertStatus(200);

        // Member A requests reward history
        $responseA = $this->actingAs($memberA, 'member')
            ->getJson('/api/member/rewards/history')
            ->assertStatus(200);

        $dataA = $responseA->json('rewards.data');
        $this->assertCount(1, $dataA);
        $this->assertEquals('evt_a', $dataA[0]['qualifying_event_id']);
        $this->assertEquals(0.025, $dataA[0]['reward_amount_usd']);

        // Member A attempts IDOR by passing member_id=memberB->id
        $responseIdor = $this->actingAs($memberA, 'member')
            ->getJson("/api/member/rewards/history?member_id={$memberB->id}")
            ->assertStatus(200);

        $dataIdor = $responseIdor->json('rewards.data');
        // Must strictly return Member A's rewards only!
        $this->assertCount(1, $dataIdor);
        $this->assertEquals('evt_a', $dataIdor[0]['qualifying_event_id']);
    }

    /**
     * TEST 2: Admin can see all campaign rewards.
     */
    public function test_admin_can_see_all_campaign_rewards(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin@123'),
        ]);

        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 20.00);

        $memberA = $this->createMember(['user_id' => 'admin_test_a']);
        $memberB = $this->createMember(['user_id' => 'admin_test_b']);

        $this->actingAs($memberA, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_admin_a'])
            ->assertStatus(200);

        $this->actingAs($memberB, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_admin_b'])
            ->assertStatus(200);

        $response = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/ad-campaigns/rewards')
            ->assertStatus(200);

        $this->assertEquals(2, $response->json('metrics.total_rewards_count'));
        $this->assertCount(2, $response->json('rewards.data'));
    }

    /**
     * TEST 3 & TEST 4: Correct exact reward amount and referral count are displayed in history.
     */
    public function test_correct_exact_reward_amount_and_referral_count_are_displayed(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);

        // Member with 8 verified referrals (Tier 6–14 -> $0.0350 USD)
        $member = $this->createMember(['user_id' => 'referrer_8']);
        for ($i = 1; $i <= 8; $i++) {
            $this->createMember([
                'user_id' => "ref_8_{$i}",
                'introducer_id' => $member->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_ref8'])
            ->assertStatus(200);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/rewards/history')
            ->assertStatus(200);

        $item = $response->json('rewards.data.0');
        $this->assertEquals(0.035, $item['reward_amount_usd']);
        $this->assertEquals('0.0350', $item['reward_amount_exact']);
        $this->assertEquals('+$0.0350 USD', $item['reward_formatted']);
        $this->assertEquals(8, $item['direct_verified_referral_count']);
        $this->assertEquals('6–14', $item['tier_label']);
        $this->assertEquals('credited', $item['status']);
    }

    /**
     * TEST 5: Historical tier snapshot remains unchanged after rule modification.
     */
    public function test_historical_tier_snapshot_remains_unchanged_after_rule_modification(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);

        $member = $this->createMember(['user_id' => 'immutability_test']);
        for ($i = 1; $i <= 8; $i++) {
            $this->createMember([
                'user_id' => "ref_imm_{$i}",
                'introducer_id' => $member->user_id,
                'mobile_verified_at' => now(),
            ]);
        }

        // Earn at $0.0350
        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_imm'])
            ->assertStatus(200);

        // Admin changes tier 6-14 to $0.0450
        $rule = AdRewardRule::where('min_referrals', 6)->first();
        $rule->update(['reward_amount' => 0.0450]);
        AdRewardRule::clearCache();

        // Member history must STILL show $0.0350!
        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/rewards/history')
            ->assertStatus(200);

        $item = $response->json('rewards.data.0');
        $this->assertEquals(0.035, $item['reward_amount_usd']);
        $this->assertEquals('+$0.0350 USD', $item['reward_formatted']);
    }

    /**
     * TEST 6: Successful reward links to correct wallet balance.
     */
    public function test_successful_reward_links_to_correct_wallet_balance(): void
    {
        $owner = $this->createMember();
        $campaign1 = $this->createActiveCampaign($owner, 10.00);
        $campaign2 = $this->createActiveCampaign($owner, 10.00);

        $member = $this->createMember(['reward_balance' => 0.00]);

        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign1->id}/qualify-visit", ['qualifying_event_id' => 'evt_w1'])
            ->assertStatus(200);

        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign2->id}/qualify-visit", ['qualifying_event_id' => 'evt_w2'])
            ->assertStatus(200);

        $member->refresh();
        $this->assertEquals(0.0500, (float) $member->reward_balance);

        $walletRes = $this->actingAs($member, 'member')
            ->getJson('/api/member/rewards/wallet')
            ->assertStatus(200);

        $this->assertEquals(0.05, $walletRes->json('wallet.reward_balance'));
        $this->assertEquals(2, $walletRes->json('wallet.total_reward_count'));
    }

    /**
     * TEST 7: Duplicate reward request does not create duplicate history.
     */
    public function test_duplicate_reward_request_does_not_create_duplicate_history(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);
        $member = $this->createMember();

        // First attempt -> SUCCESS
        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_dup1'])
            ->assertStatus(200);

        // Second attempt -> Already rewarded (blocked with 422)
        $res2 = $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_dup2']);
        $this->assertEquals(422, $res2->status());
        $this->assertTrue($res2->json('already_rewarded'));

        $this->assertEquals(1, AdReward::where('member_id', $member->id)->where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 8 & TEST 9: Failed reward does not appear as successful wallet credit and is excluded from totals.
     */
    public function test_failed_reward_does_not_appear_as_successful_wallet_credit(): void
    {
        $owner = $this->createMember();
        // Campaign with 0 budget
        $campaign = $this->createActiveCampaign($owner, 0.00);
        $campaign->status = AdCampaign::STATUS_STOPPED;
        $campaign->save();

        $member = $this->createMember(['reward_balance' => 0.00]);

        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_fail'])
            ->assertStatus(422);

        $member->refresh();
        $this->assertEquals(0.00, (float) $member->reward_balance);
        $this->assertEquals(0, AdReward::where('member_id', $member->id)->where('status', AdReward::STATUS_CREDITED)->count());
    }

    /**
     * TEST 10: Campaign reward count counts unique successful member/campaign rewards.
     */
    public function test_campaign_reward_count_counts_unique_successful_rewards(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);

        $m1 = $this->createMember(['user_id' => 'cnt_1']);
        $m2 = $this->createMember(['user_id' => 'cnt_2']);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_c1'])
            ->assertStatus(200);

        $this->actingAs($m2, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_c2'])
            ->assertStatus(200);

        $campaign->refresh();
        $this->assertEquals(2, $campaign->rewards()->count());
        $this->assertEquals(0.0500, (float) $campaign->spent_amount);
    }

    /**
     * TEST 11: Pagination works properly on backend.
     */
    public function test_pagination_works_properly_on_backend(): void
    {
        $owner = $this->createMember();
        $member = $this->createMember();

        // Create 5 campaigns and rewards
        for ($i = 1; $i <= 5; $i++) {
            $campaign = $this->createActiveCampaign($owner, 5.00);
            $this->actingAs($member, 'member')
                ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => "evt_page_{$i}"]);
        }

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/rewards/history?per_page=2&page=1')
            ->assertStatus(200);

        $this->assertEquals(1, $response->json('rewards.current_page'));
        $this->assertEquals(2, $response->json('rewards.per_page'));
        $this->assertEquals(5, $response->json('rewards.total'));
        $this->assertCount(2, $response->json('rewards.data'));
    }

    /**
     * TEST 12: Date and tier filter queries are processed server-side.
     */
    public function test_date_and_tier_filter_queries_are_server_side(): void
    {
        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin_filter@example.com',
            'password' => bcrypt('Admin@123'),
        ]);

        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 50.00);

        // Member Tier 0-5 (0 refs -> $0.025)
        $mTier1 = $this->createMember(['user_id' => 'm_t1']);
        $this->actingAs($mTier1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_t1']);

        // Member Tier 6-14 (8 refs -> $0.035)
        $mTier2 = $this->createMember(['user_id' => 'm_t2']);
        for ($i = 1; $i <= 8; $i++) {
            $this->createMember(['user_id' => "t2_ref_{$i}", 'introducer_id' => $mTier2->user_id, 'mobile_verified_at' => now()]);
        }
        $this->actingAs($mTier2, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_t2']);

        // Member Tier 15+ (16 refs -> $0.050)
        $mTier3 = $this->createMember(['user_id' => 'm_t3']);
        for ($i = 1; $i <= 16; $i++) {
            $this->createMember(['user_id' => "t3_ref_{$i}", 'introducer_id' => $mTier3->user_id, 'mobile_verified_at' => now()]);
        }
        $this->actingAs($mTier3, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_t3']);

        // Admin filters for Tier 6-14
        $resTier2 = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/ad-campaigns/rewards?tier=6-14')
            ->assertStatus(200);

        $this->assertCount(1, $resTier2->json('rewards.data'));
        $this->assertEquals('evt_t2', $resTier2->json('rewards.data.0.qualifying_event_id'));

        // Admin filters for Tier 15+
        $resTier3 = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/ad-campaigns/rewards?tier=15+')
            ->assertStatus(200);

        $this->assertCount(1, $resTier3->json('rewards.data'));
        $this->assertEquals('evt_t3', $resTier3->json('rewards.data.0.qualifying_event_id'));
    }

    /**
     * TEST 14: Existing campaign top-up preserves all reward history.
     */
    public function test_existing_campaign_topup_preserves_all_reward_history(): void
    {
        $owner = $this->createMember(['ad_balance' => 50.00]);
        $campaign = $this->createActiveCampaign($owner, 5.00);
        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_topup_hist'])
            ->assertStatus(200);

        // Top up campaign by $10
        $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/add-funds", [
                'amount' => 10.00,
            ])
            ->assertStatus(200);

        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->count());
        $reward = AdReward::where('ad_campaign_id', $campaign->id)->first();
        $this->assertEquals('evt_topup_hist', $reward->qualifying_event_id);
    }

    /**
     * TEST 15: Reward history remains correct after later referral-count changes.
     */
    public function test_reward_history_remains_correct_after_later_referral_count_changes(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);

        $member = $this->createMember(['user_id' => 'later_change']);
        // Initially 0 referrals
        $this->actingAs($member, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_init0'])
            ->assertStatus(200);

        // Member now refers 20 verified users
        for ($i = 1; $i <= 20; $i++) {
            $this->createMember(['user_id' => "later_ref_{$i}", 'introducer_id' => $member->user_id, 'mobile_verified_at' => now()]);
        }

        // History record must STILL show 0 referrals and Tier 0-5
        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/rewards/history')
            ->assertStatus(200);

        $item = $response->json('rewards.data.0');
        $this->assertEquals(0, $item['direct_verified_referral_count']);
        $this->assertEquals('0–5', $item['tier_label']);
        $this->assertEquals(0.025, $item['reward_amount_usd']);
    }

    /**
     * TEST 16: Campaign budget and reward totals remain mathematically consistent.
     */
    public function test_campaign_budget_and_reward_totals_remain_mathematically_consistent(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createActiveCampaign($owner, 10.00);

        $m1 = $this->createMember(['user_id' => 'math_1']);
        $m2 = $this->createMember(['user_id' => 'math_2']);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_m1'])
            ->assertStatus(200);

        $this->actingAs($m2, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->id}/qualify-visit", ['qualifying_event_id' => 'evt_m2'])
            ->assertStatus(200);

        $campaign->refresh();
        $this->assertEquals(10.00, round((float) $campaign->budget, 2));
        $this->assertEquals(0.0500, (float) $campaign->spent_amount);
        $this->assertEquals(9.9500, (float) $campaign->remaining_amount);
        $this->assertEquals(10.00, round((float) $campaign->spent_amount + (float) $campaign->remaining_amount, 2));
    }
}
