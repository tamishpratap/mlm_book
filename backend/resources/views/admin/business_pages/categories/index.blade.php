@extends('admin.layouts.master')
@section('title', 'Business Page Categories')
@section('page-subtitle', 'Manage taxonomy, classifications, and dynamic options for business pages.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Categories" 
            :value="$totalCount" 
            icon="layers" 
            variant="primary" 
            subtext="Configured Categories" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Active Categories" 
            :value="$activeCount" 
            icon="check-circle" 
            variant="success" 
            subtext="Available for Members" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Disabled Categories" 
            :value="$inactiveCount" 
            icon="slash" 
            variant="warning" 
            subtext="Hidden from Create Dropdowns" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Business Pages" 
            :value="$totalBusinessPages" 
            icon="briefcase" 
            variant="info" 
            subtext="Across All Categories" 
        />
    </div>
</div>

<!-- Main Categories Management Card -->
<x-admin.card title="Business Page Categories Management">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.business-pages.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Business Directory
            </a>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fa fa-plus me-1"></i> Add Category
            </button>
        </div>
    </x-slot>

    <!-- Search & Filter Bar -->
    <form action="{{ route('admin.business-pages.categories.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-5 col-md-6">
            <x-admin.form.group label="Search Categories" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search category name, slug, description..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-3 col-md-6">
            <x-admin.form.group label="Status Filter" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['active' => 'Active Only', 'inactive' => 'Disabled Only']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-4 col-md-12 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $status)
                    <a href="{{ route('admin.business-pages.categories.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Categories Data Table -->
    <x-admin.table 
        :headers="['Category Name', 'Slug / Identifier', 'Business Pages', 'Order', 'Status', 'Created Date', 'Actions']" 
        :empty="$categories->isEmpty()" 
        emptyMessage="No business page categories found matching your query."
    >
        @foreach($categories as $category)
            @php
                $usageCount = $categoryPageCounts[$category->name] ?? 0;
            @endphp
            <tr>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark fs-6">{{ $category->name }}</span>
                        @if($category->description)
                            <small class="text-muted text-truncate" style="max-width: 320px;" title="{{ $category->description }}">{{ $category->description }}</small>
                        @endif
                    </div>
                </td>
                <td>
                    <code class="text-primary fw-semibold">{{ $category->slug }}</code>
                </td>
                <td>
                    @if($usageCount > 0)
                        <a href="{{ route('admin.business-pages.index', ['category' => $category->name]) }}" class="badge bg-light-primary text-primary text-decoration-none fw-semibold p-2" title="View Business Pages in this Category">
                            <i class="fa fa-briefcase me-1"></i> {{ $usageCount }} {{ Str::plural('Page', $usageCount) }}
                        </a>
                    @else
                        <span class="badge bg-light text-muted p-2">0 Pages</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-light-secondary text-secondary">{{ $category->order }}</span>
                </td>
                <td>
                    @if($category->is_active)
                        <span class="badge bg-light-success text-success">
                            <i class="fa fa-check-circle me-1"></i> Active
                        </span>
                    @else
                        <span class="badge bg-light-warning text-warning">
                            <i class="fa fa-pause-circle me-1"></i> Disabled
                        </span>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $category->created_at ? $category->created_at->format('M d, Y') : 'System' }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <!-- Edit Button Modal Trigger -->
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}" title="Edit Category">
                            <i data-feather="edit-2" style="width: 14px; height: 14px;"></i>
                        </button>

                        <!-- Toggle Status (Enable/Disable) Form -->
                        <form action="{{ route('admin.business-pages.categories.toggle-status', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $category->is_active ? "Disable this category? (It will be hidden from Member Create/Edit dropdowns)" : "Enable this category?" }}')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-icon {{ $category->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $category->is_active ? 'Disable Category' : 'Enable Category' }}">
                                <i data-feather="{{ $category->is_active ? 'slash' : 'check' }}" style="width: 14px; height: 14px;"></i>
                            </button>
                        </form>

                        <!-- Delete Button (Safe deletion logic) -->
                        @if($usageCount === 0)
                            <form action="{{ route('admin.business-pages.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete category \'{{ addslashes($category->name) }}\'? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Unused Category">
                                    <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                </button>
                            </form>
                        @else
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reassignModal{{ $category->id }}" title="In Use ({{ $usageCount }} Pages) - Reassign / Safe Delete">
                                <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$categories" />
</x-admin.card>

<!-- ========================================== -->
<!-- MODALS SECTION                             -->
<!-- ========================================== -->

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.business-pages.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addCategoryModalLabel">
                        <i class="fa fa-plus-circle text-primary me-2"></i> Add Business Page Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_category_name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_category_name" name="name" placeholder="e.g. Affiliate MLM Solutions" required minlength="2" maxlength="255">
                        <div class="form-text">Must be unique. Automatically generates slug identifier.</div>
                    </div>

                    <div class="mb-3">
                        <label for="new_category_order" class="form-label fw-semibold">Display Order</label>
                        <input type="number" class="form-control" id="new_category_order" name="order" value="0" min="0" max="9999">
                        <div class="form-text">Lower numbers appear first in dropdowns.</div>
                    </div>

                    <div class="mb-3">
                        <label for="new_category_description" class="form-label fw-semibold">Description <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" id="new_category_description" name="description" rows="3" placeholder="Brief description of this business niche..."></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="new_category_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-semibold" for="new_category_active">Active & Available Immediately</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit & Reassign Modals per Category -->
@foreach($categories as $category)
    @php
        $usageCount = $categoryPageCounts[$category->name] ?? 0;
    @endphp

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1" aria-labelledby="editCategoryModalLabel{{ $category->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.business-pages.categories.update', $category) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="editCategoryModalLabel{{ $category->id }}">
                            <i class="fa fa-edit text-primary me-2"></i> Edit Category: {{ $category->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name_{{ $category->id }}" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name_{{ $category->id }}" name="name" value="{{ $category->name }}" required minlength="2" maxlength="255">
                            @if($usageCount > 0)
                                <div class="form-text text-info">
                                    <i class="fa fa-info-circle me-1"></i> Renaming will automatically update all {{ $usageCount }} Business Pages currently in this category.
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="edit_order_{{ $category->id }}" class="form-label fw-semibold">Display Order</label>
                            <input type="number" class="form-control" id="edit_order_{{ $category->id }}" name="order" value="{{ $category->order }}" min="0" max="9999">
                        </div>

                        <div class="mb-3">
                            <label for="edit_description_{{ $category->id }}" class="form-label fw-semibold">Description <span class="text-muted">(Optional)</span></label>
                            <textarea class="form-control" id="edit_description_{{ $category->id }}" name="description" rows="3">{{ $category->description }}</textarea>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="edit_active_{{ $category->id }}" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="edit_active_{{ $category->id }}">Active Status</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check me-1"></i> Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reassign & In-Use Protection Modal -->
    @if($usageCount > 0)
        <div class="modal fade" id="reassignModal{{ $category->id }}" tabindex="-1" aria-labelledby="reassignModalLabel{{ $category->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.business-pages.categories.reassign', $category) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold text-danger" id="reassignModalLabel{{ $category->id }}">
                                <i class="fa fa-shield text-danger me-2"></i> Category in Use ({{ $usageCount }} {{ Str::plural('Page', $usageCount) }})
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                                <i class="fa fa-exclamation-triangle mt-1"></i>
                                <div>
                                    <strong>Category Cannot Be Deleted Directly:</strong><br>
                                    '{{ $category->name }}' is actively used by <strong>{{ $usageCount }}</strong> Business Page(s). To protect data integrity and avoid orphaned records, choose an action below:
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark mb-2">Option A — Reassign Pages to Another Category:</h6>
                            <div class="mb-3">
                                <label for="target_cat_{{ $category->id }}" class="form-label fw-semibold small">Destination Category:</label>
                                <select name="target_category_id" id="target_cat_{{ $category->id }}" class="form-select" required>
                                    <option value="" disabled selected>Select destination category...</option>
                                    @foreach($allActiveCategories as $targetCat)
                                        @if($targetCat->id !== $category->id)
                                            <option value="{{ $targetCat->id }}">{{ $targetCat->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-warning w-100 mb-3" onclick="return confirm('Reassign all {{ $usageCount }} business pages to the selected category?')">
                                <i class="fa fa-exchange me-1"></i> Reassign {{ $usageCount }} Business Pages
                            </button>

                            <hr>

                            <h6 class="fw-bold text-dark mb-2">Option B — Disable Category Instead:</h6>
                            <p class="text-muted small mb-2">
                                Disabling hides the category from member creation dropdowns while preserving existing pages without moving them.
                            </p>
                            <a href="{{ route('admin.business-pages.index', ['category' => $category->name]) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fa fa-external-link me-1"></i> View Assigned Business Pages
                            </a>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

@endsection
