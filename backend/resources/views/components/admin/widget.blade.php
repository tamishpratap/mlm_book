@props([
    'title' => '',
    'action' => null,
])

<div class="card h-100 mb-0">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
        <h5 class="card-title mb-0 fw-semibold">{{ $title }}</h5>
        @if(isset($action))
            <div>{{ $action }}</div>
        @endif
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
