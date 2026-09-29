import { Link } from 'react-router-dom';
import {
  Sparkles,
  Rss,
  TvMinimalPlay,
  Users,
  Building2,
  Globe2,
  CalendarDays,
  Coins,
  ArrowRight,
} from 'lucide-react';

const SOCIAL_LINKS = [
  {
    name: 'YouTube',
    url: 'https://www.youtube.com/@MLMBookOfficail',
    color: '#ff0000',
    hoverBg: 'rgba(255, 0, 0, 0.08)',
    icon: (
      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
      </svg>
    ),
  },
  {
    name: 'Instagram',
    url: 'https://www.instagram.com/mlmbook29/',
    color: '#e4405f',
    hoverBg: 'rgba(228, 64, 95, 0.08)',
    icon: (
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
      </svg>
    ),
  },
  {
    name: 'Facebook',
    url: 'https://www.facebook.com/profile.php?id=61593794263511',
    color: '#1877f2',
    hoverBg: 'rgba(24, 119, 242, 0.08)',
    icon: (
      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
      </svg>
    ),
  },
  {
    name: 'LinkedIn',
    url: 'https://linkedin.com/in/mlm-book-248045423',
    color: '#0a66c2',
    hoverBg: 'rgba(10, 102, 194, 0.08)',
    icon: (
      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
        <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
      </svg>
    ),
  },
];

export function HomeHero() {
  return (
    <section className="card home-hero-v2" aria-label="Welcome to MLM Book">
      <div className="home-hero-v2__glow" aria-hidden="true" />

      <div className="home-hero-v2__content">
        {/* Top Floating Badge */}
        <div className="home-hero-v2__badge">
          <Sparkles size={14} className="home-hero-v2__badge-icon" aria-hidden="true" />
          <span>The Next-Generation Digital Social & Business Ecosystem</span>
          <span className="home-hero-v2__badge-pill">Unified Platform</span>
        </div>

        {/* Hero Title & Intro */}
        <h1 className="home-hero-v2__title">
          Connect. Create. Grow. <span className="text-gradient">Earn Rewards.</span>
        </h1>

        <p className="home-hero-v2__subtitle">
          MLM Book is a unified social and digital business ecosystem where people connect, communities grow, businesses promote their brands, creators share content, events bring people together, and eligible members can earn rewards through qualifying campaign interactions.
        </p>

        {/* Hero Primary Actions & Official Social Channels */}
        <div className="home-hero-v2__actions">
          <Link className="member-button member-button--primary hero-btn" to="/member/socials">
            <Rss size={17} aria-hidden="true" />
            <span>Explore Social Feed</span>
            <ArrowRight size={15} aria-hidden="true" />
          </Link>

          <Link className="member-button member-button--secondary hero-btn" to="/member/watch">
            <TvMinimalPlay size={17} aria-hidden="true" />
            <span>Watch Hub</span>
          </Link>

          <Link className="member-button member-button--secondary hero-btn" to="/member/community">
            <Users size={17} aria-hidden="true" />
            <span>Communities</span>
          </Link>

          <Link className="member-button member-button--secondary hero-btn" to="/member/business-directory">
            <Building2 size={17} aria-hidden="true" />
            <span>Business Directory</span>
          </Link>

          {/* Four Official Social Platform Channels */}
          <div
            className="home-hero-v2__socials"
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '8px',
              padding: '4px 6px',
              background: 'rgba(255, 255, 255, 0.85)',
              border: '1px solid rgba(226, 232, 240, 0.9)',
              borderRadius: '12px',
              backdropFilter: 'blur(8px)',
              marginLeft: 'auto',
            }}
          >
            {SOCIAL_LINKS.map((s) => (
              <a
                key={s.name}
                href={s.url}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={s.name}
                title={`Visit MLM Book on ${s.name}`}
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  width: '36px',
                  height: '36px',
                  borderRadius: '8px',
                  color: s.color,
                  backgroundColor: 'transparent',
                  transition: 'all 0.18s ease',
                  textDecoration: 'none',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = s.hoverBg;
                  e.currentTarget.style.transform = 'translateY(-2px)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = 'transparent';
                  e.currentTarget.style.transform = 'translateY(0)';
                }}
              >
                {s.icon}
              </a>
            ))}
          </div>
        </div>

        {/* Compact Highlight Items */}
        <div className="home-hero-v2__stats">
          <div className="hero-stat-card">
            <div className="hero-stat-card__icon hero-stat-card__icon--blue">
              <Globe2 size={18} aria-hidden="true" />
            </div>
            <div>
              <div className="hero-stat-card__val">Social Networking</div>
              <div className="hero-stat-card__lbl">Connect & Share</div>
            </div>
          </div>

          <div className="hero-stat-card">
            <div className="hero-stat-card__icon hero-stat-card__icon--purple">
              <Building2 size={18} aria-hidden="true" />
            </div>
            <div>
              <div className="hero-stat-card__val">Business Growth</div>
              <div className="hero-stat-card__lbl">Pages & Promotion</div>
            </div>
          </div>

          <div className="hero-stat-card">
            <div className="hero-stat-card__icon hero-stat-card__icon--green">
              <CalendarDays size={18} aria-hidden="true" />
            </div>
            <div>
              <div className="hero-stat-card__val">Communities & Events</div>
              <div className="hero-stat-card__lbl">Engage & Gather</div>
            </div>
          </div>

          <div className="hero-stat-card">
            <div className="hero-stat-card__icon hero-stat-card__icon--amber">
              <Coins size={18} aria-hidden="true" />
            </div>
            <div>
              <div className="hero-stat-card__val">Ads & Rewards</div>
              <div className="hero-stat-card__lbl">Qualifying Campaigns</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default HomeHero;
