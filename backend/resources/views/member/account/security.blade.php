@extends('member.layouts.app')

@section('title', 'Password & Security')

@section('content')
    <header class="member-page-heading">
        <div>
            <h1>Password &amp; Security</h1>
            <p>Use a strong, unique password to protect your account.</p>
        </div>
        <a class="member-button member-button--secondary" href="{{ route('member.account.settings') }}">
            <i data-lucide="settings" aria-hidden="true"></i>
            Account Settings
        </a>
    </header>

    <div class="settings-shell">
        <section class="member-card settings-card">
            <header class="member-card__header">
                <div>
                    <h2>Change password</h2>
                    <p>Confirm your current password before choosing a new one.</p>
                </div>
            </header>

            @if ($member->google_id)
                <div class="security-note">
                    <i data-lucide="info" aria-hidden="true"></i>
                    <span>This account is connected to Google. Changing the password still requires the current account password.</span>
                </div>
            @endif

            <form class="member-form" method="POST" action="{{ route('member.account.password.update') }}">
                @csrf
                @method('PUT')

                <div class="form-field">
                    <label for="current_password">Current Password</label>
                    <div class="password-control">
                        <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" required autocomplete="current-password" @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-visibility="current_password">
                            <i class="eye" data-lucide="eye" aria-hidden="true"></i>
                            <i class="eye-off" data-lucide="eye-off" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('current_password')<p class="form-error" id="current-password-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="password">New Password</label>
                    <div class="password-control">
                        <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" minlength="8" required autocomplete="new-password" @error('password') aria-invalid="true" aria-describedby="new-password-error" @enderror>
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-visibility="password">
                            <i class="eye" data-lucide="eye" aria-hidden="true"></i>
                            <i class="eye-off" data-lucide="eye-off" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="form-help">Use at least 8 characters and choose a password different from your current one.</p>
                    @error('password')<p class="form-error" id="new-password-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="password-control">
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
                        <button class="password-toggle" type="button" aria-label="Show password" data-password-visibility="password_confirmation">
                            <i class="eye" data-lucide="eye" aria-hidden="true"></i>
                            <i class="eye-off" data-lucide="eye-off" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="member-button member-button--primary" type="submit">
                        <i data-lucide="shield-check" aria-hidden="true"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
