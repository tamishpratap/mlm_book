<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MLM Book Admin Portal">
    <title>@yield('title', 'Admin Dashboard') || {{ config('app.name', 'MLM Book') }}</title>

    <!-- Favicon Icon -->
    <link rel="icon" type="image/png" href="{{ asset('admin_assets/images/favicon.png') }}" />
    <link rel="shortcut icon" href="{{ asset('admin_assets/images/favicon.png') }}" />
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Rubik:400,400i,500,500i,700,700i&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i,900&amp;display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    
    <!-- Vendor & Theme CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/icofont.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/themify.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/flag-icon.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/feather-icon.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/slick.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/slick-theme.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/scrollbar.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/animate.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/jquery.dataTables.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/bootstrap.css') }}">
    
    <!-- App & Custom Modular Admin CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/style.css') }}">
    <link id="color" rel="stylesheet" href="{{ asset('admin_assets/css/color-1.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/responsive.css') }}">
    
    <!-- Modular CSS Structure -->
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/variables.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/animations.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/admin-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/admin-sidebar.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/admin-header.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/admin-dashboard.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/admin-cards.css') }}">

    @stack('admin-styles')

    <!-- Feather Icons JS -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="admin-theme">
    <!-- Loader -->
    <div class="loader-wrapper">
        <div class="loader-index"><span></span></div>
        <svg>
            <defs></defs>
            <filter id="goo">
                <fegaussianblur in="SourceGraphic" stddeviation="11" result="blur"></fegaussianblur>
                <fecolormatrix in="blur" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 19 -9" result="goo"></fecolormatrix>
            </filter>
        </svg>
    </div>
    <!-- Tap on top -->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>

    <!-- Page Wrapper -->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-wrapper row m-0">
                <form class="form-inline search-full col" action="#" method="get">
                    <div class="form-group w-100">
                        <div class="Typeahead Typeahead--twitterUsers">
                            <div class="u-posRelative">
                                <input class="demo-input Typeahead-input form-control-plaintext w-100" type="text" placeholder="Search Admin Workspace..." name="q" title="" autofocus>
                                <div class="spinner-border Typeahead-spinner" role="status"><span class="sr-only">Loading...</span></div>
                                <i class="close-search" data-feather="x"></i>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="header-logo-wrapper col-auto p-0">
                    <div class="logo-wrapper">
                        <a href="{{ url('admin/dashboard') }}">
                            <img class="img-fluid" src="{{ asset('logo/logo.png') }}" alt="MLM Book Admin" style="height: 36px;">
                        </a>
                    </div>
                    <div class="toggle-sidebar">
                        <i class="status_toggle middle sidebar-toggle" data-feather="align-center"></i>
                    </div>
                </div>

                <div class="left-header col-xxl-5 col-xl-6 col-lg-5 col-md-4 col-sm-3 p-0">
                    <div class="notification-slider">
                        <div class="d-flex h-100 align-items-center">
                            <span class="badge bg-primary me-2">Admin Portal</span>
                            <h6 class="mb-0 f-w-400">MLM Book Workspace</h6>
                        </div>
                    </div>
                </div>

                <div class="nav-right col-xxl-7 col-xl-6 col-md-7 col-8 pull-right right-header p-0 ms-auto">
                    <ul class="nav-menus">
                        <li>
                            <div class="mode mt-2">
                                <i data-feather="moon"></i>
                            </div>
                        </li>

                        <!-- Notifications UI Placeholder -->
                        <li class="onhover-dropdown">
                            <div class="notification-box">
                                <i data-feather="bell"></i>
                                <span class="badge rounded-pill badge-secondary">0</span>
                            </div>
                            <div class="onhover-show-div notification-dropdown">
                                <h6 class="f-18 mb-0 dropdown-title">Notifications</h6>
                                <ul>
                                    <li class="p-3 text-center text-muted">
                                        <p class="mb-0">No new notifications</p>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Admin Profile Dropdown -->
                        <li class="profile-nav onhover-dropdown pe-0 py-0">
                            <div class="d-flex profile-media">
                                <img class="img-fluid rounded-circle" src="{{ asset('admin_assets/images/avtar/7.jpg') }}" alt="Admin Avatar" style="width:40px; height:40px;">
                                <div class="flex-grow-1">
                                    <span>{{ Auth::guard('web')->user()->name ?? 'Administrator' }}</span>
                                    <p class="mb-0">Admin <i data-feather="chevron-down" class="middle"></i></p>
                                </div>
                            </div>
                            <ul class="profile-dropdown onhover-show-div">
                                <li>
                                    <a href="#">
                                        <i data-feather="user"></i><span>Admin Profile</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <i data-feather="settings"></i><span>Settings</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                                        <i data-feather="log-out"></i><span>Log out</span>
                                    </a>
                                    <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Page Header Ends -->

        <!-- Page Body Start -->
        <div class="page-body-wrapper">
            <!-- Admin Sidebar Start -->
            <div class="sidebar-wrapper" data-sidebar-layout="stroke-svg">
                <div>
                    <div class="logo-wrapper text-center py-3">
                        <a href="{{ url('admin/dashboard') }}">
                            <h4 class="fw-bold text-primary mb-0">MLM Book</h4>
                            <small class="text-muted">ADMIN PANEL</small>
                        </a>
                        <div class="back-btn"><i data-feather="chevron-left"></i></div>
                    </div>
                    <nav class="sidebar-main">
                        <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
                        <div id="sidebar-menu">
                            <ul class="sidebar-links" id="simple-bar">
                                <li class="sidebar-list">
                                    <a class="sidebar-link" href="{{ url('admin/dashboard') }}">
                                        <i data-feather="home" class="stroke-icon"></i>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="sidebar-list">
                                    <a class="sidebar-link sidebar-title" href="#">
                                        <i data-feather="users" class="stroke-icon"></i>
                                        <span>Members Management</span>
                                    </a>
                                    <ul class="sidebar-submenu">
                                        <li><a href="#">All Members</a></li>
                                        <li><a href="#">Verification Requests</a></li>
                                    </ul>
                                </li>
                                <li class="sidebar-list">
                                    <a class="sidebar-link sidebar-title" href="#">
                                        <i data-feather="briefcase" class="stroke-icon"></i>
                                        <span>Business Pages</span>
                                    </a>
                                    <ul class="sidebar-submenu">
                                        <li><a href="#">Business Directory</a></li>
                                        <li><a href="#">Reviews Moderation</a></li>
                                    </ul>
                                </li>
                                <li class="sidebar-list">
                                    <a class="sidebar-link sidebar-title" href="#">
                                        <i data-feather="globe" class="stroke-icon"></i>
                                        <span>Communities</span>
                                    </a>
                                    <ul class="sidebar-submenu">
                                        <li><a href="#">All Communities</a></li>
                                        <li><a href="#">Moderation Logs</a></li>
                                    </ul>
                                </li>
                                <li class="sidebar-list">
                                    <a class="sidebar-link sidebar-title" href="#">
                                        <i data-feather="settings" class="stroke-icon"></i>
                                        <span>System Settings</span>
                                    </a>
                                    <ul class="sidebar-submenu">
                                        <li><a href="#">General Settings</a></li>
                                        <li><a href="#">Security & Logs</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <div class="right-arrow" id="right-arrow"></div>
                    </nav>
                </div>
            </div>
            <!-- Admin Sidebar Ends -->
