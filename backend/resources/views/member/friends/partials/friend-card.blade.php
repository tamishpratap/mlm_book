@php
    $cleanProfile = $friend->profile_photo ? ltrim(str_replace('\\', '/', $friend->profile_photo), '/') : null;
    $hasPhoto = $cleanProfile
        && str_starts_with($cleanProfile, 'uploads/profile/')
        && ! str_contains($cleanProfile, '..')
        && file_exists(public_path($cleanProfile));
    $photoUrl = $hasPhoto
        ? asset($cleanProfile).'?v='.($friend->updated_at?->timestamp ?? now()->timestamp)
        : null;

    $cleanCover = $friend->cover_photo ? ltrim(str_replace('\\', '/', $friend->cover_photo), '/') : null;
    $hasCoverPhoto = $cleanCover
        && str_starts_with($cleanCover, 'uploads/cover/')
        && ! str_contains($cleanCover, '..')
        && file_exists(public_path($cleanCover));
    $coverPhotoUrl = $hasCoverPhoto
        ? asset($cleanCover).'?v='.($friend->updated_at?->timestamp ?? now()->timestamp)
        : null;

    $initials = collect(preg_split('/\s+/', trim($friend->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'M';

    $location = collect([$friend->city, $friend->country])->filter()->implode(', ');
    $compact = $compact ?? false;
@endphp

<article class="friend-card {{ $compact ? 'friend-card--compact' : '' }}">
    <!-- SECTION 1: Fixed Cover Image / Placeholder -->
    <div class="friend-card__cover">
        @if ($coverPhotoUrl)
            <img src="{{ $coverPhotoUrl }}" alt="{{ $friend->name }}'s cover photo" loading="lazy">
        @else
            <div class="friend-card__cover-placeholder" aria-hidden="true"></div>
        @endif
    </div>

    <!-- CARD CONTENT -->
    <div class="friend-card__content">
        <!-- SECTION 2: Circular Overlapping Profile Avatar -->
        <a class="friend-card__avatar-wrap" href="{{ route('member.people.show', $friend) }}" aria-label="View {{ $friend->name }}'s profile">
            @if ($photoUrl)
                <img class="friend-card__avatar-img" src="{{ $photoUrl }}" alt="{{ $friend->name }}'s profile photo" loading="lazy">
            @else
                <span class="avatar friend-avatar-initials" role="img" aria-label="{{ $friend->name }} initials">{{ $initials }}</span>
            @endif
        </a>

        <!-- SECTION 3: Centered Member Information -->
        <div class="friend-card__details">
            <h3 class="friend-card__name">
                <a href="{{ route('member.people.show', $friend) }}">{{ $friend->name }}</a>
            </h3>

            <span class="friend-card__username">{{ filled($friend->user_id) ? '@'.$friend->user_id : '@member' }}</span>

            <p class="friend-card__bio">{{ $friend->bio ? \Illuminate\Support\Str::limit($friend->bio, 80) : 'MLM Book Member' }}</p>

            @if (isset($mutualCount) && $mutualCount > 0)
                <div class="friend-card__location">
                    <i data-lucide="users" aria-hidden="true"></i>
                    <span>{{ $mutualCount }} mutual {{ \Illuminate\Support\Str::plural('connection', $mutualCount) }}</span>
                </div>
            @else
                <div class="friend-card__location {{ $location ? '' : 'is-empty' }}">
                    <i data-lucide="map-pin" aria-hidden="true"></i>
                    <span>{{ $location ?: 'Location not set' }}</span>
                </div>
            @endif
        </div>

        <!-- SECTION 4: Action Button -->
        <div class="friend-card__actions">
            @if (isset($friendshipState))
                @include('member.friends.partials.actions', [
                    'targetMember' => $friend,
                    'friendship' => $friendship ?? null,
                    'friendshipState' => $friendshipState,
                ])
                <a class="member-button member-button--secondary friend-card__btn-view" href="{{ route('member.people.show', $friend) }}">
                    <i data-lucide="user" aria-hidden="true"></i>
                    <span>View Profile</span>
                </a>
            @elseif ($showAddFriend ?? false)
                @include('member.friends.partials.actions', [
                    'targetMember' => $friend,
                    'friendship' => null,
                    'friendshipState' => 'none',
                ])
                <a class="member-button member-button--secondary friend-card__btn-view" href="{{ route('member.people.show', $friend) }}">
                    <i data-lucide="user" aria-hidden="true"></i>
                    <span>View Profile</span>
                </a>
            @else
                <a class="member-button member-button--primary friend-card__btn-view" href="{{ route('member.people.show', $friend) }}">
                    <i data-lucide="user" aria-hidden="true"></i>
                    <span>View Profile</span>
                </a>
            @endif
        </div>
    </div>
</article>

