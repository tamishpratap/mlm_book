<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Tests\TestCase;

class AdCampaignUsdAndLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate');
        }
    }

    protected function tearDown(): void
    {
        AdClick::query()->where('placement', 'social_feed')->delete();
        AdImpression::query()->where('placement', 'social_feed')->delete();
        AdCampaign::query()->where('campaign_name', 'LIKE', '%Phase6%')->orWhere('campaign_name', 'LIKE', '%USD%')->orWhere('campaign_name', 'LIKE', '%Test%')->delete();
        Post::query()->where('body', 'LIKE', '%USD post content%')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'Biz usd_%')->delete();
        Member::query()->where('email', 'LIKE', '%_usd@test.com')->orWhere('email', 'LIKE', 'owner_%@test.com')->orWhere('email', 'LIKE', 'viewer_%@test.com')->delete();

        parent::tearDown();
    }

    protected function createVerifiedMember(string $prefix = 'member'): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_usd@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'ad_balance' => 200.00,
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('usd_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Biz ' . $unique,
            'page_username' => 'biz_' . $unique,
            'slug' => 'biz-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    protected function createPagePost(BusinessPage $page, Member $author): Post
    {
        return Post::create([
            'member_id' => $author->id,
            'business_page_id' => $page->id,
            'body' => 'USD post content for ' . $page->page_name,
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Owner can create campaign without End Date and currency defaults to USD.
     */
    public function test_owner_can_create_campaign_without_end_date_using_usd(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $response = $this->actingAs($owner, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Phase6 USD Ad No End Date',
            'post_id' => $post->id,
            'budget' => 50.00,
            'currency' => 'USD',
            'start_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        $campaign = $response->json('campaign');
        $this->assertEquals('Phase6 USD Ad No End Date', $campaign['campaign_name']);
        $this->assertEquals(50.00, (float) $campaign['budget']);
        $this->assertEquals('USD', $campaign['currency']);
        $this->assertNull($campaign['end_at'] ?? null);
    }

    /**
     * 2. New campaign rejects non-USD currency values.
     */
    public function test_new_campaign_rejects_inr_or_invalid_currency(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $response = $this->actingAs($owner, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'Phase6 INR Attempt',
            'post_id' => $post->id,
            'budget' => 500.00,
            'currency' => 'INR', // Not allowed for new campaigns
            'start_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency']);
    }

    /**
     * 3. Campaign delivery does NOT expire based on End Date.
     */
    public function test_campaign_delivery_continues_regardless_of_past_end_at_until_budget_exhausted(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        // Campaign with past end_at but positive budget
        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Phase6 Legacy Date Test',
            'budget' => 100.00,
            'currency' => 'USD',
            'spent_amount' => 0.00,
            'remaining_amount' => 100.00,
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(2), // Past end_at
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Query social feed -> ad must STILL be delivered because date expiration is removed!
        $feed = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $feed->assertStatus(200);

        $sponsored = collect($feed->json('posts'))->where('is_sponsored', true)->first();
        $this->assertNotNull($sponsored, 'Campaign should be delivered regardless of end_at');
        $this->assertEquals($campaign->campaign_id, $sponsored['ad_campaign_id']);
    }

    /**
     * 4. Campaign stops delivery strictly when budget is exhausted.
     */
    public function test_campaign_stops_delivery_strictly_when_budget_is_exhausted(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $viewer = $this->createVerifiedMember('viewer');
        $page = $this->createBusinessPage($owner);
        $post = $this->createPagePost($page, $owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'post_id' => $post->id,
            'campaign_name' => 'Phase6 Exhausted Test',
            'budget' => 50.00,
            'currency' => 'USD',
            'spent_amount' => 50.00,
            'remaining_amount' => 0.00,
            'start_at' => now()->subDays(1),
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $feed = $this->actingAs($viewer, 'member')->getJson('/api/member/socials');
        $feed->assertStatus(200);

        $sponsored = collect($feed->json('posts'))->where('is_sponsored', true);
        $this->assertEmpty($sponsored, 'Exhausted budget campaign must not be served');

        // Verify status auto-transitioned to budget_exhausted
        $this->assertEquals(AdCampaign::STATUS_BUDGET_EXHAUSTED, $campaign->fresh()->status);
    }
}
