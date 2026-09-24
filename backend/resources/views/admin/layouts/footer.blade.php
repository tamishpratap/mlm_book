        <!-- Footer Start -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12 footer-copyright text-center">
                        <p class="mb-0">Copyright © {{ date('Y') }} {{ config('app.name', 'MLM Book') }} Admin Panel. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- jQuery -->
<script src="{{ asset('admin_assets/js/jquery.min.js') }}"></script>
<!-- Bootstrap JS -->
<script src="{{ asset('admin_assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
<!-- Feather Icon JS -->
<script src="{{ asset('admin_assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('admin_assets/js/icons/feather-icon/feather-icon.js') }}"></script>
<!-- Scrollbar JS -->
<script src="{{ asset('admin_assets/js/scrollbar/simplebar.min.js') }}"></script>
<script src="{{ asset('admin_assets/js/scrollbar/custom.js') }}"></script>
<!-- Config & Sidebar JS -->
<script src="{{ asset('admin_assets/js/config.js') }}"></script>
<script src="{{ asset('admin_assets/js/sidebar-menu.js') }}"></script>
<script src="{{ asset('admin_assets/js/sidebar-pin.js') }}"></script>
<!-- Plugins JS -->
<script src="{{ asset('admin_assets/js/slick/slick.min.js') }}"></script>
<script src="{{ asset('admin_assets/js/slick/slick.js') }}"></script>
<script src="{{ asset('admin_assets/js/header-slick.js') }}"></script>
<script src="{{ asset('admin_assets/js/height-equal.js') }}"></script>
<!-- Theme JS -->
<script src="{{ asset('admin_assets/js/script.js') }}"></script>
<script src="{{ asset('admin_assets/js/theme-customizer/customizer.js') }}"></script>

<script>
    function initFeatherIcons() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFeatherIcons);
    } else {
        initFeatherIcons();
    }
</script>

@stack('admin-scripts')
</body>
</html>
