@extends('member.layouts.app')

@php
    $isShared = $post->isShared();
    $origPost = $isShared ? $post->originalPost : null;
    $bizPage = $post->businessPage ?? null;
    $sharer = $post->member;
    $sharerName = $bizPage ? $bizPage->page_name : ($sharer?->name ?? 'Member');

    if ($isShared && $origPost) {
        $origBiz = $origPost->businessPage ?? null;
        $origAuthor = $origPost->member ?? null;
        $origName = $origBiz ? $origBiz->page_name : ($origAuthor?->name ?? 'Member');
        $origUrl = $origBiz ? route('member.business-pages.show', $origBiz) : ($origAuthor ? route('member.people.show', $origAuthor) : null);
        $pageTitle = 'Post shared by ' . $sharerName;
    } elseif ($isShared) {
        $origName = null;
        $origUrl = null;
        $pageTitle = 'Shared Post by ' . $sharerName;
    } else {
        $pageTitle = 'Post by ' . $sharerName;
    }
@endphp

@section('title', $pageTitle)
@section('body-class', 'is-post-detail-page')
@section('main-class', 'feed post-detail-page')

@section('content')
    <header class="post-page-heading">
        <div class="post-page-heading__content">
            @if ($isShared)
                <span class="post-page-heading__badge post-page-heading__badge--shared">
                    <i data-lucide="repeat" aria-hidden="true"></i> Shared post
                </span>
                <h1>{{ $sharerName }} shared a post</h1>
                @if ($origPost)
                    <p class="post-page-heading__sub">
                        Originally posted by
                        @if ($origUrl)
                            <a href="{{ $origUrl }}" class="post-page-heading__author-link"><strong>{{ $origName }}</strong></a>
                        @else
                            <strong>{{ $origName }}</strong>
                        @endif
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $origPost->created_at->toIso8601String() }}">{{ $origPost->created_at->diffForHumans() }}</time>
                        <span aria-hidden="true">·</span>
                        <a href="{{ route('member.posts.show', $origPost) }}" class="post-page-heading__orig-link">
                            <i data-lucide="external-link" aria-hidden="true"></i>
                            <span>View original post</span>
                        </a>
                    </p>
                @else
                    <p class="post-page-heading__sub post-page-heading__sub--unavailable">
                        <i data-lucide="info" aria-hidden="true"></i>
                        <span>Original post is no longer available.</span>
                    </p>
                @endif
            @elseif ($bizPage)
                <span class="post-page-heading__badge">
                    <i data-lucide="building-2" aria-hidden="true"></i> Business Page post
                </span>
                <h1>Post by {{ $bizPage->page_name }}</h1>
            @else
                <span class="post-page-heading__badge">
                    <i data-lucide="user" aria-hidden="true"></i> Member post
                </span>
                <h1>Post by {{ $sharerName }}</h1>
            @endif
        </div>
        <a class="member-button member-button--secondary" href="{{ route('member.dashboard') }}">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span>Back to Dashboard</span>
        </a>
    </header>

    @include('member.posts.partials.card', compact('post'))
@endsection
