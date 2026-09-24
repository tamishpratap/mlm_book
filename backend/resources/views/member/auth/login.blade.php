<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f8fe">
    <meta name="application-name" content="{{ config('app.name') }}">
    <title>Login | {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('member_assets/css/member-login.css') }}">
</head>
<body>
    <main class="member-auth-page">
        <section class="member-auth-shell" aria-labelledby="member-login-title">
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
                            Welcome back
                            <span class="member-auth-gradient-text">Let’s continue<br>your journey</span>
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

            <div class="member-auth-form-panel">
                <span class="member-auth-ring member-auth-ring--form-top" aria-hidden="true"></span>
                <span class="member-auth-ring member-auth-ring--form-bottom" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--four" aria-hidden="true"></span>
                <span class="member-auth-dot member-auth-dot--five" aria-hidden="true"></span>
                <span class="member-auth-dot-grid member-auth-dot-grid--form" aria-hidden="true"></span>

                <div class="member-auth-form-wrap">
                    <header class="member-auth-form-heading">
                        <h2 class="member-auth-title" id="member-login-title">Welcome back!</h2>
                        <p class="member-auth-subtitle">Enter your details to access your account.</p>
                    </header>

                    @if (request('success') === 'account_created' || session('success'))
                        <div class="member-auth-alert member-auth-alert--success" role="status" style="background: #ecfdf5; border-color: #a7f3d0; color: #065f46; margin-bottom: 20px;">
                            <svg viewBox="0 0 24 24" aria-hidden="true" style="color: #10b981;"><circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path></svg>
                            <span>{{ request('success') === 'account_created' ? 'Your account has been created successfully. You can now log in.' : session('success') }}</span>
                        </div>
                    @endif

                    @if (request('error') === 'signup_required' || session('error') === 'signup_required')
                        <div class="member-auth-alert member-auth-alert--signup-required" role="alert" style="background: #eff6ff; border-color: #bfdbfe; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <div>
                                    <strong style="display: block; font-size: 15px; color: #1e3a8a; margin-bottom: 4px; font-weight: 600;">
                                        Create Your MLM Book Account First
                                    </strong>
                                    <p style="margin: 0; font-size: 13.5px; line-height: 1.45; color: #1e40af;">
                                        We couldn’t find an MLM Book account linked to this Google account. Please create your account first, then log in with Google.
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 2px;">
                                <a href="{{ route('member.register', array_filter(['ref' => request('ref', session('ref'))])) }}" class="member-button member-button--primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: #2563eb; color: #ffffff; font-weight: 600; font-size: 13.5px; padding: 8px 16px; border-radius: 8px; text-decoration: none; border: none; cursor: pointer;">
                                    <span>Create Account</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                                </a>
                                <a href="{{ route('member.login') }}" style="display: inline-flex; align-items: center; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 500; padding: 8px 14px; text-decoration: none; cursor: pointer;">
                                    Back to Login
                                </a>
                            </div>
                        </div>
                    @elseif (session('error') || request('error') || ($errors->has('email') && str_contains(strtolower($errors->first('email')), 'blocked')))
                        <div class="member-auth-alert" role="alert" style="background: #fff7f7; border: 1px solid #fecaca; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; color: #c22f2f; display: flex; align-items: flex-start; gap: 10px;">
                            <svg viewBox="0 0 24 24" aria-hidden="true" style="width: 20px; height: 20px; flex-shrink: 0; color: #dc2626; margin-top: 1px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span style="font-size: 13.5px; font-weight: 500; line-height: 1.45;">{{ session('error') ?: request('error') ?: $errors->first('email') }}</span>
                        </div>
                    @endif

                    <form class="member-auth-form" method="POST" action="{{ route('member.login.submit') }}" novalidate>
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
                                    placeholder="Enter your email address"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    @error('email') aria-invalid="true" aria-describedby="member-email-error" @enderror
                                >
                            </div>
                            @error('email')
                                @if (!str_contains(strtolower($message), 'blocked'))
                                    <p class="member-auth-error" id="member-email-error" role="alert">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 17h.01"></path></svg>
                                        <span>{{ $message }}</span>
                                    </p>
                                @endif
                            @enderror
                        </div>

                        <div class="member-auth-field">
                            <div class="member-auth-label-row">
                                <label class="member-auth-label" for="password">Password</label>
                                <a class="member-auth-forgot" href="{{ route('member.forgot-password') }}">Forgot password?</a>
                            </div>
                            <div class="member-auth-input-wrap @error('password') member-auth-input-wrap--error @enderror">
                                <svg class="member-auth-input-icon" viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="9" rx="2"></rect><path d="M8 9V6a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path></svg>
                                <input
                                    class="member-auth-input member-auth-input--password"
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    required
                                    @error('password') aria-invalid="true" aria-describedby="member-password-error" @enderror
                                >
                                <button class="member-auth-password-toggle" type="button" aria-label="Show password" aria-controls="password" data-password-toggle>
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

                        <button class="member-auth-submit" type="submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><path d="m10 17 5-5-5-5"></path><path d="M15 12H3"></path></svg>
                            <span>Login</span>
                        </button>

                        <div class="member-auth-divider" role="separator"><span>or continue with</span></div>

                        <a class="member-auth-google" href="{{ route('member.google.redirect') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z"></path>
                                <path fill="#34A853" d="M12 22c2.7 0 4.98-.9 6.63-2.42l-3.24-2.54c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"></path>
                                <path fill="#FBBC05" d="M6.39 13.87A6 6 0 0 1 6.07 12c0-.65.11-1.28.32-1.87V7.51H3.04A10 10 0 0 0 2 12c0 1.61.38 3.14 1.04 4.49l3.35-2.62Z"></path>
                                <path fill="#EA4335" d="M12 6c1.47 0 2.79.51 3.82 1.5l2.88-2.88A9.65 9.65 0 0 0 12 2a10 10 0 0 0-8.96 5.51l3.35 2.62C7.18 7.76 9.39 6 12 6Z"></path>
                            </svg>
                            <span>Login with Google</span>
                        </a>
                    </form>

                    <p class="member-auth-security">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"></path><path d="m9 12 2 2 4-4"></path></svg>
                        <span>Your data is protected and secure with us.</span>
                    </p>
                    <p class="member-auth-switch">Don’t have an account? <a href="{{ route('member.register') }}">Create account</a></p>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('member_assets/js/member-login.js') }}"></script>
</body>
</html>
