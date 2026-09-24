@extends('member.layouts.app')

@section('title', 'Community Notifications')

@section('content')
<div class="community-notifications-page">
    <div class="community-back-nav">
        <a href="{{ route('member.community.index') }}" class="community-back-link">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span>Back to Communities</span>
        </a>
    </div>

    <div class="community-notifications-card">
        <div class="community-notifications-header">
            <div class="community-notifications-header__info">
                <h1>
                    <i data-lucide="bell" class="community-notifications-header__icon" aria-hidden="true"></i>
                    <span>Community Notification Center</span>
                </h1>
                <p>Stay updated with community events, announcements, and moderation alerts.</p>
            </div>

            <div class="community-notifications-header__actions">
                @if ($unreadCount > 0)
                    <button type="button" class="member-button member-button--secondary community-notifications-mark-read-btn" data-community-mark-all-read="{{ route('member.community.notifications.mark-all-read') }}">
                        <i data-lucide="check-check" aria-hidden="true"></i> Mark All as Read
                    </button>
                @endif
                <a href="{{ url()->current() }}" class="member-button member-button--secondary community-notifications-refresh-btn" title="Refresh notifications" aria-label="Refresh notifications">
                    <i data-lucide="rotate-cw" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="community-notifications-tabs" role="tablist" aria-label="Notification Categories">
            <a href="{{ route('member.community.notifications.index', ['category' => 'all']) }}" class="community-notifications-tab {{ $category === 'all' ? 'is-active' : '' }}">
                <span>All</span>
            </a>
            <a href="{{ route('member.community.notifications.index', ['category' => 'unread']) }}" class="community-notifications-tab {{ $category === 'unread' ? 'is-active' : '' }}">
                <span>Unread</span>
                @if($unreadCount > 0)
                    <span class="community-notifications-tab__badge">{{ $unreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('member.community.notifications.index', ['category' => 'announcements']) }}" class="community-notifications-tab {{ $category === 'announcements' ? 'is-active' : '' }}">
                <span>Announcements</span>
            </a>
            <a href="{{ route('member.community.notifications.index', ['category' => 'moderation']) }}" class="community-notifications-tab {{ $category === 'moderation' ? 'is-active' : '' }}">
                <span>Moderation</span>
            </a>
        </div>

        <!-- Body Content -->
        <div class="community-notifications-body">
            <div class="community-notifications-list">
                @forelse ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                        $hasPhoto = !empty($data['actor_photo'])
                            && str_starts_with($data['actor_photo'], 'uploads/profile/')
                            && file_exists(public_path($data['actor_photo']));
                        $actorPhotoUrl = $hasPhoto ? asset($data['actor_photo']) : null;
                        $initials = collect(preg_split('/\s+/', trim($data['actor_name'] ?? 'M')))
                            ->filter()->take(2)->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->implode('') ?: 'M';
                        $url = $data['url'] ?? route('member.community.index');
                    @endphp

                    <div
                        class="community-notification-item @if($isUnread) community-notification-item--unread @endif"
                        data-community-notification-item="{{ $notification->id }}"
                    >
                        <div style="position: relative; flex-shrink: 0;">
                            @if ($actorPhotoUrl)
                                <img src="{{ $actorPhotoUrl }}" alt="{{ $data['actor_name'] ?? 'Member' }}" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
                            @else
                                <span class="avatar post-avatar-initials" style="width: 42px; height: 42px; font-size: 14px;">{{ $initials }}</span>
                            @endif

                            <div style="position: absolute; bottom: -2px; right: -2px; width: 20px; height: 20px; border-radius: 50%; background: var(--color-primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                                <i data-lucide="{{ $data['icon'] ?? 'bell' }}" style="width: 11px; height: 11px;"></i>
                            </div>
                        </div>

                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                                <strong style="font-size: 14px; color: var(--color-text-main);">
                                    <a href="{{ $url }}" style="color: inherit; text-decoration: none;">{{ $data['title'] ?? 'Community Notification' }}</a>
                                </strong>
                                <span style="font-size: 11.5px; color: var(--color-text-secondary);">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>

                            <p style="font-size: 13px; color: var(--color-text-secondary); margin: 4px 0 0 0; line-height: 1.5;">
                                {{ $data['message'] ?? '' }}
                            </p>

                            @if (!empty($data['community_name']))
                                <span class="community-badge" style="font-size: 10.5px; margin-top: 6px; display: inline-block;">
                                    {{ $data['community_name'] }}
                                </span>
                            @endif
                        </div>

                        @if ($isUnread)
                            <button
                                type="button"
                                class="member-button member-button--secondary"
                                style="padding: 4px 8px; font-size: 11px; flex-shrink: 0;"
                                title="Mark as read"
                                data-community-notification-read="{{ route('member.community.notifications.read', $notification->id) }}"
                            >
                                <i data-lucide="check" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="community-notifications-empty">
                        <div class="community-notifications-empty__icon">
                            <i data-lucide="bell-off" aria-hidden="true"></i>
                        </div>
                        <h3>No Community Notifications</h3>
                        <p>You are all caught up with your communities.</p>
                    </div>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div class="community-notifications-pagination">
                    {{ $notifications->links('member.search.pagination') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
