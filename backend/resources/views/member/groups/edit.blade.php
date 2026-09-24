@extends('member.layouts.app')

@section('title', 'Community Settings - '.$group->name)

@section('content')
<div class="product-create-page card">
    <header class="member-card__header">
        <div>
            <h1>Edit Community Settings</h1>
            <p>Update settings for {{ $group->name }}</p>
        </div>
    </header>

    <form method="POST" action="{{ route('member.groups.update', $group) }}" enctype="multipart/form-data" class="member-form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Community Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $group->name) }}" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ $group->category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="privacy">Privacy Level *</label>
                <select id="privacy" name="privacy" required>
                    <option value="public" {{ $group->privacy === 'public' ? 'selected' : '' }}>Public</option>
                    <option value="private" {{ $group->privacy === 'private' ? 'selected' : '' }}>Private</option>
                    <option value="hidden" {{ $group->privacy === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $group->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="rules">Community Rules</label>
            <textarea id="rules" name="rules" rows="4">{{ old('rules', $group->rules) }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cover_photo">Change Cover Photo</label>
                <input type="file" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="form-group">
                <label for="logo">Change Logo</label>
                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp">
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('member.groups.show', $group) }}" class="member-button member-button--secondary">Cancel</a>
            <button type="submit" class="member-button member-button--primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
