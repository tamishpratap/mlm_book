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

class SocialsFeedPaidCampaignRankingTest extends TestCase
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
            'ad_balance' => 1000.00,
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

    protected function createOrganicPost(Member $author, array $attrs = []): Post
    {
        $unique = uniqid('org_');
        return Post::create(array_merge([
            'member_id' => $author->id,
            'body' => 'Organic post ' . $unique,
            'visibility' => 'public',
            'is_pinned' => false,
            'created_at' => now(),
        ], $attrs));
    }

    /**
     * TEST 1 — PAID ORDER:
     * Business ($500), Event ($300), Business ($100).
     * Expected order: 500, 300, 100.
     */
    public function test_paid_order_sorted_by_remaining_amount_desc(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 500.00, 'budget' => 500.00]);
        $this->createPaidEventCampaign($advertiser, ['remaining_amount' => 300.00, 'budget' => 300.00]);
        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 100.00, 'budget' => 100.00]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(3, $posts);

        $this->assertEquals(500.00, (float) $posts[0]['ad_campaign']['remaining_amount']);
        $this->assertEquals(300.00, (float) $posts[1]['ad_campaign']['remaining_amount']);
        $this->assertEquals(100.00, (float) $posts[2]['ad_campaign']['remaining_amount']);
    }

    /**
     * TEST 2 — BUSINESS + EVENT MIXING:
     * Business Ad = $400, Event = $350, Business Ad = $200, Event = $50.
     * Expected: 400, 350, 200, 50 in shared paid tier.
     */
    public function test_business_and_event_campaigns_mix_in_same_tier_by_budget(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 400.00, 'budget' => 400.00]);
        $this->createPaidEventCampaign($advertiser, ['remaining_amount' => 350.00, 'budget' => 350.00]);
        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 200.00, 'budget' => 200.00]);
        $this->createPaidEventCampaign($advertiser, ['remaining_amount' => 50.00, 'budget' => 50.00]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(4, $posts);

        $this->assertEquals('PAID_AD', $posts[0]['content_type']);
        $this->assertEquals(400.00, (float) $posts[0]['ad_campaign']['remaining_amount']);

        $this->assertEquals('PAID_EVENT', $posts[1]['content_type']);
        $this->assertEquals(350.00, (float) $posts[1]['ad_campaign']['remaining_amount']);

        $this->assertEquals('PAID_AD', $posts[2]['content_type']);
        $this->assertEquals(200.00, (float) $posts[2]['ad_campaign']['remaining_amount']);

        $this->assertEquals('PAID_EVENT', $posts[3]['content_type']);
        $this->assertEquals(50.00, (float) $posts[3]['ad_campaign']['remaining_amount']);
    }

    /**
     * TEST 3 — ORGANIC BELOW ALL PAID:
     * ALL eligible paid items appear before the FIRST organic post, even if organic is pinned.
     */
    public function test_all_eligible_paid_items_appear_before_first_organic_post(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        // Create 2 paid campaigns
        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 250.00, 'budget' => 250.00]);
        $this->createPaidEventCampaign($advertiser, ['remaining_amount' => 150.00, 'budget' => 150.00]);

        // Create organic posts (one pinned)
        $this->createOrganicPost($viewer, ['body' => 'Pinned post', 'is_pinned' => true, 'created_at' => now()]);
        $this->createOrganicPost($viewer, ['body' => 'Normal post', 'is_pinned' => false, 'created_at' => now()->subHour()]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(4, $posts);

        // First 2 are paid
        $this->assertEquals('PAID_AD', $posts[0]['content_type']);
        $this->assertEquals('PAID_EVENT', $posts[1]['content_type']);

        // Next 2 are organic
        $this->assertEquals('ORGANIC_POST', $posts[2]['content_type']);
        $this->assertTrue((bool) $posts[2]['is_pinned']);
        $this->assertEquals('ORGANIC_POST', $posts[3]['content_type']);
        $this->assertFalse((bool) $posts[3]['is_pinned']);
    }

    /**
     * TEST 4 — ORGANIC ORDER:
     * Organic posts preserve order: is_pinned DESC, created_at DESC, id DESC.
     */
    public function test_organic_posts_preserve_existing_ordering(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 100.00, 'budget' => 100.00]);

        $p1 = $this->createOrganicPost($viewer, ['body' => 'Older Normal', 'is_pinned' => false, 'created_at' => now()->subHours(5)]);
        $p2 = $this->createOrganicPost($viewer, ['body' => 'Newer Normal', 'is_pinned' => false, 'created_at' => now()->subHours(1)]);
        $p3 = $this->createOrganicPost($viewer, ['body' => 'Pinned Normal', 'is_pinned' => true, 'created_at' => now()->subHours(3)]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(4, $posts);

        $this->assertEquals('PAID_AD', $posts[0]['content_type']);
        $this->assertEquals($p3->id, $posts[1]['id']); // Pinned
        $this->assertEquals($p2->id, $posts[2]['id']); // Newer Normal
        $this->assertEquals($p1->id, $posts[3]['id']); // Older Normal
    }

    /**
     * TEST 5 — PAGINATION:
     * 12 paid campaigns, 20 organic posts, perPage = 10.
     * Page 1: 10 paid.
     * Page 2: 2 paid + 8 organic.
     * Page 3: 10 organic.
     * Page 4: 2 organic.
     * No duplicates, no skipped posts, accurate total and has_more.
     */
    public function test_pagination_across_paid_and_organic_boundaries(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        // Create 12 paid campaigns with decreasing remaining_amounts
        for ($i = 1; $i <= 12; $i++) {
            $this->createBusinessAdCampaign($advertiser, [
                'remaining_amount' => 1000 - ($i * 10),
                'budget' => 1000,
            ]);
        }

        // Create 20 organic posts
        for ($j = 1; $j <= 20; $j++) {
            $this->createOrganicPost($viewer, [
                'body' => "Organic {$j}",
                'created_at' => now()->subMinutes(20 - $j),
            ]);
        }

        // --- PAGE 1 ---
        $res1 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=1&per_page=10');
        $res1->assertStatus(200);
        $this->assertEquals(32, $res1->json('total'));
        $this->assertTrue($res1->json('has_more'));
        $page1Posts = $res1->json('posts');
        $this->assertCount(10, $page1Posts);
        foreach ($page1Posts as $p) {
            $this->assertEquals('PAID_AD', $p['content_type']);
        }

        // --- PAGE 2 ---
        $res2 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=2&per_page=10');
        $res2->assertStatus(200);
        $this->assertEquals(32, $res2->json('total'));
        $this->assertTrue($res2->json('has_more'));
        $page2Posts = $res2->json('posts');
        $this->assertCount(10, $page2Posts);
        // First 2 paid
        $this->assertEquals('PAID_AD', $page2Posts[0]['content_type']);
        $this->assertEquals('PAID_AD', $page2Posts[1]['content_type']);
        // Remaining 8 organic
        for ($k = 2; $k < 10; $k++) {
            $this->assertEquals('ORGANIC_POST', $page2Posts[$k]['content_type']);
        }

        // --- PAGE 3 ---
        $res3 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=3&per_page=10');
        $res3->assertStatus(200);
        $this->assertEquals(32, $res3->json('total'));
        $this->assertTrue($res3->json('has_more'));
        $page3Posts = $res3->json('posts');
        $this->assertCount(10, $page3Posts);
        foreach ($page3Posts as $p) {
            $this->assertEquals('ORGANIC_POST', $p['content_type']);
        }

        // --- PAGE 4 ---
        $res4 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=4&per_page=10');
        $res4->assertStatus(200);
        $this->assertEquals(32, $res4->json('total'));
        $this->assertFalse($res4->json('has_more'));
        $page4Posts = $res4->json('posts');
        $this->assertCount(2, $page4Posts);
        foreach ($page4Posts as $p) {
            $this->assertEquals('ORGANIC_POST', $p['content_type']);
        }

        // Check for duplicates across all pages
        $allIds = collect(array_merge($page1Posts, $page2Posts, $page3Posts, $page4Posts))
            ->map(function ($p) {
                return ($p['content_type'] === 'PAID_AD')
                    ? 'AD_' . ($p['ad_campaign']['id'] ?? $p['id'])
                    : 'ORG_' . $p['id'];
            });
        $this->assertCount(32, $allIds->unique());
    }

    /**
     * TEST 6 — PAID COUNT LESS THAN PAGE SIZE:
     * 3 paid, 20 organic.
     * Page 1: 3 paid + 7 organic.
     * Page 2: next 10 organic.
     */
    public function test_paid_count_less_than_page_size_seamless_slice(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        for ($i = 1; $i <= 3; $i++) {
            $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 500 - $i * 10]);
        }
        for ($j = 1; $j <= 20; $j++) {
            $this->createOrganicPost($viewer, ['body' => "Org {$j}", 'created_at' => now()->subMinutes(20 - $j)]);
        }

        $res1 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=1&per_page=10');
        $res1->assertStatus(200);
        $this->assertEquals(23, $res1->json('total'));
        $page1Posts = $res1->json('posts');
        $this->assertCount(10, $page1Posts);

        // 3 paid
        $this->assertEquals('PAID_AD', $page1Posts[0]['content_type']);
        $this->assertEquals('PAID_AD', $page1Posts[1]['content_type']);
        $this->assertEquals('PAID_AD', $page1Posts[2]['content_type']);
        // 7 organic
        for ($k = 3; $k < 10; $k++) {
            $this->assertEquals('ORGANIC_POST', $page1Posts[$k]['content_type']);
        }

        $res2 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=2&per_page=10');
        $res2->assertStatus(200);
        $page2Posts = $res2->json('posts');
        $this->assertCount(10, $page2Posts);
        foreach ($page2Posts as $p) {
            $this->assertEquals('ORGANIC_POST', $p['content_type']);
        }

        // Ensure organic posts on page 2 do not duplicate organic posts from page 1
        $page1OrgIds = collect($page1Posts)->skip(3)->pluck('id');
        $page2OrgIds = collect($page2Posts)->pluck('id');
        $this->assertEmpty($page1OrgIds->intersect($page2OrgIds));
    }

    /**
     * TEST 7 — ZERO PAID:
     * 0 paid, 20 organic posts. Normal organic feed behavior preserved.
     */
    public function test_zero_paid_campaigns_returns_pure_organic_feed(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);

        for ($j = 1; $j <= 20; $j++) {
            $this->createOrganicPost($viewer, ['body' => "Org {$j}"]);
        }

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=1&per_page=10');
        $response->assertStatus(200);

        $this->assertEquals(20, $response->json('total'));
        $this->assertTrue($response->json('has_more'));
        $posts = $response->json('posts');
        $this->assertCount(10, $posts);
        foreach ($posts as $p) {
            $this->assertEquals('ORGANIC_POST', $p['content_type']);
        }
    }

    /**
     * TEST 8 — ZERO ORGANIC:
     * 5 paid, 0 organic posts. Paid campaigns appear normally.
     */
    public function test_zero_organic_posts_returns_paid_campaigns_normally(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        for ($i = 1; $i <= 5; $i++) {
            $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 100 * $i]);
        }

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials?page=1&per_page=10');
        $response->assertStatus(200);

        $this->assertEquals(5, $response->json('total'));
        $this->assertFalse($response->json('has_more'));
        $posts = $response->json('posts');
        $this->assertCount(5, $posts);
        foreach ($posts as $p) {
            $this->assertEquals('PAID_AD', $p['content_type']);
        }
        $this->assertEquals(500.00, (float) $posts[0]['ad_campaign']['remaining_amount']);
    }

    /**
     * TEST 9 — EXHAUSTED CAMPAIGN:
     * Remaining budget below threshold or 0 must not enter paid priority.
     */
    public function test_exhausted_campaign_excluded_from_paid_tier(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        // Active campaign
        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 50.00]);

        // Exhausted campaign (below min reward amount 0.0250)
        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 0.0100]);

        // Fully exhausted campaign
        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 0.00, 'status' => AdCampaign::STATUS_COMPLETED]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(1, $posts);
        $this->assertEquals(50.00, (float) $posts[0]['ad_campaign']['remaining_amount']);
    }

    /**
     * TEST 10 — EXPIRED / STOPPED:
     * Expired or stopped campaigns do not appear in paid priority.
     */
    public function test_expired_or_stopped_campaigns_excluded_from_paid_tier(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 50.00]);

        // Expired date
        $this->createBusinessAdCampaign($advertiser, [
            'remaining_amount' => 100.00,
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDay(),
        ]);

        // Stopped status
        $this->createBusinessAdCampaign($advertiser, [
            'remaining_amount' => 200.00,
            'status' => AdCampaign::STATUS_PAUSED,
        ]);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(1, $posts);
        $this->assertEquals(50.00, (float) $posts[0]['ad_campaign']['remaining_amount']);
    }

    /**
     * TEST 11 — OWNER:
     * Campaign owner sees own campaign with is_owner = true and no self-reward.
     */
    public function test_campaign_owner_sees_own_campaign_with_owner_flag_protected(): void
    {
        $owner = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($owner, ['remaining_amount' => 75.00]);

        $response = $this->actingAs($owner, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(1, $posts);
        $this->assertTrue((bool) $posts[0]['is_owner']);
        $this->assertTrue((bool) $posts[0]['ad_campaign']['is_owner']);
        $this->assertNull($posts[0]['ad_campaign']['earn_up_to_formatted'] ?? null);
    }

    /**
     * TEST 12 — UNVERIFIED MEMBER:
     * Unverified member receives only paid campaigns, 0 organic posts.
     */
    public function test_unverified_member_receives_only_paid_campaigns_and_zero_organic(): void
    {
        $unverifiedMember = $this->createMember(['mobile_verified_at' => null]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 100.00]);
        $this->createOrganicPost($unverifiedMember, ['body' => 'Organic post by unverified']);

        $response = $this->actingAs($unverifiedMember, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(1, $posts);
        $this->assertEquals('PAID_AD', $posts[0]['content_type']);
        $this->assertEquals(1, $response->json('total'));
    }

    /**
     * TEST 13 — BUDGET UPDATE:
     * Live query updates order immediately without cache staleness.
     */
    public function test_budget_update_immediately_reflects_in_ranking_order(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $cA = $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 100.00, 'budget' => 100.00]);
        $cB = $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 300.00, 'budget' => 300.00]);

        // Request 1: B ($300) before A ($100)
        $res1 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $res1->assertStatus(200);
        $posts1 = $res1->json('posts');
        $this->assertEquals($cB->id, $posts1[0]['ad_campaign']['id']);
        $this->assertEquals($cA->id, $posts1[1]['ad_campaign']['id']);

        // Update B's budget: simulate spending so remaining_amount becomes $50 (below A's $100)
        $cB->update([
            'spent_amount' => 250.00,
            'remaining_amount' => 50.00,
        ]);

        // Request 2: A ($100) before B ($50)
        $res2 = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $res2->assertStatus(200);
        $posts2 = $res2->json('posts');
        $this->assertEquals($cA->id, $posts2[0]['ad_campaign']['id']);
        $this->assertEquals($cB->id, $posts2[1]['ad_campaign']['id']);
    }

    /**
     * TEST 14 — RESPONSE FORMAT:
     * API response retains required contract: success, has_more, current_page, next_page, total, posts, html.
     */
    public function test_api_response_format_matches_expected_contract(): void
    {
        $viewer = $this->createMember(['mobile_verified_at' => now()]);
        $advertiser = $this->createMember(['mobile_verified_at' => now()]);

        $this->createBusinessAdCampaign($advertiser, ['remaining_amount' => 50.00]);
        $this->createOrganicPost($viewer, ['body' => 'Organic post']);

        $response = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'has_more',
            'current_page',
            'next_page',
            'total',
            'posts',
            'html',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(2, $response->json('total'));
        $this->assertEquals(1, $response->json('current_page'));
        $this->assertEquals(2, $response->json('next_page'));
    }
}
