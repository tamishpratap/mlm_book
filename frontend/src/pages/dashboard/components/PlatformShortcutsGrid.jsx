import { Link } from 'react-router-dom';
import {
  Rss,
  MonitorPlay,
  Compass,
  Users,
  Calendar,
  Bookmark,
  Sparkles,
  Settings,
} from 'lucide-react';

const SHORTCUTS = [
  {
    name: 'Socials Feed',
    description: 'Posts, stories, likes & discussions',
    path: '/member/socials',
    icon: Rss,
    color: '#3b82f6',
    bgColor: '#eff6ff',
  },
  {
    name: 'Watch Videos',
    description: 'Video posts & creators platform',
    path: '/member/watch',
    icon: MonitorPlay,
    color: '#ef4444',
    bgColor: '#fef2f2',
  },
  {
    name: 'Business Directory',
    description: 'Discover brands & enterprises',
    path: '/member/business-directory',
    icon: Compass,
    color: '#8b5cf6',
    bgColor: '#f5f3ff',
  },
  {
    name: 'Communities',
    description: 'Public & private networking groups',
    path: '/member/community',
    icon: Users,
    color: '#10b981',
    bgColor: '#ecfdf5',
  },
  {
    name: 'Upcoming Events',
    description: 'Networking meetups & schedules',
    path: '/member/events',
    icon: Calendar,
    color: '#f59e0b',
    bgColor: '#fffbeb',
  },
  {
    name: 'Saved Posts',
    description: 'Bookmarked updates & articles',
    path: '/member/saved-posts',
    icon: Bookmark,
    color: '#6366f1',
    bgColor: '#eef2ff',
  },
];

export function PlatformShortcutsGrid() {
  return (
    <section className="dash-wallet-section" aria-label="Platform Quick Shortcuts">
      <div className="dash-section-header" style={{ marginBottom: '14px' }}>
        <div className="dash-section-title">
          <div className="dash-section-icon" style={{ backgroundColor: 'rgba(79, 125, 243, 0.1)', color: '#4f7df3' }}>
            <Sparkles size={20} />
          </div>
          <div>
            <h2>Quick Navigation Hub</h2>
            <p style={{ margin: 0, fontSize: '0.825rem', color: '#64748b' }}>
              Jump directly into social channels, communities, and directory features
            </p>
          </div>
        </div>
      </div>

      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))',
          gap: '12px',
        }}
      >
        {SHORTCUTS.map((item) => {
          const Icon = item.icon;
          return (
            <Link
              key={item.name}
              to={item.path}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                padding: '12px 14px',
                backgroundColor: '#ffffff',
                border: '1px solid #e2e8f0',
                borderRadius: '12px',
                textDecoration: 'none',
                transition: 'all 0.18s ease',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.transform = 'translateY(-2px)';
                e.currentTarget.style.borderColor = item.color;
                e.currentTarget.style.boxShadow = '0 6px 12px -2px rgba(15, 23, 42, 0.06)';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.transform = 'none';
                e.currentTarget.style.borderColor = '#e2e8f0';
                e.currentTarget.style.boxShadow = 'none';
              }}
            >
              <div
                style={{
                  width: '38px',
                  height: '38px',
                  borderRadius: '10px',
                  backgroundColor: item.bgColor,
                  color: item.color,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  flexShrink: 0,
                }}
              >
                <Icon size={18} />
              </div>

              <div style={{ overflow: 'hidden' }}>
                <div style={{ fontWeight: 700, fontSize: '0.875rem', color: '#1e293b', whiteSpace: 'nowrap', textOverflow: 'ellipsis', overflow: 'hidden' }}>
                  {item.name}
                </div>
                <div style={{ fontSize: '0.725rem', color: '#64748b', whiteSpace: 'nowrap', textOverflow: 'ellipsis', overflow: 'hidden' }}>
                  {item.description}
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}

export default PlatformShortcutsGrid;
