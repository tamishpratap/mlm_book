<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostLikeNotification extends Notification
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
            'title' => 'Post liked',
            'message' => $this->actor->name.' liked your post.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'post',
            'reference_id' => (string) $this->post->id,
            'icon' => 'thumbs-up',
            'category' => 'posts',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
