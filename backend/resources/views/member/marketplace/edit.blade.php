@extends('member.layouts.app')

@section('title', 'Edit Listing - '.$product->title)

@section('content')
<div class="product-create-page card">
    <header class="member-card__header">
        <div>
            <h1>Edit Listing</h1>
            <p>Update your product listing details.</p>
        </div>
    </header>

    <form method="POST" action="{{ route('member.marketplace.update', $product) }}" class="member-form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $product->title) }}" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $product->category_id === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', $product->price) }}" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="condition">Condition *</label>
                <select id="condition" name="condition" required>
                    <option value="new" {{ $product->condition === 'new' ? 'selected' : '' }}>New</option>
                    <option value="like_new" {{ $product->condition === 'like_new' ? 'selected' : '' }}>Like New</option>
                    <option value="good" {{ $product->condition === 'good' ? 'selected' : '' }}>Good</option>
                    <option value="fair" {{ $product->condition === 'fair' ? 'selected' : '' }}>Fair</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="available" {{ $product->status === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="sold" {{ $product->status === 'sold' ? 'selected' : '' }}>Sold</option>
                    <option value="reserved" {{ $product->status === 'reserved' ? 'selected' : '' }}>Reserved</option>
                    <option value="hidden" {{ $product->status === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="{{ old('location', $product->location) }}">
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" rows="5" required>{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="form-actions">
            <a href="{{ route('member.marketplace.show', $product) }}" class="member-button member-button--secondary">Cancel</a>
            <button type="submit" class="member-button member-button--primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
