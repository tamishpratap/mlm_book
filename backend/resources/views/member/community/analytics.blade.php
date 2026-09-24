@extends('member.layouts.app')

@section('title', $community->name . ' — Community Analytics & Insights')

@section('content')
<div class="community-page">
    <!-- Hero Analytics Header -->
    <div class="card" style="background: linear-gradient(135deg, rgba(79, 125, 243, 0.15), rgba(125, 66, 240, 0.15)); border: 1px solid var(--color-border-soft); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="community-badge" style="background: var(--color-primary); color: #ffffff; margin-bottom: 6px; display: inline-block;">
                    <i data-lucide="bar-chart-3" style="width: 12px; height: 12px; vertical-align: middle;"></i> Enterprise Analytics Platform
                </span>
                <h1 style="font-size: 24px; font-weight: 900; color: var(--color-text-main); margin: 0 0 4px 0;">
                    {{ $community->name }} Insights & Performance
                </h1>
                <p style="font-size: 13.5px; color: var(--color-text-secondary); margin: 0;">
                    Comprehensive growth metrics, member activity, post engagement, and health diagnostics.
                </p>
            </div>

            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('member.community.analytics', $community) }}" style="margin: 0;">
                    <select name="period" class="community-search-input" style="padding: 8px 14px; font-size: 13px; width: auto;" onchange="this.form.submit()">
                        <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>Last 90 Days</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                    </select>
                </form>

                <!-- Export CSV Button -->
                <a href="{{ route('member.community.analytics.export', $community) }}" class="member-button member-button--secondary" style="padding: 8px 16px; font-size: 13px;">
                    <i data-lucide="download" aria-hidden="true"></i> Export CSV
                </a>

                <a href="{{ route('member.community.show', $community) }}" class="member-button member-button--secondary" style="padding: 8px 14px; font-size: 13px;">
                    <i data-lucide="arrow-left" aria-hidden="true"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- Community Health Diagnostics Card -->
    <section class="card fb-section-card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 800; color: var(--color-text-main); margin: 0 0 4px 0;">
                    <i data-lucide="activity" style="color: var(--color-primary); width: 18px; height: 18px; vertical-align: middle;"></i> Community Health Index
                </h3>
                <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0;">
                    Composite health score evaluated against growth rates, member retention, post frequency, and moderation activity.
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="text-align: right;">
                    <span style="font-size: 28px; font-weight: 900; color: var(--color-primary);">{{ $healthScore }}/100</span>
                    <div style="font-size: 12px; font-weight: 700; color: #10b981;">Status: {{ $healthLabel }}</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 16px; width: 100%; height: 10px; background: var(--color-surface-alt); border-radius: 6px; overflow: hidden; position: relative;">
            <div style="height: 100%; width: {{ $healthScore }}%; background: linear-gradient(90deg, #4f7df3, #7d42f0); border-radius: 6px; transition: width 0.6s ease;"></div>
        </div>
    </section>

    <!-- Overview Statistics Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px;">
        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Total Members</div>
            <div style="font-size: 24px; font-weight: 900; color: var(--color-text-main); margin-top: 4px;">{{ number_format($totalMembers) }}</div>
            <span style="font-size: 11px; color: var(--color-primary);">+{{ number_format($newMembersInPeriod) }} in selected period</span>
        </div>

        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Active Members (30d)</div>
            <div style="font-size: 24px; font-weight: 900; color: #10b981; margin-top: 4px;">{{ number_format($activeMembers) }}</div>
            <span style="font-size: 11px; color: var(--color-text-secondary);">{{ round(($activeMembers / max(1, $totalMembers)) * 100) }}% retention rate</span>
        </div>

        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Total Posts</div>
            <div style="font-size: 24px; font-weight: 900; color: var(--color-text-main); margin-top: 4px;">{{ number_format($totalPosts) }}</div>
            <span style="font-size: 11px; color: var(--color-primary);">+{{ number_format($postsInPeriod) }} in selected period</span>
        </div>

        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Announcements</div>
            <div style="font-size: 24px; font-weight: 900; color: #7d42f0; margin-top: 4px;">{{ number_format($totalAnnouncements) }}</div>
            <span style="font-size: 11px; color: var(--color-text-secondary);">Official broadcasts</span>
        </div>

        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Pending Join Requests</div>
            <div style="font-size: 24px; font-weight: 900; color: #f59e0b; margin-top: 4px;">{{ number_format($pendingRequests) }}</div>
            <span style="font-size: 11px; color: var(--color-text-secondary);">Awaiting approval</span>
        </div>

        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Pending Moderation Reports</div>
            <div style="font-size: 24px; font-weight: 900; color: #ef4444; margin-top: 4px;">{{ number_format($pendingReports) }}</div>
            <span style="font-size: 11px; color: var(--color-text-secondary);">Member flags</span>
        </div>

        <div class="card" style="padding: 16px; border-radius: var(--radius-md);">
            <div style="font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Active Member Bans</div>
            <div style="font-size: 24px; font-weight: 900; color: #6b7280; margin-top: 4px;">{{ number_format($bannedMembers) }}</div>
            <span style="font-size: 11px; color: var(--color-text-secondary);">Enforced restrictions</span>
        </div>
    </div>

    <!-- 7-Day Growth & Post Creation Charts -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <section class="card fb-section-card">
            <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 16px 0; color: var(--color-text-main);">
                <i data-lucide="user-plus" style="width: 16px; height: 16px; color: var(--color-primary);"></i> 7-Day Member Growth Trend
            </h3>
            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; height: 140px; padding-top: 20px;">
                @php $maxJoins = max(1, max($dailyMemberJoins)); @endphp
                @foreach ($dailyLabels as $index => $label)
                    @php
                        $val = $dailyMemberJoins[$index];
                        $heightPct = round(($val / $maxJoins) * 100);
                    @endphp
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%;">
                        <span style="font-size: 10.5px; font-weight: 700; color: var(--color-text-main);">{{ $val }}</span>
                        <div style="flex: 1; width: 100%; max-width: 24px; background: var(--color-surface-alt); border-radius: 4px 4px 0 0; display: flex; align-items: flex-end; overflow: hidden;">
                            <div style="width: 100%; height: {{ max(6, $heightPct) }}%; background: var(--color-primary); border-radius: 4px 4px 0 0;"></div>
                        </div>
                        <span style="font-size: 10px; color: var(--color-text-secondary);">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card fb-section-card">
            <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 16px 0; color: var(--color-text-main);">
                <i data-lucide="message-square" style="width: 16px; height: 16px; color: #7d42f0;"></i> 7-Day Post Creation Trend
            </h3>
            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; height: 140px; padding-top: 20px;">
                @php $maxPosts = max(1, max($dailyPostCreations)); @endphp
                @foreach ($dailyLabels as $index => $label)
                    @php
                        $val = $dailyPostCreations[$index];
                        $heightPct = round(($val / $maxPosts) * 100);
                    @endphp
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%;">
                        <span style="font-size: 10.5px; font-weight: 700; color: var(--color-text-main);">{{ $val }}</span>
                        <div style="flex: 1; width: 100%; max-width: 24px; background: var(--color-surface-alt); border-radius: 4px 4px 0 0; display: flex; align-items: flex-end; overflow: hidden;">
                            <div style="width: 100%; height: {{ max(6, $heightPct) }}%; background: #7d42f0; border-radius: 4px 4px 0 0;"></div>
                        </div>
                        <span style="font-size: 10px; color: var(--color-text-secondary);">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <!-- Top Contributors & Top Engaged Posts Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
        <!-- Top Contributors -->
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <h2><i data-lucide="award" style="color: #f59e0b;"></i> Top Community Contributors</h2>
            </header>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse ($topContributors as $rank => $contrib)
                    @php
                        $hasPhoto = $contrib->profile_photo
                            && str_starts_with($contrib->profile_photo, 'uploads/profile/')
                            && file_exists(public_path($contrib->profile_photo));
                        $photoUrl = $hasPhoto ? asset($contrib->profile_photo) : null;
                    @endphp
                    <div class="card" style="padding: 10px 14px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 14px; font-weight: 900; color: var(--color-primary); min-width: 20px;">#{{ $rank + 1 }}</span>
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="{{ $contrib->name }}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                            @endif
                            <strong style="font-size: 13.5px; color: var(--color-text-main);">{{ $contrib->name }}</strong>
                        </div>
                        <span class="community-badge">{{ $contrib->posts_count }} {{ \Illuminate\Support\Str::plural('post', $contrib->posts_count) }}</span>
                    </div>
                @empty
                    <div style="font-size: 13px; color: var(--color-text-secondary); text-align: center; padding: 16px;">
                        No posts published yet to calculate top contributors.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Top Engaged Posts -->
        <section class="card fb-section-card">
            <header class="fb-section-card__header">
                <h2><i data-lucide="flame" style="color: #ef4444;"></i> Top Engaged Discussions</h2>
            </header>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse ($topPosts as $post)
                    <div class="card" style="padding: 12px 14px; border-radius: var(--radius-md);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <strong style="font-size: 13px; color: var(--color-text-main);">{{ $post->member->name ?? 'Member' }}</strong>
                            <span style="font-size: 11px; color: var(--color-text-secondary);">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin: 4px 0; line-height: 1.4;">
                            {{ \Illuminate\Support\Str::limit($post->body, 80, '...') }}
                        </p>
                        <div style="display: flex; gap: 12px; font-size: 11.5px; color: var(--color-primary); font-weight: 600; margin-top: 4px;">
                            <span><i data-lucide="thumbs-up" style="width: 12px; height: 12px; vertical-align: middle;"></i> {{ $post->likes_count }} likes</span>
                            <span><i data-lucide="message-square" style="width: 12px; height: 12px; vertical-align: middle;"></i> {{ $post->comments_count }} comments</span>
                        </div>
                    </div>
                @empty
                    <div style="font-size: 13px; color: var(--color-text-secondary); text-align: center; padding: 16px;">
                        No discussion posts available to calculate engagement metrics.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
