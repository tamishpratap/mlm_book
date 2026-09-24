@php
    $author = $comment->member;
    $commentPhotoUrl = $author?->profile_photo_url;
    $initials = collect(preg_split('/\s+/', trim($author->name ?? 'Member')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
    $currentMemberId = auth('member')->id();
    $postOwnerId = $post->member_id ?? $comment->post->member_id ?? null;
    $repliesCount = $comment->replies()->count();
    $initialRepliesQuery = $comment->replies()->with('member')->orderBy('created_at', 'asc')->take(3)->get();
    $hasMoreReplies = $initialRepliesQuery->count() > 2;
    $initialReplies = $initialRepliesQuery->take(2);

    $currentUser = auth('member')->user();
    $userAvatarUrl = $currentUser?->profile_photo_url;
    $userInitials = collect(preg_split('/\s+/', trim($currentUser->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
    $userReactionRecord = $comment->reactions()->where('member_id', $currentMemberId)->first();
    $userReaction = $userReactionRecord?->reaction;
    $userEmoji = $userReactionRecord?->emoji() ?? '👍';
    $userLabel = $userReactionRecord?->label() ?? 'Like';
    $userColor = $userReactionRecord?->color() ?? '#64748b';
    $commentReactionsCount = $comment->reactions()->count();
    $topCommentReactions = $comment->reactions()
        ->selectRaw('reaction, COUNT(*) as total')
        ->groupBy('reaction')
        ->orderByDesc('total')
        ->take(3)
        ->get();
    $canEdit = $comment->member_id === $currentMemberId;
    $canDelete = $comment->member_id === $currentMemberId || $postOwnerId === $currentMemberId;
@endphp

<div class="post-comment-item" id="post-comment-{{ $comment->id }}" data-post-comment-id="{{ $comment->id }}">
    <div class="post-comment-item__avatar-wrap">
        @if ($commentPhotoUrl)
            <img class="post-comment-item__avatar" src="{{ $commentPhotoUrl }}" alt="{{ $author->name }}">
        @else
            <span class="post-comment-item__avatar post-comment-item__avatar--initials" aria-hidden="true">{{ $initials }}</span>
        @endif
    </div>

    <div class="post-comment-item__content">
        <div class="post-comment-item__bubble">
            <div class="post-comment-item__meta">
                <strong class="post-comment-item__name">{{ $author->name }}</strong>
                <span class="post-comment-item__id">
                    @if ($author->user_id)
                        {{ $author->user_id }}
                    @else
                        ID: #{{ $author->id }}
                    @endif
                </span>

                @if ($canEdit || $canDelete)
                    <div class="post-comment-options-wrapper" data-comment-options-wrapper="{{ $comment->id }}">
                        <button
                            class="post-comment-item__options-btn"
                            type="button"
                            aria-label="Comment options"
                            data-post-comment-options-toggle="{{ $comment->id }}"
                        >
                            <i data-lucide="more-horizontal" aria-hidden="true"></i>
                        </button>
                        <div class="post-comment-options-menu" data-post-comment-options-menu="{{ $comment->id }}" hidden>
                            @if ($canEdit)
                                <button
                                    class="post-comment-options-menu__item"
                                    type="button"
                                    data-post-comment-edit-toggle="{{ $comment->id }}"
                                >
                                    <i data-lucide="pencil" aria-hidden="true"></i>
                                    Edit
                                </button>
                            @endif
                            @if ($canDelete)
                                <button
                                    class="post-comment-options-menu__item post-comment-options-menu__item--danger"
                                    type="button"
                                    data-post-comment-delete="{{ $comment->id }}"
                                    data-post-id="{{ $comment->post_id }}"
                                >
                                    <i data-lucide="trash-2" aria-hidden="true"></i>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <p class="post-comment-item__text" data-comment-text-display="{{ $comment->id }}">{{ $comment->comment }}</p>

            @if ($canEdit)
                <!-- Inline Edit Comment Form -->
                <form
                    class="post-comment-edit-form"
                    method="POST"
                    action="{{ route('member.posts.comments.update', $comment) }}"
                    data-post-comment-edit-form="{{ $comment->id }}"
                    hidden
                >
                    @csrf
                    @method('PUT')
                    <textarea
                        class="post-comment-edit-form__input"
                        name="comment"
                        aria-label="Edit comment"
                        maxlength="1000"
                        required
                        data-post-comment-edit-input="{{ $comment->id }}"
                    >{{ $comment->comment }}</textarea>
                    <div class="post-comment-edit-form__actions">
                        <button class="post-comment-edit-form__btn-cancel" type="button" data-post-comment-edit-cancel="{{ $comment->id }}">Cancel</button>
                        <button class="post-comment-edit-form__btn-save" type="submit">Save</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="post-comment-item__footer">
            <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time>

            <span
                class="post-comment-item__edited-badge"
                data-comment-edited-badge="{{ $comment->id }}"
                style="@if(!$comment->isEdited()) display: none; @endif"
            >· Edited</span>

            <div class="comment-like-wrapper" data-comment-like-wrapper="{{ $comment->id }}">
                <div class="comment-reaction-picker" data-comment-reaction-picker="{{ $comment->id }}" hidden>
                    @foreach (\App\Models\CommentReaction::EMOJI_MAP as $rType => $rEmoji)
                        <button
                            class="comment-reaction-picker__item"
                            type="button"
                            title="{{ \App\Models\CommentReaction::LABEL_MAP[$rType] }}"
                            aria-label="React {{ \App\Models\CommentReaction::LABEL_MAP[$rType] }}"
                            data-comment-react-btn="{{ $rType }}"
                            data-comment-id="{{ $comment->id }}"
                        >{{ $rEmoji }}</button>
                    @endforeach
                </div>

                <button
                    class="post-comment-item__react-btn @if ($userReaction) is-active @endif"
                    type="button"
                    aria-label="React to comment"
                    style="@if($userReaction) color: {{ $userColor }}; font-weight: 700; @endif"
                    data-comment-like-btn="{{ $comment->id }}"
                    data-comment-id="{{ $comment->id }}"
                >
                    <span data-comment-reaction-label="{{ $comment->id }}">{{ $userReaction ? $userLabel : 'Like' }}</span>
                </button>
            </div>

            <button
                class="post-comment-item__reply-btn"
                type="button"
                data-post-reply-toggle="{{ $comment->id }}"
            >
                Reply
            </button>

            <button
                class="post-comment-item__reactors-trigger"
                type="button"
                aria-label="View comment reactions"
                data-comment-reactors-open="{{ $comment->id }}"
                style="@if($commentReactionsCount === 0) display: none; @endif"
            >
                <span class="post-comment-item__reaction-badges" data-comment-top-reactions="{{ $comment->id }}">
                    @foreach ($topCommentReactions as $topR)
                        <span class="post-comment-item__badge">{{ \App\Models\CommentReaction::EMOJI_MAP[$topR->reaction] ?? '👍' }}</span>
                    @endforeach
                </span>
                <span data-comment-reactions-count="{{ $comment->id }}">{{ $commentReactionsCount }}</span>
            </button>
        </div>

        <!-- Nested Replies Section -->
        <div class="post-replies-section" data-post-replies-section="{{ $comment->id }}">
            @if ($hasMoreReplies)
                <button
                    class="post-replies__more"
                    type="button"
                    data-post-replies-more="{{ $comment->id }}"
                    data-offset="2"
                >View more replies (<span data-post-replies-count="{{ $comment->id }}">{{ $repliesCount }}</span>)</button>
            @endif

            <div class="post-replies__list" data-post-replies-list="{{ $comment->id }}">
                @foreach ($initialReplies as $reply)
                    @include('member.posts.partials.reply-item', ['post' => $post, 'parentComment' => $comment, 'reply' => $reply])
                @endforeach
            </div>

            <!-- Inline Reply Form -->
            <form
                class="post-reply-form"
                method="POST"
                action="{{ route('member.comments.replies.store', $comment) }}"
                data-post-reply-form="{{ $comment->id }}"
                hidden
            >
                @csrf
                <div class="post-reply-form__avatar-wrap">
                    @if ($userAvatarUrl)
                        <img class="post-reply-form__avatar" src="{{ $userAvatarUrl }}" alt="{{ $currentUser->name }}">
                    @else
                        <span class="post-reply-form__avatar post-reply-form__avatar--initials">{{ $userInitials }}</span>
                    @endif
                </div>

                <div class="post-reply-form__input-wrap">
                    <input
                        class="post-reply-form__input"
                        type="text"
                        name="comment"
                        placeholder="Write a reply..."
                        aria-label="Write a reply"
                        maxlength="1000"
                        required
                        autocomplete="off"
                        data-post-reply-input="{{ $comment->id }}"
                    >
                    <button class="post-reply-form__submit" type="submit" aria-label="Send Reply">
                        <i data-lucide="send-horizontal" aria-hidden="true"></i>
                    </button>
                    <button class="post-reply-form__cancel" type="button" aria-label="Cancel Reply" data-post-reply-cancel="{{ $comment->id }}">
                        <i data-lucide="x" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
