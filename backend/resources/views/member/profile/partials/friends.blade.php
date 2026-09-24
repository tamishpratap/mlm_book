<section class="member-card profile-section-card" style="padding: 0; overflow: hidden;">
    <header class="member-card__header" style="padding: 20px 24px 16px; border-bottom: 1px solid var(--color-border-subtle, #f1f5f9); margin: 0;">
        <div>
            <h2><i data-lucide="users-round" aria-hidden="true"></i> Connections</h2>
            <p>{{ $friendsCount }} {{ \Illuminate\Support\Str::plural('connection', $friendsCount) }}</p>
        </div>
    </header>

    @if ($friendsList->isEmpty())
        <div class="notification-empty" style="padding: 40px 20px;">
            <div class="notification-empty__icon">
                <i data-lucide="user-x" aria-hidden="true"></i>
            </div>
            <h2>No Connections Yet</h2>
            <p>Accepted connections will appear here.</p>
            @if(auth('member')->check() && auth('member')->id() === $member->id)
                <a href="{{ route('member.people.suggestions') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                    <i data-lucide="user-plus" aria-hidden="true"></i> Find New Connections
                </a>
            @endif
        </div>
    @else
        <div class="connection-requests-list" style="border: none; border-radius: 0;">
            @foreach ($friendsList as $friend)
                @php
                    $cleanAvatar = $friend->profile_photo ? ltrim(str_replace('\\', '/', $friend->profile_photo), '/') : null;
                    $hasAvatar = $cleanAvatar && str_starts_with($cleanAvatar, 'uploads/profile/') && file_exists(public_path($cleanAvatar));
                    $avatarUrl = $hasAvatar ? asset($cleanAvatar) : null;
                    $initials = collect(preg_split('/\s+/', trim($friend->name)))
                        ->filter()->take(2)
                        ->map(fn($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->implode('') ?: 'M';
                    $location = collect([$friend->city, $friend->country])->filter()->implode(', ');
                @endphp
                <div class="connection-request-row compact-member-row compact-member-row--connection">
                    <div class="connection-request-row__main">
                        <a href="{{ route('member.people.show', $friend) }}" class="connection-request-row__avatar-link" aria-label="View {{ $friend->name }}'s profile">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $friend->name }}" class="connection-request-row__avatar-img" loading="lazy">
                            @else
                                <div class="connection-request-row__avatar-fallback">
                                    <span>{{ $initials }}</span>
                                </div>
                            @endif
                        </a>
                        <div class="connection-request-row__details">
                            <div class="connection-request-row__name-wrap">
                                <a href="{{ route('member.people.show', $friend) }}" class="connection-request-row__name-link" title="{{ $friend->name }}">
                                    <span class="connection-request-row__name">{{ $friend->name }}</span>
                                    @if (filled($friend->mobile_verified_at))
                                        <span class="verified-badge verified-badge--sm" title="Verified member" aria-label="Verified member">
                                            <i data-lucide="check" style="width: 10px; height: 10px;"></i>
                                        </span>
                                    @endif
                                </a>
                            </div>
                            @if (filled($friend->user_id))
                                <div class="connection-request-row__username-wrap">
                                    <a href="{{ route('member.people.show', $friend) }}" class="connection-request-row__username-link" title="{{ '@'.$friend->user_id }}">
                                        <span class="connection-request-row__username">{{ '@'.$friend->user_id }}</span>
                                    </a>
                                </div>
                            @endif
                            <div class="connection-request-row__meta">
                                @if ($location)
                                    <span class="connection-request-row__no-mutual-text" title="{{ $location }}">{{ $location }}</span>
                                @else
                                    <span class="connection-request-row__no-mutual-text">No mutual connections</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="connection-request-row__actions">
                        <a href="{{ route('member.people.show', $friend) }}" class="member-button member-button--secondary connection-request-btn">
                            <i data-lucide="user" aria-hidden="true"></i>
                            <span>View Profile</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
