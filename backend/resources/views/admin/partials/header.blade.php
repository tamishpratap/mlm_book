<!-- Top Header Bar Partial -->
<header class="page-header">
    <div class="header-wrapper">
        <!-- Left Section: Toggle, Mobile Brand, Search -->
        <div class="header-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
                <i data-feather="menu"></i>
            </button>

            <a href="{{ url('admin/dashboard') }}" class="header-mobile-brand d-lg-none">
                <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" class="header-brand-logo">
            </a>

            <div class="header-search-container d-none d-md-block">
                @include('admin.partials.search-bar')
            </div>
        </div>

        <!-- Right Section: Notifications, Admin Profile -->
        <div class="header-right">
            <ul class="nav-menus">
                @include('admin.partials.notifications')
                @include('admin.partials.profile-dropdown')
            </ul>
        </div>
    </div>
</header>
