@extends('admin.layouts.master')
@section('title', 'Marketplace Management')
@section('page-subtitle', 'Monitor, filter, and moderate platform marketplace products.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Products" 
            :value="$totalCount" 
            icon="shopping-bag" 
            variant="primary" 
            subtext="Marketplace Listings" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Active Listings" 
            :value="$activeCount" 
            icon="check-circle" 
            variant="success" 
            subtext="Available for Sale" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Featured Products" 
            :value="$featuredCount" 
            icon="star" 
            variant="warning" 
            subtext="Promoted Items" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Sellers" 
            :value="$totalSellersCount" 
            icon="users" 
            variant="info" 
            subtext="Unique Sellers" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Marketplace Directory">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.marketplace.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.marketplace.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Search Products" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Title, Seller, Brand..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Category" for="category_id">
                <x-admin.form.select name="category_id" :selected="$categoryId" placeholder="All Categories">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </x-admin.form.select>
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Status" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['available' => 'Available', 'sold' => 'Sold', 'hidden' => 'Hidden']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Condition" for="condition">
                <x-admin.form.select name="condition" :selected="$condition" placeholder="All Conditions" :options="['new' => 'Brand New', 'like_new' => 'Like New', 'good' => 'Good', 'fair' => 'Fair']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $categoryId || $status || $condition || $isFeatured || $dateFrom || $dateTo)
                    <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-marketplace-form" action="{{ route('admin.marketplace.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-products">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-products">Select All</label>
                </div>
                <span class="text-muted small" id="selected-products-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="feature">Feature Selected</option>
                    <option value="unfeature">Unfeature Selected</option>
                    <option value="hide">Hide Selected</option>
                    <option value="available">Mark Available</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Marketplace Products Table -->
    <x-admin.table :headers="['', 'Thumbnail', 'Product Title', 'Seller', 'Price', 'Condition', 'Featured', 'Reports', 'Status', 'Created Date', 'Actions']" :empty="$products->isEmpty()" emptyMessage="No products match your search or filter criteria.">
        @foreach($products as $product)
            @php
                $firstMedia = $product->media->first();
                $thumbUrl = $firstMedia ? (str_starts_with($firstMedia->media_path, 'http') ? $firstMedia->media_path : asset($firstMedia->media_path)) : asset('admin_assets/images/dashboard/1.png');
            @endphp
            <tr>
                <td>
                    <input class="form-check-input product-checkbox" type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulk-marketplace-form">
                </td>
                <td>
                    <img class="rounded" src="{{ $thumbUrl }}" alt="{{ $product->title }}" style="width: 40px; height: 40px; object-fit: cover;">
                </td>
                <td>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 220px;">{{ $product->title }}</div>
                    <small class="badge bg-light-primary text-primary" style="font-size: 10px;">{{ $product->category?->name ?? 'Uncategorized' }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $product->member?->avatar_url }}" alt="{{ $product->member?->name }}" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $product->member?->name ?? 'N/A' }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $product->member?->user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="fw-bold text-success">${{ number_format($product->price, 2) }}</span>
                </td>
                <td>
                    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $product->condition)) }}</small>
                </td>
                <td>
                    @if($product->is_featured)
                        <span class="badge bg-warning text-dark"><i class="fa fa-star me-1"></i> Featured</span>
                    @else
                        <small class="text-muted">Standard</small>
                    @endif
                </td>
                <td>
                    @if($product->reports_count > 0)
                        <x-admin.badge variant="danger" :light="true">{{ $product->reports_count }}</x-admin.badge>
                    @else
                        <small class="text-muted">0</small>
                    @endif
                </td>
                <td>
                    @if($product->status === 'available')
                        <x-admin.badge variant="success" :light="true">Available</x-admin.badge>
                    @elseif($product->status === 'sold')
                        <x-admin.badge variant="secondary" :light="true">Sold</x-admin.badge>
                    @else
                        <x-admin.badge variant="warning" :light="true">{{ ucfirst($product->status) }}</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $product->created_at?->format('M d, Y') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.marketplace.show', $product) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Product Details">
                            <i data-feather="eye"></i>
                        </a>

                        <form action="{{ route('admin.marketplace.toggle-featured', $product) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-icon {{ $product->is_featured ? 'btn-warning' : 'btn-outline-warning' }}" title="{{ $product->is_featured ? 'Unfeature Product' : 'Feature Product' }}">
                                <i data-feather="star"></i>
                            </button>
                        </form>

                        <form action="{{ route('admin.marketplace.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete Product {{ $product->title }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Product">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$products" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-products');
        const checkboxes = document.querySelectorAll('.product-checkbox');
        const selectedCount = document.getElementById('selected-products-count');
        const bulkForm = document.getElementById('bulk-marketplace-form');

        function updateCount() {
            const checked = document.querySelectorAll('.product-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.product-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected product(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
