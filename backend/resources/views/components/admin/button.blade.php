@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => '',
    'icon' => null,
])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'btn btn-' . $variant . ($size ? ' btn-' . $size : '')]) }}>
    @if($icon)<i data-feather="{{ $icon }}" class="me-1"></i>@endif
    {{ $slot }}
</button>
