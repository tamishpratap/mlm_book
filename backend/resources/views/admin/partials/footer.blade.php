<!-- Admin Footer Partial -->
<footer class="footer">
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-12 text-center text-muted">
                <p class="mb-0 small">Copyright &copy; {{ date('Y') }} {{ config('app.name', 'MLM Book') }} Admin Workspace. All Rights Reserved.</p>
            </div>
        </div>
    </div>
</footer>

<!-- Core Script Libraries -->
<script src="{{ asset('admin_assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('admin_assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="https://unpkg.com/feather-icons"></script>

<!-- Modular UI JS Files -->
<script src="{{ asset('admin_assets/js/sidebar.js') }}"></script>
<script src="{{ asset('admin_assets/js/theme.js') }}"></script>

@stack('admin-scripts')
