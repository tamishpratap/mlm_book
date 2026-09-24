@props([
    'variant' => 'primary',
    'light' => false,
])

<span {{ $attributes->merge(['class' => 'badge ' . ($light ? 'bg-light-' . $variant . ' text-' . $variant : 'bg-' . $variant)]) }}>
    {{ $slot }}
</span>
