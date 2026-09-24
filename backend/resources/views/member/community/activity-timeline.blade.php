@extends('member.layouts.app')

@section('title', $community->name . ' — Activity Timeline')

@section('content')
<div class="community-page">
    <section class="card fb-section-card">
        <header class="fb-section-card__header">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h2><i data-lucide="activity"></i> {{ $community->name }} Activity Timeline</h2>
                    <p>Chronological history of community events, posts, member joins, and governance updates.</p>
                </div>

                <a href="{{ route('member.community.show', $community) }}" class="member-button member-button--secondary" style="padding: 6px 12px; font-size: 12px;">
                    <i data-lucide="arrow-left"></i> Back to Community
                </a>
            </div>
        </header>

        <!-- Activity Filters -->
        <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--color-border-soft); padding-bottom: 12px; flex-wrap: wrap;">
            <a href="{{ route('member.community.activity', [$community, 'filter' => 'all']) }}" class="community-nav-tab {{ $filter === 'all' ? 'is-active' : '' }}" style="padding: 6px 12px; font-size: 12px;">All Activity</a>
            <a href="{{ route('member.community.activity', [$community, 'filter' => 'posts']) }}" class="community-nav-tab {{ $filter === 'posts' ? 'is-active' : '' }}" style="padding: 6px 12px; font-size: 12px;">Posts</a>
            <a href="{{ route('member.community.activity', [$community, 'filter' => 'members']) }}" class="community-nav-tab {{ $filter === 'members' ? 'is-active' : '' }}" style="padding: 6px 12px; font-size: 12px;">Members</a>
            <a href="{{ route('member.community.activity', [$community, 'filter' => 'announcements']) }}" class="community-nav-tab {{ $filter === 'announcements' ? 'is-active' : '' }}" style="padding: 6px 12px; font-size: 12px;">Announcements</a>
            <a href="{{ route('member.community.activity', [$community, 'filter' => 'moderation']) }}" class="community-nav-tab {{ $filter === 'moderation' ? 'is-active' : '' }}" style="padding: 6px 12px; font-size: 12px;">Moderation</a>
            <a href="{{ route('member.community.activity', [$community, 'filter' => 'settings']) }}" class="community-nav-tab {{ $filter === 'settings' ? 'is-active' : '' }}" style="padding: 6px 12px; font-size: 12px;">Settings</a>
        </div>

        <!-- Activity Feed List -->
        <div style="display: flex; flex-direction: column; gap: 14px; position: relative; padding-left: 20px;">
            <div style="position: absolute; top: 0; bottom: 0; left: 7px; width: 2px; background: var(--color-border-soft);"></div>

            @forelse ($activities as $act)
                @php
                    $actor = $act->actor;
                    $hasPhoto = $actor && $actor->profile_photo
                        && str_starts_with($actor->profile_photo, 'uploads/profile/')
                        && file_exists(public_path($actor->profile_photo));
                    $actorPhoto = $hasPhoto ? asset($actor->profile_photo) : null;
                @endphp

                <div class="card" style="padding: 14px 18px; border-radius: var(--radius-md); position: relative;">
                    <div style="position: absolute; top: 16px; left: -26px; width: 14px; height: 14px; border-radius: 50%; background: var(--color-primary); border: 3px solid #ffffff;"></div>

                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            @if ($actorPhoto)
                                <img src="{{ $actorPhoto }}" alt="{{ $actor->name }}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                            @endif
                            <div>
                                <strong style="font-size: 13.5px; color: var(--color-text-main);">{{ $actor->name ?? 'System' }}</strong>
                                <span class="community-badge" style="font-size: 11px; margin-left: 6px;">{{ $act->action }}</span>
                            </div>
                        </div>

                        <span style="font-size: 12px; color: var(--color-text-secondary);">{{ $act->created_at->diffForHumans() }}</span>
                    </div>

                    @if ($act->metadata)
                        <div style="margin-top: 8px; font-size: 12.5px; color: var(--color-text-secondary); background: var(--color-surface-alt); padding: 6px 10px; border-radius: var(--radius-sm);">
                            @if (!empty($act->metadata['reason']))
                                <div>Reason: {{ $act->metadata['reason'] }}</div>
                            @endif
                            @if (!empty($act->metadata['duration']))
                                <div>Duration: {{ $act->metadata['duration'] }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="fb-empty-state">
                    <div class="fb-empty-state__icon">
                        <i data-lucide="activity" aria-hidden="true"></i>
                    </div>
                    <h3>No Activity Recorded</h3>
                    <p>No activity timeline entries match this filter.</p>
                </div>
            @endforelse
        </div>

        @if ($activities->hasPages())
            <div class="pagination-wrapper" style="margin-top: 20px;">
                {{ $activities->links('member.search.pagination') }}
            </div>
        @endif
    </section>
</div>
@endsection
