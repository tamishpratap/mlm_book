<div
    class="story-modal post-sharers-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="post-sharers-title"
    data-post-sharers-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Close Sharers" data-post-sharers-close></button>

    <div class="story-modal__panel post-sharers-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Post Sharers</span>
                <h2 id="post-sharers-title">Shared by {{ $shares->count() }} {{ \Illuminate\Support\Str::plural('member', $shares->count()) }}</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Sharers" data-post-sharers-close>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="post-sharers-modal__body">
            @forelse ($shares as $share)
                @php
                    $member = $share->member;
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
                        @if (filled($share->share_message))
                            <p class="post-sharer-item__caption">"{{ $share->share_message }}"</p>
                        @endif
                    </div>

                    <time class="post-liker-item__time" datetime="{{ $share->created_at->toIso8601String() }}">
                        {{ $share->created_at->diffForHumans() }}
                    </time>
                </div>
            @empty
                <div class="post-likers-empty">
                    <div class="post-likers-empty__icon">
                        <i data-lucide="share-2" aria-hidden="true"></i>
                    </div>
                    <h3>No shares yet</h3>
                    <p>Be the first friend to share this post!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
