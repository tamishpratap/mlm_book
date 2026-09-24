@extends('member.layouts.app')

@section('title', 'Create New Community')

@section('content')
<div class="product-create-page card">
    <header class="member-card__header">
        <div>
            <h1>Create New Community</h1>
            <p>Build a community for your brand, team, or interest group.</p>
        </div>
    </header>

    <form method="POST" action="{{ route('member.groups.store') }}" enctype="multipart/form-data" class="member-form">
        @csrf

        <div class="form-group">
            <label for="name">Community Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Crypto Traders Club">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="privacy">Privacy Level *</label>
                <select id="privacy" name="privacy" required>
                    <option value="public" selected>Public (Anyone can see members and posts)</option>
                    <option value="private">Private (Only members can see posts; approval required)</option>
                    <option value="hidden">Hidden (Only members can find community)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="What is this community about?"></textarea>
        </div>

        <div class="form-group">
            <label for="rules">Community Rules</label>
            <textarea id="rules" name="rules" rows="4" placeholder="Rule 1: Be respectful&#10;Rule 2: No spamming"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cover_photo">Community Cover Photo</label>
                <input type="file" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="form-group">
                <label for="logo">Community Logo / Icon</label>
                <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp">
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('member.groups.index') }}" class="member-button member-button--secondary">Cancel</a>
            <button type="submit" class="member-button member-button--primary">Create Community</button>
        </div>
    </form>
</div>
@endsection
