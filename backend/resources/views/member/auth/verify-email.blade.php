<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f8fe">
    <meta name="application-name" content="{{ config('app.name') }}">
    <title>Verify Your Email | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-login.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-register.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-verify.css') }}">
</head>
<body>
    <main class="member-auth-page member-verify-page">
        <section class="member-auth-shell" aria-labelledby="member-verify-title">
            <div class="member-auth-visual">
                <span class="member-auth-decoration member-auth-decoration--top-left" aria-hidden="true"></span>
                <span class="member-auth-decoration member-auth-decoration--bottom-left" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--visual" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--one" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--two" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--three" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--visual" aria-hidden="true"></span>

                <div class="member-auth-visual-content">
                    <a class="member-auth-logo" href="{{ route('member.register') }}" aria-label="MLM Book Member Registration">
                        <img class="mlm-book-logo mlm-book-auth-logo" src="{{ asset('logo/logo.png') }}" alt="MLM Book">
                    </a>

                    <div class="member-auth-message">
                        <div class="member-auth-badge">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m12 3-1.4 3.6L7 8l3.6 1.4L12 13l1.4-3.6L17 8l-3.6-1.4L12 3Z"></path>
                                <path d="m5 14-.8 2.2L2 17l2.2.8L5 20l.8-2.2L8 17l-2.2-.8L5 14Z"></path>
                                <path d="m19 13-.8 2.2-2.2.8 2.2.8L19 19l.8-2.2L22 16l-2.2-.8L19 13Z"></path>
                            </svg>
                            <span>Security & Privacy First</span>
                        </div>

                        <h1 class="member-auth-heading">
                            Almost there!
                            <span class="member-auth-gradient-text">Verify your<br>email address</span>
                        </h1>
                        <p class="member-auth-description">We verify every email address to keep the MLM Book community authentic, trusted, and secure.</p>
                    </div>

                    <div class="member-auth-media" aria-hidden="true">
                        <div class="member-auth-photo member-auth-photo--one">
                            <img src="{{ asset('member_assets/images/login/story_2.jpg') }}" alt="">
                            <span><img src="{{ asset('member_assets/images/login/profile_1.jpg') }}" alt=""></span>
                        </div>
                        <div class="member-auth-photo member-auth-photo--two">
                            <img src="{{ asset('member_assets/images/login/story_3.jpg') }}" alt="">
                            <span><img src="{{ asset('member_assets/images/login/profile_2.jpg') }}" alt=""></span>
                        </div>
                        <div class="member-auth-photo member-auth-photo--three">
                            <img src="{{ asset('member_assets/images/login/story_5.jpg') }}" alt="">
                            <span><img src="{{ asset('member_assets/images/login/profile_4.png') }}" alt=""></span>
                        </div>

                        <div class="member-auth-community-card">
                            <div class="member-auth-community-avatars">
                                <img src="{{ asset('member_assets/images/login/profile_1.jpg') }}" alt="">
                                <img src="{{ asset('member_assets/images/login/profile_2.jpg') }}" alt="">
                                <img src="{{ asset('member_assets/images/login/profile_5.png') }}" alt="">
                                <span>+12k</span>
                            </div>
                            <div>
                                <strong>100%</strong>
                                <small>Verified community</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="member-auth-form-panel member-verify-form-panel">
                <span class="member-auth-ring member-auth-ring--form-top" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--four" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--five" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true"></span>

                <div class="member-auth-form-wrap">
                    <header class="member-auth-form-heading">
                        <div class="member-verify-icon-badge" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                        </div>
                        <h2 class="member-auth-title" id="member-verify-title">Verify Your Email</h2>
                        <p class="member-auth-subtitle">Enter the 6-digit verification code sent to your email address.</p>
                    </header>

                    <div class="member-verify-destination">
                        <span class="member-verify-destination-label">Verification code sent to:</span>
                        <strong class="member-verify-destination-email">{{ $email }}</strong>
                    </div>

                    @if (session('status'))
                        <div class="member-verify-alert member-verify-alert--success" role="alert">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    <form class="member-auth-form member-verify-form" method="POST" action="{{ route('member.register.verify.submit') }}" id="memberVerifyForm" novalidate>
                        @csrf

                        <div class="member-auth-field">
                            <label class="member-auth-label member-verify-code-label" for="otpInput">Verification Code</label>
                            
                            <div class="member-verify-otp-container @error('otp') member-auth-input-wrap--error @enderror">
                                <input
                                    class="member-verify-otp-input"
                                    id="otpInput"
                                    name="otp"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    autocomplete="one-time-code"
                                    maxlength="6"
                                    placeholder="• • • • • •"
                                    value="{{ old('otp') }}"
                                    required
                                    autofocus
                                    spellcheck="false"
                                    @error('otp') aria-invalid="true" aria-describedby="member-verify-otp-error" @enderror
                                >
                            </div>

                            @error('otp')
                                <p class="member-auth-error member-verify-error" id="member-verify-otp-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror

                            <span class="member-register-confirm-help member-verify-help">
                                Code expires in 10 minutes.
                            </span>
                        </div>

                        <button class="member-auth-submit member-verify-submit" type="submit" id="verifySubmitBtn">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <span>Verify Email & Create Account</span>
                        </button>
                    </form>

                    <div class="member-verify-actions">
                        <div class="member-verify-resend-wrap">
                            <span class="member-verify-resend-text">Didn't receive the code?</span>
                            <form method="POST" action="{{ route('member.register.resend-otp') }}" id="resendOtpForm" class="member-verify-inline-form">
                                @csrf
                                <button
                                    type="submit"
                                    class="member-verify-resend-btn"
                                    id="resendOtpBtn"
                                    data-cooldown="{{ $resendCooldown ?? 0 }}"
                                    @if(($resendCooldown ?? 0) > 0) disabled @endif
                                >
                                    <span id="resendBtnText">
                                        @if(($resendCooldown ?? 0) > 0)
                                            Resend in <strong id="cooldownTimer">{{ $resendCooldown }}</strong>s
                                        @else
                                            Resend Code
                                        @endif
                                    </span>
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('member.register.cancel') }}" class="member-verify-cancel-form">
                            @csrf
                            <button type="submit" class="member-verify-cancel-btn">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                                <span>Change email or Start Over</span>
                            </button>
                        </form>
                    </div>

                    <p class="member-auth-switch member-register-login-link">Already have an account? <a href="{{ route('member.login') }}">Login</a></p>

                    <p class="member-auth-security member-register-security">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span>Your data is protected and secure with us.</span>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('member_assets/js/member-verify.js') }}"></script>
</body>
</html>
