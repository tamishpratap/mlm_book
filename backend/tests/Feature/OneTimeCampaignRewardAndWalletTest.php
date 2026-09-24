<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneTimeCampaignRewardAndWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdRewardRule::query()->delete();
        AdRewardRule::clearCache();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();
    }

    protected function createVerifiedMember(string $prefix = 'verified', array $attributes = []): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create(array_merge([
            'name' => 'Verified ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'ad_balance' => 0.00,
            'reward_balance' => 0.00,
        ], $attributes));
    }

    protected function createUnverifiedMember(string $prefix = 'unverified', array $attributes = []): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create(array_merge([
            'name' => 'Unverified ' . $unique,
            'email' => $unique . '@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => null,
            'ad_balance' => 0.00,
            'reward_balance' => 0.00,
        ], $attributes));
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('page_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Biz ' . $unique,
            'page_username' => 'biz_' . $unique,
            'slug' => 'biz-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    protected function createActiveCampaign(BusinessPage $page, Member $owner, array $attributes = []): AdCampaign
    {
        $unique = uniqid('camp_');
        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Sponsored post content ' . $unique,
            'visibility' => 'public',
        ]);

        return AdCampaign::create(array_merge([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Reward Test Campaign ' . $unique,
            'budget' => 30.00,
            'currency' => 'USD',
            'spent_amount' => 0.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attributes));
    }

    /**
     * TEST 1: First valid qualifying visit for verified member -> +$0.05 credited to Reward Wallet, campaign budget decreases by $0.05.
     */
    public function test_first_qualifying_interaction_credits_reward_wallet_and_deducts_campaign(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
            'spent_amount' => 0.00,
        ]);

        $visitor = $this->createVerifiedMember('visitor_a', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_101',
            'landing_page_url' => 'https://example.com/promo1',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'remaining_budget' => 29.95,
                'spent_budget' => 0.05,
                'member_reward_balance' => 0.05,
            ]);

        // Verify Campaign
        $campaign->refresh();
        $this->assertEquals(29.95, (float) $campaign->remaining_amount);
        $this->assertEquals(0.05, (float) $campaign->spent_amount);

        // Verify Member Reward Wallet
        $visitor->refresh();
        $this->assertEquals(0.05, (float) $visitor->reward_balance);

        // Verify AdReward record in ledger
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 2: Second and multiple repeat qualifying visits by the SAME member for the SAME campaign receive $0.00.
     */
    public function test_same_member_repeat_visits_on_same_campaign_receive_zero_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, [
            'budget' => 30.00,
            'remaining_amount' => 30.00,
            'spent_amount' => 0.00,
        ]);

        $visitor = $this->createVerifiedMember('visitor_a', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // 1st Visit -> $0.05 reward
        $res1 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_visit_1',
            'landing_page_url' => 'https://example.com/promo1',
        ]);
        $res1->assertOk();

        // 2nd Visit (different event id) -> BLOCKED (already_rewarded)
        $res2 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_visit_2',
            'landing_page_url' => 'https://example.com/promo1',
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
                'already_rewarded' => true,
            ]);

        // 3rd Visit -> BLOCKED
        $res3 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_visit_3',
            'landing_page_url' => 'https://example.com/promo1',
        ]);
        $res3->assertStatus(422)
            ->assertJson([
                'already_rewarded' => true,
                'rewarded' => false,
            ]);

        // 4th Visit -> BLOCKED
        $res4 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_visit_4',
            'landing_page_url' => 'https://example.com/promo1',
        ]);
        $res4->assertStatus(422)
            ->assertJson([
                'already_rewarded' => true,
                'rewarded' => false,
            ]);

        // Ensure exactly 1 reward record exists
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());

        // Ensure campaign budget remains $29.95
        $campaign->refresh();
        $this->assertEquals(29.95, (float) $campaign->remaining_amount);
        $this->assertEquals(0.05, (float) $campaign->spent_amount);

        // Ensure Reward Wallet remains $0.05
        $visitor->refresh();
        $this->assertEquals(0.05, (float) $visitor->reward_balance);
    }

    /**
     * TEST 3: Different campaigns: Same member CAN earn separately on Campaign A ($0.05) and Campaign B ($0.05).
     */
    public function test_same_member_can_earn_on_different_campaigns(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);

        $campaignA = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);
        $campaignB = $this->createActiveCampaign($page, $owner, ['budget' => 20.00, 'remaining_amount' => 20.00]);

        $visitor = $this->createVerifiedMember('visitor_multi', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // 1. Earn on Campaign A
        $resA = $this->postJson("/api/member/ad-campaigns/{$campaignA->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_campA_1',
            'landing_page_url' => 'https://example.com/promoA',
        ]);
        $resA->assertOk();
        $this->assertEquals(0.05, (float) $visitor->fresh()->reward_balance);

        // 2. Earn on Campaign B
        $resB = $this->postJson("/api/member/ad-campaigns/{$campaignB->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_campB_1',
            'landing_page_url' => 'https://example.com/promoB',
        ]);
        $resB->assertOk();
        $this->assertEquals(0.10, (float) $visitor->fresh()->reward_balance);

        // Total 2 rewards
        $this->assertEquals(2, AdReward::where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 4: Different members: Member A and Member B can each earn once on the same campaign.
     */
    public function test_different_members_each_earn_once_on_same_campaign(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);

        $memberA = $this->createVerifiedMember('member_a');
        $memberB = $this->createVerifiedMember('member_b');

        // Member A earns
        $this->actingAs($memberA, 'member');
        $resA = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_a',
            'landing_page_url' => 'https://example.com/promo',
        ]);
        $resA->assertOk();

        // Member B earns
        $this->actingAs($memberB, 'member');
        $resB = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_b',
            'landing_page_url' => 'https://example.com/promo',
        ]);
        $resB->assertOk();

        $campaign->refresh();
        $this->assertEquals(29.90, (float) $campaign->remaining_amount);
        $this->assertEquals(0.10, (float) $campaign->spent_amount);
        $this->assertEquals(2, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 5: Unverified member cannot receive rewards.
     */
    public function test_unverified_member_receives_no_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);

        $unverified = $this->createUnverifiedMember('unverified_user');
        $this->actingAs($unverified, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
            'qualifying_event_id' => 'evt_unverified',
            'landing_page_url' => 'https://example.com/promo',
        ]);

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ]);

        $this->assertEquals(0, AdReward::where('member_id', $unverified->id)->count());
        $this->assertEquals(30.00, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.00, (float) $unverified->fresh()->reward_balance);
    }

    /**
     * TEST 6: Reward Wallet API (GET /api/member/rewards/wallet) returns live balance and count.
     */
    public function test_member_reward_wallet_api(): void
    {
        $member = $this->createVerifiedMember('wallet_user', ['reward_balance' => 0.15]);
        $this->actingAs($member, 'member');

        $owner = $this->createVerifiedMember('ad_owner');
        $page = $this->createBusinessPage($owner);
        $c1 = $this->createActiveCampaign($page, $owner);
        $c2 = $this->createActiveCampaign($page, $owner);
        $c3 = $this->createActiveCampaign($page, $owner);

        AdReward::create(['ad_campaign_id' => $c1->id, 'member_id' => $member->id, 'reward_amount_usd' => 0.05, 'status' => 'credited']);
        AdReward::create(['ad_campaign_id' => $c2->id, 'member_id' => $member->id, 'reward_amount_usd' => 0.05, 'status' => 'credited']);
        AdReward::create(['ad_campaign_id' => $c3->id, 'member_id' => $member->id, 'reward_amount_usd' => 0.05, 'status' => 'credited']);

        $res = $this->getJson('/api/member/rewards/wallet');

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'wallet' => [
                    'reward_balance' => 0.15,
                    'total_rewards_earned' => 0.15,
                    'total_reward_count' => 3,
                    'is_verified' => true,
                    'currency' => 'USD',
                ],
            ]);
    }

    /**
     * TEST 7: Reward History API (GET /api/member/rewards/history) returns paginated ledger.
     */
    public function test_member_reward_history_api(): void
    {
        $member = $this->createVerifiedMember('history_user', ['reward_balance' => 0.05]);
        $this->actingAs($member, 'member');

        $owner = $this->createVerifiedMember('ad_owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['campaign_name' => 'History Promo Campaign']);

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'reward_amount_usd' => 0.05,
            'qualifying_event_id' => 'evt_hist_1',
            'status' => 'credited',
        ]);

        $res = $this->getJson('/api/member/rewards/history');

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'rewards' => [
                    'total' => 1,
                    'data' => [
                        [
                            'reward_amount_usd' => 0.05,
                            'status' => 'credited',
                            'campaign' => [
                                'campaign_name' => 'History Promo Campaign',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * TEST 8: Cross-member privacy: Member A cannot access Member B's reward wallet or history.
     */
    public function test_cross_member_reward_wallet_isolation(): void
    {
        $memberA = $this->createVerifiedMember('user_a', ['reward_balance' => 5.00]);
        $memberB = $this->createVerifiedMember('user_b', ['reward_balance' => 0.00]);

        $this->actingAs($memberB, 'member');

        $res = $this->getJson('/api/member/rewards/wallet');
        $res->assertOk()
            ->assertJson([
                'success' => true,
                'wallet' => [
                    'reward_balance' => 0.00,
                ],
            ]);

        // Member B sees 0, not Member A's $5.00
        $this->assertEquals(0.00, $res->json('wallet.reward_balance'));
    }
}