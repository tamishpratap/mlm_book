@extends('member.layouts.app')

@section('title', 'Saved Posts')
@section('main-class', 'saved-posts-page member-main')
@section('main-id', 'saved-posts-page')

@section('content')
<section class="card saved-posts-header" style="margin-bottom: 20px; padding: 24px 28px; border-radius: var(--radius-xl, 20px); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 246, 250, 0.98) 100%); border: 1px solid var(--color-border-soft, #edf1f7); box-shadow: var(--shadow-sm, 0 4px 16px rgba(34, 49, 78, 0.035));">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(32, 200, 117, 0.1); color: var(--color-primary, #20c875); display: grid; place-items: center; font-size: 22px; flex-shrink: 0;">
                <i data-lucide="bookmark" aria-hidden="true"></i>
            </div>
            <div>
                <h1 style="font-size: 1.35rem; font-weight: 800; color: var(--color-text-main, #101828); margin: 0 0 4px 0; letter-spacing: -0.02em;">Saved Posts</h1>
                <p style="font-size: 0.875rem; color: var(--color-text-secondary, #687386); margin: 0;">Your bookmarked posts, videos, and articles saved for quick access.</p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 99px; background: var(--color-primary-soft, #edf3ff); color: var(--color-primary, #4f7df3); font-size: 0.8rem; font-weight: 700;">
                <i data-lucide="bookmark-check" style="width: 14px; height: 14px;"></i> {{ $posts->total() }} Saved
            </span>
        </div>
    </div>
</section>

<div class="post-feed" data-post-feed>
    @forelse ($posts as $post)
        @include('member.posts.partials.card', compact('post'))
    @empty
        <section class="card post-empty-state" data-post-empty style="padding: 48px 24px; text-align: center; border-radius: var(--radius-xl, 20px); border: 1px solid var(--color-border-soft, #edf1f7); background: #ffffff;">
            <div style="width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; background: var(--color-surface-alt, #f8faff); color: var(--color-primary, #4f7df3); display: grid; place-items: center; font-size: 28px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);">
                <i data-lucide="bookmark" aria-hidden="true"></i>
            </div>
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--color-text-main, #101828); margin: 0 0 8px 0;">No Saved Posts Yet</h2>
            <p style="font-size: 0.9rem; color: var(--color-text-secondary, #687386); max-width: 420px; margin: 0 auto 20px; line-height: 1.5;">
                Bookmark posts, videos, or announcements from your feed to easily view them here anytime.
            </p>
            <a class="member-button member-button--primary" href="{{ route('member.dashboard') }}" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 20px;">
                <i data-lucide="house" aria-hidden="true"></i>
                <span>Return to Home Feed</span>
            </a>
        </section>
    @endforelse
</div>

@if ($posts->hasPages())
    <div style="margin-top: 20px;">
        {{ $posts->links('member.search.pagination') }}
    </div>
@endif
@endsection
