<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\Event;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DynamicArbitraryPaidEventsSocialsFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('campaign_platform_fee_percent', 2.50);

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
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();
    }

    protected function createMember(float $adBalance = 500.00, bool $verified = true): Member
    {
        static $seq = 1;
        $unique = $seq++;

        return Member::create([
            'name' => "Member {$unique}",
            'user_id' => "MBR_{$unique}_" . Str::random(6),
            'email' => "user_{$unique}_" . Str::random(6) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => $verified ? now() : null,
            'ad_balance' => $adBalance,
            'reward_balance' => 0.00,
        ]);
    }

    /**
     * TEST: Dynamic arbitrary event IDs appear in Socials feed when funded, while free events do not.
     */
    public function test_arbitrary_events_appear_dynamically_in_socials_feed_upon_funding(): void
    {
        $organizer = $this->createMember(500.00, true);
        $viewer = $this->createMember(0.00, true);

        // 1. Create Free Event (Arbitrary ID)
        $freeResponse = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Arbitrary Free Community Gathering',
                'category' => 'Business',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'is_paid' => 0,
            ]);
        $freeResponse->assertStatus(201);
        $freeEventId = $freeResponse->json('event.id');
        $freeEvent = Event::find($freeEventId);
        $this->assertNotNull($freeEvent);
        $this->assertNull($freeEvent->campaign);

        // 2. Create Event with Initial Budget (Arbitrary ID)
        $initBudgetResponse = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Arbitrary Paid Tech Conference',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(7)->toDateString(),
                'is_paid' => 1,
                'create_campaign' => 1,
                'budget' => 50.00,
            ]);
        $initBudgetResponse->assertStatus(201);
        $initPaidEventId = $initBudgetResponse->json('event.id');
        $initPaidEvent = Event::find($initPaidEventId);
        $this->assertNotNull($initPaidEvent);
        $this->assertNotNull($initPaidEvent->campaign);

        // Activate the campaign via add fund
        $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$initPaidEventId}/campaign/add-funds", [
                'amount' => 10.00,
            ])->assertStatus(200);

        // 3. Create Free Event, then Creator Uses Add Funds Flow later (Arbitrary ID)
        $fundedLaterResponse = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Arbitrary Later-Funded AI Summit',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(10)->toDateString(),
                'is_paid' => 0,
            ]);
        $fundedLaterResponse->assertStatus(201);
        $laterFundedEventId = $fundedLaterResponse->json('event.id');
        
        // Organizer views campaign endpoint (verifies show() unblocks available_ad_funds)
        $showResponse = $this->actingAs($organizer, 'member')
            ->getJson("/api/member/events/{$laterFundedEventId}/campaign");
        $showResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign', null)
            ->assertJsonPath('available_ad_funds', (float) $organizer->fresh()->ad_balance);

        // Organizer funds the campaign via Add Funds flow
        $addFundResponse = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$laterFundedEventId}/campaign/add-funds", [
                'amount' => 80.00,
            ]);
        $addFundResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.status', AdCampaign::STATUS_ACTIVE)
            ->assertJsonPath('campaign.remaining_amount', 80);

        $laterCampaign = AdCampaign::where('event_id', $laterFundedEventId)->first();
        $this->assertNotNull($laterCampaign);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $laterCampaign->status);
        $this->assertEquals(AdCampaign::APPROVAL_APPROVED, $laterCampaign->approval_status);

        // 4. Request Socials Feed as Viewer
        $feedResponse = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/socials');
        $feedResponse->assertStatus(200);

        $posts = collect($feedResponse->json('posts.data') ?? $feedResponse->json('posts') ?? []);
        
        // Verify Free Event is NOT in the feed as a paid sponsored event
        $freeEventInFeed = $posts->first(function ($item) use ($freeEventId) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $freeEventId || ($item['id'] ?? null) === "event_{$freeEventId}");
        });
        $this->assertNull($freeEventInFeed, 'Free event must never be delivered as PAID_EVENT');

        // Verify Initial-Budget Paid Event IS in the feed
        $initPaidInFeed = $posts->first(function ($item) use ($initPaidEventId) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $initPaidEventId || ($item['id'] ?? null) === "event_{$initPaidEventId}");
        });
        $this->assertNotNull($initPaidInFeed, 'Event with initial budget must appear in Socials feed');
        $this->assertTrue($initPaidInFeed['is_sponsored']);
        $this->assertEquals('PAID_EVENT', $initPaidInFeed['content_type']);
        $this->assertEquals('Arbitrary Paid Tech Conference', $initPaidInFeed['event']['title']);
        $this->assertEquals(0.05, (float) ($initPaidInFeed['ad_campaign']['earn_up_to_usd'] ?? 0));

        // Verify Later-Funded Paid Event IS in the feed
        $laterPaidInFeed = $posts->first(function ($item) use ($laterFundedEventId) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $laterFundedEventId || ($item['id'] ?? null) === "event_{$laterFundedEventId}");
        });
        $this->assertNotNull($laterPaidInFeed, 'Later-funded event must appear in Socials feed');
        $this->assertTrue($laterPaidInFeed['is_sponsored']);
        $this->assertEquals('PAID_EVENT', $laterPaidInFeed['content_type']);
        $this->assertEquals('Arbitrary Later-Funded AI Summit', $laterPaidInFeed['event']['title']);
        $this->assertEquals(0.05, (float) ($laterPaidInFeed['ad_campaign']['earn_up_to_usd'] ?? 0));

        // 5. Verify Budget Ranking Order (Remaining Budget DESC)
        // Later funded event has $80 remaining, initial event has $50 + $10 = $60 remaining.
        // Thus Later Funded event MUST appear before Initial Paid event.
        $laterIndex = $posts->search(function ($item) use ($laterFundedEventId) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $laterFundedEventId || ($item['id'] ?? null) === "event_{$laterFundedEventId}");
        });
        $initIndex = $posts->search(function ($item) use ($initPaidEventId) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $initPaidEventId || ($item['id'] ?? null) === "event_{$initPaidEventId}");
        });

        $this->assertNotFalse($laterIndex);
        $this->assertNotFalse($initIndex);
        $this->assertLessThan($initIndex, $laterIndex, 'Higher budget event must rank ahead of lower budget event');
    }

    /**
     * TEST: Exhausted campaigns and past events dynamically fall off Socials feed.
     */
    public function test_exhausted_or_past_events_dynamically_fall_off_socials_feed(): void
    {
        $organizer = $this->createMember(500.00, true);
        $viewer = $this->createMember(0.00, true);

        // 1. Create a paid event that is in the past
        $pastEvent = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Past Event Test',
            'slug' => 'past-event-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->subDays(2)->toDateString(),
            'status' => 'published',
        ]);
        AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $pastEvent->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Past Event Promo',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // 2. Create a future event with zero remaining budget
        $exhaustedEvent = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Exhausted Event Test',
            'slug' => 'exhausted-event-' . uniqid(),
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'status' => 'published',
        ]);
        AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $exhaustedEvent->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Exhausted Promo',
            'budget' => 50.00,
            'remaining_amount' => 0.00,
            'status' => AdCampaign::STATUS_BUDGET_EXHAUSTED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Query feed as viewer
        $feedResponse = $this->actingAs($viewer, 'member')
            ->getJson('/api/member/socials');
        $feedResponse->assertStatus(200);

        $posts = collect($feedResponse->json('posts.data') ?? $feedResponse->json('posts') ?? []);
        
        $pastInFeed = $posts->first(function ($item) use ($pastEvent) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $pastEvent->id || ($item['id'] ?? null) === "event_{$pastEvent->id}");
        });
        $this->assertNull($pastInFeed, 'Past events must never appear in Socials feed');

        $exhaustedInFeed = $posts->first(function ($item) use ($exhaustedEvent) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $exhaustedEvent->id || ($item['id'] ?? null) === "event_{$exhaustedEvent->id}");
        });
        $this->assertNull($exhaustedInFeed, 'Exhausted events must never appear in Socials feed');
    }

    /**
     * TEST: Unverified member can view the paid event card in socials feed, but is blocked from earning rewards.
     */
    public function test_unverified_member_sees_card_in_feed_but_blocked_from_reward_action(): void
    {
        $organizer = $this->createMember(500.00, true);
        $unverifiedViewer = $this->createMember(0.00, false);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Security Gating Event',
            'slug' => 'security-gating-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'published',
        ]);
        AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'Security Promo',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Unverified viewer can view feed and sees the paid event card
        $feedResponse = $this->actingAs($unverifiedViewer, 'member')
            ->getJson('/api/member/socials');
        $feedResponse->assertStatus(200);

        $posts = collect($feedResponse->json('posts.data') ?? $feedResponse->json('posts') ?? []);
        $eventInFeed = $posts->first(function ($item) use ($event) {
            return ($item['content_type'] ?? null) === 'PAID_EVENT'
                && (($item['event']['id'] ?? null) === $event->id || ($item['id'] ?? null) === "event_{$event->id}");
        });
        $this->assertNotNull($eventInFeed, 'Unverified member must see paid event card in Socials feed');

        // Unverified viewer is blocked when trying to claim/qualify reward
        $claimResponse = $this->actingAs($unverifiedViewer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/claim", [
                'action_type' => 'interested',
            ]);
        $claimResponse->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('needs_verification', true);
    }
}
