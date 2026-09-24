<?php

namespace Tests\Feature;

use App\Models\AdCampaign;
use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class AdCampaignSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') === 'sqlite') {
            $this->artisan('migrate');
        }
        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            ValidateCsrfToken::class,
        ]);
    }

    protected function createVerifiedMember(string $prefix = 'member'): Member
    {
        $uniq = uniqid();
        return Member::create([
            'name' => ucfirst($prefix) . ' ' . $uniq,
            'email' => "{$prefix}_{$uniq}@test.com",
            'user_id' => 'USR_' . strtoupper(substr(md5($uniq), 0, 8)),
            'password' => '123456',
            'status' => 'active',
            'mobile_verified_at' => now(),
        ]);
    }

    protected function createBusinessPage(Member $owner, string $prefix = 'page'): BusinessPage
    {
        $random = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12));
        return BusinessPage::create([
            'page_id' => 'biz_' . $random,
            'member_id' => $owner->id,
            'page_name' => ucfirst($prefix) . ' ' . $random,
            'page_username' => strtolower($prefix) . '_' . $random,
            'slug' => strtolower($prefix) . '-' . $random,
            'category' => 'Health, Nutrition & Wellness MLM',
            'visibility' => 'public',
            'status' => 'active',
        ]);
    }

    /**
     * 1. Unauthorized guest or non-owner member cannot access other Business Page campaigns.
     */
    public function test_unauthorized_member_cannot_access_other_business_page_campaigns(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $otherMember = $this->createVerifiedMember('other');
        $page = $this->createBusinessPage($owner);

        // Attempt listing campaigns as unauthenticated guest -> 401/403/302
        $response = $this->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns");
        $this->assertTrue(in_array($response->status(), [401, 403, 302], true));

        // Attempt listing campaigns as non-owner member -> 403
        $response = $this->actingAs($otherMember, 'member')
            ->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns");
        $response->assertStatus(403);

        $page->delete();
        $owner->delete();
        $otherMember->delete();
    }

    /**
     * 2. Business Page owner can create a draft ad campaign with valid budget and details.
     */
    public function test_business_page_owner_can_create_draft_ad_campaign(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);

        $response = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
                'campaign_name' => 'Summer Launch Campaign',
                'budget' => 250.00,
                'start_at' => now()->addDay()->toDateTimeString(),
                'end_at' => now()->addDays(7)->toDateTimeString(),
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'campaign' => [
                'campaign_name' => 'Summer Launch Campaign',
                'budget' => '250.00',
                'spent_amount' => '0.00',
                'remaining_amount' => '250.00',
                'status' => AdCampaign::STATUS_DRAFT,
                'approval_status' => AdCampaign::APPROVAL_PENDING,
            ],
        ]);

        $campaignId = $response->json('campaign.id');
        AdCampaign::where('id', $campaignId)->delete();
        $page->delete();
        $owner->delete();
    }

    /**
     * 3. Business Page owner CANNOT promote a post that belongs to another Business Page.
     */
    public function test_business_page_owner_cannot_promote_post_from_another_page(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $pageA = $this->createBusinessPage($owner, 'pageA');
        $pageB = $this->createBusinessPage($owner, 'pageB');

        // Create post belonging to Page B
        $postOnPageB = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $pageB->id,
            'body' => 'Post belonging strictly to Page B',
        ]);

        // Attempt to create ad campaign on Page A referencing Page B's post
        $response = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$pageA->slug}/ad-campaigns", [
                'campaign_name' => 'Malicious Cross Promotion',
                'post_id' => $postOnPageB->id,
                'budget' => 100.00,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['post_id']);

        $postOnPageB->delete();
        $pageA->delete();
        $pageB->delete();
        $owner->delete();
    }

    /**
     * 4. Budget validation: Zero, negative, and non-numeric budgets must be rejected.
     */
    public function test_budget_validation_rejects_zero_negative_and_malformed_values(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);

        // Test 0 budget
        $resZero = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
                'campaign_name' => 'Zero Budget Campaign',
                'budget' => 0,
            ]);
        $resZero->assertStatus(422);
        $resZero->assertJsonValidationErrors(['budget']);

        // Test negative budget
        $resNeg = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
                'campaign_name' => 'Negative Budget Campaign',
                'budget' => -50.00,
            ]);
        $resNeg->assertStatus(422);
        $resNeg->assertJsonValidationErrors(['budget']);

        // Test non-numeric budget
        $resMalformed = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
                'campaign_name' => 'Malformed Budget Campaign',
                'budget' => 'one_hundred_dollars',
            ]);
        $resMalformed->assertStatus(422);
        $resMalformed->assertJsonValidationErrors(['budget']);

        $page->delete();
        $owner->delete();
    }

    /**
     * 5. State transitions: Owner submits draft -> Admin approves, pauses, resumes, stops.
     */
    public function test_full_campaign_lifecycle_and_admin_controls(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $admin = Admin::firstOrCreate(
            ['email' => 'pro@cpanel.com'],
            ['name' => 'Admin User', 'password' => '123456', 'status' => 'active']
        );

        $page = $this->createBusinessPage($owner);

        $post = Post::create([
            'member_id' => $owner->id,
            'business_page_id' => $page->id,
            'body' => 'Featured Post for Ad Campaign',
        ]);

        // Step 1: Owner creates draft
        $createRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns", [
                'campaign_name' => 'Lifecycle Test Campaign',
                'post_id' => $post->id,
                'budget' => 500.00,
            ]);
        $createRes->assertStatus(201);
        $campaignId = $createRes->json('campaign.id');
        $campaignPublicId = $createRes->json('campaign.campaign_id');

        // Step 2: Owner submits draft for review
        $submitRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaignPublicId}/submit");
        $submitRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_PENDING_REVIEW, $submitRes->json('campaign.status'));

        // Step 3: Admin lists campaigns and views metrics
        $adminListRes = $this->actingAs($admin, 'admin')
            ->getJson('/api/admin/ad-campaigns');
        $adminListRes->assertStatus(200);
        $adminListRes->assertJsonStructure(['campaigns', 'metrics']);

        // Step 4: Admin approves campaign
        $approveRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaignId}/approve");
        $approveRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_APPROVED, $approveRes->json('campaign.status'));
        $this->assertEquals(AdCampaign::APPROVAL_APPROVED, $approveRes->json('campaign.approval_status'));

        // Step 5: Admin pauses campaign
        $pauseRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaignId}/pause");
        $pauseRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $pauseRes->json('campaign.status'));

        // Step 6: Admin resumes campaign
        $resumeRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaignId}/resume");
        $resumeRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $resumeRes->json('campaign.status'));

        // Step 7: Admin stops campaign
        $stopRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaignId}/stop");
        $stopRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $stopRes->json('campaign.status'));

        // Step 8: Verify campaign is NOT deleted from DB
        $this->assertDatabaseHas('ad_campaigns', [
            'id' => $campaignId,
            'status' => AdCampaign::STATUS_STOPPED,
        ]);

        AdCampaign::where('id', $campaignId)->delete();
        $post->delete();
        $page->delete();
        $owner->delete();
    }

    /**
     * 6. Admin Rejection with reason.
     */
    public function test_admin_can_reject_campaign_with_reason(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $admin = Admin::firstOrCreate(
            ['email' => 'pro@cpanel.com'],
            ['name' => 'Admin User', 'password' => '123456', 'status' => 'active']
        );

        $page = $this->createBusinessPage($owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Non-Compliant Ad',
            'budget' => 150.00,
            'status' => AdCampaign::STATUS_PENDING_REVIEW,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Attempt rejection without reason -> 422
        $rejFail = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/reject", []);
        $rejFail->assertStatus(422);

        // Reject with valid reason -> 200
        $rejSuccess = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/reject", [
                'rejection_reason' => 'Creative violates policy guidelines on income claims.',
            ]);
        $rejSuccess->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_REJECTED, $rejSuccess->json('campaign.status'));
        $this->assertEquals(AdCampaign::APPROVAL_REJECTED, $rejSuccess->json('campaign.approval_status'));
        $this->assertEquals('Creative violates policy guidelines on income claims.', $rejSuccess->json('campaign.rejection_reason'));

        $campaign->delete();
        $page->delete();
        $owner->delete();
    }

    /**
     * 7. Other Member cannot approve/reject/pause admin endpoints.
     */
    public function test_regular_member_cannot_access_admin_ad_endpoints(): void
    {
        $member = $this->createVerifiedMember('regular');
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Admin Protection Test',
            'budget' => 100.00,
            'status' => AdCampaign::STATUS_PENDING_REVIEW,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Regular member attempting admin approve -> 401/403/302
        $res = $this->actingAs($member, 'member')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/approve");
        $this->assertTrue(in_array($res->status(), [401, 403, 302], true));

        // Regular member attempting admin reject -> 401/403/302
        $resRej = $this->actingAs($member, 'member')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/reject", ['rejection_reason' => 'test']);
        $this->assertTrue(in_array($resRej->status(), [401, 403, 302], true));

        $campaign->delete();
        $page->delete();
        $owner->delete();
        $member->delete();
    }

    /**
     * 8. Invalid state transitions must be blocked.
     */
    public function test_invalid_state_transitions_are_blocked(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $admin = Admin::firstOrCreate(
            ['email' => 'pro@cpanel.com'],
            ['name' => 'Admin User', 'password' => '123456', 'status' => 'active']
        );
        $page = $this->createBusinessPage($owner);

        // A draft campaign cannot be directly approved or paused by owner before submit
        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'State Transition Check',
            'budget' => 300.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        // Attempt to pause a draft -> 422
        $pauseRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/pause");
        $pauseRes->assertStatus(422);

        // Attempt to resume a draft -> 422
        $resumeRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/resume");
        $resumeRes->assertStatus(422);

        // Admin rejects campaign
        $campaign->update(['status' => AdCampaign::STATUS_REJECTED, 'approval_status' => AdCampaign::APPROVAL_REJECTED]);

        // Attempt to resume rejected campaign -> 422
        $adminResumeRes = $this->actingAs($admin, 'admin')
            ->postJson("/api/admin/ad-campaigns/{$campaign->id}/resume");
        $adminResumeRes->assertStatus(422);

        $campaign->delete();
        $page->delete();
        $owner->delete();
    }

    /**
     * 9. Owner can view, pause, resume, and stop approved campaign.
     */
    public function test_owner_can_pause_resume_and_stop_approved_campaign(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Owner Controls Test',
            'budget' => 400.00,
            'status' => AdCampaign::STATUS_APPROVED,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
        ]);

        // Owner views campaign details
        $showRes = $this->actingAs($owner, 'member')
            ->getJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}");
        $showRes->assertStatus(200);
        $showRes->assertJsonPath('campaign.campaign_name', 'Owner Controls Test');

        // Owner pauses approved campaign
        $pauseRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/pause");
        $pauseRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_PAUSED, $pauseRes->json('campaign.status'));

        // Owner resumes paused campaign
        $resumeRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/resume");
        $resumeRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_ACTIVE, $resumeRes->json('campaign.status'));

        // Owner stops campaign
        $stopRes = $this->actingAs($owner, 'member')
            ->postJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}/stop");
        $stopRes->assertStatus(200);
        $this->assertEquals(AdCampaign::STATUS_STOPPED, $stopRes->json('campaign.status'));

        $campaign->delete();
        $page->delete();
        $owner->delete();
    }

    /**
     * 10. Owner can update draft campaign details.
     */
    public function test_owner_can_update_draft_ad_campaign(): void
    {
        $owner = $this->createVerifiedMember('owner');
        $page = $this->createBusinessPage($owner);

        $campaign = AdCampaign::create([
            'business_page_id' => $page->id,
            'member_id' => $owner->id,
            'campaign_name' => 'Initial Draft Name',
            'budget' => 200.00,
            'status' => AdCampaign::STATUS_DRAFT,
            'approval_status' => AdCampaign::APPROVAL_PENDING,
        ]);

        $updateRes = $this->actingAs($owner, 'member')
            ->putJson("/api/member/business-pages/{$page->slug}/ad-campaigns/{$campaign->campaign_id}", [
                'campaign_name' => 'Updated Draft Name',
                'budget' => 350.00,
            ]);

        $updateRes->assertStatus(200);
        $this->assertEquals('Updated Draft Name', $updateRes->json('campaign.campaign_name'));
        $this->assertEquals('350.00', $updateRes->json('campaign.budget'));

        $campaign->delete();
        $page->delete();
        $owner->delete();
    }
}
