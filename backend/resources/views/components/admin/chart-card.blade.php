@props([
    'title' => '',
    'subtitle' => null,
    'action' => null,
])

<div class="card h-100 mb-0">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
        <div>
            <h5 class="card-title mb-0 fw-semibold">{{ $title }}</h5>
            @if($subtitle)<p class="text-muted small mb-0 mt-1">{{ $subtitle }}</p>@endif
        </div>
        @if(isset($action) && $action)
            <div>
                {{ $action }}
            </div>
        @endif
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
