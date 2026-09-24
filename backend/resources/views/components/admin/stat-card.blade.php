@props([
    'title' => '',
    'value' => '0',
    'icon' => 'activity',
    'variant' => 'primary',
    'subtext' => null,
    'subtextIcon' => null,
])

<div class="stat-card">
    <div class="stat-card-top">
        <div>
            <div class="stat-card-title">{{ $title }}</div>
            <h3 class="stat-card-value">{{ $value }}</h3>
        </div>
        <div class="stat-card-icon variant-{{ $variant }}">
            <i data-feather="{{ $icon }}"></i>
        </div>
    </div>
    @if($subtext)
        <div class="stat-card-subtext">
            @if($subtextIcon)<i class="fa fa-{{ $subtextIcon }} text-{{ $variant }} me-1"></i>@endif
            <span>{{ $subtext }}</span>
        </div>
    @endif
</div>
