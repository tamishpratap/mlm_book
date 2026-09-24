<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post,
        public string $commentText
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Post comment',
            'message' => $this->actor->name.' commented on your post: "'.\Illuminate\Support\Str::limit($this->commentText, 50).'"',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'post',
            'reference_id' => (string) $this->post->id,
            'post_id' => (string) $this->post->id,
            'icon' => 'message-circle',
            'category' => 'comments',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
