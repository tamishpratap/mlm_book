<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $systemMessage,
        public ?string $targetUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->systemMessage,
            'actor_id' => 0,
            'actor_name' => 'MLM Book System',
            'actor_photo' => null,
            'reference_type' => 'system',
            'reference_id' => '0',
            'icon' => 'bell',
            'category' => 'system',
            'url' => $this->targetUrl ?? route('member.notifications.index'),
        ];
    }
}
