<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Services\CampaignBudgetDepletionService;
use App\Services\EventRewardResolver;
use App\Services\RewardRuleValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminEventControlCenterAndFinalSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private Admin $superAdmin;
    private Admin $restrictedAdmin;
    private Member $creator;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Super Admin
        $this->superAdmin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->superAdmin->roles()->sync([$superRole->id]);

        // 2. Restricted Admin without view-events
        $this->restrictedAdmin = Admin::create([
            'name' => 'Restricted Admin',
            'email' => 'restricted_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $restrictedRole = Role::firstOrCreate(['slug' => 'restricted-role'], ['name' => 'Restricted Role']);
        $this->restrictedAdmin->roles()->sync([$restrictedRole->id]);

        // 3. Verified Creator
        $this->creator = Member::create([
            'name' => 'Event Creator',
            'user_id' => 'creator_' . uniqid(),
            'email' => 'creator_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'ad_balance' => 1000.00,
        ]);

        // 4. Verified Member
        $this->member = Member::create([
            'name' => 'Verified Member',
            'user_id' => 'member_' . uniqid(),
            'email' => 'member_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
        ]);

        // 5. Active Event Reward Rules (3-tier valid set: 0-5 => 0.025, 6-14 => 0.035, 15+ => 0.050)
        AdRewardRule::clearCache();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'currency' => 'USD',
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'currency' => 'USD',
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'currency' => 'USD',
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();
    }

    private function createEventWithCampaign(float $budget = 50.00, string $status = AdCampaign::STATUS_ACTIVE): array
    {
        $event = Event::create([
            'title' => 'Tech Summit 2026 ' . uniqid(),
            'slug' => 'tech-summit-' . uniqid(),
            'description' => 'Annual premier tech summit.',
            'category' => 'Technology',
            'event_type' => 'offline',
            'location_city' => 'Singapore',
            'location_country' => 'Singapore',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(8),
            'created_by' => $this->creator->id,
            'organizer_id' => $this->creator->id,
            'status' => 'published',
            'is_free' => false,
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Campaign for ' . $event->title,
            'budget' => $budget,
            'additional_funding' => 0.00,
            'total_funded' => $budget,
            'spent_amount' => 0.00,
            'remaining_amount' => $budget,
            'status' => $status,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'is_active' => ($status === AdCampaign::STATUS_ACTIVE),
        ]);

        return [$event, $campaign];
    }

    // =========================================================================
    // SECTION 61: REQUIRED ADMIN CONTROL CENTER TESTS (1 - 12)
    // =========================================================================

    public function test_01_admin_can_access_event_control_center_api(): void
    {
        $this->createEventWithCampaign(100.00);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'campaigns' => ['data', 'total', 'current_page'],
            'metrics' => [
                'total_event_campaigns',
                'active_campaigns',
                'paused_campaigns',
                'stopped_campaigns',
                'total_budget_funded',
                'total_spent',
                'total_remaining',
            ],
            'rule_health',
        ]);
    }

    public function test_02_unauthenticated_user_cannot_access_event_control_center(): void
    {
        $this->getJson('/api/admin/event-campaigns')->assertStatus(401);
    }

    public function test_03_member_cannot_access_admin_event_control_center(): void
    {
        $this->actingAs($this->member, 'member')
            ->getJson('/api/admin/event-campaigns')
            ->assertStatus(401);
    }

    public function test_04_restricted_admin_without_permissions_is_denied(): void
    {
        $response = $this->actingAs($this->restrictedAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns');

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_05_admin_can_view_campaigns_with_accurate_metrics(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(100.00, AdCampaign::STATUS_ACTIVE);

        // Record 1 interested attendee and 1 reward
        EventResponse::create([
            'event_id' => $event->id,
            'member_id' => $this->member->id,
            'response' => 'interested',
        ]);
        AdReward::create([
            'qualifying_event_id' => $event->id,
            'ad_campaign_id' => $campaign->id,
            'member_id' => $this->member->id,
            'reward_amount_usd' => 0.0500,
            'status' => AdReward::STATUS_CREDITED,
            'rule_min_referrals' => 6,
            'rule_max_referrals' => 14,
        ]);
        $campaign->update([
            'spent_amount' => 0.0500,
            'remaining_amount' => 99.9500,
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('metrics.total_event_campaigns'));
        $this->assertEquals(1, $response->json('metrics.active_campaigns'));
        $this->assertEquals(100.00, $response->json('metrics.total_budget_funded'));
        $this->assertEquals(0.05, $response->json('metrics.total_spent'));
        $this->assertEquals(99.95, $response->json('metrics.total_remaining'));
    }

    public function test_06_admin_filters_and_pagination_work(): void
    {
        [$eventA, $campA] = $this->createEventWithCampaign(50.00, AdCampaign::STATUS_ACTIVE);
        [$eventB, $campB] = $this->createEventWithCampaign(20.00, AdCampaign::STATUS_PAUSED);

        // Search by event A title
        $resSearch = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns?q=' . urlencode($eventA->title));
        $resSearch->assertStatus(200);
        $this->assertCount(1, $resSearch->json('campaigns.data'));
        $this->assertEquals($campA->id, $resSearch->json('campaigns.data.0.id'));

        // Filter by paused status
        $resPaused = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns?status=paused');
        $resPaused->assertStatus(200);
        $this->assertCount(1, $resPaused->json('campaigns.data'));
        $this->assertEquals($campB->id, $resPaused->json('campaigns.data.0.id'));
    }

    public function test_07_admin_can_inspect_detailed_campaign_with_reconciliation(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(75.00, AdCampaign::STATUS_ACTIVE);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson("/api/admin/event-campaigns/{$campaign->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('campaign.id', $campaign->id);
        $this->assertEquals(75.0000, $response->json('campaign.financials.total_funded'));
        $response->assertJsonPath('campaign.financials.reconciliation.is_balanced', true);
        $response->assertJsonPath('campaign.creator.is_mobile_verified', true);
    }

    public function test_08_admin_can_view_participants_list(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        EventResponse::create([
            'event_id' => $event->id,
            'member_id' => $this->member->id,
            'response' => 'interested',
        ]);
        AdReward::create([
            'qualifying_event_id' => $event->id,
            'ad_campaign_id' => $campaign->id,
            'member_id' => $this->member->id,
            'reward_amount_usd' => 0.0250,
            'status' => AdReward::STATUS_CREDITED,
            'rule_min_referrals' => 0,
            'rule_max_referrals' => 5,
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson("/api/admin/event-campaigns/{$campaign->id}/participants");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('participants.data'));
        $this->assertEquals($this->member->id, $response->json('participants.data.0.member_id'));
        $this->assertTrue($response->json('participants.data.0.is_rewarded'));
        $this->assertEquals(0.0250, $response->json('participants.data.0.reward.reward_amount_usd'));
    }

    public function test_09_admin_can_pause_an_active_campaign(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00, AdCampaign::STATUS_ACTIVE);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson("/api/admin/event-campaigns/{$campaign->id}/pause");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'paused');
        $this->assertFalse($response->json('is_active'));
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $campaign->fresh()->status);
    }

    public function test_10_admin_can_resume_a_paused_campaign_with_sufficient_budget(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00, AdCampaign::STATUS_PAUSED);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson("/api/admin/event-campaigns/{$campaign->id}/resume");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'active');
        $this->assertTrue($response->json('is_active'));
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $campaign->fresh()->status);
    }

    public function test_11_admin_resume_fails_if_budget_is_exhausted(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00, AdCampaign::STATUS_PAUSED);
        $campaign->update([
            'spent_amount' => 49.9800,
            'remaining_amount' => 0.0200, // less than minReward of 0.0250
        ]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson("/api/admin/event-campaigns/{$campaign->id}/resume");

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('less than minimum reward', $response->json('message'));
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $campaign->fresh()->status);
    }

    public function test_12_admin_can_stop_an_event_campaign(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00, AdCampaign::STATUS_ACTIVE);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->postJson("/api/admin/event-campaigns/{$campaign->id}/stop");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'stopped');
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $campaign->fresh()->status);
    }

    public function test_13_non_event_campaign_returns_404_on_event_campaigns_endpoints(): void
    {
        $businessPage = BusinessPage::create([
            'member_id' => $this->creator->id,
            'page_name' => 'Biz Page ' . uniqid(),
            'page_username' => 'biz_' . uniqid(),
            'slug' => 'biz-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
        ]);

        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Business Ad Campaign',
            'budget' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->superAdmin, 'admin')
            ->getJson("/api/admin/event-campaigns/{$bizCampaign->id}")
            ->assertStatus(404);
    }

    public function test_14_admin_event_data_does_not_expose_secrets(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson("/api/admin/event-campaigns/{$campaign->id}");

        $response->assertStatus(200);
        $json = json_encode($response->json());
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('private_key', $json);
        $this->assertStringNotContainsString('seed_phrase', $json);
    }

    public function test_15_control_center_connects_to_reward_rules_health(): void
    {
        $response = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns');

        $response->assertStatus(200);
        $this->assertTrue($response->json('rule_health.is_valid'));
        $this->assertEquals(3, $response->json('rule_health.active_rules_count'));
        $this->assertEquals(0.0250, $response->json('rule_health.min_reward_usd'));
        $this->assertEquals(0.0500, $response->json('rule_health.max_reward_usd'));
    }

    public function test_16_business_ads_admin_metrics_isolate_business_ads(): void
    {
        // 1 Event Campaign
        $this->createEventWithCampaign(50.00);

        // 1 Business Page Campaign
        $businessPage = BusinessPage::create([
            'member_id' => $this->creator->id,
            'page_name' => 'Biz Page ' . uniqid(),
            'page_username' => 'biz_' . uniqid(),
            'slug' => 'biz-' . uniqid(),
            'category' => 'Technology',
            'status' => 'active',
        ]);
        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $businessPage->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Business Ad Campaign',
            'budget' => 25.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $resBiz = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/ad-campaigns');

        $resBiz->assertStatus(200);
        // Business Ads manager should only count the 1 business ad
        $this->assertEquals(1, $resBiz->json('metrics.total_campaigns'));
        $this->assertEquals(25.00, $resBiz->json('metrics.total_budget'));
    }

    // =========================================================================
    // SECTION 62: REQUIRED SECURITY REGRESSION TESTS (1 - 14)
    // =========================================================================

    public function test_17_no_idor_creator_cannot_access_other_creator_analytics(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        // Another member who is not the organizer
        $attacker = Member::create([
            'name' => 'Attacker',
            'user_id' => 'attacker_' . uniqid(),
            'email' => 'attacker_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($attacker, 'member')
            ->getJson("/api/member/events/{$event->id}/campaign/analytics");

        $response->assertStatus(403);
    }

    public function test_18_no_unverified_member_bypass(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        $unverified = Member::create([
            'name' => 'Unverified Member',
            'user_id' => 'unverified_' . uniqid(),
            'email' => 'unverified_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => null, // Unverified
        ]);

        $response = $this->actingAs($unverified, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        $response->assertStatus(403);
        $this->assertEquals(0.00, $unverified->fresh()->reward_balance);
        $this->assertEquals(0.00, $campaign->fresh()->spent_amount);
    }

    public function test_19_no_unverified_creator_bypass(): void
    {
        $unverifiedCreator = Member::create([
            'name' => 'Unverified Creator',
            'user_id' => 'unverified_c_' . uniqid(),
            'email' => 'unverified_c_' . uniqid() . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => null,
        ]);

        $event = Event::create([
            'title' => 'Unverified Event ' . uniqid(),
            'slug' => 'unverified-event-' . uniqid(),
            'description' => 'Test event',
            'created_by' => $unverifiedCreator->id,
            'organizer_id' => $unverifiedCreator->id,
            'start_date' => now()->addDays(5),
            'status' => 'published',
        ]);

        $response = $this->actingAs($unverifiedCreator, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign", [
                'budget' => 50.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_20_no_fake_referral_or_reward_manipulation(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        // Member has 0 referrals, qualifying for $0.0250.
        // Adversary injects fake referral count and reward amount in request payload.
        $maliciousPayload = [
            'referral_count' => 9999,
            'reward_amount' => 500.00,
            'rule_id' => 999,
            'tier' => '15+',
        ];

        $response = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest", $maliciousPayload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        // Server must resolve authoritative reward ($0.0250), ignoring all client inputs
        $response->assertJsonPath('reward_amount_usd', 0.0250);
        $this->assertEquals(0.0250, (float) $this->member->fresh()->reward_balance);
        $this->assertEquals(0.0250, (float) $campaign->fresh()->spent_amount);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $this->member->id)->count());
    }

    public function test_21_no_duplicate_reward_or_replay_double_credit(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        // First click -> success
        $res1 = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res1->assertStatus(200);
        $this->assertFalse($res1->json('already_rewarded'));

        // Immediate replay / second click
        $res2 = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('already_rewarded'));

        // Balance must be credited exactly once
        $this->assertEquals(0.0250, (float) $this->member->fresh()->reward_balance);
        $this->assertEquals(0.0250, (float) $campaign->fresh()->spent_amount);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('member_id', $this->member->id)->count());
    }

    // =========================================================================
    // CONCURRENCY & RACE AUDITS (SECTIONS 20 - 25)
    // =========================================================================

    public function test_22_concurrent_overspending_audit_no_negative_budget(): void
    {
        // Campaign with remaining budget of only $0.0300 (enough for exactly ONE $0.0250 reward)
        [$event, $campaign] = $this->createEventWithCampaign(50.00);
        $campaign->update([
            'spent_amount' => 49.9700,
            'remaining_amount' => 0.0300,
        ]);

        $m1 = Member::create(['name' => 'M1', 'user_id' => 'u1_' . uniqid(), 'email' => 'm1_' . uniqid() . '@ex.com', 'password' => 'pw', 'mobile_verified_at' => now()]);
        $m2 = Member::create(['name' => 'M2', 'user_id' => 'u2_' . uniqid(), 'email' => 'm2_' . uniqid() . '@ex.com', 'password' => 'pw', 'mobile_verified_at' => now()]);

        // Simulate concurrent attempts
        $res1 = $this->actingAs($m1, 'member')->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $res2 = $this->actingAs($m2, 'member')->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        $freshCamp = $campaign->fresh();
        $this->assertGreaterThanOrEqual(0.00, (float) $freshCamp->remaining_amount);
        $this->assertEquals(1, AdReward::where('ad_campaign_id', $campaign->id)->where('status', AdReward::STATUS_CREDITED)->count());
        $this->assertEquals(0.0050, round((float) $freshCamp->remaining_amount, 4));
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $freshCamp->status); // Auto-stopped because 0.0050 < 0.0250
    }

    public function test_23_wallet_concurrency_audit_no_lost_updates(): void
    {
        [$eventA, $campA] = $this->createEventWithCampaign(50.00);
        [$eventB, $campB] = $this->createEventWithCampaign(50.00);

        // Member claims rewards on two different events
        $resA = $this->actingAs($this->member, 'member')->postJson("/api/member/events/{$eventA->id}/campaign/qualify-interest");
        $resB = $this->actingAs($this->member, 'member')->postJson("/api/member/events/{$eventB->id}/campaign/qualify-interest");

        $resA->assertStatus(200);
        $resB->assertStatus(200);

        // Wallet balance must reflect exactly 0.0250 + 0.0250 = 0.0500 without lost update
        $this->assertEquals(0.0500, (float) $this->member->fresh()->reward_balance);
    }

    public function test_24_add_funds_and_reward_concurrency_reconciles_cleanly(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        // 1. Reward processed
        $this->actingAs($this->member, 'member')->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        // 2. Creator adds $25.00 funds to same campaign
        $this->actingAs($this->creator, 'member')->postJson("/api/member/events/{$event->id}/campaign/add-funds", [
            'amount' => 25.00,
        ]);

        $fresh = $campaign->fresh();
        $this->assertEquals(50.00, (float) $fresh->budget);
        $this->assertEquals(25.00, (float) $fresh->additional_funding);
        $this->assertEquals(75.00, (float) $fresh->total_funded);
        $this->assertEquals(0.0250, (float) $fresh->spent_amount);
        $this->assertEquals(74.9750, (float) $fresh->remaining_amount);
        $this->assertEquals(round((float) $fresh->total_funded, 4), round((float) $fresh->spent_amount + (float) $fresh->remaining_amount, 4));
    }

    public function test_25_rule_change_race_uses_live_qualification_configuration(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        // Admin updates baseline reward amount from 0.0250 to 0.0350 while member has modal open
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->where('min_referrals', 0)->update([
            'reward_amount' => 0.0350,
        ]);
        AdRewardRule::clearCache();

        $response = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        $response->assertStatus(200);
        // Live qualification time reward is applied
        $response->assertJsonPath('reward_amount_usd', 0.0350);
        $this->assertEquals(0.0350, (float) $this->member->fresh()->reward_balance);
    }

    public function test_26_verification_race_member_unverified_at_qualification_is_denied(): void
    {
        [$event, $campaign] = $this->createEventWithCampaign(50.00);

        // Member loses verification before clicking Interested
        $this->member->update(['mobile_verified_at' => null]);

        $response = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        $response->assertStatus(403);
        $this->assertEquals(0.00, (float) $this->member->fresh()->reward_balance);
    }

    // =========================================================================
    // FULL END-TO-END SCENARIOS & REGRESSIONS (SECTIONS 44 - 48, 63 - 64)
    // =========================================================================

    public function test_27_complete_end_to_end_paid_event_lifecycle(): void
    {
        // 1. Creator creates Paid Event & Campaign
        $event = Event::create([
            'title' => 'E2E Mega Conference ' . uniqid(),
            'slug' => 'e2e-conf-' . uniqid(),
            'description' => 'End-to-end full system test conference.',
            'category' => 'Technology',
            'event_type' => 'online',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
            'created_by' => $this->creator->id,
            'organizer_id' => $this->creator->id,
            'status' => 'published',
            'is_free' => false,
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'E2E Campaign',
            'budget' => 10.00,
            'total_funded' => 10.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 10.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'is_active' => true,
        ]);

        // 2. Member views & earns reward
        $qualRes = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");
        $qualRes->assertStatus(200);
        $qualRes->assertJsonPath('success', true);
        $this->assertEquals(0.0250, (float) $this->member->fresh()->reward_balance);

        // 3. Creator checks analytics
        $analyticsRes = $this->actingAs($this->creator, 'member')
            ->getJson("/api/member/events/{$event->id}/campaign/analytics");
        $analyticsRes->assertStatus(200);
        $this->assertEquals(1, $analyticsRes->json('participant_metrics.rewarded_participants'));
        $this->assertEquals(0.0250, $analyticsRes->json('budget_metrics.spent_amount'));

        // 4. Exhaust campaign
        $campaign->update([
            'spent_amount' => 9.9900,
            'remaining_amount' => 0.0100, // below 0.0250
            'status' => AdCampaign::STATUS_STOPPED,
            'is_active' => false,
        ]);

        // 5. Creator adds funds to SAME campaign
        $addFundsRes = $this->actingAs($this->creator, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/add-funds", ['amount' => 15.00]);
        $addFundsRes->assertStatus(200);
        $this->assertEquals($campaign->id, $addFundsRes->json('campaign.id'));

        // 6. Creator reactivates SAME campaign
        $reactivateRes = $this->actingAs($this->creator, 'member')
            ->postJson("/api/member/events/{$event->id}/campaign/reactivate");
        $reactivateRes->assertStatus(200);
        $this->assertEquals('active', $reactivateRes->json('campaign.status'));
        $this->assertEquals($campaign->id, $reactivateRes->json('campaign.id'));

        // 7. Admin inspects campaign in Control Center
        $adminRes = $this->actingAs($this->superAdmin, 'admin')
            ->getJson("/api/admin/event-campaigns/{$campaign->id}");
        $adminRes->assertStatus(200);
        $this->assertTrue($adminRes->json('campaign.financials.reconciliation.is_balanced'));
        $this->assertEquals(1, $adminRes->json('campaign.participation.rewarded_count'));
    }

    public function test_28_business_ads_and_event_campaigns_run_side_by_side_without_cross_interference(): void
    {
        // Business Page Ad
        $page = BusinessPage::create([
            'member_id' => $this->creator->id,
            'page_name' => 'Parallel Page',
            'page_username' => 'parallel_page',
            'slug' => 'parallel-page',
            'category' => 'Technology',
            'status' => 'active',
        ]);
        $bizCamp = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $page->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Biz Camp A',
            'budget' => 30.00,
            'total_funded' => 30.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 30.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        // Event Campaign
        [$event, $eventCamp] = $this->createEventWithCampaign(40.00);

        // Member claims reward on event campaign
        $this->actingAs($this->member, 'member')->postJson("/api/member/events/{$event->id}/campaign/qualify-interest");

        // Business Ad budget must be 100% untouched
        $this->assertEquals(30.00, (float) $bizCamp->fresh()->remaining_amount);
        $this->assertEquals(0.00, (float) $bizCamp->fresh()->spent_amount);

        // Event Ad budget depleted exactly $0.0250
        $this->assertEquals(39.9750, (float) $eventCamp->fresh()->remaining_amount);
        $this->assertEquals(0.0250, (float) $eventCamp->fresh()->spent_amount);
    }

    public function test_29_free_events_operate_normally_without_campaign_or_rewards(): void
    {
        $freeEvent = Event::create([
            'title' => 'Community Picnic ' . uniqid(),
            'slug' => 'community-picnic-' . uniqid(),
            'description' => 'Free community picnic.',
            'category' => 'Social',
            'event_type' => 'offline',
            'start_date' => now()->addDays(3),
            'created_by' => $this->creator->id,
            'organizer_id' => $this->creator->id,
            'status' => 'published',
            'is_free' => true,
        ]);

        // RSVP on free event
        $rsvpRes = $this->actingAs($this->member, 'member')
            ->postJson("/api/member/events/{$freeEvent->id}/respond", ['response' => 'going']);
        $rsvpRes->assertStatus(200);

        // No rewards or campaigns created
        $this->assertNull($freeEvent->campaign);
        $this->assertEquals(0, AdReward::where('ad_campaign_id', 999999)->count());
        $this->assertEquals(0.00, (float) $this->member->fresh()->reward_balance);
    }

    public function test_30_admin_event_control_center_eager_loading_and_zero_state_resilience(): void
    {
        // 1. Zero state test
        AdCampaign::where('campaign_type', AdCampaign::TYPE_EVENT)->delete();
        $responseZero = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns');

        $responseZero->assertStatus(200);
        $responseZero->assertJsonPath('success', true);
        $responseZero->assertJsonPath('campaigns.data', []);
        $responseZero->assertJsonPath('metrics.total_event_campaigns', 0);

        // 2. Create Event with cover_photo and organizer (schema exact)
        $event = Event::create([
            'title' => 'Resilience Summit ' . uniqid(),
            'slug' => 'resilience-summit-' . uniqid(),
            'description' => 'Verifying eager loading.',
            'category' => 'Technology',
            'event_type' => 'online',
            'cover_photo' => 'uploads/events/covers/sample.png',
            'location_city' => 'Singapore',
            'location_country' => 'Singapore',
            'start_date' => now()->addDays(5),
            'organizer_id' => $this->creator->id,
            'status' => 'published',
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Campaign for ' . $event->title,
            'budget' => 75.00,
            'total_funded' => 75.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 75.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'is_active' => true,
        ]);

        // 3. Admin fetches event campaigns with filters
        $resFilter = $this->actingAs($this->superAdmin, 'admin')
            ->getJson('/api/admin/event-campaigns?q=Resilience&status=active&budget_state=funded&preset=7d');

        $resFilter->assertStatus(200);
        $resFilter->assertJsonPath('success', true);
        $this->assertCount(1, $resFilter->json('campaigns.data'));

        $campaignData = $resFilter->json('campaigns.data.0');
        $this->assertEquals($campaign->id, $campaignData['id']);
        $this->assertEquals($event->title, $campaignData['event']['title']);
        $this->assertNotNull($campaignData['event']['cover_image']);
        $this->assertEquals($this->creator->name, $campaignData['creator']['name']);

        // 4. Detail inspection
        $resShow = $this->actingAs($this->superAdmin, 'admin')
            ->getJson("/api/admin/event-campaigns/{$campaign->id}");
        $resShow->assertStatus(200);
        $resShow->assertJsonPath('success', true);
        $this->assertNotNull($resShow->json('campaign.event.cover_image'));
    }
}
