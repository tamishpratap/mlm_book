@extends('member.layouts.app')

@section('title', 'Discover Communities')

@section('content')
<div class="community-page">
    <!-- Explore Hero Banner -->
    <div class="card" style="background: linear-gradient(135deg, rgba(79, 125, 243, 0.15), rgba(125, 66, 240, 0.15)); border: 1px solid var(--color-border-soft); border-radius: var(--radius-lg); padding: 32px 24px; margin-bottom: 24px; position: relative; overflow: hidden;">
        <div style="max-width: 680px; position: relative; z-index: 2;">
            <span class="community-badge" style="background: var(--color-primary); color: #ffffff; margin-bottom: 8px; display: inline-block;">
                <i data-lucide="compass" style="width: 12px; height: 12px; vertical-align: middle;"></i> Community Discovery Platform
            </span>
            <h1 style="font-size: 26px; font-weight: 900; color: var(--color-text-main); margin: 0 0 8px 0; letter-spacing: -0.02em;">
                Discover & Join Vibrant Communities
            </h1>
            <p style="font-size: 14.5px; color: var(--color-text-secondary); line-height: 1.5; margin: 0 0 20px 0;">
                Find communities aligned with your goals, interests, network, and business growth. Connect with like-minded creators and professionals.
            </p>

            <!-- Instant Live Search Input -->
            <div style="position: relative; max-width: 520px;">
                <div style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--color-text-secondary);">
                    <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                </div>
                <input
                    type="text"
                    class="community-search-input"
                    placeholder="Search communities by name, category, or interests..."
                    value="{{ $search }}"
                    style="padding-left: 42px; height: 46px; font-size: 14px; border-radius: var(--radius-md); width: 100%;"
                    data-community-live-search="{{ route('member.community.search-ajax') }}"
                >
                <div id="communityLiveSearchResults" class="card" style="position: absolute; top: 52px; left: 0; right: 0; z-index: 50; display: none; max-height: 320px; overflow-y: auto; padding: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <!-- AJAX results injected here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Category Browser -->
    <div style="margin-bottom: 28px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding: 0 4px;">
            <h2 style="font-size: 16px; font-weight: 800; color: var(--color-text-main); margin: 0;">
                <i data-lucide="layers" style="width: 18px; height: 18px; color: var(--color-primary); vertical-align: middle;"></i> Browse Categories
            </h2>
            @if ($category)
                <a href="{{ route('member.community.discover') }}" style="font-size: 12.5px; color: var(--color-primary); font-weight: 600;">Clear Category Filter</a>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
            @foreach (\App\Models\Community::CATEGORIES as $cat)
                @php
                    $count = $categoriesWithCount[$cat] ?? 0;
                    $isActive = $category === $cat;
                @endphp
                <a
                    href="{{ route('member.community.discover', ['category' => $cat]) }}"
                    class="card"
                    style="padding: 12px; border-radius: var(--radius-md); text-decoration: none; display: flex; flex-direction: column; gap: 4px; transition: transform 0.2s, border-color 0.2s; @if($isActive) border-color: var(--color-primary); background: rgba(79,125,243,0.08); @endif"
                >
                    <span style="font-size: 13px; font-weight: 700; color: var(--color-text-main);">{{ $cat }}</span>
                    <span style="font-size: 11px; color: var(--color-text-secondary);">{{ number_format($count) }} {{ \Illuminate\Support\Str::plural('community', $count) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Featured Showcase -->
    @if ($featuredCommunities->isNotEmpty() && ! $search && ! $category)
        <div style="margin-bottom: 28px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; padding: 0 4px;">
                <i data-lucide="sparkles" style="width: 18px; height: 18px; color: #f59e0b;"></i>
                <h2 style="font-size: 16px; font-weight: 800; color: var(--color-text-main); margin: 0;">Featured Communities</h2>
            </div>
            <div class="community-grid">
                @foreach ($featuredCommunities as $community)
                    @include('member.community.partials.discovery-card', compact('community'))
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recommended for You -->
    @if ($suggestedCommunities->isNotEmpty() && ! $search && ! $category)
        <div style="margin-bottom: 28px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px; padding: 0 4px;">
                <i data-lucide="user-check" style="width: 18px; height: 18px; color: #10b981;"></i>
                <h2 style="font-size: 16px; font-weight: 800; color: var(--color-text-main); margin: 0;">Recommended for You</h2>
            </div>
            <div class="community-grid">
                @foreach ($suggestedCommunities as $community)
                    @include('member.community.partials.discovery-card', compact('community'))
                @endforeach
            </div>
        </div>
    @endif

    <!-- All Communities & Filters Bar -->
    <div style="margin-top: 12px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; padding: 0 4px;">
            <h2 style="font-size: 16px; font-weight: 800; color: var(--color-text-main); margin: 0;">
                @if ($category) Category: {{ $category }} @elseif ($search) Search Results for "{{ $search }}" @else All Communities @endif
            </h2>

            <!-- Sorting & Visibility Filters -->
            <form method="GET" action="{{ route('member.community.discover') }}" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($category) <input type="hidden" name="category" value="{{ $category }}"> @endif
                @if ($search) <input type="hidden" name="search" value="{{ $search }}"> @endif

                <select name="sort" class="community-search-input" style="padding: 6px 12px; font-size: 12px; width: auto;" onchange="this.form.submit()">
                    <option value="trending" {{ $sort === 'trending' ? 'selected' : '' }}>Trending</option>
                    <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>Most Members</option>
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>Oldest</option>
                    <option value="featured" {{ $sort === 'featured' ? 'selected' : '' }}>Featured</option>
                </select>

                <select name="visibility" class="community-search-input" style="padding: 6px 12px; font-size: 12px; width: auto;" onchange="this.form.submit()">
                    <option value="all" {{ $visibility === 'all' ? 'selected' : '' }}>All Visibilities</option>
                    <option value="public" {{ $visibility === 'public' ? 'selected' : '' }}>Public Only</option>
                    <option value="private" {{ $visibility === 'private' ? 'selected' : '' }}>Private Only</option>
                </select>
            </form>
        </div>

        <div class="community-grid">
            @forelse ($communities as $community)
                @include('member.community.partials.discovery-card', compact('community'))
            @empty
                <div class="fb-empty-state" style="grid-column: 1 / -1; width: 100%;">
                    <div class="fb-empty-state__icon">
                        <i data-lucide="search-x" aria-hidden="true"></i>
                    </div>
                    <h3>No Communities Found</h3>
                    <p>No communities matched your search query or filter selection.</p>
                </div>
            @endforelse
        </div>

        @if ($communities->hasPages())
            <div class="pagination-wrapper" style="margin-top: 24px;">
                {{ $communities->links('member.search.pagination') }}
            </div>
        @endif
    </div>
</div>
@endsection
