<div class="group-requests-tab card">
    <h2>Pending Join Requests ({{ $pendingRequests->count() }})</h2>

    @forelse ($pendingRequests as $req)
        <div class="member-card card" style="margin-bottom: 12px;">
            <img src="{{ $req->member->profile_photo ? asset($req->member->profile_photo) : asset('member_assets/images/default-avatar.png') }}" alt="{{ $req->member->name }}">
            <div class="member-card__info">
                <a href="{{ route('member.people.show', $req->member) }}"><strong>{{ $req->member->name }}</strong></a>
                <small>Requested {{ $req->created_at->diffForHumans() }}</small>
            </div>
            <div class="member-card__actions">
                <form method="POST" action="{{ route('member.groups.requests.respond', [$group, $req]) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="member-button member-button--primary">Approve</button>
                </form>
                <form method="POST" action="{{ route('member.groups.requests.respond', [$group, $req]) }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="member-button member-button--secondary">Reject</button>
                </form>
            </div>
        </div>
    @empty
        <p style="color: #667085; text-align: center; padding: 20px;">No pending join requests.</p>
    @endforelse
</div>
