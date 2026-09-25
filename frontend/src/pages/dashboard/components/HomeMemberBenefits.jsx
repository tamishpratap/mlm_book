import { Link } from 'react-router-dom';
import { Users, TvMinimalPlay, UsersRound, CalendarDays, Gift, ArrowRight } from 'lucide-react';

const MEMBER_BENEFITS = [
  {
    title: 'Build Your Network',
    description: 'Connect with people, creators and professionals.',
    icon: Users,
    badgeColor: 'blue',
    path: '/member/connections',
    linkText: 'Connections',
  },
  {
    title: 'Discover Content',
    description: 'Explore Socials and Watch.',
    icon: TvMinimalPlay,
    badgeColor: 'green',
    path: '/member/watch',
    linkText: 'Explore Media',
  },
  {
    title: 'Join Communities',
    description: 'Find communities around shared interests.',
    icon: UsersRound,
    badgeColor: 'indigo',
    path: '/member/community',
    linkText: 'Communities',
  },
  /*
  {
    title: 'Discover Events',
    description: 'Explore events and connect with participants.',
    icon: CalendarDays,
    badgeColor: 'amber',
    path: '/member/events',
    linkText: 'Events',
  },
  */
  {
    title: 'Earn Eligible Rewards',
    description: 'Participate in supported campaign flows and earn when qualifying conditions are met.',
    icon: Gift,
    badgeColor: 'purple',
    path: '/member/socials',
    linkText: 'Campaigns',
  },
];

export function HomeMemberBenefits() {
  return (
    <section className="card home-section home-member-benefits-section" aria-labelledby="member-benefits-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--green">
          <Users size={13} aria-hidden="true" />
          <span>Member Growth & Experience</span>
        </div>
        <h2 className="home-section__title" id="member-benefits-heading" style={{ fontSize: '1.45rem' }}>
          <Users size={22} aria-hidden="true" />
          <span>Why Members Join MLM Book</span>
        </h2>
        <p className="home-section__desc">
          A platform built for real connections, engaging content discovery, vibrant communities, and legitimate reward opportunities.
        </p>
      </div>

      {/* 5 Member Benefit Cards */}
      <div className="home-grid home-grid--5">
        {MEMBER_BENEFITS.map((item) => {
          const IconComponent = item.icon;
          return (
            <div key={item.title} className="home-process-card">
              <div
                style={{
                  width: '36px',
                  height: '36px',
                  borderRadius: '10px',
                  display: 'grid',
                  placeItems: 'center',
                  background: 'rgba(79, 125, 243, 0.1)',
                  color: 'var(--color-primary, #4f7df3)',
                  marginBottom: '12px',
                }}
              >
                <IconComponent size={18} aria-hidden="true" />
              </div>

              <h3 className="home-process-card__title" style={{ fontSize: '0.95rem' }}>
                {item.title}
              </h3>
              <p className="home-process-card__desc" style={{ fontSize: '12.5px' }}>
                {item.description}
              </p>

              <Link
                to={item.path}
                className="workflow-card__link"
                style={{ marginTop: '12px', fontSize: '12px' }}
              >
                <span>{item.linkText}</span>
                <ArrowRight size={12} aria-hidden="true" />
              </Link>
            </div>
          );
        })}
      </div>
    </section>
  );
}

export default HomeMemberBenefits;
