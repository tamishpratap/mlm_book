<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f8fe">
    <meta name="application-name" content="{{ config('app.name') }}">
    <title>Forgot Password | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-login.css') }}">
</head>
<body>
    <main class="member-auth-page">
        <section class="member-auth-shell" aria-labelledby="member-forgot-title">
            <div class="member-auth-visual">
                <span class="member-auth-decoration member-auth-decoration--top-left" aria-hidden="true"></span>
                <span class="member-auth-decoration member-auth-decoration--bottom-left" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--visual" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--one" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--two" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--three" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--visual" aria-hidden="true"></span>

                <div class="member-auth-visual-content">
                    <a class="member-auth-logo" href="{{ route('member.login') }}" aria-label="MLM Book Member Login">
                        <img class="mlm-book-logo mlm-book-auth-logo" src="{{ asset('logo/logo.png') }}" alt="MLM Book">
                    </a>

                    <div class="member-auth-message">
                        <div class="member-auth-badge">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m12 3-1.4 3.6L7 8l3.6 1.4L12 13l1.4-3.6L17 8l-3.6-1.4L12 3Z"></path>
                                <path d="m5 14-.8 2.2L2 17l2.2.8L5 20l.8-2.2L8 17l-2.2-.8L5 14Z"></path>
                                <path d="m19 13-.8 2.2-2.2.8 2.2.8L19 19l.8-2.2L22 16l-2.2-.8L19 13Z"></path>
                            </svg>
                            <span>Password Security & Account Access</span>
                        </div>

                        <h1 class="member-auth-heading">
                            Forgot Password?
                            <span class="member-auth-gradient-text">We've got you<br>covered</span>
                        </h1>
                        <p class="member-auth-description">Don't worry! Enter your email address and we'll send you<br>a secure link to reset your password.</p>
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
                                <strong>12k+</strong>
                                <small>people connect daily</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="member-auth-form-panel">
                <span class="member-auth-ring member-auth-ring--form-top" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--four" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--five" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true"></span>

                <div class="member-auth-form-wrap">
                    <header class="member-auth-form-heading">
                        <h2 class="member-auth-title" id="member-forgot-title">Forgot Password</h2>
                        <p class="member-auth-subtitle">Enter your registered email address to receive a reset link.</p>
                    </header>

                    @if (session('status'))
                        <div class="member-auth-alert member-auth-alert--success" role="status" style="background: #ecfdf5; border-color: #a7f3d0; color: #065f46; margin-bottom: 20px;">
                            <svg viewBox="0 0 24 24" aria-hidden="true" style="color: #10b981;"><circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="member-auth-alert" role="alert">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    <form class="member-auth-form" method="POST" action="{{ route('member.forgot-password.send') }}" novalidate data-reset-form>
                        @csrf

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="email">Email address</label>
                            <div class="member-auth-input-wrap @error('email') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>
                                <input
                                    class="member-auth-input"
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    placeholder="Enter your registered email address"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    @error('email') aria-invalid="true" aria-describedby="member-email-error" @enderror
                                >
                            </div>
                            @error('email')
                                <p class="member-auth-error" id="member-email-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <button class="member-auth-submit" type="submit" id="sendResetBtn">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2L11 13"></path><path d="M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
                            <span>Send Reset Link</span>
                        </button>
                    </form>

                    <p class="member-auth-security">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span>Your data is protected and secure with us.</span>
                    </p>
                    <p class="member-auth-switch"><a href="{{ route('member.login') }}">&larr; Back to Login</a></p>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('member_assets/js/member-login.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-reset-form]');
        const btn = document.getElementById('sendResetBtn');
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.querySelector('span').textContent = 'Sending Link...';
            });
        }
    });
    </script>
</body>
</html>
