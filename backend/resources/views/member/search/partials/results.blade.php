@if ($state === 'initial')
    @include('member.search.partials.empty', ['mode' => 'initial'])
@elseif ($state === 'short')
    @include('member.search.partials.empty', ['mode' => 'short'])
@elseif ($state === 'unavailable')
    @include('member.search.partials.empty', ['mode' => 'unavailable'])
@elseif ($state === 'empty')
    @if ($type === 'groups')
        @include('member.search.partials.empty', ['mode' => 'empty_communities'])
    @elseif ($type === 'pages')
        @include('member.search.partials.empty', ['mode' => 'empty_business_pages'])
    @elseif ($type === 'events')
        @include('member.search.partials.empty', ['mode' => 'empty_events'])
    @elseif ($type === 'posts')
        @include('member.search.partials.empty', ['mode' => 'empty_posts'])
    @else
        @include('member.search.partials.empty', ['mode' => 'empty'])
    @endif
@elseif ($type === 'all')
    <section class="member-search-results" aria-labelledby="member-search-summary">
        <header class="member-search-results__header">
            <div>
                <h2 id="member-search-summary">Results for “{{ $search }}”</h2>
                <p>{{ $counts['all'] }} {{ \Illuminate\Support\Str::plural('result', $counts['all']) }} found</p>
            </div>
            <span class="member-search-results__badge">All</span>
        </header>

        @if ($members && $members->isNotEmpty())
            <section class="member-search-result-group" aria-labelledby="people-results-heading">
                <header class="member-search-result-group__header">
                    <div>
                        <h3 id="people-results-heading">People</h3>
                        <span>{{ $counts['members'] }}</span>
                    </div>
                    <a href="{{ route('member.search', ['q' => $search, 'type' => 'members']) }}" data-search-type="members">See all</a>
                </header>
                @include('member.search.partials.members', ['resultMembers' => $members])
            </section>
        @endif

        @if ($communities && $communities->isNotEmpty())
            <section class="member-search-result-group" aria-labelledby="communities-results-heading" style="margin-top: 24px;">
                <header class="member-search-result-group__header">
                    <div>
                        <h3 id="communities-results-heading">Communities</h3>
                        <span>{{ $counts['groups'] }}</span>
                    </div>
                    <a href="{{ route('member.search', ['q' => $search, 'type' => 'groups']) }}" data-search-type="groups">See all</a>
                </header>
                <div class="community-grid">
                    @foreach ($communities as $community)
                        @include('member.community.partials.discovery-card', compact('community'))
                    @endforeach
                </div>
            </section>
        @endif

        @if ($pages && $pages->isNotEmpty())
            <section class="member-search-result-group" aria-labelledby="pages-results-heading" style="margin-top: 24px;">
                <header class="member-search-result-group__header">
                    <div>
                        <h3 id="pages-results-heading">Business Pages</h3>
                        <span>{{ $counts['pages'] }}</span>
                    </div>
                    <a href="{{ route('member.search', ['q' => $search, 'type' => 'pages']) }}" data-search-type="pages">See all</a>
                </header>
                <div class="community-grid">
                    @foreach ($pages as $businessPage)
                        @include('member.business-pages.partials.card', compact('businessPage'))
                    @endforeach
                </div>
            </section>
        @endif

        @if ($events && $events->isNotEmpty())
            <section class="member-search-result-group" aria-labelledby="events-results-heading" style="margin-top: 24px;">
                <header class="member-search-result-group__header">
                    <div>
                        <h3 id="events-results-heading">Events</h3>
                        <span>{{ $counts['events'] }}</span>
                    </div>
                    <a href="{{ route('member.search', ['q' => $search, 'type' => 'events']) }}" data-search-type="events">See all</a>
                </header>
                <div class="groups-grid">
                    @foreach ($events as $event)
                        @include('member.events.partials.event_card', compact('event'))
                    @endforeach
                </div>
            </section>
        @endif

        @if ($posts && $posts->isNotEmpty())
            <section class="member-search-result-group" aria-labelledby="posts-results-heading" style="margin-top: 24px;">
                <header class="member-search-result-group__header">
                    <div>
                        <h3 id="posts-results-heading">Posts</h3>
                        <span>{{ $counts['posts'] }}</span>
                    </div>
                    <a href="{{ route('member.search', ['q' => $search, 'type' => 'posts']) }}" data-search-type="posts">See all</a>
                </header>
                <div class="posts-feed" style="display: flex; flex-direction: column; gap: 16px;">
                    @foreach ($posts as $post)
                        @include('member.posts.partials.card', compact('post'))
                    @endforeach
                </div>
            </section>
        @endif
    </section>
@elseif ($type === 'groups')
    <section class="member-search-results" aria-labelledby="member-search-summary">
        <header class="member-search-results__header">
            <div>
                <h2 id="member-search-summary">
                    @if ($search !== '')
                        Communities matching “{{ $search }}”
                    @else
                        All Communities
                    @endif
                </h2>
                <p>{{ $counts['groups'] }} {{ \Illuminate\Support\Str::plural('community', $counts['groups']) }} found</p>
            </div>
            <span class="member-search-results__badge">Communities</span>
        </header>

        <div class="community-grid">
            @foreach ($communities as $community)
                @include('member.community.partials.discovery-card', compact('community'))
            @endforeach
        </div>
    </section>
@elseif ($type === 'pages')
    <section class="member-search-results" aria-labelledby="member-search-summary">
        <header class="member-search-results__header">
            <div>
                <h2 id="member-search-summary">
                    @if ($search !== '')
                        Business Pages matching “{{ $search }}”
                    @else
                        All Business Pages
                    @endif
                </h2>
                <p>{{ $counts['pages'] }} {{ \Illuminate\Support\Str::plural('business page', $counts['pages']) }} found</p>
            </div>
            <span class="member-search-results__badge">Business Pages</span>
        </header>

        <div class="community-grid">
            @foreach ($pages as $businessPage)
                @include('member.business-pages.partials.card', compact('businessPage'))
            @endforeach
        </div>
    </section>
@elseif ($type === 'events')
    <section class="member-search-results" aria-labelledby="member-search-summary">
        <header class="member-search-results__header">
            <div>
                <h2 id="member-search-summary">
                    @if ($search !== '')
                        Events matching “{{ $search }}”
                    @else
                        All Events
                    @endif
                </h2>
                <p>{{ $counts['events'] }} {{ \Illuminate\Support\Str::plural('event', $counts['events']) }} found</p>
            </div>
            <span class="member-search-results__badge">Events</span>
        </header>

        <div class="groups-grid">
            @foreach ($events as $event)
                @include('member.events.partials.event_card', compact('event'))
            @endforeach
        </div>
    </section>
@elseif ($type === 'posts')
    <section class="member-search-results" aria-labelledby="member-search-summary">
        <header class="member-search-results__header">
            <div>
                <h2 id="member-search-summary">
                    @if ($search !== '')
                        Posts matching “{{ $search }}”
                    @else
                        All Posts
                    @endif
                </h2>
                <p>{{ $counts['posts'] }} {{ \Illuminate\Support\Str::plural('post', $counts['posts']) }} found</p>
            </div>
            <span class="member-search-results__badge">Posts</span>
        </header>

        <div class="posts-feed" style="display: flex; flex-direction: column; gap: 16px;">
            @foreach ($posts as $post)
                @include('member.posts.partials.card', compact('post'))
            @endforeach
        </div>
    </section>
@else
    <section class="member-search-results" aria-labelledby="member-search-summary">
        <header class="member-search-results__header">
            <div>
                <h2 id="member-search-summary">People matching “{{ $search }}”</h2>
                <p>{{ $counts['members'] }} {{ \Illuminate\Support\Str::plural('person', $counts['members']) }} found</p>
            </div>
            <span class="member-search-results__badge">People</span>
        </header>
        @include('member.search.partials.members', ['resultMembers' => $members])
    </section>
@endif

