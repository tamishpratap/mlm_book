@php
    $author = $reply->member;
    $replyPhotoUrl = $author?->profile_photo_url;
    $initials = collect(preg_split('/\s+/', trim($author->name ?? 'Member')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';

    $currentMemberId = auth('member')->id();
    $postOwnerId = $post->member_id ?? $reply->post->member_id ?? null;
    $parentAuthorId = $parentComment->member_id ?? $reply->parent?->member_id ?? null;

    $userReactionRecord = $reply->reactions()->where('member_id', $currentMemberId)->first();
    $userReaction = $userReactionRecord?->reaction;
    $userEmoji = $userReactionRecord?->emoji() ?? '👍';
    $userLabel = $userReactionRecord?->label() ?? 'Like';
    $userColor = $userReactionRecord?->color() ?? '#64748b';
    $replyReactionsCount = $reply->reactions()->count();
    $topReplyReactions = $reply->reactions()
        ->selectRaw('reaction, COUNT(*) as total')
        ->groupBy('reaction')
        ->orderByDesc('total')
        ->take(3)
        ->get();
    $canEdit = $reply->member_id === $currentMemberId;
    $canDelete = $reply->member_id === $currentMemberId
        || $postOwnerId === $currentMemberId
        || ($parentAuthorId && $parentAuthorId === $currentMemberId);
@endphp

<div class="post-reply-item" id="post-comment-{{ $reply->id }}" data-post-comment-id="{{ $reply->id }}" data-parent-id="{{ $reply->parent_id }}">
    <div class="post-reply-item__avatar-wrap">
        @if ($replyPhotoUrl)
            <img class="post-reply-item__avatar" src="{{ $replyPhotoUrl }}" alt="{{ $author->name }}">
        @else
            <span class="post-reply-item__avatar post-reply-item__avatar--initials" aria-hidden="true">{{ $initials }}</span>
        @endif
    </div>

    <div class="post-reply-item__content">
        <div class="post-reply-item__bubble">
            <div class="post-reply-item__meta">
                <strong class="post-reply-item__name">{{ $author->name }}</strong>
                <span class="post-reply-item__id">
                    @if ($author->user_id)
                        {{ $author->user_id }}
                    @else
                        ID: #{{ $author->id }}
                    @endif
                </span>

                @if ($canEdit || $canDelete)
                    <div class="post-comment-options-wrapper" data-comment-options-wrapper="{{ $reply->id }}">
                        <button
                            class="post-comment-item__options-btn"
                            type="button"
                            aria-label="Reply options"
                            data-post-comment-options-toggle="{{ $reply->id }}"
                        >
                            <i data-lucide="more-horizontal" aria-hidden="true"></i>
                        </button>
                        <div class="post-comment-options-menu" data-post-comment-options-menu="{{ $reply->id }}" hidden>
                            @if ($canEdit)
                                <button
                                    class="post-comment-options-menu__item"
                                    type="button"
                                    data-post-comment-edit-toggle="{{ $reply->id }}"
                                >
                                    <i data-lucide="pencil" aria-hidden="true"></i>
                                    Edit
                                </button>
                            @endif
                            @if ($canDelete)
                                <button
                                    class="post-comment-options-menu__item post-comment-options-menu__item--danger"
                                    type="button"
                                    data-post-comment-delete="{{ $reply->id }}"
                                    data-post-id="{{ $reply->post_id }}"
                                    data-parent-id="{{ $reply->parent_id }}"
                                >
                                    <i data-lucide="trash-2" aria-hidden="true"></i>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <p class="post-reply-item__text" data-comment-text-display="{{ $reply->id }}">{{ $reply->comment }}</p>

            @if ($canEdit)
                <!-- Inline Edit Reply Form -->
                <form
                    class="post-comment-edit-form"
                    method="POST"
                    action="{{ route('member.posts.comments.update', $reply) }}"
                    data-post-comment-edit-form="{{ $reply->id }}"
                    hidden
                >
                    @csrf
                    @method('PUT')
                    <textarea
                        class="post-comment-edit-form__input"
                        name="comment"
                        aria-label="Edit reply"
                        maxlength="1000"
                        required
                        data-post-comment-edit-input="{{ $reply->id }}"
                    >{{ $reply->comment }}</textarea>
                    <div class="post-comment-edit-form__actions">
                        <button class="post-comment-edit-form__btn-cancel" type="button" data-post-comment-edit-cancel="{{ $reply->id }}">Cancel</button>
                        <button class="post-comment-edit-form__btn-save" type="submit">Save</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="post-reply-item__footer">
            <time datetime="{{ $reply->created_at->toIso8601String() }}">{{ $reply->created_at->diffForHumans() }}</time>

            <span
                class="post-comment-item__edited-badge"
                data-comment-edited-badge="{{ $reply->id }}"
                style="@if(!$reply->isEdited()) display: none; @endif"
            >· Edited</span>

            <div class="comment-like-wrapper" data-comment-like-wrapper="{{ $reply->id }}">
                <div class="comment-reaction-picker" data-comment-reaction-picker="{{ $reply->id }}" hidden>
                    @foreach (\App\Models\CommentReaction::EMOJI_MAP as $rType => $rEmoji)
                        <button
                            class="comment-reaction-picker__item"
                            type="button"
                            title="{{ \App\Models\CommentReaction::LABEL_MAP[$rType] }}"
                            aria-label="React {{ \App\Models\CommentReaction::LABEL_MAP[$rType] }}"
                            data-comment-react-btn="{{ $rType }}"
                            data-comment-id="{{ $reply->id }}"
                        >{{ $rEmoji }}</button>
                    @endforeach
                </div>

                <button
                    class="post-reply-item__react-btn @if ($userReaction) is-active @endif"
                    type="button"
                    aria-label="React to reply"
                    style="@if($userReaction) color: {{ $userColor }}; font-weight: 700; @endif"
                    data-comment-like-btn="{{ $reply->id }}"
                    data-comment-id="{{ $reply->id }}"
                >
                    <span data-comment-reaction-label="{{ $reply->id }}">{{ $userReaction ? $userLabel : 'Like' }}</span>
                </button>
            </div>

            <button
                class="post-reply-item__reactors-trigger"
                type="button"
                aria-label="View reply reactions"
                data-comment-reactors-open="{{ $reply->id }}"
                style="@if($replyReactionsCount === 0) display: none; @endif"
            >
                <span class="post-reply-item__reaction-badges" data-comment-top-reactions="{{ $reply->id }}">
                    @foreach ($topReplyReactions as $topR)
                        <span class="post-reply-item__badge">{{ \App\Models\CommentReaction::EMOJI_MAP[$topR->reaction] ?? '👍' }}</span>
                    @endforeach
                </span>
                <span data-comment-reactions-count="{{ $reply->id }}">{{ $replyReactionsCount }}</span>
            </button>
        </div>
    </div>
</div>
