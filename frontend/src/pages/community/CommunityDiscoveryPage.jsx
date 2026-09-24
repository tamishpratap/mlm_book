import { useState, useEffect, useCallback, useRef } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Compass,
  Search,
  Sparkles,
  UserCheck,
  Layers,
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  RotateCw,
} from 'lucide-react';
import communityApi from '../../api/communityApi';
import CommunityCard from '../../components/community/CommunityCard';

export function CommunityDiscoveryPage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const searchQuery = searchParams.get('search') || '';
  const categoryQuery = searchParams.get('category') || '';
  const sortQuery = searchParams.get('sort') || 'trending';
  const visibilityQuery = searchParams.get('visibility') || 'all';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [liveSearchQuery, setLiveSearchQuery] = useState(searchQuery);
  const [liveResults, setLiveResults] = useState([]);
  const [showLiveDropdown, setShowLiveDropdown] = useState(false);
  const searchDropdownRef = useRef(null);

  const [discoveryData, setDiscoveryData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const fetchDiscovery = useCallback(() => {
    const params = {
      search: searchQuery || undefined,
      category: categoryQuery || undefined,
      sort: sortQuery,
      visibility: visibilityQuery,
      page: currentPage,
    };
    return communityApi.getDiscovery(params);
  }, [searchQuery, categoryQuery, sortQuery, visibilityQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;

    fetchDiscovery()
      .then((data) => {
        if (isMounted && data) {
          setDiscoveryData(data);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load discovery platform.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchDiscovery]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchDiscovery()
      .then((data) => {
        if (data) setDiscoveryData(data);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  // Live search AJAX debounce
  useEffect(() => {
    if (!liveSearchQuery || liveSearchQuery.trim().length < 2) {
      return;
    }

    const timer = setTimeout(() => {
      communityApi
        .searchAjax(liveSearchQuery.trim())
        .then((data) => {
          if (data && Array.isArray(data.results)) {
            setLiveResults(data.results);
            setShowLiveDropdown(true);
          }
        })
        .catch(() => {});
    }, 250);

    return () => clearTimeout(timer);
  }, [liveSearchQuery]);

  // Click outside to close live dropdown
  useEffect(() => {
    function handleClickOutside(event) {
      if (searchDropdownRef.current && !searchDropdownRef.current.contains(event.target)) {
        setShowLiveDropdown(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLiveSearchSubmit = (e) => {
    e.preventDefault();
    setShowLiveDropdown(false);
    const newParams = new URLSearchParams(searchParams);
    if (liveSearchQuery.trim()) {
      newParams.set('search', liveSearchQuery.trim());
    } else {
      newParams.delete('search');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleCategorySelect = (cat) => {
    const newParams = new URLSearchParams(searchParams);
    if (categoryQuery === cat) {
      newParams.delete('category');
    } else {
      newParams.set('category', cat);
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleSortChange = (e) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('sort', e.target.value);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleVisibilityChange = (e) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('visibility', e.target.value);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    if (!discoveryData?.communities) return;
    if (newPage < 1 || newPage > discoveryData.communities.last_page) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const communities = discoveryData?.communities?.data || [];
  const featured = discoveryData?.featured_communities || [];
  const suggested = discoveryData?.suggested_communities || [];
  const categoriesWithCount = discoveryData?.categories_with_count || {};
  const lastPage = discoveryData?.communities?.last_page || 1;

  const categoriesList = [
    'Technology',
    'Business',
    'Education',
    'Gaming',
    'Sports',
    'Finance',
    'Crypto',
    'Entertainment',
    'Lifestyle',
    'Health',
    'Other',
  ];

  return (
    <div className="community-page">
      {/* Back button */}
      <div style={{ marginBottom: '12px' }}>
        <Link
          to="/member/community"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '6px',
            color: 'var(--color-text-secondary)',
            fontSize: '0.875rem',
            fontWeight: 500,
            textDecoration: 'none',
          }}
        >
          <ArrowLeft size={16} />
          <span>Back to Community Hub</span>
        </Link>
      </div>

      {/* Explore Hero Banner */}
      <div
        className="card"
        style={{
          background: 'linear-gradient(135deg, rgba(79, 125, 243, 0.15), rgba(125, 66, 240, 0.15))',
          border: '1px solid var(--color-border-soft)',
          borderRadius: 'var(--radius-lg, 18px)',
          padding: '32px 28px',
          position: 'relative',
          overflow: 'visible',
        }}
      >
        <div style={{ maxWidth: '680px', position: 'relative', zIndex: 2 }}>
          <span
            className="community-badge"
            style={{
              background: 'var(--color-primary, #4f7df3)',
              color: '#ffffff',
              marginBottom: '8px',
              display: 'inline-block',
            }}
          >
            <Compass size={12} style={{ verticalAlign: 'middle', marginRight: '4px' }} />
            <span>Community Discovery Platform</span>
          </span>
          <h1 style={{ fontSize: '24px', fontWeight: 900, color: 'var(--color-text, #1d2738)', margin: '0 0 8px 0' }}>
            Discover & Join Vibrant Communities
          </h1>
          <p style={{ fontSize: '14.5px', color: 'var(--color-text-secondary, #687386)', lineHeight: 1.5, margin: '0 0 20px 0' }}>
            Find communities aligned with your goals, interests, network, and business growth. Connect with like-minded creators and professionals.
          </p>

          {/* Instant Live Search Input */}
          <div ref={searchDropdownRef} style={{ position: 'relative', maxWidth: '520px' }}>
            <form onSubmit={handleLiveSearchSubmit}>
              <div style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: 'var(--color-text-secondary)' }}>
                <Search size={18} />
              </div>
              <input
                type="text"
                className="community-search-input"
                placeholder="Search communities by name, category, or interests..."
                value={liveSearchQuery}
                onChange={(e) => {
                  const val = e.target.value;
                  setLiveSearchQuery(val);
                  if (!val || val.trim().length < 2) {
                    setLiveResults([]);
                    setShowLiveDropdown(false);
                  }
                }}
                onFocus={() => {
                  if (liveResults.length > 0) setShowLiveDropdown(true);
                }}
                style={{ paddingLeft: '42px', height: '46px', fontSize: '14px', borderRadius: '10px', width: '100%' }}
              />
            </form>

            {/* Live Search Dropdown */}
            {showLiveDropdown && liveResults.length > 0 && (
              <div
                className="card"
                style={{
                  position: 'absolute',
                  top: '52px',
                  left: 0,
                  right: 0,
                  zIndex: 50,
                  maxHeight: '320px',
                  overflowY: 'auto',
                  padding: '8px',
                  boxShadow: '0 10px 30px rgba(0,0,0,0.15)',
                  background: '#ffffff',
                  borderRadius: '12px',
                }}
              >
                {liveResults.map((item) => (
                  <Link
                    key={item.id}
                    to={`/member/community/${item.slug}`}
                    onClick={() => setShowLiveDropdown(false)}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      padding: '10px 12px',
                      borderRadius: '8px',
                      textDecoration: 'none',
                      color: 'var(--color-text, #1d2738)',
                      transition: 'background 0.15s ease',
                    }}
                  >
                    <div>
                      <strong style={{ display: 'block', fontSize: '14px' }}>{item.name}</strong>
                      <span style={{ fontSize: '12px', color: 'var(--color-text-secondary)' }}>
                        {item.category} • {item.member_count} members
                      </span>
                    </div>
                    <span className="community-badge community-badge--category" style={{ fontSize: '11px' }}>
                      {item.visibility}
                    </span>
                  </Link>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Category Browser */}
      <div style={{ marginTop: '24px', marginBottom: '28px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px', padding: '0 4px' }}>
          <h2 style={{ fontSize: '16px', fontWeight: 800, color: 'var(--color-text, #1d2738)', margin: 0, display: 'flex', alignItems: 'center', gap: '6px' }}>
            <Layers size={18} color="var(--color-primary, #4f7df3)" />
            <span>Browse Categories</span>
          </h2>
          {categoryQuery && (
            <button
              type="button"
              onClick={() => handleCategorySelect('')}
              style={{ background: 'none', border: 'none', fontSize: '12.5px', color: 'var(--color-primary, #4f7df3)', fontWeight: 600, cursor: 'pointer' }}
            >
              Clear Category Filter
            </button>
          )}
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))', gap: '10px' }}>
          {categoriesList.map((cat) => {
            const count = categoriesWithCount[cat] || 0;
            const isActive = categoryQuery === cat;
            return (
              <button
                key={cat}
                type="button"
                onClick={() => handleCategorySelect(cat)}
                className="card"
                style={{
                  padding: '12px',
                  borderRadius: '12px',
                  textAlign: 'left',
                  cursor: 'pointer',
                  border: isActive ? '2px solid var(--color-primary, #4f7df3)' : '1px solid var(--color-border-soft)',
                  background: isActive ? 'rgba(79,125,243,0.08)' : '#ffffff',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '4px',
                }}
              >
                <span style={{ fontSize: '13px', fontWeight: 700, color: 'var(--color-text, #1d2738)' }}>{cat}</span>
                <span style={{ fontSize: '11px', color: 'var(--color-text-secondary, #687386)' }}>
                  {Number(count).toLocaleString()} {count === 1 ? 'community' : 'communities'}
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Featured Showcase */}
      {featured.length > 0 && !searchQuery && !categoryQuery && (
        <div style={{ marginBottom: '28px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '14px', padding: '0 4px' }}>
            <Sparkles size={18} color="#f59e0b" />
            <h2 style={{ fontSize: '16px', fontWeight: 800, color: 'var(--color-text, #1d2738)', margin: 0 }}>Featured Communities</h2>
          </div>
          <div className="community-grid">
            {featured.map((community) => (
              <CommunityCard key={community.id} community={community} onUpdate={handleRefresh} />
            ))}
          </div>
        </div>
      )}

      {/* Recommended for You */}
      {suggested.length > 0 && !searchQuery && !categoryQuery && (
        <div style={{ marginBottom: '28px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '14px', padding: '0 4px' }}>
            <UserCheck size={18} color="#10b981" />
            <h2 style={{ fontSize: '16px', fontWeight: 800, color: 'var(--color-text, #1d2738)', margin: 0 }}>Recommended for You</h2>
          </div>
          <div className="community-grid">
            {suggested.map((community) => (
              <CommunityCard key={community.id} community={community} onUpdate={handleRefresh} />
            ))}
          </div>
        </div>
      )}

      {/* All Communities & Filters Bar */}
      <div style={{ marginTop: '12px' }}>
        <div className="community-toolbar" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px' }}>
          <h2 style={{ fontSize: '16px', fontWeight: 800, margin: 0 }}>
            {categoryQuery ? `${categoryQuery} Communities` : searchQuery ? `Search Results for "${searchQuery}"` : 'All Communities'}
          </h2>

          <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
            <button
              className="member-button member-button--secondary"
              type="button"
              aria-label="Refresh Discovery"
              onClick={handleRefresh}
              disabled={isRefreshing}
              style={{ padding: '8px 12px' }}
            >
              <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
            </button>

            <select
              value={sortQuery}
              onChange={handleSortChange}
              className="community-filter-select"
              style={{ width: 'auto' }}
              aria-label="Sort communities"
            >
              <option value="trending">Trending</option>
              <option value="popular">Most Popular</option>
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
              <option value="featured">Featured First</option>
            </select>

            <select
              value={visibilityQuery}
              onChange={handleVisibilityChange}
              className="community-filter-select"
              style={{ width: 'auto' }}
              aria-label="Filter visibility"
            >
              <option value="all">All Visibilities</option>
              <option value="public">Public Only</option>
              <option value="private">Private / Invite Only</option>
            </select>
          </div>
        </div>

        {isLoading ? (
          <div className="community-grid" style={{ marginTop: '16px' }}>
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="community-card" style={{ minHeight: '260px', opacity: 0.6, background: '#fff' }} />
            ))}
          </div>
        ) : error ? (
          <div className="notification-empty" role="alert" style={{ marginTop: '16px' }}>
            <h2>Error Loading Communities</h2>
            <p>{error}</p>
            <button className="member-button member-button--primary" type="button" onClick={handleRefresh} style={{ marginTop: '12px' }}>
              Try Again
            </button>
          </div>
        ) : communities.length === 0 ? (
          <div className="notification-empty" style={{ marginTop: '16px' }}>
            <h2>No Communities Match</h2>
            <p>Try adjusting your search criteria or category filters.</p>
          </div>
        ) : (
          <>
            <div className="community-grid" style={{ marginTop: '16px' }}>
              {communities.map((community) => (
                <CommunityCard key={community.id} community={community} onUpdate={handleRefresh} />
              ))}
            </div>

            {lastPage > 1 && (
              <nav
                className="pagination"
                role="navigation"
                aria-label="Pagination"
                style={{ marginTop: '24px', display: 'flex', justifyContent: 'center', gap: '8px' }}
              >
                <button
                  className="member-button member-button--secondary"
                  type="button"
                  onClick={() => handlePageChange(currentPage - 1)}
                  disabled={currentPage <= 1}
                  aria-label="Previous Page"
                >
                  <ChevronLeft size={16} />
                </button>

                {Array.from({ length: lastPage }, (_, i) => i + 1).map((pageNum) => (
                  <button
                    key={pageNum}
                    className={`member-button ${pageNum === currentPage ? 'member-button--primary' : 'member-button--secondary'}`}
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
                  onClick={() => handlePageChange(currentPage + 1)}
                  disabled={currentPage >= lastPage}
                  aria-label="Next Page"
                >
                  <ChevronRight size={16} />
                </button>
              </nav>
            )}
          </>
        )}
      </div>
    </div>
  );
}

export default CommunityDiscoveryPage;
