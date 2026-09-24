@extends('member.layouts.app')

@section('title', 'Profile Visitors')

@section('content')
<section class="card member-card" style="margin-bottom: 20px;">
    <header class="member-card__header">
        <div>
            <h1>Recent Profile Visitors</h1>
            <p>See members who recently viewed your profile timeline.</p>
        </div>
    </header>

    @if ($visitors->isEmpty())
        <div class="notification-empty">
            <div class="notification-empty__icon">
                <i data-lucide="eye" aria-hidden="true"></i>
            </div>
            <h2>No Visitors Yet</h2>
            <p>Members who view your profile will be listed here.</p>
        </div>
    @else
        <div class="activity-log-list">
            @foreach ($visitors as $visit)
                @php
                    $visitor = $visit->visitor;
                @endphp
                @if ($visitor)
                    <div class="activity-log-item">
                        <div class="friend-card__avatar" style="width: 44px; height: 44px;">
                            <img src="{{ $visitor->profile_photo ? asset($visitor->profile_photo) : asset('member_assets/images/default-avatar.png') }}" alt="{{ $visitor->name }}">
                        </div>
                        <div class="activity-log-item__content">
                            <a href="{{ route('member.people.show', $visitor) }}">
                                <strong>{{ $visitor->name }}</strong>
                            </a>
                            <p>Viewed your profile</p>
                            <time datetime="{{ $visit->visited_at->toIso8601String() }}">{{ $visit->visited_at->diffForHumans() }}</time>
                        </div>
                        <a class="soft-cta" href="{{ route('member.people.show', $visitor) }}">View Profile</a>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</section>

@if ($visitors->hasPages())
    {{ $visitors->links('member.search.pagination') }}
@endif
@endsection
