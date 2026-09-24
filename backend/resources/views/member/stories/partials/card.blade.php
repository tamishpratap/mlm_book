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
    $isOwner = $story->member_id === $currentMemberId;
    $viewsCount = $isOwner
        ? ($story->views_count ?? $story->views()->count())
        : null;
@endphp

<a
    class="story story--published"
    href="{{ route('member.stories.show', $story) }}"
    data-story-link
    data-story-id="{{ $story->id }}"
    data-story-member-id="{{ $story->member_id }}"
    aria-label="View {{ $author->name }}'s Story"
>
    @if ($hasSafeMedia && $story->isImage())
        <img class="story__cover" src="{{ asset($story->media_path) }}" alt="{{ $author->name }}'s Story">
    @elseif ($hasSafeMedia && $story->isVideo())
        <video class="story__cover" src="{{ asset($story->media_path) }}" muted playsinline preload="metadata" aria-label="{{ $author->name }}'s Story video"></video>
    @else
        <span class="story__media-unavailable"><i data-lucide="image-off" aria-hidden="true"></i></span>
    @endif
    <span class="story__overlay" aria-hidden="true"></span>
    @if ($hasAuthorPhoto)
        <img class="story__avatar" src="{{ asset($author->profile_photo) }}" alt="">
    @else
        <span class="story__avatar story__avatar--initials" aria-hidden="true">{{ $initials }}</span>
    @endif
    @if ($isOwner)
        <button
            class="story__views-badge story__views-badge--owner"
            type="button"
            title="Viewers list"
            aria-label="Seen by {{ $viewsCount }}"
            data-story-viewers-open="{{ $story->id }}"
        >
            <i data-lucide="eye" aria-hidden="true"></i>
            <span data-story-views-count="{{ $story->id }}">Seen by {{ $viewsCount }}</span>
        </button>
    @endif
    <strong>{{ $author->name }}</strong>
</a>
