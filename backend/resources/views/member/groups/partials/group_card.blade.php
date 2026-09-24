@php
    $cover = $group->cover_photo ? asset($group->cover_photo) : asset('member_assets/images/default-cover.jpg');
    $logo = $group->logo ? asset($group->logo) : asset('member_assets/images/default-group.png');
    $isMember = $group->isMember(auth('member')->id());
    $isPending = $group->isPending(auth('member')->id());
@endphp

<div class="group-card card">
    <div class="group-card__cover">
        <img src="{{ $cover }}" alt="{{ $group->name }}" loading="lazy">
        <span class="group-card__privacy privacy-{{ $group->privacy }}">
            <i data-lucide="{{ $group->privacy === 'public' ? 'globe' : 'lock' }}" aria-hidden="true"></i>
            {{ ucfirst($group->privacy) }}
        </span>
    </div>

    <div class="group-card__body">
        <div class="group-card__logo">
            <img src="{{ $logo }}" alt="{{ $group->name }}">
        </div>
        <h3 class="group-card__title">
            <a href="{{ route('member.groups.show', $group) }}">{{ $group->name }}</a>
        </h3>
        <p class="group-card__category"><i data-lucide="tag" aria-hidden="true"></i> {{ $group->category }}</p>
        <p class="group-card__meta">{{ $group->members_count ?? $group->acceptedMembers()->count() }} Members</p>

        <div class="group-card__actions">
            @if ($isMember)
                <a href="{{ route('member.groups.show', $group) }}" class="member-button member-button--secondary">
                    <i data-lucide="check" aria-hidden="true"></i> Joined
                </a>
            @elseif ($isPending)
                <button class="member-button member-button--secondary" disabled>
                    <i data-lucide="clock" aria-hidden="true"></i> Pending
                </button>
            @else
                <button class="member-button member-button--primary"
                        type="button"
                        data-group-join-btn="{{ $group->id }}"
                        data-group-join-url="{{ route('member.groups.join', $group) }}">
                    <i data-lucide="user-plus" aria-hidden="true"></i> Join Community
                </button>
            @endif
        </div>
    </div>
</div>
