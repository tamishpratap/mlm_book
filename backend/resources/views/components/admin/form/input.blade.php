@props([
    'type' => 'text',
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'icon' => null,
])

@if($icon)
    <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i data-feather="{{ $icon }}"></i></span>
        <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'form-control border-start-0' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@else
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
@endif
