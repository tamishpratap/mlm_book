@extends('member.layouts.app')

@section('title', 'Disconnections')

@section('content')
<div class="blocked-users-page">
    <section class="card member-card blocked-users-card">
        <header class="blocked-users-header">
            <div class="blocked-users-header__info">
                <h1 class="blocked-users-header__title">Disconnections</h1>
                <p class="blocked-users-header__desc">{{ $blockedMembers->total() }} {{ \Illuminate\Support\Str::plural('member', $blockedMembers->total()) }} disconnected from interacting with your profile and content.</p>
            </div>
        </header>

        @if ($blockedMembers->isEmpty())
            <div class="notification-empty">
                <div class="notification-empty__icon">
                    <i data-lucide="shield-check" aria-hidden="true"></i>
                </div>
                <h2>No Disconnections</h2>
                <p>You haven't disconnected any members.</p>
            </div>
        @else
            <div class="blocked-users-grid">
                @foreach ($blockedMembers as $member)
                    @php
                        $cleanProfile = $member->profile_photo ? ltrim(str_replace('\\', '/', $member->profile_photo), '/') : null;
                        $hasPhoto = $cleanProfile
                            && str_starts_with($cleanProfile, 'uploads/profile/')
                            && ! str_contains($cleanProfile, '..')
                            && file_exists(public_path($cleanProfile));
                        $photoUrl = $hasPhoto
                            ? asset($cleanProfile).'?v='.($member->updated_at?->timestamp ?? now()->timestamp)
                            : null;
                        $initials = collect(preg_split('/\s+/', trim($member->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                            ->implode('') ?: 'M';
                        $username = filled($member->user_id) ? '@'.$member->user_id : '@member';
                    @endphp
                    <div class="blocked-card card" data-blocked-card="{{ $member->id }}">
                        <div class="blocked-card__header">
                            <a class="blocked-card__avatar" href="{{ route('member.people.show', $member) }}" aria-label="View {{ $member->name }}'s profile">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline-flex';">
                                    <span class="avatar post-avatar-initials" style="display: none; width: 44px; height: 44px; font-size: 15px;">{{ $initials }}</span>
                                @else
                                    <span class="avatar post-avatar-initials" style="width: 44px; height: 44px; font-size: 15px;">{{ $initials }}</span>
                                @endif
                            </a>
                            <div class="blocked-card__info">
                                <strong title="{{ $member->name }}">
                                    <a class="blocked-card__name-link" href="{{ route('member.people.show', $member) }}">
                                        <span class="blocked-card__name-text">{{ $member->name }}</span>
                                    </a>
                                </strong>
                                <small title="{{ $username }}">
                                    <a class="blocked-card__username-link" href="{{ route('member.people.show', $member) }}">
                                        {{ $username }}
                                    </a>
                                </small>
                            </div>
                        </div>
                        <div class="blocked-card__action">
                            @if (($member->friendship_state ?? 'none') === 'pending_sent')
                                <button class="member-button member-button--secondary connection-request-btn" type="button" disabled style="width: 100%;">
                                    <i data-lucide="user-minus" aria-hidden="true"></i>
                                    <span>Request Sent</span>
                                </button>
                            @elseif (($member->friendship_state ?? 'none') === 'friends')
                                <button class="member-button member-button--secondary connection-request-btn" type="button" disabled style="width: 100%;">
                                    <i data-lucide="check" aria-hidden="true"></i>
                                    <span>Connected</span>
                                </button>
                            @else
                                <form method="POST" action="{{ route('member.friends.request', $member) }}" data-connect-form="{{ $member->id }}" style="width: 100%;">
                                    @csrf
                                    <button class="member-button member-button--primary connection-request-btn connection-request-btn--connect" type="submit" style="width: 100%;">
                                        <i data-lucide="user-plus" aria-hidden="true"></i>
                                        <span>Connect</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

@if ($blockedMembers->hasPages())
    {{ $blockedMembers->links('member.search.pagination') }}
@endif
@endsection
