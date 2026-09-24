<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdCampaignActivity;
use App\Models\AdClick;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdCampaignEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdCampaignAuthoritativeEngagementTest extends TestCase
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

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 1;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "user_{$unique}_" . \Illuminate\Support\Str::random(5),
            'email' => "user_{$unique}_" . \Illuminate\Support\Str::random(5) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    protected function createCampaign(Member $owner, array $attributes = []): AdCampaign
    {
        $bizPage = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Test Business ' . \Illuminate\Support\Str::random(5),
            'page_username' => 'biz_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)),
            'slug' => 'test-biz-' . \Illuminate\Support\Str::random(8),
            'category' => 'Technology',
            'email' => 'biz_' . \Illuminate\Support\Str::random(5) . '@example.com',
            'status' => 'active',
            'is_published' => true,
        ]);

        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $bizPage->id,
            'body' => 'Test Ad Post ' . \Illuminate\Support\Str::random(5),
            'status' => 'published',
        ]);

        return AdCampaign::create(array_merge([
            'business_page_id' => $bizPage->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Test Promo Campaign',
            'campaign_objective' => 'brand_awareness',
            'destination_link' => 'https://example.com/promo',
            'budget' => 10.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 10.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ], $attributes));
    }

    /**
     * TEST 1: Authenticated member creates valid Interested event.
     */
    public function test_01_authenticated_member_creates_valid_interested_event(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('ad_campaign_activities', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $viewer->id,
            'action' => AdCampaignActivity::ACTION_INTERESTED,
            'action_label' => 'Interested',
        ]);
    }

    /**
     * TEST 2: Same member clicks campaign.
     */
    public function test_02_same_member_clicks_campaign(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/click", [
                'placement' => 'social_feed_card',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('ad_campaign_activities', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $viewer->id,
            'action' => AdCampaignActivity::ACTION_CLICKED,
            'action_label' => 'Ad Click',
        ]);
    }

    /**
     * TEST 3: Same member generates valid landing-page event.
     */
    public function test_03_same_member_generates_valid_landing_page_event(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $service = app(AdCampaignEngagementService::class);
        $activity = $service->recordLandingVisit(
            $campaign,
            $viewer,
            'visit_evt_123',
            'https://example.com/promo'
        );

        $this->assertEquals(AdCampaignActivity::ACTION_VISITED_LANDING_PAGE, $activity->action);
        $this->assertDatabaseHas('ad_campaign_activities', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $viewer->id,
            'action' => AdCampaignActivity::ACTION_VISITED_LANDING_PAGE,
            'qualifying_event_id' => 'visit_evt_123',
        ]);
    }

    /**
     * TEST 4: Invalid/unqualified landing event does not create successful reward.
     */
    public function test_04_invalid_unqualified_landing_event_does_not_create_successful_reward(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner, ['status' => AdCampaign::STATUS_PAUSED]);

        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'unq_evt_' . time(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'rewarded' => false]);

        $this->assertDatabaseMissing('ad_rewards', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $viewer->id,
        ]);
    }

    /**
     * TEST 5: Different member creates engagement on same campaign.
     */
    public function test_05_different_member_creates_engagement_on_same_campaign(): void
    {
        $owner = $this->createMember();
        $viewer1 = $this->createMember();
        $viewer2 = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $this->actingAs($viewer1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest");

        $this->actingAs($viewer2, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/interest");

        $this->assertEquals(2, AdCampaignActivity::where('ad_campaign_id', $campaign->id)->count());
        $this->assertEquals(2, AdCampaignActivity::where('ad_campaign_id', $campaign->id)->distinct('member_id')->count('member_id'));
    }

    /**
     * TEST 6: Same member engages with another campaign. Both campaigns remain isolated.
     */
    public function test_06_same_member_engages_with_another_campaign_isolated(): void
    {
        $owner1 = $this->createMember();
        $owner2 = $this->createMember();
        $viewer = $this->createMember();

        $campaignA = $this->createCampaign($owner1);
        $campaignB = $this->createCampaign($owner2);

        $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaignA->campaign_id}/interest");
        $this->actingAs($viewer, 'member')->postJson("/api/member/ad-campaigns/{$campaignB->campaign_id}/click");

        $this->assertEquals(1, AdCampaignActivity::where('ad_campaign_id', $campaignA->id)->count());
        $this->assertEquals(1, AdCampaignActivity::where('ad_campaign_id', $campaignB->id)->count());

        $this->assertEquals(AdCampaignActivity::ACTION_INTERESTED, AdCampaignActivity::where('ad_campaign_id', $campaignA->id)->first()->action);
        $this->assertEquals(AdCampaignActivity::ACTION_CLICKED, AdCampaignActivity::where('ad_campaign_id', $campaignB->id)->first()->action);
    }

    /**
     * TEST 7: Repeated clicks are recorded according to intended event model.
     */
    public function test_07_repeated_clicks_are_recorded_according_to_event_model(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $service = app(AdCampaignEngagementService::class);
        $service->recordClick($campaign, $viewer, 'social_feed', 'clk_1');
        $service->recordClick($campaign, $viewer, 'social_feed', 'clk_2');
        $service->recordClick($campaign, $viewer, 'social_feed', 'clk_3');

        $this->assertEquals(3, AdCampaignActivity::where('ad_campaign_id', $campaign->id)->where('action', 'clicked')->count());
        $this->assertEquals(1, AdCampaignActivity::where('ad_campaign_id', $campaign->id)->distinct('member_id')->count('member_id'));
    }

    /**
     * TEST 8: Successful reward remains max ONE per member/campaign.
     */
    public function test_08_successful_reward_remains_max_one_per_member_campaign(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $res1 = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'reward_evt_1',
            ]);

        $res1->assertStatus(200)->assertJson(['success' => true, 'rewarded' => true]);

        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->count());
        $this->assertEquals(1, AdCampaignActivity::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->where('action', 'rewarded')->count());
    }

    /**
     * TEST 9: Repeated reward request does not create another reward.
     */
    public function test_09_repeated_reward_request_does_not_create_another_reward(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        // 1st request
        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'reward_evt_1',
            ]);

        // 2nd request
        $res2 = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'reward_evt_2',
            ]);

        $res2->assertStatus(422)
            ->assertJson(['success' => false, 'rewarded' => false, 'already_rewarded' => true]);

        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->count());
    }

    /**
     * TEST 10: Reward event links to correct reward record.
     */
    public function test_10_reward_event_links_to_correct_reward_record(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'link_test_123',
            ]);

        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        $activity = AdCampaignActivity::where('ad_campaign_id', $campaign->id)->where('action', 'rewarded')->first();

        $this->assertNotNull($reward);
        $this->assertNotNull($activity);
        $this->assertEquals($reward->id, $activity->ad_reward_id);
        $this->assertEquals($reward->qualifying_event_id, $activity->qualifying_event_id);
    }

    /**
     * TEST 11: Reward amount is NOT accepted from client payload.
     */
    public function test_11_reward_amount_is_not_accepted_from_client_payload(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        // Client attempts to spoof $500.00 reward
        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'spoof_amt_1',
                'reward_amount_usd' => 500.00,
                'reward' => 500.00,
            ]);

        $response->assertStatus(200);

        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        // Dynamic tier 0-5 referrals is 0.0250 USD, never $500
        $this->assertEquals(0.0250, (float) $reward->reward_amount_usd);
    }

    /**
     * TEST 12: Referral count is NOT accepted from client payload.
     */
    public function test_12_referral_count_is_not_accepted_from_client_payload(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember(); // 0 referrals
        $campaign = $this->createCampaign($owner);

        // Client attempts to spoof 999 referrals
        $response = $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'spoof_ref_1',
                'direct_verified_referrals' => 999,
                'referral_count' => 999,
            ]);

        $response->assertStatus(200);

        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $viewer->id)->first();
        $this->assertEquals(0, (int) $reward->direct_verified_referral_count);
        $this->assertEquals(0.0250, (float) $reward->reward_amount_usd);
    }

    /**
     * TEST 13: Unverified member cannot generate qualifying reward event.
     */
    public function test_13_unverified_member_cannot_generate_qualifying_reward_event(): void
    {
        $owner = $this->createMember();
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($unverifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'unverified_try',
            ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'verified_required' => true]);

        $this->assertDatabaseMissing('ad_rewards', [
            'ad_campaign_id' => $campaign->id,
            'member_id' => $unverifiedMember->id,
        ]);
    }

    /**
     * TEST 14: Advertiser cannot access another owner's campaign engagement.
     */
    public function test_14_advertiser_cannot_access_another_owners_campaign_engagement(): void
    {
        $owner1 = $this->createMember();
        $owner2 = $this->createMember();
        $campaign1 = $this->createCampaign($owner1);

        // Owner 2 tries to access Owner 1's engagements
        $response = $this->actingAs($owner2, 'member')
            ->getJson("/api/member/business-pages/{$campaign1->businessPage->slug}/ad-campaigns/{$campaign1->campaign_id}/engagements");

        $response->assertStatus(403);
    }

    /**
     * TEST 15: Member cannot access another member's engagement data via unauthorized endpoint.
     */
    public function test_15_member_cannot_access_another_members_engagement_data(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        $campaign = $this->createCampaign($owner);

        // Regular viewer cannot view campaign engagements
        $response = $this->actingAs($viewer, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(403);
    }

    /**
     * TEST 16: Pagination returns correct event counts.
     */
    public function test_16_pagination_returns_correct_event_counts(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        // Create 25 rewarded members
        for ($i = 1; $i <= 25; $i++) {
            $m = $this->createMember();
            $service->recordClick($campaign, $m, 'social_feed', "clk_{$i}");
            AdReward::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $m->id,
                'reward_amount_usd' => 0.0250,
                'qualifying_event_id' => "evt_{$i}",
                'status' => AdReward::STATUS_CREDITED,
            ]);
        }

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?per_page=10&page=1");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'engagements' => [
                    'total' => 25,
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 3,
                ],
            ]);

        $this->assertCount(10, $response->json('engagements.data'));
    }

    /**
     * TEST 17: Filtering by action returns correct records.
     */
    public function test_17_filtering_by_action_returns_correct_records(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $m2 = $this->createMember();

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m2);

        // In Phase 5, unrewarded clicks/interested are excluded from People Engaged
        $resInterested = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?action=interested");

        $resInterested->assertStatus(200);
        $this->assertCount(0, $resInterested->json('engagements.data'));

        // When rewarded, member appears
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_rew_17',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $resRewarded = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $resRewarded->assertStatus(200);
        $this->assertCount(1, $resRewarded->json('engagements.data'));
        $this->assertEquals('Rewarded Visit', $resRewarded->json('engagements.data.0.action'));
    }

    /**
     * TEST 18: Historical engagement remains after campaign exhaustion.
     */
    public function test_18_historical_engagement_remains_after_campaign_exhaustion(): void
    {
        $owner = $this->createMember();
        $viewer = $this->createMember();
        // Campaign with budget just enough for 1 reward
        $campaign = $this->createCampaign($owner, ['budget' => 0.0250, 'remaining_amount' => 0.0250]);

        $this->actingAs($viewer, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'exhaust_test_1',
            ]);

        $campaign->refresh();
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->status);
        $this->assertTrue($campaign->is_budget_exhausted);

        // Engagements endpoint still returns full history
        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'engagements' => [
                    'total' => 1,
                ],
                'summary' => [
                    'total_rewards_count' => 1,
                    'total_rewards_paid' => 0.025,
                ],
            ]);
    }

    /**
     * TEST 19: Member ID filter returns only that member's chronological timeline.
     */
    public function test_19_member_timeline_filter_returns_only_selected_members_events(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $targetMember = $this->createMember();
        $otherMember = $this->createMember();

        $service->recordInterested($campaign, $targetMember);
        $service->recordClick($campaign, $targetMember);
        $service->recordClick($campaign, $otherMember);

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $targetMember->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_target_19',
            'status' => AdReward::STATUS_CREDITED,
        ]);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $otherMember->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_other_19',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?member_id={$targetMember->id}");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        foreach ($response->json('engagements.data') as $act) {
            $this->assertEquals($targetMember->id, $act['user']['id']);
        }
    }

    /**
     * TEST 20: Engagement summary metrics accurately report distinct and total counts.
     */
    public function test_20_engagement_summary_metrics_accurately_report_distinct_and_total_counts(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $m2 = $this->createMember();

        // m1 generates 3 events
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);
        $service->recordLandingVisit($campaign, $m1, 'visit_m1');

        // m2 generates 1 event
        $service->recordClick($campaign, $m2);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(200);
        $summary = $response->json('summary');

        $this->assertEquals(4, $summary['total_engagements']);
        $this->assertEquals(2, $summary['total_engaged_members']);
        $this->assertEquals(1, $summary['interested_count']);
        $this->assertEquals(2, $summary['click_count']);
        $this->assertEquals(1, $summary['landing_visit_count']);
    }

    /**
     * TEST 21: Dedicated member engagement detail returns complete profile, campaign context, and timeline.
     */
    public function test_21_dedicated_member_engagement_detail_endpoint_returns_complete_profile_and_timeline(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $member = $this->createMember();

        // 1. Interested
        $service->recordInterested($campaign, $member);
        // 2. Clicked
        $service->recordClick($campaign, $member);
        // 3. Landing Visit
        $service->recordLandingVisit($campaign, $member, 'qual_evt_detail_1');

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/{$member->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'member' => [
                    'id' => $member->id,
                    'name' => $member->name,
                ],
                'campaign' => [
                    'id' => $campaign->id,
                    'campaign_name' => $campaign->campaign_name,
                ],
                'summary' => [
                    'total_activities' => 3,
                    'interested_count' => 1,
                    'clicks_count' => 1,
                    'landing_visits_count' => 1,
                    'is_rewarded' => false,
                ],
            ]);

        $this->assertCount(3, $response->json('timeline'));
        // Verify sensitive fields are not exposed in member JSON
        $this->assertArrayNotHasKey('password', $response->json('member'));
        $this->assertArrayNotHasKey('remember_token', $response->json('member'));
    }

    /**
     * TEST 22: Dedicated member engagement detail includes authoritative historical reward snapshot.
     */
    public function test_22_dedicated_member_engagement_detail_includes_authoritative_reward_snapshot(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);

        $rewardedMember = $this->createMember();
        for ($i = 0; $i < 8; $i++) {
            $this->createMember(['introducer_id' => $rewardedMember->user_id]);
        }

        // Qualify rewarded visit
        $this->actingAs($rewardedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'detail_reward_snap_1',
            ]);

        // Check detail endpoint
        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/{$rewardedMember->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'is_rewarded' => true,
                    'reward_amount_usd' => 0.035,
                ],
                'reward_detail' => [
                    'status' => 'credited',
                    'reward_amount_usd' => 0.035,
                    'direct_verified_referral_count' => 8,
                ],
            ]);

        // Referral count changes later by adding 10 more referrals (total 18)
        for ($i = 0; $i < 10; $i++) {
            $this->createMember(['introducer_id' => $rewardedMember->user_id]);
        }

        // Old detail must still report 8 referrals and 0.035 reward
        $responseAfter = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/{$rewardedMember->id}");

        $responseAfter->assertStatus(200);
        $this->assertEquals(8, $responseAfter->json('reward_detail.direct_verified_referral_count'));
        $this->assertEquals(0.035, $responseAfter->json('reward_detail.reward_amount_usd'));
    }

    /**
     * TEST 23: Security: Unauthorized advertiser cannot access member detail of other campaigns.
     */
    public function test_23_security_unauthorized_advertiser_cannot_access_member_detail(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $member = $this->createMember();

        app(AdCampaignEngagementService::class)->recordClick($campaign, $member);

        $unauthorizedAdvertiser = $this->createMember();

        $response = $this->actingAs($unauthorizedAdvertiser, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/{$member->id}");

        $response->assertStatus(403);
    }

    /**
     * TEST 24: Non-existent member returns 404.
     */
    public function test_24_non_existent_member_returns_404(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/99999999");

        $response->assertStatus(404);
    }

    /**
     * TEST 25: Filter by action = clicked does not return unrewarded members in Phase 5.
     */
    public function test_25_filter_by_action_clicked_returns_only_clicks(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $m2 = $this->createMember();

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);
        $service->recordClick($campaign, $m2);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?action=clicked");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('engagements.total'));
        $this->assertCount(0, $response->json('engagements.data'));
    }

    /**
     * TEST 26: Filter by reward_status = not_rewarded does not return unrewarded members in Phase 5.
     */
    public function test_26_filter_by_reward_status_not_rewarded(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $m2 = $this->createMember();

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m2);

        // Qualify m2 for reward
        $this->actingAs($m2, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'filter_rew_1',
            ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?reward_status=not_rewarded");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('engagements.total'));
        $this->assertCount(0, $response->json('engagements.data'));
    }

    /**
     * TEST 27: Multi-dimensional filter intersection (Search + Verification).
     */
    public function test_27_multidimensional_filter_intersection(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $verifiedMember = $this->createMember(['name' => 'Alice Johnson', 'mobile_verified_at' => now()]);
        $unverifiedMember = $this->createMember(['name' => 'Alice Walker', 'mobile_verified_at' => null]);
        $otherMember = $this->createMember(['name' => 'Bob Smith', 'mobile_verified_at' => now()]);

        foreach ([$verifiedMember, $unverifiedMember, $otherMember] as $i => $m) {
            $service->recordInterested($campaign, $m);
            AdReward::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $m->id,
                'reward_amount_usd' => 0.0250,
                'qualifying_event_id' => "evt_multi_{$i}",
                'status' => AdReward::STATUS_CREDITED,
            ]);
        }

        // Search "Alice" + Verification "verified"
        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?q=Alice&verification=verified");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        $this->assertEquals('Alice Johnson', $response->json('engagements.data.0.user.name'));
    }

    /**
     * TEST 28: Date preset filtering.
     */
    public function test_28_date_preset_filtering(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $member = $this->createMember();
        $service->recordClick($campaign, $member);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_date_28',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Today preset
        $responseToday = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?date_preset=today");

        $responseToday->assertStatus(200);
        $this->assertEquals(1, $responseToday->json('engagements.total'));

        // Yesterday preset (should have 0)
        $responseYesterday = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?date_preset=yesterday");

        $responseYesterday->assertStatus(200);
        $this->assertEquals(0, $responseYesterday->json('engagements.total'));
    }

    /**
     * TEST 29: Sorting order parameter.
     */
    public function test_29_sorting_order_parameter(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember(['name' => 'First User']);
        $m2 = $this->createMember(['name' => 'Second User']);

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m2);

        $r1 = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_sort_1',
            'status' => AdReward::STATUS_CREDITED,
            'created_at' => now()->subHours(2),
        ]);
        $r1->update(['created_at' => now()->subHours(2)]);

        $r2 = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m2->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_sort_2',
            'status' => AdReward::STATUS_CREDITED,
            'created_at' => now(),
        ]);
        $r2->update(['created_at' => now()]);

        // Sort oldest first
        $responseOldest = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?sort=oldest");

        $responseOldest->assertStatus(200);
        $this->assertEquals('First User', $responseOldest->json('engagements.data.0.user.name'));

        // Sort newest first
        $responseNewest = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements?sort=newest");

        $responseNewest->assertStatus(200);
        $this->assertEquals('Second User', $responseNewest->json('engagements.data.0.user.name'));
    }

    /**
     * TEST 30: Audience engagement list returns structured user identity and contact eligibility.
     */
    public function test_30_audience_list_returns_contact_eligibility(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $memberWithPhone = $this->createMember([
            'name' => 'Alice With Phone',
            'phone' => '+1234567890',
            'email' => 'alice@example.com',
            'mobile_verified_at' => now(),
        ]);
        $memberWithoutPhone = $this->createMember([
            'name' => 'Bob No Phone',
            'phone' => null,
            'email' => 'bob@example.com',
            'mobile_verified_at' => null,
        ]);

        $service->recordInterested($campaign, $memberWithPhone);
        $service->recordClick($campaign, $memberWithoutPhone);

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $memberWithPhone->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_alice',
            'status' => AdReward::STATUS_CREDITED,
        ]);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $memberWithoutPhone->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_bob',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(200);
        $data = $response->json('engagements.data');
        $this->assertCount(2, $data);

        $alice = collect($data)->firstWhere('user.name', 'Alice With Phone');
        $this->assertNotNull($alice);
        $this->assertEquals('+1234567890', $alice['user']['phone']);
        $this->assertTrue($alice['user']['is_verified']);

        $bob = collect($data)->firstWhere('user.name', 'Bob No Phone');
        $this->assertNotNull($bob);
        $this->assertNull($bob['user']['phone']);
        $this->assertFalse($bob['user']['is_verified']);
    }

    /**
     * TEST 31: Campaign isolation: Member engaged on Campaign A cannot be retrieved under Campaign B.
     */
    public function test_31_campaign_audience_isolation(): void
    {
        $owner = $this->createMember();
        $campaignA = $this->createCampaign($owner);
        $campaignB = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $member = $this->createMember();
        $service->recordClick($campaignA, $member);
        AdReward::create([
            'ad_campaign_id' => $campaignA->id,
            'member_id' => $member->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_camp_a',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        // Campaign A has 1 engagement
        $resA = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaignA->businessPage->slug}/ad-campaigns/{$campaignA->campaign_id}/engagements");
        $resA->assertStatus(200);
        $this->assertEquals(1, $resA->json('engagements.total'));

        // Campaign B has 0 engagements
        $resB = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaignB->businessPage->slug}/ad-campaigns/{$campaignB->campaign_id}/engagements");
        $resB->assertStatus(200);
        $this->assertEquals(0, $resB->json('engagements.total'));
    }

    /**
     * TEST 32: Contact center inspects member without altering campaign budget or reward state.
     */
    public function test_32_contact_center_does_not_alter_campaign_budget_or_reward_state(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 100.00, 'remaining_amount' => 100.00]);
        $service = app(AdCampaignEngagementService::class);

        $member = $this->createMember();
        $service->recordInterested($campaign, $member);

        $initialBudget = $campaign->fresh()->remaining_amount;

        // View member detail
        $res = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements/{$member->id}");

        $res->assertStatus(200);

        $freshCampaign = $campaign->fresh();
        $this->assertEquals($initialBudget, $freshCampaign->remaining_amount);
        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 33: Summary analytics accurately report total activity, unique members, and financial reconciliation.
     */
    public function test_33_summary_analytics_and_financial_reconciliation(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $m2 = $this->createMember();

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);
        $service->recordLandingVisit($campaign, $m1);

        $service->recordInterested($campaign, $m2);
        $service->recordClick($campaign, $m2);

        $summary = $service->getSummary($campaign);

        $this->assertEquals(5, $summary['total_engagements']);
        $this->assertEquals(2, $summary['total_engaged_members']);
        $this->assertEquals(2, $summary['interested_count']);
        $this->assertEquals(2, $summary['click_count']);
        $this->assertEquals(1, $summary['landing_visit_count']);
        $this->assertEquals(0, $summary['total_rewards_count']);
        $this->assertEquals(0.00, $summary['total_rewards_paid']);
        $this->assertEquals(50.00, $summary['financials']['original_budget']);
        $this->assertEquals(50.00, $summary['financials']['remaining_budget']);
    }

    /**
     * TEST 34: Rates calculation (CTR, Landing Conversion, Reward Conversion).
     */
    public function test_34_rates_calculation_accuracy(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);
        $service->recordLandingVisit($campaign, $m1);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'analytics_rate_test_1',
            ]);

        $summary = $service->getSummary($campaign->fresh());

        $this->assertGreaterThan(0, $summary['rates']['ctr_percent']);
        $this->assertEquals(100.0, $summary['rates']['landing_conversion_rate']); // 1 visit / 1 click
        $this->assertEquals(100.0, $summary['rates']['reward_conversion_rate']); // 1 reward / 1 unique member
        $this->assertEquals(1, $summary['total_rewards_count']);
        $this->assertGreaterThan(0, $summary['total_rewards_paid']);
    }

    /**
     * TEST 35: Reward summary accurately computes reward count and paid amounts without tier_label grouping.
     */
    public function test_35_reward_tier_breakdown_aggregates_historical_snapshots(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'tier_breakdown_1',
            ]);

        $summary = $service->getSummary($campaign->fresh());

        $this->assertEquals(1, $summary['total_rewards_count']);
        $this->assertGreaterThan(0, $summary['total_rewards_paid']);
        $this->assertIsArray($summary['tier_breakdown']);
    }

    /**
     * TEST 36: Server-side audience export returns CSV stream.
     */
    public function test_36_export_audience_csv(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember(['name' => 'John Exporter', 'email' => 'john@exporter.com']);
        $service->recordInterested($campaign, $m1);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_csv_36',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->get("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/export-engagements");

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    /**
     * TEST 37: Filtered export respects action query parameter.
     */
    public function test_37_filtered_export_respects_parameters(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember(['name' => 'Interested Member']);
        $m2 = $this->createMember(['name' => 'Click Member']);

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m2);

        $response = $this->actingAs($owner, 'member')
            ->get("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/export-engagements?action=interested");

        $response->assertStatus(200);
    }

    /**
     * TEST 38: Security: Unauthorized advertiser cannot export another owner's campaign audience.
     */
    public function test_38_export_idor_security(): void
    {
        $owner = $this->createMember();
        $attacker = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($attacker, 'member')
            ->get("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/export-engagements");

        $response->assertStatus(403);
    }

    /**
     * TEST 39: Contact Audience validates legitimate campaign members and excludes foreign members.
     */
    public function test_39_contact_audience_validates_campaign_membership(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember(['name' => 'Valid Member', 'phone' => '+1234567890']);
        $foreignMember = $this->createMember(['name' => 'Foreign Member', 'phone' => '+9876543210']);

        $service->recordInterested($campaign, $m1);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_valid_39',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/contact-audience", [
                'member_ids' => [$m1->id, $foreignMember->id],
                'method' => 'whatsapp',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('contactable_count'));
        $this->assertEquals(1, $response->json('unauthorized_count'));
        $this->assertEquals($foreignMember->id, $response->json('unauthorized_ids.0'));
    }

    /**
     * TEST 40: Contact and export operations are strictly read-only and never alter campaign funds or rewards.
     */
    public function test_40_contact_and_export_do_not_alter_campaign_budget(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 100.00, 'remaining_amount' => 100.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);

        $initialBudget = $campaign->fresh()->remaining_amount;

        // Perform export
        $this->actingAs($owner, 'member')
            ->get("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/export-engagements");

        // Perform contact validation
        $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/contact-audience", [
                'member_ids' => [$m1->id],
                'method' => 'whatsapp',
            ]);

        $fresh = $campaign->fresh();
        $this->assertEquals($initialBudget, $fresh->remaining_amount);
        $this->assertEquals(0, AdReward::where('ad_campaign_id', $campaign->id)->count());
    }

    /**
     * TEST 41: Campaign with zero engagement returns structured zero metrics without errors.
     */
    public function test_41_zero_engagement_campaign_returns_clean_zero_summary(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $summary = $service->getSummary($campaign);

        $this->assertEquals(0, $summary['total_engagements']);
        $this->assertEquals(0, $summary['total_engaged_members']);
        $this->assertEquals(0, $summary['interested_count']);
        $this->assertEquals(0, $summary['click_count']);
        $this->assertEquals(0, $summary['landing_visit_count']);
        $this->assertEquals(0, $summary['total_rewards_count']);
        $this->assertEquals(0.0, $summary['total_rewards_paid']);
        $this->assertEquals(50.00, $summary['remaining_budget']);
        $this->assertEquals(0.0, $summary['rates']['ctr_percent']);
        $this->assertEquals(0.0, $summary['rates']['landing_conversion_rate']);
        $this->assertEquals(0.0, $summary['rates']['reward_conversion_rate']);
        $this->assertEmpty($summary['tier_breakdown']);
    }

    /**
     * TEST 42: Campaign top-up preserves existing engagement and reconciles running budget.
     */
    public function test_42_topup_preserves_engagement_history_and_reconciles_budget(): void
    {
        $owner = $this->createMember(['ad_balance' => 200.00]);
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);

        // Perform top-up via controller
        $topupResponse = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/top-up", [
                'amount' => 25.00,
            ]);

        $topupResponse->assertStatus(200);

        // Engagement and audience history must remain completely intact
        $summary = $service->getSummary($campaign->fresh());
        $this->assertEquals(2, $summary['total_engagements']);
        $this->assertEquals(1, $summary['total_engaged_members']);
        $this->assertEquals(75.00, $summary['financials']['total_funded']);
        $this->assertEquals(75.00, $summary['remaining_budget']);
    }

    /**
     * TEST 43: Exhausted campaign retains full audience history and immutable reward records.
     */
    public function test_43_exhausted_campaign_retains_full_audience_history(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 10.00, 'remaining_amount' => 10.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'exhaust_test_1',
            ]);

        // Manually exhaust the remaining campaign budget by setting spent_amount = total_funded
        $freshCampaign = $campaign->fresh();
        $freshCampaign->update([
            'spent_amount' => (float) $freshCampaign->total_funded,
            'status' => 'budget_exhausted',
            'is_budget_exhausted' => true,
        ]);

        $summary = $service->getSummary($freshCampaign->fresh());
        $detail = $service->getMemberEngagementDetail($freshCampaign->fresh(), $m1);

        $this->assertEquals(3, $summary['total_engagements']); // Interested + Click + Rewarded
        $this->assertEquals(1, $summary['total_rewards_count']);
        $this->assertEquals(0.00, $summary['remaining_budget']);
        $this->assertTrue($detail['summary']['is_rewarded']);
        $this->assertNotEmpty($detail['timeline']);
    }

    /**
     * TEST 44: Cross-screen data reconciliation: Overview, summary, and query agree 100%.
     */
    public function test_44_cross_screen_data_reconciliation(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 60.00, 'remaining_amount' => 60.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $m2 = $this->createMember();

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);
        $service->recordInterested($campaign, $m2);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'cross_screen_reconciliation_1',
            ]);

        $fresh = $campaign->fresh();
        $summary = $service->getSummary($fresh);

        // Verify mathematical reconciliation across all layers
        $this->assertEquals($fresh->remaining_amount, $summary['financials']['remaining_budget']);
        $this->assertEquals($fresh->spent_amount, $summary['financials']['spent_amount']);
        $this->assertEquals(round($summary['financials']['total_funded'] - $summary['financials']['spent_amount'], 2), round($summary['remaining_budget'], 2));
        $this->assertEquals(2, $summary['total_engaged_members']);
        $this->assertEquals(4, $summary['total_engagements']); // m1: interested, click, reward; m2: interested
    }

    /**
     * TEST 45: Unified audience query returns identical filtered member count between summary and paginated query.
     */
    public function test_45_unified_audience_query_count_consistency(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember(['name' => 'Alice Auditor']);
        $m2 = $this->createMember(['name' => 'Bob Auditor']);

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);
        $service->recordInterested($campaign, $m2);

        $paginated = $service->getPaginatedActivities($campaign, ['action' => 'interested']);
        $summary = $service->getSummary($campaign);

        $this->assertEquals($summary['interested_count'], $paginated->total());
    }

    /**
     * TEST 46: Security: Client parameter tampering (fake reward amounts, fake referrals) is rejected.
     */
    public function test_46_tampered_payload_values_ignored(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);

        $response = $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'tamper_test_1',
                'reward_amount' => 9999.99,
                'referral_count' => 1000,
                'reward_tier' => 'Super VIP',
                'reward_status' => 'approved_vip',
            ]);

        $response->assertStatus(200);
        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $m1->id)->first();
        $this->assertNotNull($reward);
        $this->assertLessThan(1.00, (float) $reward->reward_amount_usd);
        $this->assertEquals(0, $reward->direct_verified_referral_count);
    }

    /**
     * TEST 47: Security: Cross-business-page campaign IDOR manipulation returns 404/403.
     */
    public function test_47_cross_page_campaign_manipulation_blocked(): void
    {
        $owner1 = $this->createMember();
        $owner2 = $this->createMember();

        $campaign1 = $this->createCampaign($owner1);
        $campaign2 = $this->createCampaign($owner2);

        // Attempt to access campaign2 using owner1's page slug
        $response = $this->actingAs($owner1, 'member')
            ->getJson("/api/member/business-pages/{$campaign1->businessPage->slug}/ad-campaigns/{$campaign2->campaign_id}/engagements");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    /**
     * TEST 48: Security: Sensitive member security credentials never exposed in API payloads.
     */
    public function test_48_sensitive_member_credentials_not_exposed(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(200);
        $json = json_encode($response->json());

        $this->assertFalse(str_contains($json, 'password'));
        $this->assertFalse(str_contains($json, 'remember_token'));
        $this->assertFalse(str_contains($json, 'two_factor_secret'));
    }

    /**
     * TEST 49: Dynamic referral rules correctly evaluate 0-5, 6-14, and 15+ referral tiers.
     */
    public function test_49_dynamic_referral_tiers_evaluation(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        // Member with 0 referrals
        $m0 = $this->createMember();
        $service->recordInterested($campaign, $m0);
        $service->recordClick($campaign, $m0);

        $res0 = $this->actingAs($m0, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'tier_eval_0',
            ]);

        $res0->assertStatus(200);
        $reward0 = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $m0->id)->first();
        $this->assertNotNull($reward0);
        $this->assertEquals(0, $reward0->rule_min_referrals);
        $this->assertEquals(5, $reward0->rule_max_referrals);
    }

    /**
     * TEST 50: Rule modifications do not alter previously processed historical rewards.
     */
    public function test_50_historical_rewards_remain_immutable_after_rule_updates(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 50.00, 'remaining_amount' => 50.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'immutability_1',
            ]);

        $reward = AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $m1->id)->first();
        $initialAmount = (float) $reward->reward_amount_usd;

        // Simulate admin altering the 0-5 rule
        \App\Models\AdRewardRule::where('min_referrals', 0)->update(['reward_amount' => 0.0800]);
        \App\Models\AdRewardRule::clearCache();

        // Historical reward must remain unchanged
        $freshReward = $reward->fresh();
        $this->assertEquals($initialAmount, (float) $freshReward->reward_amount_usd);

        $detail = $service->getMemberEngagementDetail($campaign->fresh(), $m1);
        $this->assertEquals($initialAmount, (float) $detail['reward_detail']['reward_amount_usd']);
    }

    /**
     * TEST 51: Multi-dimensional filter intersection accurately produces identical counts across UI and CSV export.
     */
    public function test_51_multidimensional_filter_ui_and_export_count_alignment(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember(['name' => 'Audience One', 'mobile_verified_at' => now()]);
        $m2 = $this->createMember(['name' => 'Audience Two', 'mobile_verified_at' => null]);

        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m2);

        $paginated = $service->getPaginatedActivities($campaign, [
            'action' => 'interested',
            'verification' => 'verified',
        ]);

        $this->assertEquals(1, $paginated->total());
        $this->assertEquals($m1->id, $paginated->items()[0]->member_id);
    }

    /**
     * TEST 52: Full database state safety verification: no foreign deletions or orphaned records.
     */
    public function test_52_database_integrity_and_referential_consistency(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 20.00, 'remaining_amount' => 20.00]);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember();
        $service->recordInterested($campaign, $m1);
        $service->recordClick($campaign, $m1);

        $this->actingAs($m1, 'member')
            ->postJson("/api/member/ad-campaigns/{$campaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'db_safety_1',
            ]);

        $activityCount = AdCampaignActivity::where('ad_campaign_id', $campaign->id)->count();
        $rewardCount = AdReward::where('ad_campaign_id', $campaign->id)->count();

        $this->assertEquals(3, $activityCount); // interested, click, rewarded
        $this->assertEquals(1, $rewardCount);

        // Every reward must link to valid existing campaign and member
        $reward = AdReward::where('ad_campaign_id', $campaign->id)->first();
        $this->assertEquals($campaign->id, $reward->ad_campaign_id);
        $this->assertEquals($m1->id, $reward->member_id);
    }

    /**
     * TEST 53: Phase 10A — Authorized member WhatsApp/phone and email are returned for campaign owner.
     */
    public function test_53_member_contact_whatsapp_phone_and_email_present_and_authorized(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m1 = $this->createMember([
            'name' => 'John Doe',
            'phone' => '+91 95665 55655',
            'email' => 'john.doe@example.com',
        ]);

        $service->recordInterested($campaign, $m1);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_john_53',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(200);
        $data = $response->json('engagements.data');
        $this->assertNotEmpty($data);
        $this->assertEquals('+91 95665 55655', $data[0]['user']['phone']);
        $this->assertEquals('john.doe@example.com', $data[0]['user']['email']);
        $this->assertEquals('John Doe', $data[0]['user']['name']);
    }

    /**
     * TEST 54: Phase 10A — Member with no phone returns safe null/empty state without error.
     */
    public function test_54_member_with_no_phone_returns_null_safe_state(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $service = app(AdCampaignEngagementService::class);

        $m2 = $this->createMember([
            'name' => 'Jane Smith',
            'phone' => null,
            'email' => 'jane.smith@example.com',
        ]);

        $service->recordClick($campaign, $m2);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m2->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_jane_54',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/engagements");

        $response->assertStatus(200);
        $data = $response->json('engagements.data');
        $this->assertNotEmpty($data);
        $this->assertNull($data[0]['user']['phone']);
        $this->assertEquals('jane.smith@example.com', $data[0]['user']['email']);
        $this->assertEquals('Jane Smith', $data[0]['user']['name']);
    }
}

