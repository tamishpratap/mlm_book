<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\AdReward;
use App\Models\AdRewardRule;
use App\Models\BusinessPage;
use App\Models\Event;
use App\Models\Follower;
use App\Models\Friendship;
use App\Models\Member;
use App\Services\EventRewardResolver;
use App\Services\RewardRuleResolver;
use App\Services\RewardRuleValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventRewardResolverTest extends TestCase
{
    use RefreshDatabase;

    protected EventRewardResolver $eventResolver;
    protected RewardRuleResolver $ruleResolver;
    protected Member $organizer;
    protected Member $member;
    protected Event $event;
    protected AdCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        AdRewardRule::query()->delete();
        AdRewardRule::clearCache();

        $this->ruleResolver = app(RewardRuleResolver::class);
        $this->eventResolver = app(EventRewardResolver::class);

        // Create organizer
        $this->organizer = Member::create([
            'name' => 'Event Organizer',
            'user_id' => 'org_' . Str::random(8),
            'email' => 'org_' . Str::random(8) . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'ad_balance' => 500.00,
        ]);

        // Create member whose reward will be resolved
        $this->member = Member::create([
            'name' => 'Target Member',
            'user_id' => 'target_' . Str::random(8),
            'email' => 'target_' . Str::random(8) . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'ad_balance' => 50.00,
        ]);

        // Create published event
        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Global Developer Summit 2026',
            'slug' => 'global-developer-summit-' . uniqid(),
            'description' => 'A premier gathering for software architects.',
            'category' => 'Technology',
            'event_type' => 'online',
            'status' => 'published',
            'is_paid' => true,
            'ticket_price' => 25.00,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
        ]);

        // Create funded event campaign
        $this->campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $this->event->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Global Summit Campaign',
            'budget' => 100.00,
            'remaining_amount' => 100.00,
            'spent_amount' => 0.00,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Seed 3 standard active Event reward tiers:
        // Tier 1: 0–5 -> $0.0150 USD
        // Tier 2: 6–14 -> $0.0250 USD
        // Tier 3: 15+ -> $0.0400 USD
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0150,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0400,
            'is_active' => true,
        ]);

        // Also seed 3 Business Ads rules to verify cross-scope isolation
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 6,
            'max_referrals' => 14,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);

        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_BUSINESS_AD,
            'min_referrals' => 15,
            'max_referrals' => null,
            'reward_amount' => 0.0500,
            'is_active' => true,
        ]);

        AdRewardRule::clearCache();
    }

    /**
     * Helper to create direct referrals for a member.
     */
    protected function createReferrals(Member $parent, int $count, bool $verified = true): array
    {
        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $created[] = Member::create([
                'name' => "Child {$i} of {$parent->user_id}",
                'user_id' => 'child_' . Str::random(8),
                'email' => 'child_' . Str::random(8) . '@example.com',
                'password' => 'password123',
                'introducer_id' => $parent->user_id,
                'mobile_verified_at' => $verified ? now() : null,
            ]);
        }
        return $created;
    }

    /**
     * Requirement 1: Correct reward is returned for a valid referral count.
     */
    public function test_01_correct_reward_is_returned_for_valid_referral_count(): void
    {
        // 0 referrals -> Tier 1 (0-5) -> $0.0150
        $res0 = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertTrue($res0['success']);
        $this->assertEquals(0, $res0['direct_verified_referral_count']);
        $this->assertEquals(0.0150, $res0['reward_amount_usd']);
        $this->assertEquals('0.0150', $res0['reward_amount_exact']);

        // Add 7 verified referrals -> Tier 2 (6-14) -> $0.0250
        $this->createReferrals($this->member, 7, true);
        $res7 = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertTrue($res7['success']);
        $this->assertEquals(7, $res7['direct_verified_referral_count']);
        $this->assertEquals(0.0250, $res7['reward_amount_usd']);
        $this->assertEquals('0.0250', $res7['reward_amount_exact']);

        // Add 9 more (total 16) -> Tier 3 (15+) -> $0.0400
        $this->createReferrals($this->member, 9, true);
        $res16 = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertTrue($res16['success']);
        $this->assertEquals(16, $res16['direct_verified_referral_count']);
        $this->assertEquals(0.0400, $res16['reward_amount_usd']);
        $this->assertEquals('0.0400', $res16['reward_amount_exact']);
    }

    /**
     * Requirement 2: Referral count is calculated strictly from authoritative backend data.
     * Direct introducer relationship (introducer_id = member.user_id) AND mobile_verified_at IS NOT NULL.
     * Excludes: unverified registrations, indirect descendants, followers, connections.
     */
    public function test_02_referral_count_is_calculated_strictly_from_authoritative_backend_data(): void
    {
        // 1. Add 4 verified direct referrals
        $this->createReferrals($this->member, 4, true);

        // 2. Add 5 UNVERIFIED direct referrals (mobile_verified_at = null) -> should NOT be counted
        $this->createReferrals($this->member, 5, false);

        // 3. Add indirect descendants (referrals of member's child) -> should NOT be counted
        $child = Member::where('introducer_id', $this->member->user_id)->first();
        $this->createReferrals($child, 10, true);

        // 4. Add follower (social connection) -> should NOT be counted
        $followerUser = Member::create([
            'name' => 'Social Follower',
            'user_id' => 'fol_' . Str::random(8),
            'email' => 'fol_' . Str::random(8) . '@example.com',
            'password' => 'secret',
            'mobile_verified_at' => now(),
        ]);
        Follower::create([
            'follower_id' => $followerUser->id,
            'following_id' => $this->member->id,
        ]);

        // Authoritative count must be strictly 4
        $count = $this->eventResolver->getVerifiedDirectReferralCount($this->member);
        $this->assertEquals(4, $count);

        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertEquals(4, $res['direct_verified_referral_count']);
        $this->assertEquals(0.0150, $res['reward_amount_usd']);
    }

    /**
     * Requirement 3: Client-supplied referral count in request is completely ignored (Adversarial).
     */
    public function test_03_client_supplied_referral_count_is_ignored_adversarial(): void
    {
        // Member has 0 referrals authoritatively
        $response = $this->actingAs($this->member, 'member')->getJson(
            "/api/member/events/{$this->event->id}/reward-preview?referral_count=9999&direct_verified_referrals=500"
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['direct_verified_referral_count']);
        $this->assertEquals(0.0150, $data['reward_amount_usd']);
    }

    /**
     * Requirement 4: Client-supplied reward amount in request is completely ignored (Adversarial).
     */
    public function test_04_client_supplied_reward_amount_is_ignored_adversarial(): void
    {
        $response = $this->actingAs($this->member, 'member')->getJson(
            "/api/member/events/{$this->event->id}/reward-preview?reward_amount=999.99&reward=0.0500"
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0.0150, $data['reward_amount_usd']);
        $this->assertNotEquals(999.99, $data['reward_amount_usd']);
    }

    /**
     * Requirement 5: Correct Event rule scope (rule_type = 'event') is strictly used.
     */
    public function test_05_correct_event_rule_scope_is_used(): void
    {
        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertTrue($res['success']);
        $this->assertEquals(AdRewardRule::TYPE_EVENT, $res['rule_type']);
        $this->assertEquals(AdRewardRule::TYPE_EVENT, $res['snapshot']['rule_type']);
    }

    /**
     * Requirement 6: Business Ads rules are not accidentally used for Event reward resolution.
     */
    public function test_06_business_ads_rules_are_not_accidentally_used(): void
    {
        // For referral count 0:
        // Event rule has $0.0150
        // Business Ads rule has $0.0200
        $eventRes = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $businessRes = $this->ruleResolver->resolveForMember($this->member, AdRewardRule::TYPE_BUSINESS_AD);

        $this->assertEquals(0.0150, $eventRes['reward_amount_usd']);
        $this->assertEquals(0.0200, $businessRes['reward_amount_usd']);
        $this->assertNotEquals($eventRes['reward_amount_usd'], $businessRes['reward_amount_usd']);
    }

    /**
     * Requirement 7: Disabled Event rules are ignored during resolution.
     */
    public function test_07_disabled_event_rules_are_ignored(): void
    {
        // Deactivate tier 1 (0-5)
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 0)
            ->update(['is_active' => false]);
        AdRewardRule::clearCache();

        // Count 0 now matches no active rule -> fails closed with no_matching_rule
        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertFalse($res['success']);
        $this->assertEquals('no_matching_rule', $res['status']);
        $this->assertEquals(0.00, $res['reward_amount_usd']);
    }

    /**
     * Requirement 8: Active Event rules are dynamically evaluated.
     */
    public function test_08_active_event_rules_are_dynamically_evaluated(): void
    {
        // Update tier 1 reward from 0.0150 to 0.0180
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 0)
            ->update(['reward_amount' => 0.0180]);
        AdRewardRule::clearCache();

        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertTrue($res['success']);
        $this->assertEquals(0.0180, $res['reward_amount_usd']);
        $this->assertEquals('0.0180', $res['reward_amount_exact']);
    }

    /**
     * Requirement 9: Exactly one rule matches a valid referral count.
     */
    public function test_09_exactly_one_rule_matches_valid_referral_count(): void
    {
        $this->createReferrals($this->member, 10, true);
        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event);

        $this->assertTrue($res['success']);
        $this->assertEquals('resolved', $res['status']);
        $this->assertNotNull($res['matched_rule_id']);
        $this->assertEquals(6, $res['matched_range']['min']);
        $this->assertEquals(14, $res['matched_range']['max']);
    }

    /**
     * Requirement 10: Zero matching rules fails closed (never $0.05, never guesses).
     */
    public function test_10_zero_matching_rules_fails_closed_no_fallback(): void
    {
        // Create an active set with a gap between 5 and 10
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->delete();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0100,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 10,
            'max_referrals' => null,
            'reward_amount' => 0.0300,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();

        // Referral count 7 falls into gap (6-9)
        $res = $this->eventResolver->resolveForCount(7);
        $this->assertFalse($res['success']);
        $this->assertEquals('no_matching_rule', $res['status']);
        $this->assertEquals(0.00, $res['reward_amount_usd']);
        $this->assertFalse($res['eligible']);
    }

    /**
     * Requirement 11: Multiple matching overlapping rules fails closed (ambiguous_overlapping_rules).
     */
    public function test_11_multiple_matching_overlapping_rules_fails_closed(): void
    {
        // Force an overlapping rule directly in database for defensive check
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 3,
            'max_referrals' => 8,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();

        // Count 4 matches both [0, 5] and [3, 8]
        $res = $this->eventResolver->resolveForCount(4);
        $this->assertFalse($res['success']);
        $this->assertEquals('ambiguous_overlapping_rules', $res['status']);
        $this->assertEquals(0.00, $res['reward_amount_usd']);
        $this->assertCount(2, $res['matching_rule_ids']);
    }

    /**
     * Requirement 12: Unconfigured / null / zero reward amount fails closed (reward_rule_not_configured).
     */
    public function test_12_unconfigured_null_or_zero_reward_amount_fails_closed(): void
    {
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 0)
            ->update(['reward_amount' => null]);
        AdRewardRule::clearCache();

        $res = $this->eventResolver->resolveForCount(2);
        $this->assertFalse($res['success']);
        $this->assertEquals('reward_rule_not_configured', $res['status']);
        $this->assertEquals(0.00, $res['reward_amount_usd']);
    }

    /**
     * Requirement 13: Missing active Event rules fails closed (no_active_rules).
     */
    public function test_13_missing_active_event_rules_fails_closed(): void
    {
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->delete();
        AdRewardRule::clearCache();

        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event);
        $this->assertFalse($res['success']);
        $this->assertEquals('no_active_rules', $res['status']);
        $this->assertEquals(0.00, $res['reward_amount_usd']);
    }

    /**
     * Requirement 14: No hardcoded reward values or fallbacks (reads custom amounts).
     */
    public function test_14_no_hardcoded_reward_values_or_fallbacks(): void
    {
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->delete();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => null,
            'reward_amount' => 0.0123,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();

        $res = $this->eventResolver->resolveForCount(3);
        $this->assertTrue($res['success']);
        $this->assertEquals(0.0123, $res['reward_amount_usd']);
        $this->assertEquals('0.0123', $res['reward_amount_exact']);
    }

    /**
     * Requirement 15: No hardcoded thresholds (evaluates dynamic thresholds).
     */
    public function test_15_no_hardcoded_thresholds(): void
    {
        AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)->delete();
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 2,
            'reward_amount' => 0.0100,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 3,
            'max_referrals' => 7,
            'reward_amount' => 0.0200,
            'is_active' => true,
        ]);
        AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 8,
            'max_referrals' => null,
            'reward_amount' => 0.0350,
            'is_active' => true,
        ]);
        AdRewardRule::clearCache();

        // 2 -> 0.0100
        $this->assertEquals(0.0100, $this->eventResolver->resolveForCount(2)['reward_amount_usd']);
        // 3 -> 0.0200
        $this->assertEquals(0.0200, $this->eventResolver->resolveForCount(3)['reward_amount_usd']);
        // 7 -> 0.0200
        $this->assertEquals(0.0200, $this->eventResolver->resolveForCount(7)['reward_amount_usd']);
        // 8 -> 0.0350
        $this->assertEquals(0.0350, $this->eventResolver->resolveForCount(8)['reward_amount_usd']);
    }

    /**
     * Requirement 16: Reward precision is strictly 4 decimal places.
     */
    public function test_16_reward_precision_is_strictly_4_decimal_places(): void
    {
        $res = $this->eventResolver->resolveForCount(0);
        $this->assertTrue($res['success']);
        $this->assertSame('0.0150', $res['reward_amount_exact']);
        $this->assertMatchesRegularExpression('/^\d+\.\d{4}$/', $res['reward_amount_exact']);
    }

    /**
     * Requirement 17: Reward currency is strictly USD.
     */
    public function test_17_reward_currency_is_strictly_usd(): void
    {
        $res = $this->eventResolver->resolveForCount(0);
        $this->assertEquals('USD', $res['currency']);
        $this->assertEquals('$', $res['currency_symbol']);
    }

    /**
     * Requirement 18: Resolver creates NO wallet transactions (pure read-only).
     */
    public function test_18_resolver_creates_no_wallet_transactions(): void
    {
        $initialBalance = $this->member->fresh()->ad_balance;

        $this->eventResolver->resolveForEventMember($this->member, $this->event);

        $this->assertEquals($initialBalance, $this->member->fresh()->ad_balance);
        $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('ad_campaign_activities')->count());
    }

    /**
     * Requirement 19: Resolver creates NO reward payout records.
     */
    public function test_19_resolver_creates_no_reward_payout_records(): void
    {
        $initialRewardCount = AdReward::count();

        $this->eventResolver->resolveForEventMember($this->member, $this->event);

        $this->assertEquals($initialRewardCount, AdReward::count());
    }

    /**
     * Requirement 20: Resolver does NOT debit campaign budget.
     */
    public function test_20_resolver_does_not_debit_campaign_budget(): void
    {
        $initialRemaining = (float) $this->campaign->fresh()->remaining_amount;
        $initialSpent = (float) $this->campaign->fresh()->spent_amount;

        $this->eventResolver->resolveForEventMember($this->member, $this->event, $this->campaign);

        $this->assertEquals($initialRemaining, (float) $this->campaign->fresh()->remaining_amount);
        $this->assertEquals($initialSpent, (float) $this->campaign->fresh()->spent_amount);
    }

    /**
     * Requirement 21: Resolver does NOT create participant or Interested records.
     */
    public function test_21_resolver_does_not_create_participant_or_interested_records(): void
    {
        $initialCount = \Illuminate\Support\Facades\DB::table('event_responses')->count();

        $this->eventResolver->resolveForEventMember($this->member, $this->event);

        $this->assertEquals($initialCount, \Illuminate\Support\Facades\DB::table('event_responses')->count());
    }

    /**
     * Requirement 22: Member referral and verification state remain unmodified.
     */
    public function test_22_member_referral_and_verification_state_remain_unmodified(): void
    {
        $this->createReferrals($this->member, 3, true);
        $beforeRefs = Member::where('introducer_id', $this->member->user_id)->get(['id', 'mobile_verified_at']);

        $this->eventResolver->resolveForEventMember($this->member, $this->event);

        $afterRefs = Member::where('introducer_id', $this->member->user_id)->get(['id', 'mobile_verified_at']);
        $this->assertEquals($beforeRefs->toArray(), $afterRefs->toArray());
    }

    /**
     * Requirement 23: Phase 9 minimum reward source consistency.
     */
    public function test_23_phase_9_minimum_reward_source_consistency(): void
    {
        $phase9Min = AdRewardRule::getMinimumActiveRewardAmount(AdRewardRule::TYPE_EVENT);
        $this->assertEquals(0.0150, $phase9Min);

        $lowestTier = $this->eventResolver->resolveForCount(0);
        $this->assertEquals($phase9Min, $lowestTier['reward_amount_usd']);
    }

    /**
     * Requirement 24: Phase 10 Admin rule changes immediately affect resolver.
     */
    public function test_24_phase_10_admin_rule_updates_immediately_affect_resolver(): void
    {
        $rule = AdRewardRule::where('rule_type', AdRewardRule::TYPE_EVENT)
            ->where('min_referrals', 6)
            ->first();

        $rule->update(['reward_amount' => 0.0299]);
        AdRewardRule::clearCache();

        $res = $this->eventResolver->resolveForCount(10);
        $this->assertEquals(0.0299, $res['reward_amount_usd']);
    }

    /**
     * Requirement 25: Phase 11 validated rules resolve deterministically.
     */
    public function test_25_phase_11_validated_rules_resolve_deterministically(): void
    {
        $validation = AdRewardRule::validateActiveSet(AdRewardRule::TYPE_EVENT);
        $this->assertTrue($validation['valid']);

        $res1 = $this->eventResolver->resolveForCount(12);
        $res2 = $this->eventResolver->resolveForCount(12);

        $this->assertEquals($res1['reward_amount_usd'], $res2['reward_amount_usd']);
        $this->assertEquals($res1['matched_rule_id'], $res2['matched_rule_id']);
    }

    /**
     * Requirement 26: Phase 8 budget remains unchanged when resolver runs.
     */
    public function test_26_phase_8_budget_remains_unchanged_when_resolver_runs(): void
    {
        $budget = $this->campaign->fresh()->budget;
        $remaining = $this->campaign->fresh()->remaining_amount;

        $this->eventResolver->resolveForEventMember($this->member, $this->event, $this->campaign);

        $this->assertEquals($budget, $this->campaign->fresh()->budget);
        $this->assertEquals($remaining, $this->campaign->fresh()->remaining_amount);
    }

    /**
     * Requirement 27: Event context validation (invalid event fails safely).
     */
    public function test_27_event_context_validation_invalid_event_fails_safely(): void
    {
        $res = $this->eventResolver->resolveForEventMember($this->member, 999999);
        $this->assertFalse($res['success']);
        $this->assertEquals('invalid_event', $res['status']);
    }

    /**
     * Requirement 28: Member context validation (invalid member fails safely).
     */
    public function test_28_member_context_validation_invalid_member_fails_safely(): void
    {
        $res = $this->eventResolver->resolveForEventMember(999999, $this->event);
        $this->assertFalse($res['success']);
        $this->assertEquals('invalid_member', $res['status']);
    }

    /**
     * Requirement 29: Campaign context validation (cross-campaign or type mismatch fails safely).
     */
    public function test_29_campaign_context_validation_cross_campaign_or_mismatch_fails_safely(): void
    {
        // 1. Create a business page campaign
        $bizPage = BusinessPage::create([
            'member_id' => $this->organizer->id,
            'page_name' => 'Acme Corp',
            'page_username' => 'acmecorp_' . uniqid(),
            'slug' => 'acme-corp-' . uniqid(),
            'category' => 'Technology',
            'status' => 'published',
        ]);
        $bizCampaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_PAGE,
            'business_page_id' => $bizPage->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Biz Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        // Attempt resolving event with a business page campaign
        $res = $this->eventResolver->resolveForEventMember($this->member, $this->event, $bizCampaign);
        $this->assertFalse($res['success']);
        $this->assertEquals('campaign_context_mismatch', $res['status']);

        // 2. Create another event campaign belonging to a different event
        $event2 = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Other Summit',
            'slug' => 'other-summit-' . uniqid(),
            'category' => 'Design',
            'event_type' => 'online',
            'status' => 'published',
            'is_paid' => true,
            'ticket_price' => 10.00,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
        ]);
        $event2Campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event2->id,
            'member_id' => $this->organizer->id,
            'campaign_name' => 'Other Summit Campaign',
            'budget' => 50.00,
            'remaining_amount' => 50.00,
            'spent_amount' => 0.00,
            'currency' => 'USD',
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $res2 = $this->eventResolver->resolveForEventMember($this->member, $this->event, $event2Campaign);
        $this->assertFalse($res2['success']);
        $this->assertEquals('campaign_context_mismatch', $res2['status']);
    }

    /**
     * Requirement 30: Adversarial IDOR test (endpoint uses authenticated member, ignores other member IDs).
     */
    public function test_30_adversarial_idor_cannot_resolve_other_users_private_reward(): void
    {
        // Target member has 0 referrals -> 0.0150
        // Another member has 10 referrals -> 0.0250
        $otherMember = Member::create([
            'name' => 'Other Member',
            'user_id' => 'other_' . Str::random(8),
            'email' => 'other_' . Str::random(8) . '@example.com',
            'password' => 'password123',
            'mobile_verified_at' => now(),
        ]);
        $this->createReferrals($otherMember, 10, true);

        // Target member requests reward preview but attempts to pass other member's id in query
        $response = $this->actingAs($this->member, 'member')->getJson(
            "/api/member/events/{$this->event->id}/reward-preview?member_id={$otherMember->id}&user_id={$otherMember->user_id}"
        );

        $response->assertStatus(200);
        $data = $response->json();
        // Returned reward must be for $this->member (0 referrals = $0.0150), NOT $otherMember (10 referrals = $0.0250)
        $this->assertEquals($this->member->id, $data['member_id']);
        $this->assertEquals(0, $data['direct_verified_referral_count']);
        $this->assertEquals(0.0150, $data['reward_amount_usd']);
    }

    /**
     * Requirement 31: Adversarial inactive rule ID passed in request is ignored.
     */
    public function test_31_adversarial_inactive_rule_id_in_request_is_ignored(): void
    {
        $inactiveRule = AdRewardRule::create([
            'rule_type' => AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0500,
            'is_active' => false,
        ]);
        AdRewardRule::clearCache();

        $response = $this->actingAs($this->member, 'member')->getJson(
            "/api/member/events/{$this->event->id}/reward-preview?ad_reward_rule_id={$inactiveRule->id}"
        );

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEquals($inactiveRule->id, $data['matched_rule_id']);
        $this->assertEquals(0.0150, $data['reward_amount_usd']);
    }

    /**
     * Requirement 32: Test Matrix (discrete referral thresholds: 0, 5, 6, 14, 15, 20).
     */
    public function test_32_test_matrix_discrete_threshold_evaluations(): void
    {
        // 0 -> Tier 1 (0-5) -> 0.0150
        $this->assertEquals(0.0150, $this->eventResolver->resolveForCount(0)['reward_amount_usd']);

        // 5 -> Tier 1 (0-5) -> 0.0150
        $this->assertEquals(0.0150, $this->eventResolver->resolveForCount(5)['reward_amount_usd']);

        // 6 -> Tier 2 (6-14) -> 0.0250
        $this->assertEquals(0.0250, $this->eventResolver->resolveForCount(6)['reward_amount_usd']);

        // 14 -> Tier 2 (6-14) -> 0.0250
        $this->assertEquals(0.0250, $this->eventResolver->resolveForCount(14)['reward_amount_usd']);

        // 15 -> Tier 3 (15+) -> 0.0400
        $this->assertEquals(0.0400, $this->eventResolver->resolveForCount(15)['reward_amount_usd']);

        // 20 -> Tier 3 (15+) -> 0.0400
        $this->assertEquals(0.0400, $this->eventResolver->resolveForCount(20)['reward_amount_usd']);
    }

    /**
     * Requirement 33: Property-Style resolution test (for every N in [0..100], COUNT(N) == 1).
     */
    public function test_33_property_style_coverage_test_count_n_equals_one(): void
    {
        for ($n = 0; $n <= 100; $n++) {
            $res = $this->eventResolver->resolveForCount($n);
            $this->assertTrue($res['success'], "Resolution failed for count {$n}");
            $this->assertEquals('resolved', $res['status'], "Non-resolved status for count {$n}");
            $this->assertGreaterThan(0, $res['reward_amount_usd'], "Zero reward amount for count {$n}");
        }
    }

    /**
     * Requirement 34: Side-Effect Invariance (all financial and engagement records identical before and after).
     */
    public function test_34_side_effect_invariance_before_and_after_state_is_identical(): void
    {
        // Capture initial states
        $memberAdBalance = $this->member->fresh()->ad_balance;
        $campaignRemaining = $this->campaign->fresh()->remaining_amount;
        $campaignSpent = $this->campaign->fresh()->spent_amount;
        $activityCount = \Illuminate\Support\Facades\DB::table('ad_campaign_activities')->count();
        $rewardCount = AdReward::count();
        $participantCount = \Illuminate\Support\Facades\DB::table('event_responses')->count();

        // Run resolver multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->eventResolver->resolveForEventMember($this->member, $this->event, $this->campaign);
        }

        // Verify strictly identical
        $this->assertEquals($memberAdBalance, $this->member->fresh()->ad_balance);
        $this->assertEquals($campaignRemaining, $this->campaign->fresh()->remaining_amount);
        $this->assertEquals($campaignSpent, $this->campaign->fresh()->spent_amount);
        $this->assertEquals($activityCount, \Illuminate\Support\Facades\DB::table('ad_campaign_activities')->count());
        $this->assertEquals($rewardCount, AdReward::count());
        $this->assertEquals($participantCount, \Illuminate\Support\Facades\DB::table('event_responses')->count());
    }

    /**
     * Requirement 35: Free events remain unaffected.
     */
    public function test_35_free_events_remain_unaffected(): void
    {
        $freeEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Free Community Gathering',
            'slug' => 'free-community-gathering-' . uniqid(),
            'category' => 'Community',
            'event_type' => 'online',
            'status' => 'published',
            'is_paid' => false,
            'ticket_price' => 0.00,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(2),
        ]);

        $res = $this->eventResolver->resolveForEventMember($this->member, $freeEvent);
        $this->assertTrue($res['success']);
        $this->assertEquals($freeEvent->id, $res['event_id']);
        $this->assertNull($res['campaign_id']);
    }

    /**
     * Requirement 36: HTTP endpoint reward-preview responds authoritatively for authenticated member.
     */
    public function test_36_http_endpoint_reward_preview_for_authenticated_member(): void
    {
        // 1. Unauthenticated request receives 401
        $unauthResp = $this->getJson("/api/member/events/{$this->event->id}/reward-preview");
        $unauthResp->assertStatus(401);

        // 2. Authenticated request receives 200 with structured payload
        $response = $this->actingAs($this->member, 'member')->getJson(
            "/api/member/events/{$this->event->id}/reward-preview"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'status',
            'eligible',
            'event_id',
            'member_id',
            'direct_verified_referral_count',
            'matched_rule_id',
            'matched_range' => ['min', 'max', 'label', 'is_unlimited'],
            'reward_amount_usd',
            'reward_amount_exact',
            'currency',
            'currency_symbol',
            'snapshot' => [
                'rule_type',
                'event_id',
                'ad_reward_rule_id',
                'direct_verified_referral_count',
                'min_referrals',
                'max_referrals',
                'reward_amount_usd',
                'reward_amount_exact',
                'currency',
                'resolved_at',
            ],
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('resolved', $response->json('status'));
        $this->assertEquals(0.0150, $response->json('reward_amount_usd'));
    }
}
