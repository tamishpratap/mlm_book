<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdDeposit;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Tests\TestCase;

class AdCampaignFundsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate');
        }
        \App\Models\Setting::set('campaign_platform_fee_percent', 2.50);

        $r1 = \App\Models\AdRewardRule::firstOrCreate(
            ['min_referrals' => 0],
            ['max_referrals' => 5, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r1->update(['reward_amount' => 0.0500, 'is_active' => true]);

        $r2 = \App\Models\AdRewardRule::firstOrCreate(
            ['min_referrals' => 6],
            ['max_referrals' => 14, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r2->update(['reward_amount' => 0.0500, 'is_active' => true]);

        $r3 = \App\Models\AdRewardRule::firstOrCreate(
            ['min_referrals' => 15],
            ['max_referrals' => null, 'reward_amount' => 0.0500, 'is_active' => true]
        );
        $r3->update(['reward_amount' => 0.0500, 'is_active' => true]);

        \App\Models\AdRewardRule::clearCache();
    }

    protected function tearDown(): void
    {
        AdCampaign::query()->where('campaign_name', 'LIKE', 'FundsCamp_%')->delete();
        AdDeposit::query()->where('transaction_reference', 'LIKE', '0x_fund_%')->delete();
        Post::query()->where('body', 'LIKE', 'Post for FundsCamp_%')->delete();
        BusinessPage::query()->where('page_name', 'LIKE', 'FundsPage_%')->delete();
        Member::query()->where('email', 'LIKE', '%_funds@test.com')->delete();

        parent::tearDown();
    }

    protected function getOrCreateAdmin(): Admin
    {
        $admin = Admin::first();
        if (!$admin) {
            $admin = Admin::create([
                'name' => 'Super Admin',
                'email' => 'pro@cpanel.com',
                'password' => bcrypt('123456'),
            ]);
        }
        return $admin;
    }

    protected function createVerifiedMember(string $prefix = 'mem', float $initialAdBalance = 0.00): Member
    {
        $unique = uniqid($prefix . '_');
        return Member::create([
            'name' => 'User ' . $unique,
            'email' => $unique . '_funds@test.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('password123'),
            'ad_balance' => $initialAdBalance,
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner): BusinessPage
    {
        $unique = uniqid('funds_');
        return BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'FundsPage_' . $unique,
            'page_username' => 'funds_' . $unique,
            'slug' => 'funds-' . $unique,
            'category' => 'Technology & Innovation',
            'status' => 'active',
            'visibility' => 'public',
        ]);
    }

    protected function createPost(Member $owner, BusinessPage $page): Post
    {
        return Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Post for FundsCamp_' . uniqid(),
            'type' => 'business_page',
        ]);
    }

    /**
     * 1. Approved deposit creates exact available USD ad funds for Member.
     */
    public function test_approved_deposit_creates_exact_available_ad_funds(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('dep_tester', 0.00);
        $page = $this->createBusinessPage($member);

        // Member submits $100 USD deposit
        $txHash = '0x_fund_' . bin2hex(random_bytes(16));
        $subRes = $this->actingAs($member, 'member')->postJson('/api/member/funds/deposits', [
            'amount_usd' => 100.00,
            'currency' => 'USD',
            'transaction_hash' => $txHash,
        ]);
        $subRes->assertStatus(201);
        $depositId = $subRes->json('deposit.id');

        // Admin approves deposit
        $apprRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/funds/deposits/{$depositId}/approve");
        $apprRes->assertStatus(200);

        // Member ad_balance is credited with exact $100.00 USD
        $this->assertEquals(100.00, (float) $member->fresh()->ad_balance);

        // Member Ads dashboard returns $100.00 available_ad_funds
        $adsRes = $this->actingAs($member, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns");
        $adsRes->assertStatus(200);
        $this->assertEquals(100.00, (float) $adsRes->json('metrics.available_ad_funds'));
        $this->assertEquals(100.00, (float) $adsRes->json('metrics.member_ad_balance'));
    }

    /**
     * 2. Creating an ad campaign atomically validates and reserves budget from member's available ad funds.
     */
    public function test_creating_campaign_reserves_budget_from_member_ad_funds(): void
    {
        $member = $this->createVerifiedMember('camp_creator', 100.00);
        $page = $this->createBusinessPage($member);
        $post = $this->createPost($member, $page);

        // Member creates campaign with $40 budget
        $createRes = $this->actingAs($member, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'FundsCamp_SummerPromo',
            'post_id' => $post->id,
            'budget' => 40.00,
            'currency' => 'USD',
        ]);

        $createRes->assertStatus(201);
        $this->assertTrue($createRes->json('success'));
        $this->assertEquals(40.00, (float) $createRes->json('campaign.budget'));
        $this->assertEquals(40.00, (float) $createRes->json('campaign.remaining_amount'));
        $this->assertEquals(2.50, (float) $createRes->json('campaign.fee_percent'));
        $this->assertEquals(1.00, (float) $createRes->json('campaign.fee_amount'));
        $this->assertEquals(41.00, (float) $createRes->json('campaign.wallet_debit'));

        // Available balance is now $59.00 ($100 - $41 debit)
        $this->assertEquals(59.00, (float) $member->fresh()->ad_balance);

        // Metrics reflect new available and total budget
        $adsRes = $this->actingAs($member, 'member')->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns");
        $adsRes->assertStatus(200);
        $this->assertEquals(59.00, (float) $adsRes->json('metrics.available_ad_funds'));
        $this->assertEquals(40.00, (float) $adsRes->json('metrics.total_budget'));
        $this->assertEquals(40.00, (float) $adsRes->json('metrics.total_remaining'));
    }

    /**
     * 3. Creating campaign with insufficient funds is strictly blocked with 422.
     */
    public function test_creating_campaign_with_insufficient_funds_is_blocked(): void
    {
        $member = $this->createVerifiedMember('broke_member', 30.00);
        $page = $this->createBusinessPage($member);
        $post = $this->createPost($member, $page);

        // Attempt to create $50 campaign with only $30 balance
        $createRes = $this->actingAs($member, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'FundsCamp_TooExpensive',
            'post_id' => $post->id,
            'budget' => 50.00,
            'currency' => 'USD',
        ]);

        $createRes->assertStatus(422);
        $createRes->assertJsonValidationErrors(['budget']);

        // Balance remains untouched at $30.00
        $this->assertEquals(30.00, (float) $member->fresh()->ad_balance);
        $this->assertEquals(0, AdCampaign::where('business_page_id', $page->id)->count());
    }

    /**
     * 4. Updating draft campaign budget adjusts member ad_balance appropriately.
     */
    public function test_updating_draft_campaign_budget_adjusts_member_ad_funds(): void
    {
        $member = $this->createVerifiedMember('update_tester', 100.00);
        $page = $this->createBusinessPage($member);
        $post = $this->createPost($member, $page);

        // 1. Create with $40 budget (debit $41.00) -> balance becomes $59.00
        $createRes = $this->actingAs($member, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'FundsCamp_ToUpdate',
            'post_id' => $post->id,
            'budget' => 40.00,
            'currency' => 'USD',
        ]);
        $createRes->assertStatus(201);
        $campaignId = $createRes->json('campaign.id');
        $this->assertEquals(59.00, (float) $member->fresh()->ad_balance);

        // 2. Increase budget to $70 (new debit $71.75, diff +$30.75) -> balance becomes 59 - 30.75 = 28.25
        $upRes = $this->actingAs($member, 'member')->putJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}", [
            'budget' => 70.00,
        ]);
        $upRes->assertStatus(200);
        $this->assertEquals(28.25, (float) $member->fresh()->ad_balance);

        // 3. Attempt to increase budget to $120 (diff +$51.25, available is only $28.25) -> blocked with 422
        $failRes = $this->actingAs($member, 'member')->putJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}", [
            'budget' => 120.00,
        ]);
        $failRes->assertStatus(422);
        $failRes->assertJsonValidationErrors(['budget']);
        $this->assertEquals(28.25, (float) $member->fresh()->ad_balance);

        // 4. Decrease budget to $50 (new debit $51.25, diff -$20.50) -> refunds $20.50 to balance, balance becomes $48.75
        $downRes = $this->actingAs($member, 'member')->putJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}", [
            'budget' => 50.00,
        ]);
        $downRes->assertStatus(200);
        $this->assertEquals(48.75, (float) $member->fresh()->ad_balance);
    }

    /**
     * 5. Stopping a campaign with unspent budget atomically refunds remaining budget back to member ad_balance.
     */
    public function test_stopping_campaign_refunds_unspent_budget_to_member_ad_funds(): void
    {
        $member = $this->createVerifiedMember('stop_tester', 100.00);
        $page = $this->createBusinessPage($member);
        $post = $this->createPost($member, $page);

        // Create campaign with $40 budget (debit $41.00) -> balance becomes $59.00
        $createRes = $this->actingAs($member, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'FundsCamp_ToStop',
            'post_id' => $post->id,
            'budget' => 40.00,
            'currency' => 'USD',
        ]);
        $createRes->assertStatus(201);
        $campaignId = $createRes->json('campaign.id');
        $this->assertEquals(59.00, (float) $member->fresh()->ad_balance);

        // Member stops campaign
        $stopRes = $this->actingAs($member, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignId}/stop");
        $stopRes->assertStatus(200);
        $this->assertEquals(40.00, (float) $stopRes->json('refunded_amount'));

        // Balance is restored with unspent running budget to $99.00 ($59 + $40 refunded)
        $this->assertEquals(99.00, (float) $member->fresh()->ad_balance);
    }

    /**
     * 6. Admin rejecting a campaign refunds unspent budget back to member ad_balance.
     */
    public function test_admin_rejecting_campaign_refunds_budget_to_member_ad_funds(): void
    {
        $admin = $this->getOrCreateAdmin();
        $member = $this->createVerifiedMember('reject_tester', 100.00);
        $page = $this->createBusinessPage($member);
        $post = $this->createPost($member, $page);

        // Create campaign with $50 budget (debit $51.25) -> balance becomes $48.75
        $createRes = $this->actingAs($member, 'member')->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
            'campaign_name' => 'FundsCamp_ToReject',
            'post_id' => $post->id,
            'budget' => 50.00,
            'currency' => 'USD',
        ]);
        $createRes->assertStatus(201);
        $campaignId = $createRes->json('campaign.id');
        $this->assertEquals(48.75, (float) $member->fresh()->ad_balance);

        // Admin rejects campaign
        $rejectRes = $this->actingAs($admin, 'admin')->postJson("/api/admin/ad-campaigns/{$campaignId}/reject", [
            'rejection_reason' => 'Content does not meet policy guidelines.',
        ]);
        $rejectRes->assertStatus(200);
        $this->assertEquals(50.00, (float) $rejectRes->json('refunded_amount'));

        // Balance is restored with unspent budget to $98.75 ($48.75 + $50 refunded)
        $this->assertEquals(98.75, (float) $member->fresh()->ad_balance);
    }
}