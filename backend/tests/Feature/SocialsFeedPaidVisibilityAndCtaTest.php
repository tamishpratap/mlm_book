<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialsFeedPaidVisibilityAndCtaTest extends TestCase
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
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

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
     * TEST 1: Promoted post is classified as PAID_AD and not degraded to organic for verified members.
     */
    public function test_promoted_ad_post_is_classified_as_paid_ad_and_not_degraded_to_organic_for_verified_member(): void
    {
        $verifiedMember = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $adCampaign = $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 45.00]);

        $response = $this->actingAs($verifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertNotEmpty($posts);

        // Find the promoted post
        $promotedItem = collect($posts)->first(function ($item) use ($adCampaign) {
            return ($item['id'] ?? null) == $adCampaign->post_id ||
                   ($item['ad_campaign']['id'] ?? null) == $adCampaign->id ||
                   ($item['ad_campaign_id'] ?? null) == $adCampaign->id;
        });

        $this->assertNotNull($promotedItem, 'Promoted post must exist in verified member feed');
        $this->assertEquals('PAID_AD', $promotedItem['content_type']);
        $this->assertTrue($promotedItem['is_sponsored']);
        $this->assertNotNull($promotedItem['ad_campaign']);
        $this->assertEquals('$0.025', $promotedItem['ad_campaign']['earn_up_to_formatted']);
    }

    /**
     * TEST 2: Verified member sees both Paid Ads and Paid Events alongside organic posts.
     */
    public function test_verified_member_sees_both_paid_ads_and_paid_events_alongside_organic_posts(): void
    {
        $verifiedMember = $this->createMember(['mobile_verified_at' => now()]);
        $friend = $this->createMember(['mobile_verified_at' => now()]);

        Friendship::create([
            'member_one_id' => min($verifiedMember->id, $friend->id),
            'member_two_id' => max($verifiedMember->id, $friend->id),
            'requested_by_id' => $friend->id,
            'status' => 'accepted',
        ]);

        // Create organic posts
        Post::create([
            'member_id' => $friend->id,
            'body' => 'Organic post by friend 1',
            'visibility' => 'public',
        ]);
        Post::create([
            'member_id' => $friend->id,
            'body' => 'Organic post by friend 2',
            'visibility' => 'public',
        ]);

        // Create 1 business ad and 1 event campaign
        $this->createBusinessAdCampaign($friend, ['remaining_amount' => 60.00]);
        $this->createPaidEventCampaign($friend, ['remaining_amount' => 40.00]);

        $response = $this->actingAs($verifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $contentTypes = collect($posts)->pluck('content_type')->all();

        $this->assertContains('PAID_AD', $contentTypes, 'Verified feed must contain PAID_AD');
        $this->assertContains('PAID_EVENT', $contentTypes, 'Verified feed must contain PAID_EVENT');
        $this->assertContains('ORGANIC_POST', $contentTypes, 'Verified feed must contain ORGANIC_POST');
    }

    /**
     * TEST 3: Unverified member sees both Paid Ads and Paid Events, and zero organic posts.
     */
    public function test_unverified_member_sees_both_paid_ads_and_paid_events_and_zero_organic_posts(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $otherMember = $this->createMember(['mobile_verified_at' => now()]);

        // Create organic posts
        Post::create([
            'member_id' => $otherMember->id,
            'body' => 'Organic post',
            'visibility' => 'public',
        ]);

        // Create 1 business ad and 1 event campaign
        $this->createBusinessAdCampaign($otherMember, ['remaining_amount' => 50.00]);
        $this->createPaidEventCampaign($otherMember, ['remaining_amount' => 30.00]);

        $response = $this->actingAs($unverifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $contentTypes = collect($posts)->pluck('content_type')->all();

        $this->assertContains('PAID_AD', $contentTypes, 'Unverified feed must contain PAID_AD');
        $this->assertContains('PAID_EVENT', $contentTypes, 'Unverified feed must contain PAID_EVENT');
        $this->assertNotContains('ORGANIC_POST', $contentTypes, 'Unverified feed must NOT contain ORGANIC_POST');

        // Check that total count matches only paid items
        $this->assertCount(2, $posts);
    }

    /**
     * TEST 4: Content type field is explicitly serialized on all feed items.
     */
    public function test_content_type_field_is_explicitly_serialized_on_all_feed_items(): void
    {
        $verifiedMember = $this->createMember(['mobile_verified_at' => now()]);
        $otherMember = $this->createMember(['mobile_verified_at' => now()]);

        Friendship::create([
            'member_one_id' => min($verifiedMember->id, $otherMember->id),
            'member_two_id' => max($verifiedMember->id, $otherMember->id),
            'requested_by_id' => $otherMember->id,
            'status' => 'accepted',
        ]);

        Post::create([
            'member_id' => $otherMember->id,
            'body' => 'Standard organic post',
            'visibility' => 'public',
        ]);

        $this->createBusinessAdCampaign($otherMember, ['remaining_amount' => 50.00]);
        $this->createPaidEventCampaign($otherMember, ['remaining_amount' => 35.00]);

        $response = $this->actingAs($verifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        foreach ($posts as $item) {
            $this->assertArrayHasKey('content_type', $item);
            $this->assertContains($item['content_type'], ['ORGANIC_POST', 'PAID_AD', 'PAID_EVENT']);
        }
    }

    /**
     * TEST 5: Past events are strictly excluded from paid event delivery.
     */
    public function test_past_events_are_strictly_excluded_from_paid_event_delivery(): void
    {
        $member = $this->createMember(['mobile_verified_at' => now()]);

        // Create a past event
        $pastEvent = Event::create([
            'organizer_id' => $member->id,
            'title' => 'Expired Past Event',
            'slug' => 'expired-past-event-' . uniqid(),
            'description' => 'Past event description',
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => now()->subDays(5)->toDateString(),
            'start_time' => '10:00:00',
            'end_date' => now()->subDays(2)->toDateString(),
            'end_time' => '18:00:00',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        AdCampaign::create([
            'campaign_id' => 'CAMP-EVT-EXPIRED-' . uniqid(),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $pastEvent->id,
            'member_id' => $member->id,
            'campaign_name' => 'Expired Event Campaign',
            'budget' => 50.00,
            'total_funded' => 50.00,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDays(6),
            'end_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $deliveredEventIds = collect($posts)
            ->filter(fn($p) => ($p['content_type'] ?? '') === 'PAID_EVENT')
            ->pluck('event.id')
            ->all();

        $this->assertNotContains($pastEvent->id, $deliveredEventIds, 'Past event must NOT be delivered in paid feed');
    }

    /**
     * TEST 6: Shared ranking pool sorts by eligible budget desc, then recency desc.
     */
    public function test_shared_ranking_pool_sorts_by_eligible_budget_desc_then_recency_desc(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        // Event A: Budget $30
        $eventA = $this->createPaidEventCampaign($advertiser, ['remaining_amount' => 30.00, 'campaign_name' => 'Event A ($30)']);
        // Business Ad B: Budget $90
        $adB = $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 90.00, 'campaign_name' => 'Ad B ($90)']);
        // Event C: Budget $60
        $eventC = $this->createPaidEventCampaign($advertiser, ['remaining_amount' => 60.00, 'campaign_name' => 'Event C ($60)']);

        $response = $this->actingAs($unverifiedMember, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $this->assertCount(3, $posts);
        // Position 1: Ad B ($90)
        $this->assertEquals($adB->campaign_name, $posts[0]['ad_campaign']['campaign_name']);
        // Position 2: Event C ($60)
        $this->assertEquals($eventC->campaign_name, $posts[1]['ad_campaign']['campaign_name']);
        // Position 3: Event A ($30)
        $this->assertEquals($eventA->campaign_name, $posts[2]['ad_campaign']['campaign_name']);
    }

    /**
     * TEST 7: Unverified member earn actions fail closed at backend.
     */
    public function test_unverified_member_earn_actions_fail_closed_at_backend(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $adCampaign = $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 50.00]);

        // Attempting to record ad click / earn action fails closed with 403
        $clickRes = $this->actingAs($unverifiedMember, 'member')
            ->postJson("/api/member/ad-campaigns/{$adCampaign->campaign_id}/click", [
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
