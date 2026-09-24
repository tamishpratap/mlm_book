@extends('member.layouts.app')

@section('title', 'Business Pages')

@section('content')
<div class="biz-page">
    <header class="biz-header">
        <div class="biz-header__info">
            <h1><i data-lucide="building-2" aria-hidden="true"></i> Business Pages</h1>
            <p>Discover, create, and manage enterprise business pages across the network.</p>
        </div>
        <div class="biz-header__actions">
            <a href="{{ route('member.business-pages.create') }}" class="member-button member-button--primary">
                <i data-lucide="plus-circle" aria-hidden="true"></i> Create Business Page
            </a>
        </div>
    </header>

    <nav class="biz-nav-tabs" aria-label="Business Page tabs">
        <a class="biz-nav-tab {{ $tab === 'all' ? 'is-active' : '' }}" href="{{ route('member.business-pages.index', ['tab' => 'all', 'search' => request('search'), 'category' => request('category')]) }}">
            <i data-lucide="grid" aria-hidden="true"></i> All Business Pages
        </a>
        <a class="biz-nav-tab {{ $tab === 'my' ? 'is-active' : '' }}" href="{{ route('member.business-pages.index', ['tab' => 'my', 'search' => request('search'), 'category' => request('category')]) }}">
            <i data-lucide="user-check" aria-hidden="true"></i> My Business Pages ({{ $myPages->count() }})
        </a>
    </nav>

    <div class="biz-toolbar">
        <form method="GET" action="{{ route('member.business-pages.index') }}" class="biz-search-form">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="biz-search-input-wrap">
                <i data-lucide="search" aria-hidden="true"></i>
                <input
                    type="search"
                    name="search"
                    class="biz-search-input"
                    value="{{ request('search') }}"
                    placeholder="Search pages by name, @username, description, or location..."
                    aria-label="Search business pages"
                >
            </div>
            <select name="category" class="biz-filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="biz-grid">
        @forelse ($pages as $businessPage)
            @include('member.business-pages.partials.card', compact('businessPage'))
        @empty
            <div class="fb-empty-state" style="grid-column: 1 / -1; width: 100%; text-align: center; padding: 48px 20px;">
                <div class="fb-empty-state__icon" style="margin-bottom: 16px; font-size: 40px; color: #98a2b3;">
                    <i data-lucide="building-2" style="width: 48px; height: 48px;" aria-hidden="true"></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1d2738; margin-bottom: 8px;">No Business Pages Found</h3>
                <p style="color: #687386; margin-bottom: 20px;">Build your brand presence by launching your first business page!</p>
                <a href="{{ route('member.business-pages.create') }}" class="member-button member-button--primary" style="display: inline-flex;">
                    <i data-lucide="plus-circle" aria-hidden="true"></i> Create Business Page
                </a>
            </div>
        @endforelse
    </div>

    @if ($pages->hasPages())
        <div class="pagination-wrapper" style="margin-top: 20px;">
            {{ $pages->links('member.search.pagination') }}
        </div>
    @endif
</div>
@endsection
