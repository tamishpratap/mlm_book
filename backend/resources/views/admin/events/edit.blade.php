@extends('admin.layouts.master')
@section('title', 'Edit Event: ' . $event->title)
@section('page-subtitle', 'Modify event details, scheduling, venue location, online access, and banner.')

@section('content')
<x-admin.card title="Edit Event: {{ $event->title }}">
    <x-slot name="headerAction">
        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i data-feather="arrow-left" style="width: 14px; height: 14px;"></i> Back to Event Detail
        </a>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="fa fa-exclamation-triangle me-1"></i> Please fix the following errors:</h6>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- SECTION 1: EVENT IDENTITY & TIMEFRAME -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-calendar me-1"></i> 1. Event Identity & Scheduling
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin.form.group label="Event Title" for="title" :required="true">
                        <x-admin.form.input name="title" :value="$event->title" placeholder="e.g. Blockchain Projects Workshop" required />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="Category" for="category" :required="true">
                        <x-admin.form.select name="category" :selected="$event->category" required>
                            @if(!in_array($event->category, $categories) && filled($event->category))
                                <option value="{{ $event->category }}" selected>{{ $event->category }}</option>
                            @endif
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category', $event->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="Status" for="status" :required="true">
                        <x-admin.form.select name="status" :selected="$event->status" required>
                            @foreach($statuses as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $event->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Event Type" for="event_type" :required="true">
                        <x-admin.form.select name="event_type" :selected="$event->event_type" required>
                            @foreach($eventTypes as $val => $label)
                                <option value="{{ $val }}" {{ old('event_type', $event->event_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Privacy" for="privacy" :required="true">
                        <x-admin.form.select name="privacy" :selected="$event->privacy" required>
                            @foreach($privacies as $val => $label)
                                <option value="{{ $val }}" {{ old('privacy', $event->privacy) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Max Capacity (Guests)" for="max_guests">
                        <x-admin.form.input type="number" name="max_guests" :value="$event->max_guests" placeholder="e.g. 100 (leave blank for unlimited)" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="Start Date" for="start_date" :required="true">
                        <x-admin.form.input type="date" name="start_date" :value="$event->start_date?->format('Y-m-d')" required />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="Start Time" for="start_time">
                        <x-admin.form.input type="time" name="start_time" :value="$event->start_time" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="End Date" for="end_date">
                        <x-admin.form.input type="date" name="end_date" :value="$event->end_date?->format('Y-m-d')" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="End Time" for="end_time">
                        <x-admin.form.input type="time" name="end_time" :value="$event->end_time" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-12">
                    <x-admin.form.group label="Short Summary / Tagline" for="short_description">
                        <x-admin.form.input name="short_description" :value="$event->short_description" placeholder="Brief one-line highlight of the event..." />
                    </x-admin.form.group>
                </div>

                <div class="col-md-12">
                    <x-admin.form.group label="Detailed Event Description" for="description">
                        <x-admin.form.textarea name="description" :value="$event->description" rows="4" placeholder="Full agenda, schedule, speakers, and topics..." />
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 2: LOCATION & ACCESS DETAILS -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-map-marker me-1"></i> 2. Venue Location & Online Access
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin.form.group label="Venue Street Address" for="location_address">
                        <x-admin.form.input name="location_address" :value="$event->location_address" placeholder="e.g. Hall A, 123 Tech Park" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="City" for="location_city">
                        <x-admin.form.input name="location_city" :value="$event->location_city" placeholder="e.g. New York" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-3">
                    <x-admin.form.group label="State / Country" for="location_country">
                        <x-admin.form.input name="location_country" :value="$event->location_country" placeholder="e.g. United States" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Google Maps URL" for="google_maps_link">
                        <x-admin.form.input name="google_maps_link" :value="$event->google_maps_link" placeholder="https://maps.google.com/..." />
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Online Meeting Link (Zoom / Google Meet / Teams)" for="meeting_link">
                        <x-admin.form.input name="meeting_link" :value="$event->meeting_link" placeholder="https://meet.google.com/..." />
                    </x-admin.form.group>
                </div>

                <div class="col-md-2">
                    <x-admin.form.group label="Passcode / Password" for="meeting_password">
                        <x-admin.form.input name="meeting_password" :value="$event->meeting_password" placeholder="e.g. 123456" />
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 3: MEDIA & BANNER -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-picture-o me-1"></i> 3. Event Banner / Cover Photo
            </h6>
            @php
                $bannerPath = $event->banner ?: $event->cover_photo;
            @endphp
            <div class="card border p-3 bg-light">
                <label class="form-label fw-bold text-dark mb-2">Event Cover / Banner Image (Wide, Max 5MB)</label>
                <div class="d-flex align-items-center gap-3 mb-3">
                    @if($bannerPath && file_exists(public_path($bannerPath)))
                        <img src="{{ asset($bannerPath) }}" alt="Banner" class="rounded border shadow-sm" style="width: 140px; height: 80px; object-fit: cover;">
                        <div>
                            <div class="small fw-semibold text-dark">Current Banner Image</div>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="remove_banner" value="1" id="remove_banner">
                                <label class="form-check-label text-danger small" for="remove_banner">Remove existing banner</label>
                            </div>
                        </div>
                    @else
                        <div class="rounded border bg-white shadow-sm d-flex align-items-center justify-content-center text-muted" style="width: 140px; height: 80px; font-size: 24px;">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <div class="small text-muted">No custom banner uploaded yet.</div>
                    @endif
                </div>
                <input type="file" name="banner" class="form-control {{ $errors->has('banner') ? 'is-invalid' : '' }}" accept="image/jpeg,image/png,image/webp,image/jpg">
                @error('banner')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- FORM ACTION BUTTONS -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
            <a href="{{ route('admin.events.show', $event) }}" class="btn btn-outline-secondary px-4">
                <i class="fa fa-times me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-1">
                <i data-feather="save" style="width: 15px; height: 15px;"></i> Save Changes
            </button>
        </div>
    </form>
</x-admin.card>
@endsection
