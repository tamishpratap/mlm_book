import { useState, useEffect, useRef } from 'react';
import { Link, NavLink, useNavigate, useLocation } from 'react-router-dom';
import {
  Menu,
  Search,
  X,
  House,
  Sparkles,
  MonitorPlay,
  Users,
  Building2,
  Compass,
  CalendarDays,
  Gift,
} from 'lucide-react';
import NotificationDropdown from './NotificationDropdown';
import ProfileDropdown from './ProfileDropdown';
import ReferralModal from '../../components/common/ReferralModal';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import RewardWalletModal from '../../components/common/RewardWalletModal';
import { BRAND_LOGO } from '../../utils/assetHelper';
import { useBranding } from '../../hooks/useBranding';

export function MemberHeader({ onToggleSidebar, isSidebarOpen = false }) {
  const { logoUrl, siteName } = useBranding();
  const [searchQuery, setSearchQuery] = useState('');
  const [isMobileSearchOpen, setIsMobileSearchOpen] = useState(false);
  const [activeDropdown, setActiveDropdown] = useState(null); // 'profile' | 'notification' | null
  const [isReferralOpen, setIsReferralOpen] = useState(false);
  const [isVerificationOpen, setIsVerificationOpen] = useState(false);
  const [isRewardWalletOpen, setIsRewardWalletOpen] = useState(false);

  const searchInputRef = useRef(null);
  const searchBoxRef = useRef(null);
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    const handleOpenReferral = () => setIsReferralOpen(true);
    const handleOpenVerification = () => setIsVerificationOpen(true);
    const handleOpenRewardWallet = () => setIsRewardWalletOpen(true);

    window.addEventListener('open-referral-modal', handleOpenReferral);
    window.addEventListener('open-verification-modal', handleOpenVerification);
    window.addEventListener('open-reward-wallet-modal', handleOpenRewardWallet);

    return () => {
      window.removeEventListener('open-referral-modal', handleOpenReferral);
      window.removeEventListener('open-verification-modal', handleOpenVerification);
      window.removeEventListener('open-reward-wallet-modal', handleOpenRewardWallet);
    };
  }, []);

  // Sync searchQuery with URL q param when on /member/search
  useEffect(() => {
    if (location.pathname === '/member/search') {
      const params = new URLSearchParams(location.search);
      const q = params.get('q');
      if (q !== null) {
        setSearchQuery(q);
      }
    }
  }, [location.pathname, location.search]);

  // Close mobile search on route change
  useEffect(() => {
    setIsMobileSearchOpen(false);
  }, [location.pathname, location.search]);

  // Click outside listener when mobile search is open
  useEffect(() => {
    if (!isMobileSearchOpen) return;

    const handleClickOutside = (e) => {
      if (searchBoxRef.current && !searchBoxRef.current.contains(e.target)) {
        setIsMobileSearchOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('touchstart', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('touchstart', handleClickOutside);
    };
  }, [isMobileSearchOpen]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`/member/search?q=${encodeURIComponent(searchQuery.trim())}&type=all`);
      setIsMobileSearchOpen(false);
    } else {
      searchInputRef.current?.focus();
    }
  };

  const handleSearchButtonClick = (e) => {
    const isMobile = window.innerWidth <= 767.98;
    if (isMobile && !isMobileSearchOpen) {
      e.preventDefault();
      e.stopPropagation();
      setIsMobileSearchOpen(true);
      setActiveDropdown(null);
      setTimeout(() => {
        searchInputRef.current?.focus();
      }, 50);
      return;
    }
    // If open and empty query, don't submit, focus input
    if (!searchQuery.trim()) {
      e.preventDefault();
      searchInputRef.current?.focus();
    }
  };

  const handleCloseMobileSearch = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setIsMobileSearchOpen(false);
  };

  const handleClearSearch = (e) => {
    e?.preventDefault();
    e?.stopPropagation();
    setSearchQuery('');
    searchInputRef.current?.focus();
  };

  const handleToggleSidebar = () => {
    setIsMobileSearchOpen(false);
    onToggleSidebar?.();
  };

  // Helper for active top nav class matching canonical paths
  const getNavClass = (path, exact = false) => {
    const isActive = exact
      ? location.pathname === path
      : location.pathname === path || (path !== '/member/dashboard' && location.pathname.startsWith(path));
    return `top-nav__item ${isActive ? 'is-active' : ''}`;
  };

  return (
    <header className="topbar">
      <div className="topbar__inner">
        <div className="topbar__brand-area">
          <button
            className="icon-button mobile-menu"
            type="button"
            aria-label={isSidebarOpen ? 'Close navigation' : 'Open navigation'}
            aria-expanded={isSidebarOpen}
            onClick={handleToggleSidebar}
          >
            <Menu size={20} />
          </button>

          <Link className="brand" to="/member/dashboard" aria-label={`${siteName || 'MLM Book'} Member Dashboard`}>
            <img
              className="mlm-book-logo mlm-book-header-logo"
              src={logoUrl || BRAND_LOGO}
              alt={siteName || 'MLM Book'}
              width="50"
              height="50"
              onError={(e) => {
                if (e.currentTarget.src !== BRAND_LOGO) {
                  e.currentTarget.src = BRAND_LOGO;
                }
              }}
            />
          </Link>

          <form
            ref={searchBoxRef}
            className={`search-box ${isMobileSearchOpen ? 'is-open' : ''}`}
            id="memberHeaderSearchForm"
            onSubmit={handleSearchSubmit}
            role="search"
          >
            <button
              className="search-box__submit"
              type={isMobileSearchOpen ? 'submit' : 'button'}
              aria-label={isMobileSearchOpen ? 'Submit Search' : 'Open Search'}
              onClick={handleSearchButtonClick}
            >
              <Search size={16} aria-hidden="true" />
            </button>
            <input
              ref={searchInputRef}
              type="search"
              name="q"
              id="memberHeaderSearchInput"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Escape' && isMobileSearchOpen) {
                  setIsMobileSearchOpen(false);
                }
              }}
              placeholder="Search members, pages, communities..."
              aria-label="Search MLM Book"
              maxLength={100}
              autoComplete="off"
            />
            {searchQuery && (
              <button
                className="search-box__clear"
                type="button"
                aria-label="Clear search"
                onClick={handleClearSearch}
              >
                <X size={14} aria-hidden="true" />
              </button>
            )}
            {isMobileSearchOpen && (
              <button
                className="search-box__close"
                type="button"
                aria-label="Close search"
                onClick={handleCloseMobileSearch}
              >
                <X size={16} aria-hidden="true" />
              </button>
            )}
          </form>
        </div>

        <nav className="top-nav" aria-label="Primary navigation">
          <NavLink to="/member/dashboard" className={getNavClass('/member/dashboard', true)} end>
            <House size={18} />
            <span>Home</span>
          </NavLink>
          <NavLink to="/member/socials" className={getNavClass('/member/socials')}>
            <Sparkles size={18} />
            <span>Socials</span>
          </NavLink>
          <NavLink to="/member/watch" className={getNavClass('/member/watch')}>
            <MonitorPlay size={18} />
            <span>Watch</span>
          </NavLink>
          <NavLink to="/member/community" className={getNavClass('/member/community')}>
            <Users size={18} />
            <span>Community</span>
          </NavLink>
          <NavLink
            to="/member/business-pages"
            className={
              location.pathname.startsWith('/member/business-pages') &&
              !location.pathname.startsWith('/member/business-directory')
                ? 'top-nav__item is-active'
                : 'top-nav__item'
            }
          >
            <Building2 size={18} />
            <span>Business Pages</span>
          </NavLink>
          <NavLink to="/member/business-directory" className={getNavClass('/member/business-directory')}>
            <Compass size={18} />
            <span>Business Directory</span>
          </NavLink>
          <NavLink to="/member/events" className={getNavClass('/member/events')}>
            <CalendarDays size={18} />
            <span>Events</span>
          </NavLink>
        </nav>

        <div className="topbar__actions">
          <button
            className="icon-button"
            type="button"
            aria-label="Referral Program & Link"
            title="Referral Program & Link"
            onClick={() => setIsReferralOpen(true)}
            style={{ position: 'relative' }}
          >
            <Gift size={20} />
          </button>

          <NotificationDropdown
            isOpen={activeDropdown === 'notification'}
            onToggle={() =>
              setActiveDropdown((prev) => (prev === 'notification' ? null : 'notification'))
            }
            onClose={() =>
              setActiveDropdown((prev) => (prev === 'notification' ? null : prev))
            }
          />
          <ProfileDropdown
            isOpen={activeDropdown === 'profile'}
            onToggle={() =>
              setActiveDropdown((prev) => (prev === 'profile' ? null : 'profile'))
            }
            onClose={() =>
              setActiveDropdown((prev) => (prev === 'profile' ? null : prev))
            }
            onOpenReferral={() => setIsReferralOpen(true)}
            onOpenVerification={() => setIsVerificationOpen(true)}
            onOpenRewardWallet={() => setIsRewardWalletOpen(true)}
          />
        </div>
      </div>

      {/* Referral Link & Invitations Modal */}
      <ReferralModal
        isOpen={isReferralOpen}
        onClose={() => setIsReferralOpen(false)}
        onOpenVerification={() => setIsVerificationOpen(true)}
      />

      {/* Account Mobile Verification Modal */}
      <AccountVerificationModal
        isOpen={isVerificationOpen}
        onClose={() => setIsVerificationOpen(false)}
        onVerified={() => {
          setIsVerificationOpen(false);
          setIsReferralOpen(true);
        }}
      />

      {/* Reward Wallet Modal (Earned Campaign Rewards) */}
      <RewardWalletModal
        isOpen={isRewardWalletOpen}
        onClose={() => setIsRewardWalletOpen(false)}
        onOpenVerification={() => setIsVerificationOpen(true)}
      />
    </header>
  );
}

export default MemberHeader;
