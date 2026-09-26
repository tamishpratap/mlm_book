@extends('member.layouts.app')

@section('title', '500 Server Error - MLM Book')

@section('content')
<div class="notification-empty card" style="margin-top: 40px; padding: 40px; text-align: center;">
    <div class="notification-empty__icon" style="background: #fee2e2; color: #dc2626;">
        <i data-lucide="server-off" aria-hidden="true" style="width: 32px; height: 32px;"></i>
    </div>
    <h1 style="font-size: 28px; margin: 12px 0 6px; color: #111c35;">500 - Server Error</h1>
    <p style="color: #667085; max-width: 480px; margin: 0 auto 20px;">
        An unexpected error occurred on our server. Our team has been notified.
    </p>
    <a href="{{ Route::has('member.dashboard') ? route('member.dashboard') : url('/') }}" class="member-button member-button--primary">
        <i data-lucide="house" aria-hidden="true"></i> Back to Dashboard
    </a>
</div>
@endsection
