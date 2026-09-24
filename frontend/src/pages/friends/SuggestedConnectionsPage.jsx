import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  UsersRound,
  Search,
  X,
  Globe,
  ChevronDown,
  Users,
  Sparkles,
  UserCheck,
  MapPin,
  ChevronLeft,
  ChevronRight,
  RotateCw,
} from 'lucide-react';
import friendApi from '../../api/friendApi';
import CompactMemberRow from '../../components/friends/CompactMemberRow';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function SuggestedConnectionsPage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const searchQuery = searchParams.get('search') || '';
  const countryQuery = searchParams.get('country') || '';
  const filterQuery = searchParams.get('filter') || 'all';
  const pageQuery = parseInt(searchParams.get('page') || '1', 10);

  const [searchInput, setSearchInput] = useState(searchQuery);
  const [suggestions, setSuggestions] = useState([]);
  const [availableCountries, setAvailableCountries] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;
    const params = {
      page: pageQuery,
      filter: filterQuery !== 'all' ? filterQuery : undefined,
      country: countryQuery || undefined,
      search: searchQuery || undefined,
    };

    friendApi
      .getSuggestions(params)
      .then((data) => {
        if (isMounted) {
          if (data && data.suggestions) {
            const rawList = data.suggestions.data || [];
            const list = rawList.filter((m) => Boolean(m.is_verified || m.mobile_verified_at));
            setSuggestions(list);
            setLastPage(data.suggestions.last_page || 1);
            if (Array.isArray(data.available_countries)) {
              setAvailableCountries(data.available_countries);
            }
          } else {
            setSuggestions([]);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load suggested connections.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [pageQuery, filterQuery, countryQuery, searchQuery]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    const params = {
      page: pageQuery,
      filter: filterQuery !== 'all' ? filterQuery : undefined,
      country: countryQuery || undefined,
      search: searchQuery || undefined,
    };

    friendApi
      .getSuggestions(params)
      .then((data) => {
        if (data && data.suggestions) {
          const rawList = data.suggestions.data || [];
          const list = rawList.filter((m) => Boolean(m.is_verified || m.mobile_verified_at));
          setSuggestions(list);
          setLastPage(data.suggestions.last_page || 1);
          if (Array.isArray(data.available_countries)) {
            setAvailableCountries(data.available_countries);
          }
        }
      })
      .catch(() => {})
      .finally(() => {
        setIsRefreshing(false);
      });
  }, [pageQuery, filterQuery, countryQuery, searchQuery]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams(searchParams);
    if (searchInput.trim()) {
      newParams.set('search', searchInput.trim());
    } else {
      newParams.delete('search');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleClearSearch = () => {
    setSearchInput('');
    const newParams = new URLSearchParams(searchParams);
    newParams.delete('search');
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleCountryChange = (e) => {
    const val = e.target.value;
    const newParams = new URLSearchParams(searchParams);
    if (val) {
      newParams.set('country', val);
    } else {
      newParams.delete('country');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleFilterChange = (filterName) => {
    const newParams = new URLSearchParams(searchParams);
    if (filterName !== 'all') {
      newParams.set('filter', filterName);
    } else {
      newParams.delete('filter');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    if (newPage < 1 || newPage > lastPage) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  return (
    <>
      <main className="member-main feed" id="suggestions-page-main">
        <div className="friends-page suggestions-page">
          <header className="member-page-heading friend-page-heading">
            <div>
              <span className="friend-page-eyebrow">Discover</span>
              <h1>New Connections</h1>
              <p>Recommended connections based on mutual connections, registration, and location.</p>
            </div>
            <div className="friend-page-heading__actions">
              <button
                className="member-button member-button--secondary"
                type="button"
                aria-label="Refresh Suggestions"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '8px 12px' }}
              >
                <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
              </button>
              <Link className="member-button member-button--secondary" to="/member/friends">
                <UsersRound size={16} aria-hidden="true" />
                <span>My Connections</span>
              </Link>
            </div>
          </header>

          {/* Filter Bar Card */}
          <form className="card member-card connection-filter-card" onSubmit={handleSearchSubmit}>
            <div className="connection-filter-main">
              {/* Search Box */}
              <div className="connection-search-box">
                <Search size={16} aria-hidden="true" />
                <input
                  type="search"
                  name="search"
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                  placeholder="Search by name, handle, city, country, or bio..."
                  aria-label="Search members"
                />
                {searchInput && (
                  <button
                    type="button"
                    className="connection-search-clear"
                    onClick={handleClearSearch}
                    title="Clear search"
                    aria-label="Clear search"
                  >
                    <X size={14} aria-hidden="true" />
                  </button>
                )}
              </div>

              {/* Country Select */}
              <div className="connection-country-select-wrap">
                <Globe size={16} aria-hidden="true" />
                <select
                  name="country"
                  value={countryQuery}
                  onChange={handleCountryChange}
                  aria-label="Filter by country"
                >
                  <option value="">All Countries</option>
                  {availableCountries.map((c) => (
                    <option key={c} value={c}>
                      {c}
                    </option>
                  ))}
                </select>
                <ChevronDown size={14} className="connection-select-arrow" aria-hidden="true" />
              </div>

              <button className="member-button member-button--primary connection-search-btn" type="submit">
                <Search size={15} aria-hidden="true" />
                <span>Search</span>
              </button>
            </div>

            {/* Quick Filters */}
            <div className="connection-quick-filters">
              <div className="connection-quick-filter-pills" role="tablist" aria-label="Quick filters">
                <button
                  type="button"
                  onClick={() => handleFilterChange('all')}
                  className={`connection-quick-btn ${filterQuery === 'all' ? 'is-active' : ''}`}
                  role="tab"
                  aria-selected={filterQuery === 'all'}
                >
                  <Users size={14} aria-hidden="true" />
                  <span>All</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleFilterChange('new')}
                  className={`connection-quick-btn ${filterQuery === 'new' ? 'is-active' : ''}`}
                  role="tab"
                  aria-selected={filterQuery === 'new'}
                >
                  <Sparkles size={14} aria-hidden="true" />
                  <span>New Members</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleFilterChange('mutual')}
                  className={`connection-quick-btn ${filterQuery === 'mutual' ? 'is-active' : ''}`}
                  role="tab"
                  aria-selected={filterQuery === 'mutual'}
                >
                  <UserCheck size={14} aria-hidden="true" />
                  <span>Mutual Connections</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleFilterChange('nearby')}
                  className={`connection-quick-btn ${filterQuery === 'nearby' ? 'is-active' : ''}`}
                  role="tab"
                  aria-selected={filterQuery === 'nearby'}
                >
                  <MapPin size={14} aria-hidden="true" />
                  <span>Nearby</span>
                </button>
              </div>
            </div>
          </form>

          {/* Main List */}
          {isLoading ? (
            <div style={{ padding: '32px', textAlign: 'center', color: 'var(--color-text-secondary)', marginTop: '24px' }}>
              Loading suggestions...
            </div>
          ) : error ? (
            <section className="member-card friend-empty" role="alert" style={{ marginTop: '24px' }}>
              <h2>Error Loading Suggestions</h2>
              <p>{error}</p>
              <button
                className="member-button member-button--primary"
                type="button"
                onClick={handleRefresh}
              >
                Try Again
              </button>
            </section>
          ) : suggestions.length === 0 ? (
            <section className="member-card friend-empty" style={{ marginTop: '24px' }}>
              <span>
                <UsersRound size={48} aria-hidden="true" />
              </span>
              <h2>No suggestions found</h2>
              <p>Try adjusting your search criteria or removing filters to discover more members.</p>
              <button
                className="member-button member-button--secondary"
                type="button"
                onClick={() => {
                  setSearchInput('');
                  setSearchParams({});
                }}
              >
                Reset Filters
              </button>
            </section>
          ) : (
            <>
              <section className="card connection-requests-card" style={{ padding: '0', overflow: 'hidden', marginTop: '24px' }}>
                <div className="connection-requests-list">
                  {suggestions.map((member) => (
                    <CompactMemberRow
                      key={member.id}
                      member={member}
                      mode="suggestion"
                      initialFriendshipState="none"
                    />
                  ))}
                </div>
              </section>

              {lastPage > 1 && (
                <nav
                  className="pagination"
                  role="navigation"
                  aria-label="Pagination"
                  style={{ marginTop: '24px', display: 'flex', justifyContent: 'center', gap: '8px', flexWrap: 'wrap' }}
                >
                  <button
                    className="member-button member-button--secondary"
                    type="button"
                    onClick={() => handlePageChange(pageQuery - 1)}
                    disabled={pageQuery <= 1}
                    aria-label="Previous Page"
                  >
                    <ChevronLeft size={16} />
                  </button>

                  {Array.from({ length: lastPage }, (_, i) => i + 1).map((pageNum) => (
                    <button
                      key={pageNum}
                      className={`member-button ${pageNum === pageQuery ? 'member-button--primary' : 'member-button--secondary'}`}
                      type="button"
                      onClick={() => handlePageChange(pageNum)}
                      style={{ minWidth: '36px' }}
                    >
                      {pageNum}
                    </button>
                  ))}

                  <button
                    className="member-button member-button--secondary"
                    type="button"
                    onClick={() => handlePageChange(pageQuery + 1)}
                    disabled={pageQuery >= lastPage}
                    aria-label="Next Page"
                  >
                    <ChevronRight size={16} />
                  </button>
                </nav>
              )}
            </>
          )}
        </div>
      </main>

      <FeedRightSidebar />
    </>
  );
}

export default SuggestedConnectionsPage;
