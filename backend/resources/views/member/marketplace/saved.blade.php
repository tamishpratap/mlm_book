@extends('member.layouts.app')

@section('title', 'Saved Items - Marketplace')

@section('content')
<div class="marketplace-page">
    <header class="marketplace-header card">
        <div class="marketplace-header__info">
            <h1><i data-lucide="bookmark" aria-hidden="true"></i> Saved Marketplace Items</h1>
            <p>Products you have bookmarked to view or purchase later.</p>
        </div>
        <div class="marketplace-header__actions">
            <a href="{{ route('member.marketplace.index') }}" class="member-button member-button--secondary">
                <i data-lucide="store" aria-hidden="true"></i> Marketplace Home
            </a>
        </div>
    </header>

    <div id="marketplace-grid-container">
        @include('member.marketplace.partials.product_grid', compact('products'))
    </div>
</div>
@endsection
