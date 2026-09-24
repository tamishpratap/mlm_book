<section class="member-card profile-section-card">
    <header class="member-card__header">
        <div>
            <h2><i data-lucide="image" aria-hidden="true"></i> Photos</h2>
            <p>{{ $photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $photos->count()) }} shared by {{ $member->name }}</p>
        </div>
    </header>

    @if ($photos->isEmpty())
        <div class="notification-empty" style="padding: 40px 20px;">
            <div class="notification-empty__icon">
                <i data-lucide="image-off" aria-hidden="true"></i>
            </div>
            <h2>No Photos Uploaded</h2>
            <p>Photos shared in timeline posts will appear here.</p>
            @if(auth('member')->check() && auth('member')->id() === $member->id)
                <a href="{{ route('member.dashboard') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                    <i data-lucide="camera" aria-hidden="true"></i> Share a Photo
                </a>
            @endif
        </div>
    @else
        <div class="profile-photos-grid">
            @foreach ($photos as $post)
                <a class="profile-photo-item" href="{{ route('member.posts.show', $post) }}">
                    <img src="{{ asset($post->media_path) }}" alt="Photo shared by {{ $member->name }}" loading="lazy">
                    <div class="profile-photo-overlay">
                        <span><i data-lucide="heart" aria-hidden="true"></i> {{ $post->reactions_count ?? $post->likes_count ?? 0 }}</span>
                        <span><i data-lucide="message-circle" aria-hidden="true"></i> {{ $post->comments_count ?? 0 }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
