<section class="member-card profile-section-card">
    @php
        $isProfileOwner = auth('member')->id() === $member->id;
    @endphp

    <header class="member-card__header">
        <div>
            <h2><i data-lucide="circle-play" aria-hidden="true"></i> Stories</h2>
            <p>{{ $stories->count() }} {{ \Illuminate\Support\Str::plural('story', $stories->count()) }} shared by {{ $member->name }}</p>
        </div>
    </header>

    @if ($stories->isEmpty())
        <div class="notification-empty" style="padding: 40px 20px;">
            <div class="notification-empty__icon">
                <i data-lucide="circle-off" aria-hidden="true"></i>
            </div>
            <h2>No Stories Shared</h2>
            <p>Uploaded stories will appear here.</p>
            @if($isProfileOwner)
                <a href="{{ route('member.stories.index') }}" class="member-button member-button--primary" style="margin-top: 16px;">
                    <i data-lucide="plus-circle" aria-hidden="true"></i> Create a Story
                </a>
            @endif
        </div>
    @else
        <div class="profile-stories-grid">
            @foreach ($stories as $story)
                @php
                    $isExpired = $story->expires_at && $story->expires_at->isPast();
                @endphp
                <a class="profile-story-card @if($isExpired) is-archived is-expired @endif" href="{{ route('member.stories.show', $story) }}">
                    @if ($story->isImage())
                        <img src="{{ asset($story->media_path) }}" alt="Story thumbnail" loading="lazy">
                    @else
                        <video preload="metadata"><source src="{{ asset($story->media_path) }}"></video>
                    @endif
                    <div class="profile-story-card__overlay">
                        <span class="profile-story-card__status @if($isExpired) profile-story-card__status--expired @else profile-story-card__status--active @endif">{{ $isExpired ? 'Expired' : 'Active' }}</span>
                        @if ($isProfileOwner)
                            <div class="story-card-item__stats profile-story-card__stats">
                                <span><i data-lucide="eye" aria-hidden="true"></i> {{ $story->views_count }}</span>
                                <span><i data-lucide="heart" aria-hidden="true"></i> {{ $story->likes_count }}</span>
                                <span><i data-lucide="message-circle" aria-hidden="true"></i> {{ $story->replies_count }}</span>
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
