<div class="profile-menu-wrap" data-profile-menu>
    <button
        class="profile-button profile-trigger"
        type="button"
        aria-label="Open profile menu"
        aria-expanded="false"
        aria-controls="member-profile-dropdown"
        data-profile-trigger
    >
        <span class="profile-trigger__avatar">
            @if ($member->profile_photo_url)
                <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}">
            @else
                <span class="avatar post-avatar-initials" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #176bff, #7146ed); color: #fff; font-weight: 700; font-size: 0.85rem; border-radius: 50%;">{{ $member->initials }}</span>
            @endif
            <span class="online-dot" aria-hidden="true"></span>
        </span>
        <span class="profile-trigger__name">{{ $member->name }}</span>
        <i class="profile-trigger__chevron" data-lucide="chevron-down" aria-hidden="true"></i>
    </button>

    <div class="profile-dropdown" id="member-profile-dropdown" role="menu" aria-label="Member profile menu" data-profile-dropdown hidden>
        <div class="profile-dropdown__header">
            <a class="profile-dropdown__summary" href="{{ route('member.profile.show') }}" role="menuitem">
                @if ($member->profile_photo_url)
                    <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}">
                @else
                    <span class="avatar post-avatar-initials" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #176bff, #7146ed); color: #fff; font-weight: 700; font-size: 0.95rem; border-radius: 50%;">{{ $member->initials }}</span>
                @endif
                <span>
                    <strong>{{ $member->name }}</strong>
                    <small>{{ $member->email }}</small>
                    <em>View your profile</em>
                </span>
            </a>
        </div>

        <div class="profile-dropdown__divider"></div>

        <div class="profile-dropdown__menu-scroll">
            <a class="profile-dropdown__item" href="{{ route('member.profile.show') }}" role="menuitem">
                <span><i data-lucide="user-round"></i></span>
                <strong>View Profile</strong>
                <i data-lucide="chevron-right"></i>
            </a>
            <a class="profile-dropdown__item" href="{{ route('member.profile.edit') }}" role="menuitem">
                <span><i data-lucide="pencil"></i></span>
                <strong>Edit Profile</strong>
                <i data-lucide="chevron-right"></i>
            </a>
            <a class="profile-dropdown__item" href="{{ route('member.friend-requests.index') }}" role="menuitem">
                <span><i data-lucide="user-round-check"></i></span>
                <strong>Friend Requests</strong>
                <i data-lucide="chevron-right"></i>
            </a>
            <a class="profile-dropdown__item" href="{{ route('member.account.settings') }}" role="menuitem">
                <span><i data-lucide="settings"></i></span>
                <strong>Account Settings</strong>
                <i data-lucide="chevron-right"></i>
            </a>
            <a class="profile-dropdown__item" href="{{ route('member.account.security') }}" role="menuitem">
                <span><i data-lucide="shield-check"></i></span>
                <strong>Password &amp; Security</strong>
                <i data-lucide="chevron-right"></i>
            </a>
        </div>

        <div class="profile-dropdown__divider"></div>

        <div class="profile-dropdown__footer">
            <form method="POST" action="{{ route('member.logout') }}">
                @csrf
                <button class="profile-dropdown__item profile-dropdown__item--logout" type="submit" role="menuitem">
                    <span><i data-lucide="log-out"></i></span>
                    <strong>Logout</strong>
                </button>
            </form>
        </div>
    </div>
</div>
