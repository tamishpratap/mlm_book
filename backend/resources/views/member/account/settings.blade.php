@extends('member.layouts.app')

@section('title', 'Account Settings')

@section('content')
    <header class="member-page-heading">
        <div>
            <h1>Account Settings</h1>
            <p>Securely manage your verified mobile number and sign-in email.</p>
        </div>
        <a class="member-button member-button--secondary" href="{{ route('member.profile.show') }}">
            <i data-lucide="user-round" aria-hidden="true"></i>
            View Profile
        </a>
    </header>

    <div class="verification-grid">
        <section class="member-card verification-card">
            <header class="member-card__header verification-card__header">
                <div class="verification-card__title">
                    <span class="verification-card__icon"><i data-lucide="smartphone" aria-hidden="true"></i></span>
                    <div>
                        <h2>Mobile verification</h2>
                        <p>Codes are delivered by SMS and remain valid for 10 minutes.</p>
                    </div>
                </div>
                @if ($member->phone && $member->mobile_verified_at)
                    <span class="status-pill">Verified</span>
                @else
                    <span class="status-pill status-pill--warning">Unverified</span>
                @endif
            </header>

            <div class="verification-current">
                <span>Current mobile</span>
                <strong>{{ $member->phone ?: 'Not added yet' }}</strong>
                @if ($member->mobile_verified_at)
                    <small>Verified {{ $member->mobile_verified_at->diffForHumans() }}</small>
                @endif
            </div>

            <form class="member-form" method="POST" action="{{ route('member.account.mobile.send-otp') }}">
                @csrf
                <div class="form-field">
                    <label for="mobile_number">{{ $member->phone ? 'New mobile number' : 'Mobile number' }}</label>
                    <input class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number" type="tel" inputmode="tel" value="{{ old('mobile_number', $mobileOtpPending?->pending_value ?? $member->phone) }}" maxlength="16" placeholder="+919876543210" required autocomplete="tel" @error('mobile_number') aria-invalid="true" aria-describedby="mobile-number-error" @enderror>
                    <p class="form-help">Use international E.164 format without spaces, such as +919876543210.</p>
                    @error('mobile_number')<p class="form-error" id="mobile-number-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-actions form-actions--between">
                    <span class="form-help">A new request replaces any previous mobile code.</span>
                    <button class="member-button member-button--primary" type="submit" data-otp-send data-cooldown="{{ $mobileCooldown }}" @disabled($mobileCooldown > 0)>
                        <i data-lucide="send" aria-hidden="true"></i>
                        <span data-otp-send-label>{{ $mobileOtpPending ? 'Resend Code' : 'Send Code' }}</span>
                    </button>
                </div>
            </form>

            @if ($mobileOtpPending)
                <div class="otp-panel">
                    <div class="otp-panel__copy">
                        <strong>Enter the SMS code</strong>
                        <span>Code sent to {{ $mobileOtpPending->destination }}. Expires {{ $mobileOtpPending->expires_at->diffForHumans() }}.</span>
                    </div>
                    <form class="member-form otp-form" method="POST" action="{{ route('member.account.mobile.verify-otp') }}">
                        @csrf
                        <div class="form-field">
                            <label class="sr-only" for="mobile_otp">Six-digit mobile verification code</label>
                            <input class="form-control otp-input @error('mobile_otp') is-invalid @enderror" id="mobile_otp" name="mobile_otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autocomplete="one-time-code" data-otp-input @error('mobile_otp') aria-invalid="true" aria-describedby="mobile-otp-error" @enderror>
                            @error('mobile_otp')<p class="form-error" id="mobile-otp-error">{{ $message }}</p>@enderror
                        </div>
                        <button class="member-button member-button--primary" type="submit">
                            <i data-lucide="badge-check" aria-hidden="true"></i>
                            Verify Mobile
                        </button>
                    </form>
                </div>
            @endif
        </section>

        <section class="member-card verification-card">
            <header class="member-card__header verification-card__header">
                <div class="verification-card__title">
                    <span class="verification-card__icon"><i data-lucide="mail-check" aria-hidden="true"></i></span>
                    <div>
                        <h2>Email Address</h2>
                    </div>
                </div>
            </header>

            <div class="verification-current">
                <span>Current email</span>
                <strong>{{ $member->email }}</strong>
            </div>

            <form class="member-form" method="POST" action="{{ route('member.account.email.send-otp') }}">
                @csrf
                <div class="form-field">
                    <label for="new_email">New email address</label>
                    <input class="form-control @error('new_email') is-invalid @enderror" id="new_email" name="new_email" type="email" value="{{ old('new_email', $emailOtpPending?->pending_value) }}" maxlength="255" placeholder="you@example.com" required autocomplete="email" @error('new_email') aria-invalid="true" aria-describedby="new-email-error" @enderror>
                    @error('new_email')<p class="form-error" id="new-email-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-actions form-actions--end">
                    <button class="member-button member-button--primary" type="submit" data-otp-send data-cooldown="{{ $emailCooldown }}" @disabled($emailCooldown > 0)>
                        <i data-lucide="send" aria-hidden="true"></i>
                        <span data-otp-send-label>{{ $emailOtpPending ? 'Resend Code' : 'Send Code' }}</span>
                    </button>
                </div>
            </form>

            @if ($emailOtpPending)
                <div class="otp-panel">
                    <div class="otp-panel__copy">
                        <strong>Approve the email change</strong>
                        <span>Code sent to {{ $emailOtpPending->destination }}.</span>
                    </div>
                    <form class="member-form otp-form" method="POST" action="{{ route('member.account.email.verify-otp') }}">
                        @csrf
                        <div class="form-field">
                            <label class="sr-only" for="email_otp">Six-digit email verification code</label>
                            <input class="form-control otp-input @error('email_otp') is-invalid @enderror" id="email_otp" name="email_otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autocomplete="one-time-code" data-otp-input @error('email_otp') aria-invalid="true" aria-describedby="email-otp-error" @enderror>
                            @error('email_otp')<p class="form-error" id="email-otp-error">{{ $message }}</p>@enderror
                        </div>
                        <button class="member-button member-button--primary" type="submit">
                            <i data-lucide="badge-check" aria-hidden="true"></i>
                            Update Email
                        </button>
                    </form>
                </div>
            @endif
        </section>
    </div>
@endsection
