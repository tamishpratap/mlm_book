@props([
    'tabs' => [],
    'active' => '',
])

<ul class="nav nav-tabs border-bottom mb-3" role="tablist">
    @foreach($tabs as $key => $label)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $active == $key ? 'active' : '' }}" id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}" type="button" role="tab">
                {{ $label }}
            </button>
        </li>
    @endforeach
</ul>
<div class="tab-content">
    {{ $slot }}
</div>
