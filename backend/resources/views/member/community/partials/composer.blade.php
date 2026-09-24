@php
    $currentUser = auth('member')->user();
    $hasPhoto = $currentUser->profile_photo
        && str_starts_with($currentUser->profile_photo, 'uploads/profile/')
        && ! str_contains($currentUser->profile_photo, '..')
        && file_exists(public_path($currentUser->profile_photo));
    $avatarUrl = $hasPhoto ? asset($currentUser->profile_photo) : null;
    $initials = collect(preg_split('/\s+/', trim($currentUser->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';
@endphp

<form
    class="card composer community-composer"
    method="POST"
    action="{{ route('member.community.posts.store', $community) }}"
    enctype="multipart/form-data"
    data-community-post-composer
>
    @csrf
    <div class="composer__input-row">
        @if ($avatarUrl)
            <img class="avatar" src="{{ $avatarUrl }}" alt="{{ $currentUser->name }}">
        @else
            <span class="avatar post-avatar-initials" role="img">{{ $initials }}</span>
        @endif

        <textarea
            class="composer__prompt"
            name="body"
            maxlength="5000"
            rows="2"
            placeholder="What's on your mind, {{ $currentUser->name }}? Share with {{ $community->name }}..."
            aria-label="Community post text"
            required
        ></textarea>
    </div>

    <div class="composer__preview" data-post-preview hidden>
        <div class="composer__preview-media" data-post-preview-media></div>
        <button class="composer__preview-remove" type="button" aria-label="Remove media" data-post-media-remove>
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="composer__actions">
        <label class="composer__action" for="comm-post-media-{{ $community->id }}">
            <i class="action-icon action-icon--photo" data-lucide="image"></i>
            <span>Photo / Video</span>
            <input
                id="comm-post-media-{{ $community->id }}"
                name="media"
                type="file"
                accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime"
                data-community-post-media
            >
        </label>

        @if ($isAdmin)
            <label class="composer__action" style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <input type="checkbox" name="is_announcement" value="1" style="accent-color: var(--color-primary);">
                <i class="action-icon" data-lucide="megaphone" style="color: #7d42f0;"></i>
                <span style="font-size: 13px; font-weight: 600; color: #7d42f0;">Announcement</span>
            </label>
        @endif

        <button class="composer__submit" type="submit">
            <i data-lucide="send" aria-hidden="true"></i>
            <span>Post</span>
        </button>
    </div>
</form>
