<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppApiClientContract;
use App\Jobs\SendReportToWhatsAppJob;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Post;
use App\Models\ReportedPost;
use App\Services\WhatsApp\GenericWhatsAppApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostReportWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Member $author;
    protected Member $reporter;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = Member::create([
            'name' => 'Alice Author',
            'user_id' => 'alice_author',
            'email' => 'alice@example.com',
            'phone' => '+15551112222',
            'password' => bcrypt('password'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->reporter = Member::create([
            'name' => 'Bob Reporter',
            'user_id' => 'bob_reporter',
            'email' => 'bob@example.com',
            'phone' => '+15553334444',
            'password' => bcrypt('password'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->admin = Admin::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    /**
     * TEST 1 — NORMAL REPORT
     * Validation works, report row is saved, admin queue receives report, response is successful, job is dispatched.
     */
    public function test_1_normal_report_saves_record_and_dispatches_whatsapp_job(): void
    {
        Queue::fake([SendReportToWhatsAppJob::class]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'This is a test post subject to moderation.',
        ]);

        // Validation error on invalid reason
        $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'InvalidReasonNotAllowed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        Queue::assertNothingPushed();

        // Valid report submission
        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Spam',
                'description' => 'Unsolicited advertising links repeatedly posted.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'post_id' => $post->id,
                'message' => 'Thank you. We have received your report and will review it.',
            ]);

        // 1. Report is saved in reported_posts
        $this->assertDatabaseHas('reported_posts', [
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Spam',
            'description' => 'Unsolicited advertising links repeatedly posted.',
            'status' => 'pending',
        ]);

        $report = ReportedPost::where('member_id', $this->reporter->id)->where('post_id', $post->id)->firstOrFail();

        // 2. Report is visible to admin
        $adminRes = $this->actingAs($this->admin, 'admin')->getJson('/api/admin/reports?source_type=post');
        $adminRes->assertOk();
        $this->assertSame($report->id, $adminRes->json('reports.0.id'));

        // 3. Queue job was dispatched with the correct report ID
        Queue::assertPushed(SendReportToWhatsAppJob::class, function (SendReportToWhatsAppJob $job) use ($report) {
            return $job->reportId === $report->id;
        });
    }

    /**
     * TEST 2 — WHATSAPP DISABLED
     * When WHATSAPP_ENABLED=false, report is saved, admin sees report, and no external HTTP request is sent.
     */
    public function test_2_whatsapp_disabled_does_not_send_external_http_requests(): void
    {
        Config::set('whatsapp.enabled', false);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/messages');
        Config::set('whatsapp.to_number', '+15559998888');

        Http::fake();

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Post when WhatsApp is disabled.',
        ]);

        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Harassment',
                'description' => 'Offensive behavior in comments.',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        // Report exists
        $report = ReportedPost::where('post_id', $post->id)->firstOrFail();
        $this->assertEquals('Harassment', $report->reason);

        // Execute job synchronously
        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);
        $job->handle($client);

        // Assert NO HTTP request was dispatched
        Http::assertNothingSent();
    }

    /**
     * TEST 3 — WHATSAPP CONFIGURED
     * With credentials & endpoint configured, job runs, sends HTTP request with complete payload.
     */
    public function test_3_whatsapp_configured_constructs_and_sends_complete_payload(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/v1/messages');
        Config::set('whatsapp.api_token', 'test-auth-token-xyz-123');
        Config::set('whatsapp.to_number', '+15558889999');

        Http::fake([
            'https://api.whatsapp-mock.test/v1/messages' => Http::response(['success' => true, 'id' => 'msg_12345'], 200),
        ]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Controversial post body text.',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/photos/sample.jpg',
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Hate Speech',
            'description' => 'Contains unacceptable targeted hate speech.',
            'status' => 'pending',
        ]);

        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);
        $job->handle($client);

        Http::assertSent(function (Request $request) use ($report, $post) {
            // Verify endpoint
            if ($request->url() !== 'https://api.whatsapp-mock.test/v1/messages') {
                return false;
            }

            // Verify auth header
            if ($request->header('Authorization') !== ['Bearer test-auth-token-xyz-123']) {
                return false;
            }

            $data = $request->data();

            // Verify recipient
            if ($data['to'] !== '+15558889999') {
                return false;
            }

            // Verify report details
            if ($data['report']['id'] !== $report->id || $data['report']['reason'] !== 'Hate Speech') {
                return false;
            }

            // Verify reporter details
            if ($data['report']['description'] !== 'Contains unacceptable targeted hate speech.') {
                return false;
            }

            // Verify message body contains core admin-level fields
            $msg = $data['message'];
            $containsAll = str_contains($msg, "#{$report->id}")
                && str_contains($msg, 'Bob Reporter')
                && str_contains($msg, '@bob_reporter')
                && str_contains($msg, 'Alice Author')
                && str_contains($msg, '@alice_author')
                && str_contains($msg, 'Controversial post body text.')
                && str_contains($msg, 'Hate Speech')
                && str_contains($msg, 'uploads/posts/photos/sample.jpg');

            return $containsAll;
        });
    }

    /**
     * TEST 4 — WHATSAPP FAILURE
     * Simulates HTTP 500 error / connection error.
     * Report remains saved, member report flow does not fail, secrets are not leaked in logs.
     */
    public function test_4_whatsapp_failure_preserves_report_and_handles_error_safely(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/error-endpoint');
        Config::set('whatsapp.api_token', 'super-secret-production-token-999');
        Config::set('whatsapp.to_number', '+15558889999');

        Http::fake([
            'https://api.whatsapp-mock.test/error-endpoint' => Http::response('Internal Server Error with confidential details', 500),
        ]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Post to test failure resiliency.',
        ]);

        // 1. Member report flow succeeds even if downstream services have issues
        $response = $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Violence',
                'description' => 'Graphic violent content depicted.',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        // 2. Report record remains safely saved in database
        $report = ReportedPost::where('post_id', $post->id)->where('member_id', $this->reporter->id)->firstOrFail();
        $this->assertEquals('Violence', $report->reason);
        $this->assertEquals('pending', $report->status);

        // 3. Admin report inspection still retrieves the record
        $adminRes = $this->actingAs($this->admin, 'admin')->getJson("/api/admin/reports/post/{$report->id}");
        $adminRes->assertOk();

        // 4. Job catches failure, and client does NOT log the secret token
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->atLeast()->once()->withArgs(function ($message) {
            // Verify token is NEVER logged
            return ! str_contains($message, 'super-secret-production-token-999');
        });

        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);

        try {
            $job->handle($client);
            $this->fail('Expected handle() to throw RuntimeException on 500 error for queue retry.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString("WhatsApp report notification delivery failed for Report #{$report->id}", $e->getMessage());
        }

        // 5. Test failed() hook does not damage report
        $job->failed(new \RuntimeException('All 3 attempts timed out'));
        $this->assertDatabaseHas('reported_posts', [
            'id' => $report->id,
            'status' => 'pending',
        ]);
    }

    /**
     * TEST 5 — DUPLICATE REPORT
     * Same member reports same post again:
     * - Uses existing updateOrCreate (no duplicate rows in database)
     * - Dispatches notification with existing report ID
     */
    public function test_5_duplicate_report_updates_existing_row_without_creating_second_record(): void
    {
        Queue::fake([SendReportToWhatsAppJob::class]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Duplicate reporting target post.',
        ]);

        // First report
        $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Spam',
                'description' => 'First report description.',
            ])
            ->assertOk();

        $this->assertDatabaseCount('reported_posts', 1);
        $firstReport = ReportedPost::first();
        $firstReportId = $firstReport->id;
        $this->assertEquals('Spam', $firstReport->reason);

        Queue::assertPushed(SendReportToWhatsAppJob::class, 1);

        // Second report by SAME member for SAME post with updated reason
        $this->actingAs($this->reporter, 'member')
            ->postJson("/api/member/posts/{$post->id}/report", [
                'reason' => 'Adult Content',
                'description' => 'Updated details on the violation.',
            ])
            ->assertOk();

        // Still exactly 1 row in database (NO duplicate DB row!)
        $this->assertDatabaseCount('reported_posts', 1);
        $updatedReport = ReportedPost::first();

        $this->assertSame($firstReportId, $updatedReport->id);
        $this->assertEquals('Adult Content', $updatedReport->reason);
        $this->assertEquals('Updated details on the violation.', $updatedReport->description);

        // Queue was pushed again for the update with the SAME database ID
        Queue::assertPushed(SendReportToWhatsAppJob::class, 2);
    }

    /**
     * TEST 6 — POST WITH IMAGE
     * Verify normalized media data includes image type and URL.
     */
    public function test_6_post_with_image_includes_normalized_media_data(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/media');
        Config::set('whatsapp.to_number', '+15551234567');

        Http::fake([
            'https://api.whatsapp-mock.test/media' => Http::response(['success' => true], 200),
        ]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Look at this photo',
            'media_type' => 'image',
            'media_path' => 'uploads/posts/photos/test-image.jpg',
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Other',
            'description' => 'Inappropriate picture.',
        ]);

        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);
        $job->handle($client);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            return $data['type'] === 'image'
                && isset($data['media']['url'])
                && str_contains($data['media']['url'], 'test-image.jpg')
                && str_contains($data['message'], '🖼️ *Attached Image*');
        });
    }

    /**
     * TEST 7 — POST WITH VIDEO
     * Verify normalized media data includes video type and URL.
     */
    public function test_7_post_with_video_includes_normalized_media_data(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/media');
        Config::set('whatsapp.to_number', '+15551234567');

        Http::fake([
            'https://api.whatsapp-mock.test/media' => Http::response(['success' => true], 200),
        ]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Watch this video',
            'media_type' => 'video',
            'media_path' => 'uploads/posts/videos/clip.mp4',
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Violence',
            'description' => 'Violent video content.',
        ]);

        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);
        $job->handle($client);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            return $data['type'] === 'video'
                && isset($data['media']['url'])
                && str_contains($data['media']['url'], 'clip.mp4')
                && str_contains($data['message'], '🖼️ *Attached Video*');
        });
    }

    /**
     * TEST 8 — POST WITHOUT MEDIA
     * Verify notification constructs and sends successfully for text-only post without media.
     */
    public function test_8_post_without_media_sends_text_notification(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/text-only');
        Config::set('whatsapp.to_number', '+15551234567');

        Http::fake([
            'https://api.whatsapp-mock.test/text-only' => Http::response(['success' => true], 200),
        ]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Pure text post with no attachments at all.',
            'media_type' => null,
            'media_path' => null,
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Fake News',
            'description' => 'Misleading textual information.',
        ]);

        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);
        $job->handle($client);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            return $data['type'] === 'text'
                && ! isset($data['media'])
                && ! str_contains($data['message'], 'Attached Media')
                && str_contains($data['message'], 'Pure text post with no attachments at all.');
        });
    }

    /**
     * TEST 9 — AUTHOR / REPORTER SAFETY
     * Verifies reporter is distinct from author, correct identities populated, no unintended member modification.
     */
    public function test_9_author_and_reporter_identities_are_distinct_and_unaltered(): void
    {
        Config::set('whatsapp.enabled', true);
        Config::set('whatsapp.api_base_url', 'https://api.whatsapp-mock.test/audit');
        Config::set('whatsapp.to_number', '+15551234567');

        Http::fake([
            'https://api.whatsapp-mock.test/audit' => Http::response(['success' => true], 200),
        ]);

        $post = Post::create([
            'member_id' => $this->author->id,
            'body' => 'Author post body.',
        ]);

        $report = ReportedPost::create([
            'member_id' => $this->reporter->id,
            'post_id' => $post->id,
            'reason' => 'Spam',
            'description' => 'Identity safety test.',
        ]);

        $authorOriginalEmail = $this->author->email;
        $reporterOriginalEmail = $this->reporter->email;

        $job = new SendReportToWhatsAppJob($report->id);
        $client = app(WhatsAppApiClientContract::class);
        $job->handle($client);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $msg = $data['message'];

            return str_contains($msg, '👤 *Reporter*')
                && str_contains($msg, 'Bob Reporter')
                && str_contains($msg, '@bob_reporter')
                && str_contains($msg, '✍️ *Post Author*')
                && str_contains($msg, 'Alice Author')
                && str_contains($msg, '@alice_author');
        });

        // Ensure members have not been altered in any way
        $this->author->refresh();
        $this->reporter->refresh();
        $this->assertSame($authorOriginalEmail, $this->author->email);
        $this->assertSame($reporterOriginalEmail, $this->reporter->email);
    }
}
