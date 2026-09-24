<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - {{ config('app.name', 'MLM Book') }}</title>
    
    <!-- Favicon Icon -->
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}" />
    <link rel="shortcut icon" href="{{ asset('logo/logo.png') }}" />

    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/bootstrap.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        :root {
            --dark-bg: #0A0F1F;
            --card-bg: rgba(20, 32, 58, 0.78);
            --primary-blue: #3B82F6;
            --primary-purple: #8B5CF6;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --text-sub: #B8C2D8;
            --border-glass: rgba(255, 255, 255, 0.12);
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-main);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            position: relative;
        }

        .ambient-glow-1 {
            position: absolute;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.18) 0%, rgba(139,92,246,0.14) 50%, transparent 80%);
            filter: blur(140px);
            pointer-events: none;
            z-index: 0;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: -150px;
            right: -150px;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(139,92,246,0.20) 0%, transparent 70%);
            filter: blur(160px);
            pointer-events: none;
            z-index: 0;
        }

        .back-link {
            position: absolute;
            top: 24px;
            right: 32px;
            color: #CBD5E1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            z-index: 30;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.05);
        }

        .admin-login-layout {
            min-height: 100vh;
            display: flex;
            position: relative;
            z-index: 10;
        }

        .brand-section {
            flex: 0 0 45%;
            padding: 60px 50px 60px 80px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .globe-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 650px;
            height: 650px;
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
        }

        .brand-badge {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.25em;
            color: #8BA7D8;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: inline-block;
        }

        .brand-heading {
            font-size: 48px;
            font-weight: 800;
            line-height: 1.08;
            color: #F8FAFC;
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }

        .gradient-text {
            background: linear-gradient(90deg, #3B82F6 0%, #8B5CF6 50%, #B85CF6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-desc {
            color: var(--text-sub);
            font-size: 16px;
            line-height: 1.6;
            max-width: 480px;
        }

        .stats-row {
            display: flex;
            align-items: center;
            gap: 28px;
            padding-bottom: 24px;
        }

        .stat-item {
            padding-right: 28px;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
        }

        .stat-item:last-child {
            border-right: none;
            padding-right: 0;
        }

        .stat-num {
            font-size: 28px;
            font-weight: 800;
            color: #F8FAFC;
            line-height: 1.1;
        }

        .stat-lbl {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .brand-copy {
            font-size: 13px;
            color: #64748B;
            margin: 0;
        }

        .login-card-section {
            flex: 0 0 55%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .glass-card {
            width: 100%;
            max-width: 520px;
            background: var(--card-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid var(--border-glass);
            border-radius: 22px;
            padding: 40px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
        }

        .shield-icon-box {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
            color: #FFFFFF;
            font-size: 28px;
        }

        .card-title {
            font-size: 26px;
            font-weight: 700;
            color: #F8FAFC;
            margin-bottom: 6px;
            text-align: center;
        }

        .card-subtitle {
            font-size: 14px;
            color: #A8B3C7;
            margin-bottom: 28px;
            text-align: center;
        }

        .custom-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #CBD5E1;
            margin-bottom: 8px;
            display: block;
        }

        .custom-input-group {
            position: relative;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            height: 54px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            margin-bottom: 18px;
        }

        .custom-input-group:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .custom-input-group .input-icon {
            padding: 0 16px;
            color: var(--text-muted);
            font-size: 16px;
        }

        .custom-input-group input {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            color: #F8FAFC !important;
            font-size: 14px;
            width: 100%;
            height: 100%;
            box-shadow: none !important;
        }

        .custom-input-group input::placeholder {
            color: var(--text-muted);
        }

        .pw-toggle-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 0 16px;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .pw-toggle-btn:hover {
            color: #FFFFFF;
        }

        .form-check-label {
            font-size: 13px;
            color: #CBD5E1;
            cursor: pointer;
        }

        .btn-gradient-submit {
            background: linear-gradient(90deg, #3B82F6 0%, #6366F1 50%, #8B5CF6 100%);
            border: none;
            border-radius: 12px;
            height: 54px;
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
        }

        .btn-gradient-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(99, 102, 241, 0.35);
            filter: brightness(1.06);
            color: #FFFFFF;
        }

        .trust-grid {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 14px;
            padding: 12px;
            text-align: center;
        }

        .trust-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .trust-item:last-child {
            border-right: none;
        }

        .trust-icon {
            color: #93C5FD;
            font-size: 15px;
        }

        .trust-label {
            font-size: 11px;
            font-weight: 600;
            color: #CBD5E1;
        }

        @media (max-width: 991.98px) {
            .admin-login-layout {
                flex-direction: column;
            }
            .brand-section {
                flex: 0 0 100%;
                padding: 40px 24px 20px;
                text-align: center;
            }
            .brand-heading {
                font-size: 36px;
            }
            .brand-desc {
                margin: 0 auto;
            }
            .stats-row {
                justify-content: center;
                margin-top: 24px;
            }
            .login-card-section {
                flex: 0 0 100%;
                padding: 24px 16px 40px;
            }
            .glass-card {
                padding: 28px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <a href="{{ url('/') }}" class="back-link">
        <i class="fa fa-arrow-left me-1"></i> Back to Website
    </a>

    <div class="admin-login-layout">
        <!-- LEFT SIDE: Branding Section -->
        <section class="brand-section">
            <!-- Globe SVG Background -->
            <svg class="globe-bg" viewBox="0 0 600 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="netGradBlade" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.8" />
                        <stop offset="50%" stop-color="#6366F1" stop-opacity="0.5" />
                        <stop offset="100%" stop-color="#8B5CF6" stop-opacity="0.8" />
                    </linearGradient>
                </defs>
                <circle cx="300" cy="300" r="230" stroke="url(#netGradBlade)" stroke-width="1" stroke-dasharray="6 6" opacity="0.4" />
                <circle cx="300" cy="300" r="175" stroke="url(#netGradBlade)" stroke-width="1" opacity="0.35" />
                <ellipse cx="300" cy="300" rx="230" ry="75" stroke="url(#netGradBlade)" stroke-width="1" opacity="0.4" />
                <ellipse cx="300" cy="300" rx="230" ry="145" stroke="url(#netGradBlade)" stroke-width="1" opacity="0.3" />
                <ellipse cx="300" cy="300" rx="75" ry="230" stroke="url(#netGradBlade)" stroke-width="1" opacity="0.3" />
                <ellipse cx="300" cy="300" rx="150" ry="230" stroke="url(#netGradBlade)" stroke-width="1" opacity="0.3" />
                <line x1="120" y1="180" x2="250" y2="220" stroke="#3B82F6" stroke-width="1.2" opacity="0.5" />
                <line x1="250" y1="220" x2="380" y2="170" stroke="#8B5CF6" stroke-width="1.2" opacity="0.5" />
                <line x1="250" y1="220" x2="320" y2="340" stroke="#3B82F6" stroke-width="1.2" opacity="0.6" />
                <circle cx="250" cy="220" r="5" fill="#3B82F6" />
                <circle cx="380" cy="170" r="4" fill="#8B5CF6" />
                <circle cx="320" cy="340" r="6" fill="#60A5FA" />
            </svg>

            <div style="position: relative; z-index: 2;">
                <div class="mb-4">
                    <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" style="height: 48px;" class="mb-2">
                </div>

                <span class="brand-badge">ADMIN CONSOLE</span>

                <h1 class="brand-heading">
                    Global Platform<br>
                    <span class="gradient-text">Greater Possibilities</span>
                </h1>

                <p class="brand-desc">
                    Manage. Secure. Scale. &mdash; Everything you need to keep MLM Book running smoothly, all in one place.
                </p>
            </div>

            <div style="position: relative; z-index: 2;">
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-num">50K+</div>
                        <div class="stat-lbl">Members</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">1K+</div>
                        <div class="stat-lbl">Business Pages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num">100+</div>
                        <div class="stat-lbl">Communities</div>
                    </div>
                </div>

                <p class="brand-copy">&copy; {{ date('Y') }} MLM Book Platform. All rights reserved.</p>
            </div>
        </section>

        <!-- RIGHT SIDE: Glassmorphism Login Card -->
        <section class="login-card-section">
            <div class="glass-card">
                <div class="shield-icon-box">
                    <i class="fa fa-shield-alt"></i>
                </div>

                <h2 class="card-title">Admin Sign In</h2>
                <p class="card-subtitle">Secure Access to MLM Book Administration</p>

                @if($errors->any())
                    <div class="alert mb-4 text-start d-flex align-items-center" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: #FCA5A5; border-radius: 12px; font-size: 13px; padding: 12px 16px;">
                        <i class="fa fa-exclamation-circle me-2" style="font-size: 16px; color: #F87171;"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                @if(session('status'))
                    <div class="alert mb-4 text-start d-flex align-items-center" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: #6EE7B7; border-radius: 12px; font-size: 13px; padding: 12px 16px;">
                        <i class="fa fa-check-circle me-2" style="font-size: 16px;"></i>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                <form action="{{ route('admin.login.submit') }}" method="POST">
                    @csrf

                    <div>
                        <label class="custom-label">Administrator Email</label>
                        <div class="custom-input-group">
                            <span class="input-icon"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="admin@example.com" autofocus autocomplete="email">
                        </div>
                    </div>

                    <div>
                        <label class="custom-label">Password</label>
                        <div class="custom-input-group">
                            <span class="input-icon"><i class="fa fa-lock"></i></span>
                            <input id="bladePasswordInput" type="password" name="password" required placeholder="••••••••••••" autocomplete="current-password">
                            <button type="button" class="pw-toggle-btn" onclick="toggleBladePassword()">
                                <i id="bladeEyeIcon" class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" id="remember" type="checkbox" name="remember" style="background-color: #0F172A; border-color: #334155; cursor: pointer;">
                            <label class="form-check-label" for="remember">Remember this session</label>
                        </div>

                        <span style="font-size: 12px; color: #60A5FA; cursor: pointer;" onclick="alert('Password reset requires Super Administrator intervention.')">Forgot Password?</span>
                    </div>

                    <button class="btn-gradient-submit" type="submit">
                        <span>Sign In to Admin Console</span>
                        <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </form>

                <!-- Trust Indicators -->
                <div class="trust-grid">
                    <div class="trust-item">
                        <i class="fa fa-shield-alt trust-icon"></i>
                        <span class="trust-label">Secure</span>
                    </div>
                    <div class="trust-item">
                        <i class="fa fa-check-circle trust-icon"></i>
                        <span class="trust-label">Reliable</span>
                    </div>
                    <div class="trust-item">
                        <i class="fa fa-sync-alt trust-icon"></i>
                        <span class="trust-label">Always On</span>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        function toggleBladePassword() {
            var input = document.getElementById('bladePasswordInput');
            var icon = document.getElementById('bladeEyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa fa-eye';
            }
        }
    </script>
</body>
</html>

