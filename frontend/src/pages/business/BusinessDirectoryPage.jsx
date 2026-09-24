import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  Compass,
  Search,
  BadgeCheck,
  Sparkles,
  Grid,
  TrendingUp,
  RotateCw,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import BusinessCard from '../../components/business/BusinessCard';

export function BusinessDirectoryPage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const qQuery = searchParams.get('q') || '';
  const categoryQuery = searchParams.get('category') || '';
  const cityQuery = searchParams.get('city') || '';
  const countryQuery = searchParams.get('country') || '';
  const verifiedOnlyQuery = searchParams.get('verified_only') === '1';
  const sortQuery = searchParams.get('sort') || 'popular';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [qInput, setQInput] = useState(qQuery);
  const [categoryInput, setCategoryInput] = useState(categoryQuery);
  const [cityInput, setCityInput] = useState(cityQuery);
  const [countryInput, setCountryInput] = useState(countryQuery);
  const [verifiedOnly, setVerifiedOnly] = useState(verifiedOnlyQuery);
  const [sort, setSort] = useState(sortQuery);

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const fetchDirectory = useCallback(() => {
    const params = {
      q: qQuery || undefined,
      category: categoryQuery || undefined,
      city: cityQuery || undefined,
      country: countryQuery || undefined,
      verified_only: verifiedOnlyQuery ? '1' : undefined,
      sort: sortQuery || undefined,
      page: currentPage,
    };
    return businessApi.getDirectory(params);
  }, [qQuery, categoryQuery, cityQuery, countryQuery, verifiedOnlyQuery, sortQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;

    fetchDirectory()
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load business directory.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchDirectory]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchDirectory()
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams();
    if (qInput.trim()) newParams.set('q', qInput.trim());
    if (categoryInput) newParams.set('category', categoryInput);
    if (cityInput.trim()) newParams.set('city', cityInput.trim());
    if (countryInput.trim()) newParams.set('country', countryInput.trim());
    if (verifiedOnly) newParams.set('verified_only', '1');
    if (sort) newParams.set('sort', sort);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleVerifiedToggle = (checked) => {
    setVerifiedOnly(checked);
    const newParams = new URLSearchParams(searchParams);
    if (checked) {
      newParams.set('verified_only', '1');
    } else {
      newParams.delete('verified_only');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handleSortChange = (newSort) => {
    setSort(newSort);
    const newParams = new URLSearchParams(searchParams);
    if (newSort) newParams.set('sort', newSort);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const directoryPages = data?.directory_pages?.data || [];
  const lastPage = data?.directory_pages?.last_page || 1;
  const featuredPages = data?.featured_pages || [];
  const trendingPages = data?.trending_pages || [];
  const categoriesList = data?.categories_list || [];

  return (
    <div className="biz-page biz-directory-page">
      {/* Hero Directory Search Header */}
      <div className="biz-hero biz-directory-hero">
        <div className="biz-directory-hero__inner">
          <span className="biz-badge biz-badge--category biz-directory-hero__badge">
            <Compass size={14} />
            <span>Business Directory & Discovery</span>
          </span>

          <h1 className="biz-directory-hero__title">
            Find & Connect with Verified Businesses
          </h1>
          <p className="biz-directory-hero__subtitle">
            Discover top-rated enterprises, local services, startups, and professional network partners.
          </p>

          {/* Search Panel */}
          <form
            onSubmit={handleSearchSubmit}
            className="biz-directory-filter"
          >
            {/* First Row */}
            <div className="biz-directory-filter__row-primary">
              <input
                type="text"
                value={qInput}
                onChange={(e) => setQInput(e.target.value)}
                className="biz-search-input biz-directory-filter__search-input"
                placeholder="Search business name, username, keyword, city..."
                aria-label="Search business name, username, keyword, city"
              />
              <select
                value={categoryInput}
                onChange={(e) => setCategoryInput(e.target.value)}
                className="biz-filter-select biz-directory-filter__category-select"
                aria-label="Filter by category"
              >
                <option value="">All Categories</option>
                {categoriesList.map((cat) => (
                  <option key={cat.slug} value={cat.name}>
                    {cat.name} ({cat.count})
                  </option>
                ))}
              </select>
              <button
                type="submit"
                className="member-button member-button--primary biz-directory-filter__submit-btn"
              >
                <Search size={16} aria-hidden="true" />
                <span>Search Directory</span>
              </button>
            </div>

            {/* Second Row */}
            <div className="biz-directory-filter__row-secondary">
              <div className="biz-directory-filter__location-group">
                <input
                  type="text"
                  value={cityInput}
                  onChange={(e) => setCityInput(e.target.value)}
                  placeholder="City..."
                  className="biz-search-input biz-directory-filter__city-input"
                  aria-label="City"
                />
                <input
                  type="text"
                  value={countryInput}
                  onChange={(e) => setCountryInput(e.target.value)}
                  placeholder="Country..."
                  className="biz-search-input biz-directory-filter__country-input"
                  aria-label="Country"
                />
                <label className="biz-directory-filter__verified-label">
                  <input
                    type="checkbox"
                    checked={verifiedOnly}
                    onChange={(e) => handleVerifiedToggle(e.target.checked)}
                    style={{ width: '16px', height: '16px', cursor: 'pointer', accentColor: '#20c875' }}
                  />
                  <BadgeCheck size={16} color="#20c875" />
                  <span>Verified Only</span>
                </label>
              </div>

              <div className="biz-directory-filter__sort-group">
                <span className="biz-directory-filter__sort-label">Sort By:</span>
                <select
                  value={sort}
                  onChange={(e) => handleSortChange(e.target.value)}
                  className="biz-filter-select biz-directory-filter__sort-select"
                  aria-label="Sort by"
                >
                  <option value="popular">Most Popular / Trending</option>
                  <option value="newest">Newest Created</option>
                  <option value="oldest">Oldest Created</option>
                  <option value="alphabetical">Alphabetical (A-Z)</option>
                </select>
              </div>
            </div>
          </form>
        </div>
      </div>

      {error && (
        <div className="card" style={{ padding: '16px', color: '#dc2626', marginBottom: '20px' }}>
          {error}
        </div>
      )}

      {isLoading ? (
        <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
          Loading directory...
        </div>
      ) : (
        <>
          {/* Featured Businesses */}
          {featuredPages.length > 0 && (
            <div style={{ marginBottom: '36px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
                <h3 style={{ fontSize: '18px', fontWeight: 700, color: '#1d2738', margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Sparkles size={18} color="#f7b940" />
                  <span>Featured Businesses</span>
                </h3>
                <span className="biz-badge biz-badge--verified" style={{ fontSize: '11px' }}>Verified Partners</span>
              </div>
              <div className="biz-directory-grid biz-directory-grid--featured" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: '20px' }}>
                {featuredPages.map((fp) => (
                  <BusinessCard key={`feat-${fp.id}`} businessPage={fp} />
                ))}
              </div>
            </div>
          )}

          {/* Categories Grid */}
          <div
            className="biz-info-card biz-directory-categories-card"
            style={{
              padding: '28px',
              borderRadius: '18px',
              background: '#ffffff',
              border: '1px solid #e7ecf4',
              boxShadow: '0 2px 8px rgba(34, 49, 78, 0.035)',
              marginBottom: '40px',
            }}
          >
            <h3
              style={{
                marginBottom: '20px',
                paddingBottom: '12px',
                borderBottom: '1px solid #e7ecf4',
                fontSize: '17px',
                fontWeight: 700,
                color: '#1d2738',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
              }}
            >
              <Grid size={18} color="#4f7df3" />
              <span>Browse by Business Category</span>
            </h3>
            <div className="biz-directory-categories-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '16px' }}>
              {categoriesList.map((cat) => (
                <Link
                  key={cat.slug}
                  to={`/member/business-directory/categories/${cat.slug}`}
                  style={{
                    padding: '18px',
                    borderRadius: '14px',
                    background: '#f8fafc',
                    border: '1px solid #e7ecf4',
                    textDecoration: 'none',
                    display: 'flex',
                    flexDirection: 'column',
                    justifyContent: 'center',
                    alignItems: 'flex-start',
                  }}
                  className="biz-category-tile"
                >
                  <strong style={{ fontSize: '13.5px', color: '#1d2738', marginBottom: '4px', fontWeight: 700 }}>
                    {cat.name}
                  </strong>
                  <span style={{ fontSize: '12px', color: '#687386', fontWeight: 600 }}>
                    {cat.count} {cat.count === 1 ? 'Page' : 'Pages'}
                  </span>
                </Link>
              ))}
            </div>
          </div>

          {/* Trending Businesses Showcase */}
          {trendingPages.length > 0 && !qQuery && !categoryQuery && (
            <div style={{ marginBottom: '36px' }}>
              <h3 style={{ fontSize: '18px', fontWeight: 700, color: '#1d2738', margin: '0 0 16px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <TrendingUp size={18} color="#8a2be2" />
                <span>Trending & Fast Growing Pages</span>
              </h3>
              <div className="biz-directory-grid biz-directory-grid--trending" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: '20px' }}>
                {trendingPages.map((tp) => (
                  <BusinessCard key={`trend-${tp.id}`} businessPage={tp} />
                ))}
              </div>
            </div>
          )}

          {/* Directory Listings Grid */}
          <div style={{ marginTop: '24px' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
              <h3 style={{ fontSize: '18px', fontWeight: 700, color: '#1d2738', margin: 0 }}>
                Directory Listings
              </h3>
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={handleRefresh}
                disabled={isRefreshing}
                style={{ padding: '6px 12px' }}
                aria-label="Refresh Directory"
              >
                <RotateCw size={13} className={isRefreshing ? 'fa-spin' : ''} />
              </button>
            </div>

            {directoryPages.length === 0 ? (
              <div className="card" style={{ padding: '48px 20px', textAlign: 'center', borderRadius: '16px' }}>
                <Compass size={40} color="#94a3b8" style={{ margin: '0 auto 12px' }} />
                <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 6px 0' }}>No Businesses Match Your Search</h3>
                <p style={{ color: 'var(--color-text-secondary)', margin: 0, fontSize: '13.5px' }}>
                  Try changing your keyword search, category, or location filter.
                </p>
              </div>
            ) : (
              <div className="biz-directory-grid biz-directory-grid--listings" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '20px' }}>
                {directoryPages.map((bp) => (
                  <BusinessCard key={bp.id} businessPage={bp} />
                ))}
              </div>
            )}

            {/* Pagination */}
            {lastPage > 1 && (
              <div style={{ display: 'flex', justifyContent: 'center', gap: '8px', marginTop: '24px' }}>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  disabled={currentPage <= 1}
                  onClick={() => handlePageChange(currentPage - 1)}
                >
                  Previous
                </button>
                <span style={{ display: 'flex', alignItems: 'center', fontSize: '13px', color: '#64748b' }}>
                  Page {currentPage} of {lastPage}
                </span>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  disabled={currentPage >= lastPage}
                  onClick={() => handlePageChange(currentPage + 1)}
                >
                  Next
                </button>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}

export default BusinessDirectoryPage;
