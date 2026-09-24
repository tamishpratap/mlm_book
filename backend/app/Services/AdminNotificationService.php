<?php

namespace App\Services;

use App\Models\Admin;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AdminNotificationService
{
    /**
     * Send a real database notification to all active administrators.
     *
     * @param string $title
     * @param string $message
     * @param string $icon
     * @param string|null $sourceType
     * @param string|null $sourceId
     * @param string|null $actionUrl
     * @param array $metadata
     * @return int Number of admins notified
     */
    public static function notify(
        string $title,
        string $message,
        string $icon = 'bell',
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $actionUrl = null,
        array $metadata = []
    ): int {
        try {
            // Retrieve active admins; fallback to all admins if none are explicitly marked 'active'
            $admins = Admin::where('status', 'active')->get();
            if ($admins->isEmpty()) {
                $admins = Admin::all();
            }

            if ($admins->isEmpty()) {
                return 0;
            }

            $notification = new AdminAlertNotification(
                title: $title,
                message: $message,
                icon: $icon,
                sourceType: $sourceType,
                sourceId: $sourceId,
                actionUrl: $actionUrl,
                metadata: $metadata
            );

            Notification::send($admins, $notification);

            return $admins->count();
        } catch (Throwable $e) {
            Log::error('AdminNotificationService failed to dispatch notification', [
                'error' => $e->getMessage(),
                'title' => $title,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return 0;
        }
    }
}
