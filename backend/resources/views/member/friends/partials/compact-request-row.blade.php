@php
    $cleanProfile = $friend->profile_photo ? ltrim(str_replace('\\', '/', $friend->profile_photo), '/') : null;
    $hasPhoto = $cleanProfile
        && str_starts_with($cleanProfile, 'uploads/profile/')
        && ! str_contains($cleanProfile, '..')
        && file_exists(public_path($cleanProfile));
    $photoUrl = $hasPhoto
        ? asset($cleanProfile).'?v='.($friend->updated_at?->timestamp ?? now()->timestamp)
        : null;

    $initials = collect(preg_split('/\s+/', trim($friend->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';

    $username = filled($friend->user_id) ? '@'.$friend->user_id : '@member';
@endphp

<div class="connection-request-row" data-request-id="{{ $friendship->id }}">
    <div class="connection-request-row__main">
        <a class="connection-request-row__avatar-link" href="{{ route('member.people.show', $friend) }}" aria-label="View {{ $friend->name }}'s profile">
            @if ($photoUrl)
                <img class="connection-request-row__avatar-img" src="{{ $photoUrl }}" alt="" loading="lazy" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline-flex';">
                <span class="avatar post-avatar-initials connection-request-row__avatar-fallback" style="display: none;">{{ $initials }}</span>
            @else
                <span class="avatar post-avatar-initials connection-request-row__avatar-fallback">{{ $initials }}</span>
            @endif
        </a>

        <div class="connection-request-row__details">
            <div class="connection-request-row__name-wrap">
                <a class="connection-request-row__name-link" href="{{ route('member.people.show', $friend) }}" title="{{ $friend->name }}">
                    <span class="connection-request-row__name">{{ $friend->name }}</span>
                </a>
            </div>

            <div class="connection-request-row__username-wrap">
                <a class="connection-request-row__username-link" href="{{ route('member.people.show', $friend) }}" title="{{ $username }}">
                    <span class="connection-request-row__username">{{ $username }}</span>
                </a>
            </div>

            <div class="connection-request-row__meta">
                <span class="connection-request-row__no-mutual-text">No mutual connections</span>
            </div>
        </div>
    </div>

    <div class="connection-request-row__actions">
        @if ($friendshipState === 'pending_received')
            <form class="friendship-action-form" method="POST" action="{{ route('member.friend-requests.accept', $friendship) }}">
                @csrf
                <button class="member-button member-button--primary connection-request-btn connection-request-btn--accept" type="submit">
                    <i data-lucide="check" aria-hidden="true"></i>
                    <span>Accept</span>
                </button>
            </form>
            <form class="friendship-action-form" method="POST" action="{{ route('member.friend-requests.reject', $friendship) }}">
                @csrf
                <button class="member-button member-button--secondary connection-request-btn connection-request-btn--reject" type="submit">
                    <i data-lucide="x" aria-hidden="true"></i>
                    <span>Reject</span>
                </button>
            </form>
        @elseif ($friendshipState === 'pending_sent')
            <form class="friendship-action-form" method="POST" action="{{ route('member.friend-requests.cancel', $friendship) }}">
                @csrf
                @method('DELETE')
                <button class="member-button member-button--secondary connection-request-btn connection-request-btn--cancel" type="submit">
                    <i data-lucide="user-minus" aria-hidden="true"></i>
                    <span>Cancel Request</span>
                </button>
            </form>
        @endif
    </div>
</div>
