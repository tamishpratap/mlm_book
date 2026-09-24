<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostShareNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Post shared',
            'message' => $this->actor->name.' shared your post.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'post',
            'reference_id' => (string) $this->post->id,
            'icon' => 'share-2',
            'category' => 'posts',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
