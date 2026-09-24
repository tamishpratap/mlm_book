@extends('member.layouts.app')

@section('title', $member->name.' - Profile')
@section('body-class', 'profile-premium-page')

@section('content')
    @php
        $cleanProfile = $member->profile_photo ? ltrim(str_replace('\\', '/', $member->profile_photo), '/') : null;
        $hasProfilePhoto = $cleanProfile
            && str_starts_with($cleanProfile, 'uploads/profile/')
            && ! str_contains($cleanProfile, '..')
            && file_exists(public_path($cleanProfile));

        $cleanCover = $member->cover_photo ? ltrim(str_replace('\\', '/', $member->cover_photo), '/') : null;
        $hasCoverPhoto = $cleanCover
            && str_starts_with($cleanCover, 'uploads/cover/')
            && ! str_contains($cleanCover, '..')
            && file_exists(public_path($cleanCover));

        $cacheVersion = $member->updated_at?->timestamp ?? now()->timestamp;
        $profilePhotoUrl = $hasProfilePhoto ? asset($cleanProfile).'?v='.$cacheVersion : null;
        $coverPhotoUrl = $hasCoverPhoto ? asset($cleanCover).'?v='.$cacheVersion : null;
        $initials = collect(preg_split('/\s+/', trim($member->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->implode('') ?: 'M';
        $location = collect([$member->city, $member->country])->filter()->implode(', ');
        $currentTab = $activeTab ?? 'timeline';
        $isOwner = auth('member')->id() === $member->id;
    @endphp

    <div class="profile-page">
        <!-- Profile Header & Hero -->
        <section class="profile-hero" aria-labelledby="profile-name">
            <div class="profile-cover">
                @if ($coverPhotoUrl)
                    <img class="profile-cover__image" id="cover-photo-preview" src="{{ $coverPhotoUrl }}" alt="Cover Photo" data-action-trigger="cover" style="cursor: pointer;">
                @else
                    <div class="profile-cover__preview" id="cover-photo-preview" data-action-trigger="cover" style="cursor: pointer;"></div>
                @endif
                <div class="profile-cover__overlay" aria-hidden="true"></div>

                @if ($isOwner)
                    <button type="button" class="media-upload-button" data-action-trigger="cover">
                        <i data-lucide="camera" aria-hidden="true"></i>
                        <span>Change Cover</span>
                    </button>
                    <form class="media-upload-form" method="POST" action="{{ route('member.profile.cover.update') }}" enctype="multipart/form-data" data-profile-cover-form data-upload-type="cover" data-delete-url="{{ route('member.profile.cover.remove') }}" style="display: none;">
                        @csrf
                        <input
                            id="cover_photo"
                            name="cover_photo"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            data-image-input
                            data-upload-type="cover"
                            data-preview-target="modalPhotoPreviewImg"
                        >
                    </form>
                @endif

                <!-- Cover Photo Action Menu -->
                <div class="photo-action-menu photo-action-menu--cover" data-photo-menu="cover" hidden>
                    <button type="button" class="photo-action-item" data-photo-action="view" data-photo-type="cover">
                        <i data-lucide="eye" aria-hidden="true"></i>
                        <span>View Cover Photo</span>
                    </button>
                    @if ($isOwner)
                        <button type="button" class="photo-action-item" data-photo-action="edit" data-photo-type="cover">
                            <i data-lucide="pencil" aria-hidden="true"></i>
                            <span>Edit Cover Photo</span>
                        </button>
                        <button type="button" class="photo-action-item photo-action-item--danger" data-photo-action="delete" data-photo-type="cover" id="coverDeleteMenuItem" @if(! $hasCoverPhoto) style="display: none;" @endif>
                            <i data-lucide="trash-2" aria-hidden="true"></i>
                            <span>Delete Cover Photo</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="profile-identity">
                <div class="profile-avatar-container">
                    <div class="profile-avatar-clickable" data-action-trigger="avatar" title="Profile photo options">
                        @if ($profilePhotoUrl)
                            <img class="profile-avatar" id="profile-photo-preview" src="{{ $profilePhotoUrl }}" alt="{{ $member->name }}" data-default-avatar="{{ asset('member_assets/images/dashboard/image/profile.png') }}">
                        @else
                            <div class="profile-avatar profile-avatar--initials" id="profile-photo-preview" role="img" aria-label="{{ $member->name }}" data-default-avatar="{{ asset('member_assets/images/dashboard/image/profile.png') }}">
                                <span>{{ $initials }}</span>
                            </div>
                        @endif
                        @if ($isOwner)
                            <div class="profile-avatar__camera" aria-label="Change profile photo">
                                <i data-lucide="camera" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Profile Avatar Action Menu -->
                    <div class="photo-action-menu photo-action-menu--avatar" data-photo-menu="avatar" hidden>
                        <button type="button" class="photo-action-item" data-photo-action="view" data-photo-type="avatar">
                            <i data-lucide="eye" aria-hidden="true"></i>
                            <span>View Profile Photo</span>
                        </button>
                        @if ($isOwner)
                            <button type="button" class="photo-action-item" data-photo-action="edit" data-photo-type="avatar">
                                <i data-lucide="pencil" aria-hidden="true"></i>
                                <span>Edit Profile Photo</span>
                            </button>
                            <button type="button" class="photo-action-item photo-action-item--danger" data-photo-action="delete" data-photo-type="avatar" id="avatarDeleteMenuItem" @if(! $hasProfilePhoto) style="display: none;" @endif>
                                <i data-lucide="trash-2" aria-hidden="true"></i>
                                <span>Delete Profile Photo</span>
                            </button>
                        @endif
                    </div>

                    @if ($isOwner)
                        <form class="profile-avatar-form" method="POST" action="{{ route('member.profile.photo.update') }}" enctype="multipart/form-data" data-profile-photo-form data-upload-type="avatar" data-delete-url="{{ route('member.profile.photo.remove') }}" style="display: none;">
                            @csrf
                            <input
                                id="profile_photo"
                                name="profile_photo"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                data-image-input
                                data-upload-type="avatar"
                                data-preview-target="modalPhotoPreviewImg"
                            >
                        </form>
                    @endif
                </div>

                <div class="profile-identity__copy">
                    <h1 id="profile-name">{{ $member->name }}</h1>
                    @if (filled($member->user_id))
                        <p class="profile-identity__username"><span>{{ '@'.$member->user_id }}</span></p>
                    @endif
                    <p class="profile-identity__email">
                        <i data-lucide="mail" aria-hidden="true"></i>
                        <span>{{ $member->email }}</span>
                    </p>
                    <p class="profile-identity__bio">{{ $member->bio ?: 'Add a short bio to tell the community about yourself.' }}</p>
                    <div class="profile-identity__meta">
                        @if ($location)
                            <span><i data-lucide="map-pin" aria-hidden="true"></i>{{ $location }}</span>
                        @endif
                        <span><i data-lucide="calendar-days" aria-hidden="true"></i>Joined {{ $member->created_at->format('F Y') }}</span>
                    </div>
                </div>

                <div class="profile-actions">
                    @if ($isOwner)
                        <a class="member-button member-button--primary" href="{{ route('member.profile.edit') }}">
                            <i data-lucide="pencil" aria-hidden="true"></i>
                            <span>Edit Profile</span>
                        </a>
                        <a class="member-button member-button--secondary" href="{{ route('member.account.settings') }}">
                            <i data-lucide="settings" aria-hidden="true"></i>
                            <span>Account Settings</span>
                        </a>
                    @else
                        @include('member.friends.partials.actions', [
                            'targetMember' => $member,
                            'friendship' => $friendship ?? null,
                            'friendshipState' => $friendshipState ?? 'none'
                        ])
                        <form method="POST" action="{{ route('member.people.block', $member) }}" data-block-form="{{ $member->id }}">
                            @csrf
                            <button class="member-button member-button--danger" type="submit" title="Block Member">
                                <i data-lucide="shield-alert" aria-hidden="true"></i>
                                <span>Block</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Complete Profile Statistics Bar -->
            <div class="profile-metrics-bar">
                <div class="profile-stat-box">
                    <strong>{{ $postsCount }}</strong><small>Posts</small>
                </div>
                <div class="profile-stat-box">
                    <strong>{{ $storiesCount }}</strong><small>Stories</small>
                </div>
                <div class="profile-stat-box">
                    <a href="{{ route('member.friends.index') }}" style="text-decoration: none; color: inherit;">
                        <strong>{{ $friendsCount }}</strong><small>Connections</small>
                    </a>
                </div>
                <div class="profile-stat-box">
                    <strong>{{ $photosCount }}</strong><small>Photos</small>
                </div>
                <div class="profile-stat-box">
                    <strong>{{ $videosCount }}</strong><small>Videos</small>
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
                        'friends' => ['label' => 'Connections', 'icon' => 'users-round'],
                        'stories' => ['label' => 'Stories', 'icon' => 'circle-play'],
                        'saved' => ['label' => 'Saved Posts', 'icon' => 'bookmark'],
                        'activity' => ['label' => 'Activity', 'icon' => 'activity'],
                    ];
                @endphp
                @foreach ($tabs as $key => $meta)
                    <a href="{{ route('member.profile.show', ['tab' => $key]) }}"
                       class="profile-nav-tab {{ $currentTab === $key ? 'is-active' : '' }}"
                       data-profile-tab="{{ $key }}">
                        <i data-lucide="{{ $meta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $meta['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </section>

        <!-- Dynamic Content Container -->
        <main class="profile-tab-content" data-profile-tab-content>
            @if ($currentTab === 'about')
                @include('member.profile.partials.about', compact('member'))
            @elseif ($currentTab === 'photos')
                @include('member.profile.partials.photos', compact('member', 'photos'))
            @elseif ($currentTab === 'videos')
                @include('member.profile.partials.videos', compact('member', 'videos'))
            @elseif ($currentTab === 'friends')
                @include('member.profile.partials.friends', compact('member', 'friendsList', 'friendsCount'))
            @elseif ($currentTab === 'stories')
                @include('member.profile.partials.stories', compact('member', 'stories'))
            @elseif ($currentTab === 'saved')
                @include('member.profile.partials.saved', compact('member', 'savedPosts'))
            @elseif ($currentTab === 'activity')
                @include('member.profile.partials.activity', compact('member', 'posts'))
            @else
                @include('member.profile.partials.timeline', compact('member', 'posts'))
            @endif
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div class="profile-edit-modal" data-profile-edit-modal hidden>
        <div class="profile-edit-modal__backdrop" data-profile-edit-close></div>
        <div class="profile-edit-modal__content card">
            <header class="profile-edit-modal__header">
                <h2>Edit Profile</h2>
                <button type="button" aria-label="Close" data-profile-edit-close><i data-lucide="x"></i></button>
            </header>

            <form method="POST" action="{{ route('member.profile.update') }}" data-profile-edit-form>
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="edit_name">Full Name</label>
                    <input type="text" id="edit_name" name="name" value="{{ $member->name }}" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="edit_bio">Bio</label>
                    <textarea id="edit_bio" name="bio" rows="3" maxlength="500" placeholder="Share a little about yourself...">{{ $member->bio }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_city">City</label>
                        <input type="text" id="edit_city" name="city" value="{{ $member->city }}" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="edit_country">Country</label>
                        <input type="text" id="edit_country" name="country" value="{{ $member->country }}" maxlength="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="text" id="edit_phone" name="phone" value="{{ $member->phone }}" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="edit_website">Website</label>
                        <input type="url" id="edit_website" name="website" value="{{ $member->website }}" maxlength="255">
                    </div>
                </div>

                <div class="profile-edit-modal__actions">
                    <button class="member-button member-button--secondary" type="button" data-profile-edit-close>Cancel</button>
                    <button class="member-button member-button--primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Interactive Profile / Cover Photo Preview & Upload Modal -->
    <div class="profile-photo-modal" id="profilePhotoModal" hidden style="display: none;" data-photo-upload-modal>
        <div class="profile-photo-modal__backdrop" onclick="closePhotoPreviewModal()"></div>
        <div class="profile-photo-modal__content card" role="dialog" aria-labelledby="photoModalTitle" aria-modal="true">
            <header class="profile-photo-modal__header">
                <h3 id="photoModalTitle">
                    <i data-lucide="camera" style="color: #176bff;"></i>
                    <span id="photoModalTitleText">Preview Photo</span>
                </h3>
                <button type="button" class="icon-button" onclick="closePhotoPreviewModal()" aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </header>

            <div class="profile-photo-modal__body">
                <!-- Validation Alert Box inside Modal -->
                <div class="member-alert member-alert--error" id="photoModalError" hidden style="display: none; margin-bottom: 12px;">
                    <i data-lucide="circle-alert"></i>
                    <span id="photoModalErrorText">Invalid file selection.</span>
                </div>

                <!-- Preview Container -->
                <div class="photo-preview-wrapper">
                    <!-- Preview Viewport -->
                    <div class="photo-preview-viewport" id="photoPreviewViewport">
                        <div class="photo-preview-stage" id="photoPreviewStage">
                            <img id="modalPhotoPreviewImg" src="" alt="Photo Preview">
                        </div>
                    </div>

                    <!-- Zoom & Adjustment Controls -->
                    <div class="photo-preview-controls">
                        <div class="photo-preview-zoom">
                            <button type="button" class="mini-button" onclick="adjustPhotoZoom(-0.1)" title="Zoom Out">
                                <i data-lucide="zoom-out"></i>
                            </button>
                            <input type="range" id="photoZoomSlider" min="1" max="2.5" step="0.05" value="1" oninput="setPhotoZoom(this.value)">
                            <button type="button" class="mini-button" onclick="adjustPhotoZoom(0.1)" title="Zoom In">
                                <i data-lucide="zoom-in"></i>
                            </button>
                        </div>
                        <div class="photo-preview-hint">
                            <i data-lucide="move" style="width: 14px; height: 14px;"></i>
                            <span>Preview and adjust before saving</span>
                        </div>
                    </div>
                </div>

                <!-- Additional Actions -->
                <div class="photo-preview-extra-actions">
                    <button type="button" class="member-button member-button--secondary" onclick="triggerReplacePhotoInput()" style="min-height: 38px; padding: 0 14px; font-size: 12.5px;">
                        <i data-lucide="refresh-cw"></i> Replace Image
                    </button>
                </div>
            </div>

            <footer class="profile-photo-modal__footer">
                <button type="button" class="member-button member-button--secondary" onclick="closePhotoPreviewModal()">Cancel</button>
                <button type="button" class="member-button member-button--primary" id="savePhotoUploadBtn" onclick="submitPhotoUpload()">
                    <i data-lucide="upload"></i>
                    <span id="savePhotoUploadBtnText">Save Changes</span>
                </button>
            </footer>
        </div>
    </div>

    <!-- Professional Fullscreen Photo Viewer -->
    <div class="photo-fullscreen-viewer" id="photoFullscreenViewer" hidden style="display: none;" role="dialog" aria-modal="true" aria-label="Photo Viewer">
        <div class="photo-fullscreen-backdrop" onclick="closeFullscreenViewer()"></div>

        <div class="photo-fullscreen-toolbar">
            <div class="photo-fullscreen-title" id="fullscreenViewerTitle">Photo Viewer</div>
            <div class="photo-fullscreen-controls">
                <button type="button" class="fullscreen-btn" onclick="adjustFullscreenZoom(-0.25)" title="Zoom Out ( - )">
                    <i data-lucide="zoom-out"></i>
                </button>
                <span class="fullscreen-zoom-level" id="fullscreenZoomLevel">100%</span>
                <button type="button" class="fullscreen-btn" onclick="adjustFullscreenZoom(0.25)" title="Zoom In ( + )">
                    <i data-lucide="zoom-in"></i>
                </button>
                <button type="button" class="fullscreen-btn" onclick="resetFullscreenZoom()" title="Reset Zoom">
                    <i data-lucide="rotate-ccw"></i>
                </button>
                <button type="button" class="fullscreen-btn fullscreen-btn--close" onclick="closeFullscreenViewer()" title="Close (Esc)">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>

        <div class="photo-fullscreen-stage" id="fullscreenViewerStage" onclick="handleFullscreenStageClick(event)">
            <img id="fullscreenViewerImg" src="" alt="Photo View" style="transform: scale(1);">
        </div>
    </div>

    <!-- Delete Photo Confirmation Modal -->
    <div class="photo-delete-modal" id="photoDeleteConfirmModal" hidden style="display: none;" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="photo-delete-modal__backdrop" onclick="closeDeleteConfirmModal()"></div>
        <div class="photo-delete-modal__content card">
            <header class="photo-delete-modal__header">
                <h3 id="deleteModalTitle">
                    <i data-lucide="trash-2" style="color: #e5484d;"></i>
                    <span id="deleteModalTitleText">Delete Photo</span>
                </h3>
                <button type="button" class="icon-button" onclick="closeDeleteConfirmModal()" aria-label="Close modal">
                    <i data-lucide="x"></i>
                </button>
            </header>

            <div class="photo-delete-modal__body">
                <p id="deleteModalConfirmText">Are you sure you want to delete this photo?</p>
            </div>

            <footer class="photo-delete-modal__footer">
                <button type="button" class="member-button member-button--secondary" onclick="closeDeleteConfirmModal()">Cancel</button>
                <button type="button" class="member-button member-button--danger" id="confirmDeletePhotoBtn" onclick="executePhotoDelete()">
                    <i data-lucide="trash-2"></i>
                    <span id="confirmDeleteBtnText">Delete</span>
                </button>
            </footer>
        </div>
    </div>
@endsection
