import { useState, useEffect } from 'react';
import { NavLink, useLocation, useNavigate } from 'react-router-dom';
import {
  LayoutDashboard,
  Users,
  FileText,
  Clock,
  Briefcase,
  Globe,
  ShoppingBag,
  Calendar,
  AlertCircle,
  Bell,
  BarChart2,
  ChevronDown,
  X,
  Sparkles,
  Megaphone,
  Wallet,
  MessageSquare,
  Gift,
  ArrowUpRight,
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useBranding } from '../../hooks/useBranding';
import { BRAND_LOGO } from '../../utils/mediaHelper';

// Navigation Groups matching Admin Information Architecture
const NAV_GROUPS = [
  {
    title: 'Core Navigation',
    items: [
      {
        label: 'Dashboard',
        to: '/admin/dashboard',
        icon: LayoutDashboard,
        permission: 'view-dashboard',
        ownerKey: '/admin/dashboard',
      },
    ],
  },
  {
    title: 'Directory & Content',
    items: [
      {
        label: 'Members Management',
        icon: Users,
        permission: 'view-members',
        basePath: '/admin/members',
        ownerKey: '/admin/members',
        children: [
          { label: 'Verified Members', to: '/admin/members' },
          { label: 'Unverified Members', to: '/admin/members/pending' },
          { label: 'Blocked Members', to: '/admin/members/blocked' },
          { label: 'Security', to: '/admin/members/security' },
          { label: 'Wallet Address', to: '/admin/members/wallet-address' },
        ],
      },
      {
        label: 'Posts & Moderation',
        icon: FileText,
        permission: 'view-posts',
        basePath: '/admin/posts',
        ownerKey: '/admin/posts',
        children: [
          { label: 'All Posts', to: '/admin/posts' },
        ],
      },
      {
        label: 'Stories Management',
        icon: Clock,
        permission: 'view-stories',
        basePath: '/admin/stories',
        ownerKey: '/admin/stories',
        children: [
          { label: 'All Stories', to: '/admin/stories' },
          { label: 'Live Stories', to: '/admin/stories/live' },
          { label: 'Expired Stories', to: '/admin/stories/expired' },
        ],
      },
      {
        label: 'Business Pages',
        icon: Briefcase,
        permission: 'view-business-pages',
        basePath: '/admin/business-pages',
        ownerKey: '/admin/business-pages',
        children: [
          { label: 'Business Directory', to: '/admin/business-pages' },
          { label: 'Categories', to: '/admin/business-pages/categories' },
        ],
      },
      {
        label: 'Ads Management',
        icon: Megaphone,
        permission: 'view-ad-campaigns',
        basePath: '/admin/ad-campaigns',
        ownerKey: '/admin/ad-campaigns',
        children: [
          { label: 'All Campaigns', to: '/admin/ad-campaigns' },
          { label: 'Pending Review', to: '/admin/ad-campaigns?approval_status=pending' },
          { label: 'Active Ads', to: '/admin/ad-campaigns?status=active' },
          { label: 'Analytics Overview', to: '/admin/ad-campaigns/analytics' },
          { label: 'Campaign Settings', to: '/admin/ad-campaigns/settings' },
        ],
      },
      {
        label: 'Reward Management',
        icon: Gift,
        basePath: '/admin/rewards',
        ownerKey: '/admin/rewards',
        children: [
          { label: 'Reward Rules', to: '/admin/rewards/rules' },
          { label: 'Reward History', to: '/admin/rewards/history' },
        ],
      },
      {
        label: 'Withdrawal Request',
        icon: ArrowUpRight,
        to: '/admin/funds/withdrawals',
        basePath: '/admin/funds/withdrawals',
        ownerKey: '/admin/funds/withdrawals',
        children: [
          { label: 'Withdrawal Requests', to: '/admin/funds/withdrawals' },
        ],
      },
      {
        label: 'Deposit Requests',
        icon: Wallet,
        basePath: '/admin/funds',
        ownerKey: '/admin/funds',
        children: [
          { label: 'All Deposit Requests', to: '/admin/funds/deposits' },
          { label: 'Deposit Settings', to: '/admin/funds/deposit-settings' },
        ],
      },
      {
        label: 'Communities',
        icon: Globe,
        permission: 'view-communities',
        basePath: '/admin/communities',
        ownerKey: '/admin/communities',
        children: [
          { label: 'All Communities', to: '/admin/communities' },
        ],
      },
      {
        label: 'Marketplace',
        icon: ShoppingBag,
        permission: 'view-marketplace',
        basePath: '/admin/marketplace',
        ownerKey: '/admin/marketplace',
        children: [
          { label: 'All Products', to: '/admin/marketplace' },
        ],
      },
      /*
      {
        label: 'Events Management',
        icon: Calendar,
        permission: 'view-events',
        basePath: '/admin/events',
        ownerKey: '/admin/events',
        children: [
          { label: 'All Events', to: '/admin/events' },
          { label: 'Event Control Center', to: '/admin/events/campaigns' },
        ],
      },
      */
    ],
  },
  {
    title: 'Security & Oversight',
    items: [
      {
        label: 'Moderation Center',
        icon: AlertCircle,
        permission: 'view-reports',
        basePath: '/admin/reports',
        ownerKey: '/admin/reports',
        children: [
          { label: 'All Reports Queue', to: '/admin/reports' },
        ],
      },
      {
        label: 'Feedback & Suggestions',
        icon: MessageSquare,
        permission: 'view-feedback',
        basePath: '/admin/feedback-suggestions',
        ownerKey: '/admin/feedback-suggestions',
        children: [
          { label: 'All Submissions', to: '/admin/feedback-suggestions' },
        ],
      },
      {
        label: 'Notifications Center',
        icon: Bell,
        permission: 'view-notifications',
        basePath: '/admin/notifications',
        ownerKey: '/admin/notifications',
        children: [
          { label: 'Notification Queue', to: '/admin/notifications' },
          { label: 'Send Broadcast', to: '/admin/notifications/broadcast' },
        ],
      },
    ],
  },
  {
    title: 'Platform & Diagnostics',
    items: [
      {
        label: 'Analytics Dashboard',
        to: '/admin/analytics',
        icon: BarChart2,
        permission: 'view-analytics',
        ownerKey: '/admin/analytics',
      },
    ],
  },
];

/**
 * Determine canonical route owner path
 */
function getCanonicalRouteOwner(pathname) {
  const cleanPath = pathname.split('?')[0];
  if (cleanPath === '/admin' || cleanPath === '/admin/dashboard') return '/admin/dashboard';
  if (cleanPath.startsWith('/admin/members')) return '/admin/members';
  if (cleanPath.startsWith('/admin/posts')) return '/admin/posts';
  if (cleanPath.startsWith('/admin/stories')) return '/admin/stories';
  if (cleanPath.startsWith('/admin/business-pages')) return '/admin/business-pages';
  if (cleanPath.startsWith('/admin/ad-campaigns')) return '/admin/ad-campaigns';
  if (cleanPath.startsWith('/admin/rewards')) return '/admin/rewards';
  if (cleanPath.startsWith('/admin/funds/withdrawals') || cleanPath.startsWith('/admin/withdrawals')) return '/admin/funds/withdrawals';
  if (cleanPath.startsWith('/admin/funds')) return '/admin/funds';
  if (cleanPath.startsWith('/admin/communities')) return '/admin/communities';
  if (cleanPath.startsWith('/admin/marketplace')) return '/admin/marketplace';
  if (cleanPath.startsWith('/admin/events')) return '/admin/events';
  if (cleanPath.startsWith('/admin/reports')) return '/admin/reports';
  if (cleanPath.startsWith('/admin/feedback-suggestions')) return '/admin/feedback-suggestions';
  if (cleanPath.startsWith('/admin/roles')) return '/admin/roles';
  if (cleanPath.startsWith('/admin/notifications')) return '/admin/notifications';
  if (cleanPath.startsWith('/admin/analytics')) return '/admin/analytics';
  if (cleanPath.startsWith('/admin/settings')) return '/admin/settings';
  if (cleanPath.startsWith('/admin/system')) return '/admin/system';
  return cleanPath;
}

export function AdminSidebar({
  mobileOpen,
  onCloseMobile,
  collapsed,
}) {
  const location = useLocation();
  const navigate = useNavigate();
  const { hasPermission } = useAuth();
  const { branding } = useBranding();
  const [userToggledGroups, setUserToggledGroups] = useState({});
  const [logoLoadFailed, setLogoLoadFailed] = useState(false);
  const logoKey = `${branding?.site_dark_logo_url || ''}-${branding?.site_logo_url || ''}`;
  const [prevLogoKey, setPrevLogoKey] = useState(logoKey);

  if (prevLogoKey !== logoKey) {
    setPrevLogoKey(logoKey);
    setLogoLoadFailed(false);
  }

  // Keyboard accessibility: Close mobile drawer on Escape key
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && mobileOpen) {
        onCloseMobile();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [mobileOpen, onCloseMobile]);

  const canonicalOwner = getCanonicalRouteOwner(location.pathname);

  const isLinkActive = (path, siblings = []) => {
    if (!path) return false;

    const [cleanPath, queryString] = path.split('?');
    const currentPath = location.pathname;
    const currentParams = new URLSearchParams(location.search);

    // 1. Dashboard special case
    if (cleanPath === '/admin/dashboard') {
      return currentPath === '/admin/dashboard' || currentPath === '/admin';
    }

    // 2. If target path has query parameters (e.g. ?approval_status=pending or ?status=active)
    if (queryString) {
      if (currentPath !== cleanPath) return false;
      const targetParams = new URLSearchParams(queryString);
      for (const [key, val] of targetParams.entries()) {
        if (currentParams.get(key) !== val) {
          return false;
        }
      }
      return true;
    }

    // 3. Target path does NOT have query parameters
    // Check if another sibling with query params matches the current search on the same cleanPath
    const hasMatchingQuerySibling = siblings.some((sib) => {
      if (!sib.to || !sib.to.includes('?')) return false;
      const [sibClean, sibQuery] = sib.to.split('?');
      if (sibClean !== cleanPath || currentPath !== cleanPath) return false;
      const sibParams = new URLSearchParams(sibQuery);
      for (const [key, val] of sibParams.entries()) {
        if (currentParams.get(key) !== val) return false;
      }
      return true;
    });

    if (hasMatchingQuerySibling) {
      return false;
    }

    // 4. Exact pathname match without matching query siblings
    if (currentPath === cleanPath) {
      return true;
    }

    // 5. Nested sub-route prefix match (e.g. /admin/roles/create under /admin/roles if no specific sibling)
    if (currentPath.startsWith(cleanPath + '/')) {
      const hasMoreSpecificSibling = siblings.some((sib) => {
        const sibClean = sib.to?.split('?')[0];
        if (!sibClean || sibClean === cleanPath) return false;
        return currentPath === sibClean || currentPath.startsWith(sibClean + '/');
      });
      return !hasMoreSpecificSibling;
    }

    return false;
  };

  const isGroupActive = (item) => {
    if (item.ownerKey) {
      return item.ownerKey === canonicalOwner;
    }
    if (item.basePath) {
      return item.basePath === canonicalOwner;
    }
    return item.children?.some((child) => isLinkActive(child.to, item.children));
  };

  const isGroupExpanded = (item) => {
    if (userToggledGroups[item.label] !== undefined) {
      return userToggledGroups[item.label];
    }
    return isGroupActive(item);
  };

  const toggleGroup = (label, currentlyOpen) => {
    setUserToggledGroups((prev) => ({ ...prev, [label]: !currentlyOpen }));
  };

  const sidebarContent = (
    <div className="flex flex-col h-full bg-[#0f172a] text-slate-300 select-none">
      {/* Brand Header */}
      <div className="h-16 flex items-center justify-between px-4 border-b border-slate-800 shrink-0">
        <NavLink to="/admin/dashboard" className="flex items-center space-x-3 text-white font-bold text-base tracking-wide min-w-0">
          {!logoLoadFailed && (branding?.site_dark_logo_url || branding?.site_logo_url || BRAND_LOGO) ? (
            <img
              src={branding.site_dark_logo_url || branding.site_logo_url || BRAND_LOGO}
              alt={branding.site_name || 'Brand Logo'}
              className="h-8 w-auto max-w-[120px] object-contain shrink-0"
              onError={(e) => {
                if (e.currentTarget.src !== BRAND_LOGO) {
                  e.currentTarget.src = BRAND_LOGO;
                } else {
                  setLogoLoadFailed(true);
                }
              }}
            />
          ) : (
            <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-md shrink-0">
              <Sparkles className="w-4 h-4" />
            </div>
          )}
          {!collapsed && (
            <div className="flex flex-col min-w-0">
              <span className="leading-tight font-extrabold text-white truncate max-w-[120px]">
                {branding?.site_name || 'MLM Book'}
              </span>
              <span className="text-[10px] text-blue-400 font-semibold tracking-wider uppercase">Admin Portal</span>
            </div>
          )}
        </NavLink>

        {/* Mobile close button */}
        <button
          type="button"
          onClick={onCloseMobile}
          className="lg:hidden p-1.5 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-hidden cursor-pointer"
          aria-label="Close sidebar"
        >
          <X className="w-5 h-5" />
        </button>
      </div>

      {/* Navigation Links Scrollable Body */}
      <div className="flex-1 overflow-y-auto px-3 py-4 space-y-6 sidebar-scrollbar">
        {NAV_GROUPS.map((group, groupIdx) => {
          const visibleItems = group.items.filter((item) => {
            if (item.permission && !hasPermission(item.permission)) {
              return false;
            }
            return true;
          });

          if (visibleItems.length === 0) return null;

          return (
            <div key={groupIdx} className="space-y-1">
              {!collapsed && (
                <h3 className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                  {group.title}
                </h3>
              )}

              <div className="space-y-0.5">
                {visibleItems.map((item, itemIdx) => {
                  const Icon = item.icon;

                  // Submenu Group
                  if (item.children) {
                    const groupOpen = isGroupExpanded(item);
                    const groupActive = isGroupActive(item);

                    return (
                      <div key={itemIdx} className="space-y-0.5">
                        <button
                          type="button"
                          onClick={() => {
                            if (collapsed) {
                              const target = item.children?.[0]?.to || item.basePath;
                              if (target) navigate(target);
                            } else {
                              toggleGroup(item.label, groupOpen);
                              if (item.to) {
                                navigate(item.to);
                              }
                            }
                          }}
                          className={`w-full flex items-center ${
                            collapsed ? 'justify-center px-2' : 'justify-between px-3'
                          } py-2 text-xs font-medium rounded-lg transition-colors cursor-pointer ${
                            groupActive
                              ? 'bg-blue-600/15 text-blue-400 font-semibold'
                              : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                          }`}
                          title={collapsed ? item.label : undefined}
                        >
                          <div className={`flex items-center ${collapsed ? '' : 'space-x-3'}`}>
                            <Icon className={`w-4 h-4 shrink-0 ${groupActive ? 'text-blue-400' : 'text-slate-400'}`} />
                            {!collapsed && <span>{item.label}</span>}
                          </div>
                          {!collapsed && (
                            <ChevronDown
                              className={`w-3.5 h-3.5 transition-transform duration-200 ${
                                groupOpen ? 'rotate-180 text-blue-400' : 'text-slate-500'
                              }`}
                            />
                          )}
                        </button>

                        {/* Submenu links */}
                        {!collapsed && groupOpen && (
                          <div className="pl-9 pr-2 py-1 space-y-0.5">
                            {item.children.map((child, childIdx) => {
                              const childActive = isLinkActive(child.to, item.children) && groupActive;
                              return (
                                <NavLink
                                  key={childIdx}
                                  to={child.to}
                                  onClick={onCloseMobile}
                                  className={`block px-3 py-1.5 text-xs rounded-lg transition-colors ${
                                    childActive
                                      ? 'bg-blue-600 text-white font-semibold shadow-xs'
                                      : 'text-slate-400 hover:text-white hover:bg-slate-800/60 font-medium'
                                  }`}
                                >
                                  {child.label}
                                </NavLink>
                              );
                            })}
                          </div>
                        )}
                      </div>
                    );
                  }

                  // Single Navigation Link
                  const active = isLinkActive(item.to) && (item.ownerKey === canonicalOwner);
                  return (
                    <NavLink
                      key={itemIdx}
                      to={item.to}
                      onClick={onCloseMobile}
                      className={`flex items-center ${
                        collapsed ? 'justify-center px-2' : 'space-x-3 px-3'
                      } py-2 text-xs font-medium rounded-lg transition-colors ${
                        active
                          ? 'bg-blue-600 text-white font-semibold shadow-xs'
                          : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                      }`}
                      title={collapsed ? item.label : undefined}
                    >
                      <Icon className={`w-4 h-4 shrink-0 ${active ? 'text-white' : 'text-slate-400'}`} />
                      {!collapsed && <span>{item.label}</span>}
                    </NavLink>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>

      {/* Sidebar Footer Status Pill */}
      {!collapsed && (
        <div className="p-3 m-3 rounded-lg bg-slate-800/50 border border-slate-700/50 flex items-center justify-between text-[11px] text-slate-400">
          <div className="flex items-center space-x-2">
            <div className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="font-medium text-slate-300">System Live</span>
          </div>
          <span className="font-mono text-[10px] text-slate-500">v1.0.0</span>
        </div>
      )}
    </div>
  );

  return (
    <>
      {/* Desktop Sidebar (Fixed) */}
      <aside
        className={`hidden lg:block shrink-0 transition-all duration-300 ease-in-out border-r border-slate-800 ${
          collapsed ? 'w-16' : 'w-64'
        }`}
      >
        {sidebarContent}
      </aside>

      {/* Mobile Off-Canvas Drawer Backdrop */}
      {mobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden transition-opacity"
          onClick={onCloseMobile}
          aria-hidden="true"
        />
      )}

      {/* Mobile Off-Canvas Drawer */}
      <div
        className={`fixed inset-y-0 left-0 z-50 w-72 transform transition-transform duration-300 ease-in-out lg:hidden shadow-2xl ${
          mobileOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {sidebarContent}
      </div>
    </>
  );
}

export default AdminSidebar;
