<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Friendship $friendship,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Connection request declined',
            'message' => $this->actor->name.' declined your connection request.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'friendship_id' => $this->friendship->id,
            'icon' => 'user-x',
            'url' => route('member.friend-requests.index'),
        ];
    }
}
