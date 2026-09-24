@extends('member.layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="notifications-page">
        <header class="notifications-page__header">
            <div>
                <span>Stay up to date</span>
                <h1>Notifications</h1>
                <p>Activity on your posts, stories, comments, and friend requests.</p>
            </div>
            <div class="notifications-page__actions">
                <form method="POST" action="{{ route('member.notifications.read-all') }}" data-notification-read-all-form>
                    @csrf
                    <button class="member-button member-button--secondary" type="submit" @disabled($notifications->whereNull('read_at')->isEmpty())>
                        <i data-lucide="check-check" aria-hidden="true"></i>
                        Mark all as read
                    </button>
                </form>
                <form method="POST" action="{{ route('member.notifications.clear-all') }}" data-notification-clear-all-form>
                    @csrf
                    @method('DELETE')
                    <button class="member-button member-button--secondary member-button--danger" type="submit" @disabled($notifications->isEmpty()) onclick="return confirm('Are you sure you want to clear all notifications?')">
                        <i data-lucide="trash-2" aria-hidden="true"></i>
                        Clear all
                    </button>
                </form>
            </div>
        </header>

        <nav class="notification-filter-tabs" aria-label="Notification Categories">
            @php
                $currentFilter = $filter ?? 'all';
                $filters = [
                    'all' => 'All',
                    'unread' => 'Unread',
                    'friends' => 'Friends',
                    'stories' => 'Stories',
                    'posts' => 'Posts',
                    'comments' => 'Comments',
                    'system' => 'System',
                ];
            @endphp
            @foreach ($filters as $key => $label)
                <a href="{{ route('member.notifications.index', ['filter' => $key]) }}"
                   class="notification-filter-tab {{ $currentFilter === $key ? 'is-active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <section class="member-card notifications-page__card" aria-label="Notification history">
            @forelse ($notifications as $notification)
                @include('member.notifications.partials.item', compact('notification'))
            @empty
                <div class="notification-empty">
                    <div class="notification-empty__icon">
                        <i data-lucide="bell-off" aria-hidden="true"></i>
                    </div>
                    <h2>No {{ $currentFilter !== 'all' ? ucfirst($currentFilter) : '' }} Notifications</h2>
                    <p>When you get new notifications, they will show up here.</p>
                </div>
            @endforelse
        </section>

        @if ($notifications->hasPages())
            {{ $notifications->links('member.search.pagination') }}
        @endif
    </div>
@endsection
