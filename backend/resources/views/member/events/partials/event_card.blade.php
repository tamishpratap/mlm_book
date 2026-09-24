@php
    $cover = $event->cover_photo ? asset($event->cover_photo) : asset('member_assets/images/default-event.jpg');
    $userResponse = $event->userResponse(auth('member')->id());
@endphp

<div class="event-card card" data-event-card="{{ $event->id }}">
    <div class="event-card__cover">
        <img src="{{ $cover }}" alt="{{ $event->title }}" loading="lazy">
        <span class="event-card__badge badge-{{ $event->event_type }}">
            <i data-lucide="{{ $event->event_type === 'online' ? 'video' : 'map-pin' }}" aria-hidden="true"></i>
            {{ ucfirst($event->event_type) }}
        </span>
    </div>

    <div class="event-card__body">
        <div class="event-card__date">
            <span class="event-card__month">{{ $event->start_date->format('M') }}</span>
            <span class="event-card__day">{{ $event->start_date->format('d') }}</span>
        </div>

        <div class="event-card__content">
            <h3 class="event-card__title">
                <a href="{{ route('member.events.show', $event) }}">{{ $event->title }}</a>
            </h3>
            <p class="event-card__meta">
                <span><i data-lucide="clock" aria-hidden="true"></i> {{ $event->start_time ? date('g:i A', strtotime($event->start_time)) : 'All Day' }}</span>
                <span><i data-lucide="users" aria-hidden="true"></i> {{ $event->guests_count }} Responded</span>
            </p>

            <div class="event-card__actions">
                <form method="POST" action="{{ route('member.events.respond', $event) }}" class="event-response-form" data-event-response-form>
                    @csrf
                    <input type="hidden" name="response" value="going">
                    <button class="member-button {{ $userResponse === 'going' ? 'member-button--primary' : 'member-button--secondary' }}" type="submit">
                        <i data-lucide="check" aria-hidden="true"></i> {{ $userResponse === 'going' ? 'Going' : 'Going?' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('member.events.respond', $event) }}" class="event-response-form" data-event-response-form>
                    @csrf
                    <input type="hidden" name="response" value="interested">
                    <button class="member-button {{ $userResponse === 'interested' ? 'member-button--primary' : 'member-button--secondary' }}" type="submit">
                        <i data-lucide="star" aria-hidden="true"></i> Interested
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
