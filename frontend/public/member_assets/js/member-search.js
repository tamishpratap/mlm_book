(function () {
    'use strict';

    const allowedTypes = ['all', 'members', 'pages', 'groups', 'posts', 'events'];

    function initializeHeaderSearch() {
        const form = document.querySelector('[data-header-search]');

        if (! form) {
            return;
        }

        const input = form.querySelector('[data-header-search-input]');
        const clearButton = form.querySelector('[data-member-search-clear]');
        const isSearchPage = form.dataset.searchPage === 'true';
        let navigationTimer;

        if (! input || ! clearButton) {
            return;
        }

        const updateClearButton = function () {
            clearButton.hidden = input.value.length === 0;
        };

        const openSearchPage = function () {
            const query = input.value.trim();
            const url = query
                ? form.dataset.searchUrl + '?q=' + encodeURIComponent(query) + '&type=all'
                : form.dataset.searchUrl;

            window.location.assign(url);
        };

        const syncMainSearch = function (searchImmediately) {
            const mainInput = document.querySelector('#member-search-query');

            if (! mainInput) {
                return;
            }

            mainInput.value = input.value;

            if (searchImmediately && mainInput.form) {
                mainInput.dispatchEvent(new Event('input', { bubbles: true }));
                mainInput.form.requestSubmit();
            } else {
                mainInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };

        input.addEventListener('input', function () {
            window.clearTimeout(navigationTimer);
            updateClearButton();

            if (isSearchPage) {
                syncMainSearch(false);
                return;
            }

            if (input.value.trim().length < 2) {
                return;
            }

            navigationTimer = window.setTimeout(openSearchPage, 400);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            window.clearTimeout(navigationTimer);

            if (isSearchPage) {
                syncMainSearch(true);
            } else {
                openSearchPage();
            }
        });

        clearButton.addEventListener('click', function () {
            window.clearTimeout(navigationTimer);
            input.value = '';
            updateClearButton();

            if (isSearchPage) {
                syncMainSearch(false);
            }

            input.focus();
        });

        updateClearButton();
    }

    function initializeLiveSearch() {
        const searchPage = document.querySelector('[data-live-member-search]');

        if (! searchPage) {
            return;
        }

        const form = searchPage.querySelector('.member-search-form');
        const input = form?.querySelector('[data-member-search-input]');
        const clearButton = form?.querySelector('[data-member-search-clear]');
        const typeInput = form?.querySelector('[data-search-type-input]');
        const results = searchPage.querySelector('[data-search-results]');
        const announcer = searchPage.querySelector('[data-search-announcer]');
        const tabs = Array.from(searchPage.querySelectorAll('[data-search-tab]'));
        const headerInput = document.querySelector('[data-header-search-input]');
        const headerClearButton = document.querySelector('[data-header-search] [data-member-search-clear]');

        if (! form || ! input || ! clearButton || ! typeInput || ! results || tabs.length === 0) {
            return;
        }

        let activeType = allowedTypes.includes(searchPage.dataset.activeType)
            ? searchPage.dataset.activeType
            : 'all';
        let debounceTimer;
        let activeController;
        let requestVersion = 0;

        const createIcons = function () {
            if (window.lucide) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
            }
        };

        const updateClearButton = function () {
            clearButton.hidden = input.value.length === 0;
        };

        const syncHeaderInput = function () {
            if (! headerInput) {
                return;
            }

            headerInput.value = input.value;

            if (headerClearButton) {
                headerClearButton.hidden = headerInput.value.length === 0;
            }
        };

        const updateCounts = function (counts) {
            Object.entries(counts).forEach(function (entry) {
                const count = searchPage.querySelector('[data-search-count="' + entry[0] + '"]');

                if (count) {
                    count.textContent = entry[1];
                }
            });
        };

        const resetCounts = function () {
            updateCounts({ all: 0, members: 0, pages: 0, groups: 0, posts: 0, events: 0 });
        };

        const setActiveType = function (type) {
            activeType = allowedTypes.includes(type) ? type : 'all';
            typeInput.value = activeType;

            tabs.forEach(function (tab) {
                const isActive = tab.dataset.searchTab === activeType;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
            });
        };

        const replaceWithTemplate = function (templateName, message) {
            const template = searchPage.querySelector('[data-search-' + templateName + '-template]');

            if (! template) {
                return;
            }

            results.replaceChildren(template.content.cloneNode(true));
            results.removeAttribute('aria-busy');
            announcer.textContent = message;
            createIcons();
        };

        const updateUrl = function (page, historyMode) {
            if (! historyMode) {
                return;
            }

            const url = new URL(searchPage.dataset.indexUrl, window.location.origin);
            const query = input.value.trim();

            if (query) {
                url.searchParams.set('q', query);
            }

            if (activeType !== 'all' || query) {
                url.searchParams.set('type', activeType);
            }

            if (page > 1) {
                url.searchParams.set('page', page);
            }

            window.history[historyMode + 'State'](
                { query: query, type: activeType, page: page },
                '',
                url,
            );
        };

        const cancelPendingRequest = function () {
            window.clearTimeout(debounceTimer);
            requestVersion += 1;

            if (activeController) {
                activeController.abort();
                activeController = undefined;
            }
        };

        const showLocalState = function (historyMode) {
            const query = input.value.trim();

            cancelPendingRequest();
            resetCounts();
            updateUrl(1, historyMode);

            if (query === '') {
                replaceWithTemplate('initial', 'Search is ready.');
            } else {
                replaceWithTemplate('short', 'Enter at least 2 characters to search.');
            }
        };

        const showSkeleton = function () {
            const template = searchPage.querySelector('[data-search-skeleton-template]');

            if (template) {
                results.replaceChildren(template.content.cloneNode(true));
                results.setAttribute('aria-busy', 'true');
                announcer.textContent = 'Loading search results.';
            }
        };

        const loadResults = async function (page, historyMode, shouldScroll) {
            const query = input.value.trim();

            if (query.length < 2 && activeType !== 'groups') {
                showLocalState(historyMode);
                return;
            }

            cancelPendingRequest();
            const currentVersion = ++requestVersion;
            activeController = typeof AbortController === 'undefined'
                ? undefined
                : new AbortController();

            showSkeleton();
            updateUrl(page, historyMode);

            const url = new URL(searchPage.dataset.resultsUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('type', activeType);
            url.searchParams.set('page', page);

            try {
                const options = {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                };

                if (activeController) {
                    options.signal = activeController.signal;
                }

                const response = await fetch(url, options);

                if (! response.ok) {
                    throw new Error('Search request failed.');
                }

                const data = await response.json();

                if (currentVersion !== requestVersion) {
                    return;
                }

                results.innerHTML = data.html + data.pagination;
                results.removeAttribute('aria-busy');
                updateCounts(data.counts);
                announcer.textContent = data.counts[activeType] + ' search results loaded.';
                createIcons();

                if (shouldScroll) {
                    results.scrollIntoView({
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                        block: 'start',
                    });
                }
            } catch (error) {
                if (error.name === 'AbortError' || currentVersion !== requestVersion) {
                    return;
                }

                replaceWithTemplate('error', 'Search results could not be loaded.');
            } finally {
                if (currentVersion === requestVersion) {
                    activeController = undefined;
                }
            }
        };

        const scheduleSearch = function () {
            updateClearButton();
            syncHeaderInput();
            cancelPendingRequest();
            updateUrl(1, 'replace');

            if (input.value.trim().length < 2 && activeType !== 'groups') {
                showLocalState(null);
                return;
            }

            showSkeleton();
            debounceTimer = window.setTimeout(function () {
                loadResults(1, null, false);
            }, 300);
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            loadResults(1, 'push', false);
        });

        input.addEventListener('input', scheduleSearch);

        clearButton.addEventListener('click', function () {
            input.value = '';
            updateClearButton();
            syncHeaderInput();
            showLocalState('replace');
            input.focus();
        });

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                setActiveType(tab.dataset.searchTab);

                if (input.value.trim().length < 2 && activeType !== 'groups') {
                    showLocalState('push');
                } else {
                    loadResults(1, 'push', false);
                }
            });

            tab.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                    return;
                }

                event.preventDefault();
                const direction = event.key === 'ArrowRight' ? 1 : -1;
                const nextIndex = (index + direction + tabs.length) % tabs.length;
                tabs[nextIndex].focus();
                tabs[nextIndex].click();
            });
        });

        results.addEventListener('click', function (event) {
            const typeLink = event.target.closest('[data-search-type]');

            if (typeLink) {
                event.preventDefault();
                setActiveType(typeLink.dataset.searchType);
                loadResults(1, 'push', false);
                return;
            }

            const paginationLink = event.target.closest('.member-search-pagination a');

            if (paginationLink) {
                event.preventDefault();
                const page = Number.parseInt(new URL(paginationLink.href).searchParams.get('page') || '1', 10);
                loadResults(page, 'push', true);
                return;
            }

            if (event.target.closest('[data-search-retry]')) {
                loadResults(Number.parseInt(new URL(window.location.href).searchParams.get('page') || '1', 10), null, false);
            }
        });

        window.addEventListener('popstate', function () {
            const url = new URL(window.location.href);
            const restoredType = url.searchParams.get('type') || 'all';
            const restoredPage = Number.parseInt(url.searchParams.get('page') || '1', 10);

            input.value = url.searchParams.get('q') || '';
            setActiveType(restoredType);
            updateClearButton();
            syncHeaderInput();

            if (input.value.trim().length < 2) {
                showLocalState(null);
            } else {
                loadResults(restoredPage, null, false);
            }
        });

        setActiveType(activeType);
        updateClearButton();
        syncHeaderInput();
        window.history.replaceState(
            { query: input.value.trim(), type: activeType, page: Number.parseInt(new URL(window.location.href).searchParams.get('page') || '1', 10) },
            '',
            window.location.href,
        );

        try {
            input.focus({ preventScroll: true });
        } catch (error) {
            input.focus();
        }

        input.setSelectionRange(input.value.length, input.value.length);

        if (input.value.trim().length >= 2 || activeType === 'groups') {
            loadResults(
                Number.parseInt(new URL(window.location.href).searchParams.get('page') || '1', 10),
                null,
                false,
            );
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeHeaderSearch();
        initializeLiveSearch();
    });
}());
