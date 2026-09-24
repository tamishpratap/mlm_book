@extends('member.layouts.app')

@section('title', 'Edit Event - '.$event->title)

@section('content')
<div class="product-create-page card">
    <header class="member-card__header">
        <div>
            <h1>Edit Event</h1>
            <p>Update event details for {{ $event->title }}</p>
        </div>
    </header>

    <form method="POST" action="{{ route('member.events.update', $event) }}" enctype="multipart/form-data" class="member-form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $event->title) }}" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ $event->category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="event_type">Location Type *</label>
                <select id="event_type" name="event_type" required>
                    <option value="offline" {{ $event->event_type === 'offline' ? 'selected' : '' }}>In-Person</option>
                    <option value="online" {{ $event->event_type === 'online' ? 'selected' : '' }}>Online</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="privacy">Privacy *</label>
                <select id="privacy" name="privacy" required>
                    <option value="public" {{ $event->privacy === 'public' ? 'selected' : '' }}>Public</option>
                    <option value="friends_only" {{ $event->privacy === 'friends_only' ? 'selected' : '' }}>Friends Only</option>
                    <option value="private" {{ $event->privacy === 'private' ? 'selected' : '' }}>Private</option>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date *</label>
                <input type="date" id="start_date" name="start_date" min="{{ now()->toDateString() }}" value="{{ old('start_date', $event->start_date->toDateString()) }}" autocomplete="off" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('H:i') : '') }}">
            </div>

            <div class="form-group">
                <label for="location_address">Address / Location</label>
                <input type="text" id="location_address" name="location_address" value="{{ old('location_address', $event->location_address) }}">
            </div>
        </div>

        <div class="form-group">
            <label for="meeting_link">Online Meeting Link</label>
            <input type="url" id="meeting_link" name="meeting_link" value="{{ old('meeting_link', $event->meeting_link) }}">
        </div>

        <div class="form-group">
            <label for="description">Full Description</label>
            <textarea id="description" name="description" rows="5">{{ old('description', $event->description) }}</textarea>
        </div>

        <div class="form-group">
            <label for="cover_photo">Change Cover Photo</label>
            <input type="file" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-actions">
            <a href="{{ route('member.events.show', $event) }}" class="member-button member-button--secondary">Cancel</a>
            <button type="submit" class="member-button member-button--primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
