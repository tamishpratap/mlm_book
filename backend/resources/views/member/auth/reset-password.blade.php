<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f8fe">
    <meta name="application-name" content="{{ config('app.name') }}">
    <title>Reset Password | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-login.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-reset.css') }}">
</head>
<body>
    <main class="member-auth-page member-reset-page">
        <section class="member-auth-shell" aria-labelledby="member-reset-title">
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
                            <span>Your community, your world</span>
                        </div>

                        <h1 class="member-auth-heading">
                            Create New Password
                            <span class="member-auth-gradient-text">Secure your<br>account</span>
                        </h1>
                        <p class="member-auth-description">Connect with friends, share meaningful moments,<br>and discover new stories together.</p>
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

            <div class="member-auth-form-panel member-reset-form-panel">
                <span class="member-auth-ring member-auth-ring--form-top" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--four" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--five" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true"></span>

                <div class="member-auth-form-wrap">
                    <header class="member-auth-form-heading">
                        <h2 class="member-auth-title" id="member-reset-title">Create New Password</h2>
                        <p class="member-auth-subtitle">Enter your new password below to update your account.</p>
                    </header>

                    @if (session('status'))
                        <div class="member-auth-alert member-auth-alert--success" role="status" style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; color: #065f46; display: flex; align-items: flex-start; gap: 10px;">
                            <svg viewBox="0 0 24 24" aria-hidden="true" style="width: 20px; height: 20px; flex-shrink: 0; color: #10b981; margin-top: 1px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path></svg>
                            <span style="font-size: 13.5px; font-weight: 500; line-height: 1.45;">{{ session('status') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="member-auth-alert" role="alert" style="background: #fff7f7; border: 1px solid #fecaca; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; color: #c22f2f; display: flex; align-items: flex-start; gap: 10px;">
                            <svg viewBox="0 0 24 24" aria-hidden="true" style="width: 20px; height: 20px; flex-shrink: 0; color: #dc2626; margin-top: 1px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span style="font-size: 13.5px; font-weight: 500; line-height: 1.45;">{{ session('error') }}</span>
                        </div>
                    @endif

                    <form class="member-auth-form member-reset-form" method="POST" action="{{ route('member.password.update') }}" novalidate data-reset-form>
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="email">Email address</label>
                            <div class="member-auth-input-wrap @error('email') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="18" height="14" x="3" y="5" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>
                                <input
                                    class="member-auth-input"
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email', $email) }}"
                                    placeholder="Enter your registered email address"
                                    autocomplete="email"
                                    required
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

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="password">New Password</label>
                            <div class="member-auth-input-wrap @error('password') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2"></rect><path d="M8 9V6a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
                                <input
                                    class="member-auth-input member-auth-input--password"
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Enter new password (min. 8 chars)"
                                    autocomplete="new-password"
                                    required
                                    autofocus
                                    @error('password') aria-invalid="true" aria-describedby="member-password-error" @enderror
                                >
                                <button class="member-auth-password-toggle" type="button" aria-label="Show password" data-password-toggle>
                                    <svg class="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                    <svg class="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7"></path><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5"></path></svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="member-auth-error" id="member-password-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="password_confirmation">Confirm New Password</label>
                            <div class="member-auth-input-wrap @error('password_confirmation') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2"></rect><path d="M8 9V6a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
                                <input
                                    class="member-auth-input member-auth-input--password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    placeholder="Re-enter your new password"
                                    autocomplete="new-password"
                                    required
                                    @error('password_confirmation') aria-invalid="true" aria-describedby="member-confirm-password-error" @enderror
                                >
                                <button class="member-auth-password-toggle" type="button" aria-label="Show password" data-password-toggle>
                                    <svg class="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                    <svg class="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7"></path><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5"></path></svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="member-auth-error" id="member-confirm-password-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <button class="member-auth-submit" type="submit" id="resetPasswordBtn">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path></svg>
                            <span>Reset Password</span>
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
        const btn = document.getElementById('resetPasswordBtn');
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                const textSpan = btn.querySelector('span');
                if (textSpan) {
                    textSpan.textContent = 'Resetting Password...';
                }
            });
        }
    });
    </script>
</body>
</html>

