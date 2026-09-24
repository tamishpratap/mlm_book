@extends('admin.layouts.master')
@section('title', 'Post Moderation #' . $post->id)
@section('page-subtitle', 'Read-only post inspection, media preview, comments & report queue.')

@section('content')
@php
    $isShared = $post->isShared();
    $originalPost = $isShared ? $post->originalPost : null;
@endphp

<div class="row g-4 mb-4">
    <!-- Left Column: Post Inspection & Media -->
    <div class="col-lg-8">
        <!-- Post Header Card -->
        <x-admin.card>
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center gap-3">
                    <img class="rounded-circle" src="{{ $post->member?->avatar_url }}" alt="{{ $post->member?->name }}" style="width: 48px; height: 48px; object-fit: cover;">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            @if($post->member)
                                <a href="{{ route('admin.members.show', $post->member) }}" class="text-dark text-decoration-none">{{ $post->member->name }}</a>
                            @else
                                Unknown Author
                            @endif
                        </h5>
                        <small class="text-muted">{{ $post->member?->user_id }} • {{ $post->created_at?->format('F d, Y \a\t H:i') }}</small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @if($isShared)
                        <span class="badge bg-light-primary text-primary me-1"><i class="fa fa-share me-1"></i> Reshared Post</span>
                    @endif
                    @if($post->community)
                        <span class="badge bg-light-primary text-primary me-2"><i class="fa fa-users me-1"></i> {{ $post->community->name }}</span>
                    @elseif($post->businessPage)
                        <span class="badge bg-light-info text-info me-2"><i class="fa fa-briefcase me-1"></i> {{ $post->businessPage->name }}</span>
                    @endif

                    <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete Post #{{ $post->id }} permanently?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash me-1"></i> Delete Post</button>
                    </form>

                    <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <!-- Post Body Text / Resharer Commentary -->
            <div class="p-3 bg-light rounded mb-3">
                @if($isShared)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 11px;">Resharer Caption / Commentary</small>
                    </div>
                @endif
                <p class="mb-0 text-dark" style="font-size: 1.05rem; line-height: 1.6; white-space: pre-line;">
                    {{ $post->body ?: ($isShared ? 'No additional commentary added.' : 'No text caption provided.') }}
                </p>
            </div>

            @if($isShared && $originalPost)
                <!-- Embedded Original Shared Post Container -->
                <div class="border rounded p-3 mb-3 bg-white shadow-sm border-start border-3 border-primary">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <img class="rounded-circle" src="{{ $originalPost->member?->avatar_url }}" alt="{{ $originalPost->member?->name }}" style="width: 38px; height: 38px; object-fit: cover;">
                            <div>
                                <div class="fw-bold text-dark small">
                                    @if($originalPost->member)
                                        <a href="{{ route('admin.members.show', $originalPost->member) }}" class="text-dark text-decoration-none">{{ $originalPost->member->name }}</a>
                                    @else
                                        Unknown Author
                                    @endif
                                </div>
                                <small class="text-muted" style="font-size: 11px;">{{ $originalPost->member?->user_id }} • {{ $originalPost->created_at?->format('F d, Y \a\t H:i') }}</small>
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('admin.posts.show', $originalPost) }}" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 11px;">
                                <i class="fa fa-external-link me-1"></i> Original Post #{{ $originalPost->id }}
                            </a>
                        </div>
                    </div>

                    @if($originalPost->body)
                        <div class="p-2 bg-light rounded mb-3">
                            <p class="mb-0 text-dark" style="font-size: 1rem; line-height: 1.5; white-space: pre-line;">
                                {{ $originalPost->body }}
                            </p>
                        </div>
                    @endif

                    @if($originalPost->hasImage())
                        @php
                            $origImgPath = str_starts_with($originalPost->media_path, 'http') ? $originalPost->media_path : asset($originalPost->media_path);
                        @endphp
                        <div class="mb-2 text-center bg-dark rounded overflow-hidden p-2">
                            <img src="{{ $origImgPath }}" alt="Original Post Media" class="img-fluid rounded" style="max-height: 450px; object-fit: contain;">
                        </div>
                    @elseif($originalPost->hasVideo())
                        @php
                            $origVidPath = str_starts_with($originalPost->media_path, 'http') ? $originalPost->media_path : asset($originalPost->media_path);
                        @endphp
                        <div class="mb-2 text-center bg-dark rounded overflow-hidden p-2">
                            <video controls class="w-100 rounded" style="max-height: 450px;">
                                <source src="{{ $origVidPath }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Direct Post Media Preview Container (If not shared or if direct media exists) -->
            @if(!$isShared || $post->media_path)
                @if($post->hasImage())
                    @php
                        $imgPath = str_starts_with($post->media_path, 'http') ? $post->media_path : asset($post->media_path);
                    @endphp
                    <div class="mb-3 text-center bg-dark rounded overflow-hidden p-2">
                        <img src="{{ $imgPath }}" alt="Post Media" class="img-fluid rounded" style="max-height: 450px; object-fit: contain;">
                    </div>
                @elseif($post->hasVideo())
                    @php
                        $vidPath = str_starts_with($post->media_path, 'http') ? $post->media_path : asset($post->media_path);
                    @endphp
                    <div class="mb-3 text-center bg-dark rounded overflow-hidden p-2">
                        <video controls class="w-100 rounded" style="max-height: 450px;">
                            <source src="{{ $vidPath }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                @endif
            @endif

            <!-- Interaction Metric Footer -->
            <div class="d-flex align-items-center justify-content-between pt-3 border-top text-muted small">
                <div>
                    <span class="me-3"><i class="fa fa-heart text-danger me-1"></i> <strong>{{ $post->likes_count }}</strong> Likes</span>
                    <span class="me-3"><i class="fa fa-comment text-primary me-1"></i> <strong>{{ $post->comments_count }}</strong> Comments</span>
                    <span class="me-3"><i class="fa fa-share text-success me-1"></i> <strong>{{ $post->shares_count }}</strong> Shares</span>
                </div>
                <div>
                    <span class="badge {{ $post->reports_count > 0 ? 'bg-danger' : 'bg-secondary' }}">
                        {{ $post->reports_count }} Reports Flagged
                    </span>
                </div>
            </div>
        </x-admin.card>

        <!-- Comments & Replies Log -->
        <x-admin.card title="Comments & Discussion ({{ $post->comments_count }})">
            @if($post->comments->isEmpty())
                <x-admin.empty-state icon="message-square" title="No Comments" description="No comments have been posted on this publication yet." />
            @else
                <div class="comments-list">
                    @foreach($post->comments as $comment)
                        <div class="d-flex gap-3 mb-3 p-3 bg-light rounded">
                            <img class="rounded-circle" src="{{ $comment->member?->avatar_url }}" alt="{{ $comment->member?->name }}" style="width: 36px; height: 36px; object-fit: cover;">
                            <div class="w-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0 small">{{ $comment->member?->name ?? 'Member' }}</h6>
                                    <small class="text-muted" style="font-size: 11px;">{{ $comment->created_at?->format('M d, H:i') }}</small>
                                </div>
                                <p class="mb-1 text-dark small">{{ $comment->comment }}</p>

                                <!-- Nested Replies -->
                                @if($comment->replies && $comment->replies->isNotEmpty())
                                    <div class="mt-2 ps-3 border-start">
                                        @foreach($comment->replies as $reply)
                                            <div class="d-flex gap-2 mt-2">
                                                <img class="rounded-circle" src="{{ $reply->member?->avatar_url }}" alt="" style="width: 28px; height: 28px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-dark small" style="font-size: 12px;">{{ $reply->member?->name }}</div>
                                                    <p class="mb-0 text-muted small" style="font-size: 12px;">{{ $reply->comment }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>

    <!-- Right Column: Reaction Summary & Report Queue -->
    <div class="col-lg-4">
        <!-- Top Reactions Card -->
        <x-admin.card title="Reactions Summary" class="mb-4">
            @if($topReactions->isEmpty())
                <p class="text-muted small mb-0">No reactions recorded.</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($topReactions as $reaction)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="fa fa-thumbs-up me-2 text-primary"></i> {{ ucfirst($reaction->reaction) }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $reaction->total }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>

        <!-- Reports Moderation Log Queue -->
        <x-admin.card title="Report Queue Log ({{ $post->reports_count }})">
            @if($post->reports->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i data-feather="check-circle" class="text-success mb-2" style="width: 36px; height: 36px;"></i>
                    <p class="mb-0 small">No reports filed for this post.</p>
                </div>
            @else
                <div class="report-queue-list">
                    @foreach($post->reports as $report)
                        <div class="border rounded p-3 mb-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-danger text-uppercase">{{ $report->reason }}</span>
                                <small class="text-muted" style="font-size: 11px;">{{ $report->created_at?->format('M d, Y') }}</small>
                            </div>
                            
                            <p class="small text-dark mb-2">{{ $report->description ?: 'No detailed description provided by reporter.' }}</p>

                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <small class="text-muted">By: <strong>{{ $report->member?->name ?? 'Reporter' }}</strong></small>

                                <form action="{{ route('admin.posts.reports.status', $report) }}" method="POST">
                                    @csrf
                                    @if($report->status === 'reviewed')
                                        <span class="badge bg-light-success text-success">Reviewed</span>
                                    @else
                                        <input type="hidden" name="status" value="reviewed">
                                        <button type="submit" class="btn btn-xs btn-outline-success py-1 px-2" style="font-size: 11px;">Mark Reviewed</button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>
</div>
@endsection
