<?php

namespace App\Contracts;

interface WhatsAppApiClientContract
{
    /**
     * Send a normalized post report notification payload to the configured WhatsApp endpoint.
     *
     * @param array<string, mixed> $payload Normalized report data including formatted text, recipient, and metadata
     * @return bool True if the notification request was accepted/dispatched successfully
     */
    public function sendReportNotification(array $payload): bool;

    /**
     * Send a general message payload to the configured WhatsApp destination.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function sendMessage(array $payload): bool;
}
