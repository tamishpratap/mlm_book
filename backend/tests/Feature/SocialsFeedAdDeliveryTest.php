<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialsFeedAdDeliveryTest extends TestCase
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

        $r2 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 6],
            ['max_referrals' => 14, 'reward_amount' => 0.0350, 'is_active' => true]
        );
        $r2->update(['reward_amount' => 0.0350, 'is_active' => true]);

        $r3 = AdRewardRule::firstOrCreate(
            ['min_referrals' => 15],
            ['max_referrals' => null, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r3->update(['reward_amount' => 0.0500, 'is_active' => true]);

        AdRewardRule::clearCache();
    }

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 100;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "usr_{$unique}_" . \Illuminate\Support\Str::random(5),
            'email' => "usr_{$unique}_" . \Illuminate\Support\Str::random(5) . "@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    public function test_socials_feed_loads_successfully_without_class_not_found_error(): void
    {
        $member = $this->createMember([
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($member, 'member')
            ->getJson('/api/member/socials');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_verified_member_receives_eligible_sponsored_campaigns_in_feed(): void
    {
        $viewer = $this->createMember([
            'mobile_verified_at' => now(),
        ]);

        $author = $this->createMember();
        $businessPage = BusinessPage::create([
            'member_id' => $author->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp_' . \Illuminate\Support\Str::random(5),
            'slug' => 'acme-corp-' . \Illuminate\Support\Str::random(5),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $author->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Check out our new products!',
            'type' => 'post',
        ]);

        $campaign = AdCampaign::create([
            'campaign_id' => 'CAMP-' . uniqid(),
            'business_page_id' => $businessPage->id,
            'member_id' => $author->id,
            'post_id' => $post->id,
            'campaign_name' => 'Tech Launch 2026',
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $deliveryService = app(AdDeliveryService::class);
        $campaigns = $deliveryService->getEligibleCampaigns(5, $viewer);

        $this->assertNotEmpty($campaigns);
        $this->assertTrue($campaigns->contains('id', $campaign->id));

        $formatted = $deliveryService->formatSponsoredPost($campaign, $viewer);
        $this->assertNotNull($formatted);
        $this->assertTrue($formatted->is_sponsored);
        $this->assertEquals(0.0250, $formatted->ad_campaign['reward_amount_usd']);
    }

    public function test_unverified_member_does_not_receive_ad_campaigns(): void
    {
        $unverifiedViewer = $this->createMember([
            'mobile_verified_at' => null,
        ]);

        $author = $this->createMember();
        $businessPage = BusinessPage::create([
            'member_id' => $author->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp_' . \Illuminate\Support\Str::random(5),
            'slug' => 'acme-corp-' . \Illuminate\Support\Str::random(5),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $author->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Check out our new products!',
            'type' => 'post',
        ]);

        AdCampaign::create([
            'campaign_id' => 'CAMP-' . uniqid(),
            'business_page_id' => $businessPage->id,
            'member_id' => $author->id,
            'post_id' => $post->id,
            'campaign_name' => 'Tech Launch 2026',
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $deliveryService = app(AdDeliveryService::class);
        $campaigns = $deliveryService->getEligibleCampaigns(5, $unverifiedViewer);

        $this->assertNotEmpty($campaigns, 'Unverified member should receive eligible ad campaigns for viewing.');
    }

    public function test_exhausted_campaign_is_automatically_marked_and_excluded(): void
    {
        $viewer = $this->createMember([
            'mobile_verified_at' => now(),
        ]);

        $author = $this->createMember();
        $businessPage = BusinessPage::create([
            'member_id' => $author->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp_' . \Illuminate\Support\Str::random(5),
            'slug' => 'acme-corp-' . \Illuminate\Support\Str::random(5),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $post = Post::create([
            'member_id' => $author->id,
            'business_page_id' => $businessPage->id,
            'body' => 'Check out our new products!',
            'type' => 'post',
        ]);

        // Budget remaining is $0.010 which is below minimum active reward ($0.0250)
        $campaign = AdCampaign::create([
            'campaign_id' => 'CAMP-' . uniqid(),
            'business_page_id' => $businessPage->id,
            'member_id' => $author->id,
            'post_id' => $post->id,
            'campaign_name' => 'Low Budget Ad',
            'budget' => 50.00,
            'fee_percent' => 2.50,
            'fee_amount' => 1.25,
            'wallet_debit' => 51.25,
            'remaining_amount' => 0.010,
            'spent_amount' => 49.99,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        $deliveryService = app(AdDeliveryService::class);
        $campaigns = $deliveryService->getEligibleCampaigns(5, $viewer);

        $this->assertEmpty($campaigns);
        $this->assertEquals(AdCampaign::STATUS_BUDGET_EXHAUSTED, $campaign->fresh()->status);
    }
}
