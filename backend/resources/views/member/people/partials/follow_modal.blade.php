<div class="follow-modal-header">
    <h2>{{ $title }}</h2>
</div>
<div class="friend-preview-grid">
    @forelse ($members as $mem)
        <div class="friend-card card">
            <a href="{{ route('member.people.show', $mem) }}" class="friend-card__avatar">
                <img src="{{ $mem->profile_photo ? asset($mem->profile_photo) : asset('member_assets/images/default-avatar.png') }}" alt="{{ $mem->name }}">
            </a>
            <div class="friend-card__info">
                <a href="{{ route('member.people.show', $mem) }}">
                    <strong>{{ $mem->name }}</strong>
                </a>
                <small>{{ '@'.($mem->user_id ?? $mem->id) }}</small>
            </div>
            @if (auth('member')->id() !== $mem->id)
                <button class="member-button member-button--secondary" type="button" data-follow-btn="{{ $mem->id }}" data-follow-url="{{ route('member.people.follow', $mem) }}">
                    {{ auth('member')->user()->isFollowing($mem->id) ? 'Following' : 'Follow' }}
                </button>
            @endif
        </div>
    @empty
        <div class="notification-empty" style="padding: 20px;">
            <p>No users found.</p>
        </div>
    @endforelse
</div>
