@props([
    'name' => '',
    'selected' => '',
    'placeholder' => null,
    'options' => [],
])

<select name="{{ $name }}" {{ $attributes->merge(['class' => 'form-select' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @if(!empty($options))
        @foreach($options as $val => $text)
            <option value="{{ $val }}" {{ old($name, $selected) == $val ? 'selected' : '' }}>{{ $text }}</option>
        @endforeach
    @endif
    {{ $slot }}
</select>
@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
