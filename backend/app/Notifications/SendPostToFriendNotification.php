<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SendPostToFriendNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Post $post,
        public ?string $note = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $messageText = $this->actor->name.' sent you a post.';
        if ($this->note) {
            $messageText .= ' "'.Str::limit($this->note, 60).'"';
        }

        return [
            'title' => 'Post Sent to You',
            'message' => $messageText,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'post',
            'reference_id' => (string) $this->post->id,
            'icon' => 'send',
            'category' => 'posts',
            'url' => route('member.posts.show', $this->post),
        ];
    }
}
