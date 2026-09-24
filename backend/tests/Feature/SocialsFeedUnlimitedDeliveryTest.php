<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SocialsFeedUnlimitedDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected Member $viewer;
    protected Member $unverifiedViewer;
    protected Member $organizer;
    protected BusinessPage $businessPage;

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

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();

        $this->organizer = $this->createMember(['name' => 'Ad Organizer', 'mobile_verified_at' => now()]);
        $this->viewer = $this->createMember(['name' => 'Verified Viewer', 'mobile_verified_at' => now()]);
        $this->unverifiedViewer = $this->createMember(['name' => 'Unverified Viewer', 'mobile_verified_at' => null]);

        $this->businessPage = BusinessPage::create([
            'member_id' => $this->organizer->id,
            'page_name' => 'Test Business Page',
            'page_username' => 'biz_' . uniqid(),
            'slug' => 'biz-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 1000;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "user_{$unique}_" . Str::random(5),
            'email' => "user_{$unique}_" . Str::random(5) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    protected function createBusinessAd(string $name, float $budget = 50.00): AdCampaign
    {
        $post = Post::create([
            'member_id' => $this->organizer->id,
            'business_page_id' => $this->businessPage->id,
            'body' => "Ad copy for {$name}",
            'visibility' => 'public',
            'type' => 'post',
        ]);

        return AdCampaign::create([
            'campaign_id' => 'AD-' . strtoupper(Str::random(8)),
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'business_page_id' => $this->businessPage->id,
            'member_id' => $this->organizer->id,
            'post_id' => $post->id,
            'campaign_name' => $name,
            'budget' => $budget,
            'total_funded' => $budget,
            'remaining_amount' => $budget,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ]);
    }

    protected function createPaidEvent(string $title, string $startDate, string $endDate, float $budget = 50.00): AdCampaign
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . uniqid(),
            'description' => "Description for {$title}",
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => $startDate,
            'start_time' => '09:00:00',
            'end_date' => $endDate,
            'end_time' => '17:00:00',
            'timezone' => 'UTC',
            'status' => 'published',
            'privacy' => 'public',
        ]);

        return AdCampaign::create([
            'campaign_id' => 'EVT-' . strtoupper(Str::random(8)),
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => "Campaign for {$title}",
            'budget' => $budget,
            'total_funded' => $budget,
            'remaining_amount' => $budget,
            'spent_amount' => 0.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
        ]);
    }

    /**
     * Test 1: Empty organic feed delivers more than 2 eligible sponsored items (no 2-item cap).
     */
    public function test_empty_organic_feed_delivers_more_than_two_eligible_sponsored_items(): void
    {
        $this->createBusinessAd('Ad One');
        $this->createBusinessAd('Ad Two');
        $this->createBusinessAd('Ad Three');
        $this->createPaidEvent('Event One', now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString());
        $this->createPaidEvent('Event Two', now()->addDays(4)->toDateString(), now()->addDays(5)->toDateString());

        $response = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $this->assertGreaterThan(2, count($posts), 'Socials feed must NOT be limited to 2 items when organic feed is empty.');
        $this->assertCount(5, $posts, 'All 5 eligible sponsored items should be delivered.');
    }

    /**
     * Test 2: Mixed content test containing at least 3 member posts, 3 Paid Ads, and 3 Paid Events.
     */
    public function test_mixed_content_feed_delivers_all_eligible_posts_ads_and_events(): void
    {
        // 3 member posts
        $p1 = Post::create(['member_id' => $this->viewer->id, 'body' => 'Member Post 1', 'type' => 'post']);
        $p2 = Post::create(['member_id' => $this->viewer->id, 'body' => 'Member Post 2', 'type' => 'post']);
        $p3 = Post::create(['member_id' => $this->viewer->id, 'body' => 'Member Post 3', 'type' => 'post']);

        // 3 Paid Ads
        $ad1 = $this->createBusinessAd('Ad A');
        $ad2 = $this->createBusinessAd('Ad B');
        $ad3 = $this->createBusinessAd('Ad C');

        // 3 Paid Events
        $ev1 = $this->createPaidEvent('Event A', now()->addDays(1)->toDateString(), now()->addDays(2)->toDateString());
        $ev2 = $this->createPaidEvent('Event B', now()->addDays(3)->toDateString(), now()->addDays(4)->toDateString());
        $ev3 = $this->createPaidEvent('Event C', now()->addDays(5)->toDateString(), now()->addDays(6)->toDateString());

        $response = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $this->assertGreaterThan(2, count($posts), 'Combined feed must not be capped at 2 items.');
        $this->assertCount(9, $posts, 'Feed must contain 3 member posts + 3 business ads + 3 paid events = 9 items.');

        // Verify member posts presence
        $memberPosts = collect($posts)->filter(fn ($p) => empty($p['is_sponsored']));
        $this->assertCount(3, $memberPosts, 'All 3 member posts must be present in the feed.');

        // Verify business ads presence
        $adPosts = collect($posts)->filter(fn ($p) => ($p['type'] ?? '') === 'sponsored_ad');
        $this->assertCount(3, $adPosts, 'All 3 Business Ads must be present in the feed.');

        // Verify paid events presence
        $eventPosts = collect($posts)->filter(fn ($p) => ($p['type'] ?? '') === 'sponsored_event');
        $this->assertCount(3, $eventPosts, 'All 3 Paid Events must be present in the feed.');

        // Verify no duplicate IDs
        $ids = collect($posts)->pluck('id');
        $this->assertEquals($ids->count(), $ids->unique()->count(), 'No duplicate items must exist in the feed.');
    }

    /**
     * Test 3: Feed with exactly 2 member posts does not stop at 2 and includes sponsored items.
     */
    public function test_feed_with_two_member_posts_does_not_stop_at_two_and_includes_sponsored_items(): void
    {
        Post::create(['member_id' => $this->viewer->id, 'body' => 'Post 1', 'type' => 'post']);
        Post::create(['member_id' => $this->viewer->id, 'body' => 'Post 2', 'type' => 'post']);

        $this->createBusinessAd('Ad 1');
        $this->createPaidEvent('Event 1', now()->addDays(1)->toDateString(), now()->addDays(2)->toDateString());

        $response = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials');

        $response->assertStatus(200);
        $posts = $response->json('posts');

        $this->assertGreaterThan(2, count($posts), 'Feed with 2 member posts must not drop sponsored items or cap at 2.');
        $this->assertCount(4, $posts, 'Feed must contain 2 member posts + 1 ad + 1 event = 4 items.');
    }

    /**
     * Test 4: Pagination continues across multiple pages.
     */
    public function test_feed_pagination_continues_across_multiple_pages(): void
    {
        // Create 15 member posts
        for ($i = 1; $i <= 15; $i++) {
            Post::create([
                'member_id' => $this->viewer->id,
                'body' => "Bulk Member Post {$i}",
                'type' => 'post',
                'created_at' => now()->subMinutes(20 - $i),
            ]);
        }

        $page1 = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials?page=1');
        $page1->assertStatus(200);
        $this->assertTrue($page1->json('has_more'), 'Page 1 must indicate has_more = true when 15 posts exist.');
        $this->assertGreaterThanOrEqual(10, count($page1->json('posts')));

        $page2 = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials?page=2');
        $page2->assertStatus(200);
        $this->assertFalse($page2->json('has_more'), 'Page 2 should be the final page for 15 posts with per_page 10.');
        $this->assertGreaterThanOrEqual(5, count($page2->json('posts')));
    }

    /**
     * Test 5: Past Events remain excluded from unlimited feed.
     */
    public function test_past_events_remain_excluded_from_unlimited_feed(): void
    {
        $futureEventCampaign = $this->createPaidEvent('Future Event', now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString());
        $pastEventCampaign = $this->createPaidEvent('Past Event', now()->subDays(5)->toDateString(), now()->subDays(4)->toDateString());

        $response = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials');
        $posts = $response->json('posts');

        $eventCampaignIds = collect($posts)
            ->filter(fn ($p) => ($p['type'] ?? '') === 'sponsored_event')
            ->pluck('ad_campaign_id')
            ->all();

        $this->assertContains($futureEventCampaign->campaign_id, $eventCampaignIds, 'Future paid event must appear.');
        $this->assertNotContains($pastEventCampaign->campaign_id, $eventCampaignIds, 'Past paid event must be excluded.');
        $this->assertDatabaseHas('ad_campaigns', ['id' => $pastEventCampaign->id]); // Preserved in DB
    }

    /**
     * Test 6: Exhausted budget campaigns remain excluded from unlimited feed.
     */
    public function test_exhausted_budget_campaigns_remain_excluded_from_unlimited_feed(): void
    {
        $activeAd = $this->createBusinessAd('Funded Ad', 50.00);

        // Exhausted ad: remaining budget 0.010 (< min reward 0.0250)
        $exhaustedAd = $this->createBusinessAd('Exhausted Ad', 50.00);
        $exhaustedAd->update(['spent_amount' => 49.99, 'remaining_amount' => 0.010]);

        $response = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials');
        $posts = $response->json('posts');

        $adCampaignIds = collect($posts)
            ->filter(fn ($p) => ($p['type'] ?? '') === 'sponsored_ad')
            ->pluck('ad_campaign_id')
            ->all();

        $this->assertContains($activeAd->campaign_id, $adCampaignIds, 'Funded ad must appear.');
        $this->assertNotContains($exhaustedAd->campaign_id, $adCampaignIds, 'Exhausted ad must be excluded.');
    }

    /**
     * Test 7: Unverified member sees all eligible content without 2-item cap.
     */
    public function test_unverified_member_sees_all_eligible_content_without_two_item_cap(): void
    {
        $this->createBusinessAd('Ad For Unverified 1');
        $this->createBusinessAd('Ad For Unverified 2');
        $this->createPaidEvent('Event For Unverified 1', now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString());
        $this->createPaidEvent('Event For Unverified 2', now()->addDays(4)->toDateString(), now()->addDays(5)->toDateString());

        $response = $this->actingAs($this->unverifiedViewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);
        $posts = $response->json('posts');

        $this->assertGreaterThan(2, count($posts), 'Unverified member feed must not be capped at 2.');
        $this->assertCount(4, $posts, 'Unverified member should see all 4 eligible sponsored items.');
    }

    /**
     * Test 8: Unverified member Earn Up To gate remains strictly enforced.
     */
    public function test_unverified_member_earn_up_to_gate_intact(): void
    {
        $campaign = $this->createPaidEvent('Gate Event', now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString());
        $event = $campaign->event;

        $response = $this->actingAs($this->unverifiedViewer, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Please verify your phone number first before proceeding.',
                'error_code' => 'PHONE_VERIFICATION_REQUIRED',
                'verified_required' => true,
                'needs_verification' => true,
            ]);
    }

    /**
     * Test 9: Rendering unlimited feed has zero side effects on wallet and budget.
     */
    public function test_rendering_unlimited_feed_has_zero_side_effects(): void
    {
        $ad = $this->createBusinessAd('No Side Effect Ad', 100.00);
        $event = $this->createPaidEvent('No Side Effect Event', now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString(), 100.00);

        $initialWallet = $this->viewer->reward_balance;

        $response = $this->actingAs($this->viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        // Zero wallet change
        $this->assertEquals($initialWallet, $this->viewer->fresh()->reward_balance);
        $this->assertEquals(0, AdReward::count(), 'No ad reward rows created merely by viewing feed.');

        // Zero budget change
        $this->assertEquals(100.00, $ad->fresh()->remaining_amount);
        $this->assertEquals(100.00, $event->fresh()->remaining_amount);
    }
}
