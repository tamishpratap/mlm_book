<?php

namespace App\Jobs;

use App\Contracts\WhatsAppApiClientContract;
use App\Models\ReportedPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReportToWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * The ReportedPost primary key ID.
     */
    public int $reportId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppApiClientContract $client): void
    {
        // 1. Check if WhatsApp notification feature is globally enabled
        if (! config('whatsapp.enabled', false)) {
            Log::debug("SendReportToWhatsAppJob: WhatsApp notifications disabled (WHATSAPP_ENABLED=false). Skipping Report #{$this->reportId}.");
            return;
        }

        // 2. Fetch authoritative report and related models matching Admin panel inspection
        $report = ReportedPost::with(['member', 'post.member', 'post.originalPost.member'])->find($this->reportId);

        if (! $report) {
            Log::warning("SendReportToWhatsAppJob: ReportedPost #{$this->reportId} not found in database. Skipping notification.");
            return;
        }

        // 3. Extract core details
        $reporter = $report->member;
        $post = $report->post;
        $orig = $post?->originalPost;
        $author = $post?->member;

        $mediaType = $post?->media_type ?: $orig?->media_type;
        $mediaPath = $post?->media_path ?: $orig?->media_path;
        $mediaUrl = $post?->media_url ?: $orig?->media_url;

        $reportDate = $report->created_at ? $report->created_at->format('d M Y, h:i A') : 'N/A';
        $postDate = $post?->created_at ? $post->created_at->format('d M Y, h:i A') : 'N/A';
        $adminReportUrl = url('/admin/reports/post/' . $report->id);

        // 4. Construct formatted WhatsApp message
        $message = $this->buildMessageBody([
            'report_id' => $report->id,
            'report_date' => $reportDate,
            'status' => ucfirst($report->status ?: 'pending'),
            'reason' => $report->reason,
            'description' => $report->description ?: 'No additional description provided.',
            'reporter_name' => $reporter?->name ?? 'Anonymous Member',
            'reporter_username' => $reporter?->user_id ? '@' . $reporter->user_id : 'N/A',
            'reporter_id' => $reporter?->id ?? 'N/A',
            'reporter_email' => $reporter?->email ?? 'N/A',
            'post_id' => $post?->id ?? 'N/A',
            'post_date' => $postDate,
            'post_status' => $post ? ($post->is_hidden ? 'Hidden' : 'Active') : 'Unavailable',
            'post_body' => $post?->body ? trim($post->body) : ($mediaType ? '[' . ucfirst($mediaType) . ' Post]' : '[Empty Post]'),
            'author_name' => $author?->name ?? 'N/A',
            'author_username' => $author?->user_id ? '@' . $author->user_id : 'N/A',
            'author_id' => $author?->id ?? 'N/A',
            'author_email' => $author?->email ?? 'N/A',
            'is_shared' => (bool) ($post?->original_post_id),
            'original_author' => $orig?->member?->name,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'admin_url' => $adminReportUrl,
        ]);

        // 5. Construct normalized payload array
        $payload = [
            'to' => config('whatsapp.to_number'),
            'message' => $message,
            'type' => $mediaUrl ? ($mediaType ?? 'image') : 'text',
            'report' => [
                'id' => $report->id,
                'type' => 'post',
                'type_label' => 'Post',
                'reason' => $report->reason,
                'description' => $report->description,
                'status' => $report->status ?: 'pending',
                'created_at' => $report->created_at?->toIso8601String(),
                'updated_at' => $report->updated_at?->toIso8601String(),
                'admin_url' => $adminReportUrl,
            ],
            'reporter' => [
                'id' => $reporter?->id,
                'name' => $reporter?->name ?? 'Anonymous Member',
                'user_id' => $reporter?->user_id,
                'email' => $reporter?->email,
                'profile_url' => $reporter ? url('/admin/members/' . $reporter->id) : null,
            ],
            'post' => [
                'id' => $post?->id,
                'body' => $post?->body,
                'created_at' => $post?->created_at?->toIso8601String(),
                'status' => $post ? ($post->is_hidden ? 'Hidden' : 'Active') : 'Unavailable',
                'is_shared' => (bool) ($post?->original_post_id),
            ],
            'post_owner' => [
                'id' => $author?->id,
                'name' => $author?->name ?? 'N/A',
                'user_id' => $author?->user_id,
                'email' => $author?->email,
                'profile_url' => $author ? url('/admin/members/' . $author->id) : null,
            ],
            'media' => [
                'has_media' => (bool) ($mediaUrl || $mediaPath),
                'type' => $mediaType,
                'path' => $mediaPath,
                'url' => $mediaUrl,
            ],
            'metadata' => [
                'source' => 'mlm_book_post_report',
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        // 6. Check if required endpoint configuration is present
        if (empty(config('whatsapp.api_base_url')) || empty(config('whatsapp.to_number'))) {
            Log::warning("SendReportToWhatsAppJob: WhatsApp API credentials or destination number not configured for Report #{$this->reportId}. Skipping HTTP dispatch.");
            return;
        }

        // 7. Dispatch to WhatsApp API client
        $delivered = $client->sendReportNotification($payload);

        if (! $delivered) {
            // Throw exception to trigger queue retry up to $tries attempts
            throw new \RuntimeException("WhatsApp report notification delivery failed for Report #{$this->reportId}.");
        }
    }

    /**
     * Handle permanent job failure after all retries are exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error(sprintf(
            'SendReportToWhatsAppJob: Permanent failure delivering WhatsApp notification for Report #%d. Database report record remains completely intact. Error: %s',
            $this->reportId,
            $exception?->getMessage() ?? 'Unknown error'
        ));
    }

    /**
     * Format a clean, human-readable WhatsApp message.
     *
     * @param array<string, mixed> $d
     * @return string
     */
    protected function buildMessageBody(array $d): string
    {
        $lines = [
            "🚨 *NEW POST REPORT*",
            "",
            "📋 *Report Information*",
            "• *Report ID:* #{$d['report_id']}",
            "• *Date:* {$d['report_date']}",
            "• *Status:* {$d['status']}",
            "• *Reason:* {$d['reason']}",
            "• *Description:* {$d['description']}",
            "",
            "👤 *Reporter*",
            "• *Name:* {$d['reporter_name']}",
            "• *Username:* {$d['reporter_username']}",
            "• *Member ID:* #{$d['reporter_id']}",
            "• *Email:* {$d['reporter_email']}",
            "",
            "📝 *Reported Post*",
            "• *Post ID:* #{$d['post_id']}",
            "• *Created:* {$d['post_date']}",
            "• *Status:* {$d['post_status']}",
            "• *Content:*",
            $d['post_body'],
            "",
            "✍️ *Post Author*",
            "• *Name:* {$d['author_name']}",
            "• *Username:* {$d['author_username']}",
            "• *Member ID:* #{$d['author_id']}",
            "• *Email:* {$d['author_email']}",
        ];

        if (! empty($d['is_shared']) && ! empty($d['original_author'])) {
            $lines[] = "";
            $lines[] = "🔁 *Reshared Post*";
            $lines[] = "• *Original Author:* {$d['original_author']}";
        }

        if (! empty($d['media_url'])) {
            $typeLabel = ucfirst($d['media_type'] ?: 'Media');
            $lines[] = "";
            $lines[] = "🖼️ *Attached {$typeLabel}*";
            $lines[] = "• *Type:* {$d['media_type']}";
            $lines[] = "• *URL:* {$d['media_url']}";
        }

        $lines[] = "";
        $lines[] = "🔗 *Admin Moderation Link*";
        $lines[] = $d['admin_url'];

        return implode("\n", $lines);
    }
}
