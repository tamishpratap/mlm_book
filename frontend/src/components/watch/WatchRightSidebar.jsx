import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Compass,
  TvMinimalPlay,
  Flame,
  Video,
  Bookmark,
  Sparkles,
  UserPlus,
  PlayCircle,
  Clock,
  Check,
} from 'lucide-react';
import watchApi from '../../api/watchApi';
import MemberAvatar from '../common/MemberAvatar';
import { getMediaUrl } from '../../utils/assetHelper';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);
  if (diffSec < 60) return 'just now';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
  if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d ago`;
  return date.toLocaleDateString();
}

function VideoThumbnail({ video, className = '' }) {
  const [hasError, setHasError] = useState(false);
  const mediaUrl = video?.media_url || (video?.media_path ? getMediaUrl(video.media_path, 'posts/videos') : null);
  const posterUrl = video?.thumbnail_url || video?.poster_url || null;

  if (hasError || (!posterUrl && !mediaUrl)) {
    return (
      <div className={`watch-sidebar-video-thumb watch-sidebar-video-thumb--placeholder ${className}`}>
        <PlayCircle size={20} className="watch-sidebar-video-thumb__icon" />
      </div>
    );
  }

  if (posterUrl) {
    return (
      <div className={`watch-sidebar-video-thumb ${className}`}>
        <img
          src={posterUrl}
          alt={video.body || 'Video thumbnail'}
          onError={() => setHasError(true)}
          className="watch-sidebar-video-thumb__media"
        />
        <PlayCircle size={18} className="watch-sidebar-video-thumb__play-overlay" />
      </div>
    );
  }

  return (
    <div className={`watch-sidebar-video-thumb ${className}`}>
      <video
        src={`${mediaUrl}#t=0.5`}
        preload="metadata"
        muted
        playsInline
        onError={() => setHasError(true)}
        className="watch-sidebar-video-thumb__media"
      />
      <PlayCircle size={18} className="watch-sidebar-video-thumb__play-overlay" />
    </div>
  );
}

export function WatchRightSidebar({
  filter = 'all',
  onFilterChange,
  suggestedCreators = [],
  trendingVideos = [],
  recentVideos = [],
  myVideosCount = 0,
  savedVideosCount = 0,
}) {
  const [connectingIds, setConnectingIds] = useState(new Set());
  const [connectedIds, setConnectedIds] = useState(new Set());

  const handleConnect = async (creatorId) => {
    if (connectingIds.has(creatorId) || connectedIds.has(creatorId)) return;
    setConnectingIds((prev) => new Set(prev).add(creatorId));
    try {
      await watchApi.connectCreator(creatorId);
      setConnectedIds((prev) => new Set(prev).add(creatorId));
    } catch {
      // Revert if error
    } finally {
      setConnectingIds((prev) => {
        const next = new Set(prev);
        next.delete(creatorId);
        return next;
      });
    }
  };

  return (
    <aside className="right-sidebar member-sidebar--right" aria-label="Watch Sidebar">
      {/* Watch Navigation Shortcuts */}
      <div className="watch-sidebar-card">
        <div className="watch-sidebar-card__header">
          <h3>
            <Compass size={18} color="var(--color-primary)" />
            <span>Watch Navigation</span>
          </h3>
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
          <button
            type="button"
            className={`side-nav__item ${filter === 'all' ? 'is-active' : ''}`}
            onClick={() => onFilterChange && onFilterChange('all')}
            style={{ width: '100%', textAlign: 'left', border: 'none', background: 'transparent', cursor: 'pointer' }}
          >
            <TvMinimalPlay size={16} />
            <span>Home Feed</span>
          </button>
          <button
            type="button"
            className={`side-nav__item ${filter === 'trending' ? 'is-active' : ''}`}
            onClick={() => onFilterChange && onFilterChange('trending')}
            style={{ width: '100%', textAlign: 'left', border: 'none', background: 'transparent', cursor: 'pointer' }}
          >
            <Flame size={16} />
            <span>Trending Videos</span>
          </button>
          <button
            type="button"
            className={`side-nav__item ${filter === 'my_videos' ? 'is-active' : ''}`}
            onClick={() => onFilterChange && onFilterChange('my_videos')}
            style={{ width: '100%', textAlign: 'left', border: 'none', background: 'transparent', cursor: 'pointer' }}
          >
            <Video size={16} />
            <span>My Videos ({myVideosCount})</span>
          </button>
          <button
            type="button"
            className={`side-nav__item ${filter === 'saved' ? 'is-active' : ''}`}
            onClick={() => onFilterChange && onFilterChange('saved')}
            style={{ width: '100%', textAlign: 'left', border: 'none', background: 'transparent', cursor: 'pointer' }}
          >
            <Bookmark size={16} />
            <span>Saved Videos ({savedVideosCount})</span>
          </button>
        </div>
      </div>

      {/* Suggested Creators */}
      {suggestedCreators.length > 0 && (
        <div className="watch-sidebar-card">
          <div className="watch-sidebar-card__header">
            <h3>
              <Sparkles size={18} color="var(--color-primary)" />
              <span>Suggested Creators</span>
            </h3>
          </div>
          {suggestedCreators.map((creator) => {
            const isConnecting = connectingIds.has(creator.id);
            const isConnected = connectedIds.has(creator.id);

            return (
              <div className="watch-sidebar-creator" key={creator.id}>
                <Link className="watch-sidebar-creator__info" to={`/member/people/${creator.id}`}>
                  <MemberAvatar
                    member={creator}
                    size={38}
                    className="watch-sidebar-creator__avatar"
                  />
                  <div className="watch-sidebar-creator__details">
                    <span className="watch-sidebar-creator__name" title={creator.name}>
                      {creator.name}
                    </span>
                    {creator.user_id && (
                      <span className="watch-sidebar-creator__handle" title={`@${creator.user_id}`}>
                        @{creator.user_id}
                      </span>
                    )}
                  </div>
                </Link>

                <div className="watch-sidebar-creator__action">
                  <button
                    className={`member-button ${isConnected ? 'member-button--primary' : 'member-button--secondary'} member-button--sm`}
                    type="button"
                    title="Connect"
                    onClick={() => handleConnect(creator.id)}
                    disabled={isConnecting || isConnected}
                  >
                    {isConnected ? (
                      <>
                        <Check size={14} /> Sent
                      </>
                    ) : (
                      <>
                        <UserPlus size={14} /> {isConnecting ? 'Connecting...' : 'Connect'}
                      </>
                    )}
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Trending Videos Widget */}
      {trendingVideos.length > 0 && (
        <div className="watch-sidebar-card">
          <div className="watch-sidebar-card__header">
            <h3>
              <Flame size={18} color="#f59e0b" />
              <span>Trending Videos</span>
            </h3>
          </div>
          {trendingVideos.map((tVideo) => {
            const reactions = (tVideo.reactions_count || 0) + (tVideo.likes_count || 0);
            const creatorName = tVideo.member?.name || 'Member';
            const videoTitle = tVideo.body?.trim() || `Video by ${creatorName}`;

            return (
              <Link className="watch-sidebar-video-item" to={`/member/posts/${tVideo.id}`} key={tVideo.id}>
                <VideoThumbnail video={tVideo} />
                <div className="watch-sidebar-video-copy">
                  <strong className="watch-sidebar-video-copy__title" title={videoTitle}>
                    {videoTitle}
                  </strong>
                  <span className="watch-sidebar-video-copy__meta" title={`${creatorName} · ${reactions} reactions`}>
                    {creatorName} · {reactions} reactions
                  </span>
                </div>
              </Link>
            );
          })}
        </div>
      )}

      {/* Recent Videos Widget */}
      {recentVideos.length > 0 && (
        <div className="watch-sidebar-card">
          <div className="watch-sidebar-card__header">
            <h3>
              <Clock size={18} color="var(--color-primary)" />
              <span>Recent Videos</span>
            </h3>
          </div>
          {recentVideos.map((rVideo) => {
            const creatorName = rVideo.member?.name || 'Member';
            const videoTitle = rVideo.body?.trim() || `Video by ${creatorName}`;
            const timeAgo = formatRelativeTime(rVideo.created_at);

            return (
              <Link className="watch-sidebar-video-item" to={`/member/posts/${rVideo.id}`} key={rVideo.id}>
                <VideoThumbnail video={rVideo} />
                <div className="watch-sidebar-video-copy">
                  <strong className="watch-sidebar-video-copy__title" title={videoTitle}>
                    {videoTitle}
                  </strong>
                  <span className="watch-sidebar-video-copy__meta" title={`${creatorName} · ${timeAgo}`}>
                    {creatorName} · {timeAgo}
                  </span>
                </div>
              </Link>
            );
          })}
        </div>
      )}
    </aside>
  );
}

export default WatchRightSidebar;
