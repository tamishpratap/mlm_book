@extends('member.layouts.app')

@section('title', $event->title.' - Event')

@push('styles')
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-events.css') }}">
@endpush

@section('content')
@php
    $cover = $event->cover_photo ? asset($event->cover_photo) : asset('member_assets/images/default-event.jpg');
    $organizer = $event->organizer;
    $hasOrgPhoto = $organizer && $organizer->profile_photo && file_exists(public_path($organizer->profile_photo));
    $orgPhotoUrl = $hasOrgPhoto ? asset($organizer->profile_photo) : asset('member_assets/images/default-avatar.png');
    $orgInitials = $organizer ? collect(preg_split('/\s+/', trim($organizer->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') : 'H';
    $isOrganizer = $event->isOrganizer(auth('member')->id());
@endphp

<div class="event-show-wrapper">
    <!-- Facebook Style Event Hero Banner -->
    <div class="event-hero-card card">
        <div class="event-hero-cover">
            <img src="{{ $cover }}" alt="{{ $event->title }}">
            <div class="event-hero-overlay"></div>

            <!-- Floating Type Badge (Online / In-Person) -->
            <div class="event-hero-badge badge-{{ $event->event_type }}">
                <i data-lucide="{{ $event->event_type === 'online' ? 'video' : 'map-pin' }}" aria-hidden="true"></i>
                <span>{{ ucfirst($event->event_type) }} Event</span>
            </div>

            <!-- Floating Date Badge Box -->
            <div class="event-hero-date-badge">
                <span class="event-hero-date-month">{{ $event->start_date->format('M') }}</span>
                <span class="event-hero-date-day">{{ $event->start_date->format('d') }}</span>
            </div>
        </div>

        <div class="event-hero-body">
            <div class="event-hero-info">
                <div class="event-hero-meta-top">
                    <span class="category-tag"><i data-lucide="tag" aria-hidden="true"></i> {{ $event->category }}</span>
                    <span class="privacy-pill"><i data-lucide="{{ $event->privacy === 'public' ? 'globe' : 'lock' }}" aria-hidden="true"></i> {{ ucfirst($event->privacy) }} Event</span>
                </div>
                <h1 class="event-hero-title">{{ $event->title }}</h1>
                <div class="event-hero-schedule">
                    <div class="event-schedule-item">
                        <i data-lucide="calendar" aria-hidden="true"></i>
                        <span>{{ $event->start_date->format('l, F j, Y') }}
                            @if ($event->start_time) at {{ date('g:i A', strtotime($event->start_time)) }} @endif
                            @if ($event->end_date && $event->end_date->ne($event->start_date)) - {{ $event->end_date->format('F j, Y') }} @endif
                        </span>
                    </div>
                    @if ($event->event_type === 'online')
                        <div class="event-schedule-item">
                            <i data-lucide="video" aria-hidden="true"></i>
                            <span>Online Event</span>
                        </div>
                    @elseif ($event->location_address)
                        <div class="event-schedule-item">
                            <i data-lucide="map-pin" aria-hidden="true"></i>
                            <span>{{ $event->location_address }} @if($event->location_city), {{ $event->location_city }} @endif</span>
                        </div>
                    @endif
                </div>

                <!-- Host Line -->
                <div class="event-host-strip">
                    @if ($hasOrgPhoto)
                        <img class="event-host-avatar" src="{{ $orgPhotoUrl }}" alt="{{ $organizer->name }}">
                    @else
                        <span class="event-host-avatar event-host-avatar--initials">{{ $orgInitials }}</span>
                    @endif
                    <div class="event-host-text">
                        <span>Hosted by</span>
                        <a href="{{ route('member.people.show', $organizer) }}"><strong>{{ $organizer->name }}</strong></a>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Bar -->
            <div class="event-hero-actions">
                <form method="POST" action="{{ route('member.events.respond', $event) }}">
                    @csrf
                    <input type="hidden" name="response" value="going">
                    <button class="member-button {{ $userResponse === 'going' ? 'member-button--primary' : 'member-button--secondary' }} event-action-btn" type="submit">
                        <i data-lucide="check-circle-2" aria-hidden="true"></i>
                        <span>{{ $userResponse === 'going' ? 'Going' : 'Going?' }}</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('member.events.respond', $event) }}">
                    @csrf
                    <input type="hidden" name="response" value="interested">
                    <button class="member-button {{ $userResponse === 'interested' ? 'member-button--primary' : 'member-button--secondary' }} event-action-btn" type="submit">
                        <i data-lucide="star" aria-hidden="true"></i>
                        <span>{{ $userResponse === 'interested' ? 'Interested' : 'Interested?' }}</span>
                    </button>
                </form>

                @if ($isOrganizer)
                    <a href="{{ route('member.events.edit', $event) }}" class="member-button member-button--secondary event-action-btn">
                        <i data-lucide="pencil" aria-hidden="true"></i>
                        <span>Edit Event</span>
                    </a>
                @endif

                <button class="member-button member-button--secondary event-action-btn" type="button" onclick="if(navigator.clipboard){navigator.clipboard.writeText(window.location.href); alert('Event link copied to clipboard!');}">
                    <i data-lucide="share-2" aria-hidden="true"></i>
                    <span>Share</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content & Sidebar Grid -->
    <div class="event-details-layout">
        <!-- Main Column (Left) -->
        <div class="event-main-column">
            <!-- Event Description Card -->
            <div class="card event-card-section">
                <div class="event-section-header">
                    <h2><i data-lucide="info" aria-hidden="true"></i> About this Event</h2>
                </div>
                <div class="event-description-body">
                    <p>{!! nl2br(e($event->description ?? $event->short_description ?? 'No detailed description provided for this event.')) !!}</p>
                </div>

                <!-- Online Meeting / Physical Venue Info Box -->
                @if ($event->event_type === 'online' && $event->meeting_link)
                    <div class="event-info-box event-info-box--online">
                        <div class="event-info-box__icon">
                            <i data-lucide="video" aria-hidden="true"></i>
                        </div>
                        <div class="event-info-box__content">
                            <h4>Online Meeting Link</h4>
                            <p>Join the video conference when the event starts.</p>
                            <a class="member-button member-button--primary member-button--sm" href="{{ $event->meeting_link }}" target="_blank" rel="noopener">
                                <i data-lucide="external-link" aria-hidden="true"></i> Join Meeting
                            </a>
                        </div>
                    </div>
                @elseif ($event->event_type === 'offline' && $event->location_address)
                    <div class="event-info-box event-info-box--offline">
                        <div class="event-info-box__icon">
                            <i data-lucide="map-pin" aria-hidden="true"></i>
                        </div>
                        <div class="event-info-box__content">
                            <h4>Venue Address</h4>
                            <p>{{ $event->location_address }} @if($event->location_city), {{ $event->location_city }} @endif</p>
                            @if ($event->google_maps_link)
                                <a class="member-button member-button--secondary member-button--sm" href="{{ $event->google_maps_link }}" target="_blank" rel="noopener">
                                    <i data-lucide="map" aria-hidden="true"></i> Open in Maps
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Discussion Section -->
            <div class="event-discussion-wrapper">
                <div class="event-section-header" style="margin-bottom: 16px;">
                    <h2><i data-lucide="message-square" aria-hidden="true"></i> Event Discussion</h2>
                </div>

                <!-- Post Update Composer -->
                <div class="card event-composer-card">
                    <form method="POST" action="{{ route('member.events.posts.store', $event) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="event-composer-input-row">
                            @php
                                $cUser = auth('member')->user();
                                $hasUserPhoto = $cUser->profile_photo && file_exists(public_path($cUser->profile_photo));
                                $uPhotoUrl = $hasUserPhoto ? asset($cUser->profile_photo) : asset('member_assets/images/default-avatar.png');
                                $uInitials = collect(preg_split('/\s+/', trim($cUser->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'M';
                            @endphp
                            @if ($hasUserPhoto)
                                <img class="avatar" src="{{ $uPhotoUrl }}" alt="{{ $cUser->name }}">
                            @else
                                <span class="avatar post-avatar-initials">{{ $uInitials }}</span>
                            @endif
                            <textarea name="body" rows="3" class="form-control event-composer-textarea" placeholder="Post an update, ask a question, or share something with attendees..."></textarea>
                        </div>
                        <div class="event-composer-footer">
                            <label class="event-media-attach-btn">
                                <i data-lucide="image" aria-hidden="true"></i>
                                <span>Attach Photo/Video</span>
                                <input type="file" name="media" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov" hidden>
                            </label>
                            <button type="submit" class="member-button member-button--primary">
                                <i data-lucide="send" aria-hidden="true"></i> Post Update
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Discussion Posts List -->
                <div class="event-posts-list">
                    @forelse ($posts as $post)
                        @include('member.posts.partials.card', compact('post'))
                    @empty
                        <div class="notification-empty card event-empty-discussion">
                            <div class="notification-empty__icon"><i data-lucide="message-square" aria-hidden="true"></i></div>
                            <h2>No Discussion Posts Yet</h2>
                            <p>Be the first attendee to post an update or question about this event!</p>
                        </div>
                    @endforelse

                    @if ($posts->hasPages())
                        <div class="event-pagination-wrap">
                            {{ $posts->links('member.search.pagination') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Column (Right) -->
        <div class="event-sidebar-column">
            <!-- Redesigned Organizer Card -->
            <div class="card event-organizer-card">
                <div class="event-organizer-card__cover"></div>
                <div class="event-organizer-card__content">
                    <a class="event-organizer-card__avatar-wrap" href="{{ route('member.people.show', $organizer) }}">
                        @if ($hasOrgPhoto)
                            <img class="event-organizer-card__avatar" src="{{ $orgPhotoUrl }}" alt="{{ $organizer->name }}">
                        @else
                            <span class="event-organizer-card__avatar event-organizer-card__avatar--initials">{{ $orgInitials }}</span>
                        @endif
                    </a>
                    <div class="event-organizer-card__details">
                        <div class="event-organizer-card__name-row">
                            <a href="{{ route('member.people.show', $organizer) }}">
                                <strong>{{ $organizer->name }}</strong>
                            </a>
                            <span class="organizer-host-badge"><i data-lucide="shield-check" aria-hidden="true"></i> Host</span>
                        </div>
                        @if ($organizer->user_id)
                            <small class="event-organizer-card__handle">&#64;{{ $organizer->user_id }}</small>
                        @endif
                        @if ($organizer->bio)
                            <p class="event-organizer-card__bio">{{ Str::limit($organizer->bio, 80) }}</p>
                        @endif
                    </div>
                    <div class="event-organizer-card__stats">
                        <div class="event-organizer-stat">
                            <strong>{{ $goingCount }}</strong>
                            <span>Going</span>
                        </div>
                        <div class="event-organizer-stat">
                            <strong>{{ $interestedCount }}</strong>
                            <span>Interested</span>
                        </div>
                    </div>
                    <a class="member-button member-button--secondary member-button--sm w-100" href="{{ route('member.people.show', $organizer) }}" style="text-decoration: none; justify-content: center;">
                        <i data-lucide="user" aria-hidden="true"></i> View Host Profile
                    </a>
                </div>
            </div>

            <!-- Event Overview / Stats Card -->
            <div class="card event-sidebar-card">
                <div class="event-sidebar-card__header">
                    <h3><i data-lucide="bar-chart-3" aria-hidden="true"></i> Event Status</h3>
                </div>
                <div class="event-meta-list">
                    <div class="event-meta-item">
                        <div class="event-meta-item__icon"><i data-lucide="users" aria-hidden="true"></i></div>
                        <div class="event-meta-item__copy">
                            <span>Going</span>
                            <strong>{{ $goingCount }} {{ Str::plural('Member', $goingCount) }}</strong>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <div class="event-meta-item__icon"><i data-lucide="star" aria-hidden="true"></i></div>
                        <div class="event-meta-item__copy">
                            <span>Interested</span>
                            <strong>{{ $interestedCount }} {{ Str::plural('Member', $interestedCount) }}</strong>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <div class="event-meta-item__icon"><i data-lucide="{{ $event->privacy === 'public' ? 'globe' : 'lock' }}" aria-hidden="true"></i></div>
                        <div class="event-meta-item__copy">
                            <span>Privacy</span>
                            <strong>{{ ucfirst($event->privacy) }} Event</strong>
                        </div>
                    </div>
                    <div class="event-meta-item">
                        <div class="event-meta-item__icon"><i data-lucide="tag" aria-hidden="true"></i></div>
                        <div class="event-meta-item__copy">
                            <span>Category</span>
                            <strong>{{ $event->category }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
