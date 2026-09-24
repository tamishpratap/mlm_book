import { Link } from 'react-router-dom';
import {
  Rss,
  UsersRound,
  Sparkles,
  MonitorPlay,
  Users,
  Compass,
  Calendar,
} from 'lucide-react';

const SHORTCUT_ICONS = {
  rss: Rss,
  'users-round': UsersRound,
  sparkles: Sparkles,
  'monitor-play': MonitorPlay,
  users: Users,
  compass: Compass,
  calendar: Calendar,
};

const DEFAULT_SHORTCUTS = [
  { name: 'Socials Feed', path: '/member/socials', icon: 'rss' },
  { name: 'My Connections', path: '/member/friends', icon: 'users-round' },
  { name: 'New Connections', path: '/member/people/suggestions', icon: 'sparkles' },
  { name: 'Watch Videos', path: '/member/watch', icon: 'monitor-play' },
  { name: 'Community Groups', path: '/member/community', icon: 'users' },
  { name: 'Business Directory', path: '/member/business-directory', icon: 'compass' },
  { name: 'Upcoming Events', path: '/member/events', icon: 'calendar' },
];

export function DashboardRightSidebar({ member, shortcuts }) {
  const shortcutList = shortcuts && shortcuts.length > 0 ? shortcuts : DEFAULT_SHORTCUTS;

  return (
    <aside className="right-sidebar">
      {/* Quick Shortcuts Widget */}
      <section className="card widget platform-widget">
        <div className="section-heading">
          <h2>Quick Shortcuts</h2>
        </div>
        <div className="shortcut-list">
          {shortcutList.map((item, idx) => {
            const IconComponent = SHORTCUT_ICONS[item.icon] || Sparkles;
            return (
              <Link className="shortcut" to={item.path} key={idx}>
                <span className="shortcut-icon-badge">
                  <IconComponent size={16} aria-hidden="true" />
                </span>
                <span>{item.name}</span>
              </Link>
            );
          })}
        </div>
      </section>

      {/* Member Account Widget */}
      <section className="card widget profile-summary-widget">
        <div className="section-heading">
          <h2>Member Account</h2>
        </div>
        <div style={{ padding: '10px 0' }}>
          <p style={{ margin: '0 0 6px 0', fontWeight: 600, color: 'var(--color-text)' }}>
            {member?.name || 'Member Account'}
          </p>
          {member?.user_id && (
            <span
              className="member-search-results__badge"
              style={{ marginBottom: '10px', display: 'inline-block' }}
            >
              {member.user_id.startsWith('@') ? member.user_id : `@${member.user_id}`}
            </span>
          )}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', marginTop: '8px' }}>
            <Link className="soft-cta" to="/member/profile" style={{ textDecoration: 'none' }}>
              View My Profile
            </Link>
            <Link
              className="soft-cta"
              to="/member/account/settings"
              style={{ textDecoration: 'none', color: 'var(--color-text-secondary)' }}
            >
              Account Settings
            </Link>
          </div>
        </div>
      </section>
    </aside>
  );
}

export default DashboardRightSidebar;
