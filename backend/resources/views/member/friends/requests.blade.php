@extends('member.layouts.app')

@section('title', 'Connection Requests')

@section('content')
    <div class="friends-page">
        <header class="member-page-heading friend-page-heading">
            <div>
                <span class="friend-page-eyebrow">Connections</span>
                <h1>Connection Requests</h1>
                <p>Review incoming requests and your pending sent requests.</p>
            </div>
            <a class="member-button member-button--secondary" href="{{ route('member.friends.index') }}">
                <i data-lucide="users-round" aria-hidden="true"></i>
                My Connections
            </a>
        </header>

        <section class="card connection-requests-card friend-request-section">
            <header class="friend-section-heading" style="margin-bottom: 16px;">
                <div>
                    <h2>Incoming Requests</h2>
                    <p><span data-incoming-count>{{ $incomingRequests->count() }}</span> waiting</p>
                </div>
            </header>

            <div class="connection-requests-list" data-incoming-request-list>
                @forelse ($incomingRequests as $requestItem)
                    @php
                        $sender = $requestItem->otherMember($currentMember->id);
                        $friendship = $requestItem;
                        $friendshipState = 'pending_received';
                    @endphp
                    <div data-incoming-request-card>
                        @include('member.friends.partials.compact-request-row', [
                            'friend' => $sender,
                            'friendship' => $friendship,
                            'friendshipState' => $friendshipState,
                        ])
                    </div>
                @empty
                    <div class="friend-empty friend-empty--compact" data-incoming-empty style="padding: 24px; text-align: center;">
                        <p>No new connection requests</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="card connection-requests-card friend-request-section" style="margin-top: 24px;">
            <header class="friend-section-heading" style="margin-bottom: 16px;">
                <div>
                    <h2>Sent Requests</h2>
                    <p><span data-outgoing-count>{{ $outgoingRequests->count() }}</span> pending</p>
                </div>
            </header>

            <div class="connection-requests-list" data-outgoing-request-list>
                @forelse ($outgoingRequests as $requestItem)
                    @php
                        $receiver = $requestItem->otherMember($currentMember->id);
                    @endphp
                    <div data-outgoing-request-card>
                        @include('member.friends.partials.compact-request-row', [
                            'friend' => $receiver,
                            'friendship' => $requestItem,
                            'friendshipState' => 'pending_sent',
                        ])
                    </div>
                @empty
                    <div class="friend-empty friend-empty--compact" data-outgoing-empty style="padding: 24px; text-align: center;">
                        <p>No pending sent connection requests</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
