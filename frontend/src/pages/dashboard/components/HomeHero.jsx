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

        {/* Hero Primary Actions */}
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
