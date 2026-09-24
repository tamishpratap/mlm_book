<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaidCampaignAccessAndFilterLogicTest extends TestCase
{
    use RefreshDatabase;

    protected Member $verifiedMember;
    protected Member $unverifiedMember;
    protected Member $organizer;
    protected BusinessPage $businessPage;
    protected Post $adPost;
    protected AdCampaign $adCampaign;
    protected Event $upcomingEvent;
    protected AdCampaign $eventCampaign;

    protected const PHONE_VERIFY_MSG = 'Please verify your phone number first before proceeding.';

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed authoritative Business Ad and Event reward rules
        AdRewardRule::query()->delete();
        AdRewardRule::clearCache();

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();

        // 2. Members
        $this->organizer = $this->createMember([
            'name' => 'Campaign Organizer',
            'mobile_verified_at' => now(),
        ]);

        $this->verifiedMember = $this->createMember([
            'name' => 'Verified Member',
            'mobile_verified_at' => now(),
        ]);

        $this->unverifiedMember = $this->createMember([
            'name' => 'Unverified Member',
            'mobile_verified_at' => null,
        ]);

        // 3. Business Page & Ad Campaign
        $this->businessPage = BusinessPage::create([
            'member_id' => $this->organizer->id,
            'page_name' => 'Alpha Business',
            'page_username' => 'alpha_biz_' . uniqid(),
            'slug' => 'alpha-biz-' . uniqid(),
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);

        $this->adPost = Post::create([
            'member_id' => $this->organizer->id,
            'business_page_id' => $this->businessPage->id,
            'body' => 'Promoted Business Ad Post',
            'visibility' => 'public',
            'type' => 'sponsored_ad',
        ]);

        $this->adCampaign = AdCampaign::create([
            'campaign_id' => 'AD-' . strtoupper(Str::random(10)),
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->businessPage->id,
            'member_id' => $this->organizer->id,
            'post_id' => $this->adPost->id,
            'campaign_name' => 'Alpha Ad Campaign',
            'budget' => 100.00,
            'total_funded' => 100.00,
            'remaining_amount' => 100.00,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ]);

        // 4. Upcoming Event & Event Campaign
        $this->upcomingEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Future Tech Summit 2026',
            'slug' => 'future-tech-summit-' . uniqid(),
            'description' => 'Summit for developers.',
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00:00',
            'end_date' => now()->addDays(6)->toDateString(),
            'end_time' => '18:00:00',
            'timezone' => 'UTC',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $this->eventCampaign = AdCampaign::create([
            'campaign_id' => 'EVT-' . strtoupper(Str::random(10)),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $this->upcomingEvent->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Future Tech Summit Campaign',
            'budget' => 50.00,
            'total_funded' => 50.00,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ]);
    }

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 1;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "User {$unique}",
            'user_id' => "USR_{$unique}_" . Str::random(6),
            'email' => "user_{$unique}_" . Str::random(6) . "@example.com",
            'password' => bcrypt('secret123'),
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    // =========================================================================
    // 1. VISIBILITY TESTS: UNVERIFIED MEMBERS CAN SEE ADS AND EVENTS
    // =========================================================================

    public function test_unverified_member_can_view_eligible_paid_ads_in_feed(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertOk();
        $posts = $response->json('posts');

        $sponsoredAd = collect($posts)->first(function ($p) {
            return ($p['is_sponsored'] ?? false) && ($p['type'] ?? '') !== 'sponsored_event';
        });

        $this->assertNotNull($sponsoredAd, 'Unverified members MUST be able to see eligible Paid Ads in social feed.');
        $this->assertEquals($this->adCampaign->campaign_id, $sponsoredAd['ad_campaign_id']);
    }

    public function test_unverified_member_can_view_eligible_paid_events_in_feed(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertOk();
        $posts = $response->json('posts');

        $sponsoredEvent = collect($posts)->first(function ($p) {
            return ($p['type'] ?? '') === 'sponsored_event';
        });

        $this->assertNotNull($sponsoredEvent, 'Unverified members MUST be able to see eligible Paid Events in social feed.');
        $this->assertEquals($this->upcomingEvent->id, $sponsoredEvent['event']['id']);
    }

    public function test_unverified_member_can_view_ad_campaigns_feed_endpoint(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/ad-campaigns/feed');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $campaigns = $response->json('campaigns');
        $this->assertNotEmpty($campaigns, 'Unverified member should view eligible ad campaigns.');
    }

    public function test_unverified_member_can_view_sponsored_events_feed_endpoint(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/events/sponsored-feed');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $campaigns = $response->json('campaigns');
        $this->assertNotEmpty($campaigns, 'Unverified member should view eligible event campaigns.');
    }

    public function test_unverified_member_can_view_events_in_discovery_listings(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/events?tab=all');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $events = $response->json('events.data') ?? $response->json('events');
        $this->assertNotEmpty($events);

        $found = collect($events)->firstWhere('id', $this->upcomingEvent->id);
        $this->assertNotNull($found, 'Upcoming event must be present in discovery list.');
    }

    public function test_unverified_member_can_view_event_show_with_campaign_info(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/events/{$this->upcomingEvent->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_paid' => true,
            ]);

        $this->assertNotNull($response->json('campaign'));
    }

    // =========================================================================
    // 2. PROTECTED ACTIONS: UNVERIFIED MEMBERS ARE STRICTLY BLOCKED (403)
    // =========================================================================

    public function test_unverified_member_blocked_on_ad_interest(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/interest");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_ad_click(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/click", [
                'click_key' => 'clk_' . uniqid(),
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_ad_qualify_visit(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . uniqid(),
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_ad_follow_to_earn(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/follow-to-earn");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_ad_landing_reward_status(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/landing-reward-status");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_event_reward_preview(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/events/{$this->upcomingEvent->id}/reward-preview");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_event_qualify_interest(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->postJson("/api/member/events/{$this->upcomingEvent->id}/campaign/qualify-interest");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    public function test_unverified_member_blocked_on_event_respond(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->postJson("/api/member/events/{$this->upcomingEvent->id}/respond", [
                'response' => 'interested',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => self::PHONE_VERIFY_MSG,
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    // =========================================================================
    // 3. VERIFIED MEMBERS: CAN PERFORM PROTECTED ACTIONS
    // =========================================================================

    public function test_verified_member_can_click_and_qualify_ad(): void
    {
        $clickRes = $this->actingAs($this->verifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/click", [
                'click_key' => 'clk_' . uniqid(),
            ]);
        $clickRes->assertOk();

        $qualifyRes = $this->actingAs($this->verifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$this->adCampaign->campaign_id}/qualify-visit", [
                'qualifying_event_id' => 'evt_' . uniqid(),
                'landing_page_url' => 'https://example.com/alpha',
            ]);
        $qualifyRes->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
                'reward_amount_usd' => 0.05,
            ]);
    }

    public function test_verified_member_can_qualify_event_interest(): void
    {
        $qualifyRes = $this->actingAs($this->verifiedMember, 'member')
            ->postJson("/api/member/events/{$this->upcomingEvent->id}/campaign/qualify-interest");

        $qualifyRes->assertOk()
            ->assertJson([
                'success' => true,
                'rewarded' => true,
            ]);
    }

    public function test_verified_member_can_respond_to_event(): void
    {
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->postJson("/api/member/events/{$this->upcomingEvent->id}/respond", [
                'response' => 'going',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'response' => 'going',
            ]);
    }

    // =========================================================================
    // 4. BUDGET FILTERING: EXHAUSTED CAMPAIGNS ARE FILTERED OUT
    // =========================================================================

    public function test_ad_campaign_with_exhausted_budget_is_filtered_out(): void
    {
        // Set spent amount so remaining amount is below minimum active reward (0.05)
        $this->adCampaign->update([
            'spent_amount' => 99.99,
        ]);
        $this->adCampaign->refresh();
        $this->assertEquals(0.01, (float) $this->adCampaign->remaining_amount);

        $deliveryService = app(AdDeliveryService::class);
        $campaigns = $deliveryService->getEligibleCampaigns(10, $this->verifiedMember);

        $this->assertFalse($campaigns->contains('id', $this->adCampaign->id), 'Ad campaign with remaining < minReward must NOT be delivered.');
    }

    public function test_event_campaign_with_exhausted_budget_is_filtered_out(): void
    {
        // Set spent amount so remaining amount is below minimum event reward (0.025)
        $this->eventCampaign->update([
            'spent_amount' => 49.99,
        ]);
        $this->eventCampaign->refresh();
        $this->assertEquals(0.01, (float) $this->eventCampaign->remaining_amount);

        $deliveryService = app(AdDeliveryService::class);
        $campaigns = $deliveryService->getEligibleEventCampaigns(10, $this->verifiedMember);

        $this->assertFalse($campaigns->contains('id', $this->eventCampaign->id), 'Event campaign with remaining < minEventReward must NOT be delivered.');
    }

    // =========================================================================
    // 5. EVENT DATE FILTERING: PAST EVENTS ARE EXCLUDED, HISTORICAL RECORDS PRESERVED
    // =========================================================================

    public function test_past_events_are_filtered_out_from_delivery_and_discovery(): void
    {
        // Create an event that ended yesterday
        $pastEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Yesterday Past Expo',
            'slug' => 'past-expo-' . uniqid(),
            'description' => 'Concluded expo.',
            'category' => 'Technology',
            'event_type' => 'offline',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'end_time' => '12:00:00',
            'timezone' => 'UTC',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $pastCampaign = AdCampaign::create([
            'campaign_id' => 'EVT-PAST-' . strtoupper(Str::random(8)),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $pastEvent->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Past Expo Promotion',
            'budget' => 50.00,
            'total_funded' => 50.00,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDays(5),
            'end_at' => now()->addDays(5),
        ]);

        $this->assertTrue($pastEvent->hasPassed(), 'hasPassed() must return true for yesterday event.');

        // 1. Delivery service must filter out the past event
        $deliveryService = app(AdDeliveryService::class);
        $campaigns = $deliveryService->getEligibleEventCampaigns(10, $this->verifiedMember);
        $this->assertFalse($campaigns->contains('id', $pastCampaign->id), 'Past event campaign must NOT be delivered.');

        // 2. Discovery listings (/api/member/events?tab=all) must filter out the past event
        $resDiscovery = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/events?tab=all');
        $resDiscovery->assertOk();
        $eventList = $resDiscovery->json('events.data') ?? $resDiscovery->json('events');
        $foundPast = collect($eventList)->firstWhere('id', $pastEvent->id);
        $this->assertNull($foundPast, 'Past event must NOT appear in public discovery events listing.');

        // 3. Historical record MUST remain in the database (never deleted)
        $this->assertDatabaseHas('events', [
            'id' => $pastEvent->id,
            'title' => 'Yesterday Past Expo',
        ]);
    }

    public function test_single_day_event_with_passed_time_today_is_filtered_out(): void
    {
        // Event is today, but ended 2 hours ago
        $pastTimeEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Morning Workshop Today',
            'slug' => 'morning-workshop-' . uniqid(),
            'description' => 'Workshop that finished earlier today.',
            'category' => 'Workshop',
            'event_type' => 'online',
            'start_date' => now('UTC')->toDateString(),
            'end_date' => now('UTC')->toDateString(),
            'end_time' => now('UTC')->subHours(2)->format('H:i:s'),
            'timezone' => 'UTC',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $this->assertTrue($pastTimeEvent->hasPassed(), 'Event with end_time in the past today must return true for hasPassed().');

        $deliveryService = app(AdDeliveryService::class);
        $campaign = AdCampaign::create([
            'campaign_id' => 'EVT-TODAY-' . strtoupper(Str::random(8)),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $pastTimeEvent->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Morning Workshop Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $campaigns = $deliveryService->getEligibleEventCampaigns(10, $this->verifiedMember);
        $this->assertFalse($campaigns->contains('id', $campaign->id), 'Event whose end_time has passed today must not be delivered.');
    }
}
