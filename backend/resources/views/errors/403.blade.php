@extends('member.layouts.app')

@section('title', '403 Forbidden - MLM Book')

@section('content')
<div class="notification-empty card" style="margin-top: 40px; padding: 40px; text-align: center;">
    <div class="notification-empty__icon" style="background: #fee2e2; color: #dc2626;">
        <i data-lucide="shield-alert" aria-hidden="true" style="width: 32px; height: 32px;"></i>
    </div>
    <h1 style="font-size: 28px; margin: 12px 0 6px; color: #111c35;">403 - Access Forbidden</h1>
    <p style="color: #667085; max-width: 480px; margin: 0 auto 20px;">
        You do not have permission to access this resource or perform this action.
    </p>
    <a href="{{ Route::has('member.dashboard') ? route('member.dashboard') : url('/') }}" class="member-button member-button--primary">
        <i data-lucide="house" aria-hidden="true"></i> Back to Dashboard
    </a>
</div>
@endsection
