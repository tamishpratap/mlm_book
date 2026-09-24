import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Menu, Search, ChevronLeft, ChevronRight, X, Sparkles } from 'lucide-react';
import { NotificationDropdown } from './NotificationDropdown';
import { ProfileDropdown } from './ProfileDropdown';
import { AdminSearchInput } from '../common/AdminSearchInput';
import { useBranding } from '../../hooks/useBranding';
import { BRAND_LOGO } from '../../utils/mediaHelper';

export function AdminHeader({
  onToggleMobileSidebar,
  onToggleDesktopSidebar,
  sidebarCollapsed,
}) {
  const { branding } = useBranding();
  const [mobileLogoFailed, setMobileLogoFailed] = useState(false);
  const logoKey = `${branding?.site_logo_url || ''}-${branding?.site_dark_logo_url || ''}`;
  const [prevLogoKey, setPrevLogoKey] = useState(logoKey);

  if (prevLogoKey !== logoKey) {
    setPrevLogoKey(logoKey);
    setMobileLogoFailed(false);
  }

  const [searchQuery, setSearchQuery] = useState('');
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
  const navigate = useNavigate();

  const handleSearchSubmit = (e) => {
    if (e && e.preventDefault) e.preventDefault();
    if (searchQuery.trim()) {
      navigate(`/admin/members?q=${encodeURIComponent(searchQuery.trim())}`);
      setMobileSearchOpen(false);
    }
  };

  const handleClearSearch = () => {
    setSearchQuery('');
  };

  return (
    <header className="sticky top-0 z-30 bg-white border-b border-slate-200 shadow-2xs">
      <div className="h-16 flex items-center justify-between px-4 lg:px-6">
        {/* Left side: Toggles, Mobile Brand, Search */}
        <div className="flex items-center space-x-3 flex-1 max-w-lg">
          {/* Mobile drawer toggle */}
          <button
            type="button"
            onClick={onToggleMobileSidebar}
            className="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-hidden cursor-pointer"
            aria-label="Toggle navigation drawer"
          >
            <Menu className="w-5 h-5" />
          </button>

          {/* Mobile Brand Logo */}
          <Link to="/admin/dashboard" className="lg:hidden flex items-center space-x-2">
            {!mobileLogoFailed && (branding?.site_logo_url || branding?.site_dark_logo_url || BRAND_LOGO) ? (
              <img
                src={branding.site_logo_url || branding.site_dark_logo_url || BRAND_LOGO}
                alt={branding.site_name || 'Brand Logo'}
                className="h-7 w-auto max-w-[80px] object-contain shrink-0"
                onError={(e) => {
                  if (e.currentTarget.src !== BRAND_LOGO) {
                    e.currentTarget.src = BRAND_LOGO;
                  } else {
                    setMobileLogoFailed(true);
                  }
                }}
              />
            ) : (
              <div className="w-7 h-7 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-xs">
                <Sparkles className="w-3.5 h-3.5" />
              </div>
            )}
            <span className="font-extrabold text-slate-800 text-sm tracking-tight hidden xs:inline">
              {branding?.site_name || 'MLM Book'}
            </span>
          </Link>

          {/* Desktop collapse toggle */}
          <button
            type="button"
            onClick={onToggleDesktopSidebar}
            className="hidden lg:flex p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-hidden cursor-pointer"
            title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {sidebarCollapsed ? (
              <ChevronRight className="w-5 h-5" />
            ) : (
              <ChevronLeft className="w-5 h-5" />
            )}
          </button>

          {/* Global Workspace Search Form (Desktop/Tablet) */}
          <form onSubmit={handleSearchSubmit} className="w-full max-w-xs sm:max-w-sm hidden sm:block">
            <AdminSearchInput
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onClear={handleClearSearch}
              onSubmit={handleSearchSubmit}
              placeholder="Search workspace..."
              inputClassName="bg-slate-50 border-slate-200"
            />
          </form>
        </div>

        {/* Right side: Search toggle on mobile, Notifications & User profile */}
        <div className="flex items-center space-x-2 sm:space-x-3">
          {/* Mobile search trigger */}
          <button
            type="button"
            onClick={() => setMobileSearchOpen((prev) => !prev)}
            className="sm:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
            aria-label="Toggle search"
            title="Search workspace"
          >
            {mobileSearchOpen ? <X className="w-5 h-5" /> : <Search className="w-5 h-5" />}
          </button>

          <NotificationDropdown />
          <div className="h-6 w-px bg-slate-200 hidden sm:block" />
          <ProfileDropdown />
        </div>
      </div>

      {/* Expandable Mobile Workspace Search Bar */}
      {mobileSearchOpen && (
        <div className="sm:hidden px-4 pb-3 pt-1 border-t border-slate-100 bg-slate-50">
          <form onSubmit={handleSearchSubmit} className="w-full">
            <AdminSearchInput
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onClear={handleClearSearch}
              onSubmit={handleSearchSubmit}
              placeholder="Search workspace..."
              autoFocus
            />
          </form>
        </div>
      )}
    </header>
  );
}

export default AdminHeader;
