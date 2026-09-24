<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MLM Book Admin Portal">
    <title>@yield('title', 'Admin Dashboard') || {{ config('app.name', 'MLM Book') }}</title>

    <!-- Favicon Icon (reusing Member logo) -->
    <link rel="icon" type="image/png" href="{{ asset('logo/logo.png') }}" />
    <link rel="shortcut icon" href="{{ asset('logo/logo.png') }}" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Vendor CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/vendors/feather-icon.css') }}">

    <!-- Modular Custom Admin CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/variables.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/animations.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/sidebar.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/header.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/cards.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/buttons.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/forms.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/tables.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/modals.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('admin_assets/css/responsive.css') }}">

    @stack('admin-styles')

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="admin-theme">
    <!-- Page Wrapper -->
    <div class="page-wrapper" id="pageWrapper">
        @include('admin.partials.header')

        <!-- Page Body Start -->
        <div class="page-body-wrapper">
            @include('admin.partials.sidebar')

            <main class="page-body" id="adminMainContent">
                <div class="container-fluid p-0">
                    @include('admin.partials.breadcrumb')
                    @include('admin.partials.flash-messages')

                    @yield('content')
                    {{ $slot ?? '' }}
                </div>
            </main>

            @include('admin.partials.footer')
        </div>
    </div>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
</body>
</html>
