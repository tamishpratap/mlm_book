<div
    class="story-modal story-viewers-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="story-viewers-title"
    data-story-viewers-modal
>
    <button class="story-modal__backdrop" type="button" aria-label="Close Story Views" data-story-viewers-close></button>

    <div class="story-modal__panel story-viewers-modal__panel">
        <header class="story-modal__header">
            <div>
                <span>Story Analytics</span>
                <h2 id="story-viewers-title">Seen by {{ $views->count() }}</h2>
            </div>
            <button class="story-modal__close" type="button" aria-label="Close Story Views" data-story-viewers-close>
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="story-viewers-modal__body">
            @forelse ($views as $view)
                @php
                    $viewer = $view->viewer;
                    if (! $viewer) continue;
                    $hasPhoto = $viewer->profile_photo
                        && str_starts_with($viewer->profile_photo, 'uploads/profile/')
                        && ! str_contains($viewer->profile_photo, '..')
                        && $viewer->profile_photo === 'uploads/profile/'.basename($viewer->profile_photo)
                        && file_exists(public_path($viewer->profile_photo));
                    $initials = collect(preg_split('/\s+/', trim($viewer->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->implode('') ?: 'M';
                @endphp
                <div class="story-viewer-item">
                    <div class="story-viewer-item__avatar-wrap">
                        @if ($hasPhoto)
                            <img class="story-viewer-item__avatar" src="{{ asset($viewer->profile_photo) }}" alt="{{ $viewer->name }}">
                        @else
                            <span class="story-viewer-item__avatar story-viewer-item__avatar--initials" aria-hidden="true">{{ $initials }}</span>
                        @endif
                    </div>
                    <div class="story-viewer-item__info">
                        <strong class="story-viewer-item__name">{{ $viewer->name }}</strong>
                        <span class="story-viewer-item__id">
                            @if ($viewer->user_id)
                                {{ $viewer->user_id }}
                            @else
                                ID: #{{ $viewer->id }}
                            @endif
                        </span>
                    </div>
                    <time class="story-viewer-item__time" datetime="{{ $view->created_at->toIso8601String() }}">
                        Viewed {{ $view->created_at->diffForHumans() }}
                    </time>
                </div>
            @empty
                <div class="story-viewers-empty">
                    <div class="story-viewers-empty__icon">
                        <i data-lucide="eye-off" aria-hidden="true"></i>
                    </div>
                    <h3>No one has viewed your Story yet.</h3>
                    <p>When connections view your Story, you will see them listed here.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
