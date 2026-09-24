<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send an OTP verification message to a WhatsApp number.
     *
     * @param string $phoneNumber E.164 formatted or standard phone number
     * @param string $otpCode 6-digit numeric OTP code
     * @param string|null $memberName Member's display name
     * @return array{success: bool, mode: string, message_id?: string, error?: string}
     */
    public function sendOtp(string $phoneNumber, string $otpCode, ?string $memberName = 'Member'): array
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        $name = trim($memberName ?: 'Member');

        $messageBody = "🔒 *MLM Book Account Verification*\n\n"
            . "Hello *{$name}*,\n\n"
            . "Your one-time WhatsApp verification code is:\n"
            . "👉 *{$otpCode}*\n\n"
            . "⏰ This code expires in 10 minutes.\n"
            . "⚠️ Please do not share this code with anyone.\n\n"
            . "Once verified, your account will receive the official *Green Verified Tick* badge on MLM Book! ✅";

        $apiUrl = config('services.whatsapp.api_url');
        $apiKey = config('services.whatsapp.api_key');
        $senderNumber = config('services.whatsapp.sender_number');
        $isDemo = empty($apiUrl) || empty($apiKey) || config('services.whatsapp.demo_mode', true);

        if ($isDemo) {
            // Demo Mode Simulation
            Log::info("================ [DEMO WHATSAPP API] ================");
            Log::info("To: {$cleanPhone}");
            Log::info("Recipient: {$name}");
            Log::info("OTP Code: {$otpCode}");
            Log::info("Message:\n{$messageBody}");
            Log::info("=====================================================");

            return [
                'success' => true,
                'mode' => 'demo',
                'message_id' => 'demo_' . uniqid(),
                'phone' => $cleanPhone,
                'demo_code' => $otpCode,
            ];
        }

        // Live WhatsApp API Request (e.g. UltraMsg, Maytapi, or Custom Gateway)
        try {
            $response = Http::timeout(10)->post($apiUrl, [
                'token' => $apiKey,
                'to' => $cleanPhone,
                'body' => $messageBody,
                'from' => $senderNumber,
            ]);

            if ($response->successful()) {
                Log::info("Live WhatsApp OTP delivered to {$cleanPhone}");
                return [
                    'success' => true,
                    'mode' => 'live',
                    'message_id' => $response->json('id') ?? $response->json('message_id') ?? 'live_' . uniqid(),
                ];
            }

            Log::error("Live WhatsApp API error: " . $response->body());
            return [
                'success' => false,
                'mode' => 'live',
                'error' => 'Failed to deliver message via WhatsApp gateway.',
            ];
        } catch (\Throwable $e) {
            Log::error("WhatsApp API connection exception: " . $e->getMessage());
            return [
                'success' => false,
                'mode' => 'live',
                'error' => 'WhatsApp gateway connection timeout.',
            ];
        }
    }
}
