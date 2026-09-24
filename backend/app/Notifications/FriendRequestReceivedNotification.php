<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestReceivedNotification extends Notification
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
            'title' => 'New Connection request',
            'message' => $this->actor->name.' sent you a connection request.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'friendship_id' => $this->friendship->id,
            'icon' => 'user-plus',
            'url' => route('member.friend-requests.index'),
        ];
    }
}
