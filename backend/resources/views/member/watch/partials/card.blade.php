@php
    $bizPage = $post->businessPage ?? null;
    $author = $post->member;
    $hasAuthorPhoto = $author && $author->profile_photo
        && str_starts_with($author->profile_photo, 'uploads/profile/')
        && ! str_contains($author->profile_photo, '..')
        && $author->profile_photo === 'uploads/profile/'.basename($author->profile_photo)
        && file_exists(public_path($author->profile_photo));
    $authorPhotoUrl = $hasAuthorPhoto
        ? asset($author->profile_photo).'?v='.($author->updated_at?->timestamp ?? now()->timestamp)
        : null;
    $initials = $author ? collect(preg_split('/\s+/', trim($author->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M' : 'M';

    $isSharedPost = $post->isShared();
    $originalPost = $isSharedPost ? $post->originalPost : null;
    $videoPost = ($isSharedPost && $originalPost && $originalPost->hasVideo()) ? $originalPost : $post;

    $mediaDirectory = 'uploads/posts/videos';
    $videoPath = $videoPost->media_path;
    $hasVideo = $videoPath
        && str_starts_with($videoPath, $mediaDirectory.'/')
        && ! str_contains($videoPath, '..')
        && $videoPath === $mediaDirectory.'/'.basename($videoPath)
        && file_exists(public_path($videoPath));

    $currentMemberId = auth('member')->id();
    $userReactionRecord = $post->reactions()->where('member_id', $currentMemberId)->first();
    $userReaction = $userReactionRecord?->reaction;
    $userEmoji = $userReactionRecord?->emoji() ?? '👍';
    $userLabel = $userReactionRecord?->label() ?? 'Like';
    $userColor = $userReactionRecord?->color() ?? '#475569';
    $topReactions = $post->topReactions() ?? collect();
    $reactionsCount = $post->reactions_count ?? $post->reactions()->count();
    $commentsCount = $post->comments_count ?? $post->comments()->count();
    $sharesCount = $post->shares_count ?? $post->shares()->count();
    $isSaved = $post->isSavedBy($currentMemberId);
    $savesCount = $post->saved_posts_count ?? $post->savedPosts()->count();

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

    $initialCommentsQuery = $post->comments()->with('member')->orderBy('created_at', 'desc')->take(6)->get();
    $hasMoreComments = $initialCommentsQuery->count() > 5;
    $initialComments = $initialCommentsQuery->take(5)->reverse()->values();
@endphp

<article class="card feed-post watch-video-card" data-post-id="{{ $post->id }}">
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
                </div>
                <a href="{{ route('member.posts.show', $post) }}">
                    <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                    <span aria-hidden="true">·</span>
                    <i data-lucide="video" aria-hidden="true" title="Watch Video"></i>
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
                    <a href="{{ route('member.people.show', $author->id) }}" style="color: inherit; text-decoration: none;">
                        <strong>{{ $author->name }}</strong>
                    </a>
                    @if ($author->user_id)
                        <small style="color: var(--color-text-muted); font-size: 0.775rem;">&#64;{{ $author->user_id }}</small>
                    @endif
                </div>
                <a href="{{ route('member.posts.show', $post) }}">
                    <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                    <span aria-hidden="true">·</span>
                    <i data-lucide="video" aria-hidden="true" title="Watch Video"></i>
                </a>
            </div>
        @endif

        <div class="post-header__options" data-post-options-wrapper>
            <button class="mini-button" type="button" aria-label="Post options" data-post-options-toggle="{{ $post->id }}">
                <i data-lucide="ellipsis" aria-hidden="true"></i>
            </button>
            <div class="post-options-menu" data-post-options-menu="{{ $post->id }}" hidden>
                <button class="post-options-menu__item" type="button" data-post-save-btn="{{ $post->id }}">
                    <i data-lucide="bookmark" aria-hidden="true"></i>
                    <span data-post-save-menu-label="{{ $post->id }}">{{ $isSaved ? 'Unsave Video' : 'Save Video' }}</span>
                </button>
                <button class="post-options-menu__item" type="button" data-post-hide-btn="{{ $post->id }}">
                    <i data-lucide="eye-off" aria-hidden="true"></i>
                    <span>Hide Video</span>
                </button>
                <button class="post-options-menu__item post-options-menu__item--danger" type="button" data-post-report-open="{{ $post->id }}">
                    <i data-lucide="flag" aria-hidden="true"></i>
                    <span>Report Video</span>
                </button>
            </div>
        </div>
    </header>

    @if ($post->body)
        <p class="post-copy" style="padding: 0 18px 12px 18px; margin: 0;">{{ $post->body }}</p>
    @endif

    <!-- Custom Responsive Video Player -->
    <div class="watch-player is-paused" data-watch-player>
        @if ($hasVideo)
            <video
                src="{{ asset($videoPath) }}"
                playsinline
                preload="metadata"
                aria-label="Video by {{ $author->name }}"
            ></video>
        @else
            <video
                src="{{ asset($videoPost->media_path) }}"
                playsinline
                preload="metadata"
                aria-label="Video by {{ $author->name }}"
            ></video>
        @endif

        <button class="watch-player__overlay-play" type="button" aria-label="Play video" data-watch-overlay-play>
            <i data-lucide="play" style="width: 28px; height: 28px; margin-left: 3px;" aria-hidden="true"></i>
        </button>

        <div class="watch-player__controls" data-watch-controls>
            <div class="watch-player__progress-bar" data-watch-progress-bar>
                <div class="watch-player__progress-fill" data-watch-progress-fill></div>
            </div>
            <div class="watch-player__controls-row">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button class="watch-player__btn" type="button" aria-label="Play or Pause" data-watch-play-btn>
                        <i data-lucide="play" aria-hidden="true"></i>
                    </button>
                    <div class="watch-player__volume-group">
                        <button class="watch-player__btn" type="button" aria-label="Mute or Unmute" data-watch-mute-btn>
                            <i data-lucide="volume-2" aria-hidden="true"></i>
                        </button>
                        <input class="watch-player__volume-slider" type="range" min="0" max="1" step="0.1" value="1" data-watch-volume-slider aria-label="Volume">
                    </div>
                    <span class="watch-player__time" data-watch-time>0:00 / 0:00</span>
                </div>
                <div>
                    <button class="watch-player__btn" type="button" aria-label="Toggle Fullscreen" data-watch-fullscreen-btn>
                        <i data-lucide="maximize" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

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
        </div>

        <a class="post-detail-link" href="{{ route('member.posts.show', $post) }}">
            View video <i data-lucide="arrow-right" aria-hidden="true"></i>
        </a>
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
                    <p>Be the first to comment on this video.</p>
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
