import { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import {
  Building2,
  PlusCircle,
  Grid,
  UserCheck,
  Search,
  RotateCw,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import BusinessCard from '../../components/business/BusinessCard';
import useAuth from '../../hooks/useAuth';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import { isMemberMobileVerified } from '../../utils/whatsappVerification';

export function BusinessPagesPage() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const [searchParams, setSearchParams] = useSearchParams();

  const tabQuery = searchParams.get('tab') || 'all';
  const searchQuery = searchParams.get('search') || '';
  const categoryQuery = searchParams.get('category') || '';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [searchInput, setSearchInput] = useState(searchQuery);
  const [pages, setPages] = useState([]);
  const [myPages, setMyPages] = useState([]);
  const [categories, setCategories] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const fetchPages = useCallback(() => {
    const params = {
      tab: tabQuery,
      search: searchQuery || undefined,
      category: categoryQuery || undefined,
      page: currentPage,
    };
    return businessApi.getBusinessPages(params);
  }, [tabQuery, searchQuery, categoryQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;

    fetchPages()
      .then((data) => {
        if (isMounted && data) {
          setPages(data.pages?.data || []);
          setMyPages(data.my_pages || []);
          setLastPage(data.pages?.last_page || 1);
          if (Array.isArray(data.categories)) {
            setCategories(data.categories);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load business pages.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchPages]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchPages()
      .then((data) => {
        if (data) {
          setPages(data.pages?.data || []);
          setMyPages(data.my_pages || []);
          setLastPage(data.pages?.last_page || 1);
        }
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleTabChange = (newTab) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('tab', newTab);
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

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

  const handleCategoryChange = (cat) => {
    const newParams = new URLSearchParams(searchParams);
    if (cat) {
      newParams.set('category', cat);
    } else {
      newParams.delete('category');
    }
    newParams.set('page', '1');
    setSearchParams(newParams);
  };

  const handlePageChange = (newPage) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const handleDeletePage = async (slug) => {
    try {
      await businessApi.deleteBusinessPage(slug);
      handleRefresh();
    } catch {
      alert('Failed to delete business page.');
    }
  };

  const handleCreateClick = (e) => {
    if (!isVerified) {
      e.preventDefault();
      setShowVerifyModal(true);
    }
  };

  return (
    <div className="biz-page">
      {/* Header */}
      <header className="biz-header">
        <div className="biz-header__info">
          <h1>
            <Building2 size={24} aria-hidden="true" />
            <span>Business Pages</span>
          </h1>
          <p>Discover, create, and manage enterprise business pages across the network.</p>
        </div>
        <div className="biz-header__actions" style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={handleRefresh}
            disabled={isRefreshing}
            aria-label="Refresh Pages"
            style={{ padding: '8px 12px' }}
          >
            <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
          </button>
          <Link
            to="/member/business-pages/create"
            className="member-button member-button--primary"
            onClick={handleCreateClick}
          >
            <PlusCircle size={16} aria-hidden="true" />
            <span>Create Business Page</span>
          </Link>
        </div>
      </header>

      {/* Tabs */}
      <nav className="biz-nav-tabs" aria-label="Business Page tabs">
        <button
          type="button"
          className={`biz-nav-tab ${tabQuery === 'all' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('all')}
        >
          <Grid size={15} aria-hidden="true" />
          <span>All Business Pages</span>
        </button>
        <button
          type="button"
          className={`biz-nav-tab ${tabQuery === 'my' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('my')}
        >
          <UserCheck size={15} aria-hidden="true" />
          <span>My Business Pages ({myPages.length})</span>
        </button>
      </nav>

      {/* Toolbar */}
      <div className="biz-toolbar">
        <form onSubmit={handleSearchSubmit} className="biz-search-form">
          <div className="biz-search-input-wrap">
            <Search size={16} aria-hidden="true" />
            <input
              type="search"
              name="search"
              className="biz-search-input"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Search pages by name, @username, description, or location..."
              aria-label="Search business pages"
            />
          </div>
          <select
            name="category"
            className="biz-filter-select"
            value={categoryQuery}
            onChange={(e) => handleCategoryChange(e.target.value)}
          >
            <option value="">All Categories</option>
            {categories.map((cat) => (
              <option key={cat} value={cat}>
                {cat}
              </option>
            ))}
          </select>
        </form>
      </div>

      {error && (
        <div className="card" style={{ padding: '16px', color: '#dc2626', marginBottom: '20px' }}>
          {error}
        </div>
      )}

      {isLoading ? (
        <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
          Loading business pages...
        </div>
      ) : (
        <>
          <div className="biz-grid">
            {pages.length === 0 ? (
              <div className="fb-empty-state" style={{ gridColumn: '1 / -1', width: '100%', textAlign: 'center', padding: '48px 20px' }}>
                <div className="fb-empty-state__icon" style={{ marginBottom: '16px', color: '#98a2b3' }}>
                  <Building2 size={48} aria-hidden="true" style={{ margin: '0 auto' }} />
                </div>
                <h3 style={{ fontSize: '18px', fontWeight: 700, color: '#1d2738', marginBottom: '8px' }}>
                  No Business Pages Found
                </h3>
                <p style={{ color: '#687386', marginBottom: '20px' }}>
                  Build your brand presence by launching your first business page!
                </p>
                <Link
                  to="/member/business-pages/create"
                  className="member-button member-button--primary"
                  style={{ display: 'inline-flex' }}
                  onClick={handleCreateClick}
                >
                  <PlusCircle size={16} aria-hidden="true" />
                  <span>Create Business Page</span>
                </Link>
              </div>
            ) : (
              pages.map((businessPage) => (
                <BusinessCard
                  key={businessPage.id}
                  businessPage={businessPage}
                  onDelete={handleDeletePage}
                />
              ))
            )}
          </div>

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
        </>
      )}

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before creating a business page."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
            navigate('/member/business-pages/create');
          }}
        />
      )}
    </div>
  );
}

export default BusinessPagesPage;
