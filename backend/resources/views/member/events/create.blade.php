@extends('member.layouts.app')

@section('title', 'Create New Event')

@section('content')
<div class="product-create-page card">
    <header class="member-card__header">
        <div>
            <h1>Create New Event</h1>
            <p>Organize an online or in-person community event.</p>
        </div>
    </header>

    <form method="POST" action="{{ route('member.events.store') }}" enctype="multipart/form-data" class="member-form">
        @csrf

        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. MLM Leaders Annual Summit 2026">
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
                <label for="event_type">Event Location Type *</label>
                <select id="event_type" name="event_type" required>
                    <option value="offline" selected>In-Person (Physical Location)</option>
                    <option value="online">Online (Zoom, Google Meet, Link)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="privacy">Privacy *</label>
                <select id="privacy" name="privacy" required>
                    <option value="public" selected>Public (Anyone on MLM Book)</option>
                    <option value="friends_only">Friends Only</option>
                    <option value="private">Private (Invite Only)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date *</label>
                <input type="date" id="start_date" name="start_date" min="{{ now()->toDateString() }}" value="{{ old('start_date', now()->addDays(7)->toDateString()) }}" autocomplete="off" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '18:00') }}">
            </div>

            <div class="form-group">
                <label for="location_city">City / Location</label>
                <input type="text" id="location_city" name="location_city" value="{{ old('location_city', auth('member')->user()->city) }}" placeholder="e.g. New York, NY">
            </div>
        </div>

        <div class="form-group">
            <label for="location_address">Full Venue Address (If In-Person)</label>
            <input type="text" id="location_address" name="location_address" placeholder="e.g. Grand Ballroom, Hilton Hotel, 5th Ave">
        </div>

        <div class="form-group">
            <label for="meeting_link">Online Meeting Link (Zoom, Meet, Teams)</label>
            <input type="url" id="meeting_link" name="meeting_link" placeholder="https://zoom.us/j/123456789">
        </div>

        <div class="form-group">
            <label for="short_description">Short Summary</label>
            <input type="text" id="short_description" name="short_description" placeholder="A one-line summary of what attendees will learn or experience">
        </div>

        <div class="form-group">
            <label for="description">Full Description</label>
            <textarea id="description" name="description" rows="5" placeholder="Detailed event agenda, schedule, speakers, requirements..."></textarea>
        </div>

        <div class="form-group">
            <label for="cover_photo">Event Cover Photo</label>
            <input type="file" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-actions">
            <a href="{{ route('member.events.index') }}" class="member-button member-button--secondary">Cancel</a>
            <button type="submit" class="member-button member-button--primary">Publish Event</button>
        </div>
    </form>
</div>
@endsection
