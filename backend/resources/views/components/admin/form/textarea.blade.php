@props([
    'name' => '',
    'value' => '',
    'placeholder' => '',
    'rows' => 3,
])

<textarea name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}>{{ old($name, $value) }}</textarea>
@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
