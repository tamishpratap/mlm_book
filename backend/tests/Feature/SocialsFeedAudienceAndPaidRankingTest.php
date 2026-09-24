<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialsFeedAudienceAndPaidRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdRewardRule::query()->delete();
        AdRewardRule::clearCache();

        // Active reward rule for business ads ($0.025)
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        // Active reward rule for events ($0.020)
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();
    }

    protected function createMember(array $attrs = []): Member
    {
        $unique = uniqid('m_');
        return Member::create(array_merge([
            'name' => 'Member ' . $unique,
            'email' => $unique . '@example.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attrs));
    }

    protected function createBusinessAdCampaign(Member $owner, array $attrs = []): AdCampaign
    {
        $unique = uniqid('page_');
        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Biz ' . $unique,
            'page_username' => 'biz_' . $unique,
            'slug' => 'biz-' . $unique,
            'category' => 'Technology',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Promoted Business Ad Content ' . $unique,
            'visibility' => 'public',
        ]);

        $budget = $attrs['remaining_amount'] ?? $attrs['budget'] ?? 50.00;

        return AdCampaign::create(array_merge([
            'campaign_id' => 'CAMP-AD-' . uniqid(),
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Business Ad Campaign ' . $unique,
            'budget' => $budget,
            'total_funded' => $budget,
            'remaining_amount' => $budget,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ], $attrs));
    }

    protected function createPaidEventCampaign(Member $organizer, array $attrs = []): AdCampaign
    {
        $unique = uniqid('evt_');
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Paid Event ' . $unique,
            'slug' => 'event-' . $unique,
            'description' => 'Event description for ' . $unique,
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00:00',
            'end_date' => now()->addDays(4)->toDateString(),
            'end_time' => '18:00:00',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $budget = $attrs['remaining_amount'] ?? $attrs['budget'] ?? 50.00;

        return AdCampaign::create(array_merge([
            'campaign_id' => 'CAMP-EVT-' . uniqid(),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Paid Event Campaign ' . $unique,
            'budget' => $budget,
            'total_funded' => $budget,
            'remaining_amount' => $budget,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ], $attrs));
    }

    /**
     * TEST 1: Unverified member sees ZERO standard/organic posts in Socials feed.
     */
    public function test_unverified_member_sees_zero_organic_posts_in_feed(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $friend = $this->createMember(['mobile_verified_at' => now()]);

        // Create friendship
        Friendship::create([
            'member_one_id' => min($unverifiedMember->id, $friend->id),
            'member_two_id' => max($unverifiedMember->id, $friend->id),
            'requested_by_id' => $friend->id,
            'status' => 'accepted',
        ]);

        // Create organic posts
        Post::create([
            'member_id' => $friend->id,
            'body' => 'Friend organic post content',
            'visibility' => 'public',
        ]);
        Post::create([
            'member_id' => $unverifiedMember->id,
            'body' => 'Own organic post content',
            'visibility' => 'public',
        ]);

        // Create an eligible ad
        $adCampaign = $this->createBusinessAdCampaign($friend, ['remaining_amount' => 50.00]);

        $response = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/socials');
        $response->assertOk();

        $posts = collect($response->json('posts'));

        // All returned items must be sponsored/paid content, ZERO organic posts
        $this->assertNotEmpty($posts, 'Unverified member should receive eligible paid items.');
        $organicPosts = $posts->filter(fn ($p) => empty($p['is_sponsored']) && empty($p['ad_campaign']));
        $this->assertCount(0, $organicPosts, 'Unverified member must receive ZERO organic posts in the feed.');

        // Verify the sponsored ad is present
        $this->assertTrue($posts->contains(fn ($p) => ($p['ad_campaign_id'] ?? null) === $adCampaign->campaign_id));
    }

    /**
     * TEST 2: Verified member sees organic posts + sponsored content in Socials feed.
     */
    public function test_verified_member_sees_organic_posts_in_feed(): void
    {
        $verifiedMember = $this->createMember(['mobile_verified_at' => now()]);
        $friend = $this->createMember(['mobile_verified_at' => now()]);

        Friendship::create([
            'member_one_id' => min($verifiedMember->id, $friend->id),
            'member_two_id' => max($verifiedMember->id, $friend->id),
            'requested_by_id' => $friend->id,
            'status' => 'accepted',
        ]);

        Post::create([
            'member_id' => $friend->id,
            'body' => 'Friend organic post for verified member',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($verifiedMember, 'member')->getJson('/api/member/socials');
        $response->assertOk();

        $posts = collect($response->json('posts'));
        $organicPosts = $posts->filter(fn ($p) => empty($p['is_sponsored']) && empty($p['ad_campaign']));
        $this->assertGreaterThan(0, $organicPosts->count(), 'Verified member MUST see organic posts.');
    }

    /**
     * TEST 3: Both verified and unverified members see Business Page Paid Ads and Paid Events.
     */
    public function test_both_verified_and_unverified_members_see_paid_ads_and_paid_events(): void
    {
        $owner = $this->createMember();
        $adCampaign = $this->createBusinessAdCampaign($owner, ['remaining_amount' => 40.00]);
        $eventCampaign = $this->createPaidEventCampaign($owner, ['remaining_amount' => 60.00]);

        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $verifiedMember = $this->createMember(['mobile_verified_at' => now()]);

        // Unverified member checks feed
        $unverifiedRes = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/socials');
        $unverifiedRes->assertOk();
        $unverifiedPosts = collect($unverifiedRes->json('posts'));

        $this->assertTrue(
            $unverifiedPosts->contains(fn ($p) => ($p['ad_campaign_id'] ?? null) === $adCampaign->campaign_id),
            'Unverified member MUST see eligible Business Page Paid Ad.'
        );
        $this->assertTrue(
            $unverifiedPosts->contains(fn ($p) => ($p['ad_campaign_id'] ?? null) === $eventCampaign->campaign_id),
            'Unverified member MUST see eligible Paid Event.'
        );

        // Verified member checks feed
        $verifiedRes = $this->actingAs($verifiedMember, 'member')->getJson('/api/member/socials');
        $verifiedRes->assertOk();
        $verifiedPosts = collect($verifiedRes->json('posts'));

        $this->assertTrue(
            $verifiedPosts->contains(fn ($p) => ($p['ad_campaign_id'] ?? null) === $adCampaign->campaign_id),
            'Verified member MUST see eligible Business Page Paid Ad.'
        );
        $this->assertTrue(
            $verifiedPosts->contains(fn ($p) => ($p['ad_campaign_id'] ?? null) === $eventCampaign->campaign_id),
            'Verified member MUST see eligible Paid Event.'
        );
    }

    /**
     * TEST 4: Paid Ads and Paid Events compete in ONE common ranking pool sorted by budget DESC.
     * Higher remaining budget MUST rank first regardless of whether it is an Ad or Event.
     */
    public function test_paid_content_common_ranking_pool_sorts_by_budget_desc(): void
    {
        $owner = $this->createMember();

        // Ad with budget $20
        $adLow = $this->createBusinessAdCampaign($owner, [
            'campaign_name' => 'Low Budget Ad',
            'remaining_amount' => 20.00,
            'budget' => 20.00,
        ]);

        // Event with budget $100
        $eventHigh = $this->createPaidEventCampaign($owner, [
            'campaign_name' => 'High Budget Event',
            'remaining_amount' => 100.00,
            'budget' => 100.00,
        ]);

        // Ad with budget $150
        $adHighest = $this->createBusinessAdCampaign($owner, [
            'campaign_name' => 'Highest Budget Ad',
            'remaining_amount' => 150.00,
            'budget' => 150.00,
        ]);

        // Event with budget $60
        $eventMid = $this->createPaidEventCampaign($owner, [
            'campaign_name' => 'Mid Budget Event',
            'remaining_amount' => 60.00,
            'budget' => 60.00,
        ]);

        $service = app(AdDeliveryService::class);
        $ranked = $service->getRankedPaidFeedItems();

        $rankedIds = $ranked->pluck('ad_campaign_id')->all();

        $expectedOrder = [
            $adHighest->campaign_id,  // $150
            $eventHigh->campaign_id,   // $100
            $eventMid->campaign_id,    // $60
            $adLow->campaign_id,       // $20
        ];

        $this->assertEquals($expectedOrder, $rankedIds, 'Common pool must interleave Ads and Events strictly by budget DESC.');
    }

    /**
     * TEST 5: Secondary Sort is Recency DESC (created_at DESC) when budgets are equal.
     */
    public function test_secondary_sort_is_recency_desc_for_equal_budgets(): void
    {
        $owner = $this->createMember();

        // Ad created 3 days ago, budget $50
        $oldAd = $this->createBusinessAdCampaign($owner, [
            'campaign_name' => 'Old Ad',
            'remaining_amount' => 50.00,
            'budget' => 50.00,
            'created_at' => now()->subDays(3),
        ]);

        // Event created 1 day ago, budget $50
        $midEvent = $this->createPaidEventCampaign($owner, [
            'campaign_name' => 'Mid Event',
            'remaining_amount' => 50.00,
            'budget' => 50.00,
            'created_at' => now()->subDay(),
        ]);

        // Ad created 1 hour ago, budget $50
        $newAd = $this->createBusinessAdCampaign($owner, [
            'campaign_name' => 'New Ad',
            'remaining_amount' => 50.00,
            'budget' => 50.00,
            'created_at' => now()->subHour(),
        ]);

        $service = app(AdDeliveryService::class);
        $ranked = $service->getRankedPaidFeedItems();

        $rankedIds = $ranked->pluck('ad_campaign_id')->all();

        $expectedOrder = [
            $newAd->campaign_id,
            $midEvent->campaign_id,
            $oldAd->campaign_id,
        ];

        $this->assertEquals($expectedOrder, $rankedIds, 'Equal budgets must be ranked by created_at DESC.');
    }

    /**
     * TEST 6: Deterministic Tie-Breaker is ID DESC when budget and created_at are identical.
     */
    public function test_tie_breaker_is_id_desc(): void
    {
        $owner = $this->createMember();
        $fixedTime = now()->subMinutes(30);

        $first = $this->createBusinessAdCampaign($owner, [
            'remaining_amount' => 75.00,
            'created_at' => $fixedTime,
        ]);

        $second = $this->createPaidEventCampaign($owner, [
            'remaining_amount' => 75.00,
            'created_at' => $fixedTime,
        ]);

        $service = app(AdDeliveryService::class);
        $ranked = $service->getRankedPaidFeedItems();

        $rankedDbIds = $ranked->pluck('ad_campaign.id')->all();

        // Higher DB id must come first
        $expectedFirst = max($first->id, $second->id);
        $expectedSecond = min($first->id, $second->id);

        $this->assertEquals([$expectedFirst, $expectedSecond], $rankedDbIds, 'Ties must be broken deterministically by ID DESC.');
    }

    /**
     * TEST 7: Authoritative Budget is remaining_amount, NOT original total budget.
     * Campaign with high initial budget but low remaining ranks BELOW campaign with higher remaining amount.
     */
    public function test_authoritative_budget_uses_remaining_amount(): void
    {
        $owner = $this->createMember();

        // Started with $500, but spent $490, only $10 remaining
        $exhaustingCampaign = $this->createBusinessAdCampaign($owner, [
            'campaign_name' => 'Started Large Now Low',
            'budget' => 500.00,
            'total_funded' => 500.00,
            'spent_amount' => 490.00,
            'remaining_amount' => 10.00,
        ]);

        // Started with $35, spent $0, $35 remaining
        $freshCampaign = $this->createPaidEventCampaign($owner, [
            'campaign_name' => 'Smaller Initial Higher Remaining',
            'budget' => 35.00,
            'total_funded' => 35.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 35.00,
        ]);

        $service = app(AdDeliveryService::class);
        $ranked = $service->getRankedPaidFeedItems();

        $rankedIds = $ranked->pluck('ad_campaign_id')->all();

        $this->assertEquals(
            [$freshCampaign->campaign_id, $exhaustingCampaign->campaign_id],
            $rankedIds,
            'Campaign with higher remaining_amount must rank above campaign with lower remaining_amount.'
        );
    }

    /**
     * TEST 8: Exhausted campaigns or below minimum active reward threshold are excluded.
     */
    public function test_campaign_below_minimum_reward_threshold_is_excluded(): void
    {
        $owner = $this->createMember();

        // Min ad reward is $0.0250. Budget $50, spent $49.99 leaves remaining $0.0100, which is below threshold.
        $depletedAd = $this->createBusinessAdCampaign($owner, [
            'budget' => 50.00,
            'total_funded' => 50.00,
            'spent_amount' => 49.9900,
            'remaining_amount' => 0.0100,
        ]);

        // Active ad with sufficient budget
        $activeAd = $this->createBusinessAdCampaign($owner, [
            'remaining_amount' => 25.00,
            'budget' => 25.00,
        ]);

        $service = app(AdDeliveryService::class);
        $ranked = $service->getRankedPaidFeedItems();

        $rankedIds = $ranked->pluck('ad_campaign_id')->all();

        $this->assertContains($activeAd->campaign_id, $rankedIds);
        $this->assertNotContains($depletedAd->campaign_id, $rankedIds, 'Campaign with remaining_amount below minimum reward MUST be excluded.');
    }

    /**
     * TEST 9: Past events are excluded from social feed delivery while preserving DB records.
     */
    public function test_past_events_excluded_from_delivery_and_records_preserved(): void
    {
        $owner = $this->createMember();

        $pastEvent = Event::create([
            'organizer_id' => $owner->id,
            'title' => 'Past Event',
            'slug' => 'past-event-' . uniqid(),
            'description' => 'Event held last week',
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => now()->subDays(10)->toDateString(),
            'start_time' => '10:00:00',
            'end_date' => now()->subDays(9)->toDateString(),
            'end_time' => '18:00:00',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $pastCampaign = AdCampaign::create([
            'campaign_id' => 'CAMP-PAST-' . uniqid(),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $pastEvent->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Past Event Campaign',
            'budget' => 100.00,
            'total_funded' => 100.00,
            'remaining_amount' => 100.00,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $futureEventCampaign = $this->createPaidEventCampaign($owner, ['remaining_amount' => 50.00]);

        $service = app(AdDeliveryService::class);
        $ranked = $service->getRankedPaidFeedItems();

        $rankedIds = $ranked->pluck('ad_campaign_id')->all();

        $this->assertNotContains($pastCampaign->campaign_id, $rankedIds, 'Past event must be excluded from feed delivery.');
        $this->assertContains($futureEventCampaign->campaign_id, $rankedIds);

        // Verify DB record is untouched
        $this->assertDatabaseHas('events', ['id' => $pastEvent->id]);
        $this->assertDatabaseHas('ad_campaigns', ['id' => $pastCampaign->id]);
    }

    /**
     * TEST 10: Unverified member pagination across multiple pages maintains rank without duplication.
     */
    public function test_unverified_member_pagination_across_pages(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $owner = $this->createMember();

        // Create 8 campaigns with distinct remaining amounts
        $c1 = $this->createBusinessAdCampaign($owner, ['remaining_amount' => 100.00]);
        $c2 = $this->createPaidEventCampaign($owner, ['remaining_amount' => 90.00]);
        $c3 = $this->createBusinessAdCampaign($owner, ['remaining_amount' => 80.00]);
        $c4 = $this->createPaidEventCampaign($owner, ['remaining_amount' => 70.00]);
        $c5 = $this->createBusinessAdCampaign($owner, ['remaining_amount' => 60.00]);
        $c6 = $this->createPaidEventCampaign($owner, ['remaining_amount' => 50.00]);
        $c7 = $this->createBusinessAdCampaign($owner, ['remaining_amount' => 40.00]);
        $c8 = $this->createPaidEventCampaign($owner, ['remaining_amount' => 30.00]);

        // Request Page 1 with per_page=5
        $page1Res = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/socials?per_page=5&page=1');
        $page1Res->assertOk();
        $p1Items = collect($page1Res->json('posts'));
        $p1Ids = $p1Items->pluck('ad_campaign_id')->all();

        $this->assertCount(5, $p1Ids);
        $this->assertEquals([$c1->campaign_id, $c2->campaign_id, $c3->campaign_id, $c4->campaign_id, $c5->campaign_id], $p1Ids);
        $this->assertTrue($page1Res->json('has_more'));

        // Request Page 2 with per_page=5
        $page2Res = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/socials?per_page=5&page=2');
        $page2Res->assertOk();
        $p2Items = collect($page2Res->json('posts'));
        $p2Ids = $p2Items->pluck('ad_campaign_id')->all();

        $this->assertCount(3, $p2Ids);
        $this->assertEquals([$c6->campaign_id, $c7->campaign_id, $c8->campaign_id], $p2Ids);
        $this->assertFalse($page2Res->json('has_more'));

        // Ensure zero overlap between page 1 and page 2
        $intersection = array_intersect($p1Ids, $p2Ids);
        $this->assertEmpty($intersection, 'Page 1 and Page 2 must not contain duplicate items.');
    }

    /**
     * TEST 11: Viewing feed has zero side effects on balances and creates no spurious rewards.
     */
    public function test_viewing_feed_has_zero_side_effects_on_balances(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null, 'reward_balance' => 12.50, 'ad_balance' => 45.00]);
        $owner = $this->createMember(['reward_balance' => 0.00, 'ad_balance' => 100.00]);

        $this->createBusinessAdCampaign($owner, ['remaining_amount' => 50.00]);
        $this->createPaidEventCampaign($owner, ['remaining_amount' => 50.00]);

        // View feed
        $res = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/socials');
        $res->assertOk();

        // Fresh balances
        $unverifiedMember->refresh();
        $owner->refresh();

        $this->assertEquals(12.50, (float) $unverifiedMember->reward_balance);
        $this->assertEquals(45.00, (float) $unverifiedMember->ad_balance);
        $this->assertEquals(0.00, (float) $owner->reward_balance);
        $this->assertEquals(100.00, (float) $owner->ad_balance);
    }

    /**
     * TEST 12: Protected earn actions remain 403-blocked for unverified member.
     */
    public function test_unverified_member_blocked_from_protected_earn_actions(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $owner = $this->createMember();

        $adCampaign = $this->createBusinessAdCampaign($owner, ['remaining_amount' => 50.00]);

        // Try to click the ad
        $clickRes = $this->actingAs($unverifiedMember, 'member')->postJson("/api/member/ad-campaigns/{$adCampaign->campaign_id}/click", [
            'click_key' => 'clk_' . uniqid(),
            'placement' => 'social_feed',
        ]);

        $clickRes->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
            ]);
    }
}
