<div
    class="story-modal post-likers-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="post-likers-title"
    data-post-likers-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Close Post Likes" data-post-likers-close></button>

    <div class="story-modal__panel post-likers-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Post Likes</span>
                <h2 id="post-likers-title">Liked by {{ $likes->count() }} {{ \Illuminate\Support\Str::plural('person', $likes->count()) }}</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Post Likes" data-post-likers-close>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="post-likers-modal__body">
            @forelse ($likes as $like)
                @php
                    $member = $like->member;
                    if (! $member) continue;
                    $hasPhoto = $member->profile_photo
                        && str_starts_with($member->profile_photo, 'uploads/profile/')
                        && ! str_contains($member->profile_photo, '..')
                        && $member->profile_photo === 'uploads/profile/'.basename($member->profile_photo)
                        && file_exists(public_path($member->profile_photo));
                    $initials = collect(preg_split('/\s+/', trim($member->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->implode('') ?: 'M';
                @endphp
                <div class="post-liker-item">
                    <div class="post-liker-item__avatar-wrap">
                        @if ($hasPhoto)
                            <img class="post-liker-item__avatar" src="{{ asset($member->profile_photo) }}" alt="{{ $member->name }}">
                        @else
                            <span class="post-liker-item__avatar post-liker-item__avatar--initials" aria-hidden="true">{{ $initials }}</span>
                        @endif
                        <span class="post-liker-item__badge">👍</span>
                    </div>

                    <div class="post-liker-item__info">
                        <strong class="post-liker-item__name">{{ $member->name }}</strong>
                        <span class="post-liker-item__id">
                            @if ($member->user_id)
                                {{ $member->user_id }}
                            @else
                                ID: #{{ $member->id }}
                            @endif
                        </span>
                    </div>

                    <time class="post-liker-item__time" datetime="{{ $like->created_at->toIso8601String() }}">
                        {{ $like->created_at->diffForHumans() }}
                    </time>
                </div>
            @empty
                <div class="post-likers-empty">
                    <div class="post-likers-empty__icon">
                        <i data-lucide="thumbs-up" aria-hidden="true"></i>
                    </div>
                    <h3>No likes yet</h3>
                    <p>Be the first friend to like this post!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
