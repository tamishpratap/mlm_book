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
    $mode = $mode ?? 'connection'; // 'connection' | 'suggestion'
    $mutualCount = $mutualCount ?? (auth('member')->check() ? auth('member')->user()->mutualFriendsCount($friend->id) : 0);
    $location = collect([$friend->city, $friend->country])->filter()->implode(', ');
@endphp

<div class="connection-request-row compact-member-row" data-member-id="{{ $friend->id }}">
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
                @if ($mutualCount > 0)
                    <span class="connection-request-row__mutual-text">{{ $mutualCount }} mutual {{ \Illuminate\Support\Str::plural('connection', $mutualCount) }}</span>
                @elseif (filled($location))
                    <span class="connection-request-row__no-mutual-text">{{ $location }}</span>
                @else
                    <span class="connection-request-row__no-mutual-text">No mutual connections</span>
                @endif
            </div>
        </div>
    </div>

    <div class="connection-request-row__actions">
        @if ($mode === 'connection')
            <a class="member-button member-button--secondary connection-request-btn" href="{{ route('member.people.show', $friend) }}">
                <i data-lucide="user" aria-hidden="true"></i>
                <span>View Profile</span>
            </a>
        @else
            @php
                $friendshipState = auth('member')->check()
                    ? \App\Models\Friendship::pairState(auth('member')->id(), $friend->id)
                    : 'none';
            @endphp
            @if ($friendshipState === 'none')
                <form class="friendship-action-form" method="POST" action="{{ route('member.friends.request', $friend) }}">
                    @csrf
                    <button class="member-button member-button--primary connection-request-btn connection-request-btn--connect" type="submit">
                        <i data-lucide="user-plus" aria-hidden="true"></i>
                        <span>Connect</span>
                    </button>
                </form>
            @elseif ($friendshipState === 'pending_sent')
                <button class="member-button member-button--secondary connection-request-btn" type="button" disabled>
                    <span>Requested</span>
                </button>
            @elseif ($friendshipState === 'friends')
                <a class="member-button member-button--secondary connection-request-btn" href="{{ route('member.people.show', $friend) }}">
                    <i data-lucide="users-round" aria-hidden="true"></i>
                    <span>Connected</span>
                </a>
            @endif
        @endif
    </div>
</div>
