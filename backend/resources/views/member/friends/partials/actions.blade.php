<div
    class="friend-actions friend-actions--{{ str_replace('_', '-', $friendshipState) }}"
    data-friend-actions
    data-friend-member-id="{{ $targetMember->id }}"
>
    @if ($friendshipState === 'none')
        <form class="friendship-action-form" method="POST" action="{{ route('member.friends.request', $targetMember) }}" data-loading-text="Sending…">
            @csrf
            <button class="member-button member-button--primary friend-action-button" type="submit">
                <i data-lucide="user-plus" aria-hidden="true"></i>
                <span>Connect</span>
            </button>
        </form>
    @elseif ($friendshipState === 'pending_sent' && $friendship)
        <form class="friendship-action-form" method="POST" action="{{ route('member.friend-requests.cancel', $friendship) }}" data-loading-text="Cancelling…" data-friend-resolution="true">
            @csrf
            @method('DELETE')
            <button class="member-button member-button--secondary friend-action-button" type="submit">
                <i data-lucide="user-minus" aria-hidden="true"></i>
                <span>Cancel Connection Request</span>
            </button>
        </form>
    @elseif ($friendshipState === 'pending_received' && $friendship)
        <form class="friendship-action-form" method="POST" action="{{ route('member.friend-requests.accept', $friendship) }}" data-loading-text="Accepting…" data-friend-resolution="true">
            @csrf
            <button class="member-button member-button--primary friend-action-button" type="submit">
                <i data-lucide="user-check" aria-hidden="true"></i>
                <span>Accept Connection</span>
            </button>
        </form>
        <form class="friendship-action-form" method="POST" action="{{ route('member.friend-requests.reject', $friendship) }}" data-loading-text="Rejecting…" data-friend-resolution="true">
            @csrf
            <button class="member-button member-button--danger friend-action-button" type="submit">
                <i data-lucide="user-x" aria-hidden="true"></i>
                <span>Reject Connection</span>
            </button>
        </form>
    @elseif ($friendshipState === 'friends')
        <button class="member-button friend-action-button friend-action-button--friends" type="button" disabled>
            <i data-lucide="users-round" aria-hidden="true"></i>
            <span>Connected</span>
        </button>
    @endif
</div>
