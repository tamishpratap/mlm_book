@php
    $featuredMedia = $product->media->firstWhere('is_featured', true) ?? $product->media->first();
    $mediaPath = $featuredMedia ? asset($featuredMedia->media_path) : asset('member_assets/images/default-product.png');
    $isSaved = $product->isSavedBy(auth('member')->id());
@endphp

<div class="product-card card" data-product-card="{{ $product->id }}">
    <div class="product-card__image">
        <a href="{{ route('member.marketplace.show', $product) }}">
            @if ($featuredMedia && $featuredMedia->media_type === 'video')
                <video src="{{ $mediaPath }}" muted preload="metadata"></video>
            @else
                <img src="{{ $mediaPath }}" alt="{{ $product->title }}" loading="lazy">
            @endif
        </a>
        @if ($product->status === 'sold')
            <span class="product-badge product-badge--sold">SOLD</span>
        @elseif ($product->status === 'reserved')
            <span class="product-badge product-badge--reserved">RESERVED</span>
        @endif

        <button class="product-card__save-btn {{ $isSaved ? 'is-saved' : '' }}"
                type="button"
                data-product-save-btn="{{ $product->id }}"
                data-product-save-url="{{ route('member.marketplace.save', $product) }}"
                title="{{ $isSaved ? 'Unsave product' : 'Save product' }}">
            <i data-lucide="bookmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="product-card__content">
        <div class="product-card__price">${{ number_format($product->price, 2) }}</div>
        <h3 class="product-card__title">
            <a href="{{ route('member.marketplace.show', $product) }}">{{ $product->title }}</a>
        </h3>
        <div class="product-card__meta">
            @if ($product->location)
                <span><i data-lucide="map-pin" aria-hidden="true"></i>{{ $product->location }}</span>
            @endif
            <span><i data-lucide="clock" aria-hidden="true"></i>{{ $product->created_at->diffForHumans() }}</span>
        </div>
    </div>
</div>
