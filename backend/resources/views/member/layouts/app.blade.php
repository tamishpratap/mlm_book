<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f8fc">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Member Panel') | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}">
    <base href="{{ asset('member_assets/images/dashboard') }}/">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('member_assets/css/dashboard.css') }}?v={{ file_exists(public_path('member_assets/css/dashboard.css')) ? filemtime(public_path('member_assets/css/dashboard.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-profile.css') }}?v={{ file_exists(public_path('member_assets/css/member-profile.css')) ? filemtime(public_path('member_assets/css/member-profile.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-search.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-friends.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-posts.css') }}?v={{ file_exists(public_path('member_assets/css/member-posts.css')) ? filemtime(public_path('member_assets/css/member-posts.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-notifications.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-stories.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-marketplace.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-groups.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-community.css') }}?v={{ file_exists(public_path('member_assets/css/member-community.css')) ? filemtime(public_path('member_assets/css/member-community.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-business-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-events.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-people.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-account.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-blocked-users.css') }}">
    @stack('styles')
</head>
<body class="@yield('body-class')">
    @php
        $currentMember = auth('member')->user();
        $currentAvatar = $currentMember ? $currentMember->avatar_url : asset('member_assets/images/dashboard/default-avatar.png');
        $headerSearch = is_string(request('q')) ? request('q') : '';
        $unreadNotificationCount = $currentMember ? $currentMember->unreadNotifications()->count() : 0;
    @endphp

    <header class="topbar">
        <div class="topbar__inner">
            <div class="topbar__brand-area">
                <button class="icon-button mobile-menu" type="button" aria-label="Open navigation" data-menu-toggle>
                    <i data-lucide="menu"></i>
                </button>
                <a class="brand" href="{{ route('member.dashboard') }}" aria-label="MLM Book Member Dashboard">
                    <img class="mlm-book-logo mlm-book-header-logo" src="{{ asset('logo/logo.png') }}" alt="MLM Book">
                </a>
                <form
                    class="search-box"
                    id="memberHeaderSearchForm"
                    method="GET"
                    action="{{ route('member.search') }}"
                    role="search"
                    data-member-search
                    data-header-search
                    data-search-url="{{ route('member.search') }}"
                    data-search-page="{{ request()->routeIs('member.search') ? 'true' : 'false' }}"
                >
                    <input type="hidden" name="type" value="all">
                    <button class="search-box__submit" type="submit" aria-label="Search Members">
                        <i data-lucide="search" aria-hidden="true"></i>
                    </button>
                    <input
                        type="search"
                        name="q"
                        id="memberHeaderSearchInput"
                        value="{{ $headerSearch }}"
                        placeholder="Search members, pages, communities..."
                        aria-label="Search MLM Book"
                        maxlength="100"
                        autocomplete="off"
                        data-member-search-input
                        data-header-search-input
                    >
                    <button class="search-box__clear" type="button" aria-label="Clear search" data-member-search-clear @if ($headerSearch === '') hidden @endif>
                        <i data-lucide="x" aria-hidden="true"></i>
                    </button>
                </form>
            </div>

            <nav class="top-nav" aria-label="Primary navigation">
                <a class="top-nav__item {{ request()->routeIs('member.dashboard') ? 'is-active' : '' }}" href="{{ route('member.dashboard') }}" @if(request()->routeIs('member.dashboard')) aria-current="page" @endif>
                    <i data-lucide="house"></i><span>Home</span>
                </a>
                <a class="top-nav__item {{ request()->routeIs('member.socials*') ? 'is-active' : '' }}" href="{{ route('member.socials') }}" @if(request()->routeIs('member.socials*')) aria-current="page" @endif>
                    <i data-lucide="sparkles"></i><span>Socials</span>
                </a>
                <a class="top-nav__item {{ request()->routeIs('member.watch*') ? 'is-active' : '' }}" href="{{ route('member.watch.index') }}" @if(request()->routeIs('member.watch*')) aria-current="page" @endif>
                    <i data-lucide="monitor-play"></i><span>Watch</span>
                </a>
                <!--
                <a class="top-nav__item {{ request()->routeIs('member.marketplace*') ? 'is-active' : '' }}" href="{{ route('member.marketplace.index') }}" @if(request()->routeIs('member.marketplace*')) aria-current="page" @endif>
                    <i data-lucide="store"></i><span>Marketplace</span>
                </a>
                -->
                <a class="top-nav__item {{ request()->routeIs('member.community.*') ? 'is-active' : '' }}" href="{{ route('member.community.index') }}">
                    <i data-lucide="users"></i><span>Community</span>
                </a>
                <a class="top-nav__item {{ request()->routeIs('member.business-pages.*') && !request()->routeIs('member.business-pages.directory.*') ? 'is-active' : '' }}" href="{{ route('member.business-pages.index') }}">
                    <i data-lucide="building-2"></i><span>Business Pages</span>
                </a>
                <a class="top-nav__item {{ request()->routeIs('member.business-pages.directory.*') ? 'is-active' : '' }}" href="{{ route('member.business-pages.directory.index') }}">
                    <i data-lucide="compass"></i><span>Business Directory</span>
                </a>
                <a class="top-nav__item {{ request()->routeIs('member.events.*') ? 'is-active' : '' }}" href="{{ route('member.events.index') }}" @if(request()->routeIs('member.events.*')) aria-current="page" @endif>
                    <i data-lucide="calendar-days"></i><span>Events</span>
                </a>
            </nav>

            <div class="topbar__actions">
                <div
                    class="notification-menu"
                    data-notification-menu
                    data-dropdown-url="{{ route('member.notifications.dropdown') }}"
                    data-poll-url="{{ route('member.notifications.poll') }}"
                >
                    <button
                        class="icon-button has-badge"
                        type="button"
                        aria-label="Notifications"
                        aria-expanded="false"
                        aria-controls="member-notification-dropdown"
                        data-notification-trigger
                    >
                        <i data-lucide="bell" aria-hidden="true"></i>
                        <span
                            class="notification-badge"
                            aria-live="polite"
                            data-notification-badge
                            @if ($unreadNotificationCount === 0) hidden @endif
                        >{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                    </button>
                    <div
                        class="notification-dropdown"
                        id="member-notification-dropdown"
                        role="dialog"
                        aria-label="Notifications"
                        data-notification-dropdown
                        hidden
                    >
                        <div class="notification-dropdown__loading" data-notification-content aria-live="polite">
                            <i data-lucide="loader-circle" aria-hidden="true"></i>
                            <span>Loading notifications…</span>
                        </div>
                    </div>
                </div>
                @if ($currentMember)
                    @include('member.partials.profile-dropdown', [
                        'member' => $currentMember,
                        'avatarUrl' => $currentAvatar,
                    ])
                @else
                    <a href="{{ route('member.login') }}" class="member-button member-button--primary" style="padding: 6px 14px; text-decoration: none; font-size: 0.875rem; border-radius: 6px; font-weight: 600;">Sign In</a>
                @endif
            </div>
        </div>
    </header>

    <div class="page-shell @hasSection('right-sidebar') @else page-shell--single @endif">
        @if ($currentMember)
        <aside class="left-sidebar" data-sidebar>
            <section class="sidebar-card">
                <a class="sidebar-profile" href="{{ route('member.profile.show') }}">
                    @if ($currentMember->profile_photo_url)
                        <img class="avatar avatar--lg" src="{{ $currentMember->profile_photo_url }}" alt="{{ $currentMember->name }}">
                    @else
                        <span class="avatar avatar--lg post-avatar-initials" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #176bff, #7146ed); color: #fff; font-weight: 700; font-size: 1.1rem; border-radius: 50%;">{{ $currentMember->initials }}</span>
                    @endif
                    <div class="sidebar-profile__copy">
                        <strong>{{ $currentMember->name }}</strong>
                        <span>View profile</span>
                    </div>
                    <i class="sidebar-profile__arrow" data-lucide="chevron-right"></i>
                </a>

                <nav class="side-nav" aria-label="Sidebar navigation">
                    <a class="side-nav__item {{ request()->routeIs('member.dashboard') ? 'is-active' : '' }}" href="{{ route('member.dashboard') }}"><i data-lucide="house"></i><span>Home</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.socials*') ? 'is-active' : '' }}" href="{{ route('member.socials') }}"><i data-lucide="sparkles"></i><span>Socials</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.profile.show') ? 'is-active' : '' }}" href="{{ route('member.profile.show') }}"><i data-lucide="user-round"></i><span>My Profile</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.friends.*') ? 'is-active' : '' }}" href="{{ route('member.friends.index') }}"><i data-lucide="users-round"></i><span>Connections</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.people.suggestions') ? 'is-active' : '' }}" href="{{ route('member.people.suggestions') }}"><i data-lucide="sparkles"></i><span>New Connections</span></a>
                    <!--<a class="side-nav__item {{ request()->routeIs('member.profile.visitors') ? 'is-active' : '' }}" href="{{ route('member.profile.visitors') }}"><i data-lucide="eye"></i><span>Profile Visitors</span></a>-->
                    <a class="side-nav__item {{ request()->routeIs('member.posts.saved') ? 'is-active' : '' }}" href="{{ route('member.posts.saved') }}"><i data-lucide="bookmark"></i><span>Saved Posts</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.blocked-users.*') ? 'is-active' : '' }}" href="{{ route('member.blocked-users.index') }}"><i data-lucide="user-x"></i><span>Disconnections</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.watch*') ? 'is-active' : '' }}" href="{{ route('member.watch.index') }}"><i data-lucide="monitor-play"></i><span>Watch</span></a>
                    <!--<a class="side-nav__item {{ request()->routeIs('member.marketplace.*') ? 'is-active' : '' }}" href="{{ route('member.marketplace.index') }}"><i data-lucide="store"></i><span>Marketplace</span></a>-->
                    <a class="side-nav__item {{ request()->routeIs('member.community.*') ? 'is-active' : '' }}" href="{{ route('member.community.index') }}"><i data-lucide="users-round"></i><span>Community</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.business-pages.*') && !request()->routeIs('member.business-pages.directory.*') ? 'is-active' : '' }}" href="{{ route('member.business-pages.index') }}"><i data-lucide="building-2"></i><span>Business Pages</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.business-pages.directory.*') ? 'is-active' : '' }}" href="{{ route('member.business-pages.directory.index') }}"><i data-lucide="compass"></i><span>Business Directory</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.events.*') ? 'is-active' : '' }}" href="{{ route('member.events.index') }}"><i data-lucide="calendar"></i><span>Events</span></a>
                    <a class="side-nav__item {{ request()->routeIs('member.account.*') ? 'is-active' : '' }}" href="{{ route('member.account.settings') }}"><i data-lucide="settings"></i><span>Account Settings</span></a>
                </nav>

                <div class="sidebar-divider"></div>
                <div class="section-heading section-heading--compact">
                    <h2>Shortcuts</h2>
                </div>
                <div class="shortcut-list">
                    @php
                        $joinedCommunities = \App\Models\CommunityMember::query()
                            ->with('community')
                            ->where('member_id', $currentMember->id)
                            ->latest()
                            ->take(3)
                            ->get();

                        $myBusinessPages = \App\Models\BusinessPage::query()
                            ->where('member_id', $currentMember->id)
                            ->latest()
                            ->take(3)
                            ->get();

                        $savedPostsCount = \App\Models\SavedPost::query()
                            ->where('member_id', $currentMember->id)
                            ->count();

                        $hasShortcuts = false;
                    @endphp

                    @foreach ($joinedCommunities as $cMem)
                        @if ($cMem->community)
                            @php $hasShortcuts = true; @endphp
                            <a class="shortcut" href="{{ route('member.community.show', $cMem->community) }}">
                                @if ($cMem->community->logo && file_exists(public_path($cMem->community->logo)))
                                    <img src="{{ asset($cMem->community->logo) }}" alt="{{ $cMem->community->name }}">
                                @else
                                    <span class="shortcut-icon-badge"><i data-lucide="users-round" aria-hidden="true"></i></span>
                                @endif
                                <span>{{ $cMem->community->name }}</span>
                            </a>
                        @endif
                    @endforeach

                    @foreach ($myBusinessPages as $bPage)
                        @php $hasShortcuts = true; @endphp
                        <a class="shortcut" href="{{ route('member.business-pages.show', $bPage) }}">
                            @if ($bPage->logo_url)
                                <img src="{{ $bPage->logo_url }}" alt="{{ $bPage->page_name }}">
                            @else
                                <span class="shortcut-icon-badge"><i data-lucide="building-2" aria-hidden="true"></i></span>
                            @endif
                            <span>{{ $bPage->page_name }}</span>
                        </a>
                    @endforeach

                    @if ($savedPostsCount > 0)
                        @php $hasShortcuts = true; @endphp
                        <a class="shortcut" href="{{ route('member.posts.saved') }}">
                            <span class="shortcut-icon-badge"><i data-lucide="bookmark" aria-hidden="true"></i></span>
                            <span>Saved Posts ({{ $savedPostsCount }})</span>
                        </a>
                    @endif

                    @if (! $hasShortcuts)
                        <div style="padding: 6px 10px;">
                            <small style="color: var(--color-text-muted, #64748b); font-size: 0.775rem;">No shortcuts yet. Join communities or follow pages to see shortcuts here.</small>
                        </div>
                    @endif
                </div>
            </section>
        </aside>
        @endif

        <main class="@yield('main-class', 'member-main')" id="@yield('main-id', 'member-content')">
            @include('member.partials.flash-messages')
            @yield('content')
        </main>

        @hasSection('right-sidebar')
            <aside class="right-sidebar">
                @yield('right-sidebar')
            </aside>
        @endif

        <div class="sidebar-scrim" data-sidebar-scrim></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script src="{{ asset('member_assets/js/member-profile.js') }}"></script>
    <script src="{{ asset('member_assets/js/member-search.js') }}"></script>
    <script src="{{ asset('member_assets/js/member-friends.js') }}"></script>
    <script src="{{ asset('member_assets/js/member-posts.js') }}?v={{ file_exists(public_path('member_assets/js/member-posts.js')) ? filemtime(public_path('member_assets/js/member-posts.js')) : time() }}"></script>
    <script src="{{ asset('member_assets/js/member-notifications.js') }}"></script>
    <script src="{{ asset('member_assets/js/member-stories.js') }}"></script>
    <script src="{{ asset('member_assets/js/member-community.js') }}?v={{ file_exists(public_path('member_assets/js/member-community.js')) ? filemtime(public_path('member_assets/js/member-community.js')) : time() }}"></script>
    <script src="{{ asset('member_assets/js/member-watch.js') }}"></script>
    @stack('scripts')
    @stack('modals')
</body>
</html>
