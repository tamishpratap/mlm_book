<div class="post-feed" data-post-feed>
    @forelse ($posts as $post)
        @include('member.posts.partials.card', compact('post'))
    @empty
        <section class="card post-empty-state" data-post-empty>
            <div class="post-empty-state__icon">
                <i data-lucide="newspaper" aria-hidden="true"></i>
            </div>
            <h2>No Posts on Timeline</h2>
            <p>Posts created or shared by {{ $member->name }} will appear here.</p>
            @if(auth('member')->check() && auth('member')->id() === $member->id)
                <a href="{{ route('member.dashboard') }}" class="member-button member-button--primary" style="margin-top: 16px; display: inline-flex; align-items: center; gap: 8px;">
                    <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                    <span>Create a Post</span>
                </a>
            @endif
        </section>
    @endforelse
</div>

@if (method_exists($posts, 'hasPages') && $posts->hasPages())
    {{ $posts->links('member.search.pagination') }}
@endif
