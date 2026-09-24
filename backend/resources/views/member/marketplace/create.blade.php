@extends('member.layouts.app')

@section('title', 'Create Product Listing')

@section('content')
<div class="product-create-page card">
    <header class="member-card__header">
        <div>
            <h1>Create New Product Listing</h1>
            <p>Fill out the product details to sell on Facebook Marketplace.</p>
        </div>
    </header>

    <form method="POST" action="{{ route('member.marketplace.store') }}" enctype="multipart/form-data" class="member-form">
        @csrf

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. iPhone 15 Pro Max 256GB">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price') }}" required placeholder="0.00">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="condition">Condition *</label>
                <select id="condition" name="condition" required>
                    <option value="new">New</option>
                    <option value="like_new" selected>Like New</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                </select>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="{{ old('location', auth('member')->user()->city) }}" placeholder="e.g. New York, NY">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="5" required placeholder="Describe what you're selling, item specs, reason for selling..."></textarea>
        </div>

        <div class="form-group">
            <label for="images">Product Images (Up to 10 photos)</label>
            <input type="file" id="images" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-group">
            <label for="videos">Product Videos (Up to 2 videos)</label>
            <input type="file" id="videos" name="videos[]" multiple accept=".mp4,.webm,.mov">
        </div>

        <div class="form-actions">
            <a href="{{ route('member.marketplace.index') }}" class="member-button member-button--secondary">Cancel</a>
            <button type="submit" class="member-button member-button--primary">Publish Listing</button>
        </div>
    </form>
</div>
@endsection
