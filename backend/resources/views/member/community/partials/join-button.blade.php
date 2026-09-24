@php
    $currentMemberId = auth('member')->id();
    $isOwner = $community->isOwner($currentMemberId);
    $isMember = $community->isMember($currentMemberId);
    $isPending = $community->isPending($currentMemberId);
    $role = $community->memberRole($currentMemberId);
@endphp

<div class="community-join-action-area" data-community-action-area="{{ $community->slug }}">
    @if ($isOwner)
        <span class="community-badge community-badge--category" style="background: var(--color-primary-soft); color: var(--color-primary); padding: 8px 16px; font-size: 13px;">
            <i data-lucide="crown" style="width: 15px; height: 15px;"></i> Owner
        </span>
    @elseif ($isMember)
        <div style="display: inline-flex; gap: 8px; align-items: center;">
            <span class="community-badge community-badge--category" style="background: rgba(34, 197, 94, 0.12); color: #16a34a; padding: 8px 14px; font-size: 13px;">
                <i data-lucide="check-circle-2" style="width: 15px; height: 15px;"></i> Joined
                @if ($role && $role !== 'member')
                    ({{ ucfirst($role) }})
                @endif
            </span>
            <button
                type="button"
                class="member-button member-button--secondary"
                style="padding: 8px 14px; font-size: 12.5px;"
                data-community-leave-btn="{{ route('member.community.leave', $community) }}"
            >
                <i data-lucide="log-out" style="width: 14px; height: 14px;"></i> Leave
            </button>
        </div>
    @elseif ($isPending)
        <button
            type="button"
            class="member-button member-button--secondary"
            style="cursor: default; opacity: 0.85;"
            disabled
        >
            <i data-lucide="clock" style="width: 15px; height: 15px;"></i> Requested
        </button>
    @else
        <button
            type="button"
            class="member-button member-button--primary"
            data-community-join-btn="{{ route('member.community.join', $community) }}"
        >
            <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
            <span>{{ $community->isPublic() ? 'Join Community' : 'Request to Join' }}</span>
        </button>
    @endif
</div>
