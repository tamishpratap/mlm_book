<?php

namespace App\Notifications;

use App\Models\Community;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CommunityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Member $actor,
        public Community $community,
        public string $action,
        public string $title,
        public string $messageText,
        public string $icon = 'users',
        public ?string $destinationUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $url = $this->destinationUrl ?: route('member.community.show', [$this->community]);

        return [
            'title' => $this->title,
            'message' => $this->messageText,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo,
            'community_id' => $this->community->id,
            'community_name' => $this->community->name,
            'community_slug' => $this->community->slug,
            'action' => $this->action,
            'reference_type' => 'community',
            'reference_id' => (string) $this->community->id,
            'icon' => $this->icon,
            'category' => 'community',
            'url' => $url,
        ];
    }
}
