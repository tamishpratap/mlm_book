@php
    $m = $membership->member;
    $hasPhoto = $m && $m->profile_photo
        && str_starts_with($m->profile_photo, 'uploads/profile/')
        && ! str_contains($m->profile_photo, '..')
        && file_exists(public_path($m->profile_photo));
    $initials = $m ? collect(preg_split('/\s+/', trim($m->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') : 'M';
    $currentMemberId = auth('member')->id();
    $isOwner = $community->isOwner($currentMemberId);
    $isAdmin = $community->isAdmin($currentMemberId);
    $canManageThisMember = ($isOwner && ! $membership->isOwner()) || ($isAdmin && ! $membership->isAdmin());
    $joinedDate = $membership->joined_at 
        ? \Carbon\Carbon::parse($membership->joined_at)->format('M Y') 
        : ($m && $m->created_at ? \Carbon\Carbon::parse($m->created_at)->format('M Y') : null);
@endphp

<div class="community-member-card" data-community-member-card="{{ $membership->id }}" style="display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 20px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03); width: 100%; box-sizing: border-box; margin-bottom: 16px;">
    <!-- LEFT: Avatar & Status -->
    <div class="community-member-card__avatar-wrap" style="position: relative; width: 60px; height: 60px; min-width: 60px; min-height: 60px; flex-shrink: 0; border-radius: 50%; overflow: visible;">
        @if ($hasPhoto)
            <img class="community-member-card__avatar" src="{{ asset($m->profile_photo) }}" alt="{{ $m->name }}" loading="lazy" style="width: 60px !important; height: 60px !important; min-width: 60px !important; max-width: 60px !important; min-height: 60px !important; max-height: 60px !important; border-radius: 50% !important; object-fit: cover !important; display: block !important; border: 2px solid #ffffff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1);">
        @else
            <div class="community-member-card__initials" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #176bff, #7146ed); color: #ffffff; font-size: 20px; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 2px solid #ffffff; box-shadow: 0 4px 12px rgba(23, 107, 255, 0.25);">{{ $initials }}</div>
        @endif
        <span class="community-member-card__status-dot" title="Active Member" style="position: absolute; bottom: 2px; right: 2px; width: 13px; height: 13px; border-radius: 50%; background: #10b981; border: 2.5px solid #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.15);"></span>
    </div>

    <!-- CENTER: Info, Handle, Role, Joined Date -->
    <div class="community-member-card__info" style="flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px;">
        <div class="community-member-card__name-row" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h3 class="community-member-card__name" style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.25;">{{ $m->name ?? 'Member' }}</h3>
            @if ($membership->role === 'owner')
                <span class="community-member-badge community-member-badge--owner" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; font-size: 11.5px; font-weight: 700; border-radius: 99px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.15)); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <i data-lucide="crown" aria-hidden="true" style="width: 13px; height: 13px;"></i> Owner
                </span>
            @elseif ($membership->role === 'admin')
                <span class="community-member-badge community-member-badge--admin" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; font-size: 11.5px; font-weight: 700; border-radius: 99px; background: linear-gradient(135deg, rgba(125, 66, 240, 0.15), rgba(99, 102, 241, 0.15)); color: #7d42f0; border: 1px solid rgba(125, 66, 240, 0.3);">
                    <i data-lucide="shield-check" aria-hidden="true" style="width: 13px; height: 13px;"></i> Admin
                </span>
            @elseif ($membership->role === 'moderator')
                <span class="community-member-badge community-member-badge--moderator" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; font-size: 11.5px; font-weight: 700; border-radius: 99px; background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(6, 182, 212, 0.15)); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.3);">
                    <i data-lucide="shield" aria-hidden="true" style="width: 13px; height: 13px;"></i> Moderator
                </span>
            @else
                <span class="community-member-badge community-member-badge--member" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; font-size: 11.5px; font-weight: 700; border-radius: 99px; background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;">
                    <i data-lucide="user" aria-hidden="true" style="width: 13px; height: 13px;"></i> Member
                </span>
            @endif
        </div>

        <div class="community-member-card__meta" style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; flex-wrap: wrap;">
            <span class="community-member-card__handle" style="color: #64748b; font-weight: 500;">{{ '@' . ($m->username ?? 'user' . $m->id) }}</span>
            @if ($joinedDate)
                <span class="community-member-card__meta-divider" style="color: #cbd5e1;">•</span>
                <span class="community-member-card__joined" style="display: inline-flex; align-items: center; gap: 4px; color: #94a3b8; font-size: 12.5px;">
                    <i data-lucide="calendar" aria-hidden="true" style="width: 13px; height: 13px;"></i> Joined {{ $joinedDate }}
                </span>
            @endif
        </div>
    </div>

    <!-- RIGHT: Actions -->
    <div class="community-member-card__actions" style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
        <a class="community-member-card__btn community-member-card__btn--profile" href="{{ route('member.people.show', $m) }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 42px; padding: 0 18px; font-size: 13px; font-weight: 600; border-radius: 12px; text-decoration: none; background: linear-gradient(135deg, #176bff 0%, #7146ed 100%); color: #ffffff !important; box-shadow: 0 4px 14px rgba(23, 107, 255, 0.25); white-space: nowrap;">
            <i data-lucide="user" aria-hidden="true" style="width: 15px; height: 15px;"></i> View Profile
        </a>

        @if ($canManageThisMember)
            <div class="community-member-card__manage-group" style="display: flex; align-items: center; gap: 8px;">
                @if ($isOwner)
                    @if ($membership->role === 'member')
                        <button
                            type="button"
                            class="community-member-card__btn community-member-card__btn--role"
                            title="Make Admin"
                            data-community-role-btn="{{ route('member.community.members.role', [$community, $membership]) }}"
                            data-role="admin"
                            style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; padding: 0 14px; font-size: 13px; font-weight: 600; border-radius: 12px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; cursor: pointer; white-space: nowrap;"
                        >
                            <i data-lucide="shield-plus" aria-hidden="true" style="width: 15px; height: 15px;"></i> + Admin
                        </button>
                    @elseif ($membership->role === 'admin')
                        <button
                            type="button"
                            class="community-member-card__btn community-member-card__btn--role"
                            title="Demote to Member"
                            data-community-role-btn="{{ route('member.community.members.role', [$community, $membership]) }}"
                            data-role="member"
                            style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; padding: 0 14px; font-size: 13px; font-weight: 600; border-radius: 12px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; cursor: pointer; white-space: nowrap;"
                        >
                            <i data-lucide="shield-minus" aria-hidden="true" style="width: 15px; height: 15px;"></i> - Admin
                        </button>
                    @endif
                @endif

                <button
                    type="button"
                    class="community-member-card__btn community-member-card__btn--remove"
                    title="Remove Member"
                    data-community-remove-btn="{{ route('member.community.members.remove', [$community, $membership]) }}"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; padding: 0 14px; font-size: 13px; font-weight: 600; border-radius: 12px; background: #fef2f2; color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); cursor: pointer; white-space: nowrap;"
                >
                    <i data-lucide="user-x" aria-hidden="true" style="width: 15px; height: 15px;"></i> Remove
                </button>
            </div>
        @endif
    </div>
</div>
