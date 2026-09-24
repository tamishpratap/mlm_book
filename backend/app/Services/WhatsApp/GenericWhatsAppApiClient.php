<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppApiClientContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenericWhatsAppApiClient implements WhatsAppApiClientContract
{
    /**
     * Send a normalized post report notification payload to the configured WhatsApp endpoint.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function sendReportNotification(array $payload): bool
    {
        return $this->sendMessage($payload);
    }

    /**
     * Send a message payload to the configured WhatsApp destination.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function sendMessage(array $payload): bool
    {
        $enabled = (bool) config('whatsapp.enabled', false);
        if (! $enabled) {
            Log::debug('WhatsApp notification skipped: WHATSAPP_ENABLED is set to false.');
            return false;
        }

        $baseUrl = config('whatsapp.api_base_url');
        $toNumber = $payload['to'] ?? config('whatsapp.to_number');
        $token = config('whatsapp.api_token');
        $timeout = (int) config('whatsapp.timeout', 15);

        if (empty($baseUrl) || empty($toNumber)) {
            Log::warning('WhatsApp notification skipped: WHATSAPP_API_BASE_URL or WHATSAPP_TO_NUMBER is not configured.');
            return false;
        }

        $messageText = $payload['message'] ?? $payload['text'] ?? '';
        $media = $payload['media'] ?? null;

        // Construct normalized provider-neutral request payload
        $requestPayload = [
            'to' => $toNumber,
            'type' => ! empty($media['url']) ? ($media['type'] ?? 'image') : 'text',
            'message' => $messageText,
            'report_id' => $payload['report']['id'] ?? null,
            'report' => $payload['report'] ?? null,
        ];

        if (! empty($media['url'])) {
            $requestPayload['media'] = [
                'type' => $media['type'] ?? 'image',
                'url' => $media['url'],
            ];
        }

        // Include non-sensitive metadata for future provider adapters
        if (! empty($payload['metadata'])) {
            $requestPayload['metadata'] = $payload['metadata'];
        }

        try {
            $client = Http::timeout($timeout)
                ->acceptJson()
                ->asJson();

            if (! empty($token)) {
                $client = $client->withToken($token);
            }

            $response = $client->post($baseUrl, $requestPayload);

            if ($response->successful()) {
                Log::info(sprintf(
                    'WhatsApp report notification delivered successfully for Report #%s.',
                    $payload['report']['id'] ?? 'unknown'
                ));
                return true;
            }

            // Safe error logging: Log HTTP status and limited body snippet without auth headers or secrets
            $safeBody = Str::limit(strip_tags((string) $response->body()), 250);
            Log::error(sprintf(
                'WhatsApp API request returned HTTP %d for Report #%s. Response: %s',
                $response->status(),
                $payload['report']['id'] ?? 'unknown',
                $safeBody
            ));

            return false;
        } catch (\Throwable $e) {
            // Safe logging: Never include tokens or secret credentials in logs
            Log::error(sprintf(
                'WhatsApp API connection exception for Report #%s: %s',
                $payload['report']['id'] ?? 'unknown',
                $e->getMessage()
            ));

            return false;
        }
    }
}
