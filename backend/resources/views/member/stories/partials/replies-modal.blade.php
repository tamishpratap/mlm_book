<div
    class="story-modal story-replies-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="story-replies-title"
    data-story-replies-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Close Story Replies" data-story-replies-close></button>

    <div class="story-modal__panel story-replies-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Story Conversation</span>
                <h2 id="story-replies-title">Replies ({{ $replies->count() }})</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Story Replies" data-story-replies-close>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="story-replies-modal__body" data-story-replies-list>
            @forelse ($replies as $reply)
                @php
                    $sender = $reply->sender;
                    $isSelf = $sender->id === $currentMemberId;
                    $hasPhoto = $sender->profile_photo
                        && str_starts_with($sender->profile_photo, 'uploads/profile/')
                        && ! str_contains($sender->profile_photo, '..')
                        && $sender->profile_photo === 'uploads/profile/'.basename($sender->profile_photo)
                        && file_exists(public_path($sender->profile_photo));
                    $initials = collect(preg_split('/\s+/', trim($sender->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->implode('') ?: 'M';
                @endphp
                <div class="story-reply-item @if ($isSelf) story-reply-item--self @endif" data-story-reply-id="{{ $reply->id }}">
                    <div class="story-reply-item__avatar-wrap">
                        @if ($hasPhoto)
                            <img class="story-reply-item__avatar" src="{{ asset($sender->profile_photo) }}" alt="{{ $sender->name }}">
                        @else
                            <span class="story-reply-item__avatar story-reply-item__avatar--initials" aria-hidden="true">{{ $initials }}</span>
                        @endif
                    </div>
                    <div class="story-reply-item__content">
                        <div class="story-reply-item__header">
                            <strong class="story-reply-item__name">{{ $sender->name }}</strong>
                            <span class="story-reply-item__id">
                                @if ($sender->user_id)
                                    {{ $sender->user_id }}
                                @else
                                    ID: #{{ $sender->id }}
                                @endif
                            </span>
                            <time class="story-reply-item__time" datetime="{{ $reply->created_at->toIso8601String() }}">
                                {{ $reply->created_at->diffForHumans() }}
                            </time>
                        </div>
                        <div class="story-reply-item__bubble">
                            <p>{{ $reply->message }}</p>
                        </div>
                        <div class="story-reply-item__footer">
                            <span class="story-reply-item__status @if ($reply->is_seen) is-seen @endif">
                                @if ($reply->is_seen)
                                    <i data-lucide="check-check" aria-hidden="true"></i> Seen
                                @else
                                    <i data-lucide="check" aria-hidden="true"></i> Sent
                                @endif
                            </span>
                            @if ($isSelf || $isOwner)
                                <button
                                    class="story-reply-item__delete"
                                    type="button"
                                    title="Delete reply"
                                    aria-label="Delete reply"
                                    data-story-reply-delete="{{ $reply->id }}"
                                >
                                    <i data-lucide="trash-2" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="story-replies-empty">
                    <div class="story-replies-empty__icon">
                        <i data-lucide="message-square-off" aria-hidden="true"></i>
                    </div>
                    <h3>No replies yet</h3>
                    <p>When connections reply to your Story, their messages will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
