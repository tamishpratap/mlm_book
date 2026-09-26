<!-- Admin Sidebar Navigation Partial -->
<aside class="sidebar-wrapper" id="adminSidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand-wrapper">
        <a href="{{ url('admin/dashboard') }}" class="sidebar-brand">
            <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" class="sidebar-logo">
            <div class="sidebar-brand-text">
                <span class="brand-title">MLM Book</span>
                <span class="brand-subtitle">Admin Panel</span>
            </div>
        </a>
        <button class="sidebar-close-btn d-lg-none" id="sidebarClose" type="button" aria-label="Close Sidebar">
            <i data-feather="x"></i>
        </button>
    </div>

    <!-- Navigation List -->
    <nav class="sidebar-main">
        <div id="sidebar-menu">
            <ul class="sidebar-links">
                <li class="sidebar-list">
                    <a class="sidebar-link {{ request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ url('admin/dashboard') }}">
                        <i data-feather="home" class="stroke-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @php
                    $sidebarPendingDeposits = \App\Models\ImportFund::whereIn('deposit_status', ['pending', 'verified'])
                        ->orWhere(function($q) { $q->whereNull('deposit_status')->where('status', 'Pending'); })
                        ->count();
                @endphp
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/deposits*') ? 'active' : '' }}" href="#">
                        <i data-feather="dollar-sign" class="stroke-icon text-success"></i>
                        <span>Deposit Requests</span>
                        @if($sidebarPendingDeposits > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-auto me-2" style="font-size: 10px; font-weight: 700; padding: 2px 7px;">{{ $sidebarPendingDeposits }}</span>
                        @endif
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.deposits.index') }}" class="{{ request()->routeIs('admin.deposits.index') && request('status', 'all') === 'all' ? 'active' : '' }}">All Deposits</a></li>
                        <li><a href="{{ route('admin.deposits.index', ['status' => 'pending']) }}" class="{{ request('status') === 'pending' ? 'active' : '' }}">Pending Requests @if($sidebarPendingDeposits > 0) <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $sidebarPendingDeposits }}</span> @endif</a></li>
                        <li><a href="{{ route('admin.deposits.index', ['status' => 'approved']) }}" class="{{ request('status') === 'approved' ? 'active' : '' }}">Approved History</a></li>
                        <li><a href="{{ route('admin.deposits.settings') }}" class="{{ request()->routeIs('admin.deposits.settings') ? 'active' : '' }}">Deposit Settings</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/members*') ? 'active' : '' }}" href="#">
                        <i data-feather="users" class="stroke-icon"></i>
                        <span>Members Management</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.members.index') }}" class="{{ request()->routeIs('admin.members.index') || request()->routeIs('admin.members.active') ? 'active' : '' }}">Verified Members</a></li>
                        <li><a href="{{ route('admin.members.pending') }}" class="{{ request()->routeIs('admin.members.pending') ? 'active' : '' }}">Unverified Members</a></li>
                        <li><a href="{{ route('admin.members.blocked') }}" class="{{ request()->routeIs('admin.members.blocked') ? 'active' : '' }}">Blocked Members</a></li>
                        <li><a href="{{ route('admin.members.security') }}" class="{{ request()->routeIs('admin.members.security') ? 'active' : '' }}">Security</a></li>
                        <li><a href="{{ route('admin.members.wallet-address') }}" class="{{ request()->routeIs('admin.members.wallet-address') ? 'active' : '' }}">Wallet Address</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/posts*') ? 'active' : '' }}" href="#">
                        <i data-feather="file-text" class="stroke-icon"></i>
                        <span>Posts & Moderation</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.posts.index') }}" class="{{ request()->routeIs('admin.posts.index') && !request('has_reports') ? 'active' : '' }}">All Posts</a></li>
                        <li><a href="{{ route('admin.posts.index', ['has_reports' => 'yes']) }}" class="{{ request('has_reports') === 'yes' ? 'active' : '' }}">Reported Queue</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/stories*') ? 'active' : '' }}" href="#">
                        <i data-feather="clock" class="stroke-icon"></i>
                        <span>Stories Management</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.stories.index') }}" class="{{ request()->routeIs('admin.stories.index') && !request('status') ? 'active' : '' }}">All Stories</a></li>
                        <li><a href="{{ route('admin.stories.index', ['status' => 'active']) }}" class="{{ request('status') === 'active' ? 'active' : '' }}">Live Stories</a></li>
                        <li><a href="{{ route('admin.stories.index', ['status' => 'expired']) }}" class="{{ request('status') === 'expired' ? 'active' : '' }}">Expired Stories</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/business-pages*') ? 'active' : '' }}" href="#">
                        <i data-feather="briefcase" class="stroke-icon"></i>
                        <span>Business Pages</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.business-pages.index') }}" class="{{ request()->routeIs('admin.business-pages.index') && !request('verification_status') ? 'active' : '' }}">Business Directory</a></li>
                        <li><a href="{{ route('admin.business-pages.index', ['verification_status' => 'pending']) }}" class="{{ request('verification_status') === 'pending' ? 'active' : '' }}">Verification Queue</a></li>
                        <li><a href="{{ route('admin.business-pages.categories.index') }}" class="{{ request()->routeIs('admin.business-pages.categories.*') ? 'active' : '' }}">Categories</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/communities*') ? 'active' : '' }}" href="#">
                        <i data-feather="globe" class="stroke-icon"></i>
                        <span>Communities</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.communities.index') }}" class="{{ request()->routeIs('admin.communities.index') && !request('visibility') ? 'active' : '' }}">All Communities</a></li>
                        <li><a href="{{ route('admin.communities.index', ['visibility' => 'private']) }}" class="{{ request('visibility') === 'private' ? 'active' : '' }}">Private Communities</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/marketplace*') ? 'active' : '' }}" href="#">
                        <i data-feather="shopping-bag" class="stroke-icon"></i>
                        <span>Marketplace</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.marketplace.index') }}" class="{{ request()->routeIs('admin.marketplace.index') && !request('is_featured') ? 'active' : '' }}">All Products</a></li>
                        <li><a href="{{ route('admin.marketplace.index', ['is_featured' => 'yes']) }}" class="{{ request('is_featured') === 'yes' ? 'active' : '' }}">Featured Products</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/events*') ? 'active' : '' }}" href="#">
                        <i data-feather="calendar" class="stroke-icon"></i>
                        <span>Events Management</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.events.index') }}" class="{{ request()->routeIs('admin.events.index') && !request('timeframe') ? 'active' : '' }}">All Events</a></li>
                        <li><a href="{{ route('admin.events.index', ['timeframe' => 'upcoming']) }}" class="{{ request('timeframe') === 'upcoming' ? 'active' : '' }}">Upcoming Events</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/reports*') ? 'active' : '' }}" href="#">
                        <i data-feather="alert-circle" class="stroke-icon"></i>
                        <span>Moderation Center</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.index') && !request('status') ? 'active' : '' }}">All Reports Queue</a></li>
                        <li><a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="{{ request('status') === 'pending' ? 'active' : '' }}">Pending Reports</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/settings*') ? 'active' : '' }}" href="#">
                        <i data-feather="settings" class="stroke-icon"></i>
                        <span>System Settings</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.settings.index') }}">Platform Settings</a></li>
                        <li><a href="{{ route('admin.settings.index') }}#cache-pane">Cache & Maintenance</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/roles*') ? 'active' : '' }}" href="#">
                        <i data-feather="shield" class="stroke-icon"></i>
                        <span>Roles & Permissions</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.roles.index') }}">All Roles Directory</a></li>
                        <li><a href="{{ route('admin.roles.matrix') }}">Permission Matrix</a></li>
                        <li><a href="{{ route('admin.roles.create') }}">Create Custom Role</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/notifications*') ? 'active' : '' }}" href="#">
                        <i data-feather="bell" class="stroke-icon"></i>
                        <span>Notifications Center</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.notifications.index') }}">Notification Queue</a></li>
                        <li><a href="{{ route('admin.notifications.broadcast') }}">Send Broadcast</a></li>
                    </ul>
                </li>
                {{--
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/analytics*') ? 'active' : '' }}" href="#">
                        <i data-feather="bar-chart-2" class="stroke-icon"></i>
                        <span>Analytics Dashboard</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.analytics.index') }}">Executive BI Overview</a></li>
                        <li><a href="{{ route('admin.analytics.export') }}">Export BI Metrics</a></li>
                    </ul>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link sidebar-title {{ request()->is('admin/system*') ? 'active' : '' }}" href="#">
                        <i data-feather="cpu" class="stroke-icon"></i>
                        <span>System Tools</span>
                        <span class="submenu-arrow"><i data-feather="chevron-right"></i></span>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="{{ route('admin.system.index') }}">System Health & Tools</a></li>
                        <li><a href="{{ route('admin.system.logs') }}">Laravel Log Viewer</a></li>
                    </ul>
                </li>
                --}}
            </ul>
        </div>
    </nav>
</aside>
