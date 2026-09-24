@extends('admin.layouts.master')
@section('title', 'Story Details & Analytics #' . $story->id)
@section('page-subtitle', 'Read-only story inspection, media player, viewer logs, and reaction analytics.')

@section('content')
@php
    $isLive = $story->expires_at > now();
    $mediaUrl = str_starts_with($story->media_path, 'http') ? $story->media_path : asset($story->media_path);
@endphp

<div class="row g-4 mb-4">
    <!-- Left Column: Story Player & Author Information -->
    <div class="col-lg-6">
        <x-admin.card>
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center gap-3">
                    <img class="rounded-circle" src="{{ $story->member?->avatar_url }}" alt="{{ $story->member?->name }}" style="width: 48px; height: 48px; object-fit: cover;">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            @if($story->member)
                                <a href="{{ route('admin.members.show', $story->member) }}" class="text-dark text-decoration-none">{{ $story->member->name }}</a>
                            @else
                                Unknown Member
                            @endif
                        </h5>
                        <small class="text-muted">{{ $story->member?->user_id }} • {{ $story->created_at?->format('F d, Y \a\t H:i') }}</small>
                    </div>
                </div>

                <div>
                    @if($isLive)
                        <x-admin.badge variant="success" :light="true">Active (Live)</x-admin.badge>
                    @else
                        <x-admin.badge variant="warning" :light="true">Expired</x-admin.badge>
                    @endif
                </div>
            </div>

            <!-- Media Player Container -->
            <div class="bg-dark rounded p-3 text-center mb-3">
                @if($story->isImage())
                    <img src="{{ $mediaUrl }}" alt="Story Image" class="img-fluid rounded" style="max-height: 480px; object-fit: contain;">
                @elseif($story->isVideo())
                    <video controls autoplay class="w-100 rounded" style="max-height: 480px;">
                        <source src="{{ $mediaUrl }}" type="video/mp4">
                        Your browser does not support video playback.
                    </video>
                @endif
            </div>

            @if($story->caption)
                <div class="p-3 bg-light rounded mb-3">
                    <h6 class="fw-bold text-primary mb-1">Caption</h6>
                    <p class="mb-0 text-dark small">{{ $story->caption }}</p>
                </div>
            @endif

            <!-- Storage & Metadata Table -->
            <table class="table table-sm table-borderless small mb-3">
                <tr>
                    <td class="text-muted fw-bold" style="width: 140px;">Media Format:</td>
                    <td><span class="badge bg-light-primary text-primary">{{ strtoupper($story->media_type) }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Created Date:</td>
                    <td>{{ $story->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Expires Date:</td>
                    <td>{{ $story->expires_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Storage Path:</td>
                    <td><code class="text-muted">{{ $story->media_path }}</code></td>
                </tr>
            </table>

            <div class="d-flex justify-content-between pt-3 border-top">
                <a href="{{ route('admin.stories.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back to Directory
                </a>

                <form action="{{ route('admin.stories.destroy', $story) }}" method="POST" onsubmit="return confirm('Delete Story #{{ $story->id }}?')">
                    @csrf
                    @method('DELETE')
                    <x-admin.button type="submit" variant="danger" icon="trash-2">Delete Story</x-admin.button>
                </form>
            </div>
        </x-admin.card>
    </div>

    <!-- Right Column: Story Analytics & Viewers Log -->
    <div class="col-lg-6">
        <!-- Analytics Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="card p-3 text-center h-100">
                    <h4 class="fw-bold text-primary mb-0">{{ $story->views_count }}</h4>
                    <small class="text-muted text-uppercase" style="font-size: 11px;">Total Views</small>
                </div>
            </div>
            <div class="col-6">
                <div class="card p-3 text-center h-100">
                    <h4 class="fw-bold text-danger mb-0">{{ $story->reactions_count + $story->likes_count }}</h4>
                    <small class="text-muted text-uppercase" style="font-size: 11px;">Reactions</small>
                </div>
            </div>
        </div>

        <!-- Viewers Log -->
        <x-admin.card title="Viewers Log ({{ $story->views_count }})" class="mb-4">
            @if($story->views->isEmpty())
                <x-admin.empty-state icon="eye" title="No Views Yet" description="This story has not been viewed by any members yet." />
            @else
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Viewer</th>
                                <th>Viewed Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($story->views as $view)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img class="rounded-circle" src="{{ $view->viewer?->avatar_url }}" style="width: 28px; height: 28px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold text-dark small">{{ $view->viewer?->name ?? 'Member' }}</div>
                                                <small class="text-muted" style="font-size: 11px;">{{ $view->viewer?->user_id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><small class="text-muted">{{ $view->created_at?->format('M d, H:i:s') }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.card>

        <!-- Reactions & Replies Log -->
        <x-admin.card title="Replies Log ({{ $story->replies_count }})">
            @if($story->replies->isEmpty())
                <x-admin.empty-state icon="message-square" title="No Replies" description="No direct replies received for this story." />
            @else
                <div class="replies-list" style="max-height: 250px; overflow-y: auto;">
                    @foreach($story->replies as $reply)
                        <div class="p-2 border-bottom d-flex gap-2 align-items-start">
                            <img class="rounded-circle" src="{{ $reply->sender?->avatar_url }}" style="width: 30px; height: 30px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark small">{{ $reply->sender?->name }}</div>
                                <p class="mb-0 text-muted small">{{ $reply->message ?? $reply->content }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>
</div>
@endsection
