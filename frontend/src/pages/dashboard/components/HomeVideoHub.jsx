import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  TvMinimalPlay,
  Play,
  Sparkles,
  CheckCircle2,
  Gift,
  Wallet,
  ArrowRight,
  ExternalLink,
  Rss,
  Building2,
  Compass,
  Maximize2,
  Minimize2,
} from 'lucide-react';

const VIDEO_PLAYLIST = [
  {
    id: 'overview',
    title: 'About MLM Book & Vision',
    category: 'Introduction',
    duration: '3:45',
    videoId: 'L_LUpnjgPso', // Official MLM Book overview video placeholder
    icon: Compass,
    badgeColor: 'blue',
    description:
      'Learn what makes MLM Book the world’s dedicated social and business ecosystem. Discover how we empower individuals, creators, and entrepreneurs to connect, collaborate, and scale.',
    highlights: [
      'Bridge authentic personal networking with sustainable business growth.',
      'Connect with verified leaders, creators, and professionals globally.',
      'Completely transparent, privacy-first digital architecture.',
    ],
    actionLink: '/member/socials',
    actionText: 'Explore Social Feed',
  },
  {
    id: 'socials',
    title: 'Social Stream & Stories',
    category: 'Socials',
    duration: '2:30',
    videoId: 'dQw4w9WgXcQ',
    icon: Rss,
    badgeColor: 'indigo',
    description:
      'Experience our fast, interactive social stream. Publish rich posts with images, videos, emotions, and attachments. Share 24-hour stories and engage with modern emoji reactions.',
    highlights: [
      'Rich text editor with media attachments and location tagging.',
      'Interactive 24-hour stories with instant reactions.',
      'Granular privacy controls: Public, Friends-only, or Custom.',
    ],
    actionLink: '/member/socials',
    actionText: 'Go to Social Feed',
  },
  {
    id: 'rewards',
    title: 'How to Earn Rewards',
    category: 'Rewards',
    duration: '3:20',
    videoId: 'jNQXAC9IVRw',
    icon: Gift,
    badgeColor: 'green',
    description:
      'Learn how eligible verified members earn USDT rewards by engaging with promoted posts, showing interest in campaigns, and managing their BEP-20 reward wallet.',
    highlights: [
      'Earn real rewards by showing interest in verified business campaigns.',
      'Transparent calculation with real-time credited wallet ledger.',
      'Instant payout requests to your verified BEP-20 USDT wallet address.',
    ],
    actionLink: '/member/withdrawal',
    actionText: 'View Reward Wallet',
  },
  {
    id: 'business',
    title: 'Pages & Advertising',
    category: 'Business',
    duration: '3:10',
    videoId: 'M7lc1UVf-VE',
    icon: Building2,
    badgeColor: 'purple',
    description:
      'Establish your brand presence. Create verified business pages, invite team members with specific roles (Admin, Editor, Moderator), and launch advertising campaigns to reach real members.',
    highlights: [
      'Showcase your company profile in the Global Business Directory.',
      'Manage multi-user team permissions with granular roles.',
      'Dedicated business messenger inbox and lead management.',
    ],
    actionLink: '/member/business-pages',
    actionText: 'Manage Business Pages',
  },
];

export function HomeVideoHub() {
  const [activeVideoId, setActiveVideoId] = useState('overview');
  const [isPlaying, setIsPlaying] = useState(false);
  const [isTheaterMode, setIsTheaterMode] = useState(false);

  const activeVideo = VIDEO_PLAYLIST.find((v) => v.id === activeVideoId) || VIDEO_PLAYLIST[0];

  const handleSelectVideo = (id) => {
    if (id !== activeVideoId) {
      setActiveVideoId(id);
      setIsPlaying(false);
    }
  };

  const embedUrl = `https://www.youtube-nocookie.com/embed/${activeVideo.videoId}?autoplay=1&rel=0&modestbranding=1`;

  return (
    <section className={`card home-section home-video-hub ${isTheaterMode ? 'home-video-hub--theater' : ''}`} aria-labelledby="video-hub-heading">
      {/* Section Header */}
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '14px' }}>
          <div>
            <div className="home-hub-badge">
              <Sparkles size={13} aria-hidden="true" />
              <span>Interactive Video Learning Center</span>
            </div>
            <h2 className="home-section__title" id="video-hub-heading" style={{ fontSize: '1.45rem' }}>
              <TvMinimalPlay size={26} aria-hidden="true" />
              <span>Discover MLM Book in Action</span>
            </h2>
            <p className="home-section__desc">
              Watch guided high-definition video walk-throughs and learn how each module inside MLM Book accelerates your networking and brand.
            </p>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <button
              type="button"
              className="member-button member-button--secondary"
              onClick={() => setIsTheaterMode((prev) => !prev)}
              aria-label={isTheaterMode ? 'Exit Theater Mode' : 'Enter Theater Mode'}
              style={{ fontSize: '13px', padding: '8px 14px' }}
            >
              {isTheaterMode ? <Minimize2 size={15} /> : <Maximize2 size={15} />}
              <span>{isTheaterMode ? 'Standard View' : 'Cinematic Theater View'}</span>
            </button>

            <Link to="/member/withdrawal" className="member-button member-button--secondary" style={{ fontSize: '13px', padding: '8px 14px' }}>
              <Wallet size={15} aria-hidden="true" />
              <span>Reward Wallet</span>
              <ArrowRight size={14} aria-hidden="true" />
            </Link>
          </div>
        </div>
      </div>

      {/* Playlist Navigation Tabs */}
      <div className="video-hub-tabs" role="tablist" aria-label="Feature Videos">
        {VIDEO_PLAYLIST.map((item) => {
          const IconComponent = item.icon;
          const isActive = item.id === activeVideoId;
          return (
            <button
              key={item.id}
              type="button"
              role="tab"
              aria-selected={isActive}
              className={`video-hub-tab-btn ${isActive ? 'is-active' : ''}`}
              onClick={() => handleSelectVideo(item.id)}
            >
              <span className={`video-hub-tab-btn__icon video-hub-tab-btn__icon--${item.badgeColor}`}>
                <IconComponent size={18} aria-hidden="true" />
              </span>
              <span className="video-hub-tab-btn__text">
                <span className="video-hub-tab-btn__category">{item.category}</span>
                <span className="video-hub-tab-btn__title">{item.title}</span>
              </span>
            </button>
          );
        })}
      </div>

      {/* Main Video Showcase Layout */}
      <div className={`video-hub-layout ${isTheaterMode ? 'video-hub-layout--theater' : ''}`}>
        {/* Large 16:9 Video Frame */}
        <div className="video-hub-player-col">
          <div className="video-hub-frame">
            {!isPlaying ? (
              <div
                className="video-hub-poster"
                onClick={() => setIsPlaying(true)}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    setIsPlaying(true);
                  }
                }}
                aria-label={`Play video: ${activeVideo.title}`}
              >
                {/* Video High-Res Thumbnail */}
                <img
                  src={`https://img.youtube.com/vi/${activeVideo.videoId}/maxresdefault.jpg`}
                  alt={activeVideo.title}
                  className="video-hub-poster__img"
                  onError={(e) => {
                    e.currentTarget.src = `https://img.youtube.com/vi/${activeVideo.videoId}/hqdefault.jpg`;
                  }}
                />

                <div className="video-hub-poster__overlay" />

                {/* Big Glowing Play Button */}
                <div className="video-hub-play-button-wrap">
                  <button type="button" className="video-hub-play-button" aria-label="Play Video">
                    <Play size={34} fill="currentColor" />
                  </button>
                  <span className="video-hub-play-label">Click to Watch Full Video</span>
                </div>

                {/* Bottom Bar Info */}
                <div className="video-hub-poster__info">
                  <span className="video-hub-tag">{activeVideo.category}</span>
                  <span className="video-hub-duration">{activeVideo.duration} mins</span>
                </div>
              </div>
            ) : (
              <iframe
                className="video-hub-iframe"
                src={embedUrl}
                title={activeVideo.title}
                frameBorder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowFullScreen
              />
            )}
          </div>
        </div>

        {/* Video Takeaways & Feature Highlights */}
        <div className="video-hub-detail-col">
          <div className="video-hub-card">
            <div className="video-hub-card__header">
              <span className={`video-hub-badge-pill video-hub-badge-pill--${activeVideo.badgeColor}`}>
                {activeVideo.category}
              </span>
              <span className="video-hub-card__duration">HD Video Guide • {activeVideo.duration} min</span>
            </div>

            <h3 className="video-hub-card__title">{activeVideo.title}</h3>
            <p className="video-hub-card__desc">{activeVideo.description}</p>

            <div className="video-hub-card__highlights-wrap">
              <h4 className="video-hub-card__highlights-title">Key Capabilities & Highlights</h4>
              <ul className="video-hub-card__highlights-list">
                {activeVideo.highlights.map((point, index) => (
                  <li key={index} className="video-hub-card__highlight-item">
                    <CheckCircle2 size={17} className="highlight-check-icon" aria-hidden="true" />
                    <span>{point}</span>
                  </li>
                ))}
              </ul>
            </div>

            <div className="video-hub-card__actions">
              <Link to={activeVideo.actionLink} className="member-button member-button--primary" style={{ padding: '10px 18px' }}>
                <span>{activeVideo.actionText}</span>
                <ArrowRight size={15} aria-hidden="true" />
              </Link>
              <a
                href={`https://www.youtube.com/watch?v=${activeVideo.videoId}`}
                target="_blank"
                rel="noopener noreferrer"
                className="member-button member-button--secondary"
                style={{ padding: '10px 16px' }}
              >
                <span>Watch on YouTube</span>
                <ExternalLink size={14} aria-hidden="true" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default HomeVideoHub;
