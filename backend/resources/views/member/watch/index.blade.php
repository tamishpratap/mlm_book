@extends('member.layouts.app')

@section('title', 'Watch - Videos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-watch.css') }}">
@endpush

@section('content')
<div class="watch-page-container">
    <section class="watch-header-card" aria-label="Watch Navigation">
        <div class="watch-header-card__top">
            <div class="watch-header-card__title">
                <span class="watch-header-card__icon"><i data-lucide="tv-minimal-play"></i></span>
                <div>
                    <h1>Watch</h1>
                    <p>Discover videos from connections, creators, and your community.</p>
                </div>
            </div>

        </div>

        <nav class="watch-tabs" aria-label="Watch Filters">
            <a class="watch-tab-item {{ $filter === 'all' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'all']) }}">
                <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                <span>All Videos</span>
            </a>
            <a class="watch-tab-item {{ $filter === 'trending' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'trending']) }}">
                <i data-lucide="flame" style="width: 16px; height: 16px;"></i>
                <span>Trending</span>
            </a>
            <a class="watch-tab-item {{ $filter === 'my_videos' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'my_videos']) }}">
                <i data-lucide="video" style="width: 16px; height: 16px;"></i>
                <span>My Videos</span>
                @if ($myVideosCount > 0)
                    <span class="badge-count">{{ $myVideosCount }}</span>
                @endif
            </a>
            <a class="watch-tab-item {{ $filter === 'saved' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'saved']) }}">
                <i data-lucide="bookmark" style="width: 16px; height: 16px;"></i>
                <span>Saved Videos</span>
                @if ($savedVideosCount > 0)
                    <span class="badge-count">{{ $savedVideosCount }}</span>
                @endif
            </a>
        </nav>
    </section>

    <div
        class="watch-feed"
        data-watch-feed
        @if ($posts->hasMorePages())
            data-next-page="{{ route('member.watch.index', array_merge(request()->query(), ['page' => $posts->currentPage() + 1])) }}"
        @endif
    >
        @forelse ($posts as $post)
            @include('member.watch.partials.card', ['post' => $post])
        @empty
            <div class="watch-empty-state">
                <span class="watch-empty-state__icon"><i data-lucide="video-off"></i></span>
                <h3>No video posts found</h3>
                <p>
                    @if ($filter === 'my_videos')
                        No video posts found for your business pages.
                    @elseif ($filter === 'saved')
                        You haven't saved any video posts yet.
                    @elseif ($filter === 'trending')
                        No trending video posts available at the moment.
                    @else
                        No video posts available in your feed right now. Check back soon for new content!
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    <div class="watch-feed__loader" data-watch-loader style="text-align: center; padding: 20px;" hidden>
        <i data-lucide="loader-circle" class="spin-icon" style="width: 24px; height: 24px; color: var(--color-primary);"></i>
    </div>
</div>
@endsection

@section('right-sidebar')
    <!-- Watch Navigation Shortcuts -->
    <div class="watch-sidebar-card">
        <div class="watch-sidebar-card__header">
            <h3><i data-lucide="compass" style="width: 18px; height: 18px; color: var(--color-primary);"></i> Watch Navigation</h3>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
            <a class="side-nav__item {{ $filter === 'all' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'all']) }}">
                <i data-lucide="tv-minimal-play"></i><span>Home Feed</span>
            </a>
            <a class="side-nav__item {{ $filter === 'trending' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'trending']) }}">
                <i data-lucide="flame"></i><span>Trending Videos</span>
            </a>
            <a class="side-nav__item {{ $filter === 'my_videos' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'my_videos']) }}">
                <i data-lucide="video"></i><span>My Videos ({{ $myVideosCount }})</span>
            </a>
            <a class="side-nav__item {{ $filter === 'saved' ? 'is-active' : '' }}" href="{{ route('member.watch.index', ['filter' => 'saved']) }}">
                <i data-lucide="bookmark"></i><span>Saved Videos ({{ $savedVideosCount }})</span>
            </a>
        </div>
    </div>

    <!-- Suggested Creators -->
    @if ($suggestedCreators->isNotEmpty())
        <div class="watch-sidebar-card">
            <div class="watch-sidebar-card__header">
                <h3><i data-lucide="sparkles" style="width: 18px; height: 18px; color: var(--color-primary);"></i> Suggested Creators</h3>
            </div>
            @foreach ($suggestedCreators as $creator)
                @php
                    $cPhoto = $creator->profile_photo_url;
                    $cInitials = $creator->initials;
                @endphp
                <div class="watch-sidebar-creator">
                    <a class="watch-sidebar-creator__info" href="{{ route('member.people.show', $creator->id) }}">
                        @if ($cPhoto)
                            <img class="avatar watch-sidebar-creator__avatar" src="{{ $cPhoto }}" alt="{{ $creator->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="avatar post-avatar-initials watch-sidebar-creator__avatar" style="display: none;">{{ $cInitials }}</span>
                        @else
                            <span class="avatar post-avatar-initials watch-sidebar-creator__avatar">{{ $cInitials }}</span>
                        @endif
                        <div class="watch-sidebar-creator__details">
                            <span class="watch-sidebar-creator__name" title="{{ $creator->name }}">{{ $creator->name }}</span>
                            @if ($creator->user_id)
                                <span class="watch-sidebar-creator__handle" title="{{ '@' . $creator->user_id }}">{{ '@' . $creator->user_id }}</span>
                            @endif
                        </div>
                    </a>
                    <div class="watch-sidebar-creator__action">
                        <form method="POST" action="{{ route('member.friends.request', $creator->id) }}">
                            @csrf
                            <button class="member-button member-button--secondary member-button--sm" type="submit" title="Connect">
                                <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i> Connect
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Trending Videos Widget -->
    @if ($trendingVideos->isNotEmpty())
        <div class="watch-sidebar-card">
            <div class="watch-sidebar-card__header">
                <h3><i data-lucide="flame" style="width: 18px; height: 18px; color: #f59e0b;"></i> Trending Videos</h3>
            </div>
            @foreach ($trendingVideos as $tVideo)
                @php
                    $tCreator = $tVideo->member->name ?? 'Member';
                    $tTitle = $tVideo->body ? trim($tVideo->body) : 'Video by ' . $tCreator;
                    $tReactions = ($tVideo->reactions_count ?? 0) + ($tVideo->likes_count ?? 0);
                @endphp
                <a class="watch-sidebar-video-item" href="{{ route('member.posts.show', $tVideo) }}">
                    <div class="watch-sidebar-video-thumb">
                        @if ($tVideo->media_url)
                            <video src="{{ $tVideo->media_url }}#t=0.5" preload="metadata" muted playsinline class="watch-sidebar-video-thumb__media" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"></video>
                            <div class="watch-sidebar-video-thumb__fallback" style="display: none;">
                                <i data-lucide="play-circle"></i>
                            </div>
                            <div class="watch-sidebar-video-thumb__play-overlay">
                                <i data-lucide="play-circle"></i>
                            </div>
                        @else
                            <div class="watch-sidebar-video-thumb__fallback">
                                <i data-lucide="play-circle"></i>
                            </div>
                        @endif
                    </div>
                    <div class="watch-sidebar-video-copy">
                        <strong class="watch-sidebar-video-copy__title" title="{{ $tTitle }}">{{ $tTitle }}</strong>
                        <span class="watch-sidebar-video-copy__meta" title="{{ $tCreator }} · {{ $tReactions }} reactions">{{ $tCreator }} · {{ $tReactions }} reactions</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <!-- Recent Videos Widget -->
    @if ($recentVideos->isNotEmpty())
        <div class="watch-sidebar-card">
            <div class="watch-sidebar-card__header">
                <h3><i data-lucide="clock" style="width: 18px; height: 18px; color: var(--color-primary);"></i> Recent Videos</h3>
            </div>
            @foreach ($recentVideos as $rVideo)
                @php
                    $rCreator = $rVideo->member->name ?? 'Member';
                    $rTitle = $rVideo->body ? trim($rVideo->body) : 'Video by ' . $rCreator;
                    $rTimeAgo = $rVideo->created_at ? $rVideo->created_at->diffForHumans() : '';
                @endphp
                <a class="watch-sidebar-video-item" href="{{ route('member.posts.show', $rVideo) }}">
                    <div class="watch-sidebar-video-thumb">
                        @if ($rVideo->media_url)
                            <video src="{{ $rVideo->media_url }}#t=0.5" preload="metadata" muted playsinline class="watch-sidebar-video-thumb__media" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"></video>
                            <div class="watch-sidebar-video-thumb__fallback" style="display: none;">
                                <i data-lucide="play-circle"></i>
                            </div>
                            <div class="watch-sidebar-video-thumb__play-overlay">
                                <i data-lucide="play-circle"></i>
                            </div>
                        @else
                            <div class="watch-sidebar-video-thumb__fallback">
                                <i data-lucide="play-circle"></i>
                            </div>
                        @endif
                    </div>
                    <div class="watch-sidebar-video-copy">
                        <strong class="watch-sidebar-video-copy__title" title="{{ $rTitle }}">{{ $rTitle }}</strong>
                        <span class="watch-sidebar-video-copy__meta" title="{{ $rCreator }} · {{ $rTimeAgo }}">{{ $rCreator }} · {{ $rTimeAgo }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
