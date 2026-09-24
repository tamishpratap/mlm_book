@props([
    'label' => 'Actions',
    'variant' => 'outline-secondary',
    'size' => 'sm',
])

<div class="dropdown">
    <button class="btn btn-{{ $variant }} btn-{{ $size }} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $label }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        {{ $slot }}
    </ul>
</div>
