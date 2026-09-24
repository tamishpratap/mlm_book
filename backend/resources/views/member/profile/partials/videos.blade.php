<section class="member-card profile-section-card">
    <header class="member-card__header">
        <div>
            <h2><i data-lucide="video" aria-hidden="true"></i> Videos</h2>
            <p>{{ $videos->count() }} {{ \Illuminate\Support\Str::plural('video', $videos->count()) }} shared by {{ $member->name }}</p>
        </div>
    </header>

    @if ($videos->isEmpty())
        <div class="notification-empty" style="padding: 40px 20px;">
            <div class="notification-empty__icon">
                <i data-lucide="video-off" aria-hidden="true"></i>
            </div>
            <h2>No Videos Uploaded</h2>
            <p>Videos uploaded in timeline posts will appear here.</p>
            @if(auth('member')->check() && auth('member')->id() === $member->id)
                <a href="{{ route('member.dashboard') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                    <i data-lucide="video" aria-hidden="true"></i> Upload a Video
                </a>
            @endif
        </div>
    @else
        <div class="profile-videos-grid">
            @foreach ($videos as $post)
                <div class="profile-video-item">
                    <video controls muted playsinline preload="metadata" data-autoplay-video>
                        <source src="{{ asset($post->media_path) }}">
                    </video>
                    <div class="profile-video-item__meta">
                        <a href="{{ route('member.posts.show', $post) }}" class="profile-video-item__title">
                            {{ \Illuminate\Support\Str::limit($post->body, 60) ?: 'Video post' }}
                        </a>
                        <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
