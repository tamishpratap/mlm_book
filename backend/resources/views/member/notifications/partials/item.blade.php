@php
    $data = $notification->data;
    $actorName = is_string($data['actor_name'] ?? null) ? $data['actor_name'] : 'MLM Book Member';
    $actorPhoto = is_string($data['actor_photo'] ?? null) ? $data['actor_photo'] : null;
    $hasActorPhoto = $actorPhoto
        && str_starts_with($actorPhoto, 'uploads/profile/')
        && ! str_contains($actorPhoto, '..')
        && $actorPhoto === 'uploads/profile/'.basename($actorPhoto)
        && file_exists(public_path($actorPhoto));
    $initials = collect(preg_split('/\s+/', trim($actorName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
    $allowedIcons = ['user-plus', 'user-check', 'user-x', 'file-text', 'thumbs-up', 'heart', 'message-circle', 'share-2', 'eye', 'bell'];
    $icon = in_array($data['icon'] ?? null, $allowedIcons, true) ? $data['icon'] : 'bell';
    $title = is_string($data['title'] ?? null) ? $data['title'] : 'Notification';
    $message = is_string($data['message'] ?? null) ? $data['message'] : 'You have a new notification.';
@endphp

<div class="notification-item {{ $notification->unread() ? 'is-unread' : 'is-read' }}" data-notification-item="{{ $notification->id }}">
    <form
        class="notification-item__main"
        method="POST"
        action="{{ route('member.notifications.read', $notification->id) }}"
        data-notification-read-form
    >
        @csrf
        <button type="submit" aria-label="{{ $notification->unread() ? 'Unread notification: ' : 'Notification: ' }}{{ $title }}">
            <span class="notification-item__avatar">
                @if ($hasActorPhoto)
                    <img src="{{ asset($actorPhoto) }}" alt="{{ $actorName }}" loading="lazy">
                @else
                    <span aria-hidden="true">{{ $initials }}</span>
                @endif
                <i data-lucide="{{ $icon }}" aria-hidden="true"></i>
            </span>
            <span class="notification-item__copy">
                <strong>{{ $title }}</strong>
                <span>{{ $message }}</span>
                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
            </span>
            @if ($notification->unread())
                <span class="notification-item__unread" aria-label="Unread"></span>
            @endif
        </button>
    </form>

    <form method="POST" action="{{ route('member.notifications.destroy', $notification->id) }}" data-notification-delete-form>
        @csrf
        @method('DELETE')
        <button class="notification-item__delete-btn" type="submit" aria-label="Delete notification" title="Delete notification">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    </form>
</div>
