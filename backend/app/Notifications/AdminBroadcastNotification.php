<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $broadcastMessage,
        public string $audience = 'all',
        public ?string $targetUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ADMIN_ANNOUNCEMENT',
            'notification_type' => 'ADMIN_ANNOUNCEMENT',
            'source' => 'ADMIN',
            'source_type' => 'ADMIN',
            'title' => $this->title,
            'message' => $this->broadcastMessage,
            'body' => $this->broadcastMessage,
            'actor_id' => 0,
            'actor_name' => 'Admin Announcement',
            'sender' => 'Admin Broadcast',
            'actor_photo' => null,
            'category' => 'system',
            'reference_type' => 'admin_announcement',
            'reference_id' => null,
            'icon' => 'bell',
            'audience' => $this->audience,
            'url' => $this->targetUrl ?? (function_exists('route') ? route('member.notifications.index') : '/member/notifications'),
            'sent_at' => now()->toDateTimeString(),
        ];
    }
}
