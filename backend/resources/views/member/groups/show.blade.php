@extends('member.layouts.app')

@section('title', $group->name.' - Community')

@section('content')
@php
    $cover = $group->cover_photo ? asset($group->cover_photo) : asset('member_assets/images/default-cover.jpg');
    $logo = $group->logo ? asset($group->logo) : asset('member_assets/images/default-group.png');
@endphp

<div class="group-profile-page">
    <div class="group-header-card card">
        <div class="group-cover">
            <img src="{{ $cover }}" alt="{{ $group->name }}">
        </div>

        <div class="group-header-body">
            <div class="group-logo-wrapper">
                <img src="{{ $logo }}" alt="{{ $group->name }}">
            </div>

            <div class="group-header-info">
                <h1>{{ $group->name }}</h1>
                <div class="group-header-meta">
                    <span class="privacy-tag"><i data-lucide="{{ $group->privacy === 'public' ? 'globe' : 'lock' }}" aria-hidden="true"></i> {{ ucfirst($group->privacy) }} Community</span>
                    <span><i data-lucide="users" aria-hidden="true"></i> {{ $group->members_count }} Members</span>
                    <span><i data-lucide="tag" aria-hidden="true"></i> {{ $group->category }}</span>
                </div>
            </div>

            <div class="group-header-actions">
                @if ($isMember)
                    <form method="POST" action="{{ route('member.groups.leave', $group) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="member-button member-button--secondary" onclick="return confirm('Leave community?')">
                            <i data-lucide="log-out" aria-hidden="true"></i> Leave Community
                        </button>
                    </form>
                @elseif ($isPending)
                    <button class="member-button member-button--secondary" disabled>
                        <i data-lucide="clock" aria-hidden="true"></i> Join Requested
                    </button>
                @else
                    <form method="POST" action="{{ route('member.groups.join', $group) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="member-button member-button--primary">
                            <i data-lucide="user-plus" aria-hidden="true"></i> Join Community
                        </button>
                    </form>
                @endif

                @if ($group->isOwner(auth('member')->id()))
                    <a href="{{ route('member.groups.edit', $group) }}" class="member-button member-button--secondary">
                        <i data-lucide="settings" aria-hidden="true"></i> Settings
                    </a>
                @endif
            </div>
        </div>

        <nav class="group-nav-tabs">
            <a href="{{ route('member.groups.show', ['group' => $group, 'tab' => 'feed']) }}" class="group-nav-tab {{ $tab === 'feed' ? 'is-active' : '' }}">Feed</a>
            <a href="{{ route('member.groups.show', ['group' => $group, 'tab' => 'about']) }}" class="group-nav-tab {{ $tab === 'about' ? 'is-active' : '' }}">About</a>
            <a href="{{ route('member.groups.show', ['group' => $group, 'tab' => 'members']) }}" class="group-nav-tab {{ $tab === 'members' ? 'is-active' : '' }}">Members</a>
            <a href="{{ route('member.groups.show', ['group' => $group, 'tab' => 'media']) }}" class="group-nav-tab {{ $tab === 'media' ? 'is-active' : '' }}">Media</a>
            @if ($group->isAdmin(auth('member')->id()))
                <a href="{{ route('member.groups.show', ['group' => $group, 'tab' => 'requests']) }}" class="group-nav-tab {{ $tab === 'requests' ? 'is-active' : '' }}">Join Requests</a>
            @endif
        </nav>
    </div>

    <div class="group-tab-content">
        @if ($tab === 'feed')
            @if ($group->privacy === 'private' && ! $isMember)
                <div class="notification-empty card">
                    <div class="notification-empty__icon"><i data-lucide="lock" aria-hidden="true"></i></div>
                    <h2>This Community is Private</h2>
                    <p>Join this community to view posts and participate in discussion.</p>
                </div>
            @else
                @if ($isMember)
                    <div class="card post-create-card" style="margin-bottom: 20px; padding: 20px;">
                        <form method="POST" action="{{ route('member.groups.posts.store', $group) }}" enctype="multipart/form-data">
                            @csrf
                            <textarea name="body" rows="3" class="form-control" placeholder="Write something in {{ $group->name }}..."></textarea>
                            <div class="post-create-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <input type="file" name="media" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov">
                                <button type="submit" class="member-button member-button--primary">Post</button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($announcements->isNotEmpty())
                    <div class="announcements-banner card" style="margin-bottom: 20px; padding: 16px; background: #eff6ff; border-left: 4px solid #176bff;">
                        <h3 style="margin: 0 0 10px; color: #176bff;"><i data-lucide="megaphone" aria-hidden="true"></i> Community Announcements</h3>
                        @foreach ($announcements as $anc)
                            <div class="announcement-item" style="padding: 10px 0; border-bottom: 1px solid #dbeafe;">
                                <strong>{{ $anc->member->name }}:</strong> {{ $anc->body }}
                            </div>
                        @endforeach
                    </div>
                @endif

                @forelse ($posts as $post)
                    @include('member.posts.partials.card', compact('post'))
                @empty
                    <div class="notification-empty card">
                        <div class="notification-empty__icon"><i data-lucide="message-square" aria-hidden="true"></i></div>
                        <h2>No Posts Yet</h2>
                        <p>Be the first member to publish a post in this community!</p>
                    </div>
                @endforelse

                @if ($posts->hasPages())
                    {{ $posts->links('member.search.pagination') }}
                @endif
            @endif

        @elseif ($tab === 'about')
            @include('member.groups.partials.about', compact('group'))
        @elseif ($tab === 'members')
            @include('member.groups.partials.members', compact('group', 'membersList'))
        @elseif ($tab === 'requests')
            @include('member.groups.partials.requests', compact('group', 'pendingRequests'))
        @elseif ($tab === 'media')
            @include('member.groups.partials.media', compact('group', 'mediaPosts'))
        @endif
    </div>
</div>
@endsection
