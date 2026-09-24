<div class="marketplace-grid" data-marketplace-grid>
    @forelse ($products as $product)
        @include('member.marketplace.partials.product_card', compact('product'))
    @empty
        <div class="marketplace-empty card" style="grid-column: 1 / -1;">
            <div class="marketplace-empty__icon">
                <i data-lucide="store" aria-hidden="true"></i>
            </div>
            <h2>No Products Found</h2>
            <p>We couldn't find any products matching your current filters or search query.</p>
            <div class="marketplace-empty__actions">
                <a href="{{ route('member.marketplace.create') }}" class="member-button member-button--primary">
                    <i data-lucide="plus" aria-hidden="true"></i> Create First Listing
                </a>
                <a href="{{ route('member.marketplace.index') }}" class="member-button member-button--secondary">
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i> Clear Filters
                </a>
            </div>
        </div>
    @endforelse
</div>

@if ($products->hasPages())
    {{ $products->links('member.search.pagination') }}
@endif
