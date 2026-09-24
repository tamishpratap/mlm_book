@php
    $emptyStates = [
        'initial' => [
            'icon' => 'search',
            'heading' => 'Find on MLM Book',
            'description' => 'Search for Members, Business Pages, Communities, Posts and Events.',
        ],
        'short' => [
            'icon' => 'search',
            'heading' => 'Keep typing',
            'description' => 'Enter at least 2 characters to search.',
        ],
        'empty' => [
            'icon' => 'search-x',
            'heading' => 'No results found',
            'description' => "We could not find anything matching ‘{$search}’ in this category.",
        ],
        'empty_communities' => [
            'icon' => 'search-x',
            'heading' => 'No Communities Found',
            'description' => $search ? "We could not find any communities matching ‘{$search}’." : "No communities have been created yet.",
        ],
        'empty_business_pages' => [
            'icon' => 'search-x',
            'heading' => 'No Business Pages Found',
            'description' => $search ? "We could not find any business pages matching ‘{$search}’." : "No business pages have been created yet.",
        ],
        'empty_events' => [
            'icon' => 'calendar-x',
            'heading' => 'No Events Found',
            'description' => $search ? "We could not find any events matching ‘{$search}’." : "No events have been created yet.",
        ],
        'empty_posts' => [
            'icon' => 'file-x',
            'heading' => 'No Posts Found',
            'description' => $search ? "We could not find any posts matching ‘{$search}’." : "No posts have been created yet.",
        ],
        'unavailable' => [
            'icon' => 'construction',
            'heading' => 'Category unavailable',
            'description' => 'This search category is not available yet.',
        ],
    ];
    $emptyState = $emptyStates[$mode];
@endphp

<section class="member-search-state {{ $mode === 'short' ? 'member-search-state--compact' : '' }}" role="status">
    <span class="member-search-state__icon"><i data-lucide="{{ $emptyState['icon'] }}" aria-hidden="true"></i></span>
    <h2>{{ $emptyState['heading'] }}</h2>
    <p>{{ $emptyState['description'] }}</p>
</section>

