@php
    $hasLogo = $community->logo && file_exists(public_path($community->logo));
    $hasCover = $community->cover_photo && file_exists(public_path($community->cover_photo));
    $initials = collect(preg_split('/\s+/', trim($community->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'C';
    $currentMemberId = auth('member')->id();
    $isMember = $community->isMember($currentMemberId);
    $isTrending = ($community->member_count + $community->post_count) >= 3;
@endphp

<article class="community-card">
    <div class="community-card__cover">
        @if ($hasCover)
            <img src="{{ asset($community->cover_photo) }}" alt="{{ $community->name }} cover" loading="lazy">
        @endif
        @if ($community->is_featured)
            <span class="community-badge" style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; font-weight: 700; font-size: 10.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                <i data-lucide="sparkles" style="width: 11px; height: 11px; vertical-align: middle;"></i> Featured
            </span>
        @elseif ($isTrending)
            <span class="community-badge" style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #ffffff; font-weight: 700; font-size: 10.5px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                <i data-lucide="trending-up" style="width: 11px; height: 11px; vertical-align: middle;"></i> Trending
            </span>
        @endif
    </div>

    <div class="community-card__body">
        <div class="community-card__avatar">
            @if ($hasLogo)
                <img src="{{ asset($community->logo) }}" alt="{{ $community->name }} logo" loading="lazy">
            @else
                <div class="community-avatar-initials"><span>{{ $initials }}</span></div>
            @endif
        </div>

        <div style="display: flex; flex-direction: column; gap: 4px;">
            <h3 class="community-card__title">
                <a href="{{ route('member.community.show', $community) }}" title="{{ $community->name }}">
                    {{ $community->name }}
                </a>
            </h3>

            <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-bottom: 6px;">
                <span class="community-badge community-badge--category">{{ $community->category }}</span>
                <span class="community-badge community-badge--visibility">
                    @if ($community->visibility === 'private')
                        <i data-lucide="lock" style="width: 10px; height: 10px;" aria-hidden="true"></i> Private
                    @else
                        <i data-lucide="globe" style="width: 10px; height: 10px;" aria-hidden="true"></i> Public
                    @endif
                </span>
            </div>
        </div>

        <p class="community-card__description">
            {{ \Illuminate\Support\Str::limit($community->description, 110, '...') ?: 'Connect, share, and collaborate with members in this community.' }}
        </p>

        <div class="community-card__footer">
            <div class="community-card__meta">
                <span>
                    <i data-lucide="users" style="width: 13px; height: 13px; vertical-align: middle;" aria-hidden="true"></i>
                    {{ number_format($community->member_count) }} {{ \Illuminate\Support\Str::plural('member', $community->member_count) }}
                </span>
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">
                    by <strong>{{ $community->owner->name ?? 'Member' }}</strong>
                </span>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-top: 8px; width: 100%;">
                @include('member.community.partials.join-button', ['community' => $community])
                <a href="{{ route('member.community.show', $community) }}" class="member-button member-button--secondary" style="padding: 6px 12px; font-size: 12.5px; white-space: nowrap;">
                    <span>Open Community</span>
                    <i data-lucide="arrow-right" style="width: 13px; height: 13px; margin-left: 2px;" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</article>
