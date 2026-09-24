<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCampaignFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_member_cannot_create_event_campaign(): void
    {
        $unverified = Member::create([
            'name' => 'Unverified Organizer',
            'email' => 'unverified@example.com',
            'password' => 'password123',
            'mobile_verified_at' => null,
        ]);

        $event = Event::create([
            'organizer_id' => $unverified->id,
            'title' => 'Crypto Summit',
            'slug' => 'crypto-summit',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($unverified, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign", [
                'campaign_name' => 'Crypto Summit Campaign',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('needs_verification', true);

        $this->assertDatabaseMissing('ad_campaigns', [
            'event_id' => $event->id,
        ]);
    }

    public function test_verified_organizer_can_create_event_campaign_foundation(): void
    {
        $organizer = Member::create([
            'name' => 'Verified Organizer',
            'email' => 'organizer@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
            'ad_balance' => 100.00,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Global AI Conference',
            'slug' => 'global-ai-conference',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign", [
                'campaign_name' => 'Custom AI Campaign Name',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.campaign_type', 'event')
            ->assertJsonPath('campaign.event_id', $event->id)
            ->assertJsonPath('campaign.member_id', $organizer->id)
            ->assertJsonPath('campaign.campaign_name', 'Custom AI Campaign Name')
            ->assertJsonPath('campaign.status', 'draft')
            ->assertJsonPath('campaign.approval_status', 'pending');

        $this->assertDatabaseHas('ad_campaigns', [
            'event_id' => $event->id,
            'campaign_type' => 'event',
            'member_id' => $organizer->id,
            'status' => 'draft',
            'approval_status' => 'pending',
            'budget' => 0.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 0.00,
            'wallet_debit' => 0.00,
            'fee_amount' => 0.00,
        ]);

        // Verify member ad balance is completely untouched
        $this->assertEquals(100.00, (float) $organizer->fresh()->ad_balance);
    }

    public function test_non_organizer_cannot_create_or_view_event_campaign(): void
    {
        $organizer = Member::create([
            'name' => 'Real Organizer',
            'email' => 'real@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
        ]);

        $intruder = Member::create([
            'name' => 'Intruder User',
            'email' => 'intruder@example.com',
            'password' => 'password123',
            'phone' => '+15559876543',
            'mobile_verified_at' => now(),
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Private Founder Dinner',
            'slug' => 'private-founder-dinner',
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(3)->toDateString(),
            'status' => 'published',
        ]);

        // Intruder tries to create campaign for organizer's event
        $createResponse = $this->actingAs($intruder, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign", [
                'campaign_name' => 'Hijacked Campaign',
            ]);

        $createResponse->assertStatus(403)
            ->assertJsonPath('success', false);

        // Intruder tries to view campaign for organizer's event
        $viewResponse = $this->actingAs($intruder, 'member')
            ->getJson("/api/member/events/{$event->id}/campaign");

        $viewResponse->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('ad_campaigns', [
            'event_id' => $event->id,
        ]);
    }

    public function test_duplicate_creation_is_idempotent_and_returns_existing_campaign(): void
    {
        $organizer = Member::create([
            'name' => 'Repeat Organizer',
            'email' => 'repeat@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Web3 Hackathon',
            'slug' => 'web3-hackathon',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(7)->toDateString(),
            'status' => 'published',
        ]);

        // First creation call -> 201 Created
        $firstCall = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign", [
                'campaign_name' => 'Web3 Hackathon 2026',
            ]);

        $firstCall->assertStatus(201);
        $campaignId = $firstCall->json('campaign.campaign_id');

        // Second creation call -> 200 OK (Idempotent reuse)
        $secondCall = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign", [
                'campaign_name' => 'Another Name Attempt',
            ]);

        $secondCall->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('campaign.campaign_id', $campaignId);

        // Third creation call -> 200 OK
        $thirdCall = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign");

        $thirdCall->assertStatus(200)
            ->assertJsonPath('campaign.campaign_id', $campaignId);

        // Verify exactly 1 campaign record exists in database for this event
        $this->assertEquals(1, AdCampaign::where('event_id', $event->id)->count());
    }

    public function test_event_and_campaign_bidirectional_relationships(): void
    {
        $organizer = Member::create([
            'name' => 'Relation Host',
            'email' => 'host@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'FinTech Expo',
            'slug' => 'fintech-expo',
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(4)->toDateString(),
            'status' => 'published',
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $organizer->id,
            'campaign_name' => 'FinTech Expo Campaign',
            'budget' => 0.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Event -> Campaign
        $this->assertTrue($event->isPaidCampaign());
        $this->assertNotNull($event->campaign);
        $this->assertEquals($campaign->id, $event->campaign->id);
        $this->assertEquals('event', $event->campaign->campaign_type);

        // Campaign -> Event
        $this->assertNotNull($campaign->event);
        $this->assertEquals($event->id, $campaign->event->id);
        $this->assertEquals('FinTech Expo', $campaign->event->title);

        // Ownership checks
        $this->assertTrue($campaign->isOwner($organizer->id));
        $this->assertFalse($campaign->isOwner(999999));
    }

    public function test_campaign_scopes_cleanly_separate_business_ads_and_events(): void
    {
        $member = Member::create([
            'name' => 'Scope Member',
            'email' => 'scope@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
        ]);

        $businessPage = BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Scope Business Page',
            'page_username' => 'scope_page',
            'slug' => 'scope-business-page',
            'category' => 'Technology',
            'is_published' => true,
        ]);

        $post = Post::create([
            'member_id' => $member->id,
            'business_page_id' => $businessPage->id,
            'content' => 'Business Ad Post',
            'visibility' => 'public',
        ]);

        $event = Event::create([
            'organizer_id' => $member->id,
            'title' => 'Scope Event',
            'slug' => 'scope-event',
            'category' => 'Technology',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'status' => 'published',
        ]);

        $businessCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $member->id,
            'post_id' => $post->id,
            'campaign_name' => 'Business Campaign',
            'budget' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $eventCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $member->id,
            'campaign_name' => 'Event Campaign',
            'budget' => 0.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Business Ads scope
        $businessAds = AdCampaign::businessAds()->get();
        $this->assertTrue($businessAds->contains('id', $businessCampaign->id));
        $this->assertFalse($businessAds->contains('id', $eventCampaign->id));

        // Events scope
        $eventCampaigns = AdCampaign::events()->get();
        $this->assertTrue($eventCampaigns->contains('id', $eventCampaign->id));
        $this->assertFalse($eventCampaigns->contains('id', $businessCampaign->id));

        // forEvent scope
        $foundForEvent = AdCampaign::forEvent($event->id)->first();
        $this->assertNotNull($foundForEvent);
        $this->assertEquals($eventCampaign->id, $foundForEvent->id);
    }

    public function test_event_campaign_foundation_has_zero_financial_side_effects(): void
    {
        $organizer = Member::create([
            'name' => 'Zero Side Effect Organizer',
            'email' => 'zero@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
            'ad_balance' => 250.00,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Safe Zero Finance Event',
            'slug' => 'safe-zero-finance-event',
            'category' => 'Business',
            'event_type' => 'online',
            'privacy' => 'public',
            'start_date' => now()->addDays(2)->toDateString(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($organizer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign");

        $response->assertStatus(201);

        $createdCampaign = AdCampaign::where('event_id', $event->id)->first();
        $this->assertNotNull($createdCampaign);

        // Strict financial invariants
        $this->assertEquals(0.00, (float) $createdCampaign->budget);
        $this->assertEquals(0.00, (float) $createdCampaign->spent_amount);
        $this->assertEquals(0.00, (float) $createdCampaign->remaining_amount);
        $this->assertEquals(0.00, (float) $createdCampaign->wallet_debit);
        $this->assertEquals(0.00, (float) $createdCampaign->fee_amount);

        // Member balances remain 100% untouched
        $this->assertEquals(250.00, (float) $organizer->fresh()->ad_balance);
    }

    public function test_event_creation_with_create_campaign_flag_atomically_initializes_campaign(): void
    {
        $organizer = Member::create([
            'name' => 'Direct Creator',
            'email' => 'direct@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'All In One Paid Event',
                'category' => 'Technology',
                'event_type' => 'online',
                'privacy' => 'public',
                'start_date' => now()->addDays(5)->toDateString(),
                'create_campaign' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', true);

        $eventId = $response->json('event.id');
        $this->assertDatabaseHas('ad_campaigns', [
            'event_id' => $eventId,
            'campaign_type' => 'event',
            'member_id' => $organizer->id,
            'status' => 'draft',
            'approval_status' => 'pending',
            'budget' => 0.00,
        ]);
    }

    public function test_organic_event_creation_remains_unaffected_and_non_paid(): void
    {
        $organizer = Member::create([
            'name' => 'Organic Creator',
            'email' => 'organic@example.com',
            'password' => 'password123',
            'phone' => '+15551234567',
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($organizer, 'member')
            ->postJson(route('member.events.store'), [
                'title' => 'Standard Community Meetup',
                'category' => 'Meetup',
                'event_type' => 'offline',
                'privacy' => 'public',
                'start_date' => now()->addDays(3)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_paid', false);

        $eventId = $response->json('event.id');
        $this->assertDatabaseMissing('ad_campaigns', [
            'event_id' => $eventId,
        ]);

        // Ensure Event show endpoint returns is_paid = false and campaign = null
        $showResponse = $this->actingAs($organizer, 'member')
            ->getJson(route('member.events.show', $eventId));

        $showResponse->assertStatus(200)
            ->assertJsonPath('is_paid', false)
            ->assertJsonPath('campaign', null);
    }
}
