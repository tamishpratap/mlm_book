@props([
    'paginator' => null,
])

@if($paginator && $paginator->total() > 0)
    @php
        $p = $paginator->withQueryString();
    @endphp
    <div class="admin-pagination-wrapper">
        <div class="admin-pagination-info">
            Showing <span class="fw-semibold">{{ $p->firstItem() ?? 1 }}</span> to <span class="fw-semibold">{{ $p->lastItem() ?? $p->total() }}</span> of <span class="fw-semibold">{{ $p->total() }}</span> results
        </div>

        @if($p->hasPages())
            <nav role="navigation" aria-label="Pagination Navigation" class="admin-pagination-nav">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($p->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="Previous">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $p->previousPageUrl() }}" rel="prev" aria-label="Previous">&lsaquo;</a>
                        </li>
                    @endif

                    {{-- Sliding Window Page Numbers --}}
                    @php
                        $currentPage = $p->currentPage();
                        $lastPage = $p->lastPage();
                        $start = max(1, $currentPage - 2);
                        $end = min($lastPage, $currentPage + 2);
                    @endphp

                    @if ($start > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $p->url(1) }}">1</a>
                        </li>
                        @if ($start > 2)
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">&hellip;</span></li>
                        @endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page == $currentPage)
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $p->url($page) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endfor

                    @if ($end < $lastPage)
                        @if ($end < $lastPage - 1)
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">&hellip;</span></li>
                        @endif
                        <li class="page-item">
                            <a class="page-link" href="{{ $p->url($lastPage) }}">{{ $lastPage }}</a>
                        </li>
                    @endif

                    {{-- Next Page Link --}}
                    @if ($p->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $p->nextPageUrl() }}" rel="next" aria-label="Next">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="Next">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
@endif
