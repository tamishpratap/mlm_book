@extends('member.layouts.app')

@section('title', 'You\'re Invited to ' . $community->name)

@section('content')
@php
    $hasLogo = $community->logo && file_exists(public_path($community->logo));
    $hasCover = $community->cover_photo && file_exists(public_path($community->cover_photo));
    $initials = collect(preg_split('/\s+/', trim($community->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'C';
    $code = $invitation->invite_code ?? $community->invite_code;
@endphp

<div class="community-page" style="max-width: 680px; margin: 20px auto;">
    <div class="card fb-section-card" style="padding: 0; overflow: hidden;">
        <div class="community-card__cover" style="height: 180px;">
            @if ($hasCover)
                <img src="{{ asset($community->cover_photo) }}" alt="{{ $community->name }} cover">
            @endif
        </div>

        <div style="padding: 0 28px 28px; text-align: center;">
            <div class="community-card__avatar" style="width: 90px; height: 90px; margin: -45px auto 16px; border-radius: 22px; border: 4px solid #ffffff; box-shadow: var(--shadow-md);">
                @if ($hasLogo)
                    <img src="{{ asset($community->logo) }}" alt="{{ $community->name }} logo">
                @else
                    <div class="community-avatar-initials" style="font-size: 28px;">{{ $initials }}</div>
                @endif
            </div>

            <span class="community-badge community-badge--category" style="margin-bottom: 8px;">{{ $community->category }}</span>

            <h1 style="font-size: 24px; font-weight: 800; color: var(--color-text-main); margin: 6px 0 8px 0;">
                You're Invited to Join {{ $community->name }}
            </h1>

            <p style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.6; max-width: 520px; margin: 0 auto 20px;">
                {{ $community->description ?: 'Connect, collaborate, and share with members of this thriving community on MLM Book.' }}
            </p>

            <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 24px; font-size: 13px; color: var(--color-text-secondary);">
                <span>
                    <i data-lucide="users" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
                    <strong>{{ number_format($community->member_count ?? 1) }}</strong> {{ \Illuminate\Support\Str::plural('member', $community->member_count ?? 1) }}
                </span>
                <span>
                    <i data-lucide="shield" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
                    <strong>{{ ucfirst($community->visibility) }}</strong> Community
                </span>
                <span>
                    <i data-lucide="user-check" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
                    By <strong>{{ $community->owner->name ?? 'Member' }}</strong>
                </span>
            </div>

            @if ($isLoggedIn)
                <form method="POST" action="{{ route('member.community.invite.join', $code) }}">
                    @csrf
                    <button type="submit" class="member-button member-button--primary" style="width: 100%; max-width: 320px; margin: 0 auto; justify-content: center; padding: 12px 24px; font-size: 14px; font-weight: 700;">
                        <i data-lucide="user-plus" aria-hidden="true"></i>
                        <span>{{ $community->isPublic() ? 'Accept Invitation & Join' : 'Submit Join Request' }}</span>
                    </button>
                </form>
            @else
                <div style="display: flex; flex-direction: column; gap: 10px; align-items: center;">
                    <a href="{{ route('member.login') }}" class="member-button member-button--primary" style="width: 100%; max-width: 320px; justify-content: center; padding: 12px 24px; font-size: 14px; font-weight: 700;">
                        <i data-lucide="log-in" aria-hidden="true"></i> Log In to Accept Invitation
                    </a>
                    <span style="font-size: 12px; color: var(--color-text-secondary);">
                        Don't have an account? <a href="{{ route('member.register') }}" style="color: var(--color-primary); font-weight: 600;">Register here</a>
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
