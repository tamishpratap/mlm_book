@extends('member.layouts.app')

@section('title', $community->name)

@section('content')
@php
    $hasLogo = $community->logo && file_exists(public_path($community->logo));
    $hasCover = $community->cover_photo && file_exists(public_path($community->cover_photo));
    $initials = collect(preg_split('/\s+/', trim($community->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'C';
    $currentMemberId = auth('member')->id();
@endphp

<div class="community-page">
    <!-- Community Hero Header -->
    <div class="community-hero">
        <div class="community-hero__cover" data-action-trigger="cover" style="cursor: pointer; position: relative;">
            @if ($hasCover)
                <img class="profile-cover__image" id="community-cover-preview" src="{{ asset($community->cover_photo) }}" alt="{{ $community->name }} cover">
            @else
                <div class="profile-cover__preview" id="community-cover-preview"></div>
            @endif
            <div class="community-cover__overlay" aria-hidden="true"></div>

            @if ($isOwner || $isAdmin)
                <button type="button" class="media-upload-button" data-action-trigger="cover" title="Change Cover Photo">
                    <i data-lucide="camera" aria-hidden="true"></i>
                    <span>Change Cover</span>
                </button>
                <form class="media-upload-form" method="POST" action="{{ route('member.community.cover.update', $community) }}" enctype="multipart/form-data" data-community-cover-form data-upload-type="cover" data-delete-url="{{ route('member.community.cover.remove', $community) }}" style="display: none;">
                    @csrf
                    <input
                        id="community_cover_photo"
                        name="cover_photo"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp"
                        data-image-input
                        data-upload-type="cover"
                        data-preview-target="community-cover-preview"
                    >
                </form>
            @endif

            <!-- Cover Photo Action Menu -->
            <div class="photo-action-menu photo-action-menu--cover" data-photo-menu="cover" hidden>
                <button type="button" class="photo-action-item" data-photo-action="view" data-photo-type="cover" data-photo-src="{{ $hasCover ? asset($community->cover_photo) : '' }}">
                    <i data-lucide="eye" aria-hidden="true"></i>
                    <span>View Cover Photo</span>
                </button>
                @if ($isOwner || $isAdmin)
                    <button type="button" class="photo-action-item" data-photo-action="edit" data-photo-type="cover">
                        <i data-lucide="pencil" aria-hidden="true"></i>
                        <span>Edit Cover Photo</span>
                    </button>
                    <button type="button" class="photo-action-item photo-action-item--danger" data-photo-action="delete" data-photo-type="cover" id="communityCoverDeleteMenuItem" @if(! $hasCover) style="display: none;" @endif>
                        <i data-lucide="trash-2" aria-hidden="true"></i>
                        <span>Delete Cover Photo</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="community-hero__body">
            <div class="community-hero__avatar">
                <div class="profile-avatar-clickable" data-action-trigger="logo" title="Community logo options" style="cursor: pointer; width: 100%; height: 100%; position: relative;">
                    @if ($hasLogo)
                        <img class="profile-avatar" id="community-logo-preview" src="{{ asset($community->logo) }}" alt="{{ $community->name }} logo">
                    @else
                        <div class="community-avatar-initials" id="community-logo-preview" role="img" aria-label="{{ $community->name }}">
                            <span>{{ $initials }}</span>
                        </div>
                    @endif
                    @if ($isOwner || $isAdmin)
                        <div class="profile-avatar__camera" aria-label="Change community logo">
                            <i data-lucide="camera" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>

                <!-- Logo Action Menu -->
                <div class="photo-action-menu photo-action-menu--avatar" data-photo-menu="logo" hidden>
                    <button type="button" class="photo-action-item" data-photo-action="view" data-photo-type="logo" data-photo-src="{{ $hasLogo ? asset($community->logo) : '' }}">
                        <i data-lucide="eye" aria-hidden="true"></i>
                        <span>View Logo</span>
                    </button>
                    @if ($isOwner || $isAdmin)
                        <button type="button" class="photo-action-item" data-photo-action="edit" data-photo-type="logo">
                            <i data-lucide="pencil" aria-hidden="true"></i>
                            <span>Edit Logo</span>
                        </button>
                        <button type="button" class="photo-action-item photo-action-item--danger" data-photo-action="delete" data-photo-type="logo" id="communityLogoDeleteMenuItem" @if(! $hasLogo) style="display: none;" @endif>
                            <i data-lucide="trash-2" aria-hidden="true"></i>
                            <span>Delete Logo</span>
                        </button>
                    @endif
                </div>

                @if ($isOwner || $isAdmin)
                    <form class="community-logo-form" method="POST" action="{{ route('member.community.logo.update', $community) }}" enctype="multipart/form-data" data-community-logo-form data-upload-type="logo" data-delete-url="{{ route('member.community.logo.remove', $community) }}" style="display: none;">
                        @csrf
                        <input
                            id="community_logo"
                            name="logo"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            data-image-input
                            data-upload-type="logo"
                            data-preview-target="community-logo-preview"
                        >
                    </form>
                @endif
            </div>

            <div class="community-hero__content">
                <div class="community-hero__title-row">
                    <h1>{{ $community->name }}</h1>
                    <div class="community-hero__actions">
                        @if ($isAdmin || $community->isModerator($currentMemberId))
                            <a href="{{ route('member.community.admin', $community) }}" class="member-button member-button--primary">
                                <i data-lucide="shield-check" aria-hidden="true"></i> Admin Panel
                            </a>
                            <a href="{{ route('member.community.analytics', $community) }}" class="member-button member-button--secondary" title="Analytics Platform">
                                <i data-lucide="bar-chart-3" aria-hidden="true"></i> Analytics
                            </a>
                        @endif

                        @if ($isOwner)
                            <a href="{{ route('member.community.edit', $community) }}" class="member-button member-button--secondary">
                                <i data-lucide="edit-3" aria-hidden="true"></i> Edit Settings
                            </a>
                        @endif

                        @include('member.community.partials.join-button', ['community' => $community])

                        <button type="button" class="member-button member-button--secondary" data-bs-toggle="modal" data-bs-target="#inviteShareModal" data-share-modal-open="{{ $community->id }}">
                            <i data-lucide="share-2" aria-hidden="true"></i> Invite & Share
                        </button>

                        @if ($isMember)
                            <button type="button" class="member-button member-button--secondary" data-bs-toggle="modal" data-bs-target="#notificationPreferencesModal" data-preferences-modal-open="{{ $community->id }}" title="Notification Settings">
                                <i data-lucide="bell" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="community-hero__badges">
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

                <div class="community-hero__meta">
                    <span>
                        <i data-lucide="users" aria-hidden="true"></i>
                        <span data-community-member-counter>{{ number_format($community->member_count ?? 0) }}</span> {{ \Illuminate\Support\Str::plural('member', $community->member_count ?? 0) }}
                    </span>
                    <span>
                        <i data-lucide="user-check" aria-hidden="true"></i>
                        Created by <strong>{{ $community->owner->name ?? 'Member' }}</strong>
                    </span>
                    <span>
                        <i data-lucide="calendar" aria-hidden="true"></i>
                        {{ $community->created_at->format('M d, Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Community Sub-Tabs (Phase 6 System) -->
    <nav class="community-nav-tabs" aria-label="Community detail tabs">
        @if ($community->isPublic() || $isMember)
            <a class="community-nav-tab {{ $activeTab === 'feed' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'feed']) }}">
                <i data-lucide="message-square" aria-hidden="true"></i> Discussion Feed
            </a>
        @endif

        <a class="community-nav-tab {{ $activeTab === 'about' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'about']) }}">
            <i data-lucide="info" aria-hidden="true"></i> About
        </a>

        <a class="community-nav-tab {{ $activeTab === 'members' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'members']) }}">
            <i data-lucide="users" aria-hidden="true"></i> Members (<span data-community-member-tab-count>{{ $community->member_count }}</span>)
        </a>

        @if ($community->isPublic() || $isMember)
            <a class="community-nav-tab {{ $activeTab === 'photos' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'photos']) }}">
                <i data-lucide="image" aria-hidden="true"></i> Photos
            </a>

            <a class="community-nav-tab {{ $activeTab === 'videos' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'videos']) }}">
                <i data-lucide="video" aria-hidden="true"></i> Videos
            </a>

            <a class="community-nav-tab {{ $activeTab === 'media' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'media']) }}">
                <i data-lucide="grid" aria-hidden="true"></i> Media
            </a>

            <a class="community-nav-tab {{ $activeTab === 'announcements' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'announcements']) }}">
                <i data-lucide="megaphone" aria-hidden="true"></i> Announcements
            </a>
        @endif

        <a class="community-nav-tab {{ $activeTab === 'rules' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'rules']) }}">
            <i data-lucide="shield" aria-hidden="true"></i> Rules
        </a>

        <a class="community-nav-tab" href="{{ route('member.community.activity', $community) }}">
            <i data-lucide="activity" aria-hidden="true"></i> Activity
        </a>

        <a class="community-nav-tab {{ $activeTab === 'files' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'files']) }}">
            <i data-lucide="folder" aria-hidden="true"></i> Files
        </a>

        @if ($isAdmin)
            <a class="community-nav-tab {{ $activeTab === 'requests' ? 'is-active' : '' }}" href="{{ route('member.community.show', [$community, 'tab' => 'requests']) }}">
                <i data-lucide="user-check" aria-hidden="true"></i> Join Requests (<span data-community-pending-counter>{{ $pendingCount }}</span>)
            </a>
        @endif
    </nav>

    <!-- Tab 1: Discussion Feed -->
    @if ($activeTab === 'feed')
        @if ($community->isPrivate() && ! $isMember)
            <section class="card fb-section-card">
                <div class="fb-empty-state">
                    <div class="fb-empty-state__icon">
                        <i data-lucide="lock" aria-hidden="true"></i>
                    </div>
                    <h3>Private Community Feed</h3>
                    <p>Join {{ $community->name }} to see posts, participate in discussions, and connect with members.</p>
                    <div style="margin-top: 16px;">
                        @include('member.community.partials.join-button', ['community' => $community])
                    </div>
                </div>
            </section>
        @else
            @if ($isMember)
                @include('member.community.partials.composer', ['community' => $community, 'isAdmin' => $isAdmin])
            @endif

            @if ($announcements->isNotEmpty())
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 0 4px;">
                        <i data-lucide="megaphone" style="width: 18px; height: 18px; color: #7d42f0;"></i>
                        <h2 style="font-size: 15px; font-weight: 700; color: var(--color-text-main); margin: 0;">Community Announcements</h2>
                    </div>
                    <div class="post-feed">
                        @foreach ($announcements as $post)
                            @include('member.posts.partials.card', compact('post'))
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($pinnedPost)
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 0 4px;">
                        <i data-lucide="pin" style="width: 18px; height: 18px; color: var(--color-primary);"></i>
                        <h2 style="font-size: 15px; font-weight: 700; color: var(--color-text-main); margin: 0;">Pinned Post</h2>
                    </div>
                    <div class="post-feed">
                        @include('member.posts.partials.card', ['post' => $pinnedPost])
                    </div>
                </div>
            @endif

            <div class="post-feed" data-community-feed>
                @forelse ($feedPosts as $post)
                    @include('member.posts.partials.card', compact('post'))
                @empty
                    @if ($announcements->isEmpty() && ! $pinnedPost)
                        <section class="card post-empty-state">
                            <div class="post-empty-state__icon">
                                <i data-lucide="message-square" aria-hidden="true"></i>
                            </div>
                            <h2>No Posts Yet</h2>
                            <p>Be the first to share something with {{ $community->name }}!</p>
                        </section>
                    @endif
                @endforelse
            </div>

            @if (method_exists($feedPosts, 'hasPages') && $feedPosts->hasPages())
                <div class="pagination-wrapper">
                    {{ $feedPosts->links('member.search.pagination') }}
                </div>
            @endif
        @endif

    <!-- Tab 2: About Card -->
    @elseif ($activeTab === 'about')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="info" aria-hidden="true"></i> About this Community</h2>
                    <p>Learn more about the mission, rules, and governance of {{ $community->name }}.</p>
                </div>
            </header>

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 6px 0;">Description</h3>
                    <p style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.6; margin: 0;">
                        {{ $community->description ?: 'No detailed description provided yet.' }}
                    </p>
                </div>

                @if ($community->rules)
                    <div style="border-top: 1px solid var(--color-border-soft); padding-top: 16px;">
                        <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 6px 0;"><i data-lucide="shield" style="width: 16px; height: 16px; color: var(--color-primary);"></i> Community Guidelines & Rules</h3>
                        <p style="font-size: 13.5px; color: var(--color-text-secondary); line-height: 1.6; margin: 0; white-space: pre-line;">
                            {{ $community->rules }}
                        </p>
                    </div>
                @endif

                <div style="border-top: 1px solid var(--color-border-soft); padding-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div>
                        <span style="font-size: 12px; color: var(--color-text-secondary);">Category</span>
                        <p style="font-size: 14px; font-weight: 700; margin: 2px 0 0 0;">{{ $community->category }}</p>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--color-text-secondary);">Privacy / Visibility</span>
                        <p style="font-size: 14px; font-weight: 700; margin: 2px 0 0 0;">{{ ucfirst($community->visibility) }}</p>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: var(--color-text-secondary);">Community ID</span>
                        <p style="font-size: 14px; font-weight: 600; font-family: monospace; margin: 2px 0 0 0; color: var(--color-primary);">{{ $community->community_id }}</p>
                    </div>
                </div>
            </div>
        </section>

    <!-- Tab 3: Members Directory -->
    @elseif ($activeTab === 'members')
        <section class="card fb-section-card community-members-section" style="background: #ffffff; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.9); box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04); padding: 28px; box-sizing: border-box; display: flex; flex-direction: column; gap: 24px; width: 100%;">
            <header class="community-members-section__header" style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; width: 100%;">
                <div class="community-members-section__title-wrap" style="display: flex; align-items: center; gap: 16px;">
                    <div class="community-members-section__icon-bg" style="width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, rgba(79, 125, 243, 0.12), rgba(113, 70, 237, 0.12)); color: #4f7df3; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="users" aria-hidden="true" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="community-members-section__title" style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; line-height: 1.25; letter-spacing: -0.01em;">Community Members</h2>
                        <p class="community-members-section__subtitle" style="font-size: 13.5px; color: #64748b; margin: 0; line-height: 1.4;">Browse and connect with active members, admins, and moderators.</p>
                    </div>
                </div>

                <div class="community-members-section__filter-pills" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
                    <a href="{{ route('member.community.show', [$community, 'tab' => 'members']) }}" 
                       class="community-filter-pill {{ !request('role') ? 'is-active' : '' }}"
                       style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 10px; text-decoration: none; line-height: 1; transition: all 0.2s ease; {{ !request('role') ? 'background: linear-gradient(135deg, #176bff 0%, #7146ed 100%); color: #ffffff !important; box-shadow: 0 4px 12px rgba(23, 107, 255, 0.3);' : 'color: #64748b; background: transparent;' }}">
                        <i data-lucide="users" aria-hidden="true" style="width: 15px; height: 15px;"></i> All Members
                    </a>
                    <a href="{{ route('member.community.show', [$community, 'tab' => 'members', 'role' => 'admin']) }}" 
                       class="community-filter-pill {{ request('role') === 'admin' ? 'is-active' : '' }}"
                       style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 10px; text-decoration: none; line-height: 1; transition: all 0.2s ease; {{ request('role') === 'admin' ? 'background: linear-gradient(135deg, #176bff 0%, #7146ed 100%); color: #ffffff !important; box-shadow: 0 4px 12px rgba(23, 107, 255, 0.3);' : 'color: #64748b; background: transparent;' }}">
                        <i data-lucide="shield-check" aria-hidden="true" style="width: 15px; height: 15px;"></i> Admins
                    </a>
                    <a href="{{ route('member.community.show', [$community, 'tab' => 'members', 'role' => 'moderator']) }}" 
                       class="community-filter-pill {{ request('role') === 'moderator' ? 'is-active' : '' }}"
                       style="display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 10px; text-decoration: none; line-height: 1; transition: all 0.2s ease; {{ request('role') === 'moderator' ? 'background: linear-gradient(135deg, #176bff 0%, #7146ed 100%); color: #ffffff !important; box-shadow: 0 4px 12px rgba(23, 107, 255, 0.3);' : 'color: #64748b; background: transparent;' }}">
                        <i data-lucide="shield" aria-hidden="true" style="width: 15px; height: 15px;"></i> Moderators
                    </a>
                </div>
            </header>

            <div class="community-members-grid" data-community-members-grid style="display: flex; flex-direction: column; gap: 16px; width: 100%;">
                @if (!request('role') || request('role') === 'owner' || request('role') === 'admin')
                    @php
                        $ownerMember = new \App\Models\CommunityMember([
                            'community_id' => $community->id,
                            'member_id' => $community->owner_id,
                            'role' => 'owner',
                            'status' => 'accepted',
                            'joined_at' => $community->created_at,
                        ]);
                        $ownerMember->setRelation('member', $community->owner);
                    @endphp
                    @include('member.community.partials.member-card', ['membership' => $ownerMember, 'community' => $community])
                @endif

                @foreach ($acceptedMembers as $membership)
                    @if ((int) $membership->member_id !== (int) $community->owner_id)
                        @include('member.community.partials.member-card', ['membership' => $membership, 'community' => $community])
                    @endif
                @endforeach
            </div>

            @if ($acceptedMembers->hasPages())
                <div class="pagination-wrapper">
                    {{ $acceptedMembers->links('member.search.pagination') }}
                </div>
            @endif
        </section>

    <!-- Tab 4: Photos Gallery -->
    @elseif ($activeTab === 'photos')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="image"></i> Community Photos</h2>
                    <p>Photos auto-collected from posts published in {{ $community->name }}.</p>
                </div>
            </header>

            @include('member.community.partials.gallery-grid', ['items' => $photos])
        </section>

    <!-- Tab 5: Videos Gallery -->
    @elseif ($activeTab === 'videos')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="video"></i> Community Videos</h2>
                    <p>Videos auto-collected from posts published in {{ $community->name }}.</p>
                </div>
            </header>

            @include('member.community.partials.gallery-grid', ['items' => $videos])
        </section>

    <!-- Tab 6: Combined Media Gallery -->
    @elseif ($activeTab === 'media')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="grid"></i> All Media Uploads</h2>
                    <p>Combined photo and video gallery of {{ $community->name }}.</p>
                </div>
            </header>

            @include('member.community.partials.gallery-grid', ['items' => $mediaItems])
        </section>

    <!-- Tab 7: Announcements Tab -->
    @elseif ($activeTab === 'announcements')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="megaphone" style="color: #7d42f0;"></i> Official Announcements</h2>
                    <p>Important updates and broadcast announcements published by community admins.</p>
                </div>
            </header>

            <div class="post-feed">
                @forelse ($announcementsList as $post)
                    @include('member.posts.partials.card', compact('post'))
                @empty
                    <div class="fb-empty-state">
                        <div class="fb-empty-state__icon">
                            <i data-lucide="megaphone" aria-hidden="true"></i>
                        </div>
                        <h3>No Announcements Yet</h3>
                        <p>No official community announcements have been published.</p>
                    </div>
                @endforelse
            </div>

            @if ($announcementsList->hasPages())
                <div class="pagination-wrapper">
                    {{ $announcementsList->links('member.search.pagination') }}
                </div>
            @endif
        </section>

    <!-- Tab 8: Rules Tab -->
    @elseif ($activeTab === 'rules')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <h2><i data-lucide="shield"></i> Community Rules & Guidelines</h2>
                        <p>Guidelines and rules set by community owners to maintain order.</p>
                    </div>
                    @if ($isAdmin)
                        <a href="{{ route('member.community.admin', [$community, 'tab' => 'settings']) }}" class="member-button member-button--secondary" style="padding: 6px 12px; font-size: 12px;">
                            <i data-lucide="edit-3"></i> Edit Rules
                        </a>
                    @endif
                </div>
            </header>

            <div style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.7; white-space: pre-line;">
                {{ $community->rules ?: "1. Be respectful to all community members.\n2. No spam, self-promotion, or unsolicited advertising.\n3. Respect privacy and confidentiality.\n4. Follow MLM Book general terms of service." }}
            </div>
        </section>

    <!-- Tab 9: Files Architecture Placeholder -->
    @elseif ($activeTab === 'files')
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="folder"></i> Shared Files & Documents</h2>
                    <p>Document sharing and downloadable file attachments repository.</p>
                </div>
            </header>

            <div class="fb-empty-state">
                <div class="fb-empty-state__icon">
                    <i data-lucide="file-text" aria-hidden="true"></i>
                </div>
                <h3>Shared Files Repository</h3>
                <p>Document attachment sharing architecture ready for Phase 7 integrations.</p>
            </div>
        </section>

    <!-- Tab 10: Join Requests (Admin Panel) -->
    @elseif ($activeTab === 'requests' && $isAdmin)
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <div>
                    <h2><i data-lucide="user-check" aria-hidden="true"></i> Pending Join Requests</h2>
                    <p>Review and manage membership requests for this private community.</p>
                </div>
            </header>

            <div class="fb-friends-card-grid" data-community-requests-grid>
                @forelse ($pendingRequests as $membership)
                    @include('member.community.partials.request-card', ['membership' => $membership, 'community' => $community])
                @empty
                    <div class="fb-empty-state" style="grid-column: 1 / -1; width: 100%;">
                        <div class="fb-empty-state__icon">
                            <i data-lucide="user-check" aria-hidden="true"></i>
                        </div>
                        <h3>No Pending Requests</h3>
                        <p>All join requests have been processed.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</div>

@push('modals')
@include('member.community.partials.share-modal', ['community' => $community])
@include('member.community.partials.report-modal')
@include('member.community.partials.lightbox-modal')
@include('member.community.partials.preferences-modal', ['community' => $community])
@endpush
@endsection

@push('scripts')
<script src="{{ asset('member_assets/js/member-posts.js') }}"></script>
<script src="{{ asset('member_assets/js/member-community.js') }}"></script>
@endpush
