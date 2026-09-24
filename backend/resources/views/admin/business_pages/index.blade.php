@extends('admin.layouts.master')
@section('title', 'Business Pages Management')
@section('page-subtitle', 'Manage, verify, and moderate enterprise business pages.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Business Pages" 
            :value="$totalCount" 
            icon="briefcase" 
            variant="primary" 
            subtext="Registered Pages" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Verified Pages" 
            :value="$verifiedCount" 
            icon="check-circle" 
            variant="success" 
            subtext="Verified Identity" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Pending Verification" 
            :value="$pendingVerificationCount" 
            icon="clock" 
            variant="warning" 
            subtext="Applications Queue" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Followers" 
            :value="$totalFollowersCount" 
            icon="users" 
            variant="info" 
            subtext="Across All Pages" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Business Pages Directory">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.business-pages.categories.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-tags me-1"></i> Manage Categories
            </a>
            <a href="{{ route('admin.business-pages.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.business-pages.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Search Pages" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Name, Username, Email..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Verification" for="verification_status">
                <x-admin.form.select name="verification_status" :selected="$verificationStatus" placeholder="All Verification" :options="['verified' => 'Verified Only', 'pending' => 'Pending Verification', 'unverified' => 'Unverified']" />
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
                @if($search || $verificationStatus || $status || $category || $dateFrom || $dateTo)
                    <a href="{{ route('admin.business-pages.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-pages-form" action="{{ route('admin.business-pages.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-pages">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-pages">Select All</label>
                </div>
                <span class="text-muted small" id="selected-pages-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="verify">Mark Verified</option>
                    <option value="unverify">Mark Unverified</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="activate">Activate Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Business Pages Table -->
    <x-admin.table :headers="['', 'Logo', 'Business Name', 'Owner', 'Followers', 'Posts', 'Reviews', 'Verification', 'Status', 'Created Date', 'Actions']" :empty="$businessPages->isEmpty()" emptyMessage="No business pages match your search or filter criteria.">
        @foreach($businessPages as $page)
            @php
                $logoUrl = $page->logo ? (str_starts_with($page->logo, 'http') ? $page->logo : asset($page->logo)) : asset('admin_assets/images/dashboard/1.png');
            @endphp
            <tr>
                <td>
                    <input class="form-check-input page-checkbox" type="checkbox" name="ids[]" value="{{ $page->id }}" form="bulk-pages-form">
                </td>
                <td>
                    <img class="rounded" src="{{ $logoUrl }}" alt="{{ $page->page_name }}" style="width: 36px; height: 36px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-dark">{{ $page->page_name }}</div>
                    <small class="badge bg-light-primary text-primary" style="font-size: 10px;">{{ $page->category }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $page->owner?->avatar_url }}" alt="{{ $page->owner?->name }}" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $page->owner?->name ?? 'N/A' }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $page->owner?->user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <small class="fw-bold text-dark">{{ $page->accepted_followers_count }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $page->posts_count }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $page->reviews_count }}</small>
                </td>
                <td>
                    @if($page->is_verified)
                        <x-admin.badge variant="success" :light="true">Verified</x-admin.badge>
                    @elseif($page->isVerificationPending())
                        <x-admin.badge variant="warning" :light="true">Pending</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true">Unverified</x-admin.badge>
                    @endif
                </td>
                <td>
                    @if($page->status === 'active')
                        <x-admin.badge variant="success" :light="true">Active</x-admin.badge>
                    @elseif($page->status === 'suspended')
                        <x-admin.badge variant="danger" :light="true">Suspended</x-admin.badge>
                    @else
                        <x-admin.badge variant="secondary" :light="true">{{ ucfirst($page->status) }}</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $page->created_at?->format('M d, Y') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.business-pages.show', $page) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Page Details">
                            <i data-feather="eye"></i>
                        </a>

                        <form action="{{ route('admin.business-pages.verification', $page) }}" method="POST" class="d-inline">
                            @csrf
                            @if($page->is_verified)
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-warning" title="Revoke Verification">
                                    <i data-feather="x-circle"></i>
                                </button>
                            @else
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Mark Verified">
                                    <i data-feather="check-circle"></i>
                                </button>
                            @endif
                        </form>

                        <form action="{{ route('admin.business-pages.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete Business Page {{ $page->page_name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Page">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$businessPages" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-pages');
        const checkboxes = document.querySelectorAll('.page-checkbox');
        const selectedCount = document.getElementById('selected-pages-count');
        const bulkForm = document.getElementById('bulk-pages-form');

        function updateCount() {
            const checked = document.querySelectorAll('.page-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.page-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected page(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
