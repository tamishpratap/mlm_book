@extends('member.layouts.app')

@section('title', 'Search MLM Book')

@section('content')
    @php
        $tabs = [
            'all' => ['label' => 'All', 'icon' => 'search'],
            'members' => ['label' => 'People', 'icon' => 'users'],
            'pages' => ['label' => 'Business Pages', 'icon' => 'flag'],
            'groups' => ['label' => 'Communities', 'icon' => 'users-round'],
            'posts' => ['label' => 'Posts', 'icon' => 'file-text'],
            'events' => ['label' => 'Events', 'icon' => 'calendar-days'],
        ];
    @endphp

    <div
        class="member-search-page"
        data-live-member-search
        data-results-url="{{ route('member.search.results') }}"
        data-index-url="{{ route('member.search') }}"
        data-active-type="{{ $type }}"
    >
        <section class="member-card member-search-panel" aria-labelledby="member-search-heading">
            <header class="member-search-panel__header">
                <div>
                    <span class="member-search-panel__eyebrow">Discover</span>
                    <h1 id="member-search-heading">Search</h1>
                    <p>Find people and explore everything available on MLM Book.</p>
                </div>
            </header>

            <form class="member-search-form" method="GET" action="{{ route('member.search') }}" role="search" data-member-search>
                <input type="hidden" name="type" value="{{ $type }}" data-search-type-input>
                <button class="member-search-form__submit" type="submit" aria-label="Search MLM Book">
                    <i data-lucide="search" aria-hidden="true"></i>
                </button>
                <input
                    id="member-search-query"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search MLM Book..."
                    aria-label="Search MLM Book"
                    maxlength="100"
                    autocomplete="off"
                    autofocus
                    data-member-search-input
                >
                <button class="member-search-form__clear" type="button" aria-label="Clear search" data-member-search-clear @if ($search === '') hidden @endif>
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </form>

            <div class="member-search-tabs" role="tablist" aria-label="Search categories">
                @foreach ($tabs as $tabType => $tab)
                    <a
                        class="member-search-tab {{ $type === $tabType ? 'is-active' : '' }}"
                        href="{{ route('member.search', ['q' => $search ?: null, 'type' => $tabType]) }}"
                        role="tab"
                        aria-selected="{{ $type === $tabType ? 'true' : 'false' }}"
                        tabindex="{{ $type === $tabType ? '0' : '-1' }}"
                        data-search-tab="{{ $tabType }}"
                    >
                        <i data-lucide="{{ $tab['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $tab['label'] }}</span>
                        <small data-search-count="{{ $tabType }}">{{ $counts[$tabType] }}</small>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="member-search-live-region" aria-live="polite" aria-atomic="true" data-search-announcer></div>

        <div class="member-search-results-container" data-search-results>
            @include('member.search.partials.results')

            @if ($type === 'members' && $members?->hasPages())
                {{ $members->links('member.search.pagination') }}
            @elseif ($type === 'groups' && $communities?->hasPages())
                {{ $communities->links('member.search.pagination') }}
            @elseif ($type === 'pages' && $pages?->hasPages())
                {{ $pages->links('member.search.pagination') }}
            @elseif ($type === 'events' && $events?->hasPages())
                {{ $events->links('member.search.pagination') }}
            @elseif ($type === 'posts' && $posts?->hasPages())
                {{ $posts->links('member.search.pagination') }}
            @endif
        </div>

        <template data-search-skeleton-template>
            @include('member.search.partials.skeleton')
        </template>

        <template data-search-initial-template>
            @include('member.search.partials.empty', ['mode' => 'initial'])
        </template>

        <template data-search-short-template>
            @include('member.search.partials.empty', ['mode' => 'short'])
        </template>

        <template data-search-error-template>
            <section class="member-search-state" role="alert">
                <span class="member-search-state__icon"><i data-lucide="circle-alert" aria-hidden="true"></i></span>
                <h2>Search unavailable</h2>
                <p>Search results could not be loaded. Please try again.</p>
                <button class="member-search-retry" type="button" data-search-retry>Retry</button>
            </section>
        </template>
    </div>
@endsection
