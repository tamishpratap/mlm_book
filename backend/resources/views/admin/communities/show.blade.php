@extends('admin.layouts.master')
@section('title', 'Community Profile: ' . $community->name)
@section('page-subtitle', 'Read-only community details, members, join requests & moderation queue.')

@section('content')
@php
    $logoUrl = $community->logo ? (str_starts_with($community->logo, 'http') ? $community->logo : asset($community->logo)) : asset('admin_assets/images/dashboard/1.png');
    $coverUrl = $community->cover_photo ? (str_starts_with($community->cover_photo, 'http') ? $community->cover_photo : asset($community->cover_photo)) : null;
@endphp

<!-- Community Banner Header Card -->
<div class="card mb-4 overflow-hidden border shadow-sm">
    <!-- 1. COVER AREA -->
    <div class="position-relative" style="height: 175px; background: linear-gradient(135deg, #4338ca 0%, #312e81 50%, #0f172a 100%);">
        @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="Cover Photo" class="w-100 h-100" style="object-fit: cover; opacity: 0.85;">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.6) 100%);"></div>
        @else
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25" style="background-image: radial-gradient(rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 16px 16px;"></div>
        @endif
    </div>

    <!-- 2. COMMUNITY IDENTITY & ACTIONS SECTION (WHITE CARD BODY) -->
    <div class="card-body position-relative pt-0 pb-3 px-4 bg-white">
        <!-- Identity Top Row: Protruding Logo & Right Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <!-- Left: Protruding Logo + Identity Details -->
            <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3 text-center text-sm-start flex-grow-1">
                <!-- Protruding Logo (Only this element overlaps the cover) -->
                <div class="position-relative flex-shrink-0" style="margin-top: -55px; z-index: 2;">
                    <img src="{{ $logoUrl }}" alt="{{ $community->name }}" class="rounded-circle border border-4 border-white shadow" style="width: 110px; height: 110px; object-fit: cover; background: #fff;">
                </div>

                <!-- Text Details in Normal Flow (Inside White Card Body) -->
                <div class="pt-sm-2 flex-grow-1">
                    <!-- Title Line -->
                    <h3 class="fw-bold mb-1" style="font-size: 1.55rem; letter-spacing: -0.02em; color: #0f172a !important;">
                        {{ $community->name }}
                    </h3>

                    <!-- Slug / Identifier Badge -->
                    <div class="mb-2">
                        <span class="badge bg-light border text-secondary fw-semibold px-2 py-1" style="font-size: 0.82rem; font-family: monospace;">
                            <i class="fa fa-users me-1 text-primary"></i> c/{{ $community->slug }}
                        </span>
                    </div>

                    <!-- Badges Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mb-2">
                        <span class="badge bg-light-primary text-primary fw-semibold px-2.5 py-1.5" style="font-size: 0.8rem;">
                            <i class="fa fa-tag me-1"></i> {{ $community->category }}
                        </span>

                        @if($community->visibility === 'public')
                            <span class="badge bg-light-success text-success fw-bold px-2.5 py-1.5 border border-success-subtle">
                                <i class="fa fa-globe me-1"></i> Public Community
                            </span>
                        @elseif($community->visibility === 'private')
                            <span class="badge bg-light-warning text-warning fw-bold px-2.5 py-1.5 border border-warning-subtle">
                                <i class="fa fa-lock me-1"></i> Private Community
                            </span>
                        @else
                            <span class="badge bg-light-secondary text-secondary fw-bold px-2.5 py-1.5">
                                {{ ucfirst($community->visibility) }}
                            </span>
                        @endif

                        @if($community->status === 'active')
                            <span class="badge bg-light-success text-success fw-bold px-2.5 py-1.5 border border-success-subtle">
                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Active
                            </span>
                        @else
                            <span class="badge bg-danger text-white px-2.5 py-1.5 fw-bold">
                                <i class="fa fa-ban me-1"></i> Suspended
                            </span>
                        @endif
                    </div>

                    <!-- Creator & Creation Metadata Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 text-muted small mt-1">
                        <span>
                            <i class="fa fa-user text-primary me-1"></i>
                            Created by <strong class="text-dark">{{ $community->owner?->name ?? 'System' }}</strong>
                            @if($community->owner?->user_id)
                                <span class="text-muted">({{ $community->owner?->user_id }})</span>
                            @endif
                        </span>
                        <span class="text-muted">•</span>
                        <span>
                            <i class="fa fa-calendar-o text-muted me-1"></i>
                            Created on <span class="text-secondary">{{ $community->created_at?->format('F d, Y') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons Group -->
            <div class="pt-sm-2 d-flex justify-content-center justify-content-md-end gap-2 flex-wrap flex-shrink-0">
                <a href="{{ route('admin.communities.edit', $community) }}" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="edit-2" style="width: 15px; height: 15px;"></i> Edit Community
                </a>

                <form action="{{ route('admin.communities.status', $community) }}" method="POST" class="d-inline">
                    @csrf
                    @if($community->status === 'active')
                        <input type="hidden" name="status" value="suspended">
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="slash" style="width: 15px; height: 15px;"></i> Suspend Community
                        </button>
                    @else
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="check" style="width: 15px; height: 15px;"></i> Restore Community
                        </button>
                    @endif
                </form>

                <a href="{{ route('admin.communities.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="arrow-left" style="width: 15px; height: 15px;"></i> Back to Directory
                </a>
            </div>
        </div>

        <!-- 3. STATISTICS ROW -->
        <div class="row g-2 border-top pt-3 mt-3 text-center">
            <div class="col-6 col-sm-4 col-md">
                <h5 class="fw-bold mb-0 text-primary">{{ $community->accepted_members_count }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Total Members</small>
            </div>
            <div class="col-6 col-sm-4 col-md border-start">
                <h5 class="fw-bold mb-0 text-warning">{{ $community->pending_members_count }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Pending Requests</small>
            </div>
            <div class="col-6 col-sm-4 col-md border-start">
                <h5 class="fw-bold mb-0 text-info">{{ $postsCount ?? count($posts) }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Community Posts</small>
            </div>
            <div class="col-6 col-sm-6 col-md border-start">
                <h5 class="fw-bold mb-0 text-secondary">{{ count($moderators) }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Moderators</small>
            </div>
            <div class="col-12 col-sm-6 col-md border-start">
                <h5 class="fw-bold mb-0 text-danger">{{ $community->reports_count }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Reports Flagged</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabbed Navigation Details Card -->
<x-admin.card>
    <x-admin.tabs id="communityTabs" active="overview" :tabs="[
        'overview' => 'Overview & Rules',
        'members' => 'Members (' . $community->accepted_members_count . ')',
        'moderators' => 'Moderators (' . count($moderators) . ')',
        'requests' => 'Join Requests (' . $community->pending_members_count . ')',
        'posts' => 'Posts (' . ($postsCount ?? count($posts)) . ')',
        'reports' => 'Reports (' . $community->reports_count . ')'
    ]">
        <!-- 1. Overview Pane -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Community Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 160px;">Community Name:</td>
                            <td class="fw-bold text-dark">{{ $community->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Community Slug:</td>
                            <td><code class="text-primary">{{ $community->slug }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Category:</td>
                            <td><span class="badge bg-light-primary text-primary">{{ $community->category }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Visibility:</td>
                            <td>{{ ucfirst($community->visibility) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Status:</td>
                            <td>{{ ucfirst($community->status) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Join Approval:</td>
                            <td>{{ ucfirst($community->join_approval_mode ?: 'Auto') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Posting Permissions:</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $community->posting_permissions ?: 'Everyone')) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Invite Code:</td>
                            <td><code>{{ $community->invite_code ?: 'N/A' }}</code></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-2">Description</h6>
                    <div class="text-muted small bg-light p-3 rounded mb-3" style="min-height: 80px;">
                        {{ $community->description ?: 'No description provided for this community.' }}
                    </div>

                    <h6 class="fw-bold text-primary mb-2">Community Rules</h6>
                    <div class="text-muted small bg-light p-3 rounded mb-0" style="min-height: 80px;">
                        {{ $community->rules ?: 'No explicit rules defined.' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Members Pane -->
        <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
            <x-admin.table :headers="['Member', 'Role', 'Status', 'Joined Date', 'Actions']" :empty="$community->acceptedMembers->isEmpty()" emptyMessage="No community members found.">
                @foreach($community->acceptedMembers as $cm)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $cm->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div>
                                    <div class="fw-bold text-dark small">{{ $cm->member?->name ?? 'Deleted Member' }}</div>
                                    <small class="text-muted" style="font-size: 11px;">{{ $cm->member?->user_id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($cm->role === 'owner')
                                <span class="badge bg-danger">Owner</span>
                            @elseif($cm->role === 'admin')
                                <span class="badge bg-primary">Admin</span>
                            @elseif($cm->role === 'moderator')
                                <span class="badge bg-info">Moderator</span>
                            @else
                                <span class="badge bg-light-secondary text-secondary">Member</span>
                            @endif
                        </td>
                        <td><span class="badge bg-light-success text-success">{{ ucfirst($cm->status) }}</span></td>
                        <td><small class="text-muted">{{ $cm->joined_at?->format('M d, Y') ?? ($cm->created_at?->format('M d, Y') ?? 'N/A') }}</small></td>
                        <td>
                            @if($cm->member)
                                <a href="{{ route('admin.members.show', $cm->member) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Member Profile">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 3. Moderators Pane -->
        <div class="tab-pane fade" id="moderators" role="tabpanel" aria-labelledby="moderators-tab">
            <x-admin.table :headers="['Moderator', 'Assigned Role', 'Joined Date', 'Actions']" :empty="$moderators->isEmpty()" emptyMessage="No moderators found.">
                @foreach($moderators as $mod)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $mod->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div>
                                    <div class="fw-bold text-dark small">{{ $mod->member?->name ?? 'Moderator' }}</div>
                                    <small class="text-muted" style="font-size: 11px;">{{ $mod->member?->user_id }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-primary text-uppercase">{{ $mod->role }}</span></td>
                        <td><small class="text-muted">{{ $mod->joined_at?->format('M d, Y') ?? ($mod->created_at?->format('M d, Y') ?? 'N/A') }}</small></td>
                        <td>
                            @if($mod->member)
                                <a href="{{ route('admin.members.show', $mod->member) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Member Profile">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 4. Join Requests Pane -->
        <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
            <x-admin.table :headers="['Member', 'Requested Date', 'Actions']" :empty="$community->pendingMembers->isEmpty()" emptyMessage="No pending join requests.">
                @foreach($community->pendingMembers as $req)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $req->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div>
                                    <div class="fw-bold text-dark small">{{ $req->member?->name ?? 'Requester' }}</div>
                                    <small class="text-muted" style="font-size: 11px;">{{ $req->member?->user_id }}</small>
                                </div>
                            </div>
                        </td>
                        <td><small class="text-muted">{{ $req->created_at?->format('M d, Y H:i') }}</small></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <form action="{{ route('admin.communities.join-requests', [$community, $req]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                        <i data-feather="check" style="width: 14px; height: 14px;"></i> Approve
                                    </button>
                                </form>

                                <form action="{{ route('admin.communities.join-requests', [$community, $req]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                        <i data-feather="x" style="width: 14px; height: 14px;"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 5. Posts Pane -->
        <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
            <x-admin.table :headers="['Post ID', 'Author', 'Content Preview', 'Media', 'Interactions', 'Status', 'Created Date', 'Actions']" :empty="$posts->isEmpty()" emptyMessage="No community posts found.">
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

        <!-- 6. Reports Pane -->
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
            <x-admin.table :headers="['Report ID', 'Reporter', 'Reason', 'Details', 'Status', 'Date', 'Action']" :empty="$community->reports->isEmpty()" emptyMessage="No reports found.">
                @foreach($community->reports as $report)
                    <tr>
                        <td>#{{ $report->id }}</td>
                        <td><small class="fw-bold text-dark">{{ $report->reporter?->name ?? 'Anonymous' }}</small></td>
                        <td><span class="badge bg-danger">{{ $report->reason }}</span></td>
                        <td><small class="text-muted">{{ $report->details ?: 'N/A' }}</small></td>
                        <td>
                            @if($report->status === 'resolved')
                                <span class="badge bg-light-success text-success">Resolved</span>
                            @else
                                <span class="badge bg-light-warning text-warning">{{ ucfirst($report->status) }}</span>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $report->created_at?->format('M d, Y') }}</small></td>
                        <td>
                            @if($report->status !== 'resolved')
                                <form action="{{ route('admin.communities.reports.resolve', $report) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                                        <i data-feather="check-circle" style="width: 14px; height: 14px;"></i> Mark Resolved
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-success"><i class="fa fa-check me-1"></i> Resolved</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>
    </x-admin.tabs>
</x-admin.card>

<!-- ========================================== -->
<!-- MODALS FOR COMMUNITY POSTS                 -->
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
        // Tab switching logic for Community Profile Tabs
        const tabButtons = document.querySelectorAll('#communityTabs [data-bs-toggle="tab"]');
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
            const initialButton = document.querySelector(`#communityTabs [data-bs-target="${initialHash}"]`);
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
