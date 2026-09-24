<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignPlatformFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_and_update_campaign_platform_fee_percent(): void
    {
        $admin = Admin::create([
            'name' => 'Ad Admin',
            'email' => 'adadmin@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($admin, 'admin');

        Setting::set('campaign_platform_fee_percent', 2.50);

        // Read default settings
        $response = $this->getJson('/api/admin/ad-campaigns/settings');
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'settings' => [
                    'campaign_platform_fee_percent' => 2.5,
                    'currency' => 'USD',
                ],
            ]);

        // Update fee to 3.5%
        $updateRes = $this->postJson('/api/admin/ad-campaigns/settings', [
            'campaign_platform_fee_percent' => 3.5,
        ]);

        $updateRes->assertOk()
            ->assertJson([
                'success' => true,
                'settings' => [
                    'campaign_platform_fee_percent' => 3.5,
                ],
            ]);

        $this->assertSame('3.5', (string) Setting::get('campaign_platform_fee_percent'));
    }

    public function test_campaign_creation_calculates_platform_fee_and_debits_total_from_wallet(): void
    {
        Setting::set('campaign_platform_fee_percent', '2.50', 'ads');

        $owner = $this->createMember(['ad_balance' => 100.00]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns", [
            'campaign_name' => 'Phase 12 Promo Campaign',
            'budget' => 30.00,
            'currency' => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'campaign' => [
                    'campaign_name' => 'Phase 12 Promo Campaign',
                    'budget' => '30.00',
                    'currency' => 'USD',
                    'fee_percent' => '2.50',
                    'fee_amount' => '0.75',
                    'wallet_debit' => '30.75',
                    'remaining_amount' => '30.00',
                    'spent_amount' => '0.00',
                ],
            ]);

        // Member wallet must be debited exactly $30.75 (100.00 - 30.75 = 69.25)
        $owner->refresh();
        $this->assertSame(69.25, (float) $owner->ad_balance);

        // Verify database campaign record
        $campaign = AdCampaign::latest()->first();
        $this->assertNotNull($campaign);
        $this->assertSame(30.00, (float) $campaign->budget);
        $this->assertSame(2.50, (float) $campaign->fee_percent);
        $this->assertSame(0.75, (float) $campaign->fee_amount);
        $this->assertSame(30.75, (float) $campaign->wallet_debit);
        $this->assertSame(30.00, (float) $campaign->remaining_amount);
        $this->assertSame('USD', $campaign->currency);
    }

    public function test_insufficient_funds_blocks_campaign_creation_with_zero_deduction(): void
    {
        Setting::set('campaign_platform_fee_percent', '2.50', 'ads');

        // Member has exactly $30.00 available. Total required for $30 budget + 2.5% fee is $30.75.
        $owner = $this->createMember(['ad_balance' => 30.00]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns", [
            'campaign_name' => 'Insufficient Funds Test',
            'budget' => 30.00,
            'currency' => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);

        // Member balance must remain untouched
        $owner->refresh();
        $this->assertSame(30.00, (float) $owner->ad_balance);
        $this->assertSame(0, AdCampaign::count());
    }

    public function test_historical_campaign_snapshot_remains_immutable_when_admin_changes_fee(): void
    {
        // 1. Create first campaign with 2.5% fee
        Setting::set('campaign_platform_fee_percent', '2.50', 'ads');

        $owner = $this->createMember(['ad_balance' => 200.00]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $res1 = $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns", [
            'campaign_name' => 'Historical Campaign at 2.5%',
            'budget' => 30.00,
        ]);
        $res1->assertStatus(201);

        $campaign1 = AdCampaign::where('campaign_name', 'Historical Campaign at 2.5%')->first();
        $this->assertSame(2.50, (float) $campaign1->fee_percent);
        $this->assertSame(0.75, (float) $campaign1->fee_amount);
        $this->assertSame(30.75, (float) $campaign1->wallet_debit);

        // 2. Admin changes fee to 5.0%
        Setting::set('campaign_platform_fee_percent', '5.00', 'ads');

        // 3. Create second campaign under 5.0% fee
        $res2 = $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns", [
            'campaign_name' => 'New Campaign at 5.0%',
            'budget' => 40.00,
        ]);
        $res2->assertStatus(201);

        $campaign2 = AdCampaign::where('campaign_name', 'New Campaign at 5.0%')->first();
        $this->assertSame(5.00, (float) $campaign2->fee_percent);
        $this->assertSame(2.00, (float) $campaign2->fee_amount);
        $this->assertSame(42.00, (float) $campaign2->wallet_debit);

        // 4. Verify historical campaign 1 was NOT modified
        $campaign1->refresh();
        $this->assertSame(2.50, (float) $campaign1->fee_percent);
        $this->assertSame(0.75, (float) $campaign1->fee_amount);
        $this->assertSame(30.75, (float) $campaign1->wallet_debit);
        $this->assertSame(30.00, (float) $campaign1->budget);

        // Total debited: 30.75 + 42.00 = 72.75 -> Remaining: 200 - 72.75 = 127.25
        $owner->refresh();
        $this->assertSame(127.25, (float) $owner->ad_balance);
    }

    public function test_stopping_campaign_refunds_only_unspent_running_budget(): void
    {
        Setting::set('campaign_platform_fee_percent', '2.50', 'ads');

        $owner = $this->createMember(['ad_balance' => 100.00]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $createRes = $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns", [
            'campaign_name' => 'Refundable Campaign',
            'budget' => 30.00,
        ]);
        $createRes->assertStatus(201);

        $campaign = AdCampaign::latest()->first();

        // 100 - 30.75 = 69.25
        $owner->refresh();
        $this->assertSame(69.25, (float) $owner->ad_balance);

        // Simulate some spending: $10 spent out of $30 budget -> $20 remaining
        $campaign->update([
            'spent_amount' => 10.00,
            'remaining_amount' => 20.00,
        ]);

        // Stop campaign
        $stopRes = $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns/{$campaign->campaign_id}/stop");
        $stopRes->assertOk()
            ->assertJson([
                'success' => true,
                'refunded_amount' => 20.00,
            ]);

        // Member gets back unspent running budget ($20.00), platform fee ($0.75) is kept by platform
        $owner->refresh();
        $this->assertSame(89.25, (float) $owner->ad_balance); // 69.25 + 20.00 = 89.25
    }

    public function test_admin_rejecting_campaign_refunds_unspent_budget(): void
    {
        Setting::set('campaign_platform_fee_percent', '2.50', 'ads');

        $owner = $this->createMember(['ad_balance' => 100.00]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $this->postJson("/api/member/business-pages/{$businessPage->slug}/ad-campaigns", [
            'campaign_name' => 'Rejected Campaign',
            'budget' => 30.00,
        ])->assertStatus(201);

        $campaign = AdCampaign::latest()->first();

        $admin = Admin::create([
            'name' => 'Ad Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'secret123',
        ]);

        $this->actingAs($admin, 'admin');

        $rejectRes = $this->postJson("/api/admin/ad-campaigns/{$campaign->id}/reject", [
            'rejection_reason' => 'Content does not meet platform advertising guidelines.',
        ]);

        $rejectRes->assertOk();

        // Member gets refunded the unspent $30.00 budget: 69.25 + 30.00 = 99.25
        $owner->refresh();
        $this->assertSame(99.25, (float) $owner->ad_balance);
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Ad Advertiser',
            'email' => 'advertiser-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'ad_balance' => 0.00,
        ], $attributes));
    }

    private function createBusinessPage(Member $owner, array $attributes = []): BusinessPage
    {
        return BusinessPage::create(array_merge([
            'member_id' => $owner->id,
            'page_name' => 'Biz Corp ' . uniqid(),
            'page_username' => 'bizcorp_' . strtolower(uniqid()),
            'slug' => 'biz-corp-' . strtolower(uniqid()),
            'category' => 'Crypto, Forex & FinTech MLM',
            'description' => 'Business page description for ad testing.',
            'email' => 'corp@example.com',
            'phone' => '+919876543210',
            'country' => 'India',
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'address' => '100 Ad Street',
            'visibility' => 'public',
            'status' => 'active',
        ], $attributes));
    }
}
