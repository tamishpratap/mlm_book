@extends('admin.layouts.master')
@section('title', 'Business Page: ' . $businessPage->page_name)
@section('page-subtitle', 'Read-only business page inspection, verification queue, followers & reviews.')

@section('content')
@php
    $logoUrl = $businessPage->logo_url 
        ?: ($businessPage->logo && file_exists(public_path($businessPage->logo)) ? asset($businessPage->logo) : 'https://ui-avatars.com/api/?name=' . urlencode($businessPage->page_name) . '&background=0284c7&color=fff&size=128&bold=true');
    $coverUrl = $businessPage->cover_url
        ?: ($businessPage->cover_photo && file_exists(public_path($businessPage->cover_photo)) ? asset($businessPage->cover_photo) : null);
@endphp

<!-- Business Page Banner Header Card -->
<div class="card mb-4 overflow-hidden border shadow-sm">
    <!-- 1. COVER AREA -->
    <div class="position-relative" style="height: 175px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0f172a 100%);">
        @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="Cover Photo" class="w-100 h-100" style="object-fit: cover; opacity: 0.85;">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.6) 100%);"></div>
        @else
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25" style="background-image: radial-gradient(rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 16px 16px;"></div>
        @endif
    </div>

    <!-- 2. BUSINESS IDENTITY & ACTIONS SECTION (WHITE CARD BODY) -->
    <div class="card-body position-relative pt-0 pb-3 px-4 bg-white">
        <!-- Identity Top Row: Protruding Logo & Right Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <!-- Left: Protruding Logo + Identity Details -->
            <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3 text-center text-sm-start flex-grow-1">
                <!-- Protruding Logo (Only this element overlaps the cover) -->
                <div class="position-relative flex-shrink-0" style="margin-top: -55px; z-index: 2;">
                    <img src="{{ $logoUrl }}" alt="{{ $businessPage->page_name }}" class="rounded-circle border border-4 border-white shadow" style="width: 110px; height: 110px; object-fit: cover; background: #fff;">
                </div>

                <!-- Text Details in Normal Flow (Inside White Card Body) -->
                <div class="pt-sm-2 flex-grow-1">
                    <!-- Title Line -->
                    <h3 class="fw-bold mb-1" style="font-size: 1.55rem; letter-spacing: -0.02em; color: #0f172a !important;">
                        {{ $businessPage->page_name }}
                    </h3>

                    <!-- Username Line (Dedicated line) -->
                    @if($businessPage->page_username)
                        <div class="mb-2">
                            <span class="badge bg-light border text-secondary fw-semibold px-2 py-1" style="font-size: 0.82rem; font-family: monospace;">
                                {{ '@' . ltrim($businessPage->page_username, '@') }}
                            </span>
                        </div>
                    @endif

                    <!-- Badges Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mb-2">
                        <span class="badge bg-light-primary text-primary fw-semibold px-2.5 py-1.5" style="font-size: 0.8rem;">
                            <i class="fa fa-tag me-1"></i> {{ $businessPage->category }}
                        </span>

                        @if($businessPage->is_verified)
                            <span class="badge bg-success text-white px-2.5 py-1.5 fw-bold">
                                <i class="fa fa-check-circle me-1"></i> Verified Business
                            </span>
                        @elseif($businessPage->isVerificationPending())
                            <span class="badge bg-warning text-dark px-2.5 py-1.5 fw-bold">
                                <i class="fa fa-clock-o me-1"></i> Pending Verification
                            </span>
                        @else
                            <span class="badge bg-secondary text-white px-2.5 py-1.5 fw-bold">
                                Unverified
                            </span>
                        @endif

                        @if($businessPage->status === 'active')
                            <span class="badge bg-light-success text-success fw-bold px-2.5 py-1.5 border border-success-subtle">
                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Active
                            </span>
                        @else
                            <span class="badge bg-danger text-white px-2.5 py-1.5 fw-bold">
                                <i class="fa fa-ban me-1"></i> Suspended
                            </span>
                        @endif
                    </div>

                    <!-- Owner & Creation Metadata Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 text-muted small mt-1">
                        <span>
                            <i class="fa fa-user text-primary me-1"></i>
                            Owned by <strong class="text-dark">{{ $businessPage->owner?->name ?? 'System' }}</strong>
                            @if($businessPage->owner?->user_id)
                                <span class="text-muted">({{ $businessPage->owner?->user_id }})</span>
                            @endif
                        </span>
                        <span class="text-muted">•</span>
                        <span>
                            <i class="fa fa-calendar-o text-muted me-1"></i>
                            Created on <span class="text-secondary">{{ $businessPage->created_at?->format('F d, Y') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons Group -->
            <div class="pt-sm-2 d-flex justify-content-center justify-content-md-end gap-2 flex-wrap flex-shrink-0">
                <a href="{{ route('admin.business-pages.edit', $businessPage) }}" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="edit-2" style="width: 15px; height: 15px;"></i> Edit Page
                </a>

                <form action="{{ route('admin.business-pages.verification', $businessPage) }}" method="POST" class="d-inline">
                    @csrf
                    @if($businessPage->is_verified)
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-outline-warning btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="x-circle" style="width: 15px; height: 15px;"></i> Revoke Verification
                        </button>
                    @else
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="check-circle" style="width: 15px; height: 15px;"></i> Approve Verification
                        </button>
                    @endif
                </form>

                <form action="{{ route('admin.business-pages.status', $businessPage) }}" method="POST" class="d-inline">
                    @csrf
                    @if($businessPage->status === 'active')
                        <input type="hidden" name="status" value="suspended">
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="slash" style="width: 15px; height: 15px;"></i> Suspend Page
                        </button>
                    @else
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="check" style="width: 15px; height: 15px;"></i> Activate Page
                        </button>
                    @endif
                </form>

                <a href="{{ route('admin.business-pages.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="arrow-left" style="width: 15px; height: 15px;"></i> Back to Directory
                </a>
            </div>
        </div>

        <!-- 3. STATISTICS ROW -->
        <div class="row g-2 border-top pt-3 mt-3 text-center">
            <div class="col-6 col-sm-3">
                <h5 class="fw-bold mb-0 text-primary">{{ $businessPage->accepted_followers_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Followers</small>
            </div>
            <div class="col-6 col-sm-3 border-start">
                <h5 class="fw-bold mb-0 text-info">{{ $businessPage->posts_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Page Posts</small>
            </div>
            <div class="col-6 col-sm-3 border-start">
                <h5 class="fw-bold mb-0 text-warning">{{ $businessPage->reviews_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Customer Reviews</small>
            </div>
            <div class="col-6 col-sm-3 border-start">
                <h5 class="fw-bold mb-0 text-secondary">{{ count($businessPage->teamMembers) }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Team Members</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabbed Details Section -->
<x-admin.card>
    <x-admin.tabs id="businessPageTabs" active="overview" :tabs="[
        'overview' => 'Overview & Contact',
        'verification' => 'Verification Applications (' . count($businessPage->verifications) . ')',
        'followers' => 'Followers (' . $businessPage->accepted_followers_count . ')',
        'reviews' => 'Reviews (' . $businessPage->reviews_count . ')',
        'posts' => 'Posts (' . count($posts) . ')',
        'team' => 'Team (' . count($businessPage->teamMembers) . ')'
    ]">
        <!-- 1. Overview Pane -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Business Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 150px;">Business Name:</td>
                            <td class="fw-bold text-dark">{{ $businessPage->page_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Page ID:</td>
                            <td><code class="text-primary">{{ $businessPage->page_id ?: $businessPage->id }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Username:</td>
                            <td>{{ $businessPage->page_username ? '@' . $businessPage->page_username : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Category:</td>
                            <td><span class="badge bg-light-primary text-primary">{{ $businessPage->category }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Email:</td>
                            <td>{{ $businessPage->email ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Phone:</td>
                            <td>{{ $businessPage->phone ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Website:</td>
                            <td>
                                @if($businessPage->website)
                                    <a href="{{ $businessPage->website }}" target="_blank" class="text-primary">{{ $businessPage->website }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Location & Details</h6>
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 150px;">Country:</td>
                            <td>{{ $businessPage->country ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">City / State:</td>
                            <td>{{ implode(', ', array_filter([$businessPage->city, $businessPage->state])) ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Address:</td>
                            <td>{{ $businessPage->address ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Created Date:</td>
                            <td>{{ $businessPage->created_at?->format('F d, Y - H:i') }}</td>
                        </tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-2">Description</h6>
                    <p class="text-muted small bg-light p-3 rounded mb-0">
                        {{ $businessPage->description ?: 'No description provided by business owner.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. Verification Applications Pane -->
        <div class="tab-pane fade" id="verification" role="tabpanel" aria-labelledby="verification-tab">
            <x-admin.table :headers="['Application ID', 'Member', 'Document Type', 'Document Number', 'Document File', 'Status', 'Date', 'Action']" :empty="$businessPage->verifications->isEmpty()" emptyMessage="No verification applications submitted for this business page.">
                @foreach($businessPage->verifications as $v)
                    <tr>
                        <td>#{{ $v->id }}</td>
                        <td><small class="fw-bold text-dark">{{ $v->member?->name }}</small></td>
                        <td><span class="badge bg-light-primary text-primary">{{ ucfirst($v->document_type ?: 'ID Document') }}</span></td>
                        <td><code>{{ $v->document_number ?: 'N/A' }}</code></td>
                        <td>
                            @if($v->document_path)
                                <a href="{{ asset($v->document_path) }}" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 11px;">
                                    <i class="fa fa-external-link me-1"></i> View Document
                                </a>
                            @else
                                <small class="text-muted">No File Attached</small>
                            @endif
                        </td>
                        <td>
                            @if($v->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($v->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending Review</span>
                            @else
                                <span class="badge bg-danger">{{ ucfirst($v->status) }}</span>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $v->created_at?->format('M d, Y') }}</small></td>
                        <td>
                            @if($v->status === 'pending')
                                <div class="d-flex gap-1">
                                    <form action="{{ route('admin.business-pages.verification', $businessPage) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-xs btn-success py-1 px-2" style="font-size: 11px;">Approve</button>
                                    </form>

                                    <form action="{{ route('admin.business-pages.verification', $businessPage) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size: 11px;">Reject</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 3. Followers Pane -->
        <div class="tab-pane fade" id="followers" role="tabpanel" aria-labelledby="followers-tab">
            <x-admin.table :headers="['Follower', 'User ID', 'Status', 'Followed Date', 'Action']" :empty="$businessPage->acceptedFollowers->isEmpty()" emptyMessage="No followers for this business page yet.">
                @foreach($businessPage->acceptedFollowers as $follower)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $follower->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div class="fw-bold text-dark small">{{ $follower->member?->name ?? 'Member' }}</div>
                            </div>
                        </td>
                        <td><code>{{ $follower->member?->user_id }}</code></td>
                        <td><span class="badge bg-light-success text-success">{{ ucfirst($follower->status) }}</span></td>
                        <td><small class="text-muted">{{ $follower->created_at?->format('M d, Y') }}</small></td>
                        <td>
                            @if($follower->member)
                                <a href="{{ route('admin.members.show', $follower->member) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Profile">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 4. Reviews Pane -->
        <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
            <x-admin.table :headers="['Reviewer', 'Rating', 'Review Text', 'Date']" :empty="$businessPage->reviews->isEmpty()" emptyMessage="No customer reviews submitted.">
                @foreach($businessPage->reviews as $review)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $review->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div class="fw-bold text-dark small">{{ $review->member?->name ?? 'Customer' }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="text-warning fw-bold">
                                {{ $review->rating }} <i class="fa fa-star"></i>
                            </span>
                        </td>
                        <td><div class="text-truncate" style="max-width: 320px;">{{ $review->review_text ?: $review->content }}</div></td>
                        <td><small class="text-muted">{{ $review->created_at?->format('M d, Y') }}</small></td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 5. Posts Pane -->
        <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
            <x-admin.table :headers="['Post ID', 'Author', 'Content Preview', 'Media', 'Interactions', 'Status', 'Created Date', 'Actions']" :empty="$posts->isEmpty()" emptyMessage="No posts published by this page.">
                @foreach($posts as $post)
                    @php
                        $isPostHidden = \App\Models\HiddenPost::where('post_id', $post->id)->exists();
                    @endphp
                    <tr>
                        <td>#{{ $post->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $post->member?->avatar_url }}" style="width: 28px; height: 28px; object-fit: cover; background: #fff;">
                                <small class="fw-bold text-dark">{{ $post->member?->name ?? 'Author' }}</small>
                            </div>
                        </td>
                        <td><div class="text-truncate" style="max-width: 240px;">{{ $post->body ?: '[Media Attachment]' }}</div></td>
                        <td><span class="badge bg-light-info text-info">{{ ucfirst($post->media_type ?: 'text') }}</span></td>
                        <td><small class="text-muted">{{ $post->likes_count }} Likes • {{ $post->comments_count }} Comments</small></td>
                        <td>
                            @if($isPostHidden)
                                <x-admin.badge variant="danger" :light="true">Hidden</x-admin.badge>
                            @else
                                <x-admin.badge variant="success" :light="true">Visible</x-admin.badge>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $post->created_at?->format('M d, Y') }}</small></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <!-- View Action Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewPostModal{{ $post->id }}" title="View Post Details">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </button>

                                <!-- Edit Action Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPostModal{{ $post->id }}" title="Edit Post (Text & Media)">
                                    <i data-feather="edit-2" style="width: 14px; height: 14px;"></i>
                                </button>

                                <!-- Block / Hide Post Action -->
                                <form action="{{ route('admin.posts.toggle-hide', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $isPostHidden ? "Unhide this post?" : "Block / hide this post from platform feeds?" }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-icon {{ $isPostHidden ? 'btn-outline-success' : 'btn-outline-warning' }}" title="{{ $isPostHidden ? 'Unhide Post' : 'Block/Hide Post' }}">
                                        <i data-feather="{{ $isPostHidden ? 'eye' : 'eye-off' }}" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </form>

                                <!-- Delete Post Action -->
                                <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete post #{{ $post->id }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Post">
                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 6. Team Pane -->
        <div class="tab-pane fade" id="team" role="tabpanel" aria-labelledby="team-tab">
            <x-admin.table :headers="['Member', 'Role', 'Status', 'Assigned Date']" :empty="$businessPage->teamMembers->isEmpty()" emptyMessage="No additional team members assigned.">
                @foreach($businessPage->teamMembers as $tm)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $tm->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div class="fw-bold text-dark small">{{ $tm->member?->name ?? 'Team Member' }}</div>
                            </div>
                        </td>
                        <td><span class="badge bg-primary text-uppercase">{{ $tm->role }}</span></td>
                        <td><span class="badge bg-light-success text-success">{{ ucfirst($tm->status) }}</span></td>
                        <td><small class="text-muted">{{ $tm->created_at?->format('M d, Y') }}</small></td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>
    </x-admin.tabs>
</x-admin.card>

<!-- ========================================== -->
<!-- MODALS FOR BUSINESS PAGE POSTS             -->
<!-- ========================================== -->
@foreach($posts as $post)
    <!-- View Post Modal -->
    <div class="modal fade" id="viewPostModal{{ $post->id }}" tabindex="-1" aria-labelledby="viewPostModalLabel{{ $post->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-height: calc(100dvh - 2rem);">
            <div class="modal-content overflow-hidden" style="max-height: calc(100dvh - 2rem); display: flex; flex-direction: column;">
                <div class="modal-header flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="viewPostModalLabel{{ $post->id }}">
                        <i data-feather="file-text" class="me-2 text-primary" style="width: 18px; height: 18px;"></i>
                        Post #{{ $post->id }} Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body flex-grow-1" style="overflow-y: auto; max-height: calc(100dvh - 180px); min-height: 0;">
                    <!-- Author & Timestamp -->
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <img src="{{ $post->member?->avatar_url }}" class="rounded-circle" style="width: 38px; height: 38px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark">{{ $post->member?->name ?? 'Author' }}</div>
                            <small class="text-muted">{{ $post->created_at?->format('F d, Y - H:i') }} ({{ $post->created_at?->diffForHumans() }})</small>
                        </div>
                    </div>

                    <!-- Post Body Text -->
                    <div class="mb-3">
                        <label class="text-muted small fw-bold text-uppercase">Content / Text</label>
                        <div class="p-3 bg-light rounded text-dark" style="white-space: pre-wrap;">{{ $post->body ?: 'No text content attached to this post.' }}</div>
                    </div>

                    <!-- Media Attachment -->
                    @if($post->media_path)
                        <div class="mb-3">
                            <label class="text-muted small fw-bold text-uppercase">Attached {{ ucfirst($post->media_type ?? 'Media') }}</label>
                            <div class="p-2 bg-dark rounded text-center">
                                @if($post->media_type === 'video')
                                    <video src="{{ asset($post->media_path) }}" controls class="img-fluid rounded" style="max-height: 320px;"></video>
                                @else
                                    <img src="{{ asset($post->media_path) }}" alt="Post Media" class="img-fluid rounded" style="max-height: 320px; object-fit: contain;">
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="row g-2 text-center pt-2 border-top">
                        <div class="col">
                            <span class="text-muted small">Likes</span>
                            <div class="fw-bold text-primary">{{ $post->likes_count ?? 0 }}</div>
                        </div>
                        <div class="col border-start">
                            <span class="text-muted small">Comments</span>
                            <div class="fw-bold text-secondary">{{ $post->comments_count ?? 0 }}</div>
                        </div>
                        <div class="col border-start">
                            <span class="text-muted small">Type</span>
                            <div class="fw-bold text-dark">{{ ucfirst($post->media_type ?: 'Text') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal (Text + Media Support with Fixed Viewport & Internal Scroll) -->
    <div class="modal fade" id="editPostModal{{ $post->id }}" tabindex="-1" aria-labelledby="editPostModalLabel{{ $post->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-height: calc(100dvh - 2rem);">
            <div class="modal-content overflow-hidden" style="max-height: calc(100dvh - 2rem); display: flex; flex-direction: column;">
                <form action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data" id="editPostForm{{ $post->id }}" class="d-flex flex-column h-100 overflow-hidden m-0" style="max-height: calc(100dvh - 2rem); min-height: 0;">
                    @csrf
                    @method('PUT')
                    <div class="modal-header flex-shrink-0">
                        <h5 class="modal-title fw-bold" id="editPostModalLabel{{ $post->id }}">
                            <i data-feather="edit-2" class="me-2 text-primary" style="width: 18px; height: 18px;"></i>
                            Edit Post #{{ $post->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="resetEditPostModal('{{ $post->id }}')"></button>
                    </div>
                    <div class="modal-body flex-grow-1" style="overflow-y: auto; max-height: calc(100dvh - 180px); min-height: 0;">
                        <!-- 1. Post Content / Text -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark" for="post-body-{{ $post->id }}">Post Content / Text</label>
                            <textarea class="form-control" id="post-body-{{ $post->id }}" name="body" rows="4" placeholder="Enter post text...">{{ $post->body }}</textarea>
                            <small class="text-muted">Moderating or correcting inappropriate wording updates the post body.</small>
                        </div>

                        <!-- 2. Post Image / Media Attachment Management -->
                        <div class="mb-3 border-top pt-3">
                            <label class="form-label fw-bold text-dark mb-2">Post Image / Media Attachment</label>
                            
                            @if($post->media_path)
                                <!-- Current Media Card with Clear Removal Indicator -->
                                <div class="mb-3 p-3 bg-light rounded border transition-all" id="current-media-block-{{ $post->id }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-light-primary text-primary fw-bold" id="current-media-badge-{{ $post->id }}">Current {{ ucfirst($post->media_type ?? 'Media') }}</span>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input cursor-pointer" type="checkbox" name="remove_media" value="1" id="remove-media-{{ $post->id }}" onchange="toggleRemoveMedia(this, '{{ $post->id }}')">
                                            <label class="form-check-label text-danger small fw-bold cursor-pointer" for="remove-media-{{ $post->id }}">Remove Attachment</label>
                                        </div>
                                    </div>

                                    <!-- Removal Pending Alert -->
                                    <div id="removal-alert-{{ $post->id }}" class="alert alert-danger py-2 px-3 mb-2 small fw-bold d-none" role="alert">
                                        <i class="fa fa-trash me-1"></i> Attachment marked for removal. It will be removed on Save Changes.
                                    </div>

                                    <div class="text-center bg-dark p-2 rounded position-relative" id="current-media-preview-{{ $post->id }}" style="transition: all 0.2s ease;">
                                        @if($post->media_type === 'video')
                                            <video src="{{ asset($post->media_path) }}" controls class="img-fluid rounded" style="max-height: 200px;"></video>
                                        @else
                                            <img src="{{ asset($post->media_path) }}" alt="Current Post Media" class="img-fluid rounded" style="max-height: 200px; object-fit: contain;">
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Replace / Upload New Image/Media Control -->
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <label for="post-media-{{ $post->id }}" class="btn btn-sm btn-outline-primary mb-0" style="cursor: pointer;">
                                    <i data-feather="{{ $post->media_path ? 'refresh-cw' : 'upload' }}" style="width: 14px; height: 14px;" class="me-1"></i>
                                    {{ $post->media_path ? 'Replace Media / Upload New' : 'Upload Image / Media' }}
                                </label>
                                <input type="file" name="media" id="post-media-{{ $post->id }}" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,video/mp4,video/quicktime,video/x-msvideo" onchange="previewSelectedMedia(this, '{{ $post->id }}')">
                                <small class="text-muted">Supported: JPG, PNG, WEBP, GIF, MP4 (Max: 25MB)</small>
                            </div>

                            <!-- New Selected Preview Container (Hidden Until Selected) -->
                            <div id="new-media-preview-container-{{ $post->id }}" class="d-none mt-2 p-3 border border-success rounded bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success text-white"><i class="fa fa-check-circle me-1"></i> New Media Selected (Overrides removal)</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="cancelSelectedMedia('{{ $post->id }}')">
                                        <i class="fa fa-times me-1"></i> Cancel New Media
                                    </button>
                                </div>
                                <div class="text-center bg-dark p-2 rounded">
                                    <img id="new-media-preview-img-{{ $post->id }}" src="" alt="New Preview" class="img-fluid rounded d-none" style="max-height: 220px; object-fit: contain;">
                                    <video id="new-media-preview-video-{{ $post->id }}" src="" controls class="img-fluid rounded d-none" style="max-height: 220px;"></video>
                                </div>
                                <small class="text-muted d-block mt-1" id="new-media-name-{{ $post->id }}"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-shrink-0 bg-white border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetEditPostModal('{{ $post->id }}')">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('admin-scripts')
<script>
    function toggleRemoveMedia(checkbox, postId) {
        const currentPreview = document.getElementById(`current-media-preview-${postId}`);
        const currentBlock = document.getElementById(`current-media-block-${postId}`);
        const removalAlert = document.getElementById(`removal-alert-${postId}`);
        const currentBadge = document.getElementById(`current-media-badge-${postId}`);

        if (checkbox.checked) {
            if (currentPreview) {
                currentPreview.style.opacity = '0.35';
                currentPreview.style.filter = 'grayscale(100%)';
            }
            if (currentBlock) {
                currentBlock.style.borderColor = '#dc3545';
                currentBlock.style.backgroundColor = '#fff5f5';
            }
            if (removalAlert) {
                removalAlert.classList.remove('d-none');
            }
            if (currentBadge) {
                currentBadge.className = 'badge bg-danger text-white fw-bold';
                currentBadge.textContent = 'Marked for Removal';
            }
        } else {
            if (currentPreview) {
                currentPreview.style.opacity = '1';
                currentPreview.style.filter = 'none';
            }
            if (currentBlock) {
                currentBlock.style.borderColor = '';
                currentBlock.style.backgroundColor = '';
            }
            if (removalAlert) {
                removalAlert.classList.add('d-none');
            }
            if (currentBadge) {
                currentBadge.className = 'badge bg-light-primary text-primary fw-bold';
                currentBadge.textContent = 'Current Media';
            }
        }
    }

    function previewSelectedMedia(input, postId) {
        const container = document.getElementById(`new-media-preview-container-${postId}`);
        const imgPreview = document.getElementById(`new-media-preview-img-${postId}`);
        const videoPreview = document.getElementById(`new-media-preview-video-${postId}`);
        const fileNameLabel = document.getElementById(`new-media-name-${postId}`);

        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            // Replacement overrides Remove: uncheck remove_media toggle
            const removeCheckbox = document.getElementById(`remove-media-${postId}`);
            if (removeCheckbox && removeCheckbox.checked) {
                removeCheckbox.checked = false;
                toggleRemoveMedia(removeCheckbox, postId);
            }

            reader.onload = function (e) {
                if (file.type.startsWith('video/')) {
                    if (videoPreview) {
                        videoPreview.src = e.target.result;
                        videoPreview.classList.remove('d-none');
                    }
                    if (imgPreview) {
                        imgPreview.classList.add('d-none');
                    }
                } else {
                    if (imgPreview) {
                        imgPreview.src = e.target.result;
                        imgPreview.classList.remove('d-none');
                    }
                    if (videoPreview) {
                        videoPreview.classList.add('d-none');
                    }
                }

                if (fileNameLabel) {
                    fileNameLabel.textContent = `Selected: ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                }
                if (container) {
                    container.classList.remove('d-none');
                }
            };

            reader.readAsDataURL(file);
        }
    }

    function cancelSelectedMedia(postId) {
        const input = document.getElementById(`post-media-${postId}`);
        const container = document.getElementById(`new-media-preview-container-${postId}`);
        const imgPreview = document.getElementById(`new-media-preview-img-${postId}`);
        const videoPreview = document.getElementById(`new-media-preview-video-${postId}`);
        const fileNameLabel = document.getElementById(`new-media-name-${postId}`);

        if (input) input.value = '';
        if (imgPreview) { imgPreview.src = ''; imgPreview.classList.add('d-none'); }
        if (videoPreview) { videoPreview.src = ''; videoPreview.classList.add('d-none'); }
        if (fileNameLabel) fileNameLabel.textContent = '';
        if (container) container.classList.add('d-none');
    }

    function resetEditPostModal(postId) {
        const removeCheckbox = document.getElementById(`remove-media-${postId}`);
        if (removeCheckbox && removeCheckbox.checked) {
            removeCheckbox.checked = false;
            toggleRemoveMedia(removeCheckbox, postId);
        }
        cancelSelectedMedia(postId);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Tab switching logic for Business Page Tabs
        const tabButtons = document.querySelectorAll('#businessPageTabs [data-bs-toggle="tab"]');
        const tabPanes = document.querySelectorAll('.tab-content .tab-pane');

        tabButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const targetSelector = this.getAttribute('data-bs-target');
                if (!targetSelector) return;

                // Deactivate all tab buttons & panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => {
                    pane.classList.remove('show', 'active');
                });

                // Activate clicked button & corresponding target pane
                this.classList.add('active');
                const targetPane = document.querySelector(targetSelector);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }

                // Update URL hash without scroll jump
                if (history.replaceState) {
                    history.replaceState(null, null, targetSelector);
                }

                // Re-render Feather icons in newly revealed panes
                if (window.feather) {
                    window.feather.replace();
                }
            });
        });

        // Restore tab from URL hash if provided
        const initialHash = window.location.hash;
        if (initialHash) {
            const initialButton = document.querySelector(`#businessPageTabs [data-bs-target="${initialHash}"]`);
            if (initialButton) {
                initialButton.click();
            }
        }

        // Clean modal reset when dismissed via backdrop / ESC
        document.querySelectorAll('.modal[id^="editPostModal"]').forEach(modalEl => {
            modalEl.addEventListener('hidden.bs.modal', function () {
                const postId = this.id.replace('editPostModal', '');
                resetEditPostModal(postId);
            });
        });
    });
</script>
@endpush
