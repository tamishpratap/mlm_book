@extends('member.layouts.app')

@section('title', $businessPage->seo_title ?? ($businessPage->page_name . ' - Business Profile'))

@push('styles')
    <!-- SEO & Open Graph Meta Tags -->
    <meta name="description" content="{{ $businessPage->meta_description ?? Str::limit($businessPage->description, 160) }}">
    <meta property="og:title" content="{{ $businessPage->seo_title ?? $businessPage->page_name }}">
    <meta property="og:description" content="{{ $businessPage->meta_description ?? Str::limit($businessPage->description, 160) }}">
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="{{ route('member.business-pages.show', $businessPage) }}">
    @if ($businessPage->logo)
        <meta property="og:image" content="{{ asset($businessPage->logo) }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $businessPage->page_name }}">
    <meta name="twitter:description" content="{{ Str::limit($businessPage->description, 160) }}">
@endpush

@section('content')
@php
    $hasLogo = $businessPage->logo && file_exists(public_path($businessPage->logo));
    $hasCover = $businessPage->cover_photo && file_exists(public_path($businessPage->cover_photo));
    $currentMemberId = auth('member')->id();
    $currentTab = $activeTab ?? 'home';
    $followStatus = $businessPage->getFollowStatus($currentMemberId);
    $isFollower = $businessPage->isFollowedBy($currentMemberId);
@endphp

<div class="biz-page">
    <!-- Business Hero Profile Header -->
    <div class="biz-hero">
        <div class="biz-hero__cover">
            @if ($hasCover)
                <img class="biz-hero__cover-img" src="{{ asset($businessPage->cover_photo) }}" alt="{{ $businessPage->page_name }} cover banner">
            @endif
            <div class="biz-hero__cover-overlay"></div>
        </div>

        <div class="biz-hero__body">
            <div class="biz-hero__avatar">
                @if ($hasLogo)
                    <img src="{{ asset($businessPage->logo) }}" alt="{{ $businessPage->page_name }} logo">
                @else
                    <span>{{ $businessPage->initials }}</span>
                @endif
            </div>

            <div class="biz-hero__content">
                <div class="biz-hero__title-row">
                    <div>
                        <h1>
                            {{ $businessPage->page_name }}
                            @if ($businessPage->is_verified)
                                <i data-lucide="badge-check" style="width: 22px; height: 22px; color: #20c875;" title="Verified Business"></i>
                            @endif
                        </h1>
                        <span class="biz-hero__username">{{ '@' . $businessPage->page_username }}</span>
                    </div>

                    <div class="biz-hero__actions">
                        @if ($canManage)
                            <!-- Page Management Actions (Owner / Team Members) -->
                            @if ($isOwner)
                                <span class="biz-badge biz-badge--category" style="padding: 8px 14px; font-size: 13px;">
                                    <i data-lucide="crown" style="width: 14px; height: 14px;"></i> Page Owner
                                </span>
                            @else
                                <span class="biz-badge biz-badge--category" style="padding: 8px 14px; font-size: 13px; background: rgba(79, 125, 243, 0.15); color: #4f7df3;">
                                    <i data-lucide="shield" style="width: 14px; height: 14px;"></i> Page Team
                                </span>
                            @endif

                            @if ($isAdmin)
                                <a href="{{ route('member.business-pages.analytics.index', $businessPage) }}" class="member-button member-button--secondary">
                                    <i data-lucide="bar-chart-3" aria-hidden="true"></i> Analytics
                                </a>
                                <a href="{{ route('member.business-pages.inbox.index', $businessPage) }}" class="member-button member-button--secondary">
                                    <i data-lucide="inbox" aria-hidden="true"></i> Inbox
                                    @if ($businessPage->unreadMessagesCount() > 0)
                                        <span style="background: #e53e3e; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 11px; margin-left: 4px;">{{ $businessPage->unreadMessagesCount() }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('member.business-pages.team.index', $businessPage) }}" class="member-button member-button--secondary">
                                    <i data-lucide="users-round" aria-hidden="true"></i> Manage Team
                                </a>
                            @endif

                            @if ($isOwner)
                                <a href="{{ route('member.business-pages.edit', $businessPage) }}" class="member-button member-button--primary">
                                    <i data-lucide="edit-3" aria-hidden="true"></i> Edit Page
                                </a>
                            @endif
                        @else
                            <!-- Visitor / Follower Actions -->
                            <button type="button" class="member-button {{ $followStatus === 'accepted' || $followStatus === 'pending' ? 'member-button--secondary' : 'member-button--primary' }}" id="bizFollowBtn" onclick="toggleBizFollow()">
                                @if ($followStatus === 'accepted')
                                    <i data-lucide="check" aria-hidden="true" style="color: #20c875;"></i> <span id="bizFollowBtnText">Following</span>
                                @elseif ($followStatus === 'pending')
                                    <i data-lucide="clock" aria-hidden="true" style="color: #f7b940;"></i> <span id="bizFollowBtnText">Requested</span>
                                @else
                                    <i data-lucide="user-plus" aria-hidden="true"></i> <span id="bizFollowBtnText">Follow</span>
                                @endif
                            </button>

                            <!-- Customer Message Page Button (Visitors Only) -->
                            <button type="button" class="member-button member-button--secondary" onclick="openCustomerMessageModal()">
                                <i data-lucide="message-square" aria-hidden="true"></i> Message
                            </button>
                        @endif

                        <!-- Share Button (Always Visible) -->
                        <button type="button" class="member-button member-button--secondary" onclick="openBizShareModal()">
                            <i data-lucide="share-2" aria-hidden="true"></i> Share
                        </button>

                        <!-- More Menu Dropdown -->
                        <div class="biz-dropdown">
                            <button type="button" class="member-button member-button--secondary" onclick="toggleBizDropdown(event)" aria-label="More options">
                                <i data-lucide="more-horizontal" aria-hidden="true"></i>
                            </button>
                            <div class="biz-dropdown-menu" id="bizDropdownMenu">
                                <button type="button" class="biz-dropdown-item" onclick="copyBizLink('{{ route('member.business-pages.show', $businessPage) }}')">
                                    <i data-lucide="copy" style="width: 15px; height: 15px;"></i> Copy Page Link
                                </button>
                                @if ($businessPage->owner)
                                    <a href="{{ route('member.people.show', $businessPage->owner) }}" class="biz-dropdown-item">
                                        <i data-lucide="user" style="width: 15px; height: 15px;"></i> View Owner Profile
                                    </a>
                                @endif
                                <div style="height: 1px; background: #e7ecf4; margin: 4px 0;"></div>
                                <div style="padding: 6px 16px; font-size: 11px; color: #98a2b3;">
                                    Page ID: <code>{{ $businessPage->page_id ?? 'biz_' . $businessPage->id }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="biz-card__badges">
                    <span class="biz-badge biz-badge--category">
                        <i data-lucide="tag" style="width: 12px; height: 12px;"></i> {{ $businessPage->category }}
                    </span>
                    <span class="biz-badge biz-badge--visibility">
                        @if ($businessPage->visibility === 'private')
                            <i data-lucide="lock" style="width: 12px; height: 12px;"></i> Private
                        @elseif ($businessPage->visibility === 'draft')
                            <i data-lucide="file-text" style="width: 12px; height: 12px;"></i> Draft
                        @else
                            <i data-lucide="globe" style="width: 12px; height: 12px;"></i> Public
                        @endif
                    </span>
                    @if ($businessPage->is_verified)
                        <span class="biz-badge biz-badge--verified">
                            <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> Official Verified Page
                        </span>
                    @else
                        <span class="biz-badge biz-badge--unverified">
                            <i data-lucide="shield" style="width: 12px; height: 12px;"></i> Unverified Page
                        </span>
                    @endif
                </div>

                <div class="biz-hero__meta">
                    <span>
                        <i data-lucide="star" style="width: 14px; height: 14px; color: #f7b940;"></i>
                        <strong>{{ $businessPage->averageRating() }} ★</strong> ({{ $businessPage->reviewsCount() }} Reviews)
                    </span>
                    <span>
                        <i data-lucide="users" style="width: 14px; height: 14px;"></i>
                        <strong id="bizHeroFollowersCount">{{ $audienceCounters['followers'] ?? $businessPage->followersCount() }}</strong> Followers
                    </span>
                    @if ($businessPage->formatted_location)
                        <span>
                            <i data-lucide="map-pin" style="width: 14px; height: 14px;"></i>
                            {{ $businessPage->formatted_location }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Navigation Tabs -->
    <nav class="biz-nav-tabs" aria-label="Business Profile Tabs">
        <a class="biz-nav-tab {{ $currentTab === 'home' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'home']) }}">
            <i data-lucide="home" aria-hidden="true"></i> Home
        </a>
        <a class="biz-nav-tab {{ $currentTab === 'about' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'about']) }}">
            <i data-lucide="info" aria-hidden="true"></i> About
        </a>
        <a class="biz-nav-tab {{ $currentTab === 'photos' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'photos']) }}">
            <i data-lucide="image" aria-hidden="true"></i> Photos
        </a>
        <a class="biz-nav-tab {{ $currentTab === 'videos' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'videos']) }}">
            <i data-lucide="video" aria-hidden="true"></i> Videos
        </a>
        <a class="biz-nav-tab {{ $currentTab === 'events' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'events']) }}">
            <i data-lucide="calendar" aria-hidden="true"></i> Events
        </a>
        <a class="biz-nav-tab {{ $currentTab === 'reviews' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'reviews']) }}">
            <i data-lucide="star" aria-hidden="true"></i> Reviews ({{ $businessPage->reviewsCount() }})
        </a>
        <a class="biz-nav-tab {{ $currentTab === 'followers' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'followers']) }}">
            <i data-lucide="users" aria-hidden="true"></i> Followers ({{ $audienceCounters['followers'] ?? $businessPage->followersCount() }})
        </a>
        @if ($isAdmin)
            <a class="biz-nav-tab" href="{{ route('member.business-pages.analytics.index', $businessPage) }}">
                <i data-lucide="bar-chart-3" aria-hidden="true"></i> Analytics
            </a>
            <a class="biz-nav-tab" href="{{ route('member.business-pages.inbox.index', $businessPage) }}">
                <i data-lucide="inbox" aria-hidden="true"></i> Inbox
                @if ($businessPage->unreadMessagesCount() > 0)
                    <span style="background: #e53e3e; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 10px; margin-left: 2px;">{{ $businessPage->unreadMessagesCount() }}</span>
                @endif
            </a>
            <a class="biz-nav-tab" href="{{ route('member.business-pages.team.index', $businessPage) }}">
                <i data-lucide="users-round" aria-hidden="true"></i> Team
            </a>
        @endif
        @if ($isOwner)
            <a class="biz-nav-tab {{ $currentTab === 'settings' ? 'is-active' : '' }}" href="{{ route('member.business-pages.show', [$businessPage, 'tab' => 'settings']) }}">
                <i data-lucide="settings" aria-hidden="true"></i> Settings
            </a>
        @endif
    </nav>
    <!-- Tab Contents (Unified Detail Grid Architecture across ALL Tabs) -->
    <div class="biz-detail-grid">
        <!-- Main Content Area (Left Column) -->
        <div class="biz-detail-main">
            @if ($currentTab === 'home')
                <!-- HOME TAB CONTENT (TIMELINE FEED) -->
                @if ($isOwner)
                    @include('member.business-pages.partials.composer', compact('businessPage'))
                @endif

                <div id="bizTimelineFeed" style="display: flex; flex-direction: column; gap: 20px;">
                    @if ($pinnedPost)
                        @include('member.posts.partials.card', ['post' => $pinnedPost])
                    @endif

                    @forelse ($timelinePosts as $post)
                        @include('member.posts.partials.card', compact('post'))
                    @empty
                        @if (!$pinnedPost)
                            <div class="biz-phase-placeholder" id="bizTimelineEmptyState">
                                <div class="biz-phase-placeholder__icon">
                                    <i data-lucide="newspaper" style="width: 28px; height: 28px;"></i>
                                </div>
                                <h3>No Timeline Posts Yet</h3>
                                @if ($isOwner)
                                    <p>Create your first business post using the composer above!</p>
                                @else
                                    <p>This business page has not published any posts yet.</p>
                                @endif
                            </div>
                        @endif
                    @endforelse
                </div>

                @if ($timelinePosts->hasPages())
                    <div class="pagination-wrapper">
                        {{ $timelinePosts->links('member.search.pagination') }}
                    </div>
                @endif

            @elseif ($currentTab === 'about')
                <!-- ABOUT TAB CONTENT -->
                <div class="biz-info-card">
                    <h3 class="biz-info-card__title">
                        <i data-lucide="file-text" style="color: #4f7df3;"></i> Overview & Story
                    </h3>
                    @if ($businessPage->description)
                        <p style="color: #1d2738; font-size: 14.5px; line-height: 1.65; margin: 0; white-space: pre-line;">{{ $businessPage->description }}</p>
                    @else
                        <p style="color: #98a2b3; font-style: italic; margin: 0;">No description available.</p>
                    @endif
                </div>

                <div class="biz-info-card">
                    <h3 class="biz-info-card__title">
                        <i data-lucide="contact" style="color: #4f7df3;"></i> Contact Information
                    </h3>
                    <div class="biz-contact-list">
                        @if ($businessPage->website)
                            <div class="biz-contact-item">
                                <div class="biz-contact-icon"><i data-lucide="globe" style="width: 18px; height: 18px;"></i></div>
                                <div style="min-width: 0; flex: 1;">
                                    <span style="display: block; font-size: 11.5px; color: #98a2b3; text-transform: uppercase;">Website</span>
                                    <a href="{{ $businessPage->website }}" target="_blank" rel="noopener noreferrer" style="color: #4f7df3; font-weight: 600; text-decoration: underline; word-break: break-all;">
                                        {{ $businessPage->website }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($businessPage->email)
                            <div class="biz-contact-item">
                                <div class="biz-contact-icon"><i data-lucide="mail" style="width: 18px; height: 18px;"></i></div>
                                <div style="min-width: 0; flex: 1;">
                                    <span style="display: block; font-size: 11.5px; color: #98a2b3; text-transform: uppercase;">Email</span>
                                    <a href="mailto:{{ $businessPage->email }}" style="color: #1d2738; font-weight: 500; word-break: break-all;">{{ $businessPage->email }}</a>
                                </div>
                            </div>
                        @endif

                        @if ($businessPage->phone)
                            <div class="biz-contact-item">
                                <div class="biz-contact-icon"><i data-lucide="phone" style="width: 18px; height: 18px;"></i></div>
                                <div style="min-width: 0; flex: 1;">
                                    <span style="display: block; font-size: 11.5px; color: #98a2b3; text-transform: uppercase;">Phone</span>
                                    <a href="tel:{{ $businessPage->phone }}" style="color: #1d2738; font-weight: 500;">{{ $businessPage->phone }}</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif ($currentTab === 'photos')
                <!-- PHOTOS TAB CONTENT -->
                <div class="biz-info-card">
                    <h3 class="biz-info-card__title">
                        <i data-lucide="image" style="color: #4f7df3;"></i> Business Photo Gallery
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
                        @forelse ($photos as $post)
                            <div style="border-radius: 14px; overflow: hidden; height: 180px; background: #f4f6fa;">
                                <a href="{{ route('member.posts.show', $post) }}">
                                    <img src="{{ asset($post->media_path) }}" alt="Gallery Image" style="width: 100%; height: 100%; object-fit: cover;">
                                </a>
                            </div>
                        @empty
                            <div class="biz-phase-placeholder" style="grid-column: 1 / -1; width: 100%;">
                                <div class="biz-phase-placeholder__icon"><i data-lucide="image" style="width: 28px; height: 28px;"></i></div>
                                <h3>No Photos Uploaded</h3>
                            </div>
                        @endforelse
                    </div>
                    @if ($photos->hasPages())
                        <div class="pagination-wrapper">{{ $photos->links('member.search.pagination') }}</div>
                    @endif
                </div>

            @elseif ($currentTab === 'videos')
                <!-- VIDEOS TAB CONTENT -->
                <div class="biz-info-card">
                    <h3 class="biz-info-card__title">
                        <i data-lucide="video" style="color: #4f7df3;"></i> Business Video Gallery
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                        @forelse ($videos as $post)
                            <div style="border-radius: 14px; overflow: hidden; background: #000;">
                                <video src="{{ asset($post->media_path) }}" controls style="width: 100%; max-height: 220px;"></video>
                            </div>
                        @empty
                            <div class="biz-phase-placeholder" style="grid-column: 1 / -1; width: 100%;">
                                <div class="biz-phase-placeholder__icon"><i data-lucide="video" style="width: 28px; height: 28px;"></i></div>
                                <h3>No Videos Uploaded</h3>
                            </div>
                        @endforelse
                    </div>
                    @if ($videos->hasPages())
                        <div class="pagination-wrapper">{{ $videos->links('member.search.pagination') }}</div>
                    @endif
                </div>

            @elseif ($currentTab === 'followers')
                <!-- FOLLOWERS TAB CONTENT -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px;">
                        <div class="biz-info-card" style="padding: 14px; align-items: center; flex-direction: row; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(79, 125, 243, 0.1); color: #4f7df3; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div>
                                <strong style="font-size: 18px; color: #1d2738; display: block;" id="followersTabCounter">{{ $audienceCounters['followers'] }}</strong>
                                <span style="font-size: 12px; color: #687386;">Followers</span>
                            </div>
                        </div>

                        <div class="biz-info-card" style="padding: 14px; align-items: center; flex-direction: row; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(32, 200, 117, 0.1); color: #20c875; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="newspaper" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div>
                                <strong style="font-size: 18px; color: #1d2738; display: block;">{{ $audienceCounters['posts'] }}</strong>
                                <span style="font-size: 12px; color: #687386;">Posts Published</span>
                            </div>
                        </div>

                        <div class="biz-info-card" style="padding: 14px; align-items: center; flex-direction: row; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(138, 43, 226, 0.1); color: #8a2be2; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="image" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div>
                                <strong style="font-size: 18px; color: #1d2738; display: block;">{{ $audienceCounters['photos'] }}</strong>
                                <span style="font-size: 12px; color: #687386;">Photos</span>
                            </div>
                        </div>
                    </div>

                    <div class="biz-info-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                            <form method="GET" action="{{ route('member.business-pages.show', $businessPage) }}" style="display: flex; align-items: center; gap: 12px; flex: 1; flex-wrap: wrap;">
                                <input type="hidden" name="tab" value="followers">
                                <div style="flex: 1; min-width: 180px;">
                                    <input type="text" name="q" value="{{ request('q') }}" class="biz-search-input" placeholder="Search followers by name or username...">
                                </div>
                                <select name="sort" class="biz-filter-select" onchange="this.form.submit()">
                                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest Followers</option>
                                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest Followers</option>
                                    <option value="alphabetical" {{ request('sort') === 'alphabetical' ? 'selected' : '' }}>Alphabetical (A-Z)</option>
                                </select>
                                <button type="submit" class="member-button member-button--primary" style="padding: 8px 16px;">Search</button>
                            </form>

                            @if ($isAdmin)
                                <button type="button" class="member-button member-button--secondary" onclick="openFollowInviteModal()">
                                    <i data-lucide="user-plus"></i> Invite Friends to Follow
                                </button>
                            @endif
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 16px;">
                            @forelse ($followers as $fl)
                                <div id="followerCard-{{ $fl->id }}" style="padding: 14px; border-radius: 14px; background: #f8fafc; border: 1px solid #e7ecf4; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <a href="{{ route('member.people.show', $fl->member) }}" style="display: inline-flex; flex-shrink: 0; text-decoration: none;">
                                            <img src="{{ $fl->member->avatar_url }}" alt="" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null;this.src='{{ asset('member_assets/images/dashboard/image/profile.png') }}';">
                                        </a>
                                        <div>
                                            <strong style="font-size: 14px; color: #1d2738; display: block;">
                                                <a href="{{ route('member.people.show', $fl->member) }}" style="color: inherit; text-decoration: none;">{{ $fl->member->name }}</a>
                                            </strong>
                                            <span style="font-size: 12px; color: #98a2b3; display: block;">{{ '@' . ($fl->member->user_id ?? 'member') }}</span>
                                        </div>
                                    </div>
                                    @if ($isAdmin)
                                        <button type="button" class="mini-button" style="color: #e53e3e;" title="Remove Follower" onclick="removeBizFollower({{ $fl->id }}, '{{ $fl->member->name }}')">
                                            <i data-lucide="user-minus"></i>
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div class="biz-phase-placeholder" style="grid-column: 1 / -1; width: 100%;">
                                    <div class="biz-phase-placeholder__icon"><i data-lucide="users" style="width: 28px; height: 28px;"></i></div>
                                    <h3>No Followers Found</h3>
                                </div>
                            @endforelse
                        </div>

                        @if ($followers->hasPages())
                            <div class="pagination-wrapper">{{ $followers->links('member.search.pagination') }}</div>
                        @endif
                    </div>
                </div>

            @elseif ($currentTab === 'reviews')
                <!-- REVIEWS TAB CONTENT -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Overall Ratings Summary Card -->
                    <div class="biz-info-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="font-size: 42px; font-weight: 800; color: #1d2738; line-height: 1;">
                                    {{ number_format($businessPage->averageRating(), 1) }}
                                </div>
                                <div>
                                    <div style="color: #f7b940; font-size: 16px;">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <i data-lucide="star" style="width: 16px; height: 16px; fill: {{ $star <= round($businessPage->averageRating()) ? '#f7b940' : 'none' }};"></i>
                                        @endfor
                                    </div>
                                    <span style="font-size: 13px; color: #687386;">Based on {{ $businessPage->reviewsCount() }} {{ \Illuminate\Support\Str::plural('review', $businessPage->reviewsCount()) }}</span>
                                </div>
                            </div>

                            <div>
                                @if ($userReview)
                                    <button type="button" class="member-button member-button--secondary" onclick="openWriteReviewModal(true)">
                                        <i data-lucide="edit-3"></i> Edit Your Review
                                    </button>
                                @elseif ($isFollower && !$isOwner)
                                    <button type="button" class="member-button member-button--primary" onclick="openWriteReviewModal(false)">
                                        <i data-lucide="star"></i> Write a Review
                                    </button>
                                @elseif ($isOwner)
                                    <span style="font-size: 12px; color: #98a2b3; font-style: italic;">Page owners cannot review their own page.</span>
                                @else
                                    <button type="button" class="member-button member-button--disabled" disabled title="Follow this business page to write a review">
                                        <i data-lucide="lock"></i> Follow to Write Review
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Rating Distribution Bars -->
                        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e7ecf4; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;">
                            @php $totalRev = max(1, $businessPage->reviewsCount()); @endphp
                            @foreach ([5, 4, 3, 2, 1] as $star)
                                @php $cnt = $ratingDistribution[$star] ?? 0; $pct = round(($cnt / $totalRev) * 100); @endphp
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #687386;">
                                    <span style="width: 45px;">{{ $star }} Stars</span>
                                    <div style="flex: 1; height: 8px; border-radius: 4px; background: #edf3ff; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $pct }}%; background: #f7b940; border-radius: 4px;"></div>
                                    </div>
                                    <span style="width: 30px; text-align: right; font-weight: 600;">{{ $cnt }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Search & Filter Bar -->
                    <div class="biz-info-card">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                            <form method="GET" action="{{ route('member.business-pages.show', $businessPage) }}" style="display: flex; align-items: center; gap: 12px; flex: 1; flex-wrap: wrap;">
                                <input type="hidden" name="tab" value="reviews">
                                <div style="flex: 1; min-width: 180px;">
                                    <input type="text" name="q" value="{{ request('q') }}" class="biz-search-input" placeholder="Search reviews by keyword or author...">
                                </div>
                                <select name="sort" class="biz-filter-select" onchange="this.form.submit()">
                                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest Reviews</option>
                                    <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>Highest Rating</option>
                                    <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>Lowest Rating</option>
                                    <option value="recommended" {{ request('sort') === 'recommended' ? 'selected' : '' }}>Recommended First</option>
                                    <option value="photos" {{ request('sort') === 'photos' ? 'selected' : '' }}>With Photos Only</option>
                                </select>
                                <button type="submit" class="member-button member-button--primary" style="padding: 8px 16px;">Filter</button>
                            </form>
                        </div>

                        <!-- Reviews Cards List -->
                        <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 16px;" id="bizReviewsList">
                            @forelse ($reviews as $rv)
                                <div id="reviewCard-{{ $rv->id }}" class="card" style="padding: 18px; border-radius: 16px; background: #fff; border: 1px solid #e7ecf4; @if($rv->is_hidden) opacity: 0.6; border-style: dashed; @endif">
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
                                        <!-- Reviewer Profile -->
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <img src="{{ $rv->member->profile_photo ? asset($rv->member->profile_photo) : asset('member_assets/images/dashboard/image/profile.png') }}" alt="{{ $rv->member->name }}" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
                                            <div>
                                                <strong style="font-size: 14.5px; color: #1d2738; display: block;">{{ $rv->member->name }}</strong>
                                                <span style="font-size: 12px; color: #98a2b3;">{{ $rv->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>

                                        <!-- Rating Stars & Recommendation Badge -->
                                        <div style="text-align: right;">
                                            <div style="color: #f7b940; font-size: 14px;">
                                                @for ($star = 1; $star <= 5; $star++)
                                                    <i data-lucide="star" style="width: 14px; height: 14px; fill: {{ $star <= $rv->rating ? '#f7b940' : 'none' }};"></i>
                                                @endfor
                                            </div>
                                            <span class="biz-badge {{ $rv->isRecommended() ? 'biz-badge--verified' : 'biz-badge--unverified' }}" style="font-size: 11px; padding: 2px 8px; margin-top: 4px; display: inline-block;">
                                                @if ($rv->isRecommended())
                                                    <i data-lucide="thumbs-up" style="width: 10px; height: 10px;"></i> Recommends
                                                @else
                                                    <i data-lucide="thumbs-down" style="width: 10px; height: 10px;"></i> Doesn't Recommend
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    @if ($rv->title)
                                        <h4 style="font-size: 15px; font-weight: 700; color: #1d2738; margin: 12px 0 6px 0;">{{ $rv->title }}</h4>
                                    @endif

                                    <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 6px 0; white-space: pre-line;">{{ $rv->body }}</p>

                                    <!-- Review Photos Gallery -->
                                    @if (!empty($rv->photos) && is_array($rv->photos))
                                        <div style="display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
                                            @foreach ($rv->photos as $ph)
                                                <a href="{{ asset($ph) }}" target="_blank" style="width: 70px; height: 70px; border-radius: 10px; overflow: hidden; border: 1px solid #e7ecf4;">
                                                    <img src="{{ asset($ph) }}" alt="Review photo" style="width: 100%; height: 100%; object-fit: cover;">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Official Owner Reply Box -->
                                    @if ($rv->officialReply)
                                        <div style="margin-top: 14px; padding: 14px; border-radius: 12px; background: rgba(79, 125, 243, 0.05); border-left: 3px solid #4f7df3;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i data-lucide="building-2" style="width: 16px; height: 16px; color: #4f7df3;"></i>
                                                    <strong style="font-size: 13px; color: #4f7df3;">Official Reply from {{ $businessPage->page_name }}</strong>
                                                </div>
                                                <span style="font-size: 11px; color: #98a2b3;">{{ $rv->officialReply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p style="font-size: 13.5px; color: #1e293b; margin: 0;">{{ $rv->officialReply->reply }}</p>
                                        </div>
                                    @endif

                                    <!-- Review Footer Actions -->
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px;">
                                        <!-- Helpful Vote Buttons -->
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            @php $userVote = $rv->userVote($currentMemberId); @endphp
                                            <button type="button" class="mini-button" style="color: {{ $userVote === 'helpful' ? '#4f7df3' : '#64748b' }}; font-weight: 600;" onclick="voteBizReview({{ $rv->id }}, 'helpful')">
                                                <i data-lucide="thumbs-up" style="width: 14px; height: 14px;"></i> Helpful (<span id="helpfulCount-{{ $rv->id }}">{{ $rv->helpful_count }}</span>)
                                            </button>
                                            <button type="button" class="mini-button" style="color: {{ $userVote === 'unhelpful' ? '#e53e3e' : '#64748b' }}; font-weight: 600;" onclick="voteBizReview({{ $rv->id }}, 'unhelpful')">
                                                <i data-lucide="thumbs-down" style="width: 14px; height: 14px;"></i> Unhelpful (<span id="unhelpfulCount-{{ $rv->id }}">{{ $rv->unhelpful_count }}</span>)
                                            </button>
                                        </div>

                                        <!-- Options & Moderation Menu -->
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            @if ($isAdmin)
                                                <button type="button" class="mini-button" style="color: #4f7df3;" onclick="openOfficialReplyModal({{ $rv->id }}, '{{ $rv->officialReply->reply ?? '' }}')">
                                                    <i data-lucide="message-square"></i> {{ $rv->officialReply ? 'Edit Reply' : 'Official Reply' }}
                                                </button>
                                                <button type="button" class="mini-button" style="color: #64748b;" onclick="toggleHideBizReview({{ $rv->id }})">
                                                    <i data-lucide="{{ $rv->is_hidden ? 'eye' : 'eye-off' }}"></i> {{ $rv->is_hidden ? 'Unhide' : 'Hide' }}
                                                </button>
                                            @endif

                                            <button type="button" class="mini-button" style="color: #94a3b8;" onclick="openReportReviewModal({{ $rv->id }})">
                                                <i data-lucide="flag"></i> Report
                                            </button>

                                            @if ($rv->member_id === $currentMemberId || $isAdmin)
                                                <button type="button" class="mini-button" style="color: #e53e3e;" onclick="deleteBizReview({{ $rv->id }})">
                                                    <i data-lucide="trash-2"></i> Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="biz-phase-placeholder">
                                    <div class="biz-phase-placeholder__icon"><i data-lucide="star" style="width: 28px; height: 28px;"></i></div>
                                    <h3>No Reviews Submitted Yet</h3>
                                    <p>Be the first follower to leave a review and share your feedback for {{ $businessPage->page_name }}!</p>
                                </div>
                            @endforelse
                        </div>

                        @if ($reviews->hasPages())
                            <div class="pagination-wrapper">{{ $reviews->links('member.search.pagination') }}</div>
                        @endif
                    </div>
                </div>

            @elseif ($currentTab === 'events')
                <div class="biz-phase-placeholder">
                    <div class="biz-phase-placeholder__icon"><i data-lucide="calendar" style="width: 28px; height: 28px;"></i></div>
                    <h3>Business Events</h3>
                    <p>Hosting corporate events and launches will be unlocked in <strong>Phase 7</strong>.</p>
                </div>

            @elseif ($currentTab === 'settings' && $isOwner)
                <div class="biz-info-card">
                    <h3 class="biz-info-card__title"><i data-lucide="settings" style="color: #4f7df3;"></i> Business Page Settings</h3>
                    <p style="color: #687386; font-size: 14px; margin: 0;">Manage page information, branding, and visibility settings.</p>
                    <div style="display: flex; gap: 12px; margin-top: 8px;">
                        <a href="{{ route('member.business-pages.edit', $businessPage) }}" class="member-button member-button--primary">
                            <i data-lucide="edit" aria-hidden="true"></i> Edit Page Settings
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Business Information Sidebar Column (RESTORED ON EVERY SINGLE TAB) -->
        <div class="biz-detail-sidebar">
            @include('member.business-pages.partials.sidebar-info', compact('businessPage', 'isOwner'))
        </div>
    </div>
</div>

<!-- Write / Edit Review Modal -->
<div class="biz-modal-overlay" id="bizWriteReviewModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="writeReviewTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="writeReviewTitle"><i data-lucide="star" style="color: #f7b940;"></i> Write a Business Review</h3>
            <button type="button" class="icon-button" onclick="closeWriteReviewModal()"><i data-lucide="x"></i></button>
        </div>
        <form id="bizReviewForm" method="POST" action="{{ route('member.business-pages.reviews.store', $businessPage) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="reviewFormMethod" value="POST">
            <div class="biz-modal__body">
                <!-- Star Rating Selector -->
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Overall Rating</label>
                    <div style="display: flex; gap: 8px; font-size: 24px; color: #f7b940; cursor: pointer;" id="starRatingPicker">
                        @for ($st = 1; $st <= 5; $st++)
                            <i data-lucide="star" data-star="{{ $st }}" onclick="selectStarRating({{ $st }})" style="width: 24px; height: 24px; fill: {{ $st <= 5 ? '#f7b940' : 'none' }};"></i>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="reviewRatingInput" value="5">
                </div>

                <!-- Recommendation Toggle -->
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Do you recommend this business?</label>
                    <div style="display: flex; gap: 12px;">
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                            <input type="radio" name="recommendation" value="recommend" checked>
                            <span><i data-lucide="thumbs-up" style="width: 14px; height: 14px; color: #20c875;"></i> Highly Recommend</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                            <input type="radio" name="recommendation" value="not_recommend">
                            <span><i data-lucide="thumbs-down" style="width: 14px; height: 14px; color: #e53e3e;"></i> Do Not Recommend</span>
                        </label>
                    </div>
                </div>

                <!-- Headline / Title -->
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Review Headline (Optional)</label>
                    <input type="text" name="title" id="reviewTitleInput" class="biz-search-input" placeholder="Summarize your experience..." maxlength="255">
                </div>

                <!-- Review Text Body -->
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Detailed Review</label>
                    <textarea name="body" id="reviewBodyInput" rows="4" class="biz-search-input" style="height: auto; padding: 12px;" placeholder="Tell us about product quality, customer service, or your overall feedback..." required maxlength="5000"></textarea>
                </div>

                <!-- Photos Attachment -->
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Attach Photos (Optional)</label>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="biz-search-input" style="padding: 8px;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeWriteReviewModal()">Cancel</button>
                    <button type="submit" id="submitReviewBtn" class="member-button member-button--primary">Submit Review</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Official Reply Modal -->
<div class="biz-modal-overlay" id="bizOfficialReplyModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="officialReplyTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="officialReplyTitle"><i data-lucide="building-2" style="color: #4f7df3;"></i> Official Page Owner Reply</h3>
            <button type="button" class="icon-button" onclick="closeOfficialReplyModal()"><i data-lucide="x"></i></button>
        </div>
        <form id="bizOfficialReplyForm" method="POST">
            @csrf
            <div class="biz-modal__body">
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Official Response</label>
                    <textarea name="reply" id="officialReplyInput" rows="4" class="biz-search-input" style="height: auto; padding: 12px;" placeholder="Thank the customer or clarify details officially as the business owner..." required maxlength="3000"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeOfficialReplyModal()">Cancel</button>
                    <button type="submit" class="member-button member-button--primary">Publish Reply</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Report Review Modal -->
<div class="biz-modal-overlay" id="bizReportReviewModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="reportReviewTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="reportReviewTitle"><i data-lucide="flag" style="color: #e53e3e;"></i> Report Review</h3>
            <button type="button" class="icon-button" onclick="closeReportReviewModal()"><i data-lucide="x"></i></button>
        </div>
        <form id="bizReportReviewForm" method="POST">
            @csrf
            <div class="biz-modal__body">
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Reason for Report</label>
                    <select name="reason" class="biz-filter-select" style="width: 100%;" required>
                        <option value="spam">Spam / Advertisement</option>
                        <option value="fake_review">Fake / Misleading Review</option>
                        <option value="harassment">Harassment or Hate Speech</option>
                        <option value="offensive">Offensive / Inappropriate Content</option>
                        <option value="other">Other Violation</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Additional Details (Optional)</label>
                    <textarea name="details" rows="3" class="biz-search-input" style="height: auto; padding: 10px;" placeholder="Provide details to assist moderation team..." maxlength="1000"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeReportReviewModal()">Cancel</button>
                    <button type="submit" class="member-button member-button--danger">Submit Report</button>
                </div>
            </div>
        </form>
    </div>
</div>

@include('member.business-pages.partials.share-modal', compact('businessPage'))

<script>
function toggleBizDropdown(e) {
    e.stopPropagation();
    const menu = document.getElementById('bizDropdownMenu');
    menu.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    const menu = document.getElementById('bizDropdownMenu');
    if (menu && menu.classList.contains('show')) {
        menu.classList.remove('show');
    }
});

function toggleBizFollow() {
    const btn = document.getElementById('bizFollowBtn');
    const url = '{{ route("member.business-pages.follow.toggle", $businessPage) }}';
    btn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            if (data.status === 'accepted') {
                btn.className = 'member-button member-button--secondary';
                btn.innerHTML = '<i data-lucide="check" style="color:#20c875;"></i> <span id="bizFollowBtnText">Following</span>';
            } else if (data.status === 'pending') {
                btn.className = 'member-button member-button--secondary';
                btn.innerHTML = '<i data-lucide="clock" style="color:#f7b940;"></i> <span id="bizFollowBtnText">Requested</span>';
            } else {
                btn.className = 'member-button member-button--primary';
                btn.innerHTML = '<i data-lucide="user-plus"></i> <span id="bizFollowBtnText">Follow</span>';
            }
            if (window.lucide) { window.lucide.createIcons(); }
            
            const heroCounter = document.getElementById('bizHeroFollowersCount');
            if (heroCounter && data.followers_count !== undefined) {
                heroCounter.innerText = data.followers_count;
            }
        } else {
            alert(data.message || 'Action failed.');
        }
    });
}

function selectStarRating(val) {
    document.getElementById('reviewRatingInput').value = val;
    const stars = document.querySelectorAll('#starRatingPicker i');
    stars.forEach((s, idx) => {
        if (idx < val) {
            s.setAttribute('fill', '#f7b940');
        } else {
            s.setAttribute('fill', 'none');
        }
    });
}

function openWriteReviewModal(isEdit) {
    const modal = document.getElementById('bizWriteReviewModal');
    const form = document.getElementById('bizReviewForm');
    const methodInput = document.getElementById('reviewFormMethod');
    const title = document.getElementById('writeReviewTitle');

    if (isEdit && @json($userReview ? true : false)) {
        title.innerHTML = '<i data-lucide="edit-3" style="color: #f7b940;"></i> Edit Your Business Review';
        form.action = '{{ url("member/business-pages/" . $businessPage->slug . "/reviews") }}/' + @json($userReview->id ?? 0);
        methodInput.value = 'PUT';
        selectStarRating(@json($userReview->rating ?? 5));
        document.getElementById('reviewTitleInput').value = @json($userReview->title ?? '');
        document.getElementById('reviewBodyInput').value = @json($userReview->body ?? '');
    } else {
        title.innerHTML = '<i data-lucide="star" style="color: #f7b940;"></i> Write a Business Review';
        form.action = '{{ route("member.business-pages.reviews.store", $businessPage) }}';
        methodInput.value = 'POST';
    }
    modal.removeAttribute('hidden');
}

function closeWriteReviewModal() {
    document.getElementById('bizWriteReviewModal').setAttribute('hidden', 'true');
}

document.getElementById('bizReviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('submitReviewBtn');
    btn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            alert(data.message);
            closeWriteReviewModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to submit review.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        alert('An error occurred while submitting your review.');
    });
});

function openOfficialReplyModal(reviewId, existingReply) {
    const form = document.getElementById('bizOfficialReplyForm');
    form.action = '{{ url("member/business-pages/" . $businessPage->slug . "/reviews") }}/' + reviewId + '/reply';
    document.getElementById('officialReplyInput').value = existingReply || '';
    document.getElementById('bizOfficialReplyModal').removeAttribute('hidden');
}
function closeOfficialReplyModal() {
    document.getElementById('bizOfficialReplyModal').setAttribute('hidden', 'true');
}

document.getElementById('bizOfficialReplyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeOfficialReplyModal();
            location.reload();
        } else {
            alert(data.message);
        }
    });
});

function voteBizReview(reviewId, type) {
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/reviews") }}/' + reviewId + '/vote';
    fetch(url, {
        method: 'POST',
        body: JSON.stringify({ vote_type: type }),
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const h = document.getElementById(`helpfulCount-${reviewId}`);
            const u = document.getElementById(`unhelpfulCount-${reviewId}`);
            if (h) h.innerText = data.helpful_count;
            if (u) u.innerText = data.unhelpful_count;
        } else {
            alert(data.message);
        }
    });
}

function openReportReviewModal(reviewId) {
    const form = document.getElementById('bizReportReviewForm');
    form.action = '{{ url("member/business-pages/" . $businessPage->slug . "/reviews") }}/' + reviewId + '/report';
    document.getElementById('bizReportReviewModal').removeAttribute('hidden');
}
function closeReportReviewModal() {
    document.getElementById('bizReportReviewModal').setAttribute('hidden', 'true');
}

document.getElementById('bizReportReviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        closeReportReviewModal();
    });
});

function toggleHideBizReview(reviewId) {
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/reviews") }}/' + reviewId + '/hide';
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) { location.reload(); }
    });
}

function deleteBizReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review?')) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/reviews") }}/' + reviewId;
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById(`reviewCard-${reviewId}`);
            if (card) card.remove();
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function openCustomerMessageModal() {
    const modal = document.getElementById('bizCustomerMessageModal');
    if (modal) {
        modal.removeAttribute('hidden');
        modal.style.display = 'flex';
    }
}
function closeCustomerMessageModal() {
    const modal = document.getElementById('bizCustomerMessageModal');
    if (modal) {
        modal.setAttribute('hidden', 'true');
        modal.style.display = 'none';
        const form = document.getElementById('bizCustomerMessageForm');
        if (form) form.reset();
    }
}

// ESC Key & Overlay Click Listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCustomerMessageModal();
        if (typeof closeBizShareModal === 'function') closeBizShareModal();
        if (typeof closeWriteReviewModal === 'function') closeWriteReviewModal();
        if (typeof closeOfficialReplyModal === 'function') closeOfficialReplyModal();
        if (typeof closeReportReviewModal === 'function') closeReportReviewModal();
    }
});

document.getElementById('bizCustomerMessageModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomerMessageModal();
    }
});

document.getElementById('bizCustomerMessageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        alert(data.message);
        if (data.success) {
            closeCustomerMessageModal();
        }
    })
    .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        alert('An error occurred while sending your message. Please try again.');
    });
});
</script>

<!-- Start Customer Message Modal -->
<div class="biz-modal-overlay" id="bizCustomerMessageModal" hidden style="display: none;">
    <div class="biz-modal" role="dialog" aria-labelledby="startMessageTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="startMessageTitle"><i data-lucide="message-square" style="color: #4f7df3;"></i> Message {{ $businessPage->page_name }}</h3>
            <button type="button" class="icon-button" onclick="closeCustomerMessageModal()" aria-label="Close modal"><i data-lucide="x"></i></button>
        </div>
        <form id="bizCustomerMessageForm" method="POST" action="{{ route('member.business-pages.inbox.start', $businessPage) }}">
            @csrf
            <div class="biz-modal__body">
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Your Inquiry / Message</label>
                    <textarea name="message" rows="4" class="biz-search-input" style="height: auto; padding: 10px;" placeholder="Ask about products, services, operating hours, or general inquiries..." required maxlength="3000"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeCustomerMessageModal()">Cancel</button>
                    <button type="submit" class="member-button member-button--primary">Send Message</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
