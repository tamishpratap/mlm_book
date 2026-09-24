@extends('member.layouts.app')

@section('title', 'Events')

@section('content')
<div class="events-page">
    <header class="marketplace-header card">
        <div class="marketplace-header__info">
            <h1><i data-lucide="calendar" aria-hidden="true"></i> Events Hub</h1>
            <p>Discover, create, and attend community events, workshops, and meetups.</p>
        </div>
        <div class="marketplace-header__actions">
            <a href="{{ route('member.events.create') }}" class="member-button member-button--primary">
                <i data-lucide="plus" aria-hidden="true"></i> Create New Event
            </a>
        </div>
    </header>

    <nav class="events-nav-tabs" aria-label="Event tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}"
           class="events-nav-tab {{ $tab === 'all' ? 'is-active' : '' }}">
            <i data-lucide="compass" aria-hidden="true"></i>
            <span>All Events</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'my', 'page' => 1]) }}"
           class="events-nav-tab {{ $tab === 'my' ? 'is-active' : '' }}">
            <i data-lucide="calendar-check" aria-hidden="true"></i>
            <span>My Events</span>
            @if ($myEventsCount > 0)
                <span class="events-nav-tab__count">{{ $myEventsCount }}</span>
            @endif
        </a>
    </nav>

    <div class="marketplace-toolbar card">
        <form method="GET" action="{{ route('member.events.index') }}" class="marketplace-search-form">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="search-box">
                <i data-lucide="search" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search Events by title, city, category...">
            </div>
            <div class="marketplace-filters-row">
                <select name="timeframe" onchange="this.form.submit()">
                    <option value="">All Dates</option>
                    <option value="today" {{ request('timeframe') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="tomorrow" {{ request('timeframe') === 'tomorrow' ? 'selected' : '' }}>Tomorrow</option>
                    <option value="this_week" {{ request('timeframe') === 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ request('timeframe') === 'this_month' ? 'selected' : '' }}>This Month</option>
                </select>

                <select name="type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="online" {{ request('type') === 'online' ? 'selected' : '' }}>Online Events</option>
                    <option value="offline" {{ request('type') === 'offline' ? 'selected' : '' }}>In-Person Events</option>
                </select>

                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($tab === 'my')
        <div class="groups-section">
            <h2><i data-lucide="calendar-check" aria-hidden="true"></i> My Events & RSVPs ({{ $events->total() }})</h2>
            <div class="groups-grid">
                @forelse ($events as $event)
                    @include('member.events.partials.event_card', compact('event'))
                @empty
                    <div class="notification-empty notification-empty--full-width">
                        <div class="notification-empty__icon"><i data-lucide="calendar-check" aria-hidden="true"></i></div>
                        <h2>No Events Yet</h2>
                        <p>You haven't created or joined any events yet.</p>
                        <a href="{{ route('member.events.create') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                            <i data-lucide="plus" aria-hidden="true"></i> Create New Event
                        </a>
                    </div>
                @endforelse
            </div>

            @if ($events->hasPages())
                {{ $events->links('member.search.pagination') }}
            @endif
        </div>
    @else
        <div class="groups-section">
            <h2><i data-lucide="compass" aria-hidden="true"></i> Discover Upcoming Events ({{ $events->total() }})</h2>
            <div class="groups-grid">
                @forelse ($events as $event)
                    @include('member.events.partials.event_card', compact('event'))
                @empty
                    <div class="notification-empty notification-empty--full-width">
                        <div class="notification-empty__icon"><i data-lucide="calendar" aria-hidden="true"></i></div>
                        <h2>No Events Found</h2>
                        <p>No events match your current filter criteria. Try clearing filters or create a new event!</p>
                        <a href="{{ route('member.events.create') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                            <i data-lucide="plus" aria-hidden="true"></i> Create New Event
                        </a>
                    </div>
                @endforelse
            </div>

            @if ($events->hasPages())
                {{ $events->links('member.search.pagination') }}
            @endif
        </div>
    @endif
</div>
@endsection
