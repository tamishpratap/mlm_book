@extends('admin.layouts.master')
@section('title', 'Event Inspection: ' . $event->title)
@section('page-subtitle', 'Read-only event details, organizer info, RSVPs & event discussion.')

@section('content')
@php
    $bannerUrl = $event->banner ? (str_starts_with($event->banner, 'http') ? $event->banner : asset($event->banner)) : ($event->cover_photo ? (str_starts_with($event->cover_photo, 'http') ? $event->cover_photo : asset($event->cover_photo)) : null);
@endphp

<!-- Event Banner Header Card -->
<div class="card mb-4 overflow-hidden border shadow-sm">
    <!-- 1. COVER AREA -->
    <div class="position-relative" style="height: 175px; background: linear-gradient(135deg, #4f46e5 0%, #3730a3 50%, #0f172a 100%);">
        @if($bannerUrl)
            <img src="{{ $bannerUrl }}" alt="Event Banner" class="w-100 h-100" style="object-fit: cover; opacity: 0.85;">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.6) 100%);"></div>
        @else
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25" style="background-image: radial-gradient(rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 16px 16px;"></div>
        @endif
    </div>

    <!-- 2. EVENT IDENTITY & ACTIONS SECTION (WHITE CARD BODY) -->
    <div class="card-body position-relative pt-0 pb-3 px-4 bg-white">
        <!-- Identity Top Row: Protruding Icon & Right Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <!-- Left: Protruding Icon + Identity Details -->
            <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3 text-center text-sm-start flex-grow-1">
                <!-- Protruding Category/Event Avatar (Only this overlaps cover boundary) -->
                <div class="position-relative flex-shrink-0" style="margin-top: -55px; z-index: 2;">
                    <div class="rounded-circle border border-4 border-white shadow d-flex align-items-center justify-content-center bg-white text-primary" style="width: 110px; height: 110px; font-size: 38px;">
                        <i class="fa fa-calendar"></i>
                    </div>
                </div>

                <!-- Text Details in Normal Flow (Inside White Card Body) -->
                <div class="pt-sm-2 flex-grow-1">
                    <!-- Title Line -->
                    <h3 class="fw-bold mb-1" style="font-size: 1.55rem; letter-spacing: -0.02em; color: #0f172a !important;">
                        {{ $event->title }}
                    </h3>

                    <!-- Slug / Identifier Badge -->
                    <div class="mb-2">
                        <span class="badge bg-light border text-secondary fw-semibold px-2 py-1" style="font-size: 0.82rem; font-family: monospace;">
                            <i class="fa fa-link me-1 text-primary"></i> {{ $event->slug }}
                        </span>
                    </div>

                    <!-- Badges Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mb-2">
                        <span class="badge bg-light-primary text-primary fw-semibold px-2.5 py-1.5" style="font-size: 0.8rem;">
                            <i class="fa fa-tag me-1"></i> {{ $event->category }}
                        </span>

                        <span class="badge bg-light-info text-info fw-bold px-2.5 py-1.5 border border-info-subtle">
                            <i class="fa fa-{{ $event->event_type === 'online' ? 'video-camera' : 'map-marker' }} me-1"></i>
                            {{ ucfirst($event->event_type) }} Event
                        </span>

                        <span class="badge bg-light-secondary text-secondary fw-semibold px-2.5 py-1.5">
                            {{ ucfirst(str_replace('_', ' ', $event->privacy)) }}
                        </span>

                        @if($event->status === 'published')
                            <span class="badge bg-light-success text-success fw-bold px-2.5 py-1.5 border border-success-subtle">
                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Published
                            </span>
                        @elseif($event->status === 'cancelled')
                            <span class="badge bg-danger text-white px-2.5 py-1.5 fw-bold">
                                <i class="fa fa-ban me-1"></i> Cancelled
                            </span>
                        @else
                            <span class="badge bg-secondary text-white px-2.5 py-1.5 fw-bold">
                                {{ ucfirst($event->status) }}
                            </span>
                        @endif
                    </div>

                    <!-- Organizer & Schedule Metadata Row -->
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 text-muted small mt-1">
                        <span>
                            <i class="fa fa-user text-primary me-1"></i>
                            Organized by <strong class="text-dark">{{ $event->organizer?->name ?? 'System' }}</strong>
                            @if($event->organizer?->user_id)
                                <span class="text-muted">({{ $event->organizer?->user_id }})</span>
                            @endif
                        </span>
                        <span class="text-muted">•</span>
                        <span>
                            <i class="fa fa-calendar-o text-muted me-1"></i>
                            Start Date: <span class="text-secondary fw-semibold">{{ $event->start_date?->format('F d, Y') }}</span>
                        </span>
                        @if($event->start_time)
                            <span class="text-muted">•</span>
                            <span>
                                <i class="fa fa-clock-o text-muted me-1"></i>
                                Start Time: <span class="text-secondary fw-semibold">{{ $event->start_time }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons Group -->
            <div class="pt-sm-2 d-flex justify-content-center justify-content-md-end gap-2 flex-wrap flex-shrink-0">
                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="edit-2" style="width: 15px; height: 15px;"></i> Edit Event
                </a>

                <form action="{{ route('admin.events.status', $event) }}" method="POST" class="d-inline">
                    @csrf
                    @if($event->status === 'published')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="slash" style="width: 15px; height: 15px;"></i> Cancel Event
                        </button>
                    @else
                        <input type="hidden" name="status" value="published">
                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="check" style="width: 15px; height: 15px;"></i> Restore Event
                        </button>
                    @endif
                </form>

                <a href="{{ route('admin.events.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="arrow-left" style="width: 15px; height: 15px;"></i> Back to Directory
                </a>
            </div>
        </div>

        <!-- 3. STATISTICS ROW -->
        <div class="row g-2 border-top pt-3 mt-3 text-center">
            <div class="col-6 col-sm-4 col-md">
                <h5 class="fw-bold mb-0 text-primary">{{ $goingCount }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Going</small>
            </div>
            <div class="col-6 col-sm-4 col-md border-start">
                <h5 class="fw-bold mb-0 text-info">{{ $interestedCount }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Interested</small>
            </div>
            <div class="col-6 col-sm-4 col-md border-start">
                <h5 class="fw-bold mb-0 text-secondary">{{ count($event->invitations) }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Invited</small>
            </div>
            <div class="col-6 col-sm-6 col-md border-start">
                <h5 class="fw-bold mb-0 text-dark">{{ $event->max_guests ?: 'Unlimited' }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Max Capacity</small>
            </div>
            <div class="col-12 col-sm-6 col-md border-start">
                <h5 class="fw-bold mb-0 text-warning">{{ $postsCount ?? count($event->posts) }}</h5>
                <small class="text-muted text-uppercase" style="font-size: 11px;">Event Posts</small>
            </div>
        </div>
    </div>
</div>

<!-- Tabbed Navigation Details Card -->
<x-admin.card>
    <x-admin.tabs id="eventTabs" active="overview" :tabs="[
        'overview' => 'Overview & Venue/Link',
        'organizer' => 'Organizer Info',
        'participants' => 'RSVP Participants (' . count($event->responses) . ')',
        'posts' => 'Discussion Posts (' . ($postsCount ?? count($event->posts)) . ')'
    ]">
        <!-- 1. Overview Pane -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Event Specifications</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 160px;">Event Title:</td>
                            <td class="fw-bold text-dark">{{ $event->title }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Category:</td>
                            <td><span class="badge bg-light-primary text-primary">{{ $event->category }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Event Type:</td>
                            <td>{{ ucfirst($event->event_type) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Privacy:</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $event->privacy)) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Status:</td>
                            <td>{{ ucfirst($event->status) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Start Date & Time:</td>
                            <td>{{ $event->start_date?->format('F d, Y') }} {{ $event->start_time }} {{ $event->timezone ? '(' . $event->timezone . ')' : '' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">End Date & Time:</td>
                            <td>{{ $event->end_date ? $event->end_date->format('F d, Y') : 'Same Day' }} {{ $event->end_time ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Max Capacity:</td>
                            <td>{{ $event->max_guests ?: 'Unlimited' }}</td>
                        </tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-2 mt-3">Location & Access</h6>
                    @if($event->event_type === 'online')
                        <div class="p-3 bg-light rounded border">
                            <div class="fw-bold text-dark mb-1"><i class="fa fa-video-camera text-info me-1"></i> Online Meeting Link</div>
                            @if($event->meeting_link)
                                <a href="{{ $event->meeting_link }}" target="_blank" class="text-primary text-break">{{ $event->meeting_link }}</a>
                            @else
                                <span class="text-muted">Link not provided</span>
                            @endif
                            @if($event->meeting_password)
                                <div class="small text-muted mt-1">Passcode / Password: <code>{{ $event->meeting_password }}</code></div>
                            @endif
                        </div>
                    @else
                        <div class="p-3 bg-light rounded border">
                            <div class="fw-bold text-dark mb-1"><i class="fa fa-map-marker text-danger me-1"></i> Venue Location</div>
                            <div>{{ $event->location_address ?: 'Address not specified' }}</div>
                            <div class="small text-muted">{{ implode(', ', array_filter([$event->location_city, $event->location_state, $event->location_country])) }}</div>
                            @if($event->google_maps_link)
                                <a href="{{ $event->google_maps_link }}" target="_blank" class="small text-primary mt-1 d-inline-block"><i class="fa fa-external-link me-1"></i> Open Google Maps</a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-2">Short Summary</h6>
                    <div class="text-muted small bg-light p-3 rounded mb-3 border" style="min-height: 80px;">
                        {{ $event->short_description ?: 'No short description provided.' }}
                    </div>

                    <h6 class="fw-bold text-primary mb-2">Full Description</h6>
                    <div class="text-muted small bg-light p-3 rounded mb-0 border" style="min-height: 120px; white-space: pre-line;">
                        {{ $event->description ?: 'No full description provided for this event.' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Organizer Pane -->
        <div class="tab-pane fade" id="organizer" role="tabpanel" aria-labelledby="organizer-tab">
            <div class="d-flex flex-column flex-sm-row align-items-center gap-3 mb-3 p-3 bg-light rounded border">
                <img class="rounded-circle border" src="{{ $event->organizer?->avatar_url }}" style="width: 60px; height: 60px; object-fit: cover; background: #fff;">
                <div class="text-center text-sm-start flex-grow-1">
                    <h5 class="fw-bold text-dark mb-0">{{ $event->organizer?->name ?? 'Deleted Member' }}</h5>
                    <code class="text-primary">{{ $event->organizer?->user_id }}</code>
                    <p class="text-muted small mb-0 mt-1">
                        <i class="fa fa-envelope-o me-1"></i> {{ $event->organizer?->email }}
                        <span class="mx-2">•</span>
                        <i class="fa fa-phone me-1"></i> {{ $event->organizer?->phone ?? 'Phone not provided' }}
                    </p>
                </div>
                <div class="mt-2 mt-sm-0">
                    @if($event->organizer)
                        <a href="{{ route('admin.members.show', $event->organizer) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                            <i data-feather="user" style="width: 14px; height: 14px;"></i> View Organizer Profile
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. RSVP Participants Pane -->
        <div class="tab-pane fade" id="participants" role="tabpanel" aria-labelledby="participants-tab">
            <x-admin.table :headers="['Participant', 'User ID', 'RSVP Status', 'Responded Date']" :empty="$event->responses->isEmpty()" emptyMessage="No member responses / RSVPs recorded for this event yet.">
                @foreach($event->responses as $resp)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="rounded-circle" src="{{ $resp->member?->avatar_url }}" style="width: 32px; height: 32px; object-fit: cover; background: #fff;">
                                <div class="fw-bold text-dark small">{{ $resp->member?->name ?? 'Member' }}</div>
                            </div>
                        </td>
                        <td><code>{{ $resp->member?->user_id }}</code></td>
                        <td>
                            @if($resp->response === 'going')
                                <span class="badge bg-success">Going</span>
                            @elseif($resp->response === 'interested')
                                <span class="badge bg-info">Interested</span>
                            @elseif($resp->response === 'declined')
                                <span class="badge bg-secondary">Declined</span>
                            @else
                                <span class="badge bg-light-secondary text-secondary">{{ ucfirst($resp->response) }}</span>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $resp->created_at?->format('M d, Y H:i') }}</small></td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 4. Discussion Posts Pane -->
        <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
            <x-admin.table :headers="['Post ID', 'Author', 'Content Preview', 'Media', 'Interactions', 'Status', 'Created Date', 'Actions']" :empty="$posts->isEmpty()" emptyMessage="No discussion posts published in this event.">
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
    </x-admin.tabs>
</x-admin.card>

<!-- ========================================== -->
<!-- MODALS FOR EVENT DISCUSSION POSTS          -->
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
        // Tab switching logic for Event Tabs
        const tabButtons = document.querySelectorAll('#eventTabs [data-bs-toggle="tab"]');
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
            const initialButton = document.querySelector(`#eventTabs [data-bs-target="${initialHash}"]`);
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
