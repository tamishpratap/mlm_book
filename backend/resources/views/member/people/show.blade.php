@extends('member.layouts.app')

@section('title', $member->name . ' - Profile')
@section('body-class', 'profile-premium-page')

@section('content')
    @php
        $hasProfilePhoto =
            $member->profile_photo &&
            str_starts_with($member->profile_photo, 'uploads/profile/') &&
            !str_contains($member->profile_photo, '..') &&
            file_exists(public_path($member->profile_photo));
        $hasCoverPhoto =
            $member->cover_photo &&
            str_starts_with($member->cover_photo, 'uploads/cover/') &&
            !str_contains($member->cover_photo, '..') &&
            file_exists(public_path($member->cover_photo));
        $cacheVersion = $member->updated_at?->timestamp ?? now()->timestamp;
        $profilePhotoUrl = $hasProfilePhoto ? asset($member->profile_photo) . '?v=' . $cacheVersion : null;
        $coverPhotoUrl = $hasCoverPhoto ? asset($member->cover_photo) . '?v=' . $cacheVersion : null;
        $initials =
            collect(preg_split('/\s+/', trim($member->name)))
                ->filter()
                ->take(2)
                ->map(fn($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                ->implode('') ?:
            'M';
        $location = collect([$member->city, $member->country])
            ->filter()
            ->implode(', ');
        $currentTab = $activeTab ?? 'timeline';
        $canViewTimeline = ($friendshipState ?? 'none') === 'friends';
    @endphp

    <div class="profile-page public-member-profile">
        <!-- Profile Header & Hero -->
        <section class="profile-hero" aria-labelledby="profile-name">
            <div class="profile-cover">
                @if ($coverPhotoUrl)
                    <img class="profile-cover__image" id="cover-photo-preview" src="{{ $coverPhotoUrl }}" alt="Cover Photo"
                        data-action-trigger="cover" style="cursor: pointer;">
                @else
                    <div class="profile-cover__preview" id="cover-photo-preview" data-action-trigger="cover"
                        style="cursor: pointer;"></div>
                @endif
                <div class="profile-cover__overlay" aria-hidden="true"></div>

                <!-- Visitor Cover Photo Action Menu -->
                <div class="photo-action-menu photo-action-menu--cover" data-photo-menu="cover" hidden>
                    <button type="button" class="photo-action-item" data-photo-action="view" data-photo-type="cover">
                        <i data-lucide="eye" aria-hidden="true"></i>
                        <span>View Cover Photo</span>
                    </button>
                </div>
            </div>

            <div class="profile-identity">
                <div class="profile-avatar-container">
                    <div class="profile-avatar-clickable" data-action-trigger="avatar" title="View profile photo">
                        @if ($profilePhotoUrl)
                            <img class="profile-avatar" id="profile-photo-preview" src="{{ $profilePhotoUrl }}"
                                alt="{{ $member->name }}">
                        @else
                            <div class="profile-avatar profile-avatar--initials" id="profile-photo-preview" role="img"
                                aria-label="{{ $member->name }}">
                                <span>{{ $initials }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Visitor Avatar Action Menu -->
                    <div class="photo-action-menu photo-action-menu--avatar" data-photo-menu="avatar" hidden>
                        <button type="button" class="photo-action-item" data-photo-action="view" data-photo-type="avatar">
                            <i data-lucide="eye" aria-hidden="true"></i>
                            <span>View Profile Photo</span>
                        </button>
                    </div>
                </div>

                <div class="profile-identity__copy">
                    <h1 id="profile-name">{{ $member->name }}</h1>
                    @if (filled($member->user_id))
                        <p class="profile-identity__username"><span>{{ '@' . $member->user_id }}</span></p>
                    @endif
                    <p class="profile-identity__bio">{{ $member->bio ?: 'MLM Book Member' }}</p>
                    <div class="profile-identity__meta">
                        @if ($location)
                            <span><i data-lucide="map-pin" aria-hidden="true"></i>{{ $location }}</span>
                        @endif
                        <span><i data-lucide="calendar-days" aria-hidden="true"></i>Joined
                            {{ $member->created_at->format('F Y') }}</span>
                    </div>
                </div>

                <div class="profile-actions">
                    @include(
                        'member.friends.partials.actions',
                        compact('member', 'friendship', 'friendshipState') + ['targetMember' => $member]
                    )

                    <form method="POST" action="{{ route('member.people.block', $member) }}"
                        data-block-form="{{ $member->id }}">
                        @csrf
                        <button class="member-button member-button--danger" type="submit" title="Block Member">
                            <i data-lucide="shield-alert" aria-hidden="true"></i>
                            <span>Block</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Complete Profile Statistics Bar -->
            <div class="profile-metrics-bar">
                <div class="profile-stat-box">
                    @if ($canViewTimeline)
                        <strong>{{ $postsCount ?? 0 }}</strong><small>Posts</small>
                    @else
                        <strong aria-label="Private">&mdash;</strong><small>Private Posts</small>
                    @endif
                </div>
                <div class="profile-stat-box">
                    <strong>{{ $storiesCount ?? 0 }}</strong><small>Stories</small>
                </div>
                <div class="profile-stat-box">
                    <a href="{{ route('member.people.friends', $member) }}" style="text-decoration: none; color: inherit;">
                        <strong>{{ $friendsCount ?? 0 }}</strong><small>Friends</small>
                    </a>
                </div>
                <div class="profile-stat-box">
                    <strong>{{ $photosCount ?? 0 }}</strong><small>Photos</small>
                </div>
                <div class="profile-stat-box">
                    <strong>{{ $videosCount ?? 0 }}</strong><small>Videos</small>
                </div>
            </div>

            <!-- Profile Navigation Tabs Bar -->
            <nav class="profile-nav-tabs" aria-label="Profile Sections" data-profile-tabs>
                @php
                    $tabs = [
                        'timeline' => ['label' => 'Timeline', 'icon' => 'newspaper'],
                        'about' => ['label' => 'About', 'icon' => 'user'],
                        'photos' => ['label' => 'Photos', 'icon' => 'image'],
                        'videos' => ['label' => 'Videos', 'icon' => 'video'],
                        'friends' => ['label' => 'Friends', 'icon' => 'users-round'],
                        'stories' => ['label' => 'Stories', 'icon' => 'circle-play'],
                        'activity' => ['label' => 'Activity', 'icon' => 'activity'],
                    ];
                @endphp
                @foreach ($tabs as $key => $meta)
                    <a href="{{ route('member.people.show', [$member, 'tab' => $key]) }}"
                        class="profile-nav-tab {{ $currentTab === $key ? 'is-active' : '' }}"
                        data-profile-tab="{{ $key }}">
                        <i data-lucide="{{ $meta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $meta['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </section>

        <!-- Dynamic AJAX Tab Content Container -->
        <main class="profile-tab-content" data-profile-tab-content>
            @if ($currentTab === 'about')
                @include('member.profile.partials.about', compact('member'))
            @elseif ($currentTab === 'photos')
                @include('member.profile.partials.photos', compact('member', 'photos'))
            @elseif ($currentTab === 'videos')
                @include('member.profile.partials.videos', compact('member', 'videos'))
            @elseif ($currentTab === 'friends')
                @include(
                    'member.profile.partials.friends',
                    compact('member', 'friendsList', 'friendsCount'))
            @elseif ($currentTab === 'stories')
                @include('member.profile.partials.stories', compact('member', 'stories'))
            @elseif ($currentTab === 'activity')
                @include('member.profile.partials.activity', compact('member', 'posts'))
            @else
                @include('member.profile.partials.timeline', compact('member', 'posts'))
            @endif
        </main>
    </div>

    <!-- Professional Fullscreen Photo Viewer -->
    <div class="photo-fullscreen-viewer" id="photoFullscreenViewer" hidden style="display: none;" role="dialog"
        aria-modal="true" aria-label="Photo Viewer">
        <div class="photo-fullscreen-backdrop" onclick="closeFullscreenViewer()"></div>

        <div class="photo-fullscreen-toolbar">
            <div class="photo-fullscreen-title" id="fullscreenViewerTitle">Photo Viewer</div>
            <div class="photo-fullscreen-controls">
                <button type="button" class="fullscreen-btn" onclick="adjustFullscreenZoom(-0.25)"
                    title="Zoom Out ( - )">
                    <i data-lucide="zoom-out"></i>
                </button>
                <span class="fullscreen-zoom-level" id="fullscreenZoomLevel">100%</span>
                <button type="button" class="fullscreen-btn" onclick="adjustFullscreenZoom(0.25)"
                    title="Zoom In ( + )">
                    <i data-lucide="zoom-in"></i>
                </button>
                <button type="button" class="fullscreen-btn" onclick="resetFullscreenZoom()" title="Reset Zoom">
                    <i data-lucide="rotate-ccw"></i>
                </button>
                <button type="button" class="fullscreen-btn fullscreen-btn--close" onclick="closeFullscreenViewer()"
                    title="Close (Esc)">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>

        <div class="photo-fullscreen-stage" id="fullscreenViewerStage" onclick="handleFullscreenStageClick(event)">
            <img id="fullscreenViewerImg" src="" alt="Photo View" style="transform: scale(1);">
        </div>
    </div>
@endsection
