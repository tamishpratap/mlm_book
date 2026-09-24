<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdDeliveryService;
use App\Services\EventRewardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventAudienceGatingAndSocialCardTest extends TestCase
{
    use RefreshDatabase;

    protected Member $verifiedMember;
    protected Member $unverifiedMember;
    protected Member $organizer;
    protected Event $paidEvent;
    protected Event $freeEvent;
    protected AdCampaign $paidCampaign;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed authoritative Event reward rules
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->delete();
        AdRewardRule::clearCache();

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();

        // 2. Create organizer and audience members
        $this->organizer = $this->createMember(['name' => 'Host Organizer', 'mobile_verified_at' => now()]);
        $this->verifiedMember = $this->createMember(['name' => 'Verified Member', 'mobile_verified_at' => now()]);
        $this->unverifiedMember = $this->createMember(['name' => 'Unverified Member', 'mobile_verified_at' => null]);

        // 3. Create Paid Event with active campaign
        $this->paidEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Tech Summit 2026',
            'slug' => 'tech-summit-' . uniqid(),
            'description' => 'Annual global tech summit on distributed systems.',
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00:00',
            'end_date' => now()->addDays(6)->toDateString(),
            'end_time' => '18:00:00',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $this->paidCampaign = AdCampaign::create([
            'campaign_id' => 'CAMP-EVT-' . uniqid(),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $this->paidEvent->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Tech Summit 2026 Promotion',
            'budget' => 50.00,
            'total_funded' => 50.00,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ]);

        // 4. Create Free Event
        $this->freeEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Community Coffee Meetup',
            'slug' => 'coffee-meetup-' . uniqid(),
            'description' => 'Casual meetup for developers.',
            'category' => 'Meetup',
            'event_type' => 'offline',
            'location_city' => 'New York',
            'start_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'status' => 'published',
            'privacy' => 'public',
        ]);
    }

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 1;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "User {$unique}",
            'user_id' => "USR_{$unique}_" . \Illuminate\Support\Str::random(6),
            'email' => "user_{$unique}_" . \Illuminate\Support\Str::random(6) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    // =========================================================================
    // SECTION 39: REQUIRED SECURITY TEST MATRIX (TEST A - TEST H)
    // =========================================================================

    /**
     * TEST A — VERIFIED MEMBER: Verified member -> Social Space -> Paid Event campaign appears -> card contains "Earn up to $X"
     */
    public function test_security_matrix_test_a_verified_member_receives_paid_event_card_with_earn_up_to(): void
    {
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $this->assertNotEmpty($posts, 'Verified member feed should receive feed posts');
        $eventCampaignItem = collect($posts)->first(function ($p) {
            return isset($p['type']) && $p['type'] === 'sponsored_event';
        });

        $this->assertNotNull($eventCampaignItem, 'Verified member feed must contain a sponsored_event item');
        $this->assertEquals($this->paidEvent->id, $eventCampaignItem['event']['id']);
        $this->assertEquals(0.05, $eventCampaignItem['ad_campaign']['earn_up_to_usd']);
        $this->assertEquals('$0.05', $eventCampaignItem['ad_campaign']['earn_up_to_formatted']);
    }

    /**
     * TEST B — UNVERIFIED MEMBER: Unverified member -> Social Space -> Paid Event campaign DOES appear for viewing
     */
    public function test_security_matrix_test_b_unverified_member_receives_paid_event_campaign(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $eventCampaignItem = collect($posts)->first(function ($p) {
            return isset($p['type']) && $p['type'] === 'sponsored_event';
        });

        $this->assertNotNull($eventCampaignItem, 'Unverified member feed MUST contain sponsored_event item for viewing');
        $this->assertEquals($this->paidEvent->id, $eventCampaignItem['event']['id']);
    }

    /**
     * TEST C — DIRECT API: Unverified member -> direct Paid Event reward-preview endpoint -> 403 Forbidden
     */
    public function test_security_matrix_test_c_unverified_member_blocked_on_direct_reward_preview_api(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/events/{$this->paidEvent->id}/reward-preview");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status' => 'unverified_member',
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    /**
     * TEST D — DIRECT CAMPAIGN ID: Unverified member -> known Paid Event campaign ID -> 403 Forbidden
     */
    public function test_security_matrix_test_d_unverified_member_cannot_view_campaign_by_id(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/events/{$this->paidEvent->id}/campaign");

        $response->assertStatus(403);
    }

    /**
     * TEST E — SEARCH: Unverified member -> search for Paid Event -> earning campaign status not exposed
     */
    public function test_security_matrix_test_e_unverified_member_search_does_not_expose_paid_campaign_data(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/events?search=Tech Summit');

        $response->assertStatus(200);
        $events = $response->json('events.data') ?? $response->json('events');

        $this->assertNotEmpty($events);
        // The event list endpoint returns public events without campaign financials
        foreach ($events as $evt) {
            $this->assertArrayNotHasKey('campaign', $evt, 'Event list/search must not expose campaign object');
        }
    }

    /**
     * TEST F — PAGINATION: Unverified member receives Paid Event in feed for viewing
     */
    public function test_security_matrix_test_f_unverified_member_pagination_serves_event_campaigns(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/socials?page=1");

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $eventAd = collect($posts)->first(function ($p) {
            return isset($p['type']) && $p['type'] === 'sponsored_event';
        });

        $this->assertNotNull($eventAd, "Unverified member must receive sponsored_event in feed");
    }

    /**
     * TEST G — DEEP LINK: Unverified member -> direct Event URL -> can view event and paid campaign status
     */
    public function test_security_matrix_test_g_unverified_member_deep_link_exposes_paid_state(): void
    {
        $response = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/events/{$this->paidEvent->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_paid' => true,
            ]);
        $this->assertNotNull($response->json('campaign'));
    }

    /**
     * TEST H — VERIFIED MEMBER: Verified member -> same campaign -> campaign available subject to normal campaign state
     */
    public function test_security_matrix_test_h_verified_member_deep_link_exposes_paid_state(): void
    {
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->getJson("/api/member/events/{$this->paidEvent->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_paid' => true,
            ]);
        $this->assertNotNull($response->json('campaign'));
    }

    // =========================================================================
    // SECTION 40: REQUIRED CACHE LEAK TEST
    // =========================================================================

    /**
     * REQUIRED CACHE LEAK TEST:
     * 1. Login as verified member -> request feed -> confirm Paid Event is available.
     * 2. Login as unverified member -> request same feed -> confirm Paid Event is also available for viewing.
     */
    public function test_required_cache_leak_isolation_between_verified_and_unverified_members(): void
    {
        // 1. Verified member requests feed
        $resVerified = $this->actingAs($this->verifiedMember, 'member')
            ->getJson('/api/member/socials');
        $resVerified->assertStatus(200);

        $verifiedPosts = $resVerified->json('posts');
        $hasEventAdVerified = collect($verifiedPosts)->contains(fn ($p) => ($p['type'] ?? '') === 'sponsored_event');
        $this->assertTrue($hasEventAdVerified, 'Verified member must receive sponsored event');

        // 2. Unverified member requests feed
        $resUnverified = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/socials');
        $resUnverified->assertStatus(200);

        $unverifiedPosts = $resUnverified->json('posts');
        $hasEventAdUnverified = collect($unverifiedPosts)->contains(fn ($p) => ($p['type'] ?? '') === 'sponsored_event');
        $this->assertTrue($hasEventAdUnverified, 'Unverified member must also receive sponsored event for viewing');
    }

    // =========================================================================
    // SECTION 41: REQUIRED CARD TEST — DYNAMIC "EARN UP TO $X"
    // =========================================================================

    /**
     * REQUIRED CARD TEST:
     * Card displays dynamic "EARN UP TO $X" where X equals the maximum valid active Event reward.
     * When Admin updates the Event reward rules, X dynamically reflects the change.
     */
    public function test_required_card_test_dynamic_earn_up_to_reflects_maximum_event_reward(): void
    {
        // Initially, max configured reward is 0.0500
        $this->assertEquals(0.05, AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT));

        $deliveryService = app(AdDeliveryService::class);
        $eventPost = $deliveryService->formatSponsoredEventPost($this->paidCampaign, $this->verifiedMember);

        $this->assertNotNull($eventPost);
        $this->assertEquals(0.05, $eventPost->ad_campaign['earn_up_to_usd']);
        $this->assertEquals('$0.05', $eventPost->ad_campaign['earn_up_to_formatted']);

        // Admin updates the maximum tier reward to 0.0400
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 15)
            ->update(['reward_amount' => 0.0400]);
        AdRewardRule::clearCache();

        $this->assertEquals(0.04, AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT));

        $updatedEventPost = $deliveryService->formatSponsoredEventPost($this->paidCampaign, $this->verifiedMember);
        $this->assertEquals(0.04, $updatedEventPost->ad_campaign['earn_up_to_usd']);
        $this->assertEquals('$0.04', $updatedEventPost->ad_campaign['earn_up_to_formatted']);

        // Admin disables the 15+ tier -> max becomes 0.0350 (6-14 tier)
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 15)
            ->update(['is_active' => false]);
        AdRewardRule::clearCache();

        $this->assertEquals(0.035, AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT));

        $thirdEventPost = $deliveryService->formatSponsoredEventPost($this->paidCampaign, $this->verifiedMember);
        $this->assertEquals(0.035, $thirdEventPost->ad_campaign['earn_up_to_usd']);
        $this->assertEquals('$0.035', $thirdEventPost->ad_campaign['earn_up_to_formatted']);
    }

    // =========================================================================
    // SECTION 62: ZERO REWARD SIDE EFFECTS
    // =========================================================================

    /**
     * ZERO SIDE EFFECTS:
     * Rendering, viewing, and resolving cards has strictly ZERO side effects:
     * - No wallet ledger debits/credits
     * - No campaign budget deductions
     * - No Interested records created
     * - No participant records created
     * - No member verification or referral count mutations
     */
    public function test_card_rendering_and_api_request_causes_zero_side_effects(): void
    {
        $initialViewerReward = (float) $this->verifiedMember->fresh()->reward_balance;
        $initialOrganizerAd = (float) $this->organizer->fresh()->ad_balance;
        $initialRemaining = (float) $this->paidCampaign->fresh()->remaining_amount;
        $initialSpent = (float) $this->paidCampaign->fresh()->spent_amount;
        $initialCampaignStatus = $this->paidCampaign->fresh()->status;

        // 1. Fetch social feed (delivers sponsored event card)
        $response = $this->actingAs($this->verifiedMember, 'member')
            ->getJson('/api/member/socials');
        $response->assertStatus(200);

        // 2. Fetch sponsored feed endpoint
        $feedRes = $this->actingAs($this->verifiedMember, 'member')
            ->getJson('/api/member/events/sponsored-feed');
        $feedRes->assertStatus(200);

        // 3. Verify side-effect invariance
        $this->assertEquals($initialViewerReward, (float) $this->verifiedMember->fresh()->reward_balance, 'Viewer reward balance must remain unchanged');
        $this->assertEquals($initialOrganizerAd, (float) $this->organizer->fresh()->ad_balance, 'Organizer ad balance must remain unchanged');
        $this->assertEquals($initialRemaining, (float) $this->paidCampaign->fresh()->remaining_amount, 'Campaign remaining budget must remain unchanged');
        $this->assertEquals($initialSpent, (float) $this->paidCampaign->fresh()->spent_amount, 'Campaign spent budget must remain unchanged');
        $this->assertEquals($initialCampaignStatus, $this->paidCampaign->fresh()->status, 'Campaign status must remain unchanged');

        // Confirm 0 interested records exist for this member on this event
        $this->assertEquals(0, DB::table('event_responses')->where('member_id', $this->verifiedMember->id)->where('event_id', $this->paidEvent->id)->count());
    }

    // =========================================================================
    // SECTION 78: ADDITIONAL EDGE CASES & REGRESSION CHECKS
    // =========================================================================

    /**
     * Free events remain fully accessible to both unverified and verified members.
     */
    public function test_free_events_remain_accessible_to_all_members(): void
    {
        // Verified member can view free event
        $resV = $this->actingAs($this->verifiedMember, 'member')
            ->getJson("/api/member/events/{$this->freeEvent->id}");
        $resV->assertStatus(200)->assertJson(['success' => true, 'is_paid' => false]);

        // Unverified member can view free event
        $resU = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson("/api/member/events/{$this->freeEvent->id}");
        $resU->assertStatus(200)->assertJson(['success' => true, 'is_paid' => false]);
    }

    /**
     * Event creator/organizer retains full access to their own campaign management.
     */
    public function test_event_organizer_retains_full_access_to_own_campaign(): void
    {
        $response = $this->actingAs($this->organizer, 'member')
            ->getJson("/api/member/events/{$this->paidEvent->id}/campaign");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertEquals($this->paidCampaign->id, $response->json('campaign.id'));
    }

    /**
     * Unauthenticated requests are rejected safely.
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson("/api/member/events/{$this->paidEvent->id}/reward-preview");
        $response->assertStatus(401);

        $feedResponse = $this->getJson('/api/member/events/sponsored-feed');
        $feedResponse->assertStatus(401);
    }

    /**
     * Sponsored event feed API endpoint allows viewing for both verified and unverified members.
     */
    public function test_sponsored_event_feed_api_endpoint_is_accessible(): void
    {
        // Unverified member receives 200 with campaigns
        $resU = $this->actingAs($this->unverifiedMember, 'member')
            ->getJson('/api/member/events/sponsored-feed');
        $resU->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertNotEmpty($resU->json('campaigns'));

        // Verified member receives 200 with campaigns
        $resV = $this->actingAs($this->verifiedMember, 'member')
            ->getJson('/api/member/events/sponsored-feed');
        $resV->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertNotEmpty($resV->json('campaigns'));
    }

    /**
     * Exhausted or inactive event campaigns are not delivered to feed.
     */
    public function test_inactive_or_exhausted_event_campaigns_are_not_delivered(): void
    {
        // Stop the campaign
        $this->paidCampaign->update(['status' => AdCampaign::STATUS_STOPPED]);

        $deliveryService = app(AdDeliveryService::class);
        $eligible = $deliveryService->getEligibleEventCampaigns(5, $this->verifiedMember);

        $this->assertTrue($eligible->isEmpty(), 'Stopped campaign must not be eligible for feed delivery');
    }

    /**
     * EventRewardResolver fails closed when member is unverified.
     */
    public function test_event_reward_resolver_fails_closed_for_unverified_member(): void
    {
        $resolver = app(EventRewardResolver::class);
        $result = $resolver->resolveForEventMember($this->unverifiedMember, $this->paidEvent);

        $this->assertFalse($result['success']);
        $this->assertEquals('unverified_member', $result['status']);
        $this->assertTrue($result['verified_required']);
        $this->assertEquals(0.00, $result['reward_amount_usd']);
    }
/**
     * Business Ads reward values are NOT accidentally used for Event campaigns.
     */
    public function test_business_ads_reward_values_are_not_accidentally_used(): void
    {
        // Configure Business Ad rule with distinct reward amount ($0.0900)
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_BUSINESS_AD)->delete();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0900,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();

        $deliveryService = app(AdDeliveryService::class);
        $eventPost = $deliveryService->formatSponsoredEventPost($this->paidCampaign, $this->verifiedMember);

        // Event post MUST use Event max reward ($0.05), NOT Business Ad reward ($0.09)
        $this->assertEquals(0.05, $eventPost->ad_campaign['earn_up_to_usd']);
        $this->assertEquals('$0.05', $eventPost->ad_campaign['earn_up_to_formatted']);
        $this->assertNotEquals(0.09, $eventPost->ad_campaign['earn_up_to_usd']);
    }

    /**
     * Event campaign type is correctly isolated from business ads.
     */
    public function test_event_campaign_type_is_correctly_isolated_from_business_ads(): void
    {
        $this->assertEquals(AdCampaign::TYPE_EVENT, $this->paidCampaign->campaign_type);

        $businessAds = app(AdDeliveryService::class)->getEligibleCampaigns(10, $this->verifiedMember);
        // Paid Event campaign must not appear in business ad query
        $this->assertFalse($businessAds->contains('id', $this->paidCampaign->id));

        $eventAds = app(AdDeliveryService::class)->getEligibleEventCampaigns(10, $this->verifiedMember);
        // Paid Event campaign must appear in event ad query
        $this->assertTrue($eventAds->contains('id', $this->paidCampaign->id));
    }

    /**
     * Wrong creator or campaign context mismatch is rejected safely.
     */
    public function test_wrong_campaign_event_context_mismatch_is_rejected(): void
    {
        // Another event
        $anotherEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Another Unrelated Event',
            'slug' => 'unrelated-event-' . uniqid(),
            'start_date' => now()->addDays(2)->toDateString(),
            'status' => 'published',
            'privacy' => 'public',
        ]);

        $resolver = app(EventRewardResolver::class);
        // Pass mismatched campaign ID to another event
        $result = $resolver->resolveForEventMember($this->verifiedMember, $anotherEvent, $this->paidCampaign->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('campaign_context_mismatch', $result['status']);
    }

    /**
     * Missing active event rules causes zero event campaigns to be delivered (fails closed).
     */
    public function test_missing_active_event_rules_fails_closed_in_delivery(): void
    {
        // Deactivate all Event rules
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->update(['is_active' => false]);
        AdRewardRule::clearCache();

        $this->assertNull(AdRewardRule::getMaximumActiveRewardAmount(AdRewardRule::TYPE_EVENT));
        $this->assertNull(AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT));

        $deliveryService = app(AdDeliveryService::class);
        $eligible = $deliveryService->getEligibleEventCampaigns(5, $this->verifiedMember);
        $this->assertTrue($eligible->isEmpty(), 'Feed delivery must return empty when no active rules exist');
    }
}
