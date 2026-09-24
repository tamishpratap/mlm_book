@extends('member.layouts.app')

@section('title', 'Manage Team - ' . $businessPage->page_name)

@section('content')
<div class="biz-page">
    <header class="biz-header">
        <div class="biz-header__info">
            <h1><i data-lucide="users-round" aria-hidden="true"></i> Team Management - {{ $businessPage->page_name }}</h1>
            <p>Assign enterprise roles, manage team permissions, and invite team members.</p>
        </div>
        <div class="biz-header__actions">
            <button type="button" class="member-button member-button--primary" onclick="openInviteModal()">
                <i data-lucide="user-plus" aria-hidden="true"></i> Invite Team Member
            </button>
            <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--secondary">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Profile
            </a>
        </div>
    </header>

    <!-- Metric Counters Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
        <div class="biz-info-card" style="padding: 16px; align-items: center; flex-direction: row; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(79, 125, 243, 0.1); color: #4f7df3; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="users" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <strong style="font-size: 20px; color: #1d2738; display: block;">{{ $activeMembers->count() + 1 }}</strong>
                <span style="font-size: 12.5px; color: #687386;">Total Team Members</span>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px; align-items: center; flex-direction: row; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(32, 200, 117, 0.1); color: #20c875; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="shield-check" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <strong style="font-size: 20px; color: #1d2738; display: block;">{{ $activeMembers->where('role', 'admin')->count() + 1 }}</strong>
                <span style="font-size: 12.5px; color: #687386;">Admins & Owners</span>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px; align-items: center; flex-direction: row; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(138, 43, 226, 0.1); color: #8a2be2; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="edit-3" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <strong style="font-size: 20px; color: #1d2738; display: block;">{{ $activeMembers->where('role', 'editor')->count() }}</strong>
                <span style="font-size: 12.5px; color: #687386;">Editors</span>
            </div>
        </div>

        <div class="biz-info-card" style="padding: 16px; align-items: center; flex-direction: row; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(247, 185, 64, 0.15); color: #b7791f; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="mail" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <strong style="font-size: 20px; color: #1d2738; display: block;" id="pendingCountBadge">{{ $pendingInvitations->count() }}</strong>
                <span style="font-size: 12.5px; color: #687386;">Pending Invites</span>
            </div>
        </div>
    </div>

    <!-- Active Team Members Card -->
    <div class="biz-info-card">
        <h3 class="biz-info-card__title">
            <i data-lucide="users" style="color: #4f7df3;"></i> Active Team Members
        </h3>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid #e7ecf4; font-size: 12px; color: #98a2b3; text-transform: uppercase;">
                        <th style="padding: 12px 8px;">Member</th>
                        <th style="padding: 12px 8px;">Role</th>
                        <th style="padding: 12px 8px;">Joined Date</th>
                        <th style="padding: 12px 8px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="activeTeamTableBody">
                    <!-- Page Owner Row -->
                    <tr style="border-bottom: 1px solid #f4f6fa;">
                        <td style="padding: 14px 8px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="{{ $owner->profile_photo ? asset($owner->profile_photo) : asset('member_assets/images/dashboard/image/profile.png') }}" alt="{{ $owner->name }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div>
                                    <strong style="font-size: 14px; color: #1d2738; display: block;">{{ $owner->name }}</strong>
                                    <span style="font-size: 12px; color: #98a2b3;">{{ '@' . ($owner->user_id ?? 'owner') }}</span>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 8px;">
                            <span class="biz-badge biz-badge--category" style="background: rgba(138, 43, 226, 0.1); color: #8a2be2; font-weight: 700;">
                                <i data-lucide="crown" style="width: 12px; height: 12px;"></i> Page Owner
                            </span>
                        </td>
                        <td style="padding: 14px 8px; font-size: 13px; color: #687386;">
                            {{ $businessPage->created_at->format('M d, Y') }}
                        </td>
                        <td style="padding: 14px 8px; text-align: right;">
                            <span style="font-size: 12px; color: #98a2b3; font-style: italic;">Primary Owner</span>
                        </td>
                    </tr>

                    <!-- Team Members Rows -->
                    @foreach ($activeMembers as $tm)
                        <tr id="teamRow-{{ $tm->id }}" style="border-bottom: 1px solid #f4f6fa;">
                            <td style="padding: 14px 8px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="{{ $tm->member->profile_photo ? asset($tm->member->profile_photo) : asset('member_assets/images/dashboard/image/profile.png') }}" alt="{{ $tm->member->name }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <div>
                                        <strong style="font-size: 14px; color: #1d2738; display: block;">{{ $tm->member->name }}</strong>
                                        <span style="font-size: 12px; color: #98a2b3;">{{ '@' . ($tm->member->user_id ?? 'member') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px 8px;">
                                <span class="biz-badge biz-badge--category" id="roleBadge-{{ $tm->id }}">
                                    {{ $tm->role_label }}
                                </span>
                            </td>
                            <td style="padding: 14px 8px; font-size: 13px; color: #687386;">
                                {{ $tm->joined_at ? $tm->joined_at->format('M d, Y') : $tm->created_at->format('M d, Y') }}
                            </td>
                            <td style="padding: 14px 8px; text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button type="button" class="member-button member-button--secondary" style="padding: 4px 10px; font-size: 12px;" onclick="openChangeRoleModal({{ $tm->id }}, '{{ $tm->role }}', '{{ $tm->member->name }}')">
                                        <i data-lucide="edit" style="width: 12px; height: 12px;"></i> Role
                                    </button>
                                    <button type="button" class="member-button member-button--danger" style="padding: 4px 10px; font-size: 12px;" onclick="removeTeamMember({{ $tm->id }}, '{{ $tm->member->name }}')">
                                        <i data-lucide="user-x" style="width: 12px; height: 12px;"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pending Invitations Card -->
    <div class="biz-info-card">
        <h3 class="biz-info-card__title">
            <i data-lucide="mail" style="color: #f7b940;"></i> Pending Invitations
        </h3>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid #e7ecf4; font-size: 12px; color: #98a2b3; text-transform: uppercase;">
                        <th style="padding: 12px 8px;">Invited Member</th>
                        <th style="padding: 12px 8px;">Assigned Role</th>
                        <th style="padding: 12px 8px;">Invited By</th>
                        <th style="padding: 12px 8px;">Sent Date</th>
                        <th style="padding: 12px 8px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="pendingInvitesTableBody">
                    @forelse ($pendingInvitations as $inv)
                        <tr id="inviteRow-{{ $inv->id }}" style="border-bottom: 1px solid #f4f6fa;">
                            <td style="padding: 12px 8px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="{{ $inv->invitee->profile_photo ? asset($inv->invitee->profile_photo) : asset('member_assets/images/dashboard/image/profile.png') }}" alt="{{ $inv->invitee->name }}" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover;">
                                    <strong style="font-size: 13.5px; color: #1d2738;">{{ $inv->invitee->name }}</strong>
                                </div>
                            </td>
                            <td style="padding: 12px 8px;">
                                <span class="biz-badge biz-badge--visibility">{{ $roles[$inv->role] ?? ucfirst($inv->role) }}</span>
                            </td>
                            <td style="padding: 12px 8px; font-size: 13px; color: #687386;">
                                {{ $inv->inviter->name ?? 'Admin' }}
                            </td>
                            <td style="padding: 12px 8px; font-size: 13px; color: #687386;">
                                {{ $inv->created_at->format('M d, Y') }}
                            </td>
                            <td style="padding: 12px 8px; text-align: right;">
                                <button type="button" class="member-button member-button--danger" style="padding: 4px 10px; font-size: 12px;" onclick="cancelInvitation({{ $inv->id }})">
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="noPendingRow">
                            <td colspan="5" style="text-align: center; padding: 24px; color: #98a2b3; font-style: italic;">
                                No pending invitations.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Invite Team Member Modal -->
<div class="biz-modal-overlay" id="bizInviteModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="inviteModalTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="inviteModalTitle">
                <i data-lucide="user-plus" style="color: #4f7df3;"></i> Invite Team Member
            </h3>
            <button type="button" class="icon-button" onclick="closeInviteModal()"><i data-lucide="x"></i></button>
        </div>

        <form id="bizInviteForm" method="POST" action="{{ route('member.business-pages.team.invite', $businessPage) }}">
            @csrf
            <div class="biz-modal__body">
                <!-- Select Member from Friends List -->
                @if ($friends->count() > 0)
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Select Friend</label>
                        <select name="invitee_id" id="inviteeSelect" class="biz-filter-select" style="width: 100%;" required>
                            <option value="">Choose a friend to invite...</option>
                            @foreach ($friends as $fr)
                                <option value="{{ $fr->id }}">{{ $fr->name }} (@ {{ $fr->user_id ?? 'user' }})</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Enter Member ID / Username</label>
                        <input type="text" id="inviteeSearchInput" class="biz-search-input" placeholder="Type member name or ID...">
                        <input type="hidden" name="invitee_id" id="inviteeSelectHidden">
                    </div>
                @endif

                <!-- Select Role -->
                <div class="form-group">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">Assign Role</label>
                    <select name="role" class="biz-filter-select" style="width: 100%;" required>
                        @if ($isOwner)
                            <option value="admin">Page Admin (Full management access except ownership transfer)</option>
                        @endif
                        <option value="editor" selected>Page Editor (Can publish posts, upload photos/videos)</option>
                        <option value="moderator">Page Moderator (Can manage comments & report issues)</option>
                        <option value="analyst">Page Analyst (Read-only insights & reports access)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeInviteModal()">Cancel</button>
                    <button type="submit" id="submitInviteBtn" class="member-button member-button--primary">
                        <i data-lucide="send"></i> Send Invitation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Change Role Modal -->
<div class="biz-modal-overlay" id="bizChangeRoleModal" hidden>
    <div class="biz-modal" role="dialog" aria-labelledby="changeRoleTitle" aria-modal="true">
        <div class="biz-modal__header">
            <h3 id="changeRoleTitle">
                <i data-lucide="shield" style="color: #4f7df3;"></i> Change Team Role
            </h3>
            <button type="button" class="icon-button" onclick="closeChangeRoleModal()"><i data-lucide="x"></i></button>
        </div>

        <form id="bizChangeRoleForm" method="POST">
            @csrf
            @method('PUT')
            <div class="biz-modal__body">
                <p style="margin: 0; font-size: 13.5px; color: #687386;" id="changeRoleMemberNameText">Changing role for team member...</p>

                <div class="form-group" style="margin-top: 10px;">
                    <label style="font-size: 13px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">New Role</label>
                    <select name="role" id="changeRoleSelect" class="biz-filter-select" style="width: 100%;" required>
                        @if ($isOwner)
                            <option value="admin">Page Admin</option>
                        @endif
                        <option value="editor">Page Editor</option>
                        <option value="moderator">Page Moderator</option>
                        <option value="analyst">Page Analyst</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="member-button member-button--secondary" onclick="closeChangeRoleModal()">Cancel</button>
                    <button type="submit" class="member-button member-button--primary">Save Role</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openInviteModal() {
    document.getElementById('bizInviteModal').removeAttribute('hidden');
}
function closeInviteModal() {
    document.getElementById('bizInviteModal').setAttribute('hidden', 'true');
}

function openChangeRoleModal(memberId, currentRole, name) {
    document.getElementById('changeRoleMemberNameText').innerText = 'Changing role for ' + name;
    document.getElementById('changeRoleSelect').value = currentRole;
    const form = document.getElementById('bizChangeRoleForm');
    form.action = '{{ url("member/business-pages/" . $businessPage->slug . "/team/members") }}/' + memberId + '/role';
    document.getElementById('bizChangeRoleModal').removeAttribute('hidden');
}
function closeChangeRoleModal() {
    document.getElementById('bizChangeRoleModal').setAttribute('hidden', 'true');
}

document.getElementById('bizInviteForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('submitInviteBtn');
    btn.disabled = true;
    
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            alert(data.message);
            closeInviteModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to send invitation.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        alert('An error occurred.');
    });
});

document.getElementById('bizChangeRoleForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeChangeRoleModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to update role.');
        }
    });
});

function cancelInvitation(invId) {
    if (!confirm('Are you sure you want to cancel this invitation?')) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/team/invitations") }}/' + invId;
    
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`inviteRow-${invId}`);
            if (row) row.remove();
        } else {
            alert(data.message);
        }
    });
}

function removeTeamMember(tmId, name) {
    if (!confirm(`Are you sure you want to remove ${name} from the business team?`)) return;
    const url = '{{ url("member/business-pages/" . $businessPage->slug . "/team/members") }}/' + tmId;
    
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById(`teamRow-${tmId}`);
            if (row) row.remove();
        } else {
            alert(data.message);
        }
    });
}
</script>
@endsection
