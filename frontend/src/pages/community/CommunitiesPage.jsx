import { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import {
  UsersRound,
  Plus,
  Grid,
  ShieldCheck,
  Compass,
  TrendingUp,
  Search,
  ChevronLeft,
  ChevronRight,
  RotateCw,
  Bell,
} from 'lucide-react';
import communityApi from '../../api/communityApi';
import CommunityCard from '../../components/community/CommunityCard';
import useAuth from '../../hooks/useAuth';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import { isMemberMobileVerified } from '../../utils/whatsappVerification';

export function CommunitiesPage() {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);

  const [searchParams, setSearchParams] = useSearchParams();
  const rawTab = searchParams.get('tab');
  const currentTab = ['joined', 'my', 'mine', 'discover'].includes(rawTab)
    ? (rawTab === 'mine' ? 'my' : rawTab)
    : 'all';
  const searchQuery = searchParams.get('search') || '';
  const categoryQuery = searchParams.get('category') || '';
  const currentPage = parseInt(searchParams.get('page') || '1', 10);

  const [searchInput, setSearchInput] = useState(searchQuery);
  const [communities, setCommunities] = useState([]);
  const [joinedCommunitiesCount, setJoinedCommunitiesCount] = useState(0);
  const [myCommunitiesCount, setMyCommunitiesCount] = useState(0);
  const [categories, setCategories] = useState([]);
  const [lastPage, setLastPage] = useState(1);
  const [unreadNotificationsCount, setUnreadNotificationsCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const fetchCommunities = useCallback(() => {
    const params = {
      tab: currentTab,
      search: searchQuery || undefined,
      category: categoryQuery || undefined,
      page: currentPage,
    };

    return communityApi.getCommunities(params);
  }, [currentTab, searchQuery, categoryQuery, currentPage]);

  useEffect(() => {
    let isMounted = true;
    setIsLoading(true);

    communityApi
      .getUnreadCommunityNotificationsCount()
      .then((res) => {
        if (isMounted && res && res.unread_count !== undefined) {
          setUnreadNotificationsCount(res.unread_count);
        }
      })
      .catch(() => {});

    fetchCommunities()
      .then((data) => {
        if (isMounted) {
          if (data && data.communities) {
            setCommunities(data.communities.data || []);
            setLastPage(data.communities.last_page || 1);
            if (data.joined_count !== undefined) {
              setJoinedCommunitiesCount(data.joined_count);
            } else if (Array.isArray(data.joined_communities)) {
              setJoinedCommunitiesCount(data.joined_communities.length);
            }
            if (data.my_count !== undefined) {
              setMyCommunitiesCount(data.my_count);
            } else if (Array.isArray(data.my_communities)) {
              setMyCommunitiesCount(data.my_communities.length);
            }
            if (Array.isArray(data.categories)) {
              setCategories(data.categories);
            }
          } else {
            setCommunities([]);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load communities.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchCommunities]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchCommunities()
      .then((data) => {
        if (data && data.communities) {
          setCommunities(data.communities.data || []);
          setLastPage(data.communities.last_page || 1);
          if (data.joined_count !== undefined) {
            setJoinedCommunitiesCount(data.joined_count);
          } else if (Array.isArray(data.joined_communities)) {
            setJoinedCommunitiesCount(data.joined_communities.length);
          }
          if (data.my_count !== undefined) {
            setMyCommunitiesCount(data.my_count);
          } else if (Array.isArray(data.my_communities)) {
            setMyCommunitiesCount(data.my_communities.length);
          }
        }
      })
      .catch(() => {})
      .finally(() => setIsRefreshing(false));
  };

  const handleTabChange = (tabKey) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('tab', tabKey);
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

  const handleCategoryChange = (e) => {
    const cat = e.target.value;
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
    if (newPage < 1 || newPage > lastPage) return;
    const newParams = new URLSearchParams(searchParams);
    newParams.set('page', newPage.toString());
    setSearchParams(newParams);
  };

  const handleCreateClick = (e) => {
    if (!isVerified) {
      e.preventDefault();
      setShowVerifyModal(true);
    }
  };

  return (
    <div className="community-page">
      {/* Header */}
      <header className="community-header">
        <div className="community-header__info">
          <h1>
            <UsersRound size={24} aria-hidden="true" />
            <span>Community Hub</span>
          </h1>
          <p>Discover, build, and collaborate with thriving member communities.</p>
        </div>
        <div className="community-header__actions">
          <button
            className="member-button member-button--secondary community-header__btn-refresh"
            type="button"
            aria-label="Refresh Communities"
            onClick={handleRefresh}
            disabled={isRefreshing}
          >
            <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
          </button>
          <Link
            to="/member/community/notifications"
            className="member-button member-button--secondary community-header__btn-notifications"
            title="Community Notifications"
          >
            <Bell size={15} aria-hidden="true" />
            <span>Notifications</span>
            {unreadNotificationsCount > 0 && (
              <span
                className="community-badge"
                style={{
                  background: '#ef4444',
                  color: '#ffffff',
                  padding: '1px 6px',
                  borderRadius: '10px',
                  fontSize: '10px',
                  marginLeft: '4px',
                }}
              >
                {unreadNotificationsCount}
              </span>
            )}
          </Link>
          <Link
            to="/member/community/create"
            className="member-button member-button--primary community-header__btn-create"
            onClick={handleCreateClick}
          >
            <Plus size={15} aria-hidden="true" />
            <span>Create Community</span>
          </Link>
        </div>
      </header>

      {/* Navigation Tabs */}
      <nav className="community-nav-tabs" aria-label="Community tabs">
        <button
          type="button"
          className={`community-nav-tab ${currentTab === 'all' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('all')}
        >
          <Grid size={15} aria-hidden="true" />
          <span>All Communities</span>
        </button>

        <button
          type="button"
          className={`community-nav-tab ${currentTab === 'joined' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('joined')}
        >
          <ShieldCheck size={15} aria-hidden="true" />
          <span>Joined Communities ({joinedCommunitiesCount})</span>
        </button>

        <button
          type="button"
          className={`community-nav-tab ${currentTab === 'my' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('my')}
        >
          <UsersRound size={15} aria-hidden="true" />
          <span>My Communities ({myCommunitiesCount})</span>
        </button>

        <Link to="/member/community/discover" className="community-nav-tab">
          <Compass size={15} aria-hidden="true" />
          <span>Discover Platform</span>
        </Link>

        <Link to="/member/community/discover?sort=trending" className="community-nav-tab">
          <TrendingUp size={15} aria-hidden="true" />
          <span>Trending</span>
        </Link>
      </nav>

      {/* Toolbar */}
      <div className="community-toolbar">
        <form className="community-search-form" onSubmit={handleSearchSubmit}>
          <div className="community-search-input-wrap">
            <Search size={16} aria-hidden="true" />
            <input
              type="search"
              className="community-search-input"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Search communities by name, description, or category..."
              aria-label="Search communities"
            />
          </div>

          <select
            name="category"
            className="community-filter-select"
            value={categoryQuery}
            onChange={handleCategoryChange}
            aria-label="Filter by Category"
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

      {/* Community Grid */}
      {isLoading ? (
        <div className="community-grid">
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <div key={i} className="community-card" style={{ minHeight: '260px', opacity: 0.6, background: '#fff' }} />
          ))}
        </div>
      ) : error ? (
        <div className="notification-empty" role="alert">
          <h2>Error Loading Communities</h2>
          <p>{error}</p>
          <button
            className="member-button member-button--primary"
            type="button"
            onClick={handleRefresh}
            style={{ marginTop: '12px' }}
          >
            Try Again
          </button>
        </div>
      ) : communities.length === 0 ? (
        <div className="card community-empty-state" role="status">
          <div className="community-empty-state__icon">
            <UsersRound size={36} aria-hidden="true" />
          </div>
          <h3>
            {currentTab === 'joined'
              ? 'No Joined Communities Yet'
              : currentTab === 'my'
              ? "You Haven't Created Any Communities Yet"
              : 'No Communities Found'}
          </h3>
          <p>
            {currentTab === 'joined'
              ? (searchQuery || categoryQuery
                  ? 'No joined communities found matching your current search or category filter.'
                  : 'You haven’t joined any communities yet. Discover exciting communities and connect with other members!')
              : currentTab === 'my'
              ? (searchQuery || categoryQuery
                  ? 'No created communities found matching your current search or category filter.'
                  : "You haven't created any communities yet. Start your own thriving community and bring people together!")
              : (searchQuery
                  ? `No communities found matching "${searchQuery}". Be the first pioneer to create a community for this category!`
                  : 'Be the first pioneer to create a community for this category!')}
          </p>
          {currentTab === 'joined' ? (
            <button
              type="button"
              className="member-button member-button--primary"
              onClick={() => handleTabChange('all')}
            >
              <Compass size={15} aria-hidden="true" />
              <span>Explore All Communities</span>
            </button>
          ) : currentTab === 'my' ? (
            <Link
              to="/member/community/create"
              className="member-button member-button--primary"
              onClick={handleCreateClick}
            >
              <Plus size={15} aria-hidden="true" />
              <span>Create Your First Community</span>
            </Link>
          ) : (
            <Link
              to="/member/community/create"
              className="member-button member-button--primary"
              onClick={handleCreateClick}
            >
              <Plus size={15} aria-hidden="true" />
              <span>Create Community</span>
            </Link>
          )}
        </div>
      ) : (
        <>
          <div className="community-grid">
            {communities.map((community) => (
              <CommunityCard
                key={community.id}
                community={community}
                onUpdate={handleRefresh}
              />
            ))}
          </div>

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

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before creating a community."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
            navigate('/member/community/create');
          }}
        />
      )}
    </div>
  );
}

export default CommunitiesPage;
