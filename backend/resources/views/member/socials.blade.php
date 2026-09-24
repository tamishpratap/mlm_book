@extends('member.layouts.app')

@section('title', 'Socials')
@section('main-class', 'feed')
@section('main-id', 'feed')

@section('content')
<section class="card stories" aria-label="Stories" data-story-row>
    <button class="story story--create" type="button" aria-haspopup="dialog" aria-controls="create-story-modal" data-story-create-open>
        <img src="image/story_1.png" alt="Your story">
        <div class="story--create__shade"></div>
        <span class="story-add" aria-hidden="true"><i data-lucide="plus"></i></span>
        <strong>Create story</strong>
    </button>
    @foreach ($stories as $story)
        @include('member.stories.partials.card', [
            'story' => $story,
            'currentMemberId' => $currentMember->id,
        ])
    @endforeach
    <button class="stories__next" type="button" aria-label="Next stories" data-stories-next @if ($stories->count() <= 4) hidden @endif><i data-lucide="chevron-right"></i></button>
</section>

@include('member.stories.partials.create-modal')
<div data-story-viewer-mount></div>

<form
    class="card composer"
    method="POST"
    action="{{ route('member.posts.store') }}"
    enctype="multipart/form-data"
    data-post-composer
>
    @csrf
    <div class="composer__input-row">
        <img class="avatar" src="{{ $composerAvatar }}" alt="{{ $currentMember->name }}">
        <textarea
            class="composer__prompt"
            name="body"
            maxlength="5000"
            rows="1"
            placeholder="What's on your mind, {{ $currentMember->name }}?"
            aria-label="Post text"
            data-post-body
        >{{ old('body') }}</textarea>
    </div>

    <div class="composer__create-row">
        <button
            type="button"
            class="member-button member-button--primary composer__create-btn"
            data-post-create-btn
            aria-label="Create Post"
        >
            <i data-lucide="plus" aria-hidden="true"></i>
            <span>Create Post</span>
        </button>
    </div>

    <div class="composer__preview" data-post-preview hidden>
        <div class="composer__preview-media" data-post-preview-media></div>
        <button class="composer__preview-remove" type="button" aria-label="Remove selected media" data-post-media-remove>
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="composer__actions">
        <label class="composer__action" for="post-media">
            <i class="action-icon action-icon--photo" data-lucide="image"></i><span>Photo / video</span>
            <input
                id="post-media"
                name="media"
                type="file"
                accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
                data-post-media
            >
        </label>
        <button class="composer__submit" type="submit" data-post-submit>
            <i data-lucide="send" aria-hidden="true"></i><span data-post-submit-label>Post</span>
        </button>
    </div>
    <p class="composer__feedback" role="status" aria-live="polite" data-post-feedback></p>
</form>

<div class="feed-toolbar">
    <div class="feed-toolbar__title">
        <span>Smart Feed</span>
    </div>
    <button class="feed-refresh-btn" type="button" aria-label="Refresh Feed" data-feed-refresh-btn>
        <i data-lucide="rotate-cw" aria-hidden="true"></i>
        <span>Refresh Feed</span>
    </button>
</div>

<button class="new-posts-pill" type="button" data-new-posts-pill hidden>
    <i data-lucide="arrow-up" aria-hidden="true"></i>
    <span>New Posts Available</span>
</button>

<div class="post-feed" data-post-feed data-latest-post-id="{{ $posts->first()?->id ?? 0 }}">
    @forelse ($posts as $post)
        @include('member.posts.partials.card', compact('post'))
    @empty
        <section class="card post-empty-state" data-post-empty>
            <div class="post-empty-state__icon">
                <i data-lucide="newspaper" aria-hidden="true"></i>
            </div>
            <h2>No Posts Available</h2>
            <p>Your feed is quiet right now. Share something or connect with friends!</p>

            @if (isset($suggestedMembers) && $suggestedMembers->isNotEmpty())
                <div class="suggested-members-box">
                    <h3>Suggested People to Connect With</h3>
                    <div class="suggested-members-list">
                        @foreach ($suggestedMembers as $sMember)
                            @php
                                $sHasPhoto = $sMember->profile_photo
                                    && str_starts_with($sMember->profile_photo, 'uploads/profile/')
                                    && ! str_contains($sMember->profile_photo, '..')
                                    && $sMember->profile_photo === 'uploads/profile/'.basename($sMember->profile_photo)
                                    && file_exists(public_path($sMember->profile_photo));
                                $sInitials = collect(preg_split('/\s+/', trim($sMember->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                                    ->implode('') ?: 'M';
                            @endphp
                            <div class="suggested-member-card">
                                @if ($sHasPhoto)
                                    <img src="{{ asset($sMember->profile_photo) }}" alt="{{ $sMember->name }}" loading="lazy">
                                @else
                                    <span class="suggested-member-card__initials">{{ $sInitials }}</span>
                                @endif
                                <strong>{{ $sMember->name }}</strong>
                                <a class="soft-cta" href="{{ route('member.people.show', $sMember) }}">View Profile</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endforelse
</div>

@if ($posts->hasPages())
    {{ $posts->links('member.search.pagination') }}
@endif

@endsection

@section('right-sidebar')
@php
    $ownedPage = \App\Models\BusinessPage::query()
        ->where('member_id', $currentMember->id)
        ->first();

    $friendIds = $currentMember->acceptedFriendIds();
    $activeContacts = \App\Models\Member::query()
        ->whereIn('id', $friendIds)
        ->take(8)
        ->get();
@endphp

<section class="card widget pages-widget">
    <div class="section-heading d-flex align-items-center justify-content-between gap-2">
        <h2 class="m-0">Your Pages & Profiles</h2>
        <a href="{{ route('member.business-pages.index') }}" style="color: var(--color-primary); font-size: 11px; font-weight: 600; text-decoration: none; white-space: nowrap; flex-shrink: 0;">See all</a>
    </div>
    @if ($ownedPage)
        <div class="page-profile">
            @if ($ownedPage->logo_url)
                <img src="{{ $ownedPage->logo_url }}" alt="{{ $ownedPage->page_name }}">
            @else
                <span class="avatar post-avatar-initials" style="width: 38px; height: 38px;">BP</span>
            @endif
            <div>
                <strong><a href="{{ route('member.business-pages.show', $ownedPage) }}" style="color: inherit; text-decoration: none;">{{ $ownedPage->page_name }}</a></strong>
                <span>Business Page</span>
            </div>
        </div>
        <a class="widget-row" href="{{ route('member.notifications.index') }}"><i data-lucide="bell"></i><span>Notifications</span></a>
    @else
        <div style="padding: 6px 0; margin-bottom: 8px;">
            <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0 0 8px 0;">Create a Business Page to reach clients & promote your brand.</p>
            <a class="member-button member-button--primary member-button--sm w-100" href="{{ route('member.business-pages.create') }}" style="text-decoration: none; justify-content: center;">
                <i data-lucide="plus-circle"></i> Create Page
            </a>
        </div>
    @endif
</section>

@php
    $todayBirthdays = \App\Models\Member::query()
        ->whereIn('id', $friendIds)
        ->whereNotNull('date_of_birth')
        ->get()
        ->filter(fn ($m) => $m->date_of_birth && \Illuminate\Support\Carbon::parse($m->date_of_birth)->isBirthday());
@endphp

<section class="card widget birthday-widget">
    <h2>Birthdays</h2>
    <div class="birthday-widget__body">
        @if ($todayBirthdays->isNotEmpty())
            <p><strong>{{ $todayBirthdays->first()->name }}</strong> @if($todayBirthdays->count() > 1) and <strong>{{ $todayBirthdays->count() - 1 }} others</strong> @endif have birthdays today!</p>
        @else
            <p>No friend birthdays today.</p>
        @endif
        <div class="gift-visual"><span>✦</span><i data-lucide="cake" style="width: 24px; height: 24px; color: var(--color-primary);"></i><i>✦</i></div>
    </div>
    <div class="birthday-widget__action">
        <a class="soft-cta" href="{{ route('member.friends.index') }}">View Friends</a>
    </div>
</section>

<section class="card widget contacts-widget">
    <div class="contacts-widget__header">
        <h2>Contacts</h2>
        <div>
            <a class="mini-button" href="{{ route('member.friends.index') }}" aria-label="Search friends"><i data-lucide="search"></i></a>
        </div>
    </div>
    <div class="contact-list">
        @forelse ($activeContacts as $contact)
            @php
                $cPhoto = $contact->profile_photo && file_exists(public_path($contact->profile_photo)) ? asset($contact->profile_photo) : null;
                $cInitials = collect(preg_split('/\s+/', trim($contact->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'C';
            @endphp
            <a class="contact" href="{{ route('member.people.show', $contact->id) }}">
                <span>
                    @if ($cPhoto)
                        <img src="{{ $cPhoto }}" alt="{{ $contact->name }}">
                    @else
                        <span class="avatar post-avatar-initials" style="width: 28px; height: 28px; font-size: 11px;">{{ $cInitials }}</span>
                    @endif
                    <i></i>
                </span>
                <strong>{{ $contact->name }}</strong>
            </a>
        @empty
            <div style="padding: 10px 0; text-align: center;">
                <small style="color: var(--color-text-muted); font-size: 0.8rem;">No contacts yet.</small>
                <a href="{{ route('member.people.suggestions') }}" style="display: block; margin-top: 4px; font-size: 0.8rem; color: var(--color-primary); font-weight: 600; text-decoration: none;">Find Connections</a>
            </div>
        @endforelse
    </div>
</section>

@endsection
