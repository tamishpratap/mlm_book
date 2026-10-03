<?php

namespace App\Notifications;

use App\Models\DirectMessage;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DirectMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public DirectMessage $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Message',
            'message' => $this->actor->name . ' sent you a message.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'direct_message_id' => $this->message->id,
            'icon' => 'message-circle',
            'url' => '/member/business-directory?chat=' . $this->actor->id,
        ];
    }
}
