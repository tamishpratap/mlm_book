@extends('member.layouts.app')

@section('title', '404 Page Not Found - MLM Book')

@section('content')
<div class="notification-empty card" style="margin-top: 40px; padding: 40px; text-align: center;">
    <div class="notification-empty__icon" style="background: #eff6ff; color: #176bff;">
        <i data-lucide="file-question" aria-hidden="true" style="width: 32px; height: 32px;"></i>
    </div>
    <h1 style="font-size: 28px; margin: 12px 0 6px; color: #111c35;">404 - Page Not Found</h1>
    <p style="color: #667085; max-width: 480px; margin: 0 auto 20px;">
        The page or resource you are looking for does not exist, has been removed, or has moved.
    </p>
    <a href="{{ route('member.dashboard') }}" class="member-button member-button--primary">
        <i data-lucide="house" aria-hidden="true"></i> Back to Dashboard
    </a>
</div>
@endsection
