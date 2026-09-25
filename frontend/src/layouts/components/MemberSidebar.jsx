import { useState, useEffect } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { ArrowDownToLine } from "lucide-react";
import {
  ChevronRight,
  House,
  Sparkles,
  UserRound,
  UsersRound,
  Bookmark,
  UserX,
  MonitorPlay,
  // Store,
  Building2,
  Compass,
  Calendar,
  Settings,
  Gift,
  MessageSquareText,
  Wallet,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import { getAvatarUrl } from '../../utils/assetHelper';

export function MemberSidebar({ isOpen, onCloseMobile }) {
  const { user } = useAuth();
  const location = useLocation();
  const [avatarImgError, setAvatarImgError] = useState(false);

  useEffect(() => {
    setAvatarImgError(false);
  }, [user?.profile_photo]);

  if (!user) return null;

  const initials = user.name
    ? user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
    : 'MB';

  const avatarUrl = getAvatarUrl(user.profile_photo);

  // Exact & prefix matching helper for canonical sidebar items
  const isItemActive = (path, exact = false) => {
    if (exact) {
      return location.pathname === path;
    }
    if (path === '/member/profile') {
      return location.pathname === '/member/profile' || location.pathname === '/member/profile/edit';
    }
    if (path === '/member/business-pages') {
      return (
        location.pathname.startsWith('/member/business-pages') &&
        !location.pathname.startsWith('/member/business-directory')
      );
    }
    if (path === '/member/blocked-users') {
      return location.pathname === '/member/blocked-users' || location.pathname === '/member/disconnections';
    }
    if (path === '/member/account/settings') {
      return location.pathname.startsWith('/member/account');
    }
    if (path === '/member/deposit') {
      return location.pathname.startsWith('/member/deposit');
    }
    if (path === '/member/feedback-suggestions') {
      return location.pathname.startsWith('/member/feedback-suggestions');
    }
    if (path === '/member/withdrawal') {
      return location.pathname.startsWith('/member/withdrawal') || location.pathname.startsWith('/member/wallet/withdrawal');
    }
    return location.pathname.startsWith(path);
  };

  const getSideNavClass = (path, exact = false) => {
    return `side-nav__item ${isItemActive(path, exact) ? 'is-active' : ''}`;
  };

  return (
    <aside className={`left-sidebar ${isOpen ? 'is-open' : ''}`} data-sidebar>
      <section className="sidebar-card">
        <Link
          className="sidebar-profile"
          to="/member/profile"
          onClick={onCloseMobile}
        >
          {user.profile_photo && !avatarImgError ? (
            <img
              className="avatar avatar--lg"
              src={avatarUrl}
              alt={user.name}
              onError={() => setAvatarImgError(true)}
            />
          ) : (
            <span
              className="avatar avatar--lg post-avatar-initials"
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'linear-gradient(135deg, #176bff, #7146ed)',
                color: '#fff',
                fontWeight: 700,
                fontSize: '1.1rem',
                borderRadius: '50%',
                width: '48px',
                height: '48px',
              }}
            >
              {initials}
            </span>
          )}
          <div className="sidebar-profile__copy">
            <strong>{user.name}</strong>
            <span>View profile</span>
          </div>
          <ChevronRight size={18} className="sidebar-profile__arrow" />
        </Link>

        <nav className="side-nav" aria-label="Sidebar navigation">
          <NavLink
            to="/member/dashboard"
            className={getSideNavClass('/member/dashboard', true)}
            onClick={onCloseMobile}
          >
            <House size={18} />
            <span>Home</span>
          </NavLink>
          <NavLink
            to="/member/socials"
            className={getSideNavClass('/member/socials')}
            onClick={onCloseMobile}
          >
            <Sparkles size={18} />
            <span>Socials</span>
          </NavLink>
          <NavLink
            to="/member/profile"
            className={getSideNavClass('/member/profile')}
            onClick={onCloseMobile}
          >
            <UserRound size={18} />
            <span>My Profile</span>
          </NavLink>
          <NavLink
            to="/member/friends"
            className={getSideNavClass('/member/friends')}
            onClick={onCloseMobile}
          >
            <UsersRound size={18} />
            <span>Connections</span>
          </NavLink>
          <NavLink
            to="/member/people/suggestions"
            className={getSideNavClass('/member/people/suggestions', true)}
            onClick={onCloseMobile}
          >
            <Sparkles size={18} />
            <span>New Connections</span>
          </NavLink>
          <NavLink
            to="/member/saved-posts"
            className={getSideNavClass('/member/saved-posts', true)}
            onClick={onCloseMobile}
          >
            <Bookmark size={18} />
            <span>Saved Posts</span>
          </NavLink>
          <NavLink
            to="/member/blocked-users"
            className={getSideNavClass('/member/blocked-users', true)}
            onClick={onCloseMobile}
          >
            <UserX size={18} />
            <span>Disconnections</span>
          </NavLink>
          <NavLink
            to="/member/watch"
            className={getSideNavClass('/member/watch')}
            onClick={onCloseMobile}
          >
            <MonitorPlay size={18} />
            <span>Watch</span>
          </NavLink>
          <NavLink
            to="/member/community"
            className={getSideNavClass('/member/community')}
            onClick={onCloseMobile}
          >
            <UsersRound size={18} />
            <span>Community</span>
          </NavLink>
          {/* Marketplace - Temporarily disabled/hidden */}
          {/*
          <NavLink
            to="/member/marketplace"
            className={getSideNavClass('/member/marketplace')}
            onClick={onCloseMobile}
          >
            <Store size={18} />
            <span>Marketplace</span>
          </NavLink>
          */}
          <NavLink
            to="/member/business-pages"
            className={getSideNavClass('/member/business-pages')}
            onClick={onCloseMobile}
          >
            <Building2 size={18} />
            <span>Business Pages</span>
          </NavLink>
          <NavLink
            to="/member/business-directory"
            className={getSideNavClass('/member/business-directory')}
            onClick={onCloseMobile}
          >
            <Compass size={18} />
            <span>Business Directory</span>
          </NavLink>
          {/* Events - Temporarily disabled/hidden */}
          {/*
          <NavLink
            to="/member/events"
            className={getSideNavClass('/member/events')}
            onClick={onCloseMobile}
          >
            <Calendar size={18} />
            <span>Events</span>
          </NavLink>
          */}
          {/* Member Direct Messages - Temporarily disabled/hidden */}
          {/*
          <NavLink
            to="/member/messages"
            className={getSideNavClass('/member/messages')}
            onClick={onCloseMobile}
          >
            <MessageSquare size={18} />
            <span>Messages</span>
          </NavLink>
          */}
          <NavLink
            to="/member/deposit"
            className={getSideNavClass('/member/deposit')}
            onClick={onCloseMobile}
          >
            <Wallet size={18} />
            <span>Fund Wallet</span>
          </NavLink>
          <NavLink
            to="/member/account/settings"
            className={getSideNavClass('/member/account/settings')}
            onClick={onCloseMobile}
          >
            <Settings size={18} />
            <span>Account Settings</span>
          </NavLink>
          <NavLink
            to="/member/feedback-suggestions"
            className={getSideNavClass('/member/feedback-suggestions')}
            onClick={onCloseMobile}
          >
            <MessageSquareText size={18} />
            <span>Feedback & Suggestions</span>
          </NavLink>
          <NavLink
            to="/member/withdrawal"
            className={getSideNavClass('/member/withdrawal')}
            onClick={onCloseMobile}
          >
            <ArrowDownToLine size={18} />
            <span>Withdrawal</span>
          </NavLink>
        </nav>

        <div className="sidebar-divider" />

        <div className="section-heading section-heading--compact">
          <h2>Shortcuts</h2>
        </div>
        <div className="shortcut-list">
          <button
            type="button"
            className="shortcut"
            onClick={() => {
              onCloseMobile?.();
              window.dispatchEvent(new CustomEvent('open-referral-modal'));
            }}
            style={{ width: '100%', background: 'none', border: 'none', textAlign: 'left', cursor: 'pointer' }}
          >
            <span
              className="shortcut-icon-badge"
              style={{
                background: user?.is_verified || user?.mobile_verified_at
                  ? 'rgba(23, 107, 255, 0.1)'
                  : 'rgba(245, 158, 11, 0.1)',
                color: user?.is_verified || user?.mobile_verified_at ? '#176bff' : '#d97706',
              }}
            >
              <Gift size={14} aria-hidden="true" />
            </span>
            <span>Referral Program</span>
          </button>
        </div>
      </section>
    </aside>
  );
}

export default MemberSidebar;
