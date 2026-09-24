<div class="notification-dropdown__header">
    <div>
        <span>Latest updates</span>
        <h2>Notifications</h2>
    </div>
    <form method="POST" action="{{ route('member.notifications.read-all') }}" data-notification-read-all-form>
        @csrf
        <button type="submit" @disabled($notifications->whereNull('read_at')->isEmpty())>
            <i data-lucide="check-check" aria-hidden="true"></i>
            Mark all as read
        </button>
    </form>
</div>

<div class="notification-dropdown__body">
    @forelse ($notifications as $notification)
        @include('member.notifications.partials.item', compact('notification'))
    @empty
        <div class="notification-empty notification-empty--compact">
            <i data-lucide="bell-ring" aria-hidden="true"></i>
            <strong>No notifications yet</strong>
            <span>Friend requests and new posts will appear here.</span>
        </div>
    @endforelse
</div>

<footer class="notification-dropdown__footer">
    <a href="{{ route('member.notifications.index') }}">
        See all notifications <i data-lucide="arrow-right" aria-hidden="true"></i>
    </a>
</footer>
