@extends('admin.layouts.master')
@section('title', 'Report Inspection #' . $reportData->id)
@section('page-subtitle', 'Read-only report details, reporter profile, and target content inspection.')

@section('content')
<div class="row g-4 mb-4">
    <!-- Left Column: Report Details & Reporter Card -->
    <div class="col-lg-6">
        <x-admin.card title="Report Details">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger text-uppercase fs-6">{{ $reportData->reason }}</span>
                    <span class="badge bg-light-primary text-primary">{{ $reportData->type_label }} Report</span>
                </div>

                @if(strtolower($reportData->status) === 'resolved')
                    <x-admin.badge variant="success" :light="true">Resolved</x-admin.badge>
                @else
                    <form action="{{ route('admin.reports.status', ['type' => $reportData->type, 'id' => $reportData->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="resolved">
                        <x-admin.button type="submit" variant="success" icon="check-circle">Mark Resolved</x-admin.button>
                    </form>
                @endif
            </div>

            <!-- Reported Reason & Details -->
            <div class="p-3 bg-light rounded mb-4">
                <h6 class="fw-bold text-dark mb-1">Reporter Explanation / Notes</h6>
                <p class="mb-0 text-muted small" style="white-space: pre-line;">
                    {{ $reportData->details ?: 'No detailed notes provided by reporter.' }}
                </p>
            </div>

            <!-- Reporter Profile Section -->
            <h6 class="fw-bold text-primary mb-3">Reporter Information</h6>
            @if($reportData->reporter)
                <div class="d-flex align-items-center gap-3 p-3 border rounded bg-white mb-4">
                    <img class="rounded-circle" src="{{ $reportData->reporter->avatar_url }}" style="width: 48px; height: 48px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">{{ $reportData->reporter->name }}</h6>
                        <code class="text-primary">{{ $reportData->reporter->user_id }}</code>
                        <div class="text-muted small">{{ $reportData->reporter->email }}</div>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('admin.members.show', $reportData->reporter) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-user me-1"></i> View Profile
                        </a>
                    </div>
                </div>
            @else
                <p class="text-muted small mb-4">Anonymous / Deleted Member</p>
            @endif

            <div class="pt-3 border-top">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back to Queue
                </a>
            </div>
        </x-admin.card>
    </div>

    <!-- Right Column: Reported Content Preview -->
    <div class="col-lg-6">
        <x-admin.card title="Reported Target Content">
            @if(!$reportData->target)
                <x-admin.empty-state icon="alert-triangle" title="Content Unavailable" description="The reported target item may have been deleted or removed." />
            @else
                @if($reportData->type === 'post')
                    @php
                        $targetPost = $reportData->target;
                        $targetIsShared = $targetPost->isShared();
                        $targetOrigPost = $targetIsShared ? $targetPost->originalPost : null;
                        $targetResolvedPost = ($targetIsShared && $targetOrigPost) ? $targetOrigPost : $targetPost;
                    @endphp
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img class="rounded-circle" src="{{ $reportData->target->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark small">{{ $reportData->target->member?->name }}</div>
                                <small class="text-muted" style="font-size: 11px;">Posted on {{ $reportData->target->created_at?->format('M d, Y H:i') }}</small>
                            </div>
                            @if($targetIsShared)
                                <span class="badge bg-light-primary text-primary ms-auto" style="font-size: 10px;"><i class="fa fa-share me-1"></i> Reshared</span>
                            @endif
                        </div>

                        <p class="text-dark small mb-3">{{ $reportData->target->body ?: ($targetIsShared && $targetOrigPost ? ($targetOrigPost->body ?: '[Reshared Media]') : '[Media Attachment]') }}</p>

                        @if($targetResolvedPost->hasImage())
                            <div class="bg-dark rounded p-2 text-center mb-3">
                                <img src="{{ str_starts_with($targetResolvedPost->media_path, 'http') ? $targetResolvedPost->media_path : asset($targetResolvedPost->media_path) }}" class="img-fluid rounded" style="max-height: 250px;">
                            </div>
                        @elseif($targetResolvedPost->hasVideo())
                            <div class="bg-dark rounded p-2 text-center mb-3">
                                <video controls class="w-100 rounded" style="max-height: 250px;">
                                    <source src="{{ str_starts_with($targetResolvedPost->media_path, 'http') ? $targetResolvedPost->media_path : asset($targetResolvedPost->media_path) }}" type="video/mp4">
                                </video>
                            </div>
                        @endif

                        <div class="pt-2 border-top text-end">
                            <a href="{{ route('admin.posts.show', $reportData->target) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-external-link me-1"></i> Moderate Post
                            </a>
                        </div>
                    </div>
                @elseif($reportData->type === 'community')
                    <div class="p-3 bg-light rounded border mb-3">
                        <h5 class="fw-bold text-dark mb-1">{{ $reportData->target->name }}</h5>
                        <small class="badge bg-light-primary text-primary mb-2">{{ $reportData->target->category }}</small>
                        <p class="text-muted small mb-3">{{ $reportData->target->description ?: 'No description provided.' }}</p>

                        <div class="pt-2 border-top text-end">
                            <a href="{{ route('admin.communities.show', $reportData->target) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-external-link me-1"></i> Moderate Community
                            </a>
                        </div>
                    </div>
                @elseif($reportData->type === 'product')
                    <div class="p-3 bg-light rounded border mb-3">
                        <h5 class="fw-bold text-dark mb-1">{{ $reportData->target->title }}</h5>
                        <h6 class="fw-bold text-success mb-2">${{ number_format($reportData->target->price, 2) }}</h6>
                        <p class="text-muted small mb-3">{{ $reportData->target->description }}</p>

                        <div class="pt-2 border-top text-end">
                            <a href="{{ route('admin.marketplace.show', $reportData->target) }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-external-link me-1"></i> Moderate Product
                            </a>
                        </div>
                    </div>
                @elseif($reportData->type === 'business_review')
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img class="rounded-circle" src="{{ $reportData->target->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark small">{{ $reportData->target->member?->name }}</div>
                                <span class="text-warning fw-bold small">{{ $reportData->target->rating }} <i class="fa fa-star"></i></span>
                            </div>
                        </div>
                        <p class="text-dark small mb-0">{{ $reportData->target->review_text ?: $reportData->target->content }}</p>
                    </div>
                @endif
            @endif
        </x-admin.card>
    </div>
</div>
@endsection
