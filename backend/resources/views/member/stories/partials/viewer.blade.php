@php
    $author = $story->member;
    $expectedMediaDirectory = $story->isImage()
        ? 'uploads/stories/images'
        : 'uploads/stories/videos';
    $hasSafeMedia = in_array($story->media_type, ['image', 'video'], true)
        && str_starts_with($story->media_path, $expectedMediaDirectory.'/')
        && ! str_contains($story->media_path, '..')
        && $story->media_path === $expectedMediaDirectory.'/'.basename($story->media_path)
        && file_exists(public_path($story->media_path));
    $hasAuthorPhoto = $author->profile_photo
        && str_starts_with($author->profile_photo, 'uploads/profile/')
        && ! str_contains($author->profile_photo, '..')
        && $author->profile_photo === 'uploads/profile/'.basename($author->profile_photo)
        && file_exists(public_path($author->profile_photo));
    $initials = collect(preg_split('/\s+/', trim($author->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
    $isOwner = auth('member')->id() === $author->id;
    $prevId = $previousStoryId ?? null;
    $nextId = $nextStoryId ?? null;
    $storyCount = $authorStoryCount ?? 1;
    $storyIndex = $authorStoryIndex ?? 0;
@endphp

<div
    class="story-viewer @if ($standalone ?? false) is-open @endif"
    role="dialog"
    aria-modal="true"
    aria-labelledby="story-viewer-title"
    data-story-viewer
    data-story-id="{{ $story->id }}"
    data-story-prev-id="{{ $prevId }}"
    data-story-next-id="{{ $nextId }}"
    data-story-duration="{{ $story->isVideo() ? 0 : 5000 }}"
    data-story-standalone="{{ ($standalone ?? false) ? 'true' : 'false' }}"
    data-story-close-url="{{ route('member.socials') }}"
    data-story-media-type="{{ $story->media_type }}"
>
    @if ($standalone ?? false)
        <a class="story-viewer__backdrop" href="{{ route('member.socials') }}" aria-label="Close Story" data-story-viewer-close></a>
    @else
        <button class="story-viewer__backdrop" type="button" aria-label="Close Story" data-story-viewer-close></button>
    @endif

    <section class="story-viewer__panel" tabindex="-1">
        <div class="story-viewer__progress" aria-hidden="true" data-story-progress-bar data-story-count="{{ $storyCount }}" data-story-index="{{ $storyIndex }}">
            @if ($storyCount > 1)
                @for ($i = 0; $i < $storyCount; $i++)
                    <div class="story-viewer__progress-segment">
                        <div
                            class="story-viewer__progress-fill"
                            @if ($i === $storyIndex) data-story-progress-fill @endif
                            style="@if ($i < $storyIndex) width: 100%; @elseif ($i > $storyIndex) width: 0%; @endif"
                        ></div>
                    </div>
                @endfor
            @else
                <div class="story-viewer__progress-segment">
                    <div class="story-viewer__progress-fill" data-story-progress-fill></div>
                </div>
            @endif
        </div>

        <div class="story-viewer__background" aria-hidden="true">
            @if ($hasSafeMedia)
                @if ($story->isImage())
                    <img src="{{ asset($story->media_path) }}" alt="">
                @else
                    <video src="{{ asset($story->media_path) }}" muted playsinline loop></video>
                @endif
            @else
                <div class="story-viewer__background--text"></div>
            @endif
        </div>
        <div class="story-viewer__shade" aria-hidden="true"></div>

        <header class="story-viewer__header">
            <div class="story-viewer__author">
                @if ($hasAuthorPhoto)
                    <img src="{{ asset($author->profile_photo) }}" alt="{{ $author->name }}">
                @else
                    <span aria-hidden="true">{{ $initials }}</span>
                @endif
                <div>
                    <div class="story-viewer__author-line">
                        <h1 id="story-viewer-title">{{ $author->name }}</h1>
                        @if ($isOwner)
                            <span class="story-viewer__owner">Your Story</span>
                        @else
                            <span class="story-viewer__badge">Connection</span>
                        @endif
                    </div>
                    <time datetime="{{ $story->created_at->toIso8601String() }}">{{ $story->created_at->diffForHumans() }}</time>
                </div>
            </div>

            <div class="story-viewer__top-actions">
                @if ($hasSafeMedia && $story->isVideo())
                    <button class="story-viewer__sound" type="button" aria-label="Toggle Sound" data-story-mute>
                        <i data-lucide="volume-x" aria-hidden="true"></i>
                    </button>
                @endif
                @if ($isOwner)
                    <div class="story-viewer__options-wrap">
                        <button class="story-viewer__options-btn" type="button" aria-label="Story options" data-story-options-toggle="{{ $story->id }}">
                            <i data-lucide="ellipsis" aria-hidden="true"></i>
                        </button>
                        <div class="story-viewer__options-menu" data-story-options-menu="{{ $story->id }}" hidden>
                            <button class="story-viewer__option-item story-viewer__option-item--danger" type="button" data-story-delete-trigger="{{ $story->id }}">
                                <i data-lucide="trash-2" aria-hidden="true"></i>
                                <span>Delete Story</span>
                            </button>
                            <button class="story-viewer__option-item" type="button" data-story-copy-link="{{ route('member.stories.show', $story) }}">
                                <i data-lucide="link" aria-hidden="true"></i>
                                <span>Copy Story Link</span>
                            </button>
                            <button class="story-viewer__option-item" type="button" data-story-options-close="{{ $story->id }}">
                                <i data-lucide="x" aria-hidden="true"></i>
                                <span>Cancel</span>
                            </button>
                        </div>
                    </div>
                @endif
                @if ($standalone ?? false)
                    <a class="story-viewer__close" href="{{ route('member.socials') }}" aria-label="Close Story" data-story-viewer-close>
                        <i data-lucide="x" aria-hidden="true"></i>
                    </a>
                @else
                    <button class="story-viewer__close" type="button" aria-label="Close Story" data-story-viewer-close>
                        <i data-lucide="x" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
        </header>

        <div class="story-viewer__media" data-story-media-wrap>
            <button class="story-viewer__nav story-viewer__nav--prev" type="button" aria-label="Previous Story" data-story-nav-prev @if(!$prevId) disabled @endif></button>
            <button class="story-viewer__nav story-viewer__nav--next" type="button" aria-label="Next Story" data-story-nav-next></button>

            @if ($hasSafeMedia)
                <div class="story-viewer__loading" role="status" aria-label="Loading Story" data-story-loading>
                    <div class="story-viewer__skeleton" aria-hidden="true"></div>
                </div>

                @if ($story->isImage())
                    <img
                        class="story-viewer__media-content"
                        src="{{ asset($story->media_path) }}"
                        alt="{{ $author->name }}'s Story"
                        data-story-media
                    >
                @else
                    <video
                        class="story-viewer__media-content"
                        src="{{ asset($story->media_path) }}"
                        muted
                        playsinline
                        preload="auto"
                        aria-label="{{ $author->name }}'s Story video"
                        data-story-media
                    ></video>
                    <button class="story-viewer__play is-paused" type="button" aria-label="Play Story" data-story-play hidden>
                        <i data-lucide="play" aria-hidden="true"></i>
                    </button>
                @endif

                <div class="story-viewer__error" role="alert" data-story-error hidden>
                    <i data-lucide="circle-alert" aria-hidden="true"></i>
                    <span>This Story could not be loaded.</span>
                </div>
            @elseif ($story->caption)
                <div class="story-viewer__text-canvas" role="region" aria-label="Text Story">
                    <div class="story-viewer__text-canvas-inner">
                        <p class="story-viewer__text-content">{{ $story->caption }}</p>
                    </div>
                </div>
            @else
                <div class="story-viewer__unavailable" role="alert">
                    <i data-lucide="image-off" aria-hidden="true"></i>
                    <span>This Story could not be loaded.</span>
                </div>
            @endif
        </div>

        <div class="story-viewer__bottom-container">
            @if ($story->caption && $hasSafeMedia)
                @php
                    $isLongCaption = mb_strlen($story->caption) > 110 || str_contains($story->caption, "\n");
                    $firstLine = strtok($story->caption, "\n");
                    $captionPreview = $isLongCaption
                        ? (mb_strlen($firstLine) <= 110
                            ? $firstLine
                            : mb_substr($firstLine, 0, 100) . '...')
                        : $story->caption;
                @endphp
                <div class="story-viewer__caption-card @if($isLongCaption) is-collapsed @else is-short @endif" role="region" aria-label="Story caption" data-story-caption-card>
                    @if ($isLongCaption)
                        <div class="story-viewer__caption-header" data-story-caption-header hidden>
                            <span class="story-viewer__caption-title">Story Caption</span>
                            <button type="button" class="story-viewer__caption-collapse-btn" data-story-caption-collapse aria-label="Collapse caption">
                                <i data-lucide="chevron-down" aria-hidden="true"></i>
                                <span>Show less</span>
                            </button>
                        </div>
                    @endif
                    <div class="story-viewer__caption-body">
                        @if ($isLongCaption)
                            <p class="story-viewer__caption story-viewer__caption--preview" data-story-caption-preview>
                                <span>{{ $captionPreview }} </span>
                                <button type="button" class="story-viewer__caption-more-btn" data-story-caption-more aria-label="See full caption">See more</button>
                            </p>
                            <p class="story-viewer__caption story-viewer__caption--full" data-story-caption-full hidden>
                                {{ $story->caption }}
                            </p>
                        @else
                            <p class="story-viewer__caption story-viewer__caption--full">
                                {{ $story->caption }}
                            </p>
                        @endif
                    </div>
                    @if ($isLongCaption)
                        <div class="story-viewer__caption-footer" data-story-caption-footer hidden>
                            <button type="button" class="story-viewer__caption-collapse-btn story-viewer__caption-collapse-btn--bottom" data-story-caption-collapse aria-label="Collapse caption">
                                <i data-lucide="chevron-up" aria-hidden="true"></i>
                                <span>Show less</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            @php
                $currentMemberId = auth('member')->id();
                $viewsCount = $isOwner
                    ? ($story->views_count ?? $story->views()->count())
                    : null;
                $reactionsCount = $isOwner ? ($story->reactions_count ?? $story->reactions()->count()) : null;
                $userReactionModel = $story->reactions()->where('member_id', $currentMemberId)->first();
                $userReaction = $userReactionModel?->reaction;
                $userLike = $story->likes()->where('member_id', $currentMemberId)->exists();

                $topReactionTypes = $isOwner ? $story->reactions()
                    ->selectRaw('reaction, count(*) as total')
                    ->groupBy('reaction')
                    ->orderByDesc('total')
                    ->limit(3)
                    ->pluck('reaction')
                    ->toArray() : [];
                $topReactions = array_map(fn ($r) => \App\Models\StoryReaction::EMOJI_MAP[$r] ?? '👍', $topReactionTypes);
                $repliesCount = $isOwner ? ($story->replies_count ?? $story->replies()->count()) : null;
                $unreadRepliesCount = $isOwner
                    ? $story->replies()->where('receiver_id', $currentMemberId)->where('is_seen', false)->count()
                    : 0;
            @endphp

            <div class="story-viewer__footer">
                <div class="story-viewer__footer-actions">
                    @if ($isOwner)
                        <button
                            class="story-viewer__seen-by"
                            type="button"
                            aria-label="Seen by {{ $viewsCount }}"
                            data-story-viewers-open="{{ $story->id }}"
                        >
                            <i data-lucide="eye" aria-hidden="true"></i>
                            <span data-story-views-count="{{ $story->id }}">Seen by {{ $viewsCount }}</span>
                        </button>

                        <button
                            class="story-viewer__react-summary story-viewer__react-summary--owner"
                            type="button"
                            aria-label="View reactions"
                            data-story-reactors-open="{{ $story->id }}"
                        >
                            <span class="story-viewer__react-emojis" data-story-top-reactions="{{ $story->id }}">
                                @foreach ($topReactions as $emoji)
                                    <i>{{ $emoji }}</i>
                                @endforeach
                            </span>
                            <span data-story-reactions-count="{{ $story->id }}">{{ $reactionsCount }}</span>
                        </button>

                        <button
                            class="story-viewer__replies-btn"
                            type="button"
                            aria-label="View story replies"
                            data-story-replies-open="{{ $story->id }}"
                        >
                            <i data-lucide="message-square" aria-hidden="true"></i>
                            <span>Replies</span>
                            <span class="story-viewer__replies-count" data-story-replies-count="{{ $story->id }}">{{ $repliesCount }}</span>
                            @if ($unreadRepliesCount > 0)
                                <span class="story-viewer__unread-badge" data-story-unread-badge="{{ $story->id }}">{{ $unreadRepliesCount }}</span>
                            @endif
                        </button>
                    @else
                        <form
                            class="story-viewer__reply-form"
                            action="{{ route('member.stories.reply', $story) }}"
                            method="POST"
                            data-story-reply-form="{{ $story->id }}"
                        >
                            @csrf
                            <input
                                class="story-viewer__reply-input"
                                type="text"
                                name="message"
                                placeholder="Reply to {{ $author->name }}..."
                                maxlength="500"
                                required
                                autocomplete="off"
                                data-story-reply-input="{{ $story->id }}"
                            >
                            <button class="story-viewer__reply-submit" type="submit" aria-label="Send reply">
                                <i data-lucide="send" aria-hidden="true"></i>
                            </button>
                        </form>

                        <div class="story-viewer__like-wrapper">
                            <div class="story-reaction-picker" data-story-reaction-picker="{{ $story->id }}" hidden>
                                @foreach (\App\Models\StoryReaction::EMOJI_MAP as $reactionKey => $emoji)
                                    <button
                                        class="story-reaction-picker__btn @if ($userReaction === $reactionKey) is-active @endif"
                                        type="button"
                                        aria-label="React {{ \App\Models\StoryReaction::LABEL_MAP[$reactionKey] }}"
                                        data-story-react-btn="{{ $reactionKey }}"
                                        data-story-id="{{ $story->id }}"
                                    >
                                        <span>{{ $emoji }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <button
                                class="story-viewer__like-btn @if ($userReaction || $userLike) is-active @endif"
                                type="button"
                                aria-label="Like Story"
                                data-story-like-btn="{{ $story->id }}"
                                data-story-id="{{ $story->id }}"
                            >
                                <span class="story-viewer__like-icon" data-story-like-icon="{{ $story->id }}">
                                    @if ($userReaction && isset(\App\Models\StoryReaction::EMOJI_MAP[$userReaction]))
                                        {{ \App\Models\StoryReaction::EMOJI_MAP[$userReaction] }}
                                    @else
                                        <i data-lucide="thumbs-up" aria-hidden="true"></i>
                                    @endif
                                </span>
                                <span class="story-viewer__like-label" data-story-like-label="{{ $story->id }}">
                                    {{ $userReaction ? \App\Models\StoryReaction::LABEL_MAP[$userReaction] : ($userLike ? 'Liked' : 'Like') }}
                                </span>
                            </button>
                        </div>
                    @endif
                </div>
                <div class="story-viewer__toast" data-story-toast="{{ $story->id }}" hidden role="status" aria-live="polite"></div>
            </div>
        </div>
    </section>

    @if ($isOwner)
        <div class="story-modal story-delete-modal" role="dialog" aria-modal="true" aria-labelledby="story-delete-title-{{ $story->id }}" data-story-delete-modal="{{ $story->id }}" hidden>
            <button class="story-modal__backdrop" type="button" aria-label="Cancel" data-story-delete-cancel="{{ $story->id }}"></button>
            <div class="story-modal__panel story-delete-modal__panel">
                <div class="story-delete-modal__icon">
                    <i data-lucide="trash-2" aria-hidden="true"></i>
                </div>
                <h2 id="story-delete-title-{{ $story->id }}">Delete Story?</h2>
                <p>Are you sure you want to delete this story? This action cannot be undone.</p>
                <div class="story-delete-modal__actions">
                    <button class="member-button member-button--secondary" type="button" data-story-delete-cancel="{{ $story->id }}">Cancel</button>
                    <button class="member-button member-button--danger" type="button" data-story-delete-confirm="{{ $story->id }}" data-story-delete-url="{{ route('member.stories.destroy', $story) }}">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
