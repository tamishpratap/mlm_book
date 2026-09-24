<!-- Dynamic Page Title Partial -->
<div class="page-title mb-4">
    <div class="row align-items-center g-2">
        <div class="col-12">
            <h3 class="fw-bold mb-1">@yield('title', 'Admin Dashboard')</h3>
            @if(View::hasSection('page-subtitle'))
                <p class="text-muted mb-0 small">@yield('page-subtitle')</p>
            @endif
        </div>
    </div>
</div>
