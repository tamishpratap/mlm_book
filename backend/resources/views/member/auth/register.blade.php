<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f8fe">
    <meta name="application-name" content="{{ config('app.name') }}">
    <title>Create Account | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-login.css') }}">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-register.css') }}">
</head>
<body>
    <main class="member-auth-page member-register-page">
        <section class="member-auth-shell" aria-labelledby="member-register-title">
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
                            <span>Your community, your world</span>
                        </div>

                        <h1 class="member-auth-heading">
                            Join your community
                            <span class="member-auth-gradient-text">Start a new<br>journey today</span>
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

            <div class="member-auth-form-panel member-register-form-panel">
                <span class="member-auth-ring member-auth-ring--form-top" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--four" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--five" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true"></span>

                <div class="member-auth-form-wrap">
                    <header class="member-auth-form-heading">
                        <h2 class="member-auth-title" id="member-register-title">Create your account</h2>
                        <p class="member-auth-subtitle">Join the community and start your journey today.</p>
                    </header>

                    @if (session('status'))
                        <div class="member-verify-alert member-verify-alert--success" role="alert" style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; background: #eafaf1; border: 1px solid #c3f0d4; color: #0f7642; font-size: 14px;">
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif
                    @if (request('error') === 'account_exists' || session('error') === 'account_exists')
                        <div class="member-auth-alert member-auth-alert--account-exists" role="alert" style="background: #eff6ff; border-color: #bfdbfe; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <div>
                                    <strong style="display: block; font-size: 15px; color: #1e3a8a; margin-bottom: 4px; font-weight: 600;">
                                        Account Already Exists
                                    </strong>
                                    <p style="margin: 0; font-size: 13.5px; line-height: 1.45; color: #1e40af;">
                                        An MLM Book account already exists for this Google account. Please log in instead.
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 2px;">
                                <a href="{{ route('member.login') }}" class="member-button member-button--primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: #2563eb; color: #ffffff; font-weight: 600; font-size: 13.5px; padding: 8px 16px; border-radius: 8px; text-decoration: none; border: none; cursor: pointer;">
                                    <span>Login</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path></svg>
                                </a>
                                <a href="{{ route('member.register') }}" style="display: inline-flex; align-items: center; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 500; padding: 8px 14px; text-decoration: none; cursor: pointer;">
                                    Dismiss
                                </a>
                            </div>
                        </div>
                    @elseif (session('error'))
                        <div class="member-auth-error" role="alert" style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; font-size: 14px;">
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    <form class="member-auth-form member-register-form" method="POST" action="{{ route('member.register.submit') }}" novalidate>
                        @csrf

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="name">Full name</label>
                            <div class="member-auth-input-wrap @error('name') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                                <input
                                    class="member-auth-input"
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    placeholder="Enter your full name"
                                    autocomplete="name"
                                    required
                                    autofocus
                                    @error('name') aria-invalid="true" aria-describedby="member-name-error" @enderror
                                >
                            </div>
                            @error('name')
                                <p class="member-auth-error" id="member-name-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="member-auth-field" data-member-user-id-field>
                            <label class="member-auth-label" for="memberUserId">User ID (Optional)</label>
                            <div class="member-auth-input-wrap @error('user_id') member-auth-input-wrap--error @enderror" data-member-user-id-input-wrap>
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"></path></svg>
                                <input
                                    class="member-auth-input"
                                    id="memberUserId"
                                    name="user_id"
                                    type="text"
                                    value="{{ old('user_id') }}"
                                    placeholder="Leave blank to auto-generate (e.g. abcd123456)"
                                    autocomplete="username"
                                    minlength="10"
                                    maxlength="10"
                                    spellcheck="false"
                                    autocapitalize="characters"
                                    aria-describedby="memberUserIdFeedback"
                                    @error('user_id') aria-invalid="true" @enderror
                                    data-check-url="{{ route('member.register.check-user-id') }}"
                                >
                            </div>
                            <div
                                id="memberUserIdFeedback"
                                class="member-user-id-feedback @error('user_id') is-invalid @enderror"
                                aria-live="polite"
                            >
                                @error('user_id')
                                    {{ $message }}
                                @else
                                    Leave blank to auto-generate a 10-character User ID from your name.
                                @enderror
                            </div>
                        </div>

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
                                    placeholder="Enter your email address"
                                    autocomplete="email"
                                    required
                                    @error('email') aria-invalid="true" aria-describedby="member-register-email-error" @enderror
                                >
                            </div>
                            @error('email')
                                <p class="member-auth-error" id="member-register-email-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="password">Password</label>
                            <div class="member-auth-input-wrap @error('password') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2"></rect><path d="M8 9V6a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
                                <input
                                    class="member-auth-input member-auth-input--password"
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Create a strong password"
                                    autocomplete="new-password"
                                    required
                                    @error('password') aria-invalid="true" aria-describedby="member-register-password-error" @enderror
                                >
                                <button class="member-auth-password-toggle" type="button" aria-label="Show password" aria-controls="password" data-password-toggle data-password-target="password">
                                    <svg class="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                    <svg class="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7"></path><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5"></path></svg>
                                </button>
                            </div>
                            <span class="member-register-confirm-help" id="member-password-help">Password must be at least 8 characters long.</span>
                            @error('password')
                                <p class="member-auth-error" id="member-register-password-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="member-auth-field">
                            <label class="member-auth-label" for="password_confirmation">Confirm password</label>
                            <div class="member-auth-input-wrap @error('password_confirmation') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                                <input
                                    class="member-auth-input member-auth-input--password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    placeholder="Enter your password again"
                                    autocomplete="new-password"
                                    required
                                    @error('password_confirmation') aria-invalid="true" aria-describedby="member-confirm-password-error" @else aria-describedby="member-confirm-password-help" @enderror
                                >
                                <button class="member-auth-password-toggle" type="button" aria-label="Show confirm password" aria-controls="password_confirmation" data-password-toggle data-password-target="password_confirmation">
                                    <svg class="member-auth-eye member-auth-eye--show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                    <svg class="member-auth-eye member-auth-eye--hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 6.2A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.1 2.7"></path><path d="M6.3 6.4C3.9 8.1 2.5 12 2.5 12s3.5 6 9.5 6a9.8 9.8 0 0 0 3.1-.5"></path></svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="member-auth-error" id="member-confirm-password-error" role="alert">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @else
                                <span class="member-register-confirm-help" id="member-confirm-password-help">Use the same password entered above.</span>
                            @enderror
                        </div>

                        <button class="member-auth-submit" type="submit" data-member-register-submit>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M22 11h-6"></path></svg>
                            <span>Create account</span>
                        </button>

                        <div class="member-auth-divider" role="separator"><span>or continue with</span></div>

                        <a class="member-auth-google" href="{{ route('member.google.redirect') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z"></path>
                                <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.42l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"></path>
                                <path fill="#FBBC05" d="M6.39 13.87A6 6 0 0 1 6.07 12c0-.65.11-1.28.32-1.87V7.51H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.49l3.35-2.62Z"></path>
                                <path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.82 1.5l2.88-2.88A9.65 9.65 0 0 0 12 2a10 10 0 0 0-8.96 5.51l3.35 2.62C7.18 7.76 9.39 6 12 6Z"></path>
                            </svg>
                            <span>Sign up with Google</span>
                        </a>
                    </form>

                    <p class="member-auth-switch member-register-login-link">Already have an account? <a href="{{ route('member.login') }}">Login</a></p>

                    <p class="member-auth-security member-register-security">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span>Your data is protected and secure with us.</span>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="{{ asset('member_assets/js/member-register.js') }}"></script>
</body>
</html>
