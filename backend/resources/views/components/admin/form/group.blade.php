@props([
    'label' => null,
    'for' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'form-group mb-3']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="form-label fw-semibold small text-muted">
            {{ $label }}
            @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    {{ $slot }}
</div>
