@if ($paginator->hasPages())
    <nav class="member-search-pagination" role="navigation" aria-label="Member search pagination">
        <p>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>

        <div class="member-search-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true"><i data-lucide="chevron-left" aria-hidden="true"></i><span>Previous</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"><i data-lucide="chevron-left" aria-hidden="true"></i><span>Previous</span></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="member-search-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="member-search-pagination__page" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"><span>Next</span><i data-lucide="chevron-right" aria-hidden="true"></i></a>
            @else
                <span class="is-disabled" aria-disabled="true"><span>Next</span><i data-lucide="chevron-right" aria-hidden="true"></i></span>
            @endif
        </div>
    </nav>
@endif
