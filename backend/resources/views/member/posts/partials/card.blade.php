@php
    $isEventSponsored = (($post->content_type ?? null) === 'PAID_EVENT') || (!empty($post->is_sponsored) && (!empty($post->event) || !empty($post->event_id)));
    $eventData = is_array($post->event ?? null) ? $post->event : (is_object($post->event ?? null) ? (array) $post->event : []);
    $eventCampaign = is_array($post->ad_campaign ?? null) ? $post->ad_campaign : [];
@endphp

@if ($isEventSponsored && !empty($eventData))
    @php
        $eventId = $eventData['id'] ?? ($post->event_id ?? null);
        $eventTitle = $eventData['title'] ?? 'Sponsored Event';
        $eventCover = $eventData['cover_photo_url'] ?? ($post->media_url ?? null);
        $eventDate = $eventData['start_date'] ?? null;
        $eventTime = $eventData['start_time'] ?? null;
        $eventLocation = $eventData['location_city'] ?? $eventData['location_venue'] ?? (($eventData['event_type'] ?? '') === 'online' ? 'Online Event' : 'In-Person');
        $earnUpTo = $eventCampaign['earn_up_to_formatted'] ?? '$0.05';
        $isRewarded = !empty($eventCampaign['already_rewarded']);
        $organizer = $eventData['organizer'] ?? null;
        $organizerName = is_array($organizer) ? ($organizer['name'] ?? 'Organizer') : 'Organizer';
        $isOwner = auth('member')->id() && (int) auth('member')->id() === (int) ($post->member_id ?? ($organizer['id'] ?? 0));
    @endphp
    <article class="card feed-post feed-post--sponsored" data-post-id="{{ $post->id }}">
        <header class="post-header">
            <span class="avatar post-avatar-initials" role="img">{{ substr($organizerName, 0, 1) }}</span>
            <div class="post-header__meta">
                <div>
                    <strong>{{ $organizerName }}</strong>
                    @if (!empty($post->is_sponsored))
                    <span class="sponsored-tag" style="background-color: rgba(79, 125, 243, 0.12); color: #4f7df3; border: 1px solid rgba(79, 125, 243, 0.25); font-size: 11px; padding: 2px 8px; border-radius: 12px; margin-left: 6px; font-weight: 700;">
                        Sponsored Event
                    </span>
                    @else
                    <span class="event-tag" style="background-color: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); font-size: 11px; padding: 2px 8px; border-radius: 12px; margin-left: 6px; font-weight: 700;">
                        Event
                    </span>
                    @endif
                </div>
                <span style="font-size: 12px; color: #64748b;">{{ !empty($post->is_sponsored) ? 'Promoted Event' : 'Community Event' }}</span>
            </div>
        </header>

        <div class="paid-event-card" style="padding: 12px 16px;">
            @if ($eventCover)
                <div style="margin-bottom: 12px; border-radius: 8px; overflow: hidden; max-height: 320px;">
                    <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}">
                        <img src="{{ $eventCover }}" alt="{{ $eventTitle }}" style="width: 100%; height: auto; object-fit: cover; display: block;" loading="lazy">
                    </a>
                </div>
            @endif

            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 8px;">
                <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}" style="color: inherit; text-decoration: none;">
                    {{ $eventTitle }}
                </a>
            </h3>

            <div style="font-size: 13px; color: #64748b; display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                @if ($eventDate)
                    <span>📅 {{ $eventDate }} {{ $eventTime ? 'at ' . $eventTime : '' }}</span>
                @endif
                <span>📍 {{ $eventLocation }}</span>
            </div>

            <div style="background-color: {{ $isRewarded ? 'rgba(16, 185, 129, 0.06)' : 'rgba(79, 125, 243, 0.06)' }}; border: 1px solid {{ $isRewarded ? '#a7f3d0' : 'rgba(79, 125, 243, 0.2)' }}; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div>
                    <div style="font-size: 13px; font-weight: 700; color: {{ $isRewarded ? '#047857' : '#1e293b' }};">
                        @if ($isRewarded)
                            ✅ Rewarded
                        @elseif (!empty($post->is_sponsored) && !empty($eventCampaign))
                            🎁 Earn up to {{ $earnUpTo }}
                        @else
                            📅 Event
                        @endif
                    </div>
                    <div style="font-size: 11.5px; color: #64748b;">
                        @if ($isRewarded)
                            Reward already earned
                        @elseif (!empty($post->is_sponsored) && !empty($eventCampaign))
                            Earn rewards based on direct verified referrals
                        @else
                            Join or view event details
                        @endif
                    </div>
                </div>

                @if ($isRewarded)
                    <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        View Event
                    </a>
                @elseif ($isOwner)
                    <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        Manage Event
                    </a>
                @elseif (!empty($post->is_sponsored) && !empty($eventCampaign))
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}" style="background-color: #ffffff; color: #059669; border: 1.5px solid #059669; padding: 7px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            Interested
                        </a>
                        <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}" style="background-color: #059669; color: #ffffff; border: 1px solid #059669; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            Earn Up To {{ $earnUpTo }}
                        </a>
                    </div>
                @else
                    <a href="{{ $eventId ? url('/member/events/' . $eventId) : '#' }}" style="background-color: #4f7df3; color: #ffffff; border: 1px solid #4f7df3; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        View Event
                    </a>
                @endif
            </div>
        </div>
    </article>
@else
@php
    $bizPage = $post->businessPage ?? null;
    $author = $post->member;
    $hasAuthorPhoto = $author->profile_photo
        && str_starts_with($author->profile_photo, 'uploads/profile/')
        && ! str_contains($author->profile_photo, '..')
        && $author->profile_photo === 'uploads/profile/'.basename($author->profile_photo)
        && file_exists(public_path($author->profile_photo));
    $authorPhotoUrl = $hasAuthorPhoto
        ? asset($author->profile_photo).'?v='.($author->updated_at?->timestamp ?? now()->timestamp)
        : null;
    $initials = collect(preg_split('/\s+/', trim($author->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
    $hasMedia = $post->hasImage() || $post->hasVideo();
    $currentMemberId = auth('member')->id();
    $userReactionRecord = $post->reactions()->where('member_id', $currentMemberId)->first();
    $userReaction = $userReactionRecord?->reaction;
    $userEmoji = $userReactionRecord?->emoji() ?? '👍';
    $userLabel = $userReactionRecord?->label() ?? 'Like';
    $userColor = $userReactionRecord?->color() ?? '#475569';
    $topReactions = $topReactions ?? ($post->topReactions() ?? collect());
    $reactionsCount = $post->reactions_count ?? $post->reactions()->count();
    $commentsCount = $post->comments_count ?? $post->comments()->count();
    $initialCommentsQuery = $post->comments()->with('member')->orderBy('created_at', 'desc')->take(6)->get();
    $hasMoreComments = $initialCommentsQuery->count() > 5;
    $initialComments = $initialCommentsQuery->take(5)->reverse()->values();

    $currentUser = auth('member')->user();
    $hasUserPhoto = $currentUser->profile_photo
        && str_starts_with($currentUser->profile_photo, 'uploads/profile/')
        && ! str_contains($currentUser->profile_photo, '..')
        && $currentUser->profile_photo === 'uploads/profile/'.basename($currentUser->profile_photo)
        && file_exists(public_path($currentUser->profile_photo));
    $userAvatarUrl = $hasUserPhoto
        ? asset($currentUser->profile_photo).'?v='.($currentUser->updated_at?->timestamp ?? now()->timestamp)
        : null;
    $userInitials = collect(preg_split('/\s+/', trim($currentUser->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
    $isSharedPost = $post->isShared();
    $originalPost = $isSharedPost ? $post->originalPost : null;
    $shareTargetPost = $isSharedPost && $originalPost ? $originalPost : $post;
    $sharesCount = $shareTargetPost->shares_count ?? $shareTargetPost->shares()->count();

    $origBiz = $originalPost?->businessPage;
    $origAuthor = $originalPost?->member;
    $origName = $origBiz ? $origBiz->page_name : ($origAuthor?->name ?? 'Member');
    $origAuthorUrl = $origBiz
        ? route('member.business-pages.show', $origBiz)
        : ($origAuthor ? route('member.people.show', $origAuthor) : null);

    $hasOrigAuthorPhoto = $origAuthor && $origAuthor->profile_photo
        && str_starts_with($origAuthor->profile_photo, 'uploads/profile/')
        && ! str_contains($origAuthor->profile_photo, '..')
        && $origAuthor->profile_photo === 'uploads/profile/'.basename($origAuthor->profile_photo)
        && file_exists(public_path($origAuthor->profile_photo));
    $origAuthorPhotoUrl = $hasOrigAuthorPhoto
        ? asset($origAuthor->profile_photo).'?v='.($origAuthor->updated_at?->timestamp ?? now()->timestamp)
        : null;
    $origInitials = $origBiz ? $origBiz->initials : ($origAuthor ? collect(preg_split('/\s+/', trim($origAuthor->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M' : 'M');

    $hasOrigMedia = $originalPost && ($originalPost->hasImage() || $originalPost->hasVideo());
    $isSaved = $post->isSavedBy($currentMemberId);
    $savesCount = $post->saved_posts_count ?? $post->savedPosts()->count();
    $isBizAdmin = $bizPage ? $bizPage->isTeamAdmin($currentMemberId) : false;
@endphp

<article class="card feed-post @if($isSharedPost) feed-post--shared @endif @if(!empty($post->is_suggested)) feed-post--suggested @endif" data-post-id="{{ $post->id }}">
    <header class="post-header">
        @if ($bizPage)
            @if ($bizPage->logo_url)
                <img class="avatar" src="{{ $bizPage->logo_url }}" alt="{{ $bizPage->page_name }}" loading="lazy">
            @else
                <span class="avatar post-avatar-initials" role="img" aria-label="{{ $bizPage->page_name }} initials">{{ $bizPage->initials }}</span>
            @endif
            <div class="post-header__meta">
                <div>
                    <a href="{{ route('member.business-pages.show', $bizPage) }}" style="color: inherit; text-decoration: none;">
                        <strong>{{ $bizPage->page_name }}</strong>
                    </a>
                    @if ($bizPage->is_verified)
                        <i data-lucide="badge-check" style="width: 15px; height: 15px; color: #20c875; vertical-align: middle;" title="Verified Business"></i>
                    @endif
                    @if ($post->is_pinned)
                        <span class="post-header__pinned-tag" data-biz-pinned-tag="true">· <i data-lucide="pin" aria-hidden="true"></i> Pinned Post</span>
                    @endif
                    @if ($post->is_featured)
                        <span class="biz-badge biz-badge--verified" data-biz-featured-tag="true" style="margin-left: 4px; font-size: 11px; padding: 2px 8px;">
                            <i data-lucide="sparkles" style="width: 10px; height: 10px;"></i> Featured
                        </span>
                    @endif
                    @if ($post->is_announcement)
                        <span class="biz-badge biz-badge--category" style="margin-left: 4px; font-size: 11px; padding: 2px 8px; background: rgba(247, 185, 64, 0.15); color: #b7791f;">
                            <i data-lucide="megaphone" style="width: 10px; height: 10px;"></i> Announcement
                        </span>
                    @endif
                </div>
                <a href="{{ route('member.posts.show', $post) }}">
                    <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                    <span aria-hidden="true">·</span>
                    <i data-lucide="building-2" aria-hidden="true" title="Business Page Timeline"></i>
                </a>
            </div>
        @else
            @if ($authorPhotoUrl)
                <img class="avatar" src="{{ $authorPhotoUrl }}" alt="{{ $author->name }}" loading="lazy" onerror="this.style.opacity='0.5'">
            @else
                <span class="avatar post-avatar-initials" role="img" aria-label="{{ $author->name }} initials">{{ $initials }}</span>
            @endif
            <div class="post-header__meta">
                <div>
                    <strong>{{ $author->name }}</strong>
                    @if ($post->is_pinned)
                        <span class="post-header__pinned-tag">· <i data-lucide="pin" aria-hidden="true"></i> Pinned Post</span>
                    @elseif ($isSharedPost)
                        <span class="post-header__shared-tag">
                            <i data-lucide="repeat" aria-hidden="true" style="width: 12px; height: 12px; vertical-align: -1px;"></i>
                            shared a post
                        </span>
                    @elseif (!empty($post->is_suggested))
                        <span class="post-header__suggested-tag">· Suggested for you</span>
                    @endif
                </div>
                <a href="{{ route('member.posts.show', $post) }}">
                    <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                    <span aria-hidden="true">·</span>
                    <i data-lucide="users-round" aria-hidden="true"></i>
                </a>
            </div>
        @endif

        <div class="post-header__options" data-post-options-wrapper>
            <button class="mini-button" type="button" aria-label="Post options" data-post-options-toggle="{{ $post->id }}">
                <i data-lucide="ellipsis" aria-hidden="true"></i>
            </button>
            <div class="post-options-menu" data-post-options-menu="{{ $post->id }}" hidden>
                @if ($bizPage && $isBizAdmin)
                    <button class="post-options-menu__item" type="button" onclick="toggleBizPinPost('{{ route('member.business-pages.posts.pin', [$bizPage, $post]) }}', {{ $post->id }}, this)">
                        <i data-lucide="pin" aria-hidden="true"></i>
                        <span data-biz-pin-label="{{ $post->id }}">{{ $post->is_pinned ? 'Unpin Post' : 'Pin to Top' }}</span>
                    </button>
                    <button class="post-options-menu__item" type="button" onclick="toggleBizFeaturePost('{{ route('member.business-pages.posts.feature', [$bizPage, $post]) }}', {{ $post->id }}, this)">
                        <i data-lucide="sparkles" aria-hidden="true"></i>
                        <span data-biz-feature-label="{{ $post->id }}">{{ $post->is_featured ? 'Remove Featured Tag' : 'Mark as Featured' }}</span>
                    </button>
                    <button class="post-options-menu__item post-options-menu__item--danger" type="button" onclick="deleteBizPost('{{ route('member.business-pages.posts.destroy', [$bizPage, $post]) }}', {{ $post->id }}, this)">
                        <i data-lucide="trash-2" aria-hidden="true"></i>
                        <span>Delete Post</span>
                    </button>
                @elseif ($post->member_id === $currentMemberId)
                    <button class="post-options-menu__item" type="button" data-post-pin-btn="{{ $post->id }}">
                        <i data-lucide="pin" aria-hidden="true"></i>
                        <span data-post-pin-menu-label="{{ $post->id }}">{{ $post->is_pinned ? 'Unpin from profile' : 'Pin to profile top' }}</span>
                    </button>
                @endif
                <button class="post-options-menu__item" type="button" data-post-save-btn="{{ $post->id }}">
                    <i data-lucide="bookmark" aria-hidden="true"></i>
                    <span data-post-save-menu-label="{{ $post->id }}">{{ $isSaved ? 'Unsave Post' : 'Save Post' }}</span>
                </button>
                {{-- Hide Post action removed from UI per requirement --}}
                <button class="post-options-menu__item post-options-menu__item--danger" type="button" data-post-report-open="{{ $post->id }}">
                    <i data-lucide="flag" aria-hidden="true"></i>
                    <span>Report Post</span>
                </button>
            </div>
        </div>
    </header>

    @if ($post->body)
        <p class="post-copy">{{ $post->body }}</p>
    @endif

    @if ($isSharedPost && $originalPost)
        <!-- Embedded Original Post Box -->
        <div class="shared-post-box">
            <div class="shared-post-box__header">
                @if ($origAuthorUrl)
                    <a href="{{ $origAuthorUrl }}" class="shared-post-box__avatar-link">
                @else
                    <div class="shared-post-box__avatar-link">
                @endif
                    @if ($origBiz && $origBiz->logo_url)
                        <img class="shared-post-box__avatar" src="{{ $origBiz->logo_url }}" alt="{{ $origBiz->page_name }}" loading="lazy">
                    @elseif ($origAuthorPhotoUrl)
                        <img class="shared-post-box__avatar" src="{{ $origAuthorPhotoUrl }}" alt="{{ $origName }}" loading="lazy" onerror="this.style.opacity='0.5'">
                    @else
                        <span class="shared-post-box__avatar shared-post-box__avatar--initials" role="img" aria-label="{{ $origName }} initials">{{ $origInitials }}</span>
                    @endif
                @if ($origAuthorUrl)
                    </a>
                @else
                    </div>
                @endif
                <div class="shared-post-box__meta">
                    <div class="shared-post-box__author-row">
                        @if ($origAuthorUrl)
                            <a href="{{ $origAuthorUrl }}" class="shared-post-box__author-name">
                                <strong>{{ $origName }}</strong>
                            </a>
                        @else
                            <strong class="shared-post-box__author-name">{{ $origName }}</strong>
                        @endif
                        @if ($origBiz && $origBiz->is_verified)
                            <i data-lucide="badge-check" style="width: 14px; height: 14px; color: #20c875; vertical-align: middle;" title="Verified Business"></i>
                        @endif
                    </div>
                    <a href="{{ route('member.posts.show', $originalPost) }}" class="shared-post-box__time-link" title="Open original post">
                        <time datetime="{{ $originalPost->created_at->toIso8601String() }}">{{ $originalPost->created_at->diffForHumans() }}</time>
                        <span aria-hidden="true">·</span>
                        <span>Original post</span>
                    </a>
                </div>
                <a href="{{ route('member.posts.show', $originalPost) }}" class="shared-post-box__view-original" title="View original post">
                    <i data-lucide="external-link" aria-hidden="true"></i>
                    <span>Original post</span>
                </a>
            </div>

            @if ($originalPost->body)
                <p class="shared-post-box__text">{{ $originalPost->body }}</p>
            @endif

            @if ($originalPost->hasImage())
                <a href="{{ route('member.posts.show', $originalPost) }}" class="shared-post-box__media-wrap">
                    <img class="post-image" src="{{ asset($originalPost->media_path) }}" alt="Image by {{ $origName }}" loading="lazy" onerror="this.style.opacity='0.5'">
                </a>
            @elseif ($originalPost->hasVideo())
                <video class="post-video" controls muted playsinline preload="metadata" aria-label="Video by {{ $origName }}" data-autoplay-video>
                    <source src="{{ asset($originalPost->media_path) }}">
                    Your browser does not support HTML video.
                </video>
            @endif
        </div>
    @elseif ($isSharedPost && ! $originalPost)
        <div class="shared-post-box shared-post-box--unavailable">
            <div class="shared-post-box__unavailable-content">
                <i data-lucide="lock" aria-hidden="true"></i>
                <div>
                    <strong>This content isn't available right now</strong>
                    <p>When this happens, it's usually because the original author deleted the post or it's only shared with a private audience.</p>
                </div>
            </div>
        </div>
    @elseif ($post->hasImage())
        <img class="post-image" src="{{ asset($post->media_path) }}" alt="Image shared by {{ $author->name }}" loading="lazy" onerror="this.style.opacity='0.5'">
    @elseif ($post->hasVideo())
        <video class="post-video" controls muted playsinline preload="metadata" aria-label="Video shared by {{ $author->name }}" data-autoplay-video>
            <source src="{{ asset($post->media_path) }}">
            Your browser does not support HTML video.
        </video>
    @endif

    @if (!empty($post->is_sponsored) && !empty($post->ad_campaign))
        @php
            $adCampaignData = is_array($post->ad_campaign) ? $post->ad_campaign : [];
            $adEarnUpTo = $adCampaignData['earn_up_to_formatted'] ?? '$0.05';
            $isAdRewarded = !empty($adCampaignData['already_rewarded']);
            $bizPageName = $bizPage->page_name ?? ($adCampaignData['campaign_name'] ?? ($author->name ?? 'Promoted Business'));
        @endphp
        <div class="sponsored-cta-banner" style="margin: 10px 16px 4px 16px; padding: 12px 16px; background-color: {{ $isAdRewarded ? 'rgba(16, 185, 129, 0.05)' : 'rgba(5, 150, 105, 0.06)' }}; border-radius: 10px; border: {{ $isAdRewarded ? '1px solid rgba(16, 185, 129, 0.2)' : '1px solid rgba(5, 150, 105, 0.25)' }}; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="font-size: 13.5px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                    <span>{{ $bizPageName }}</span>
                    @if ($isAdRewarded)
                        <span style="font-size: 10.5px; font-weight: 700; color: #047857; background-color: #d1fae5; padding: 1px 6px; border-radius: 4px;">Rewarded</span>
                    @else
                        <span style="font-size: 10.5px; font-weight: 700; color: #047857; background-color: #d1fae5; padding: 1px 6px; border-radius: 4px;">Earn up to {{ $adEarnUpTo }}</span>
                    @endif
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                    {{ $isAdRewarded ? 'Promoted Business • Reward already earned' : 'Promoted Business • Earn rewards based on direct verified referrals' }}
                </div>
            </div>
            @if ($isAdRewarded)
                <span style="background-color: #ecfdf5; color: #047857; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 6px; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px;">
                    <i data-lucide="check" style="width: 13px; height: 13px; color: #059669;"></i>
                    <span>Rewarded</span>
                </span>
            @elseif ($bizPage)
                <a href="{{ route('member.business-pages.show', $bizPage) }}" style="background-color: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                    <span>Visit Page</span>
                    <i data-lucide="external-link" style="width: 12px; height: 12px;"></i>
                </a>
            @endif
        </div>
    @endif

    <footer class="post-footer">
        <div class="post-actions">
            <div class="post-like-wrapper" data-post-like-wrapper="{{ $post->id }}">
                <div class="post-reaction-picker" data-post-reaction-picker="{{ $post->id }}" hidden>
                    @foreach (\App\Models\PostReaction::EMOJI_MAP as $rType => $rEmoji)
                        <button
                            class="post-reaction-picker__item"
                            type="button"
                            title="{{ \App\Models\PostReaction::LABEL_MAP[$rType] }}"
                            aria-label="React {{ \App\Models\PostReaction::LABEL_MAP[$rType] }}"
                            data-post-react-btn="{{ $rType }}"
                            data-post-id="{{ $post->id }}"
                        >{{ $rEmoji }}</button>
                    @endforeach
                </div>

                <button
                    class="post-action-btn post-action-btn--like @if ($userReaction) is-active @endif"
                    type="button"
                    aria-label="React to Post"
                    style="@if($userReaction) color: {{ $userColor }}; border-color: {{ $userColor }}44; background: {{ $userColor }}12; @endif"
                    data-post-like-btn="{{ $post->id }}"
                    data-post-id="{{ $post->id }}"
                >
                    <span class="post-action-btn__icon" data-post-reaction-icon="{{ $post->id }}">
                        @if ($userReaction)
                            {{ $userEmoji }}
                        @else
                            <i data-lucide="thumbs-up" aria-hidden="true"></i>
                        @endif
                    </span>
                    <span data-post-like-label="{{ $post->id }}">{{ $userReaction ? $userLabel : 'Like' }}</span>
                </button>
            </div>

            <button
                class="post-action-btn post-action-btn--likers"
                type="button"
                aria-label="View Reactions"
                data-post-reactors-open="{{ $post->id }}"
            >
                <span class="post-actions__reaction-badges" data-post-top-reactions="{{ $post->id }}">
                    @forelse ($topReactions as $topR)
                        <span class="post-actions__badge">{{ \App\Models\PostReaction::EMOJI_MAP[$topR->reaction] ?? '👍' }}</span>
                    @empty
                        <span class="post-actions__badge">👍</span>
                    @endforelse
                </span>
                <span data-post-likes-count="{{ $post->id }}">{{ $reactionsCount }}</span>
            </button>

            <button
                class="post-action-btn post-action-btn--comment-count"
                type="button"
                aria-label="View Comments"
                onclick="document.querySelector('[data-post-comment-input=\'{{ $post->id }}\']')?.focus()"
            >
                <i data-lucide="message-circle" aria-hidden="true"></i>
                <span>Comments (<span data-post-comment-count="{{ $post->id }}">{{ $commentsCount }}</span>)</span>
            </button>

            <button
                class="post-action-btn post-action-btn--save @if ($isSaved) is-active @endif"
                type="button"
                aria-label="Save Post"
                data-post-save-btn="{{ $post->id }}"
            >
                <i data-lucide="bookmark" aria-hidden="true"></i>
                <span>
                    <span data-post-save-label="{{ $post->id }}">{{ $isSaved ? 'Saved' : 'Save' }}</span>
                    (<span data-post-saves-count="{{ $post->id }}">{{ $savesCount }}</span>)
                </span>
            </button>

            <button
                class="post-action-btn post-action-btn--share"
                type="button"
                aria-label="Share Post"
                data-post-share-open="{{ $post->id }}"
            >
                <i data-lucide="share-2" aria-hidden="true"></i>
                <span data-post-share-label="{{ $post->id }}">Share</span>
            </button>

            <button
                class="post-action-btn post-action-btn--share-count"
                type="button"
                aria-label="View Shares"
                data-post-sharers-open="{{ $shareTargetPost->id }}"
            >
                <i data-lucide="repeat" aria-hidden="true"></i>
                <span><span data-post-share-count="{{ $shareTargetPost->id }}">{{ $sharesCount }}</span> Shares</span>
            </button>
        </div>
    </footer>

    <template data-post-share-template="{{ $post->id }}">
        @include('member.posts.partials.share-modal', ['post' => $post])
    </template>

    <!-- Post Comments Section -->
    <div class="post-comments" data-post-comment-section="{{ $post->id }}">
        @if ($hasMoreComments)
            <button
                class="post-comments__more"
                type="button"
                data-post-comment-more="{{ $post->id }}"
                data-offset="5"
            >View previous comments</button>
        @endif

        <div class="post-comments__list" data-post-comment-list="{{ $post->id }}">
            @forelse ($initialComments as $comment)
                @include('member.posts.partials.comment-item', ['post' => $post, 'comment' => $comment])
            @empty
                <div class="post-comments__empty" data-post-comments-empty="{{ $post->id }}">
                    <p>Be the first to comment.</p>
                </div>
            @endforelse
        </div>

        <form
            class="post-comment-form"
            method="POST"
            action="{{ route('member.posts.comments.store', $post) }}"
            data-post-comment-form="{{ $post->id }}"
        >
            @csrf
            <div class="post-comment-form__avatar-wrap">
                @if ($userAvatarUrl)
                    <img class="post-comment-form__avatar" src="{{ $userAvatarUrl }}" alt="{{ $currentUser->name }}">
                @else
                    <span class="post-comment-form__avatar post-comment-form__avatar--initials">{{ $userInitials }}</span>
                @endif
            </div>

            <div class="post-comment-form__input-wrap">
                <input
                    class="post-comment-form__input"
                    type="text"
                    name="comment"
                    placeholder="Write a comment..."
                    aria-label="Write a comment"
                    maxlength="1000"
                    required
                    autocomplete="off"
                    data-post-comment-input="{{ $post->id }}"
                >
                <button class="post-comment-form__submit" type="submit" aria-label="Send Comment">
                    <i data-lucide="send-horizontal" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>
</article>
@endif
