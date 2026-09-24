<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdReward;
use App\Models\BusinessFollower;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignInterestAndFollowRewardFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\AdRewardRule::query()->delete();
        \App\Models\AdRewardRule::create([
            'rule_type' => \App\Models\AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);
        \App\Models\AdRewardRule::clearCache();
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
            'campaign_name' => 'Landing Reward Campaign ' . $unique,
            'budget' => 30.00,
            'currency' => 'USD',
            'spent_amount' => 0.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attributes));
    }

    /**
     * TEST 1: Verified member clicks Interested -> records click event, returns target page with campaign context. Does NOT reward directly.
     */
    public function test_verified_member_expresses_interest_without_immediate_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $visitor = $this->createVerifiedMember('visitor_a', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => true]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'campaign_id' => $campaign->campaign_id,
                'business_page_slug' => $page->slug,
            ]);

        // Verify that NO financial reward was granted for Interested click alone
        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
        $this->assertEquals(30.00, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.00, (float) $visitor->fresh()->reward_balance);

        // Verify that Interested interaction was logged in ad_clicks
        $this->assertEquals(1, AdClick::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 2: Unverified member cannot express interest.
     */
    public function test_unverified_member_cannot_express_interest(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $unverified = $this->createUnverifiedMember('unverified_user');
        $this->actingAs($unverified, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => true]);

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
            ]);
    }

    /**
     * TEST 3: Complete Flow: Interested -> Target Page -> Follow Page to Earn $0.05 -> Verified Follow -> +$0.05 Reward.
     */
    public function test_complete_interested_to_follow_reward_flow(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);

        $visitor = $this->createVerifiedMember('visitor_earn', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // 1. Check Follow Reward Status before follow
        $statusRes = $this->getJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-reward-status");
        $statusRes->assertOk()
            ->assertJson([
                'success' => true,
                'is_verified' => true,
                'already_rewarded' => false,
                'is_following' => false,
                'eligible_to_earn' => true,
                'reward_amount_usd' => 0.05,
            ]);

        // 2. Click "Follow Page to Earn $0.05"
        $earnRes = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn", [
            'landing_page_url' => "https://example.com/member/business-pages/{$page->slug}?campaign={$campaign->campaign_id}",
        ]);

        $earnRes->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'is_following' => true,
                'member_reward_balance' => 0.05,
                'remaining_budget' => 29.95,
            ]);

        // Verify Campaign Budget deduction
        $campaign->refresh();
        $this->assertEquals(29.95, (float) $campaign->remaining_amount);
        $this->assertEquals(0.05, (float) $campaign->spent_amount);

        // Verify Member Reward Wallet credit
        $visitor->refresh();
        $this->assertEquals(0.05, (float) $visitor->reward_balance);

        // Verify Follow Relationship exists
        $this->assertTrue(BusinessFollower::where('business_page_id', $page->id)->where('member_id', $visitor->id)->where('status', 'accepted')->exists());

        // Verify Ledger record exists
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->where('status', 'credited')->count());
    }

    /**
     * TEST 4: Repeat follow action on same campaign yields $0.00 additional reward.
     */
    public function test_repeat_follow_on_same_campaign_yields_zero_additional_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 30.00, 'remaining_amount' => 30.00]);

        $visitor = $this->createVerifiedMember('visitor_repeat', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // First follow -> +$0.05
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn")->assertOk();

        // Second follow -> $0.00
        $res2 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn");
        $res2->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => false,
                'already_rewarded' => true,
                'reward_amount_usd' => 0.00,
                'is_following' => true,
                'member_reward_balance' => 0.05,
            ]);

        // Campaign budget remains 29.95
        $this->assertEquals(29.95, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.05, (float) $visitor->fresh()->reward_balance);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 5: Pre-existing historical follow policy (Option A):
     * If member was already a follower, entering through campaign flow and clicking the campaign action verifies the active follow and grants the one-time $0.05 reward.
     */
    public function test_pre_existing_follower_entering_via_campaign_claims_one_time_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 20.00, 'remaining_amount' => 20.00]);

        $visitor = $this->createVerifiedMember('existing_follower', ['reward_balance' => 0.00]);

        // Historical follow created prior to campaign
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $visitor->id,
            'status' => 'accepted',
            'followed_at' => now()->subDays(5),
        ]);

        $this->actingAs($visitor, 'member');

        // Verified member claims campaign follow reward
        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn");

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
                'is_following' => true,
                'member_reward_balance' => 0.05,
            ]);

        $this->assertEquals(19.95, (float) $campaign->fresh()->remaining_amount);
        $this->assertEquals(0.05, (float) $visitor->fresh()->reward_balance);

        // Subsequent attempt -> blocked
        $res2 = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn");
        $res2->assertJson([
            'rewarded' => false,
            'already_rewarded' => true,
            'reward_amount_usd' => 0.00,
        ]);
    }

    /**
     * TEST 6: Unverified member cannot participate in Follow-to-Earn flow.
     */
    public function test_unverified_member_blocked_from_follow_to_earn(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $unverified = $this->createUnverifiedMember('unverified_user');
        $this->actingAs($unverified, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn");

        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
                'verified_required' => true,
                'rewarded' => false,
            ]);

        $this->assertEquals(0, AdReward::where('member_id', $unverified->id)->count());
        $this->assertEquals(0.00, (float) $unverified->fresh()->reward_balance);
        $this->assertEquals(30.00, (float) $campaign->fresh()->remaining_amount);
    }

    /**
     * TEST 7: Exhausted campaign (< $0.05) cannot grant rewards and auto-stops.
     */
    public function test_exhausted_campaign_blocks_follow_reward(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner, ['budget' => 0.05, 'remaining_amount' => 0.00, 'spent_amount' => 0.05]);

        $visitor = $this->createVerifiedMember('visitor_exhausted');
        $this->actingAs($visitor, 'member');

        $res = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn");

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
                'rewarded' => false,
                'reward_amount_usd' => 0.00,
            ]);

        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }

    /**
     * TEST 8: Campaign Itemized Engagements shows Interested and Followed Page actions.
     */
    public function test_campaign_engagements_shows_interested_and_follow_actions(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $visitor = $this->createVerifiedMember('visitor_logged');
        $this->actingAs($visitor, 'member');

        // 1. Click Interested
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => true])->assertOk();

        // 2. Follow to Earn
        $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/follow-to-earn")->assertOk();

        // Owner views engagements
        $this->actingAs($owner, 'member');
        $engRes = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $engRes->assertOk();
        $items = collect($engRes->json('engagements.data'));

        // Phase 5: People Engaged lists credited rewards
        $rewardItem = $items->first();
        $this->assertNotNull($rewardItem);
        $this->assertEquals(0.05, (float) $rewardItem['reward_amount_usd']);
        $this->assertEquals('Rewarded Visit', $rewardItem['action_label']);

        // Check member engagement detail timeline for Interested activity
        $detailRes = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/{$visitor->id}");
        $detailRes->assertOk();
        $timeline = collect($detailRes->json('timeline'));
        $interestedItem = $timeline->firstWhere('action_label', 'Interested');
        $this->assertNotNull($interestedItem);
        $this->assertEquals(0.00, (float) $interestedItem['reward_amount_usd']);
    }

    /**
     * TEST 9: Business ad interest strictly validates consent_accepted matrix and blocks direct API bypass.
     */
    public function test_business_interest_consent_validation_matrix_and_security_bypass(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $campaign = $this->createActiveCampaign($page, $owner);

        $visitor = $this->createVerifiedMember('visitor_bypass', ['reward_balance' => 0.00]);
        $this->actingAs($visitor, 'member');

        // Case 1: Missing consent
        $resMissing = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", []);
        $resMissing->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Please accept the profile-sharing consent before continuing.',
            ]);

        // Case 2: Boolean false
        $resFalse = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => false]);
        $resFalse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Please accept the profile-sharing consent before continuing.',
            ]);

        // Case 3: Integer 0
        $resZero = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => 0]);
        $resZero->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Please accept the profile-sharing consent before continuing.',
            ]);

        // Case 4: String "false"
        $resStringFalse = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => 'false']);
        $resStringFalse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Please accept the profile-sharing consent before continuing.',
            ]);

        // Case 5: Null
        $resNull = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => null]);
        $resNull->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Please accept the profile-sharing consent before continuing.',
            ]);

        // Verify zero clicks or activities created during rejected bypass attempts
        $this->assertSame(0, AdClick::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
        $this->assertSame(0, \App\Models\AdCampaignActivity::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());

        // Case 6: Explicit affirmative consent_accepted = true passes
        $resPass = $this->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest", ['consent_accepted' => true]);
        $resPass->assertOk()
            ->assertJson([
                'success' => true,
                'campaign_id' => $campaign->campaign_id,
            ]);

        $this->assertSame(1, AdClick::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
        $this->assertSame(1, \App\Models\AdCampaignActivity::where('ad_campaign_id', $campaign->id)->where('member_id', $visitor->id)->count());
    }
}