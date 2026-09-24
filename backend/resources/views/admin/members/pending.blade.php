@extends('admin.layouts.master')
@section('title', 'Pending Member Requests')
@section('page-subtitle', 'Review, approve, or reject unverified member registration requests.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <a href="{{ route('admin.members.index') }}" class="text-decoration-none">
            <x-admin.stat-card 
                title="Total Members" 
                :value="$totalCount" 
                icon="users" 
                variant="primary" 
                subtext="All System Records" 
            />
        </a>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <a href="{{ route('admin.members.index') }}" class="text-decoration-none">
            <x-admin.stat-card 
                title="Active Members" 
                :value="$activeCount" 
                icon="check-circle" 
                variant="success" 
                subtext="Verified & Unblocked" 
            />
        </a>
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Pending Requests" 
            :value="$pendingCount" 
            icon="clock" 
            variant="warning" 
            subtext="Awaiting Action" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <a href="{{ route('admin.members.blocked') }}" class="text-decoration-none">
            <x-admin.stat-card 
                title="Blocked Members" 
                :value="$blockedCount" 
                icon="slash" 
                variant="secondary" 
                subtext="Suspended Accounts" 
            />
        </a>
    </div>
</div>

<!-- Search, Filter & Bulk Actions Container -->
<x-admin.card title="Pending Registration Queue">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.members.export', ['type' => 'pending']) }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.members.pending') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-5 col-md-6">
            <x-admin.form.group label="Search Pending Requests" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search by ID, Name, Email, Phone..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Country" for="country">
                <x-admin.form.select name="country" :selected="$country" placeholder="All Countries">
                    @foreach($countries as $c)
                        <option value="{{ $c }}" {{ $country == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </x-admin.form.select>
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Joined From" for="date_from">
                <x-admin.form.input type="date" name="date_from" :value="$dateFrom" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $country || $dateFrom || $dateTo)
                    <a href="{{ route('admin.members.pending') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-form" action="{{ route('admin.members.bulk-action') }}" method="POST">
        @csrf
        
        <!-- Bulk Action Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-members">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-members">Select All</label>
                </div>
                <span class="text-muted small" id="selected-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 170px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="approve">Approve Selected</option>
                    <option value="reject">Reject Selected</option>
                    <option value="block">Block Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Pending Members Table -->
    <x-admin.table :headers="['', 'Avatar', 'Member ID', 'Name & Email', 'Phone', 'Location', 'Registered On', 'Actions']" :empty="$members->isEmpty()" emptyMessage="No pending member requests awaiting review.">
        @foreach($members as $member)
            <tr>
                <td>
                    <input class="form-check-input member-checkbox" type="checkbox" name="ids[]" value="{{ $member->id }}" form="bulk-form">
                </td>
                <td>
                    <img class="rounded-circle" src="{{ $member->avatar_url }}" alt="{{ $member->name }}" style="width: 38px; height: 38px; object-fit: cover;">
                </td>
                <td>
                    <span class="fw-bold text-primary">{{ $member->user_id }}</span>
                </td>
                <td>
                    <div class="fw-bold text-dark">{{ $member->name }}</div>
                    <small class="text-muted">{{ $member->email }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $member->phone ?? 'N/A' }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ implode(', ', array_filter([$member->city, $member->country])) ?: 'N/A' }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $member->created_at?->format('M d, Y H:i') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <!-- Approve Action -->
                        <form action="{{ route('admin.members.approve', $member) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" title="Approve Request">
                                <i data-feather="check" style="width: 14px; height: 14px;"></i> Approve
                            </button>
                        </form>

                        <!-- Reject Action -->
                        <form action="{{ route('admin.members.reject', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject verification for member {{ $member->name }}?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Reject Request">
                                <i data-feather="x" style="width: 14px; height: 14px;"></i>
                            </button>
                        </form>

                        <!-- Block Action -->
                        <form action="{{ route('admin.members.block', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Block member {{ $member->name }}?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-secondary" title="Block Member">
                                <i data-feather="slash" style="width: 14px; height: 14px;"></i>
                            </button>
                        </form>

                        <a href="{{ route('admin.members.show', $member) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Inspect Profile">
                            <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Global Pagination -->
    <x-admin.pagination :paginator="$members" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-members');
        const checkboxes = document.querySelectorAll('.member-checkbox');
        const selectedCount = document.getElementById('selected-count');
        const bulkForm = document.getElementById('bulk-form');

        function updateCount() {
            const checked = document.querySelectorAll('.member-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.member-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected request(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
