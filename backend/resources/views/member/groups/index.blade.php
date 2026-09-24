@extends('member.layouts.app')

@section('title', 'Communities')

@section('content')
<div class="groups-page">
    <header class="marketplace-header card">
        <div class="marketplace-header__info">
            <h1><i data-lucide="users-round" aria-hidden="true"></i> Communities</h1>
            <p>Connect with communities, share interests, and collaborate with members.</p>
        </div>
        <div class="marketplace-header__actions">
            <a href="{{ route('member.groups.create') }}" class="member-button member-button--primary">
                <i data-lucide="plus" aria-hidden="true"></i> Create New Community
            </a>
        </div>
    </header>

    <div class="marketplace-toolbar card">
        <form method="GET" action="{{ route('member.groups.index') }}" class="marketplace-search-form">
            <div class="search-box">
                <i data-lucide="search" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search Communities...">
            </div>
            <div class="marketplace-filters-row">
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($myGroups->isNotEmpty())
        <div class="groups-section">
            <h2><i data-lucide="shield-check" aria-hidden="true"></i> My Joined Communities ({{ $myGroups->count() }})</h2>
            <div class="groups-grid">
                @foreach ($myGroups as $group)
                    @include('member.groups.partials.group_card', compact('group'))
                @endforeach
            </div>
        </div>
    @endif

    <div class="groups-section">
        <h2><i data-lucide="compass" aria-hidden="true"></i> Discover Communities</h2>
        <div class="groups-grid">
            @forelse ($suggestedGroups as $group)
                @include('member.groups.partials.group_card', compact('group'))
            @empty
                <div class="notification-empty" style="grid-column: 1 / -1;">
                    <div class="notification-empty__icon">
                        <i data-lucide="users-round" aria-hidden="true"></i>
                    </div>
                    <h2>No Communities Found</h2>
                    <p>Be the first to create a community!</p>
                </div>
            @endforelse
        </div>

        @if ($suggestedGroups->hasPages())
            <div class="pagination-wrapper">
                {{ $suggestedGroups->links('member.search.pagination') }}
            </div>
        @endif
    </div>
</div>
@endsection
