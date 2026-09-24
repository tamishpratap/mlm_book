@extends('member.layouts.app')

@section('title', '419 Session Expired - MLM Book')

@section('content')
<div class="notification-empty card" style="margin-top: 40px; padding: 40px; text-align: center;">
    <div class="notification-empty__icon" style="background: #fef3c7; color: #d97706;">
        <i data-lucide="clock" aria-hidden="true" style="width: 32px; height: 32px;"></i>
    </div>
    <h1 style="font-size: 28px; margin: 12px 0 6px; color: #111c35;">419 - Session Expired</h1>
    <p style="color: #667085; max-width: 480px; margin: 0 auto 20px;">
        Your session has expired or the security token timed out. Please refresh and try again.
    </p>
    <a href="javascript:location.reload()" class="member-button member-button--primary">
        <i data-lucide="rotate-cw" aria-hidden="true"></i> Refresh Page
    </a>
</div>
@endsection
