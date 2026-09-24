@extends('member.layouts.app')

@section('title', 'My Products')

@section('content')
<div class="marketplace-page">
    <header class="marketplace-header card">
        <div class="marketplace-header__info">
            <h1><i data-lucide="package" aria-hidden="true"></i> My Products</h1>
            <p>Manage your Marketplace product listings and status.</p>
        </div>
        <div class="marketplace-header__actions">
            <a href="{{ route('member.marketplace.create') }}" class="member-button member-button--primary">
                <i data-lucide="plus" aria-hidden="true"></i> Create New Listing
            </a>
            <a href="{{ route('member.marketplace.index') }}" class="member-button member-button--secondary">
                <i data-lucide="store" aria-hidden="true"></i> Marketplace Home
            </a>
        </div>
    </header>

    <div class="category-nav-bar">
        <a href="{{ route('member.marketplace.my-products', ['status' => 'all']) }}" class="category-pill {{ $status === 'all' ? 'is-active' : '' }}">All</a>
        <a href="{{ route('member.marketplace.my-products', ['status' => 'available']) }}" class="category-pill {{ $status === 'available' ? 'is-active' : '' }}">Available</a>
        <a href="{{ route('member.marketplace.my-products', ['status' => 'sold']) }}" class="category-pill {{ $status === 'sold' ? 'is-active' : '' }}">Sold</a>
        <a href="{{ route('member.marketplace.my-products', ['status' => 'reserved']) }}" class="category-pill {{ $status === 'reserved' ? 'is-active' : '' }}">Reserved</a>
    </div>

    <div id="marketplace-grid-container">
        @include('member.marketplace.partials.product_grid', compact('products'))
    </div>
</div>
@endsection
