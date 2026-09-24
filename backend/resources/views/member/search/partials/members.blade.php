<div class="member-search-list">
    @foreach ($resultMembers as $resultMember)
        @php
            $hasPhoto = $resultMember->profile_photo
                && str_starts_with($resultMember->profile_photo, 'uploads/profile/')
                && ! str_contains($resultMember->profile_photo, '..')
                && file_exists(public_path($resultMember->profile_photo));
            $photoUrl = $hasPhoto
                ? asset($resultMember->profile_photo).'?v='.($resultMember->updated_at?->timestamp ?? now()->timestamp)
                : null;
            $initials = collect(preg_split('/\s+/', trim($resultMember->name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                ->implode('') ?: 'M';
            $location = collect([$resultMember->city, $resultMember->country])->filter()->implode(', ');
            $friendship = $friendships->get($resultMember->id);
            $friendshipState = $friendship?->stateFor(auth('member')->id()) ?? 'none';
        @endphp

        <article class="member-result-card">
            @if ($photoUrl)
                <img class="member-result-card__avatar" src="{{ $photoUrl }}" alt="{{ $resultMember->name }}'s profile photo">
            @else
                <div class="member-result-card__avatar member-result-card__avatar--initials" role="img" aria-label="{{ $resultMember->name }} initials">
                    {{ $initials }}
                </div>
            @endif

            <div class="member-result-card__content">
                <div class="member-result-card__name-row">
                    <h3><a href="{{ route('member.people.show', $resultMember) }}">{{ $resultMember->name }}</a></h3>
                    <span>Member</span>
                </div>
                @if (filled($resultMember->user_id))
                    <div class="member-result-card__meta">
                        <span>{{ '@'.$resultMember->user_id }}</span>
                    </div>
                @endif
                <p class="member-result-card__bio">
                    {{ $resultMember->bio ? \Illuminate\Support\Str::limit($resultMember->bio, 140) : 'MLM Book Member' }}
                </p>
                <div class="member-result-card__meta">
                    @if ($location)
                        <span><i data-lucide="map-pin" aria-hidden="true"></i>{{ $location }}</span>
                    @endif
                    <span><i data-lucide="calendar-days" aria-hidden="true"></i>Joined {{ $resultMember->created_at->format('F Y') }}</span>
                </div>
            </div>

            <div class="member-result-card__actions">
                @include('member.friends.partials.actions', compact(
                    'resultMember',
                    'friendship',
                    'friendshipState',
                ) + ['targetMember' => $resultMember])
                <a class="friend-link" href="{{ route('member.people.show', $resultMember) }}">View Profile</a>
            </div>
        </article>
    @endforeach
</div>
