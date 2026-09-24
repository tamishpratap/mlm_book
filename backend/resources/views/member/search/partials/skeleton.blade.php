<section class="member-search-skeleton" aria-label="Loading search results" role="status">
    <span class="member-search-skeleton__label">Loading search results...</span>
    @for ($card = 0; $card < 4; $card++)
        <div class="member-search-skeleton__card" aria-hidden="true">
            <span class="member-search-skeleton__avatar"></span>
            <span class="member-search-skeleton__copy">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>
    @endfor
</section>
