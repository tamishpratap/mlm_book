@extends('member.layouts.app')

@section('title', $errorTitle ?? 'Invalid Invite')

@section('content')
<div class="community-page" style="max-width: 600px; margin: 40px auto;">
    <section class="card fb-section-card">
        <div class="fb-empty-state" style="padding: 40px 20px;">
            <div class="fb-empty-state__icon" style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                <i data-lucide="link-2-off" aria-hidden="true"></i>
            </div>

            <h2 style="font-size: 20px; font-weight: 800; color: var(--color-text-main); margin: 12px 0 8px 0;">
                {{ $errorTitle ?? 'Invalid Community Invitation' }}
            </h2>

            <p style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.6; max-width: 460px; margin: 0 auto 24px;">
                {{ $errorMessage ?? 'This invitation link is invalid, expired, or has been revoked by community administrators.' }}
            </p>

            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('member.community.index') }}" class="member-button member-button--primary">
                    <i data-lucide="compass" aria-hidden="true"></i> Explore Communities
                </a>
                <a href="{{ route('member.dashboard') }}" class="member-button member-button--secondary">
                    <i data-lucide="house" aria-hidden="true"></i> Go to Dashboard
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
