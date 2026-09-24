<section class="member-card profile-section-card" style="margin-bottom: 18px;">
    <header class="member-card__header" style="margin-bottom: 0;">
        <div>
            <h2><i data-lucide="bookmark" aria-hidden="true"></i> Saved Posts</h2>
            <p>Your bookmarked posts and articles saved for later viewing.</p>
        </div>
    </header>
</section>

<div class="post-feed" data-post-feed>
    @forelse ($savedPosts as $post)
        @include('member.posts.partials.card', compact('post'))
    @empty
        <div class="notification-empty" style="padding: 40px 20px;">
            <div class="notification-empty__icon">
                <i data-lucide="bookmark-check" aria-hidden="true"></i>
            </div>
            <h2>No Saved Posts Yet</h2>
            <p>Bookmark posts from your feed to view them here anytime.</p>
            <a href="{{ route('member.dashboard') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                <i data-lucide="compass" aria-hidden="true"></i> Explore Feed
            </a>
        </div>
    @endforelse
</div>

@if (method_exists($savedPosts, 'hasPages') && $savedPosts->hasPages())
    {{ $savedPosts->links('member.search.pagination') }}
@endif
