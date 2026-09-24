<!-- Notifications Dropdown Partial -->
@php
    $currentAdmin = auth('admin')->user();
    $unreadCount = $currentAdmin ? $currentAdmin->unreadNotifications()->count() : 0;
    $adminNotifications = $currentAdmin ? $currentAdmin->notifications()->latest()->take(6)->get() : collect();
@endphp

<li class="nav-item notification-dropdown-wrapper">
    <button class="header-icon-btn notification-box" id="notificationToggle" type="button" aria-label="Notifications" title="Notifications">
        <i data-feather="bell"></i>
        @if($unreadCount > 0)
            <span class="badge bg-danger rounded-pill">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-dropdown-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <span class="fw-bold">Notifications</span>
            @if($unreadCount > 0)
                <span class="badge bg-primary rounded-pill">{{ $unreadCount }} New</span>
            @else
                <span class="badge bg-light text-muted rounded-pill">0 New</span>
            @endif
        </div>

        @if($adminNotifications->isEmpty())
            <div class="p-4 text-center text-muted">
                <i data-feather="bell-off" class="mb-2 text-muted" style="width:24px; height:24px;"></i>
                <p class="mb-0 small">No new notifications</p>
            </div>
        @else
            <div class="notification-list" style="max-height: 320px; overflow-y: auto;">
                @foreach($adminNotifications as $notif)
                    @php
                        $notifData = is_array($notif->data) ? $notif->data : (json_decode($notif->data, true) ?: []);
                        $notifTitle = $notifData['title'] ?? $notifData['message'] ?? 'Admin Notification';
                        $notifMessage = $notifData['message'] ?? $notifData['body'] ?? '';
                        $notifIcon = $notifData['icon'] ?? 'bell';
                        $isUnread = is_null($notif->read_at);
                    @endphp
                    <div class="notification-item p-3 border-bottom d-flex align-items-start gap-3" style="{{ $isUnread ? 'background-color: #f8fafc;' : '' }}">
                        <div class="notification-icon rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; background-color: {{ $isUnread ? 'rgba(23, 107, 255, 0.1)' : '#f1f5f9' }}; color: {{ $isUnread ? '#176bff' : '#64748b' }};">
                            <i data-feather="{{ in_array($notifIcon, ['bell', 'user', 'shield', 'alert-circle', 'info', 'check-circle', 'mail', 'eye', 'file-text']) ? $notifIcon : 'bell' }}" style="width: 14px; height: 14px;"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                <span class="fw-semibold text-dark small text-truncate" style="font-size: 12px;">{{ $notifTitle }}</span>
                                @if($isUnread)
                                    <span class="badge bg-primary" style="font-size: 8px; padding: 2px 4px;">NEW</span>
                                @endif
                            </div>
                            @if($notifMessage && $notifMessage !== $notifTitle)
                                <p class="text-muted mb-1 text-truncate" style="font-size: 11px; max-width: 210px;">{{ $notifMessage }}</p>
                            @endif
                            <small class="text-muted" style="font-size: 10px;">{{ $notif->created_at ? \Carbon\Carbon::parse($notif->created_at)->diffForHumans() : 'Just now' }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="notification-dropdown-footer p-2 border-top bg-light d-flex align-items-center justify-content-between">
            @if($unreadCount > 0)
                <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm text-primary p-0 text-decoration-none" style="font-size: 11px;">
                        <i data-feather="check-circle" style="width: 12px; height: 12px;" class="me-1"></i>Mark all read
                    </button>
                </form>
            @else
                <span></span>
            @endif
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-link btn-sm text-secondary p-0 text-decoration-none" style="font-size: 11px;">
                View all &rarr;
            </a>
        </div>
    </div>
</li>
