@extends('admin.layouts.master')
@section('title', 'Communities Management')
@section('page-subtitle', 'Manage, filter, and moderate enterprise platform communities.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Communities" 
            :value="$totalCount" 
            icon="globe" 
            variant="primary" 
            subtext="Platform Groups" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Active Communities" 
            :value="$activeCount" 
            icon="check-circle" 
            variant="success" 
            subtext="Live Groups" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Private Communities" 
            :value="$privateCount" 
            icon="lock" 
            variant="warning" 
            subtext="Restricted Access" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Members" 
            :value="$totalMembersCount" 
            icon="users" 
            variant="info" 
            subtext="Across All Communities" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Communities Directory">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.communities.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.communities.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Search Communities" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Name, Owner, Slug..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Visibility" for="visibility">
                <x-admin.form.select name="visibility" :selected="$visibility" placeholder="All Visibilities" :options="['public' => 'Public', 'private' => 'Private', 'invite_only' => 'Invite Only', 'secret' => 'Secret']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Status" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['active' => 'Active', 'suspended' => 'Suspended', 'hidden' => 'Hidden']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Category" for="category">
                <x-admin.form.select name="category" :selected="$category" placeholder="All Categories">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </x-admin.form.select>
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $visibility || $status || $category || $dateFrom || $dateTo)
                    <a href="{{ route('admin.communities.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-communities-form" action="{{ route('admin.communities.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-communities">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-communities">Select All</label>
                </div>
                <span class="text-muted small" id="selected-communities-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="activate">Activate Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Communities Table -->
    <x-admin.table :headers="['', 'Logo', 'Community Name', 'Owner', 'Visibility', 'Members', 'Posts', 'Requests', 'Reports', 'Status', 'Created Date', 'Actions']" :empty="$communities->isEmpty()" emptyMessage="No communities match your search or filter criteria.">
        @foreach($communities as $community)
            @php
                $logoUrl = $community->logo ? (str_starts_with($community->logo, 'http') ? $community->logo : asset($community->logo)) : asset('admin_assets/images/dashboard/1.png');
            @endphp
            <tr>
                <td>
                    <input class="form-check-input community-checkbox" type="checkbox" name="ids[]" value="{{ $community->id }}" form="bulk-communities-form">
                </td>
                <td>
                    <img class="rounded" src="{{ $logoUrl }}" alt="{{ $community->name }}" style="width: 36px; height: 36px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-dark">{{ $community->name }}</div>
                    <small class="badge bg-light-primary text-primary" style="font-size: 10px;">{{ $community->category }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $community->owner?->avatar_url }}" alt="{{ $community->owner?->name }}" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $community->owner?->name ?? 'N/A' }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $community->owner?->user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    @if($community->visibility === 'public')
                        <x-admin.badge variant="success" :light="true">Public</x-admin.badge>
                    @elseif($community->visibility === 'private')
                        <x-admin.badge variant="warning" :light="true">Private</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true">{{ ucfirst($community->visibility) }}</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="fw-bold text-dark">{{ $community->accepted_members_count }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $community->post_count }}</small>
                </td>
                <td>
                    @if($community->pending_members_count > 0)
                        <span class="badge bg-warning text-dark">{{ $community->pending_members_count }} Pending</span>
                    @else
                        <small class="text-muted">0</small>
                    @endif
                </td>
                <td>
                    @if($community->reports_count > 0)
                        <x-admin.badge variant="danger" :light="true">{{ $community->reports_count }}</x-admin.badge>
                    @else
                        <small class="text-muted">0</small>
                    @endif
                </td>
                <td>
                    @if($community->status === 'active')
                        <x-admin.badge variant="success" :light="true">Active</x-admin.badge>
                    @elseif($community->status === 'suspended')
                        <x-admin.badge variant="danger" :light="true">Suspended</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true">{{ ucfirst($community->status) }}</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $community->created_at?->format('M d, Y') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.communities.show', $community) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Community Details">
                            <i data-feather="eye"></i>
                        </a>

                        <form action="{{ route('admin.communities.status', $community) }}" method="POST" class="d-inline">
                            @csrf
                            @if($community->status === 'active')
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-warning" title="Suspend Community">
                                    <i data-feather="slash"></i>
                                </button>
                            @else
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Activate Community">
                                    <i data-feather="check-circle"></i>
                                </button>
                            @endif
                        </form>

                        <form action="{{ route('admin.communities.destroy', $community) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete Community {{ $community->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Community">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$communities" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-communities');
        const checkboxes = document.querySelectorAll('.community-checkbox');
        const selectedCount = document.getElementById('selected-communities-count');
        const bulkForm = document.getElementById('bulk-communities-form');

        function updateCount() {
            const checked = document.querySelectorAll('.community-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.community-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected community(ies)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
