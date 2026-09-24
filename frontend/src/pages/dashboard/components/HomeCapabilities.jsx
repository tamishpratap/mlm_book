import { Link } from 'react-router-dom';
import {
  LayoutGrid,
  Rss,
  TvMinimalPlay,
  Users,
  Building2,
  CalendarDays,
  UserPlus,
  // MessageSquare,
  ArrowRight,
} from 'lucide-react';

const FEATURES = [
  {
    icon: Rss,
    title: 'Socials',
    tag: 'Social Feed',
    badgeColor: 'blue',
    description: 'Post photos, videos and updates, react, comment, share and connect with your network.',
    path: '/member/socials',
    linkText: 'Open Socials',
  },
  {
    icon: TvMinimalPlay,
    title: 'Watch',
    tag: 'Video',
    badgeColor: 'green',
    description: 'Discover and share video content through the dedicated Watch experience.',
    path: '/member/watch',
    linkText: 'Explore Watch',
  },
  {
    icon: Users,
    title: 'Communities',
    tag: 'Groups',
    badgeColor: 'indigo',
    description: 'Build or join focused communities and participate in discussions and shared interests.',
    path: '/member/community',
    linkText: 'Browse Communities',
  },
  {
    icon: Building2,
    title: 'Business Pages',
    tag: 'Brands',
    badgeColor: 'purple',
    description: 'Create and manage a professional Business Page for your brand.',
    path: '/member/business-pages',
    linkText: 'Manage Pages',
  },
  {
    icon: Building2,
    title: 'Business Directory',
    tag: 'Discovery',
    badgeColor: 'amber',
    description: 'Discover businesses and make your brand easier to find.',
    path: '/member/business-directory',
    linkText: 'View Directory',
  },
  {
    icon: CalendarDays,
    title: 'Events',
    tag: 'Gatherings',
    badgeColor: 'blue',
    description: 'Create and discover events, connect with participants and grow engagement.',
    path: '/member/events',
    linkText: 'Discover Events',
  },
  {
    icon: UserPlus,
    title: 'Connections',
    tag: 'Network',
    badgeColor: 'green',
    description: 'Build your personal and professional network.',
    path: '/member/connections',
    linkText: 'View Connections',
  },
  /*
  {
    icon: MessageSquare,
    title: 'Messaging',
    tag: 'Direct Chat',
    badgeColor: 'cyan',
    description: 'Communicate directly with people in your network.',
    path: '/member/messages',
    linkText: 'Open Messages',
  },
  */
];

export function HomeCapabilities() {
  return (
    <section className="card home-section home-capabilities-section" aria-labelledby="capabilities-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
          <div>
            <div className="home-hub-badge home-hub-badge--blue">
              <LayoutGrid size={13} aria-hidden="true" />
              <span>Platform Features</span>
            </div>
            <h2 className="home-section__title" id="capabilities-heading" style={{ fontSize: '1.45rem' }}>
              <LayoutGrid size={22} aria-hidden="true" />
              <span>What Can You Do on MLM Book?</span>
            </h2>
            <p className="home-section__desc">
              Explore the rich suite of interconnected tools built to empower members, creators, and businesses across the digital ecosystem.
            </p>
          </div>
        </div>
      </div>

      {/* Feature Cards in 4-Column Grid */}
      <div className="home-grid home-grid--4">
        {FEATURES.map((item) => {
          const IconComponent = item.icon;
          return (
            <div key={item.title} className="home-feature-card-v2">
              <div className="home-feature-card-v2__top">
                <div className={`home-feature-card-v2__icon home-feature-card-v2__icon--${item.badgeColor}`}>
                  <IconComponent size={22} aria-hidden="true" />
                </div>
                <span className={`home-feature-card-v2__tag home-feature-card-v2__tag--${item.badgeColor}`}>
                  {item.tag}
                </span>
              </div>

              <h3 className="home-feature-card-v2__title">{item.title}</h3>
              <p className="home-feature-card-v2__desc">{item.description}</p>

              <Link to={item.path} className="home-feature-card-v2__link">
                <span>{item.linkText}</span>
                <ArrowRight size={13} aria-hidden="true" />
              </Link>
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeCapabilities;
