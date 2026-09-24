@extends('member.layouts.app')

@section('title', $member->name."'s Friends")

@section('content')
    <div class="friends-page">
        <header class="member-page-heading friend-page-heading">
            <div>
                <span class="friend-page-eyebrow">Public connections</span>
                <h1>{{ $member->name }}'s Friends</h1>
                <p>{{ $friends->total() }} accepted {{ \Illuminate\Support\Str::plural('friendship', $friends->total()) }}</p>
            </div>
            <a class="member-button member-button--secondary" href="{{ route('member.people.show', $member) }}">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                View Profile
            </a>
        </header>

        @if ($friends->isEmpty())
            <section class="member-card friend-empty">
                <span><i data-lucide="users-round" aria-hidden="true"></i></span>
                <h2>No accepted friends to show</h2>
            </section>
        @else
            <div class="friend-grid">
                @foreach ($friends as $friend)
                    @include('member.friends.partials.friend-card', ['friend' => $friend])
                @endforeach
            </div>
            {{ $friends->links('member.search.pagination') }}
        @endif
    </div>
@endsection
