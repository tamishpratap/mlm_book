@extends('admin.layouts.master')
@section('title', 'Member Profile: ' . $member->name)
@section('page-subtitle', 'Read-only profile details, content moderation & community statistics.')

@section('content')
<!-- Member Profile Banner Header Card -->
<div class="card mb-4 overflow-hidden border shadow-sm">
    <!-- Cover Banner -->
    <div class="position-relative" style="height: 190px; background: linear-gradient(135deg, #1e1b4b 0%, #2e1065 50%, #312e81 100%);">
        @if($member->cover_photo_url)
            <img src="{{ $member->cover_photo_url }}" alt="Cover Photo" class="w-100 h-100" style="object-fit: cover; opacity: 0.75;">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.6) 100%);"></div>
        @else
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25" style="background-image: radial-gradient(rgba(255,255,255,0.2) 1px, transparent 1px); background-size: 16px 16px;"></div>
        @endif
    </div>
    
    <!-- Profile Info Bar -->
    <div class="card-body position-relative pt-0 pb-3 px-4 bg-white">
        <div class="row align-items-end justify-content-between g-3" style="margin-top: -55px;">
            <!-- Left: Avatar + Details -->
            <div class="col-12 col-md-auto d-flex flex-column flex-sm-row align-items-center align-items-sm-end gap-3 text-center text-sm-start">
                <div class="position-relative flex-shrink-0">
                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="rounded-circle border border-4 border-white shadow" style="width: 110px; height: 110px; object-fit: cover; background: #fff;">
                </div>
                
                <div class="pb-1">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mb-1">
                        <h3 class="fw-bold text-dark mb-0" style="font-size: 1.5rem; letter-spacing: -0.02em; color: #0f172a !important;">
                            {{ $member->name }}
                        </h3>
                        <span class="badge bg-light border text-primary fw-semibold px-2 py-1" style="font-size: 0.8rem; font-family: monospace;">
                            {{ '@' . ltrim($member->user_id, '@') }}
                        </span>
                        @if($member->isBlocked())
                            <span class="badge bg-danger text-white px-2 py-1 fw-bold">
                                <i class="fa fa-ban me-1"></i> Account Blocked
                            </span>
                        @elseif($member->mobile_verified_at)
                            <span class="badge bg-success text-white px-2 py-1 fw-bold">
                                <i class="fa fa-check-circle me-1"></i> VERIFIED MEMBER
                            </span>
                        @else
                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">
                                <i class="fa fa-clock-o me-1"></i> UNVERIFIED MEMBER
                            </span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 text-secondary small">
                        <span>
                            <i class="fa fa-map-marker text-primary me-1"></i>
                            <strong class="text-dark">{{ implode(', ', array_filter([$member->city, $member->country])) ?: 'Location not specified' }}</strong>
                        </span>
                        <span class="text-muted">•</span>
                        <span>
                            <i class="fa fa-clock-o text-muted me-1"></i>
                            <span class="text-secondary">{{ $member->onlineStatusLabel() }}</span>
                        </span>
                        <span class="text-muted">•</span>
                        <span>
                            <i class="fa fa-calendar-o text-muted me-1"></i>
                            Joined <span class="text-secondary">{{ $member->created_at?->format('M Y') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="col-12 col-md-auto pb-1 d-flex justify-content-center justify-content-md-end gap-2 flex-wrap">
                <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="edit" style="width: 15px; height: 15px;"></i> Edit Member
                </a>

                @if($member->isBlocked())
                    <form action="{{ route('admin.members.unblock', $member) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="check-circle" style="width: 15px; height: 15px;"></i> Unblock Member
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.members.block', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Block member {{ $member->name }}?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="slash" style="width: 15px; height: 15px;"></i> Block Member
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.members.status', $member) }}" method="POST" class="d-inline">
                    @csrf
                    @if($member->mobile_verified_at)
                        <input type="hidden" name="action" value="unverify">
                        <button type="submit" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="x-circle" style="width: 15px; height: 15px;"></i> Revoke Verification
                        </button>
                    @else
                        <input type="hidden" name="action" value="verify">
                        <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i data-feather="check" style="width: 15px; height: 15px;"></i> Verify Member
                        </button>
                    @endif
                </form>

                <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                    <i data-feather="arrow-left" style="width: 15px; height: 15px;"></i> Back to Directory
                </a>
            </div>
        </div>

        <!-- Metric Stat Row -->
        <div class="row g-2 border-top pt-3 mt-2 text-center">
            <div class="col">
                <h5 class="fw-bold mb-0 text-primary">{{ $member->posts_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Posts</small>
            </div>
            <div class="col border-start">
                <h5 class="fw-bold mb-0 text-info">{{ $member->stories_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Stories</small>
            </div>
            <div class="col border-start">
                <h5 class="fw-bold mb-0 text-secondary">{{ $member->joined_communities_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Communities</small>
            </div>
            <div class="col border-start">
                <h5 class="fw-bold mb-0 text-success">{{ $productsCount ?? $products->count() }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Marketplace</small>
            </div>
            <div class="col border-start">
                <h5 class="fw-bold mb-0 text-warning">{{ $member->events_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Events</small>
            </div>
            <div class="col border-start">
                <h5 class="fw-bold mb-0 text-danger">{{ $member->reports_count }}</h5>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Reports</small>
            </div>
            <div class="col border-start cursor-pointer user-select-none" data-bs-toggle="modal" data-bs-target="#connectionsModal" style="cursor: pointer;" title="Click to view all {{ $connectionsCount ?? 0 }} accepted connections">
                <h5 class="fw-bold mb-0 text-dark">{{ $connectionsCount ?? 0 }}</h5>
                <small class="text-muted text-uppercase fw-semibold d-flex align-items-center justify-content-center gap-1" style="font-size: 11px; letter-spacing: 0.5px;">
                    Connections <i data-feather="external-link" style="width: 11px; height: 11px;" class="text-primary"></i>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Tabbed Details Section -->
<x-admin.card>
    <x-admin.tabs id="memberProfileTabs" active="overview" :tabs="[
        'overview' => 'Overview & Info',
        'posts' => 'Posts (' . $member->posts_count . ')',
        'stories' => 'Stories (' . $member->stories_count . ')',
        'communities' => 'Communities (' . $member->joined_communities_count . ')',
        'marketplace' => 'Marketplace (' . ($productsCount ?? $products->count()) . ')',
        'events' => 'Events (' . $member->events_count . ')',
        'reports' => 'Reports (' . $member->reports_count . ')'
    ]">
        <!-- 1. Overview Pane -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Basic Information</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 140px;">Full Name:</td>
                            <td class="fw-bold text-dark">{{ $member->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">User ID:</td>
                            <td><code class="text-primary">{{ $member->user_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Email:</td>
                            <td>{{ $member->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Phone:</td>
                            <td>{{ $member->phone ?? 'Not provided' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Gender:</td>
                            <td>{{ ucfirst($member->gender ?? 'Not specified') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Date of Birth:</td>
                            <td>{{ $member->date_of_birth?->format('M d, Y') ?? 'Not specified' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold text-primary mb-3">Location & Biography</h6>
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 140px;">City:</td>
                            <td>{{ $member->city ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Country:</td>
                            <td>{{ $member->country ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Website:</td>
                            <td>
                                @if($member->website)
                                    <a href="{{ $member->website }}" target="_blank" class="text-primary">{{ $member->website }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Joined On:</td>
                            <td>{{ $member->created_at?->format('F d, Y - H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Last Activity:</td>
                            <td>{{ $member->onlineStatusLabel() }}</td>
                        </tr>
                    </table>

                    <h6 class="fw-bold text-primary mb-2">Bio</h6>
                    <p class="text-muted small bg-light p-3 rounded mb-0">
                        {{ $member->bio ?: 'No bio details provided by member.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. Posts Pane (with View, Edit, Block/Hide, Delete Actions) -->
        <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
            <x-admin.table :headers="['ID', 'Type', 'Content / Media', 'Likes', 'Comments', 'Status', 'Created At', 'Actions']" :empty="$posts->isEmpty()" emptyMessage="No posts authored by this member.">
                @foreach($posts as $post)
                    @php
                        $isPostHidden = \App\Models\HiddenPost::where('post_id', $post->id)->where('member_id', $post->member_id)->exists();
                        $isShared = $post->isShared();
                        $originalPost = $isShared ? $post->originalPost : null;
                        $resolvedPost = ($isShared && $originalPost) ? $originalPost : $post;
                        $effectiveMediaType = $resolvedPost->media_type;
                        $effectiveMediaPath = $resolvedPost->media_path;
                    @endphp
                    <tr>
                        <td>
                            #{{ $post->id }}
                            @if($isShared)
                                <span class="badge bg-light-primary text-primary d-block mt-1" style="font-size: 9px; padding: 1px 3px;"><i class="fa fa-share me-1"></i> Reshare</span>
                            @endif
                        </td>
                        <td>
                            @if($effectiveMediaType)
                                <span class="badge bg-light-primary text-primary">{{ ucfirst($effectiveMediaType) }} {{ $isShared ? '(Shared)' : '' }}</span>
                            @else
                                <span class="badge bg-light-secondary text-secondary">Text</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 320px;">
                                @if($post->body)
                                    {{ $post->body }}
                                @elseif($isShared && $originalPost)
                                    <span class="text-muted fst-italic">{{ $originalPost->body ?: '[Reshared Media]' }}</span>
                                @elseif($effectiveMediaPath)
                                    <span class="text-muted font-italic"><i class="fa fa-image me-1"></i> [Attached {{ ucfirst($effectiveMediaType ?? 'Media') }}]</span>
                                @else
                                    <span class="text-muted">No text content</span>
                                @endif
                            </div>
                            @if($isShared && $originalPost)
                                <small class="text-muted d-block text-truncate mt-1" style="font-size: 11px;">
                                    <i class="fa fa-share text-primary me-1"></i> Shared from <strong>{{ $originalPost->member?->name ?? 'Original Author' }}</strong>
                                </small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark"><i class="fa fa-thumbs-up text-primary me-1"></i> {{ $post->likes_count ?? 0 }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark"><i class="fa fa-comment text-secondary me-1"></i> {{ $post->comments_count ?? 0 }}</span>
                        </td>
                        <td>
                            @if($isPostHidden)
                                <x-admin.badge variant="danger" :light="true">Hidden</x-admin.badge>
                            @else
                                <x-admin.badge variant="success" :light="true">Visible</x-admin.badge>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $post->created_at?->format('M d, Y H:i') }}</small></td>
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

        <!-- 3. Stories Pane (with View and Delete Actions) -->
        <div class="tab-pane fade" id="stories" role="tabpanel" aria-labelledby="stories-tab">
            <x-admin.table :headers="['ID', 'Media Type', 'Caption', 'Expires At', 'Status', 'Created At', 'Actions']" :empty="$stories->isEmpty()" emptyMessage="No stories posted by this member.">
                @foreach($stories as $story)
                    <tr>
                        <td>#{{ $story->id }}</td>
                        <td><span class="badge bg-light-info text-info">{{ ucfirst($story->media_type ?? 'image') }}</span></td>
                        <td>
                            <div class="text-truncate" style="max-width: 280px;">
                                {{ $story->caption ?: 'No caption' }}
                            </div>
                        </td>
                        <td><small class="text-muted">{{ $story->expires_at ? Carbon\Carbon::parse($story->expires_at)->format('M d, Y H:i') : 'N/A' }}</small></td>
                        <td>
                            @if($story->expires_at && Carbon\Carbon::parse($story->expires_at)->isPast())
                                <x-admin.badge variant="secondary" :light="true">Expired</x-admin.badge>
                            @else
                                <x-admin.badge variant="success" :light="true">Active</x-admin.badge>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $story->created_at?->format('M d, Y H:i') }}</small></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <!-- View Story Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewStoryModal{{ $story->id }}" title="View Story">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </button>

                                <!-- Delete Story Action -->
                                <form action="{{ route('admin.stories.destroy', $story) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete story #{{ $story->id }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Story">
                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 4. Communities Pane (with View and Remove Membership Actions) -->
        <div class="tab-pane fade" id="communities" role="tabpanel" aria-labelledby="communities-tab">
            <x-admin.table :headers="['Community Name', 'Role', 'Status', 'Joined Date', 'Actions']" :empty="$communities->isEmpty()" emptyMessage="Not joined in any community.">
                @foreach($communities as $community)
                    <tr>
                        <td class="fw-bold text-dark">{{ $community->name }}</td>
                        <td><span class="badge bg-light-primary text-primary">{{ ucfirst($community->pivot->role ?? 'member') }}</span></td>
                        <td><span class="badge bg-light-success text-success">{{ ucfirst($community->pivot->status ?? 'accepted') }}</span></td>
                        <td><small class="text-muted">{{ $community->pivot->joined_at ? Carbon\Carbon::parse($community->pivot->joined_at)->format('M d, Y') : ($community->pivot->created_at ? Carbon\Carbon::parse($community->pivot->created_at)->format('M d, Y') : 'N/A') }}</small></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('admin.communities.show', $community->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Community Details">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>

                                <form action="{{ route('admin.members.remove-community', [$member, $community]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove member from community {{ $community->name }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Remove Membership">
                                        <i data-feather="user-x" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 5. Marketplace Pane (with View and Delete Actions) -->
        <div class="tab-pane fade" id="marketplace" role="tabpanel" aria-labelledby="marketplace-tab">
            <x-admin.table :headers="['Product Name', 'Price', 'Condition', 'Status', 'Listed On', 'Actions']" :empty="$products->isEmpty()" emptyMessage="No marketplace items listed.">
                @foreach($products as $product)
                    <tr>
                        <td class="fw-bold text-dark">{{ $product->title ?? $product->name }}</td>
                        <td><span class="fw-bold text-success">${{ number_format($product->price ?? 0, 2) }}</span></td>
                        <td><span class="badge bg-light-secondary text-secondary">{{ ucfirst(str_replace('_', ' ', $product->condition ?? 'N/A')) }}</span></td>
                        <td><span class="badge bg-light-primary text-primary">{{ ucfirst($product->status ?? 'active') }}</span></td>
                        <td><small class="text-muted">{{ $product->created_at?->format('M d, Y') }}</small></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('admin.marketplace.show', $product->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Product Inspection">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>

                                <form action="{{ route('admin.marketplace.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete product {{ $product->title }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Product">
                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 6. Events Pane (with View and Delete Actions) -->
        <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
            <x-admin.table :headers="['Event Title', 'Category', 'Location', 'Event Date & Time', 'Status', 'Actions']" :empty="$events->isEmpty()" emptyMessage="No events organized.">
                @foreach($events as $event)
                    <tr>
                        <td class="fw-bold text-dark">{{ $event->title }}</td>
                        <td><span class="badge bg-light-info text-info">{{ ucfirst($event->category ?? 'General') }}</span></td>
                        <td><small class="text-muted">{{ implode(', ', array_filter([$event->location_city, $event->location_country])) ?: ($event->location_address ?: 'Online') }}</small></td>
                        <td>
                            <small class="text-primary fw-bold">
                                {{ $event->start_date ? Carbon\Carbon::parse($event->start_date)->format('M d, Y') : 'N/A' }}
                                {{ $event->start_time ? Carbon\Carbon::parse($event->start_time)->format('H:i') : '' }}
                            </small>
                        </td>
                        <td><span class="badge bg-light-success text-success">{{ ucfirst($event->status ?? 'published') }}</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('admin.events.show', $event->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="View Event Inspection">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>

                                <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete event {{ $event->title }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Event">
                                        <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>

        <!-- 7. Reports Pane (with Resolve and Dismiss Actions) -->
        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
            <x-admin.table :headers="['Report ID', 'Target Post', 'Reason', 'Description', 'Status', 'Reported On', 'Actions']" :empty="$reports->isEmpty()" emptyMessage="No reports recorded for this member.">
                @foreach($reports as $report)
                    <tr>
                        <td>#{{ $report->id }}</td>
                        <td>
                            <span class="badge bg-light-secondary text-secondary">Post #{{ $report->post_id }}</span>
                        </td>
                        <td><span class="text-danger fw-bold">{{ $report->reason ?? 'Policy Violation' }}</span></td>
                        <td>
                            <div class="text-truncate" style="max-width: 250px;">
                                {{ $report->description ?: 'No details provided' }}
                            </div>
                        </td>
                        <td>
                            @if($report->status === 'resolved')
                                <x-admin.badge variant="success" :light="true">Resolved</x-admin.badge>
                            @elseif($report->status === 'dismissed')
                                <x-admin.badge variant="secondary" :light="true">Dismissed</x-admin.badge>
                            @else
                                <x-admin.badge variant="warning" :light="true">Pending Review</x-admin.badge>
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $report->created_at?->format('M d, Y H:i') }}</small></td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <form action="{{ route('admin.posts.reports.status', $report) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Mark Resolved">
                                        <i data-feather="check" style="width: 13px; height: 13px;"></i>
                                    </button>
                                </form>

                                <form action="{{ route('admin.posts.reports.status', $report) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="dismissed">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Dismiss Report">
                                        <i data-feather="x" style="width: 13px; height: 13px;"></i>
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
<!-- MODALS FOR POST INSPECTION & EDITING       -->
<!-- ========================================== -->
@foreach($posts as $post)
    <!-- View Post Details Modal -->
    <div class="modal fade" id="viewPostModal{{ $post->id }}" tabindex="-1" aria-labelledby="viewPostModalLabel{{ $post->id }}" aria-hidden="true">
        @php
            $isSharedModal = $post->isShared();
            $origPostModal = $isSharedModal ? $post->originalPost : null;
            $resolvedPostModal = ($isSharedModal && $origPostModal) ? $origPostModal : $post;
            $effectiveMediaTypeModal = $resolvedPostModal->media_type;
            $effectiveMediaPathModal = $resolvedPostModal->media_path;
            $effectiveMediaUrlModal = $effectiveMediaPathModal ? (str_starts_with($effectiveMediaPathModal, 'http') ? $effectiveMediaPathModal : asset($effectiveMediaPathModal)) : null;
        @endphp
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-height: calc(100dvh - 2rem);">
            <div class="modal-content overflow-hidden" style="max-height: calc(100dvh - 2rem); display: flex; flex-direction: column;">
                <div class="modal-header flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="viewPostModalLabel{{ $post->id }}">
                        <i data-feather="file-text" class="me-2 text-primary" style="width: 18px; height: 18px;"></i>
                        Post #{{ $post->id }} Inspection
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body flex-grow-1" style="overflow-y: auto; max-height: calc(100dvh - 180px); min-height: 0;">
                    <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded">
                        <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="rounded-circle" style="width: 42px; height: 42px; object-fit: cover;">
                        <div>
                            <div class="fw-bold text-dark">{{ $member->name }}</div>
                            <small class="text-muted">{{ $member->user_id }} • {{ $post->created_at?->format('M d, Y H:i') }}</small>
                            @if($isSharedModal)
                                <span class="badge bg-light-primary text-primary ms-2" style="font-size: 10px;"><i class="fa fa-share me-1"></i> Reshare</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold text-muted small text-uppercase">Content / Body:</h6>
                        <div class="p-3 bg-light rounded text-dark">
                            {{ $post->body ?: ($isSharedModal ? 'No additional commentary added.' : 'No text content provided.') }}
                        </div>
                    </div>

                    @if($isSharedModal && $origPostModal)
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img class="rounded-circle" src="{{ $origPostModal->member?->avatar_url }}" style="width: 28px; height: 28px; object-fit: cover;">
                                <div>
                                    <strong class="text-dark small">{{ $origPostModal->member?->name ?? 'Original Author' }}</strong>
                                    <small class="text-muted" style="font-size: 11px;">• Post #{{ $origPostModal->id }}</small>
                                </div>
                            </div>
                            @if($origPostModal->body)
                                <p class="small text-dark mb-2">{{ $origPostModal->body }}</p>
                            @endif
                        </div>
                    @endif

                    @if($effectiveMediaPathModal)
                        <div class="mb-3">
                            <h6 class="fw-bold text-muted small text-uppercase">Attached {{ ucfirst($effectiveMediaTypeModal ?? 'Media') }} {{ $isSharedModal ? '(Original Shared Media)' : '' }}:</h6>
                            <div class="p-2 border rounded text-center bg-dark">
                                @if($effectiveMediaTypeModal === 'video')
                                    <video src="{{ $effectiveMediaUrlModal }}" controls class="img-fluid rounded" style="max-height: 250px;"></video>
                                @else
                                    <img src="{{ $effectiveMediaUrlModal }}" alt="Post Media" class="img-fluid rounded" style="max-height: 250px; object-fit: contain;">
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
                            <div class="fw-bold text-dark">{{ ucfirst($effectiveMediaTypeModal ?: 'Text') }} {{ $isSharedModal ? '(Shared)' : '' }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Post Modal (Text + Image/Media Support with Fixed Viewport & Internal Scroll) -->
    <div class="modal fade" id="editPostModal{{ $post->id }}" tabindex="-1" aria-labelledby="editPostModalLabel{{ $post->id }}" aria-hidden="true">
        @php
            $isSharedModal = $post->isShared();
            $origPostModal = $isSharedModal ? $post->originalPost : null;
        @endphp
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
                        @if($isSharedModal)
                            <div class="alert alert-info py-2 px-3 mb-3 small">
                                <i class="fa fa-info-circle me-1"></i> This is a reshared publication from <strong>{{ $origPostModal?->member?->name ?? 'Original Author' }}</strong>. You can moderate the member's commentary text below. The original shared media relationship is preserved.
                            </div>
                        @endif

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

<!-- ========================================== -->
<!-- MODALS FOR STORY INSPECTION                -->
<!-- ========================================== -->
@foreach($stories as $story)
    <div class="modal fade" id="viewStoryModal{{ $story->id }}" tabindex="-1" aria-labelledby="viewStoryModalLabel{{ $story->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-height: calc(100dvh - 2rem);">
            <div class="modal-content overflow-hidden" style="max-height: calc(100dvh - 2rem); display: flex; flex-direction: column;">
                <div class="modal-header flex-shrink-0">
                    <h5 class="modal-title fw-bold" id="viewStoryModalLabel{{ $story->id }}">
                        <i data-feather="clock" class="me-2 text-info" style="width: 18px; height: 18px;"></i>
                        Story #{{ $story->id }} Inspection
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body flex-grow-1" style="overflow-y: auto; max-height: calc(100dvh - 180px); min-height: 0;">
                    @if($story->media_path)
                        <div class="mb-3 text-center bg-dark p-2 rounded">
                            @if($story->media_type === 'video')
                                <video src="{{ asset($story->media_path) }}" controls class="img-fluid rounded" style="max-height: 300px;"></video>
                            @else
                                <img src="{{ asset($story->media_path) }}" alt="Story Media" class="img-fluid rounded" style="max-height: 300px; object-fit: contain;">
                            @endif
                        </div>
                    @endif

                    @if($story->caption)
                        <div class="mb-3">
                            <h6 class="fw-bold text-muted small text-uppercase">Caption:</h6>
                            <div class="p-2 bg-light rounded text-dark">
                                {{ $story->caption }}
                            </div>
                        </div>
                    @endif

                    <div class="row g-2 text-center pt-2 border-top">
                        <div class="col">
                            <span class="text-muted small">Posted On</span>
                            <div class="fw-bold text-dark">{{ $story->created_at?->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="col border-start">
                            <span class="text-muted small">Expires At</span>
                            <div class="fw-bold text-muted">{{ $story->expires_at ? Carbon\Carbon::parse($story->expires_at)->format('M d, Y H:i') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- ========================================== -->
<!-- MODAL FOR CONNECTIONS LIST                 -->
<!-- ========================================== -->
<div class="modal fade" id="connectionsModal" tabindex="-1" aria-labelledby="connectionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-height: calc(100dvh - 2rem); max-width: 650px;">
        <div class="modal-content overflow-hidden" style="max-height: calc(100dvh - 2rem); display: flex; flex-direction: column;">
            <div class="modal-header flex-shrink-0">
                <h5 class="modal-title fw-bold" id="connectionsModalLabel">
                    <i data-feather="users" class="me-2 text-primary" style="width: 18px; height: 18px;"></i>
                    {{ $member->name }}'s Connections ({{ $connectionsCount }})
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body flex-grow-1 p-3" style="overflow-y: auto; max-height: calc(100dvh - 180px); min-height: 0;">
                @if($connections->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i data-feather="user-x" style="width: 48px; height: 48px; opacity: 0.4;" class="mb-3"></i>
                        <h6 class="fw-bold mb-1">No Connections Found</h6>
                        <p class="small text-muted mb-0">This member currently has no active accepted connections.</p>
                    </div>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($connections as $conn)
                            <div class="d-flex align-items-center justify-content-between p-3 rounded border bg-light">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $conn->avatar_url }}" alt="{{ $conn->name }}" class="rounded-circle border" style="width: 48px; height: 48px; object-fit: cover; background: #fff;">
                                    <div>
                                        <div class="fw-bold text-dark mb-0">{{ $conn->name }}</div>
                                        <div class="small text-muted">
                                            <code class="text-primary">{{ $conn->user_id }}</code>
                                            @if($conn->city || $conn->country)
                                                <span class="mx-1">•</span>
                                                <i class="fa fa-map-marker text-muted me-1"></i>{{ implode(', ', array_filter([$conn->city, $conn->country])) }}
                                            @endif
                                        </div>
                                        @if($conn->connected_at)
                                            <div class="small text-muted" style="font-size: 11px;">
                                                <i class="fa fa-calendar-check-o text-success me-1"></i> Connected on {{ Carbon\Carbon::parse($conn->connected_at)->format('M d, Y') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('admin.members.show', $conn) }}" class="btn btn-sm btn-outline-primary">
                                        <i data-feather="user" style="width: 14px; height: 14px;" class="me-1"></i> View Profile
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer flex-shrink-0 bg-white border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('admin-scripts')
<script>
    function toggleRemoveMedia(checkbox, postId) {
        const currentPreview = document.getElementById(`current-media-preview-${postId}`);
        const currentBlock = document.getElementById(`current-media-block-${postId}`);
        const removalAlert = document.getElementById(`removal-alert-${postId}`);
        const currentBadge = document.getElementById(`current-media-badge-${postId}`);

        if (checkbox.checked) {
            // Mark visually for removal
            if (currentPreview) {
                currentPreview.style.opacity = '0.25';
                currentPreview.style.filter = 'grayscale(100%)';
            }
            if (currentBlock) {
                currentBlock.style.borderColor = '#ef4444';
                currentBlock.style.backgroundColor = '#fef2f2';
            }
            if (removalAlert) {
                removalAlert.classList.remove('d-none');
            }
            if (currentBadge) {
                currentBadge.className = 'badge bg-danger text-white fw-bold';
                currentBadge.textContent = 'Marked for Removal';
            }

            // Clear any staged new upload when remove is intentionally toggled ON
            const fileInput = document.getElementById(`post-media-${postId}`);
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                cancelSelectedMedia(postId);
            }
        } else {
            // Restore normal state
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
        // Reset Remove Attachment toggle
        const removeCheckbox = document.getElementById(`remove-media-${postId}`);
        if (removeCheckbox && removeCheckbox.checked) {
            removeCheckbox.checked = false;
            toggleRemoveMedia(removeCheckbox, postId);
        }
        // Reset Selected Media
        cancelSelectedMedia(postId);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Tab switching logic
        const tabButtons = document.querySelectorAll('#memberProfileTabs [data-bs-toggle="tab"]');
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
            const initialButton = document.querySelector(`#memberProfileTabs [data-bs-target="${initialHash}"]`);
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
