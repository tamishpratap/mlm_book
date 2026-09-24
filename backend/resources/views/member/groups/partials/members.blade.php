<div class="group-members-tab card">
    <h2>Community Members ({{ $membersList->total() ?? 0 }})</h2>

    <div class="members-grid">
        @foreach ($membersList as $gm)
            <div class="member-card card">
                <img src="{{ $gm->member->profile_photo ? asset($gm->member->profile_photo) : asset('member_assets/images/default-avatar.png') }}" alt="{{ $gm->member->name }}">
                <div class="member-card__info">
                    <a href="{{ route('member.people.show', $gm->member) }}"><strong>{{ $gm->member->name }}</strong></a>
                    <span class="role-pill role-{{ $gm->role }}">{{ ucfirst($gm->role) }}</span>
                </div>

                @if ($group->isOwner(auth('member')->id()) && $gm->role !== 'owner')
                    <div class="member-card__actions">
                        <form method="POST" action="{{ route('member.groups.members.role', [$group, $gm]) }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="role" value="{{ $gm->role === 'admin' ? 'member' : 'admin' }}">
                            <button type="submit" class="member-button member-button--secondary">
                                {{ $gm->role === 'admin' ? 'Demote' : 'Promote to Admin' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('member.groups.members.remove', [$group, $gm]) }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="member-button member-button--secondary" onclick="return confirm('Remove member?')">
                                Remove
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($membersList->hasPages())
        {{ $membersList->links('member.search.pagination') }}
    @endif
</div>
