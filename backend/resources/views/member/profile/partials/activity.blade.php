<section class="member-card profile-activity-card">
    <header class="member-card__header">
        <div>
            <h2><i data-lucide="activity" aria-hidden="true"></i> Recent Activity</h2>
            <p>Chronological activity log for {{ $member->name }}</p>
        </div>
    </header>

    @if ($posts->isEmpty())
        <div class="notification-empty" style="padding: 40px 20px;">
            <div class="notification-empty__icon">
                <i data-lucide="activity" aria-hidden="true"></i>
            </div>
            <h2>No Recent Activity</h2>
            <p>Activity history will appear here once {{ $member->name }} creates posts or interacts with the platform.</p>
            @if (auth('member')->check() && auth('member')->id() === $member->id)
                <a href="{{ route('member.dashboard') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                    <i data-lucide="plus-circle" aria-hidden="true"></i> Create a Post
                </a>
            @endif
        </div>
    @else
        <div class="profile-activity-list">
            @foreach ($posts as $post)
                @php
                    $isShared = $post->isShared();
                    $hasMedia = $post->hasImage() || $post->hasVideo();
                    $icon = $isShared ? 'share-2' : ($hasMedia ? 'image' : 'file-text');
                    $title = $isShared ? 'Shared a post' : ($hasMedia ? 'Published media' : 'Created a new post');
                    $previewText = $post->body ? \Illuminate\Support\Str::limit($post->body, 100) : ($hasMedia ? 'Shared photo/video content' : 'Post content');
                @endphp
                <div class="profile-activity-item">
                    <span class="profile-activity-item__icon">
                        <i data-lucide="{{ $icon }}" aria-hidden="true"></i>
                    </span>
                    <div class="profile-activity-item__content">
                        <div class="profile-activity-item__header">
                            <strong>{{ $title }}</strong>
                            <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                        </div>
                        <p>{{ $previewText }}</p>
                    </div>
                    <a class="member-button member-button--secondary" href="{{ route('member.posts.show', $post) }}">
                        <span>View Post</span>
                        <i data-lucide="chevron-right" aria-hidden="true"></i>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</section>
