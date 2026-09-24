@php
    $m = $membership->member;
    $hasPhoto = $m && $m->profile_photo
        && str_starts_with($m->profile_photo, 'uploads/profile/')
        && ! str_contains($m->profile_photo, '..')
        && file_exists(public_path($m->profile_photo));
    $initials = $m ? collect(preg_split('/\s+/', trim($m->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') : 'M';
@endphp

<div class="friend-card" data-community-request-card="{{ $membership->id }}">
    <div style="position: relative;">
        @if ($hasPhoto)
            <img class="avatar avatar--lg" src="{{ asset($m->profile_photo) }}" alt="{{ $m->name }}" loading="lazy">
        @else
            <span class="friend-card__initials">{{ $initials }}</span>
        @endif
    </div>

    <div class="friend-card__info">
        <strong>{{ $m->name ?? 'Member' }}</strong>
        <span class="friend-card__handle">{{ '@' . ($m->username ?? 'user' . $m->id) }}</span>
        <span style="font-size: 11.5px; color: var(--color-text-secondary); margin-top: 4px;">
            Requested {{ $membership->created_at->diffForHumans() }}
        </span>
    </div>

    <div class="friend-card__actions" style="width: 100%; display: flex; gap: 8px; margin-top: auto;">
        <button
            type="button"
            class="member-button member-button--primary"
            style="flex: 1; justify-content: center; padding: 7px 12px; font-size: 12px;"
            data-community-request-handle="{{ route('member.community.requests.handle', [$community, $membership]) }}"
            data-action="accept"
        >
            <i data-lucide="check" style="width: 14px; height: 14px;"></i> Accept
        </button>

        <button
            type="button"
            class="member-button member-button--secondary"
            style="flex: 1; justify-content: center; padding: 7px 12px; font-size: 12px; color: var(--color-error, #ef4444);"
            data-community-request-handle="{{ route('member.community.requests.handle', [$community, $membership]) }}"
            data-action="reject"
        >
            <i data-lucide="x" style="width: 14px; height: 14px;"></i> Reject
        </button>
    </div>
</div>
