<div
    class="story-modal story-reactors-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="story-reactors-title"
    data-story-reactors-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Close Story Reactions" data-story-reactors-close></button>

    <div class="story-modal__panel story-reactors-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Story Analytics</span>
                <h2 id="story-reactors-title">Reactions ({{ $reactions->count() }})</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Story Reactions" data-story-reactors-close>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="story-reactors-modal__body">
            @forelse ($reactions as $reactionItem)
                @php
                    $reactor = $reactionItem->member;
                    if (! $reactor) continue;
                    $hasPhoto = $reactor->profile_photo
                        && str_starts_with($reactor->profile_photo, 'uploads/profile/')
                        && ! str_contains($reactor->profile_photo, '..')
                        && $reactor->profile_photo === 'uploads/profile/'.basename($reactor->profile_photo)
                        && file_exists(public_path($reactor->profile_photo));
                    $initials = collect(preg_split('/\s+/', trim($reactor->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->implode('') ?: 'M';
                    $emoji = $reactionItem->emoji();
                @endphp
                <div class="story-reactor-item">
                    <div class="story-reactor-item__avatar-wrap">
                        @if ($hasPhoto)
                            <img class="story-reactor-item__avatar" src="{{ asset($reactor->profile_photo) }}" alt="{{ $reactor->name }}">
                        @else
                            <span class="story-reactor-item__avatar story-reactor-item__avatar--initials" aria-hidden="true">{{ $initials }}</span>
                        @endif
                        <span class="story-reactor-item__emoji-badge" aria-label="{{ $reactionItem->label() }}">{{ $emoji }}</span>
                    </div>
                    <div class="story-reactor-item__info">
                        <strong class="story-reactor-item__name">{{ $reactor->name }}</strong>
                        <span class="story-reactor-item__id">
                            @if ($reactor->user_id)
                                {{ $reactor->user_id }}
                            @else
                                ID: #{{ $reactor->id }}
                            @endif
                        </span>
                    </div>
                    <time class="story-reactor-item__time" datetime="{{ $reactionItem->updated_at->toIso8601String() }}">
                        {{ $reactionItem->updated_at->diffForHumans() }}
                    </time>
                </div>
            @empty
                <div class="story-reactors-empty">
                    <div class="story-reactors-empty__icon">
                        <i data-lucide="heart-off" aria-hidden="true"></i>
                    </div>
                    <h3>No reactions yet</h3>
                    <p>When connections react to your Story, you will see them listed here.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
