import { useState } from 'react';
import {
  Layers,
  Sparkles,
  Play,
  CheckCircle2,
  Users,
  Building2,
  Coins,
} from 'lucide-react';

export function HomeWhatIs() {
  const [isPlaying, setIsPlaying] = useState(false);

  return (
    <section className="card home-section home-what-is-section" aria-labelledby="what-is-heading">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div className="home-hub-badge home-hub-badge--blue">
          <Layers size={13} aria-hidden="true" />
          <span>Ecosystem Overview</span>
        </div>
        <h2 className="home-section__title" id="what-is-heading" style={{ fontSize: '1.45rem' }}>
          <Sparkles size={22} aria-hidden="true" />
          <span>One Platform. Multiple Ways to Connect and Grow.</span>
        </h2>
        <p className="home-section__desc" style={{ fontSize: '14.5px', lineHeight: '1.65' }}>
          MLM Book brings social networking, content sharing, video discovery, business pages, business discovery, communities, events, messaging, advertising and rewards into one connected digital ecosystem.
        </p>
      </div>

      {/* 3 Connected Core Pillars */}
      <div className="home-grid home-grid--3" style={{ marginBottom: '28px' }}>
        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon home-info-card-v2__icon--primary">
              <Users size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag">Social & Media</span>
          </div>
          <h3 className="home-info-card-v2__title">Connect & Share</h3>
          <p className="home-info-card-v2__desc">
            Engage with your network through interactive posts, rich stories, video discovery in Watch, direct messaging, and niche interest communities.
          </p>
        </div>

        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon" style={{ background: 'rgba(124, 58, 237, 0.12)', color: '#7c3aed' }}>
              <Building2 size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag" style={{ background: 'rgba(124, 58, 237, 0.1)', color: '#7c3aed' }}>
              Business & Events
            </span>
          </div>
          <h3 className="home-info-card-v2__title">Build Your Presence</h3>
          <p className="home-info-card-v2__desc">
            Establish professional Business Pages, get discovered in the Business Directory, organize live events, and collaborate with team members.
          </p>
        </div>

        <div className="home-info-card-v2">
          <div className="home-info-card-v2__header">
            <div className="home-info-card-v2__icon" style={{ background: 'rgba(245, 158, 11, 0.12)', color: '#f59e0b' }}>
              <Coins size={22} aria-hidden="true" />
            </div>
            <span className="home-info-card-v2__tag" style={{ background: 'rgba(245, 158, 11, 0.1)', color: '#d97706' }}>
              Ads & Rewards
            </span>
          </div>
          <h3 className="home-info-card-v2__title">Campaign Ecosystem</h3>
          <p className="home-info-card-v2__desc">
            Promote qualifying business content through supported advertising campaigns and receive rewards in your Wallet upon meeting campaign conditions.
          </p>
        </div>
      </div>

      {/* Embedded Official Video Presentation */}
      <div className="video-hub-layout" style={{ marginTop: '12px' }}>
        <div className="video-hub-player-col">
          <div className="video-hub-frame">
            {isPlaying ? (
              <iframe
                className="video-hub-iframe"
                src="https://www.youtube-nocookie.com/embed/L_LUpnjgPso?autoplay=1&rel=0"
                title="MLM Book Official Ecosystem Introduction"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
              />
            ) : (
              <div
                className="video-hub-poster"
                onClick={() => setIsPlaying(true)}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && setIsPlaying(true)}
                aria-label="Play MLM Book Introduction Video"
              >
                <img
                  src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80"
                  alt="MLM Book Ecosystem Overview"
                  className="video-hub-poster__img"
                  loading="lazy"
                />
                <div className="video-hub-poster__overlay" />
                <div className="video-hub-play-button-wrap">
                  <div className="video-hub-play-button">
                    <Play size={28} fill="currentColor" aria-hidden="true" />
                  </div>
                  <span className="video-hub-play-label">Watch Platform Introduction</span>
                </div>
                <div className="video-hub-poster__info">
                  <span className="video-hub-tag">Official Overview</span>
                  <span className="video-hub-duration">3:45</span>
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="video-hub-detail-col">
          <div className="video-hub-card">
            <div className="video-hub-card__header">
              <span className="video-hub-badge-pill video-hub-badge-pill--blue">Unified Ecosystem</span>
              <span className="video-hub-card__duration">Full Video Guide</span>
            </div>
            <h3 className="video-hub-card__title">Explore the Vision of MLM Book</h3>
            <p className="video-hub-card__desc">
              Watch our overview video to see how authentic social connections, creator media, verified business tools, and campaign-based rewards work seamlessly together.
            </p>

            <div className="video-hub-card__highlights-wrap">
              <div className="video-hub-card__highlights-title">Key Platform Highlights</div>
              <ul className="video-hub-card__highlights-list">
                <li className="video-hub-card__highlight-item">
                  <CheckCircle2 size={16} className="highlight-check-icon" aria-hidden="true" />
                  <span>Integrated social networking, creator videos & business directory</span>
                </li>
                <li className="video-hub-card__highlight-item">
                  <CheckCircle2 size={16} className="highlight-check-icon" aria-hidden="true" />
                  <span>Mobile & WhatsApp verified member identity architecture</span>
                </li>
                <li className="video-hub-card__highlight-item">
                  <CheckCircle2 size={16} className="highlight-check-icon" aria-hidden="true" />
                  <span>Supported business & event campaigns with transparent reward rules</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default HomeWhatIs;
