<?php

namespace App\Services\ContentModeration;

use App\Contracts\ContentModerationProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RemoteContentModerationProvider implements ContentModerationProvider
{
    protected string $url;
    protected string $secret;
    protected int $timeout;
    protected ModerationPolicy $policy;

    public function __construct(?ModerationPolicy $policy = null)
    {
        $this->url = rtrim(config('content_moderation.url', 'http://127.0.0.1:3001'), '/');
        $this->secret = config('content_moderation.secret', 'mlm-moderation-dev-secret-2026');
        $this->timeout = (int) config('content_moderation.timeout', 10);
        $this->policy = $policy ?: new ModerationPolicy();
    }

    /**
     * Moderate an image file against content safety policies via the independent Node microservice.
     */
    public function moderateImage(
        string $absolutePath,
        string $mimeType,
        string $sha256,
        string $context
    ): ModerationResult {
        $startTime = microtime(true);
        $timestamp = (string) time();
        $nonce = (string) Str::random(16);
        $cleanSha256 = strtolower(trim($sha256));

        // Sign request with HMAC-SHA256
        $payloadToSign = "{$timestamp}\n{$nonce}\n{$cleanSha256}";
        $signature = hash_hmac('sha256', $payloadToSign, $this->secret);

        $fileHandle = @fopen($absolutePath, 'r');
        if (! $fileHandle) {
            return ModerationResult::failClosed(
                errorCode: 'QUARANTINE_READ_ERROR',
                userMessage: 'Unable to process image file.',
                fileHash: $cleanSha256,
                source: 'remote_provider'
            );
        }

        try {
            $endpoint = "{$this->url}/v1/moderate/image";

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-Moderation-Timestamp' => $timestamp,
                    'X-Moderation-Nonce' => $nonce,
                    'X-Moderation-SHA256' => $cleanSha256,
                    'X-Moderation-Signature' => $signature,
                    'Accept' => 'application/json',
                ])
                ->attach('file', $fileHandle, basename($absolutePath))
                ->post($endpoint);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                $status = $response->status();
                Log::warning("[ContentModeration] Remote service returned HTTP {$status}", [
                    'context' => $context,
                    'file_hash' => substr($cleanSha256, 0, 12),
                    'duration_ms' => $durationMs,
                ]);

                return ModerationResult::failClosed(
                    errorCode: "PROVIDER_HTTP_{$status}",
                    userMessage: "We couldn't complete the image safety check. Please try again.",
                    fileHash: $cleanSha256,
                    durationMs: $durationMs,
                    source: 'remote_provider'
                );
            }

            $data = $response->json();
            if (! is_array($data) || empty($data['success']) || ! isset($data['predictions'])) {
                Log::error('[ContentModeration] Malformed response from moderation service', [
                    'context' => $context,
                    'file_hash' => substr($cleanSha256, 0, 12),
                ]);

                return ModerationResult::failClosed(
                    errorCode: 'MALFORMED_PROVIDER_RESPONSE',
                    userMessage: "We couldn't complete the image safety check. Please try again.",
                    fileHash: $cleanSha256,
                    durationMs: $durationMs,
                    source: 'remote_provider'
                );
            }

            // TOCTOU Verification: Ensure returned file hash matches the quarantined file hash
            $returnedHash = strtolower(trim((string) ($data['fileSha256'] ?? '')));
            if ($returnedHash !== $cleanSha256) {
                Log::error('[ContentModeration] Hash mismatch between quarantine and provider response', [
                    'expected' => substr($cleanSha256, 0, 12),
                    'received' => substr($returnedHash, 0, 12),
                    'context' => $context,
                ]);

                return ModerationResult::failClosed(
                    errorCode: 'HASH_MISMATCH',
                    userMessage: 'Image integrity verification failed. Please try again.',
                    fileHash: $cleanSha256,
                    providerRequestId: $data['requestId'] ?? null,
                    durationMs: $durationMs,
                    source: 'remote_provider'
                );
            }

            $predictions = (array) $data['predictions'];
            $requestId = $data['requestId'] ?? null;

            // Authoritative server-side policy evaluation
            return $this->policy->evaluate(
                predictions: $predictions,
                context: $context,
                fileHash: $cleanSha256,
                durationMs: $durationMs,
                providerRequestId: $requestId,
                source: 'remote_provider'
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('[ContentModeration] Connection failed to moderation service', [
                'error' => $e->getMessage(),
                'context' => $context,
                'duration_ms' => $durationMs,
            ]);

            return ModerationResult::failClosed(
                errorCode: 'PROVIDER_CONNECTION_FAILED',
                userMessage: "We couldn't complete the image safety check. Please try again.",
                fileHash: $cleanSha256,
                durationMs: $durationMs,
                source: 'remote_provider'
            );
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('[ContentModeration] Unexpected provider error', [
                'error' => $e->getMessage(),
                'context' => $context,
                'duration_ms' => $durationMs,
            ]);

            return ModerationResult::failClosed(
                errorCode: 'PROVIDER_EXCEPTION',
                userMessage: "We couldn't complete the image safety check. Please try again.",
                fileHash: $cleanSha256,
                durationMs: $durationMs,
                source: 'remote_provider'
            );
        } finally {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }
    }
}
