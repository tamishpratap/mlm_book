@extends('member.layouts.app')

@section('title', 'Community Hub')

@section('content')
<div class="community-page">
    <header class="community-header">
        <div class="community-header__info">
            <h1><i data-lucide="users-round" aria-hidden="true"></i> Community Hub</h1>
            <p>Discover, build, and collaborate with thriving member communities.</p>
        </div>
        <div class="community-header__actions">
            <a href="{{ route('member.community.create') }}" class="member-button member-button--primary">
                <i data-lucide="plus" aria-hidden="true"></i> Create Community
            </a>
        </div>
    </header>

    <nav class="community-nav-tabs" aria-label="Community tabs">
        <a class="community-nav-tab {{ $tab === 'all' ? 'is-active' : '' }}" href="{{ route('member.community.index', ['tab' => 'all', 'search' => request('search'), 'category' => request('category')]) }}">
            <i data-lucide="grid" aria-hidden="true"></i> All Communities
        </a>
        <a class="community-nav-tab {{ $tab === 'joined' ? 'is-active' : '' }}" href="{{ route('member.community.index', ['tab' => 'joined', 'search' => request('search'), 'category' => request('category')]) }}">
            <i data-lucide="shield-check" aria-hidden="true"></i> Joined Communities ({{ $joinedCommunities->count() }})
        </a>
        <a class="community-nav-tab {{ $tab === 'my' ? 'is-active' : '' }}" href="{{ route('member.community.index', ['tab' => 'my', 'search' => request('search'), 'category' => request('category')]) }}">
            <i data-lucide="users-round" aria-hidden="true"></i> My Communities ({{ $myCommunities->count() }})
        </a>
        <a class="community-nav-tab" href="{{ route('member.community.discover') }}">
            <i data-lucide="compass" aria-hidden="true"></i> Discover Platform
        </a>
        <a class="community-nav-tab" href="{{ route('member.community.discover', ['sort' => 'trending']) }}">
            <i data-lucide="trending-up" aria-hidden="true"></i> Trending
        </a>
    </nav>

    <div class="community-toolbar">
        <form method="GET" action="{{ route('member.community.index') }}" class="community-search-form">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="community-search-input-wrap">
                <i data-lucide="search" aria-hidden="true"></i>
                <input
                    type="search"
                    name="search"
                    class="community-search-input"
                    value="{{ request('search') }}"
                    placeholder="Search communities by name, description, or category..."
                    aria-label="Search communities"
                >
            </div>
            <select name="category" class="community-filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="community-grid">
        @forelse ($communities as $community)
            @include('member.community.partials.card', compact('community'))
        @empty
            <div class="card community-empty-state" style="grid-column: 1 / -1; width: 100%;">
                <div class="community-empty-state__icon">
                    <i data-lucide="users-round" aria-hidden="true"></i>
                </div>
                @if ($tab === 'joined')
                    <h3>No Joined Communities Yet</h3>
                    <p>{{ request('search') || request('category') ? 'No joined communities found matching your filters.' : 'You haven\'t joined any communities yet. Discover exciting communities and connect with other members!' }}</p>
                    <a href="{{ route('member.community.index', ['tab' => 'all']) }}" class="member-button member-button--primary">
                        <i data-lucide="compass" aria-hidden="true"></i> <span>Explore All Communities</span>
                    </a>
                @elseif ($tab === 'my')
                    <h3>You Haven't Created Any Communities Yet</h3>
                    <p>{{ request('search') || request('category') ? 'No created communities found matching your filters.' : 'You haven\'t created any communities yet. Start your own thriving community and bring people together!' }}</p>
                    <a href="{{ route('member.community.create') }}" class="member-button member-button--primary">
                        <i data-lucide="plus" aria-hidden="true"></i> <span>Create Your First Community</span>
                    </a>
                @else
                    <h3>No Communities Found</h3>
                    <p>Be the first pioneer to create a community for this category!</p>
                    <a href="{{ route('member.community.create') }}" class="member-button member-button--primary">
                        <i data-lucide="plus" aria-hidden="true"></i> <span>Create Community</span>
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    @if ($communities->hasPages())
        <div class="pagination-wrapper">
            {{ $communities->links('member.search.pagination') }}
        </div>
    @endif
</div>
@endsection
