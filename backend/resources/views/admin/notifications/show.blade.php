@extends('admin.layouts.master')
@section('title', 'Notification Inspection: #' . Str::limit($notification->id, 8))
@section('page-subtitle', 'Read-only notification details, recipient info, and raw payload data.')

@section('content')
<div class="row g-4 mb-4">
    <!-- Left Column: Notification Overview -->
    <div class="col-lg-6">
        <x-admin.card title="Notification Details">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light-primary text-primary">{{ $notification->channel }}</span>
                    <span class="badge bg-light-info text-info">{{ $notification->type }}</span>
                </div>

                @if($notification->is_read)
                    <x-admin.badge variant="success" :light="true">Read</x-admin.badge>
                @else
                    <form action="{{ route('admin.notifications.mark-read', $notification->id) }}" method="POST">
                        @csrf
                        <x-admin.button type="submit" variant="success" icon="check">Mark Read</x-admin.button>
                    </form>
                @endif
            </div>

            <div class="p-3 bg-light rounded mb-4">
                <h6 class="fw-bold text-dark mb-1">{{ $notification->title }}</h6>
                <p class="text-muted small mb-0">{{ $notification->message }}</p>
                <div class="text-muted small mt-2" style="font-size: 11px;">
                    Sent Date: {{ $notification->created_at?->format('F d, Y \a\t H:i:s') }}
                    @if($notification->read_at)
                        <span class="ms-2">• Read At: {{ \Carbon\Carbon::parse($notification->read_at)->format('F d, Y \a\t H:i:s') }}</span>
                    @endif
                </div>
            </div>

            <!-- Recipient Information -->
            <h6 class="fw-bold text-primary mb-3">Recipient Member</h6>
            @if($notification->recipient)
                <div class="d-flex align-items-center gap-3 p-3 border rounded bg-white mb-4">
                    <img class="rounded-circle" src="{{ $notification->recipient->avatar_url }}" style="width: 48px; height: 48px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">{{ $notification->recipient->name }}</h6>
                        <code class="text-primary">{{ $notification->recipient->user_id }}</code>
                        <div class="text-muted small">{{ $notification->recipient->email }}</div>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('admin.members.show', $notification->recipient) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-user me-1"></i> View Profile
                        </a>
                    </div>
                </div>
            @else
                <p class="text-muted small mb-4">Recipient profile not found.</p>
            @endif

            <div class="pt-3 border-top">
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back to Queue
                </a>
            </div>
        </x-admin.card>
    </div>

    <!-- Right Column: JSON Payload Data -->
    <div class="col-lg-6">
        <x-admin.card title="JSON Data Payload">
            <pre class="bg-dark text-success p-3 rounded small overflow-auto" style="max-height: 350px;"><code>{{ json_encode($notification->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </x-admin.card>
    </div>
</div>
@endsection
