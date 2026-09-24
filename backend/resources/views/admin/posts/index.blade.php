@extends('admin.layouts.master')
@section('title', 'Posts & Content Moderation')
@section('page-subtitle', 'Inspect, moderate, and manage platform posts and content reports.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Posts" 
            :value="$totalCount" 
            icon="file-text" 
            variant="primary" 
            subtext="Platform Content" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Image Posts" 
            :value="$imageCount" 
            icon="image" 
            variant="info" 
            subtext="Photos & Graphics" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Video Posts" 
            :value="$videoCount" 
            icon="video" 
            variant="secondary" 
            subtext="Media Stream" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Reported Posts" 
            :value="$reportedCount" 
            icon="alert-triangle" 
            variant="danger" 
            subtext="Flagged for Moderation" 
        />
    </div>
</div>

<!-- Search, Filter & Bulk Actions Container -->
<x-admin.card title="Posts Directory & Queue">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.posts.export') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Export CSV
            </a>
        </div>
    </x-slot>

    <!-- Search & Filter Form -->
    <form action="{{ route('admin.posts.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <x-admin.form.group label="Search Content" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search ID, Author, Caption, Community..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Media Type" for="media_type">
                <x-admin.form.select name="media_type" :selected="$mediaType" placeholder="All Media Types" :options="['text' => 'Text Only', 'image' => 'Image', 'video' => 'Video']" />
            </x-admin.form.group>
        </div>

        <div class="col-lg-2 col-md-6">
            <x-admin.form.group label="Reports Queue" for="has_reports">
                <x-admin.form.select name="has_reports" :selected="$hasReports" placeholder="All Posts" :options="['yes' => 'Flagged / Reported', 'no' => 'No Reports']" />
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
                @if($search || $mediaType || $hasReports || $dateFrom || $dateTo)
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                @endif
            </div>
        </div>
    </form>

    <!-- Bulk Actions Form -->
    <form id="bulk-posts-form" action="{{ route('admin.posts.bulk-action') }}" method="POST">
        @csrf

        <!-- Bulk Bar -->
        <div class="bg-light p-3 rounded mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all-posts">
                    <label class="form-check-label fw-bold text-dark small" for="select-all-posts">Select All</label>
                </div>
                <span class="text-muted small" id="selected-posts-count">0 selected</span>
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

    <!-- Posts Table -->
    <x-admin.table :headers="['', 'Post ID', 'Author', 'Content Preview', 'Media', 'Context', 'Interactions', 'Reports', 'Created Date', 'Actions']" :empty="$posts->isEmpty()" emptyMessage="No posts match your search or moderation filter.">
        @foreach($posts as $post)
            @php
                $isShared = $post->isShared();
                $originalPost = $isShared ? $post->originalPost : null;
                $resolvedPost = ($isShared && $originalPost) ? $originalPost : $post;
                $effectiveMediaType = $resolvedPost->media_type;
                $effectiveMediaPath = $resolvedPost->media_path;
                $effectiveMediaUrl = $effectiveMediaPath ? (str_starts_with($effectiveMediaPath, 'http') ? $effectiveMediaPath : asset($effectiveMediaPath)) : null;
            @endphp
            <tr>
                <td>
                    <input class="form-check-input post-checkbox" type="checkbox" name="ids[]" value="{{ $post->id }}" form="bulk-posts-form">
                </td>
                <td>
                    <span class="fw-bold text-primary">#{{ $post->id }}</span>
                    @if($isShared)
                        <span class="badge bg-light-primary text-primary d-block mt-1" style="font-size: 9px; padding: 2px 4px;"><i class="fa fa-share me-1"></i> Reshare</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img class="rounded-circle" src="{{ $post->member?->avatar_url }}" alt="{{ $post->member?->name }}" style="width: 34px; height: 34px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark small">{{ $post->member?->name ?? 'Deleted Member' }}</div>
                            <small class="text-muted" style="font-size: 11px;">{{ $post->member?->user_id }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 250px;" title="{{ $post->body ?: ($originalPost?->body ?: '[Media Attachment]') }}">
                        @if($post->body)
                            {{ $post->body }}
                        @elseif($isShared && $originalPost)
                            <span class="text-muted fst-italic">{{ $originalPost->body ?: '[Reshared Media]' }}</span>
                        @else
                            <span class="text-muted fst-italic">[Media Attachment]</span>
                        @endif
                    </div>
                    @if($isShared && $originalPost)
                        <small class="text-muted d-block text-truncate mt-1" style="max-width: 250px; font-size: 11px;">
                            <i class="fa fa-share text-primary me-1"></i> From <strong>{{ $originalPost->member?->name ?? 'Original Author' }}</strong>
                        </small>
                    @endif
                </td>
                <td>
                    @if($effectiveMediaType === 'image' && $effectiveMediaUrl)
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ $effectiveMediaUrl }}" target="_blank" title="View Full Image">
                                <img src="{{ $effectiveMediaUrl }}" alt="Post Media" class="rounded border shadow-sm" style="width: 38px; height: 38px; object-fit: cover; flex-shrink: 0;" loading="lazy">
                            </a>
                            <div>
                                <x-admin.badge variant="info" :light="true"><i class="fa fa-image me-1"></i> Image</x-admin.badge>
                                @if($isShared)
                                    <small class="d-block text-muted fw-bold" style="font-size: 10px;">(Shared)</small>
                                @endif
                            </div>
                        </div>
                    @elseif($effectiveMediaType === 'video' && $effectiveMediaUrl)
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded bg-dark d-flex align-items-center justify-content-center border shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;">
                                <i class="fa fa-play text-white" style="font-size: 12px;"></i>
                            </div>
                            <div>
                                <x-admin.badge variant="secondary" :light="true"><i class="fa fa-video-camera me-1"></i> Video</x-admin.badge>
                                @if($isShared)
                                    <small class="d-block text-muted fw-bold" style="font-size: 10px;">(Shared)</small>
                                @endif
                            </div>
                        </div>
                    @else
                        <x-admin.badge variant="light" :light="false">Text</x-admin.badge>
                    @endif
                </td>
                <td>
                    @if($post->community)
                        <small class="badge bg-light-primary text-primary"><i class="fa fa-users me-1"></i> {{ Str::limit($post->community->name, 18) }}</small>
                    @elseif($post->businessPage)
                        <small class="badge bg-light-info text-info"><i class="fa fa-briefcase me-1"></i> {{ Str::limit($post->businessPage->name, 18) }}</small>
                    @else
                        <small class="text-muted">Personal Feed</small>
                    @endif
                </td>
                <td>
                    <small class="text-muted">
                        <i class="fa fa-heart me-1 text-danger"></i> {{ $post->likes_count }}
                        <span class="mx-1">•</span>
                        <i class="fa fa-comment me-1 text-primary"></i> {{ $post->comments_count }}
                    </small>
                </td>
                <td>
                    @if($post->reports_count > 0)
                        <x-admin.badge variant="danger" :light="true">{{ $post->reports_count }} Reports</x-admin.badge>
                    @else
                        <small class="text-muted">0</small>
                    @endif
                </td>
                <td>
                    <small class="text-muted">{{ $post->created_at?->format('M d, Y H:i') }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.posts.show', $post) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Inspect Post">
                            <i data-feather="eye"></i>
                        </a>

                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Post #{{ $post->id }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Post">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <!-- Pagination -->
    <x-admin.pagination :paginator="$posts" />
</x-admin.card>
@endsection

@push('admin-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select-all-posts');
        const checkboxes = document.querySelectorAll('.post-checkbox');
        const selectedCount = document.getElementById('selected-posts-count');
        const bulkForm = document.getElementById('bulk-posts-form');

        function updateCount() {
            const checked = document.querySelectorAll('.post-checkbox:checked').length;
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
                const checked = document.querySelectorAll('.post-checkbox:checked');
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
                if (!confirm('Apply bulk action to ' + checked.length + ' selected post(s)?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>
@endpush
