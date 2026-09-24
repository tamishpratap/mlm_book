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

class AdCampaignPeopleEngagedPhase5Test extends TestCase
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
            'phone' => '+1555' . sprintf('%07d', $unique),
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
     * TEST 1: Interested only -> not displayed in People Engaged.
     */
    public function test_01_interested_only_is_not_displayed(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $visitor = $this->createMember();

        $service = app(AdCampaignEngagementService::class);
        $service->recordInterested($campaign, $visitor);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('engagements.total'));
        $this->assertCount(0, $response->json('engagements.data'));
    }

    /**
     * TEST 2: Interested + landing visit but reward not successful -> not displayed.
     */
    public function test_02_interested_plus_landing_visit_without_successful_reward_is_not_displayed(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $visitor = $this->createMember();

        $service = app(AdCampaignEngagementService::class);
        $service->recordInterested($campaign, $visitor);
        $service->recordLandingVisit($campaign, $visitor, 'evt_unrewarded_123');

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('engagements.total'));
        $this->assertCount(0, $response->json('engagements.data'));
    }

    /**
     * TEST 3: Successful reward -> displayed.
     */
    public function test_03_successful_reward_is_displayed(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $rewardedUser = $this->createMember(['name' => 'Jane Rewarded', 'user_id' => 'janerewarded']);

        $service = app(AdCampaignEngagementService::class);
        $service->recordInterested($campaign, $rewardedUser);
        $service->recordLandingVisit($campaign, $rewardedUser, 'evt_rev_001');

        $reward = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $rewardedUser->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_rev_001',
            'status' => AdReward::STATUS_CREDITED,
        ]);
        $service->recordRewarded($campaign, $rewardedUser, $reward);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        $this->assertCount(1, $response->json('engagements.data'));

        $item = $response->json('engagements.data.0');
        $this->assertEquals('Jane Rewarded', $item['user']['name']);
        $this->assertEquals('Rewarded Visit', $item['action_label']);
        $this->assertEquals('credited', $item['status']);
    }

    /**
     * TEST 4: Successful reward repeated request -> still one row.
     */
    public function test_04_successful_reward_repeated_request_still_one_row(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $rewardedUser = $this->createMember();

        $service = app(AdCampaignEngagementService::class);
        $service->recordInterested($campaign, $rewardedUser);
        $service->recordLandingVisit($campaign, $rewardedUser, 'evt_002');
        $reward = AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $rewardedUser->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_002',
            'status' => AdReward::STATUS_CREDITED,
        ]);
        $service->recordRewarded($campaign, $rewardedUser, $reward);

        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($owner, 'member')
                ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

            $response->assertStatus(200);
            $this->assertEquals(1, $response->json('engagements.total'));
            $this->assertCount(1, $response->json('engagements.data'));
        }
    }

    /**
     * TEST 5: Same member rewarded for same campaign -> one row.
     */
    public function test_05_same_member_rewarded_for_same_campaign_yields_maximum_one_row(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $rewardedUser = $this->createMember();

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $rewardedUser->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_003',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        $this->assertCount(1, $response->json('engagements.data'));
    }

    /**
     * TEST 6: Same member rewarded for another campaign -> one row on each relevant campaign page.
     */
    public function test_06_same_member_rewarded_for_another_campaign_appears_once_on_each_relevant_campaign(): void
    {
        $owner = $this->createMember();
        $campaignA = $this->createCampaign($owner, ['campaign_name' => 'Campaign A']);
        $campaignB = $this->createCampaign($owner, ['campaign_name' => 'Campaign B']);
        $sharedMember = $this->createMember(['name' => 'Active Member']);

        AdReward::create([
            'ad_campaign_id' => $campaignA->id,
            'member_id' => $sharedMember->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_a',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        AdReward::create([
            'ad_campaign_id' => $campaignB->id,
            'member_id' => $sharedMember->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_b',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $resA = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaignA->businessPage->slug}/ad-campaigns/{$campaignA->id}/engagements");
        $resA->assertStatus(200);
        $this->assertEquals(1, $resA->json('engagements.total'));
        $this->assertEquals($sharedMember->id, $resA->json('engagements.data.0.user.id'));

        $resB = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaignB->businessPage->slug}/ad-campaigns/{$campaignB->id}/engagements");
        $resB->assertStatus(200);
        $this->assertEquals(1, $resB->json('engagements.total'));
        $this->assertEquals($sharedMember->id, $resB->json('engagements.data.0.user.id'));
    }

    /**
     * TEST 7: Different members rewarded for same campaign -> one row per member.
     */
    public function test_07_different_members_rewarded_for_same_campaign_appear_one_row_per_member(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $m1 = $this->createMember(['name' => 'Member One']);
        $m2 = $this->createMember(['name' => 'Member Two']);
        $m3 = $this->createMember(['name' => 'Member Three']);

        foreach ([$m1, $m2, $m3] as $i => $m) {
            AdReward::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $m->id,
                'reward_amount_usd' => 0.0250,
                'qualifying_event_id' => "evt_{$i}",
                'status' => AdReward::STATUS_CREDITED,
            ]);
        }

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('engagements.total'));
        $this->assertCount(3, $response->json('engagements.data'));

        $returnedIds = collect($response->json('engagements.data'))->pluck('user.id')->toArray();
        $this->assertEqualsCanonicalizing([$m1->id, $m2->id, $m3->id], $returnedIds);
    }

    /**
     * TEST 8: Historical engagement records contain duplicates -> People Engaged still shows one row per member.
     */
    public function test_08_historical_engagement_records_contain_duplicates_people_engaged_still_shows_one_row(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $member = $this->createMember();

        $service = app(AdCampaignEngagementService::class);
        $service->recordInterested($campaign, $member);
        $service->recordInterested($campaign, $member);
        $service->recordClick($campaign, $member, 'int_dup1');
        $service->recordClick($campaign, $member, 'int_dup2');
        $service->recordLandingVisit($campaign, $member, 'evt_dup1');
        $service->recordLandingVisit($campaign, $member, 'evt_dup2');

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_dup1',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        $this->assertCount(1, $response->json('engagements.data'));
    }

    /**
     * TEST 9: Campaign top-up -> existing rewarded members remain exactly once.
     */
    public function test_09_campaign_topup_preserves_rewarded_members_exactly_once(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 5.00, 'remaining_amount' => 0.10]);
        $member = $this->createMember();

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $member->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_topup',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $campaign->update([
            'budget' => 25.00,
            'remaining_amount' => 20.10,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        $this->assertCount(1, $response->json('engagements.data'));
    }

    /**
     * TEST 10: Campaign A data must not appear under Campaign B.
     */
    public function test_10_campaign_a_data_does_not_appear_under_campaign_b(): void
    {
        $owner = $this->createMember();
        $campaignA = $this->createCampaign($owner, ['campaign_name' => 'Campaign Alpha']);
        $campaignB = $this->createCampaign($owner, ['campaign_name' => 'Campaign Beta']);

        $userA = $this->createMember(['name' => 'User Alpha']);
        $userB = $this->createMember(['name' => 'User Beta']);

        AdReward::create([
            'ad_campaign_id' => $campaignA->id,
            'member_id' => $userA->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_alpha',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        AdReward::create([
            'ad_campaign_id' => $campaignB->id,
            'member_id' => $userB->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'evt_beta',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $resA = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaignA->businessPage->slug}/ad-campaigns/{$campaignA->id}/engagements");
        $resA->assertStatus(200);
        $this->assertEquals(1, $resA->json('engagements.total'));
        $this->assertEquals($userA->id, $resA->json('engagements.data.0.user.id'));
        $this->assertNotEquals($userB->id, $resA->json('engagements.data.0.user.id'));

        $resB = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaignB->businessPage->slug}/ad-campaigns/{$campaignB->id}/engagements");
        $resB->assertStatus(200);
        $this->assertEquals(1, $resB->json('engagements.total'));
        $this->assertEquals($userB->id, $resB->json('engagements.data.0.user.id'));
        $this->assertNotEquals($userA->id, $resB->json('engagements.data.0.user.id'));
    }

    /**
     * TEST 11: Unauthorized campaign_id -> access denied.
     */
    public function test_11_unauthorized_campaign_access_is_denied(): void
    {
        $owner = $this->createMember();
        $stranger = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($stranger, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(403);
    }

    /**
     * TEST 12: No successful rewards -> proper empty state.
     */
    public function test_12_no_successful_rewards_yields_proper_empty_state(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('engagements.total'));
        $this->assertCount(0, $response->json('engagements.data'));
    }

    /**
     * TEST 13: Result count matches unique successfully rewarded members.
     */
    public function test_13_result_count_matches_unique_successfully_rewarded_members(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);

        for ($i = 1; $i <= 5; $i++) {
            $m = $this->createMember();
            AdReward::create([
                'ad_campaign_id' => $campaign->id,
                'member_id' => $m->id,
                'reward_amount_usd' => 0.0250,
                'qualifying_event_id' => "evt_count_{$i}",
                'status' => AdReward::STATUS_CREDITED,
            ]);
        }

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('engagements.total'));
        $this->assertEquals(5, $response->json('engagements.filtered_unique_members'));
    }

    /**
     * TEST 14: Backend API itself returns only qualified/rewarded members.
     */
    public function test_14_backend_api_itself_returns_only_qualified_rewarded_members(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $mA = $this->createMember(['name' => 'Interested Member']);
        app(AdCampaignEngagementService::class)->recordInterested($campaign, $mA);

        $mB = $this->createMember(['name' => 'Click Member']);
        app(AdCampaignEngagementService::class)->recordClick($campaign, $mB, 'click_b');

        $mC = $this->createMember(['name' => 'Visit Member']);
        app(AdCampaignEngagementService::class)->recordLandingVisit($campaign, $mC, 'visit_c');

        $mD = $this->createMember(['name' => 'Rewarded Member']);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $mD->id,
            'reward_amount_usd' => 0.0250,
            'qualifying_event_id' => 'reward_d',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('engagements.total'));
        $this->assertEquals($mD->id, $response->json('engagements.data.0.user.id'));
    }

    /**
     * TEST 15: Frontend cannot manipulate the API to expose unrewarded members.
     */
    public function test_15_frontend_cannot_manipulate_the_api_to_expose_unrewarded_members(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);

        $unrewarded1 = $this->createMember(['name' => 'Sneaky Interested']);
        app(AdCampaignEngagementService::class)->recordInterested($campaign, $unrewarded1);

        $unrewarded2 = $this->createMember(['name' => 'Sneaky Click']);
        app(AdCampaignEngagementService::class)->recordClick($campaign, $unrewarded2, 'click_sneak');

        $tamperedQueries = [
            '?action=interested',
            '?action=clicked',
            '?action=all',
            '?reward_status=not_rewarded',
            '?reward_status=all',
            '?action=all&reward_status=all',
        ];

        foreach ($tamperedQueries as $qs) {
            $response = $this->actingAs($owner, 'member')
                ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements{$qs}");

            $response->assertStatus(200);
            $this->assertEquals(0, $response->json('engagements.total'), "Query {$qs} exposed unrewarded users!");
            $this->assertCount(0, $response->json('engagements.data'), "Query {$qs} exposed unrewarded users!");
        }
    }

    /**
     * TEST 16: Response does not contain tier_label and queries never reference tier_label.
     */
    public function test_16_response_does_not_contain_or_query_tier_label(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner);
        $m1 = $this->createMember(['name' => 'Rewarded User']);

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0500,
            'qualifying_event_id' => 'evt_test_16',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        \Illuminate\Support\Facades\DB::listen(function ($query) {
            $this->assertStringNotContainsString('tier_label', $query->sql, "SQL query contained obsolete tier_label!");
        });

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $data = $response->json('engagements.data');
        $this->assertCount(1, $data);

        // Verify tier_label is absent from item contract
        $this->assertArrayNotHasKey('tier_label', $data[0]);
    }

    /**
     * TEST 17: Summary accurately reports unique rewarded count and total amount without tier grouping.
     */
    public function test_17_summary_accurately_reports_rewarded_counts_and_paid_usd(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 10.00, 'remaining_amount' => 9.90]);

        $m1 = $this->createMember(['name' => 'User 1']);
        $m2 = $this->createMember(['name' => 'User 2']);

        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m1->id,
            'reward_amount_usd' => 0.0400,
            'qualifying_event_id' => 'evt_sum_1',
            'status' => AdReward::STATUS_CREDITED,
        ]);
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $m2->id,
            'reward_amount_usd' => 0.0600,
            'qualifying_event_id' => 'evt_sum_2',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);
        $summary = $response->json('summary');
        $this->assertEquals(2, $summary['total_rewards_count']);
        $this->assertEquals(0.1000, $summary['total_rewards_paid']);
        $this->assertEmpty($summary['tier_breakdown']);
    }

    /**
     * TEST 18: Section 18 specification example scenario (Member A, B, C, D).
     */
    public function test_18_spec_example_scenario_members_a_b_c_d(): void
    {
        $owner = $this->createMember();
        $campaign = $this->createCampaign($owner, ['budget' => 20.00, 'remaining_amount' => 19.90]);
        $service = app(AdCampaignEngagementService::class);

        $memberA = $this->createMember(['name' => 'Member A']);
        $memberB = $this->createMember(['name' => 'Member B']);
        $memberC = $this->createMember(['name' => 'Member C']);
        $memberD = $this->createMember(['name' => 'Member D']);

        // Member A: Interested -> Landing Visit -> Reward SUCCESS + Repeated Landing Visit
        $service->recordInterested($campaign, $memberA);
        $service->recordLandingVisit($campaign, $memberA, 'evt_a_1', 'landing_url');
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $memberA->id,
            'reward_amount_usd' => 0.0500,
            'qualifying_event_id' => 'evt_a_1',
            'status' => AdReward::STATUS_CREDITED,
        ]);
        $service->recordLandingVisit($campaign, $memberA, 'evt_a_2', 'landing_url');

        // Member B: Interested only
        $service->recordInterested($campaign, $memberB);

        // Member C: Interested -> Landing Visit -> Reward FAILED (rejected/pending)
        $service->recordInterested($campaign, $memberC);
        $service->recordLandingVisit($campaign, $memberC, 'evt_c_1', 'landing_url');
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $memberC->id,
            'reward_amount_usd' => 0.0500,
            'qualifying_event_id' => 'evt_c_1',
            'status' => AdReward::STATUS_REJECTED,
        ]);

        // Member D: Interested -> Landing Visit -> Reward SUCCESS
        $service->recordInterested($campaign, $memberD);
        $service->recordLandingVisit($campaign, $memberD, 'evt_d_1', 'landing_url');
        AdReward::create([
            'ad_campaign_id' => $campaign->id,
            'member_id' => $memberD->id,
            'reward_amount_usd' => 0.0500,
            'qualifying_event_id' => 'evt_d_1',
            'status' => AdReward::STATUS_CREDITED,
        ]);

        $response = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$campaign->businessPage->slug}/ad-campaigns/{$campaign->id}/engagements");

        $response->assertStatus(200);

        // Assert count equals 2 (Member A and Member D only)
        $this->assertEquals(2, $response->json('engagements.total'));
        $this->assertCount(2, $response->json('engagements.data'));

        $memberIds = collect($response->json('engagements.data'))->pluck('user.id')->toArray();
        $this->assertContains($memberA->id, $memberIds);
        $this->assertContains($memberD->id, $memberIds);
        $this->assertNotContains($memberB->id, $memberIds);
        $this->assertNotContains($memberC->id, $memberIds);

        // Total reward amount = SUM of Member A + Member D successful rewards (0.05 + 0.05 = 0.10)
        $this->assertEquals(0.10, (float) $response->json('summary.total_rewards_paid'));
        $this->assertEquals(2, $response->json('summary.total_rewards_count'));
    }
}
