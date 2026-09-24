import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Play, TvMinimalPlay, Sparkles, CheckCircle2, Video, ArrowRight, ExternalLink } from 'lucide-react';

/**
 * HomeVideoMessage Component
 * Embedded YouTube Video showcase for MLM Book message and platform overview.
 */
export function HomeVideoMessage({
  videoId = 'L_LUpnjgPso', // Replace with official MLM Book YouTube video ID
  title = 'A Message About MLM Book',
  subtitle = 'Discover our vision, core values, and how MLM Book is revolutionizing digital networking.',
}) {
  const [isPlaying, setIsPlaying] = useState(false);

  // YouTube embed URL with privacy-enhanced mode
  const embedUrl = `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`;

  return (
    <section className="card home-section home-video-section" aria-labelledby="home-video-title">
      <div className="home-section__header" style={{ marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
          <div>
            <div className="home-video-badge" style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '4px 10px', background: 'rgba(79, 125, 243, 0.1)', color: 'var(--color-primary, #4f7df3)', borderRadius: '20px', fontSize: '12px', fontWeight: 600, marginBottom: '8px' }}>
              <Sparkles size={13} aria-hidden="true" />
              <span>Official Introduction</span>
            </div>
            <h2 className="home-section__title" id="home-video-title" style={{ margin: '0 0 6px 0', fontSize: '1.35rem' }}>
              <TvMinimalPlay size={22} aria-hidden="true" />
              <span>{title}</span>
            </h2>
            <p className="home-section__desc" style={{ margin: 0 }}>{subtitle}</p>
          </div>

          <Link
            to="/member/watch"
            className="member-button member-button--secondary"
            style={{ fontSize: '13px', padding: '8px 14px' }}
          >
            <Video size={15} aria-hidden="true" />
            <span>Explore Watch Hub</span>
            <ArrowRight size={14} aria-hidden="true" />
          </Link>
        </div>
      </div>

      <div className="home-video-layout">
        {/* Video Embed Frame */}
        <div className="home-video-player-wrap">
          <div className="home-video-frame">
            {!isPlaying ? (
              <div
                className="home-video-poster"
                onClick={() => setIsPlaying(true)}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    setIsPlaying(true);
                  }
                }}
                aria-label="Play MLM Book Introduction Video"
              >
                {/* Background Poster / Thumbnail */}
                <img
                  src={`https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`}
                  alt="MLM Book Introduction Video Thumbnail"
                  className="home-video-poster__img"
                  onError={(e) => {
                    // Fallback to high-quality thumbnail if maxresdefault is unavailable
                    e.currentTarget.src = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
                  }}
                />
                <div className="home-video-poster__overlay" />
                
                {/* Play Button Overlay */}
                <button
                  type="button"
                  className="home-video-play-btn"
                  aria-label="Play Video"
                >
                  <Play size={28} fill="currentColor" />
                </button>

                <div className="home-video-poster__caption">
                  <span className="home-video-poster__tag">Watch Video</span>
                  <span className="home-video-poster__time">Message from MLM Book</span>
                </div>
              </div>
            ) : (
              <iframe
                className="home-video-iframe"
                src={embedUrl}
                title="MLM Book Official Introduction Video"
                frameBorder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowFullScreen
              />
            )}
          </div>
        </div>

        {/* Video Information & Highlights */}
        <div className="home-video-info">
          <div className="home-video-info__card">
            <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 12px 0', color: 'var(--color-text, #1e293b)' }}>
              Why MLM Book?
            </h3>
            <p style={{ fontSize: '13.5px', color: 'var(--color-text-secondary, #64748b)', lineHeight: 1.6, margin: '0 0 18px 0' }}>
              MLM Book is built to bridge social networking with sustainable business collaboration. Learn how our platform empowers members with digital tools, authentic community engagement, and verified networking.
            </p>

            <ul className="home-video-highlights" style={{ listStyle: 'none', padding: 0, margin: '0 0 20px 0', display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <li style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13.5px', color: 'var(--color-text, #334155)' }}>
                <CheckCircle2 size={18} color="#20c875" style={{ flexShrink: 0, marginTop: '2px' }} />
                <span><strong>Authentic Networking:</strong> Connect directly with verified members and professionals.</span>
              </li>
              <li style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13.5px', color: 'var(--color-text, #334155)' }}>
                <CheckCircle2 size={18} color="#20c875" style={{ flexShrink: 0, marginTop: '2px' }} />
                <span><strong>Community Building:</strong> Launch private or public groups tailored to your interests.</span>
              </li>
              <li style={{ display: 'flex', alignItems: 'flex-start', gap: '10px', fontSize: '13.5px', color: 'var(--color-text, #334155)' }}>
                <CheckCircle2 size={18} color="#20c875" style={{ flexShrink: 0, marginTop: '2px' }} />
                <span><strong>Business Growth:</strong> Promote your brand, products, and services seamlessly.</span>
              </li>
            </ul>

            <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
              <Link to="/member/socials" className="member-button member-button--primary" style={{ fontSize: '13px', padding: '8px 16px' }}>
                <span>Join the Conversation</span>
              </Link>
              <a
                href={`https://www.youtube.com/watch?v=${videoId}`}
                target="_blank"
                rel="noopener noreferrer"
                className="member-button member-button--secondary"
                style={{ fontSize: '13px', padding: '8px 14px', gap: '6px' }}
              >
                <span>Watch on YouTube</span>
                <ExternalLink size={13} />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default HomeVideoMessage;
