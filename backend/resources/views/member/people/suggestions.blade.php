@extends('member.layouts.app')

@section('title', 'New Connections')

@section('content')
<div class="friends-page suggestions-page">
    <header class="member-page-heading friend-page-heading">
        <div>
            <span class="friend-page-eyebrow">Discover</span>
            <h1>New Connections</h1>
            <p>Recommended connections based on mutual connections, registration, and location.</p>
        </div>
        <a class="member-button member-button--secondary" href="{{ route('member.friends.index') }}">
            <i data-lucide="users-round" aria-hidden="true"></i>
            <span>My Connections</span>
        </a>
    </header>

    <!-- Filter Bar Card -->
    <form class="card member-card connection-filter-card" method="GET" action="{{ route('member.people.suggestions') }}">
        <div class="connection-filter-main">
            <!-- Member Search Input -->
            <div class="connection-search-box">
                <i data-lucide="search" aria-hidden="true"></i>
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by name, handle, city, country, or bio..."
                    aria-label="Search members"
                >
                @if (filled($search))
                    <a
                        class="connection-search-clear"
                        href="{{ route('member.people.suggestions', array_filter(['country' => $selectedCountry, 'filter' => $activeFilter !== 'all' ? $activeFilter : null])) }}"
                        title="Clear search"
                        aria-label="Clear search"
                    >
                        <i data-lucide="x" aria-hidden="true"></i>
                    </a>
                @endif
            </div>

            <!-- Dynamic Country Dropdown -->
            <div class="connection-country-select-wrap">
                <i data-lucide="globe" aria-hidden="true"></i>
                <select name="country" onchange="this.form.submit()" aria-label="Filter by country">
                    <option value="">All Countries</option>
                    @foreach ($availableCountries as $countryName)
                        <option value="{{ $countryName }}" {{ $selectedCountry === $countryName ? 'selected' : '' }}>
                            {{ $countryName }}
                        </option>
                    @endforeach
                </select>
                <i data-lucide="chevron-down" class="connection-select-arrow" aria-hidden="true"></i>
            </div>

            <button class="member-button member-button--primary connection-search-btn" type="submit">
                <i data-lucide="search" aria-hidden="true"></i>
                <span>Search</span>
            </button>
        </div>

        <!-- Quick Filters Row -->
        <div class="connection-quick-filters">
            <input type="hidden" name="filter" id="connectionActiveFilter" value="{{ $activeFilter }}">

            <div class="connection-quick-filter-pills" role="tablist" aria-label="Quick filters">
                <button
                    type="submit"
                    onclick="document.getElementById('connectionActiveFilter').value='all'"
                    class="connection-quick-btn {{ $activeFilter === 'all' ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeFilter === 'all' ? 'true' : 'false' }}"
                >
                    <i data-lucide="users" aria-hidden="true"></i>
                    <span>All</span>
                </button>

                <button
                    type="submit"
                    onclick="document.getElementById('connectionActiveFilter').value='new'"
                    class="connection-quick-btn {{ $activeFilter === 'new' ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeFilter === 'new' ? 'true' : 'false' }}"
                >
                    <i data-lucide="sparkles" aria-hidden="true"></i>
                    <span>New Members</span>
                </button>

                <button
                    type="submit"
                    onclick="document.getElementById('connectionActiveFilter').value='mutual'"
                    class="connection-quick-btn {{ $activeFilter === 'mutual' ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeFilter === 'mutual' ? 'true' : 'false' }}"
                >
                    <i data-lucide="user-check" aria-hidden="true"></i>
                    <span>Mutual Connections</span>
                </button>

                <button
                    type="submit"
                    onclick="document.getElementById('connectionActiveFilter').value='nearby'"
                    class="connection-quick-btn {{ $activeFilter === 'nearby' ? 'is-active' : '' }}"
                    role="tab"
                    aria-selected="{{ $activeFilter === 'nearby' ? 'true' : 'false' }}"
                >
                    <i data-lucide="map-pin" aria-hidden="true"></i>
                    <span>Nearby</span>
                </button>
            </div>

            @if (filled($search) || filled($selectedCountry) || $activeFilter !== 'all')
                <a class="connection-clear-filters-link" href="{{ route('member.people.suggestions') }}">
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                    <span>Reset Filters</span>
                </a>
            @endif
        </div>
    </form>

    <!-- Member Cards Results Grid -->
    @if ($suggestions->isEmpty())
        <section class="member-card friend-empty">
            <span><i data-lucide="sparkles" aria-hidden="true"></i></span>
            @if (filled($search) || filled($selectedCountry) || $activeFilter !== 'all')
                <h2>No new connections match your search or filter.</h2>
                <p>Try clearing your search terms or selecting a different country or quick filter.</p>
                <a class="member-button member-button--primary" href="{{ route('member.people.suggestions') }}" style="margin-top: 12px;">
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                    <span>Reset All Filters</span>
                </a>
            @else
                <h2>No New Connections Available</h2>
                <p>Check back later for new connection recommendations.</p>
            @endif
        </section>
    @else
        <div class="connection-requests-list">
            @foreach ($suggestions as $member)
                @php
                    $mutualCount = auth('member')->user()->mutualFriendsCount($member->id);
                @endphp
                @include('member.friends.partials.compact-member-row', [
                    'friend' => $member,
                    'mode' => 'suggestion',
                    'mutualCount' => $mutualCount,
                ])
            @endforeach
        </div>

        @if ($suggestions->hasPages())
            {{ $suggestions->links('member.search.pagination') }}
        @endif
    @endif
</div>
@endsection
