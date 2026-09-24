<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post,
        public PostComment $parentComment,
        public string $replyText
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Comment reply',
            'message' => $this->actor->name.' replied to your comment: "'.\Illuminate\Support\Str::limit($this->replyText, 50).'"',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'comment',
            'reference_id' => (string) $this->parentComment->id,
            'post_id' => (string) $this->post->id,
            'comment_id' => (string) $this->parentComment->id,
            'icon' => 'message-circle',
            'category' => 'comments',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
