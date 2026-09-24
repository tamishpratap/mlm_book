@extends('member.layouts.app')

@section('title', 'Marketplace')

@section('content')
<div class="marketplace-page">
    <header class="marketplace-header card">
        <div class="row align-items-center g-3 w-100 m-0">
            <div class="col-12 col-lg-5 col-xl-6 p-0">
                <div class="marketplace-header__info">
                    <div class="marketplace-header__icon-badge">
                        <i data-lucide="store" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Marketplace</h1>
                        <p>Buy & sell items locally with MLM Book community members.</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7 col-xl-6 p-0 d-flex justify-content-lg-end">
                <div class="marketplace-header__actions">
                    <a href="{{ route('member.marketplace.create') }}" class="member-button member-button--primary">
                        <i data-lucide="plus" aria-hidden="true"></i> Create New Listing
                    </a>
                    <a href="{{ route('member.marketplace.my-products') }}" class="member-button member-button--secondary">
                        <i data-lucide="package" aria-hidden="true"></i> My Products
                    </a>
                    <a href="{{ route('member.marketplace.saved') }}" class="member-button member-button--secondary">
                        <i data-lucide="bookmark" aria-hidden="true"></i> Saved Items
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="marketplace-toolbar card">
        <form method="GET" action="{{ route('member.marketplace.index') }}" class="marketplace-search-form" data-marketplace-filter-form>
            <div class="search-box">
                <i data-lucide="search" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search Marketplace items, electronics, vehicles..." data-marketplace-search-input>
            </div>

            <div class="row g-3 marketplace-filters-row">
                <div class="col-12 col-md-4">
                    <div class="select-wrapper">
                        <i data-lucide="grid" aria-hidden="true" class="select-icon"></i>
                        <select name="category" data-marketplace-filter-select aria-label="Select Category">
                            <option value="">All Categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="select-wrapper">
                        <i data-lucide="sparkles" aria-hidden="true" class="select-icon"></i>
                        <select name="condition" data-marketplace-filter-select aria-label="Select Condition">
                            <option value="">Any Condition</option>
                            <option value="new" {{ request('condition') === 'new' ? 'selected' : '' }}>Brand New</option>
                            <option value="like_new" {{ request('condition') === 'like_new' ? 'selected' : '' }}>Like New</option>
                            <option value="good" {{ request('condition') === 'good' ? 'selected' : '' }}>Good Condition</option>
                            <option value="fair" {{ request('condition') === 'fair' ? 'selected' : '' }}>Fair Condition</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="select-wrapper">
                        <i data-lucide="arrow-up-down" aria-hidden="true" class="select-icon"></i>
                        <select name="sort" data-marketplace-filter-select aria-label="Sort Results">
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="most_viewed" {{ request('sort') === 'most_viewed' ? 'selected' : '' }}>Most Popular</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <nav class="category-nav-bar" aria-label="Product Categories">
        <a href="{{ route('member.marketplace.index') }}" class="category-pill {{ !request('category') ? 'is-active' : '' }}">
            <i data-lucide="layers" aria-hidden="true"></i> All Items
        </a>
        @foreach ($categories as $cat)
            <a href="{{ route('member.marketplace.index', ['category' => $cat->slug]) }}"
               class="category-pill {{ request('category') === $cat->slug ? 'is-active' : '' }}">
                <i data-lucide="{{ $cat->icon ?? 'box' }}" aria-hidden="true"></i> {{ $cat->name }}
            </a>
        @endforeach
    </nav>

    <div id="marketplace-grid-container" data-marketplace-grid-container>
        @include('member.marketplace.partials.product_grid', compact('products'))
    </div>
</div>
@endsection
