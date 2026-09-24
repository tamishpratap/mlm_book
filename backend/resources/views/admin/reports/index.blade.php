@extends('admin.layouts.master')
@section('title', 'Reports & Moderation Center')
@section('page-subtitle', 'Consolidated enterprise queue for platform reports & moderation actions.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Reports" 
            :value="$totalCount" 
            icon="alert-triangle" 
            variant="danger" 
            subtext="All Flagged Items" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Post Reports" 
            :value="$postCount" 
            icon="file-text" 
            variant="primary" 
            subtext="Feed Publications" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Community Reports" 
            :value="$commCount" 
            icon="globe" 
            variant="warning" 
            subtext="Groups & Members" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Marketplace Reports" 
            :value="$productCount" 
            icon="shopping-bag" 
            variant="info" 
            subtext="Product Listings" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Unified Reports Queue">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.reports.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.reports.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Search Reports" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Reporter, Reason, Title..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Source Module" for="source_type">
                <x-admin.form.select name="source_type" :selected="$sourceType" placeholder="All Report Sources" :options="['post' => 'Posts', 'community' => 'Communities', 'product' => 'Marketplace Products', 'business_review' => 'Business Reviews']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Status" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['pending' => 'Pending Queue', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-4 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $sourceType || $status || $dateFrom || $dateTo)
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-reports-form" action="{{ route('admin.reports.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-reports">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-reports">Select All</label>
                </div>
                <span class="text-muted small" id="selected-reports-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="resolve">Mark Resolved</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Reports Queue Table -->
    <x-admin.table :headers="['', 'Report ID', 'Source', 'Target Content', 'Reporter', 'Reason', 'Status', 'Date', 'Actions']" :empty="$paginatedReports->isEmpty()" emptyMessage="No reports match your search or filter criteria.">
        @foreach($paginatedReports as $report)
            <tr>
                <td>
                    <input class="form-check-input report-checkbox" type="checkbox" name="items[]" value="{{ $report->type }}_{{ $report->id }}" form="bulk-reports-form">
                </td>
                <td>
                    <span class="fw-bold text-primary">#{{ $report->id }}</span>
                </td>
                <td>
                    @if($report->type === 'post')
                        <x-admin.badge variant="primary" :light="true"><i class="fa fa-file-text me-1"></i> Post</x-admin.badge>
                    @elseif($report->type === 'community')
                        <x-admin.badge variant="warning" :light="true"><i class="fa fa-globe me-1"></i> Community</x-admin.badge>
                    @elseif($report->type === 'product')
                        <x-admin.badge variant="info" :light="true"><i class="fa fa-shopping-bag me-1"></i> Product</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true"><i class="fa fa-star me-1"></i> Review</x-admin.badge>
                    @endif
                </td>
                <td>
                    <div class="text-truncate fw-bold text-dark" style="max-width: 260px;" title="{{ $report->target_title }}">
                        {{ $report->target_title }}
                    </div>
                    <small class="text-muted" style="font-size: 11px;">Author: {{ $report->target_owner }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $report->reporter_avatar }}" alt="" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $report->reporter_name }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $report->reporter_user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-danger text-uppercase" style="font-size: 10px;">{{ $report->reason }}</span>
                </td>
                <td>
                    @if(strtolower($report->status) === 'resolved')
                        <x-admin.badge variant="success" :light="true">Resolved</x-admin.badge>
                    @elseif(strtolower($report->status) === 'reviewed')
                        <x-admin.badge variant="info" :light="true">Reviewed</x-admin.badge>
                    @else
                        <x-admin.badge variant="warning" :light="true">Pending</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $report->created_at?->format('M d, Y') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.reports.show', ['type' => $report->type, 'id' => $report->id]) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Inspect Report">
                            <i data-feather="eye"></i>
                        </a>

                        @if(strtolower($report->status) !== 'resolved')
                            <form action="{{ route('admin.reports.status', ['type' => $report->type, 'id' => $report->id]) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Mark Resolved">
                                    <i data-feather="check-circle"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$paginatedReports" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-reports');
        const checkboxes = document.querySelectorAll('.report-checkbox');
        const selectedCount = document.getElementById('selected-reports-count');
        const bulkForm = document.getElementById('bulk-reports-form');

        function updateCount() {
            const checked = document.querySelectorAll('.report-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.report-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected report(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
