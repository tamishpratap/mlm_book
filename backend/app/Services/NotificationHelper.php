<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class NotificationHelper
{
    /**
     * Safely send a notification to a member.
     * Prevents self-notifications and prunes stale unread duplicate notifications of the same type.
     */
    public static function send(
        Member $receiver,
        Notification $notification,
        string $referenceType,
        int|string $referenceId,
        Member $actor
    ): void {
        // Rule 1: No self-notifications
        if ($actor->id === $receiver->id) {
            return;
        }

        // Rule 2: Prune stale unread notifications of same type for same reference from same actor
        try {
            $notificationClass = get_class($notification);

            $receiver->unreadNotifications()
                ->where('type', $notificationClass)
                ->where('data->actor_id', $actor->id)
                ->where('data->reference_type', $referenceType)
                ->where('data->reference_id', (string) $referenceId)
                ->delete();
        } catch (\Throwable $e) {
            // Ignore database json query errors gracefully
        }

        // Rule 3: Dispatch fresh notification
        $receiver->notify($notification);
    }
}
