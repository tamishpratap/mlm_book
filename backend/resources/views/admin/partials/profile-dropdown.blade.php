<!-- Profile Dropdown Partial -->
<li class="nav-item profile-nav">
    <div class="profile-media" id="profileDropdownToggle">
        <img src="{{ Auth::guard('admin')->user()?->profile_photo ? asset(Auth::guard('admin')->user()->profile_photo) : asset('admin_assets/images/avtar/7.jpg') }}" alt="Admin Avatar">
        <div class="profile-info d-none d-sm-block">
            <span class="profile-name">{{ Auth::guard('admin')->user()->name ?? 'Administrator' }}</span>
            <span class="profile-role">Admin <i data-feather="chevron-down" style="width:12px; height:12px;"></i></span>
        </div>
    </div>
    <ul class="profile-dropdown" id="profileDropdown">
        <li>
            <a href="{{ route('admin.settings.index') }}">
                <i data-feather="settings"></i><span>Settings</span>
            </a>
        </li>
        <li>
            <hr class="dropdown-divider my-1">
        </li>
        <li>
            <a href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();" class="text-danger">
                <i data-feather="log-out"></i><span>Log out</span>
            </a>
            <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</li>
