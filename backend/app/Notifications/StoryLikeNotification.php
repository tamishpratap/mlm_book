<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StoryLikeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Story $story
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Story liked',
            'message' => $this->actor->name.' liked your story.',
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
