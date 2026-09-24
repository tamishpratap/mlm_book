<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendCreatedPostNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New post from a Connection',
            'message' => $this->actor->name.' shared a new post.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'post_id' => $this->post->id,
            'icon' => 'file-text',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
