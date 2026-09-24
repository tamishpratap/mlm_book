<?php

namespace Tests\Feature;

use App\Jobs\SendReportToWhatsAppJob;
use App\Models\AdCampaign;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Models\ReportedPost;
use App\Services\AdDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignPostReportFixTest extends TestCase
{
    use RefreshDatabase;

    protected Member $creator;
    protected Member $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = Member::create([
            'name' => 'Event Creator',
            'user_id' => 'event_creator',
            'email' => 'creator@example.com',
            'password' => bcrypt('password'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->reporter = Member::create([
            'name' => 'Feed Viewer',
            'user_id' => 'feed_viewer',
            'email' => 'viewer@example.com',
            'password' => bcrypt('password'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * 1. A normal organic post with numeric ID reports successfully, creates a reported_posts row,
     * and dispatches the WhatsApp job with the integer post_id.
     */
    public function test_normal_numeric_post_reporting_works_as_expected(): void
    {
        Queue::fake([SendReportToWhatsAppJob::class]);

        $post = Post::create([
            'member_id' => $this->creator->id,
            'body' => 'Standard organic post body content.',
        ]);

        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Spam',
                'description' => 'Unwanted promotional message.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'post_id' => $post->id,
            ]);

        $this->assertDatabaseHas('reported_posts', [
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Spam',
            'description' => 'Unwanted promotional message.',
            'status' => 'pending',
        ]);

        Queue::assertPushed(SendReportToWhatsAppJob::class);
    }

    /**
     * 2. When an event campaign has an underlying post_id, reporting with the synthetic ID
     * `event_campaign_{id}` resolves the underlying real post, saves the real numeric post_id,
     * and does NOT throw ModelNotFoundException or insert non-numeric post_id into database.
     */
    public function test_event_campaign_with_underlying_post_resolves_and_reports_real_post(): void
    {
        Queue::fake([SendReportToWhatsAppJob::class]);

        $realPost = Post::create([
            'member_id' => $this->creator->id,
            'body' => 'Real underlying post for event campaign.',
        ]);

        $event = Event::create([
            'title' => 'Summit 2026',
            'slug' => 'summit-2026-' . uniqid(),
            'description' => 'Great summit',
            'category' => 'Technology',
            'event_type' => 'offline',
            'location_city' => 'Singapore',
            'location_country' => 'Singapore',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'organizer_id' => $this->creator->id,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'post_id' => $realPost->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Campaign for Summit',
            'budget' => 100.00,
            'additional_funding' => 0.00,
            'total_funded' => 100.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 100.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $syntheticId = 'event_campaign_' . $campaign->id;

        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$syntheticId}/report", [
                'reason' => 'Harassment',
                'description' => 'Inappropriate event promotion.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'post_id' => $realPost->id,
            ]);

        // Verify that the recorded post_id in DB is the real numeric post ID, never the string
        $this->assertDatabaseHas('reported_posts', [
            'member_id' => $this->reporter->id,
            'post_id' => $realPost->id,
            'reason' => 'Harassment',
        ]);

        $report = ReportedPost::where('member_id', $this->reporter->id)->where('post_id', $realPost->id)->firstOrFail();
        $this->assertSame((int) $realPost->id, (int) $report->post_id);

        Queue::assertPushed(SendReportToWhatsAppJob::class, function (SendReportToWhatsAppJob $job) use ($report) {
            return $job->reportId === $report->id;
        });
    }

    /**
     * 3. When an event campaign has NO underlying post, reporting with synthetic ID
     * does not crash with ModelNotFoundException, does not insert corrupt records into DB,
     * and returns a clean 422 JSON response.
     */
    public function test_event_campaign_without_post_returns_clean_422_without_model_not_found_exception(): void
    {
        Queue::fake([SendReportToWhatsAppJob::class]);

        $event = Event::create([
            'title' => 'Concert Without Post',
            'slug' => 'concert-' . uniqid(),
            'description' => 'No post attached.',
            'category' => 'Music',
            'event_type' => 'offline',
            'location_city' => 'Tokyo',
            'location_country' => 'Japan',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(11),
            'organizer_id' => $this->creator->id,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'post_id' => null, // Explicitly no post
            'member_id' => $this->creator->id,
            'campaign_name' => 'Campaign for Concert',
            'budget' => 50.00,
            'additional_funding' => 0.00,
            'total_funded' => 50.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 50.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $syntheticId = 'event_campaign_' . $campaign->id;

        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$syntheticId}/report", [
                'reason' => 'Spam',
            ]);

        // Must return 422 with a clean error message, NOT 500 ModelNotFoundException
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This event content cannot be reported as a post.',
            ]);

        $this->assertDatabaseMissing('reported_posts', [
            'member_id' => $this->reporter->id,
        ]);

        Queue::assertNothingPushed();
    }

    /**
     * 4. Business campaign or regular campaign with synthetic ID `business_campaign_{id}`
     * resolves to the campaign's underlying post.
     */
    public function test_business_campaign_synthetic_id_resolves_and_reports_real_post(): void
    {
        Queue::fake([SendReportToWhatsAppJob::class]);

        $realPost = Post::create([
            'member_id' => $this->creator->id,
            'body' => 'Promoted business post.',
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_BUSINESS_AD,
            'post_id' => $realPost->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Business Promotion',
            'budget' => 80.00,
            'additional_funding' => 0.00,
            'total_funded' => 80.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 80.00,
            'status' => AdCampaign::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/business_campaign_{$campaign->id}/report", [
                'reason' => 'Adult Content',
                'description' => 'Inappropriate business ad content.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'post_id' => $realPost->id,
            ]);

        $this->assertDatabaseHas('reported_posts', [
            'member_id' => $this->reporter->id,
            'post_id' => $realPost->id,
            'reason' => 'Adult Content',
        ]);
    }

    /**
     * 5. Non-existent post ID returns clean 404, not unhandled 500 error.
     */
    public function test_non_existent_post_id_returns_clean_404(): void
    {
        $response = $this->actingAs($this->reporter, 'member')
            ->postJson('/api/member/posts/99999999/report', [
                'reason' => 'Spam',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Post not found.',
            ]);
    }

    /**
     * 6. Duplicate reports by the same member update the existing report without throwing duplicate key errors.
     */
    public function test_duplicate_reporting_updates_existing_row(): void
    {
        $post = Post::create([
            'member_id' => $this->creator->id,
            'body' => 'Reportable post.',
        ]);

        // First report
        $res1 = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Spam',
                'description' => 'Initial report reason.',
            ]);
        $res1->assertOk();

        // Second report with updated reason
        $res2 = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Violence',
                'description' => 'Updated report reason.',
            ]);
        $res2->assertOk();

        $this->assertEquals(
            1,
            ReportedPost::where('member_id', $this->reporter->id)->where('post_id', $post->id)->count()
        );

        $this->assertDatabaseHas('reported_posts', [
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Violence',
            'description' => 'Updated report reason.',
        ]);
    }

    /**
     * 7. AdDeliveryService formatEventPost exposes post_id, source_post_id, and ad_campaign metadata.
     */
    public function test_ad_delivery_service_format_event_post_exposes_underlying_post_id(): void
    {
        $realPost = Post::create([
            'member_id' => $this->creator->id,
            'body' => 'Event promo post.',
        ]);

        $event = Event::create([
            'title' => 'Exhibition 2026',
            'slug' => 'exhibition-' . uniqid(),
            'description' => 'Great exhibition.',
            'category' => 'Art',
            'event_type' => 'offline',
            'location_city' => 'London',
            'location_country' => 'UK',
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(4),
            'organizer_id' => $this->creator->id,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        \App\Models\AdRewardRule::create([
            'rule_type' => \App\Models\AdRewardRule::TYPE_EVENT,
            'min_referrals' => 0,
            'max_referrals' => 5,
            'reward_amount' => 0.0250,
            'is_active' => true,
        ]);

        $campaign = AdCampaign::create([
            'campaign_type' => AdCampaign::TYPE_EVENT,
            'event_id' => $event->id,
            'post_id' => $realPost->id,
            'member_id' => $this->creator->id,
            'campaign_name' => 'Campaign for Exhibition',
            'budget' => 60.00,
            'additional_funding' => 0.00,
            'total_funded' => 60.00,
            'spent_amount' => 0.00,
            'remaining_amount' => 60.00,
            'status' => AdCampaign::STATUS_ACTIVE,
            'approval_status' => AdCampaign::APPROVAL_APPROVED,
            'start_at' => now()->subMinute(),
        ]);

        $service = app(AdDeliveryService::class);
        $formattedPost = $service->formatEventPost($event, $this->reporter, $campaign);

        $this->assertNotNull($formattedPost);
        $this->assertSame('event_campaign_' . $campaign->id, $formattedPost->id);
        $this->assertSame($realPost->id, $formattedPost->post_id);
        $this->assertSame($realPost->id, $formattedPost->source_post_id);
        $this->assertSame($realPost->id, $formattedPost->ad_campaign['post_id']);
        $this->assertSame($realPost->id, $formattedPost->ad_campaign['source_post_id']);
    }
}
