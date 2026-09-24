@props([
    'icon' => 'folder',
    'title' => 'No Data Found',
    'description' => '',
])

<div class="text-center py-5">
    <div class="mb-3 text-muted">
        <i data-feather="{{ $icon }}" style="width: 48px; height: 48px; stroke-width: 1.5;"></i>
    </div>
    <h6 class="fw-semibold text-dark">{{ $title }}</h6>
    @if($description)
        <p class="text-muted small mb-0">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
