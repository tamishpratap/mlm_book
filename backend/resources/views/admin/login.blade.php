<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MLM Book Admin Portal Login">
    <title>Admin Login | {{ config('app.name', 'MLM Book') }}</title>

    <!-- Favicon Icon -->
    <link rel="icon" type="image/png" href="{{ asset('admin_assets/images/favicon.png') }}" />
    <link rel="shortcut icon" href="{{ asset('admin_assets/images/favicon.png') }}" />

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&family=Nunito+Sans:wght@600;700;800&display=swap">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/admin-login.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/variables.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/animations.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>

<body class="admin-auth-body">
    <main class="admin-auth-shell animate-fade-in">
        <div class="container admin-auth-container">
            <div class="admin-auth-card">
                <div class="row g-0">
                    <!-- Brand Panel -->
                    <div class="col-lg-5 d-none d-lg-block">
                        <aside class="admin-auth-brand">
                            <span class="badge bg-primary text-uppercase mb-3 px-3 py-2" style="width: fit-content;">Admin Portal</span>
                            <h1 class="fw-bold mb-3" style="font-size: 1.8rem; line-height: 1.3;">Secure workspace for system administration & management.</h1>
                            <p class="text-white-50 mb-4" style="font-size: 0.95rem;">Manage members, business pages, communities, system settings, and analytics from one protected space.</p>
                            
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3 d-flex align-items-center"><i class="fa fa-shield me-3 text-primary"></i> Protected admin authentication</li>
                                <li class="mb-3 d-flex align-items-center"><i class="fa fa-line-chart me-3 text-primary"></i> Real-time system monitoring</li>
                                <li class="d-flex align-items-center"><i class="fa fa-lock me-3 text-primary"></i> Encrypted session control</li>
                            </ul>
                        </aside>
                    </div>

                    <!-- Login Form Panel -->
                    <div class="col-lg-7">
                        <section class="admin-auth-panel">
                            <div class="admin-auth-head mb-4 text-center text-lg-start">
                                <div class="mb-3">
                                    <h3 class="fw-bold text-primary mb-0">{{ config('app.name', 'MLM Book') }}</h3>
                                    <small class="text-muted">ADMINISTRATION</small>
                                </div>
                                <h2 class="fw-bold h4 mb-1">Sign in to Admin Dashboard</h2>
                                <p class="text-muted small">Enter your administrator credentials to proceed.</p>
                            </div>

                            @if (session()->has('error') || session()->has('FailMsg'))
                                <div class="alert alert-danger mb-4" role="alert">
                                    {{ session('error') ?? session('FailMsg') }}
                                </div>
                            @endif

                            @if (session()->has('status') || session()->has('succMsg'))
                                <div class="alert alert-success mb-4" role="alert">
                                    {{ session('status') ?? session('succMsg') }}
                                </div>
                            @endif

                            <form action="{{ route('login.submit') }}" method="POST" class="admin-auth-form">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label font-weight-bold">Email / Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-envelope text-muted"></i></span>
                                        <input id="email" type="text" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="admin@example.com" value="{{ old('email') }}" required autofocus>
                                    </div>
                                    @error('email')
                                        <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label font-weight-bold">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa fa-lock text-muted"></i></span>
                                        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required>
                                    </div>
                                    @error('password')
                                        <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label small text-muted" for="remember">Keep me logged in</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 font-weight-bold text-uppercase" style="letter-spacing: 0.5px;">
                                    Sign In To Dashboard <i class="fa fa-arrow-right ms-2"></i>
                                </button>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
