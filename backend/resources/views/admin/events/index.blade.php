@extends('admin.layouts.master')
@section('title', 'Events Management')
@section('page-subtitle', 'Monitor, filter, and moderate enterprise platform events.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Events" 
            :value="$totalCount" 
            icon="calendar" 
            variant="primary" 
            subtext="Platform Events" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Upcoming Events" 
            :value="$upcomingCount" 
            icon="clock" 
            variant="success" 
            subtext="Scheduled Future Events" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Ongoing Events" 
            :value="$ongoingCount" 
            icon="activity" 
            variant="warning" 
            subtext="Currently Live" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total RSVPs" 
            :value="$totalResponsesCount" 
            icon="users" 
            variant="info" 
            subtext="Member Responses" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Events Directory">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.events.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.events.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Search Events" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Title, Organizer, City..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Timeframe" for="timeframe">
                <x-admin.form.select name="timeframe" :selected="$timeframe" placeholder="All Timeframes" :options="['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Status" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['published' => 'Published', 'cancelled' => 'Cancelled', 'hidden' => 'Hidden']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Event Type" for="event_type">
                <x-admin.form.select name="event_type" :selected="$eventType" placeholder="All Types" :options="['offline' => 'Offline / Venue', 'online' => 'Online Stream']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $timeframe || $status || $eventType || $category || $dateFrom || $dateTo)
                    <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-events-form" action="{{ route('admin.events.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-events">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-events">Select All</label>
                </div>
                <span class="text-muted small" id="selected-events-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="cancel">Cancel Selected</option>
                    <option value="publish">Publish Selected</option>
                    <option value="hide">Hide Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Events Table -->
    <x-admin.table :headers="['', 'Banner', 'Event Title', 'Organizer', 'Type', 'Start Date', 'Location / Link', 'RSVPs', 'Status', 'Actions']" :empty="$events->isEmpty()" emptyMessage="No events match your search or filter criteria.">
        @foreach($events as $event)
            @php
                $bannerUrl = $event->banner ? (str_starts_with($event->banner, 'http') ? $event->banner : asset($event->banner)) : ($event->cover_photo ? (str_starts_with($event->cover_photo, 'http') ? $event->cover_photo : asset($event->cover_photo)) : asset('admin_assets/images/dashboard/1.png'));
            @endphp
            <tr>
                <td>
                    <input class="form-check-input event-checkbox" type="checkbox" name="ids[]" value="{{ $event->id }}" form="bulk-events-form">
                </td>
                <td>
                    <img class="rounded" src="{{ $bannerUrl }}" alt="{{ $event->title }}" style="width: 40px; height: 40px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 220px;">{{ $event->title }}</div>
                    <small class="badge bg-light-primary text-primary" style="font-size: 10px;">{{ $event->category }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $event->organizer?->avatar_url }}" alt="{{ $event->organizer?->name }}" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $event->organizer?->name ?? 'N/A' }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $event->organizer?->user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    @if($event->event_type === 'online')
                        <x-admin.badge variant="info" :light="true">Online</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true">Offline</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="fw-bold text-dark">{{ $event->start_date?->format('M d, Y') }}</small>
                </td>
                <td>
                    <small class="text-muted text-truncate d-inline-block" style="max-width: 150px;">
                        {{ $event->event_type === 'online' ? ($event->meeting_link ?: 'Online Link') : (implode(', ', array_filter([$event->location_city, $event->location_country])) ?: 'Venue') }}
                    </small>
                </td>
                <td>
                    <small class="fw-bold text-primary"><i class="fa fa-users me-1"></i> {{ $event->responses_count }}</small>
                </td>
                <td>
                    @if($event->status === 'published')
                        <x-admin.badge variant="success" :light="true">Published</x-admin.badge>
                    @elseif($event->status === 'cancelled')
                        <x-admin.badge variant="danger" :light="true">Cancelled</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true">{{ ucfirst($event->status) }}</x-admin.badge>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Event Details">
                            <i data-feather="eye"></i>
                        </a>

                        <form action="{{ route('admin.events.status', $event) }}" method="POST" class="d-inline">
                            @csrf
                            @if($event->status === 'published')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-warning" title="Cancel Event">
                                    <i data-feather="slash"></i>
                                </button>
                            @else
                                <input type="hidden" name="status" value="published">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Publish Event">
                                    <i data-feather="check-circle"></i>
                                </button>
                            @endif
                        </form>

                        <form action="{{ route('admin.events.destroy', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete Event {{ $event->title }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Event">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$events" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-events');
        const checkboxes = document.querySelectorAll('.event-checkbox');
        const selectedCount = document.getElementById('selected-events-count');
        const bulkForm = document.getElementById('bulk-events-form');

        function updateCount() {
            const checked = document.querySelectorAll('.event-checkbox:checked').length;
            if (selectedCount) selectedCount.textContent = checked + ' selected';
            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && checked === checkboxes.length;
                selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateCount();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateCount);
        });

        if (bulkForm) {
            bulkForm.addEventListener('submit', function (e) {
                const checked = document.querySelectorAll('.event-checkbox:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one record.');
                    return false;
                }
                const actionSelect = bulkForm.querySelector('select[name="action"]');
                if (!actionSelect || !actionSelect.value) {
                    e.preventDefault();
                    alert('Please select a bulk action.');
                    return false;
                }
                if (!confirm('Apply bulk action to ' + checked.length + ' selected event(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
