@extends('admin.layouts.master')
@section('title', 'Stories Management')
@section('page-subtitle', 'Inspect, monitor, and manage platform member stories.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Stories" 
            :value="$totalCount" 
            icon="clock" 
            variant="primary" 
            subtext="Platform Stories" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Active Stories" 
            :value="$activeCount" 
            icon="zap" 
            variant="success" 
            subtext="Currently Live" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Expired Stories" 
            :value="$expiredCount" 
            icon="slash" 
            variant="warning" 
            subtext="Past 24h Window" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Image Stories" 
            :value="$imageCount" 
            icon="image" 
            variant="info" 
            subtext="Photos & Graphics" 
        />
    </div>
</div>

<!-- Search, Filter & Data Table Container -->
<x-admin.card title="Stories Directory">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.stories.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.stories.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <x-admin.form.group label="Search Stories" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Author, Caption..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Expiration Status" for="status">
                <x-admin.form.select name="status" :selected="$status" placeholder="All Statuses" :options="['active' => 'Active (Live)', 'expired' => 'Expired']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Media Type" for="media_type">
                <x-admin.form.select name="media_type" :selected="$mediaType" placeholder="All Media Types" :options="['image' => 'Image', 'video' => 'Video']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Created From" for="date_from">
                <x-admin.form.input type="date" name="date_from" :value="$dateFrom" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6 d-flex align-items-end mb-3">
            <div class="d-flex gap-2 w-100">
                <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter</x-admin.button>
                @if($search || $status || $mediaType || $dateFrom || $dateTo)
                    <a href="{{ route('admin.stories.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-stories-form" action="{{ route('admin.stories.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Selection Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-stories">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-stories">Select All</label>
                </div>
                <span class="text-muted small" id="selected-stories-count">0 selected</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width: 160px;" required>
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
            </div>
        </div>
    </form>

    <!-- Stories Table -->
    <x-admin.table :headers="['', 'Story ID', 'Author', 'Caption Preview', 'Media', 'Views', 'Reactions', 'Created Time', 'Expires Time', 'Status', 'Actions']" :empty="$stories->isEmpty()" emptyMessage="No stories match your search or filter criteria.">
        @foreach($stories as $story)
            @php
                $isLive = $story->expires_at > now();
                $mediaUrl = str_starts_with($story->media_path, 'http') ? $story->media_path : asset($story->media_path);
            @endphp
            <tr>
                <td>
                    <input class="form-check-input story-checkbox" type="checkbox" name="ids[]" value="{{ $story->id }}" form="bulk-stories-form">
                </td>
                <td>
                    <span class="fw-bold text-primary">#{{ $story->id }}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $story->member?->avatar_url }}" alt="{{ $story->member?->name }}" style="width: 34px; height: 34px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small">{{ $story->member?->name ?? 'Deleted Member' }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $story->member?->user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 220px;" title="{{ $story->caption }}">
                        {{ $story->caption ?: '[No Caption]' }}
                    </div>
                </td>
                <td>
                    @if($story->isImage())
                        <x-admin.badge variant="info" :light="true"><i class="fa fa-image me-1"></i> Image</x-admin.badge>
                    @elseif($story->isVideo())
                        <x-admin.badge variant="secondary" :light="true"><i class="fa fa-video-camera me-1"></i> Video</x-admin.badge>
                    @endif
                </td>
                <td>
                    <small class="text-muted"><i class="fa fa-eye me-1 text-primary"></i> <strong>{{ $story->views_count }}</strong></small>
                </td>
                <td>
                    <small class="text-muted"><i class="fa fa-heart me-1 text-danger"></i> <strong>{{ $story->reactions_count + $story->likes_count }}</strong></small>
                </td>
                <td>
                    <small class="text-muted">{{ $story->created_at?->format('M d, H:i') }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $story->expires_at?->format('M d, H:i') }}</small>
                </td>
                <td>
                    @if($isLive)
                        <x-admin.badge variant="success" :light="true">Active (Live)</x-admin.badge>
                    @else
                        <x-admin.badge variant="warning" :light="true">Expired</x-admin.badge>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <!-- Story Viewer Button -->
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#storyViewerModal-{{ $story->id }}" title="View Story">
                            <i data-feather="play-circle"></i>
                        </button>

                        <a href="{{ route('admin.stories.show', $story) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Story Details & Analytics">
                            <i data-feather="eye"></i>
                        </a>

                        <form action="{{ route('admin.stories.destroy', $story) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete Story #{{ $story->id }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Story">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Story Viewer Modal -->
                    <x-admin.modal id="storyViewerModal-{{ $story->id }}" title="Admin Story Viewer" size="md">
                        <div class="text-center bg-dark rounded p-3 position-relative">
                            <div class="d-flex align-items-center gap-2 mb-3 text-white text-start">
                                <img class="rounded-circle" src="{{ $story->member?->avatar_url }}" style="width: 38px; height: 38px; object-fit: cover;">
                                <div>
                                    <div class="fw-bold">{{ $story->member?->name }}</div>
                                    <small class="text-white-50">{{ $story->created_at?->diffForHumans() }}</small>
                                </div>
                            </div>

                            @if($story->isImage())
                                <img src="{{ $mediaUrl }}" alt="Story Image" class="img-fluid rounded" style="max-height: 480px; object-fit: contain;">
                            @elseif($story->isVideo())
                                <video controls class="w-100 rounded" style="max-height: 480px;">
                                    <source src="{{ $mediaUrl }}" type="video/mp4">
                                    Your browser does not support video playback.
                                </video>
                            @endif

                            @if($story->caption)
                                <div class="mt-3 p-2 bg-secondary text-white rounded small">
                                    {{ $story->caption }}
                                </div>
                            @endif
                        </div>
                    </x-admin.modal>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$stories" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-stories');
        const checkboxes = document.querySelectorAll('.story-checkbox');
        const selectedCount = document.getElementById('selected-stories-count');
        const bulkForm = document.getElementById('bulk-stories-form');

        function updateCount() {
            const checked = document.querySelectorAll('.story-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.story-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected story(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
