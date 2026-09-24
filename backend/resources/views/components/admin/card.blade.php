@props([
    'title' => null,
    'subtitle' => null,
    'headerAction' => null,
])

<div class="card border-0 shadow-sm mb-4">
    @if($title || isset($headerAction))
        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3">
            <div>
                @if($title)<h5 class="card-title mb-0 fw-semibold">{{ $title }}</h5>@endif
                @if($subtitle)<p class="text-muted small mb-0">{{ $subtitle }}</p>@endif
            </div>
            @if(isset($headerAction) && $headerAction)
                <div>
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
