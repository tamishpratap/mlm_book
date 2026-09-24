@extends('admin.layouts.master')
@section('title', 'Notifications & Communication Center')
@section('page-subtitle', 'Monitor, filter, and dispatch platform notifications and broadcast announcements.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Notifications" 
            :value="$totalCount" 
            icon="bell" 
            variant="primary" 
            subtext="Platform Notifications" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Sent Today" 
            :value="$sentTodayCount" 
            icon="send" 
            variant="success" 
            subtext="Dispatched Today" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Read Notifications" 
            :value="$readCount" 
            icon="check-circle" 
            variant="info" 
            subtext="Opened by Members" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Queued Jobs" 
            :value="$queuedJobsCount" 
            icon="layers" 
            variant="warning" 
            subtext="Queue Worker Jobs" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Notification Records Queue">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.notifications.broadcast') }}" class="btn btn-sm btn-primary">
                <i class="fa fa-bullhorn me-1"></i> Send Broadcast Announcement
            </a>
            <a href="{{ route('admin.notifications.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.notifications.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Search Notifications" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Recipient, Message..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Channel" for="channel">
                <x-admin.form.select name="channel" :selected="$channel" placeholder="All Channels" :options="['in_app' => 'In-App Database', 'business' => 'Business Page Alerts']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Status" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['read' => 'Read', 'unread' => 'Unread']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-4 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $channel || $status || $dateFrom || $dateTo)
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-notifications-form" action="{{ route('admin.notifications.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-notifications">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-notifications">Select All</label>
                </div>
                <span class="text-muted small" id="selected-notifications-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="read">Mark Selected Read</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Notifications Table -->
    <x-admin.table :headers="['', 'ID', 'Channel', 'Notification Title / Message', 'Recipient', 'Status', 'Sent Date', 'Actions']" :empty="$paginatedNotifications->isEmpty()" emptyMessage="No notifications match your search or filter criteria.">
        @foreach($paginatedNotifications as $n)
            <tr>
                <td>
                    <input class="form-check-input notification-checkbox" type="checkbox" name="ids[]" value="{{ $n->id }}" form="bulk-notifications-form">
                </td>
                <td>
                    <small class="fw-bold text-primary">#{{ Str::limit($n->id, 8) }}</small>
                </td>
                <td>
                    @if($n->channel === 'In-App')
                        <x-admin.badge variant="primary" :light="true"><i class="fa fa-bell me-1"></i> In-App</x-admin.badge>
                    @else
                        <x-admin.badge variant="info" :light="true"><i class="fa fa-briefcase me-1"></i> Business</x-admin.badge>
                    @endif
                </td>
                <td>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 260px;" title="{{ $n->title }}">
                        {{ $n->title }}
                    </div>
                    <small class="text-muted text-truncate d-inline-block" style="max-width: 260px; font-size: 11px;">
                        {{ $n->message }}
                    </small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $n->recipient_avatar }}" alt="" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $n->recipient_name }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $n->recipient_user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    @if($n->is_read)
                        <x-admin.badge variant="success" :light="true">Read</x-admin.badge>
                    @else
                        <x-admin.badge variant="warning" :light="true">Unread</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $n->created_at?->format('M d, Y H:i') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.notifications.show', $n->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Inspect Notification">
                            <i data-feather="eye"></i>
                        </a>

                        @if(!$n->is_read)
                            <form action="{{ route('admin.notifications.mark-read', $n->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Mark Read">
                                    <i data-feather="check"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$paginatedNotifications" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-notifications');
        const checkboxes = document.querySelectorAll('.notification-checkbox');
        const selectedCount = document.getElementById('selected-notifications-count');
        const bulkForm = document.getElementById('bulk-notifications-form');

        function updateCount() {
            const checked = document.querySelectorAll('.notification-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.notification-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected notification(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
