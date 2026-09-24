<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $icon = 'bell',
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public ?string $actionUrl = null,
        public array $metadata = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'body' => $this->message,
            'icon' => $this->icon,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'action_url' => $this->actionUrl,
            'metadata' => $this->metadata,
        ];
    }
}
