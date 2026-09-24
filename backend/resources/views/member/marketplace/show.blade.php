@extends('member.layouts.app')

@section('title', $product->title.' - Marketplace')

@section('content')
<div class="product-details-page">
    <div class="product-details-grid">
        <div class="product-details-gallery card">
            @php
                $mediaItems = $product->media;
            @endphp
            @if ($mediaItems->isEmpty())
                <div class="gallery-main">
                    <img src="{{ asset('member_assets/images/default-product.png') }}" alt="{{ $product->title }}">
                </div>
            @else
                <div class="gallery-main" id="gallery-main-viewport">
                    @php $first = $mediaItems->first(); @endphp
                    @if ($first->media_type === 'video')
                        <video src="{{ asset($first->media_path) }}" controls preload="metadata"></video>
                    @else
                        <img id="gallery-main-image" src="{{ asset($first->media_path) }}" alt="{{ $product->title }}">
                    @endif
                </div>
                @if ($mediaItems->count() > 1)
                    <div class="gallery-thumbnails">
                        @foreach ($mediaItems as $media)
                            <button type="button"
                                    class="gallery-thumb-btn"
                                    data-media-path="{{ asset($media->media_path) }}"
                                    data-media-type="{{ $media->media_type }}">
                                @if ($media->media_type === 'video')
                                    <video src="{{ asset($media->media_path) }}" muted></video>
                                @else
                                    <img src="{{ asset($media->media_path) }}" alt="Thumbnail">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="product-details-info card">
            <div class="product-details-header">
                <span class="category-tag"><i data-lucide="tag" aria-hidden="true"></i> {{ $product->category->name }}</span>
                <h1>{{ $product->title }}</h1>
                <div class="product-details-price">${{ number_format($product->price, 2) }}</div>
                <div class="product-status-tag status-{{ $product->status }}">
                    {{ Str::headline($product->status) }}
                </div>
            </div>

            <div class="product-meta-list">
                <div class="product-meta-item">
                    <span><i data-lucide="shield-check" aria-hidden="true"></i> Condition</span>
                    <strong>{{ Str::headline($product->condition) }}</strong>
                </div>
                @if ($product->location)
                    <div class="product-meta-item">
                        <span><i data-lucide="map-pin" aria-hidden="true"></i> Location</span>
                        <strong>{{ $product->location }}</strong>
                    </div>
                @endif
                <div class="product-meta-item">
                    <span><i data-lucide="eye" aria-hidden="true"></i> Views</span>
                    <strong>{{ $product->views_count }}</strong>
                </div>
                <div class="product-meta-item">
                    <span><i data-lucide="clock" aria-hidden="true"></i> Posted</span>
                    <strong>{{ $product->created_at->diffForHumans() }}</strong>
                </div>
            </div>

            <div class="product-actions">
                @php $isSaved = $product->isSavedBy(auth('member')->id()); @endphp
                <button class="member-button {{ $isSaved ? 'member-button--secondary' : 'member-button--primary' }}"
                        type="button"
                        data-product-save-btn="{{ $product->id }}"
                        data-product-save-url="{{ route('member.marketplace.save', $product) }}">
                    <i data-lucide="bookmark" aria-hidden="true"></i>
                    <span>{{ $isSaved ? 'Saved Item' : 'Save Product' }}</span>
                </button>

                @if ($product->member_id === auth('member')->id())
                    <a class="member-button member-button--secondary" href="{{ route('member.marketplace.edit', $product) }}">
                        <i data-lucide="pencil" aria-hidden="true"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('member.marketplace.status', $product) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="status" value="{{ $product->status === 'sold' ? 'available' : 'sold' }}">
                        <button class="member-button member-button--secondary" type="submit">
                            {{ $product->status === 'sold' ? 'Mark Available' : 'Mark as Sold' }}
                        </button>
                    </form>
                @endif
            </div>

            <div class="seller-box card">
                <h3>Seller Information</h3>
                <div class="seller-box__profile">
                    <img src="{{ $product->member->profile_photo ? asset($product->member->profile_photo) : asset('member_assets/images/default-avatar.png') }}" alt="{{ $product->member->name }}">
                    <div>
                        <strong>{{ $product->member->name }}</strong>
                        <small>Member since {{ $product->member->created_at->format('M Y') }}</small>
                    </div>
                    <a class="soft-cta" href="{{ route('member.people.show', $product->member) }}">Profile</a>
                </div>
            </div>
        </div>
    </div>

    <div class="product-description-box card">
        <h2>Description</h2>
        <p>{!! nl2br(e($product->description)) !!}</p>
    </div>

    @if ($relatedProducts->isNotEmpty())
        <div class="related-products-section">
            <h2>Similar Listings</h2>
            <div class="marketplace-grid">
                @foreach ($relatedProducts as $related)
                    @include('member.marketplace.partials.product_card', ['product' => $related])
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
