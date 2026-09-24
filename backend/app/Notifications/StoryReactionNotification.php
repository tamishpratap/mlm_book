<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StoryReactionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Story $story,
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
            'title' => 'Story reaction',
            'message' => $this->actor->name.' reacted '.$emoji.' to your story.',
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'reference_type' => 'story',
            'reference_id' => (string) $this->story->id,
            'icon' => 'heart',
            'category' => 'stories',
            'url' => route('member.stories.show', $this->story),
        ];
    }
}
