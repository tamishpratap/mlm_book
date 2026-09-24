<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostReactionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post,
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
            'title' => 'Post reaction',
            'message' => $this->actor->name.' reacted '.$emoji.' to your post.',
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
