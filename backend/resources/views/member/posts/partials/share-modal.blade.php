@php
    $targetPost = $post->originalPost ?? $post;
    $author = $targetPost->member;
    $hasPhoto = $author->profile_photo
        && str_starts_with($author->profile_photo, 'uploads/profile/')
        && ! str_contains($author->profile_photo, '..')
        && $author->profile_photo === 'uploads/profile/'.basename($author->profile_photo)
        && file_exists(public_path($author->profile_photo));
    $initials = collect(preg_split('/\s+/', trim($author->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';

    $currentMemberId = auth('member')->id();
    $acceptedFriends = \App\Models\Friendship::query()
        ->forMember($currentMemberId)
        ->accepted()
        ->with(['memberOne', 'memberTwo'])
        ->get()
        ->map(fn ($f) => $f->otherMember($currentMemberId));
    $shareUrl = route('member.posts.show', $post);
@endphp

<div
    class="story-modal post-share-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="post-share-modal-title"
    data-post-share-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Cancel Share" data-post-share-cancel></button>

    <div class="story-modal__panel post-share-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Share Post</span>
                <h2 id="post-share-modal-title">Share Options</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Modal" data-post-share-cancel>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <!-- Share Options Navigation Section -->
        <div class="share-options-nav">
            <button type="button" class="share-option-tab is-active" data-share-tab-trigger="feed">
                <i data-lucide="rss" aria-hidden="true"></i>
                <span>Share to Feed</span>
            </button>
            <button type="button" class="share-option-tab" data-share-tab-trigger="friend">
                <i data-lucide="send" aria-hidden="true"></i>
                <span>Send to Friend</span>
            </button>
            <button type="button" class="share-option-tab share-option-tab--copy" data-copy-link-btn="{{ $shareUrl }}">
                <i data-lucide="link" aria-hidden="true"></i>
                <span>Copy Link</span>
            </button>
        </div>

        <!-- 1. Share to Feed Panel (Existing Feature) -->
        <form
            class="post-share-modal__form share-tab-panel"
            data-share-tab-panel="feed"
            method="POST"
            action="{{ route('member.posts.share', $targetPost) }}"
            data-post-share-form="{{ $targetPost->id }}"
        >
            @csrf
            <div class="post-share-modal__body">
                <div class="post-share-modal__input-wrap">
                    <textarea
                        class="post-share-modal__textarea"
                        name="share_message"
                        placeholder="Say something about this..."
                        aria-label="Say something about this"
                        maxlength="1000"
                        rows="3"
                        autoFocus
                    ></textarea>
                </div>

                <!-- Original Post Preview Box -->
                <div class="shared-post-preview">
                    <div class="shared-post-preview__header">
                        <div class="shared-post-preview__avatar-wrap">
                            @if ($hasPhoto)
                                <img class="shared-post-preview__avatar" src="{{ asset($author->profile_photo) }}" alt="{{ $author->name }}">
                            @else
                                <span class="shared-post-preview__avatar shared-post-preview__avatar--initials" aria-hidden="true">{{ $initials }}</span>
                            @endif
                        </div>
                        <div class="shared-post-preview__info">
                            <strong class="shared-post-preview__name">{{ $author->name }}</strong>
                            <span class="shared-post-preview__time">{{ $targetPost->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    @if (filled($targetPost->body))
                        <p class="shared-post-preview__text">{{ \Illuminate\Support\Str::limit($targetPost->body, 280) }}</p>
                    @endif

                    @if ($targetPost->hasImage())
                        <div class="shared-post-preview__media">
                            <img src="{{ asset($targetPost->media_path) }}" alt="Original Post Media">
                        </div>
                    @elseif ($targetPost->hasVideo())
                        <div class="shared-post-preview__media">
                            <video src="{{ asset($targetPost->media_path) }}" controls preload="metadata"></video>
                        </div>
                    @endif
                </div>
            </div>

            <footer class="post-share-modal__footer">
                <button class="post-share-modal__btn-cancel" type="button" data-post-share-cancel>Cancel</button>
                <button class="post-share-modal__btn-submit" type="submit">Share Now</button>
            </footer>
        </form>

        <!-- 2. Send to Friend Panel -->
        <form
            class="post-share-modal__form share-tab-panel"
            data-share-tab-panel="friend"
            style="display: none;"
            method="POST"
            action="{{ route('member.posts.send-to-friends', $targetPost) }}"
            data-send-friends-form="{{ $targetPost->id }}"
        >
            @csrf
            <div class="post-share-modal__body">
                <div class="friend-search-wrap">
                    <i data-lucide="search" aria-hidden="true"></i>
                    <input
                        type="text"
                        class="friend-search-input"
                        placeholder="Search friends..."
                        aria-label="Search friends"
                        data-friend-search-input
                    >
                </div>

                <div class="friends-share-list" data-friends-share-list>
                    @forelse ($acceptedFriends as $friend)
                        @php
                            $friendHasPhoto = $friend->profile_photo
                                && str_starts_with($friend->profile_photo, 'uploads/profile/')
                                && ! str_contains($friend->profile_photo, '..')
                                && file_exists(public_path($friend->profile_photo));
                            $friendInitials = collect(preg_split('/\s+/', trim($friend->name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                                ->implode('') ?: 'M';
                            $isOnline = $friend->last_seen_at && $friend->last_seen_at->gt(now()->subMinutes(5));
                        @endphp
                        <label class="friend-share-item" data-friend-name="{{ strtolower($friend->name) }}" data-friend-username="{{ strtolower($friend->user_id ?? '') }}">
                            <div class="friend-share-item__avatar-wrap">
                                @if ($friendHasPhoto)
                                    <img class="friend-share-item__avatar" src="{{ asset($friend->profile_photo) }}" alt="{{ $friend->name }}">
                                @else
                                    <span class="friend-share-item__avatar friend-share-item__avatar--initials">{{ $friendInitials }}</span>
                                @endif
                                @if ($isOnline)
                                    <span class="friend-share-item__online-dot" title="Online"></span>
                                @endif
                            </div>
                            <div class="friend-share-item__info">
                                <strong class="friend-share-item__name">{{ $friend->name }}</strong>
                                <span class="friend-share-item__username">{{ '@'.($friend->user_id ?? 'member') }}</span>
                            </div>
                            <input type="checkbox" name="friend_ids[]" value="{{ $friend->id }}" class="friend-share-item__checkbox" data-friend-checkbox>
                        </label>
                    @empty
                        <div class="friends-share-empty">
                            <p>No accepted connections found to send to.</p>
                        </div>
                    @endforelse
                </div>

                <div class="post-share-modal__input-wrap">
                    <input
                        type="text"
                        class="friend-send-message-input"
                        name="message"
                        placeholder="Add an optional message..."
                        aria-label="Add an optional message"
                        maxlength="500"
                    >
                </div>
            </div>

            <footer class="post-share-modal__footer">
                <button class="post-share-modal__btn-cancel" type="button" data-post-share-cancel>Cancel</button>
                <button class="post-share-modal__btn-submit" type="submit" data-send-to-friends-submit-btn>Send</button>
            </footer>
        </form>
    </div>
</div>
