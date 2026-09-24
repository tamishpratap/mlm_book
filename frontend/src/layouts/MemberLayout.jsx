import { useState, useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import MemberHeader from './components/MemberHeader';
import MemberSidebar from './components/MemberSidebar';

// Load Core Member CSS
import '../styles/dashboard.css';
import '../styles/member-posts.css';
import '../styles/member-stories.css';
import '../styles/member-notifications.css';
import '../styles/member-search.css';
import '../styles/member-friends.css';
import '../styles/member-people.css';
import '../styles/member-profile.css';
import '../styles/member-account.css';
import '../styles/member-community.css';
import '../styles/member-business-pages.css';
import '../styles/member-events.css';
import '../styles/member-marketplace.css';
import '../styles/member-watch.css';
import '../styles/member-blocked-users.css';
import '../styles/member-chat.css';

export function MemberLayout() {
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
  const location = useLocation();

  // Close mobile sidebar upon route change
  useEffect(() => {
    setMobileSidebarOpen(false);
  }, [location.pathname]);

  // Sync body scroll lock and menu-open stacking context
  useEffect(() => {
    if (mobileSidebarOpen) {
      document.body.classList.add('menu-open');
    } else {
      document.body.classList.remove('menu-open');
    }
    return () => {
      document.body.classList.remove('menu-open');
    };
  }, [mobileSidebarOpen]);

  // Close mobile sidebar if window resizes to desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 1024) {
        setMobileSidebarOpen(false);
      }
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const isDashboard = location.pathname === '/member/dashboard' || location.pathname === '/';

  const isThreeColumn = [
    '/member/socials',
    '/member/stories',
    '/member/posts',
    '/member/saved-posts',
    '/member/watch',
    '/member/connections',
    '/member/friends',
    '/member/friend-requests',
    '/member/people',
    '/member/blocked-users',
    '/member/notifications',
    '/member/search',
  ].some((p) => location.pathname.startsWith(p));

  const shellClass = isDashboard
    ? 'page-shell--full'
    : isThreeColumn
    ? ''
    : 'page-shell--single';

  return (
    <div className="member-app-root" key={location.pathname}>
      <MemberHeader
        isSidebarOpen={mobileSidebarOpen}
        onToggleSidebar={() => setMobileSidebarOpen((prev) => !prev)}
      />

      <div className={`page-shell ${shellClass}`}>
        <MemberSidebar
          isOpen={mobileSidebarOpen}
          onCloseMobile={() => setMobileSidebarOpen(false)}
        />

        <Outlet />

        {mobileSidebarOpen && (
          <div
            className="sidebar-scrim is-open is-visible"
            data-sidebar-scrim
            onClick={() => setMobileSidebarOpen(false)}
            style={{ display: 'block' }}
          />
        )}
      </div>
    </div>
  );
}

export default MemberLayout;
