@extends('member.layouts.app')

@section('title', 'Connections')

@section('content')
    <div class="friends-page">
        <header class="member-page-heading friend-page-heading">
            <div>
                <span class="friend-page-eyebrow">Your network</span>
                <h1>My Connections</h1>
                <p>{{ $friends->total() }} accepted {{ \Illuminate\Support\Str::plural('connection', $friends->total()) }}</p>
            </div>
            <a class="member-button member-button--secondary" href="{{ route('member.friend-requests.index') }}">
                <i data-lucide="user-round-check" aria-hidden="true"></i>
                Connection Requests
            </a>
        </header>

        @if ($friends->isEmpty())
            <section class="member-card friend-empty">
                <span><i data-lucide="users-round" aria-hidden="true"></i></span>
                <h2>No connections yet</h2>
                <p>Search for Members and send a connection request to start building your network.</p>
                <a class="member-button member-button--primary" href="{{ route('member.search', ['type' => 'members']) }}">Find People</a>
            </section>
        @else
            <div class="connection-requests-list">
                @foreach ($friends as $friend)
                    @include('member.friends.partials.compact-member-row', ['friend' => $friend, 'mode' => 'connection'])
                @endforeach
            </div>

            {{ $friends->links('member.search.pagination') }}
        @endif
    </div>
@endsection
