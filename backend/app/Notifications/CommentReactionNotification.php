<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentReactionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post,
        public PostComment $comment,
        public string $reaction
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $emojiMap = [
            'like' => '👍',
            'love' => '❤️',
            'haha' => '😆',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😡',
        ];
        $emoji = $emojiMap[$this->reaction] ?? '👍';

        return [
            'title' => 'Comment reaction',
            'message' => $this->actor->name.' reacted '.$emoji.' to your comment.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'comment',
            'reference_id' => (string) $this->comment->id,
            'post_id' => (string) $this->post->id,
            'comment_id' => (string) $this->comment->id,
            'icon' => 'thumbs-up',
            'category' => 'comments',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
