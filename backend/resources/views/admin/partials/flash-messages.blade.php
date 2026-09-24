<!-- Session Flash Messages Partial -->
@if (session()->has('success') || session()->has('succMsg') || session()->has('status'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i>
        {{ session('success') ?? session('succMsg') ?? session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session()->has('error') || session()->has('FailMsg'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-circle me-2"></i>
        {{ session('error') ?? session('FailMsg') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session()->has('warning'))
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i>
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session()->has('info'))
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-info-circle me-2"></i>
        {{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
