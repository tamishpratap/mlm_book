@php
    $hasLogo = $community->logo
        && file_exists(public_path($community->logo));
    $hasCover = $community->cover_photo
        && file_exists(public_path($community->cover_photo));
    $initials = collect(preg_split('/\s+/', trim($community->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'C';
@endphp

<article class="community-card">
    <div class="community-card__cover">
        @if ($hasCover)
            <img src="{{ asset($community->cover_photo) }}" alt="{{ $community->name }} cover" loading="lazy">
        @endif
        <div class="community-card__badges">
            <span class="community-badge community-badge--category">{{ $community->category }}</span>
            <span class="community-badge community-badge--visibility">
                @if ($community->visibility === 'private')
                    <i data-lucide="lock" aria-hidden="true"></i> Private
                @elseif ($community->visibility === 'invite_only')
                    <i data-lucide="mail-check" aria-hidden="true"></i> Invite Only
                @elseif ($community->visibility === 'secret')
                    <i data-lucide="eye-off" aria-hidden="true"></i> Secret
                @else
                    <i data-lucide="globe" aria-hidden="true"></i> Public
                @endif
            </span>
        </div>
    </div>

    <div class="community-card__body">
        <div class="community-card__avatar">
            @if ($hasLogo)
                <img src="{{ asset($community->logo) }}" alt="{{ $community->name }} logo" loading="lazy">
            @else
                <div class="community-avatar-initials">{{ $initials }}</div>
            @endif
        </div>

        <h3 class="community-card__title">
            <a href="{{ route('member.community.show', $community) }}">{{ $community->name }}</a>
        </h3>

        <div class="community-card__owner">
            <span>Created by</span>
            <strong>{{ $community->owner->name ?? 'Member' }}</strong>
        </div>

        <p class="community-card__description">
            {{ $community->description ?: 'No description provided for this community yet.' }}
        </p>

        <div class="community-card__footer">
            <div class="community-card__meta">
                <span>
                    <i data-lucide="users" aria-hidden="true"></i>
                    {{ number_format($community->member_count ?? 0) }} {{ \Illuminate\Support\Str::plural('member', $community->member_count ?? 0) }}
                </span>
            </div>

            <button type="button" class="community-btn--disabled" disabled title="Joining communities will be enabled in Phase 2">
                <i data-lucide="user-plus" aria-hidden="true"></i>
                <span>Join (Phase 2)</span>
            </button>
        </div>
    </div>
</article>
