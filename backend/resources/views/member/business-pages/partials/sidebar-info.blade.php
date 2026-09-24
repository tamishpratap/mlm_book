<div class="biz-sidebar-card">
    <div class="biz-sidebar-card__header">
        <i data-lucide="building-2" style="color: #4f7df3; width: 20px; height: 20px;"></i>
        <span>About {{ $businessPage->page_name }}</span>
    </div>

    <div class="biz-sidebar-list">
        <!-- Category -->
        <div class="biz-sidebar-item">
            <div class="biz-sidebar-item__icon">
                <i data-lucide="tag" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="biz-sidebar-item__content">
                <label>Category</label>
                <span>{{ $businessPage->category }}</span>
            </div>
        </div>

        <!-- Audience / Followers -->
        <div class="biz-sidebar-item">
            <div class="biz-sidebar-item__icon" style="background: rgba(32, 200, 117, 0.1); color: #20c875;">
                <i data-lucide="users" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="biz-sidebar-item__content">
                <label>Audience</label>
                <span>{{ number_format($businessPage->followersCount()) }} followers</span>
            </div>
        </div>

        <!-- Rating & Feedback -->
        <div class="biz-sidebar-item">
            <div class="biz-sidebar-item__icon" style="background: rgba(247, 185, 64, 0.15); color: #f7b940;">
                <i data-lucide="star" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="biz-sidebar-item__content">
                <label>Rating & Feedback</label>
                <span>{{ $businessPage->averageRating() }} ★ ({{ $businessPage->reviewsCount() }} {{ \Illuminate\Support\Str::plural('review', $businessPage->reviewsCount()) }})</span>
            </div>
        </div>

        <!-- Website -->
        @if ($businessPage->website)
            <div class="biz-sidebar-item">
                <div class="biz-sidebar-item__icon">
                    <i data-lucide="globe" style="width: 16px; height: 16px;"></i>
                </div>
                <div class="biz-sidebar-item__content">
                    <label>Website</label>
                    <a href="{{ $businessPage->website }}" target="_blank" rel="noopener noreferrer">
                        {{ parse_url($businessPage->website, PHP_URL_HOST) ?? $businessPage->website }}
                    </a>
                </div>
            </div>
        @endif

        <!-- Email -->
        @if ($businessPage->email)
            <div class="biz-sidebar-item">
                <div class="biz-sidebar-item__icon" style="background: rgba(138, 43, 226, 0.1); color: #8a2be2;">
                    <i data-lucide="mail" style="width: 16px; height: 16px;"></i>
                </div>
                <div class="biz-sidebar-item__content">
                    <label>Email Address</label>
                    <a href="mailto:{{ $businessPage->email }}">{{ $businessPage->email }}</a>
                </div>
            </div>
        @endif

        <!-- Phone -->
        @if ($businessPage->phone)
            <div class="biz-sidebar-item">
                <div class="biz-sidebar-item__icon" style="background: rgba(32, 200, 117, 0.1); color: #20c875;">
                    <i data-lucide="phone" style="width: 16px; height: 16px;"></i>
                </div>
                <div class="biz-sidebar-item__content">
                    <label>Phone Number</label>
                    <a href="tel:{{ $businessPage->phone }}">{{ $businessPage->phone }}</a>
                </div>
            </div>
        @endif

        <!-- Location -->
        @if ($businessPage->formatted_location)
            <div class="biz-sidebar-item">
                <div class="biz-sidebar-item__icon" style="background: rgba(229, 62, 62, 0.1); color: #e53e3e;">
                    <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i>
                </div>
                <div class="biz-sidebar-item__content">
                    <label>Location</label>
                    <span>{{ $businessPage->formatted_location }}</span>
                </div>
            </div>
        @endif

        <!-- Status & Visibility -->
        <div class="biz-sidebar-item">
            <div class="biz-sidebar-item__icon">
                <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="biz-sidebar-item__content">
                <label>Status & Verification</label>
                <div style="display: flex; gap: 6px; align-items: center; margin-top: 2px; flex-wrap: wrap;">
                    <span class="biz-badge biz-badge--status">Active</span>
                    <span class="biz-badge biz-badge--visibility">
                        {{ ucfirst($businessPage->visibility) }}
                    </span>
                    @if ($businessPage->is_verified)
                        <span class="biz-badge biz-badge--verified">Verified</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Created Date -->
        <div class="biz-sidebar-item">
            <div class="biz-sidebar-item__icon" style="background: rgba(104, 115, 134, 0.1); color: #687386;">
                <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="biz-sidebar-item__content">
                <label>Page Created</label>
                <span>{{ $businessPage->created_at->format('F d, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Page Owner Block -->
    <div style="border-top: 1px solid #e7ecf4; padding-top: 14px; margin-top: 4px;">
        <label style="font-size: 11px; font-weight: 700; color: #98a2b3; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Page Owner</label>
        @if ($businessPage->owner)
            <a href="{{ route('member.people.show', $businessPage->owner) }}" class="biz-sidebar-owner">
                <img src="{{ $businessPage->owner->profile_photo ? asset($businessPage->owner->profile_photo) : asset('member_assets/images/dashboard/image/profile.png') }}" alt="{{ $businessPage->owner->name }}" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover;">
                <div>
                    <strong style="font-size: 13.5px; color: #1d2738; display: block; line-height: 1.2;">{{ $businessPage->owner->name }}</strong>
                    <span style="font-size: 11.5px; color: #98a2b3;">View Member Profile</span>
                </div>
            </a>
        @else
            <span style="font-size: 13px; color: #687386;">System Administrator</span>
        @endif
    </div>
</div>
