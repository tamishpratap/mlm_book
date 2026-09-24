import { useState, useEffect, useCallback } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
  TvMinimalPlay,
  Sparkles,
  Flame,
  Video,
  Bookmark,
  VideoOff,
  PlusCircle,
  RotateCw,
  AlertCircle,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import watchApi from '../../api/watchApi';
import WatchVideoCard from '../../components/watch/WatchVideoCard';
import WatchRightSidebar from '../../components/watch/WatchRightSidebar';

/**
 * Safely extract a human-readable string message from an error of any type.
 */
function extractErrorMessage(err, fallback = 'Unable to load videos right now. Please try again.') {
  if (!err) return fallback;
  if (typeof err === 'string') {
    const trimmed = err.trim();
    return trimmed || fallback;
  }

  if (err.response?.status === 401) {
    return 'Session expired. Please log in again.';
  }

  const data = err.response?.data;
  if (typeof data === 'string' && data.trim()) {
    if (data.includes('<html') || data.includes('<!DOCTYPE')) {
      return fallback;
    }
    return data.trim();
  }

  if (data && typeof data === 'object') {
    if (typeof data.message === 'string' && data.message.trim()) {
      return data.message.trim();
    }
    if (typeof data.error === 'string' && data.error.trim()) {
      return data.error.trim();
    }
    if (data.errors && typeof data.errors === 'object') {
      const keys = Object.keys(data.errors);
      if (keys.length > 0) {
        const val = data.errors[keys[0]];
        if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'string') {
          return val[0].trim();
        }
        if (typeof val === 'string') {
          return val.trim();
        }
      }
    }
  }

  if (typeof err.message === 'string' && err.message.trim()) {
    return err.message.trim();
  }

  return fallback;
}

/**
 * Detect whether an error contains internal server paths or PHP stack traces.
 */
function isTechnicalError(msg) {
  if (typeof msg !== 'string') return false;
  return (
    msg.includes('include(') ||
    msg.includes('Failed to open stream') ||
    msg.includes('No such file or directory') ||
    msg.includes('vendor/composer') ||
    msg.includes('/app/Models/') ||
    msg.includes('SQLSTATE') ||
    msg.includes('Stack trace:') ||
    msg.includes('ErrorException') ||
    msg.includes('Fatal error') ||
    /(\/home\/|\/var\/www\/|\/public_html\/)/i.test(msg)
  );
}

export function WatchPage() {
  const { user } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const activeFilter = searchParams.get('filter') || 'all';

  const [posts, setPosts] = useState([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);

  const safeErrorText = typeof error === 'string'
    ? error
    : error && typeof error === 'object'
      ? (error.message || error.error || JSON.stringify(error))
      : 'Unable to load videos right now. Please try again.';

  // Sidebar metrics & widgets state
  const [suggestedCreators, setSuggestedCreators] = useState([]);
  const [trendingVideos, setTrendingVideos] = useState([]);
  const [recentVideos, setRecentVideos] = useState([]);
  const [myVideosCount, setMyVideosCount] = useState(0);
  const [savedVideosCount, setSavedVideosCount] = useState(0);

  const fetchFeed = useCallback((filterToLoad, pageToLoad = 1, isLoadMore = false) => {
    if (isLoadMore) {
      setIsLoadingMore(true);
    } else {
      setIsLoading(true);
    }
    setError(null);

    watchApi
      .getWatchFeed(filterToLoad, pageToLoad)
      .then((data) => {
        if (data) {
          if (Array.isArray(data.posts)) {
            if (isLoadMore) {
              setPosts((prev) => [...prev, ...data.posts]);
            } else {
              setPosts(data.posts);
            }
          }
          setHasMore(Boolean(data.has_more));
          setPage(pageToLoad);

          if (data.suggested_creators) setSuggestedCreators(data.suggested_creators);
          if (data.trending_videos) setTrendingVideos(data.trending_videos);
          if (data.recent_videos) setRecentVideos(data.recent_videos);
          if (data.my_videos_count !== undefined) setMyVideosCount(data.my_videos_count);
          if (data.saved_videos_count !== undefined) setSavedVideosCount(data.saved_videos_count);
        }
      })
      .catch((err) => {
        console.error('[Watch] Feed load error:', err);
        setError(extractErrorMessage(err));
      })
      .finally(() => {
        setIsLoading(false);
        setIsLoadingMore(false);
        setIsRefreshing(false);
      });
  }, []);

  useEffect(() => {
    fetchFeed(activeFilter, 1, false);
  }, [activeFilter, fetchFeed]);

  const handleFilterTab = (newFilter) => {
    if (newFilter === activeFilter) return;
    setSearchParams(newFilter === 'all' ? {} : { filter: newFilter });
  };

  const handleRefresh = () => {
    setIsRefreshing(true);
    fetchFeed(activeFilter, 1, false);
  };

  const handleLoadMore = () => {
    if (isLoadingMore || !hasMore) return;
    fetchFeed(activeFilter, page + 1, true);
  };

  const handlePostHidden = (postId) => {
    setPosts((prev) => prev.filter((p) => p.id !== postId));
  };

  const handlePostUpdated = (updatedPost) => {
    setPosts((prev) => prev.map((p) => (p.id === updatedPost.id ? updatedPost : p)));
  };

  return (
    <>
      <main className="member-main feed" id="watch-page-main">
        <div className="watch-page-container">
          {/* Header Card */}
          <section className="watch-header-card" aria-label="Watch Navigation">
            <div className="watch-header-card__top">
              <div className="watch-header-card__title">
                <span className="watch-header-card__icon">
                  <TvMinimalPlay size={24} />
                </span>
                <div>
                  <h1>Watch</h1>
                  <p>Discover videos from connections, creators, and your community.</p>
                </div>
              </div>

              <div className="watch-header-card__actions">
                <button
                  className="member-button member-button--secondary"
                  type="button"
                  aria-label="Refresh Videos"
                  onClick={handleRefresh}
                  disabled={isRefreshing || isLoading}
                  style={{ padding: '8px 12px' }}
                >
                  <RotateCw size={14} className={isRefreshing ? 'spin-icon' : ''} aria-hidden="true" />
                </button>
              </div>
            </div>

            {/* Filter Navigation Tabs */}
            <nav className="watch-tabs" aria-label="Watch Filters">
              <button
                type="button"
                className={`watch-tab-item ${activeFilter === 'all' ? 'is-active' : ''}`}
                onClick={() => handleFilterTab('all')}
              >
                <Sparkles size={16} />
                <span>All Videos</span>
              </button>

              <button
                type="button"
                className={`watch-tab-item ${activeFilter === 'trending' ? 'is-active' : ''}`}
                onClick={() => handleFilterTab('trending')}
              >
                <Flame size={16} />
                <span>Trending</span>
              </button>

              <button
                type="button"
                className={`watch-tab-item ${activeFilter === 'my_videos' ? 'is-active' : ''}`}
                onClick={() => handleFilterTab('my_videos')}
              >
                <Video size={16} />
                <span>My Videos</span>
                {myVideosCount > 0 && <span className="badge-count">{myVideosCount}</span>}
              </button>

              <button
                type="button"
                className={`watch-tab-item ${activeFilter === 'saved' ? 'is-active' : ''}`}
                onClick={() => handleFilterTab('saved')}
              >
                <Bookmark size={16} />
                <span>Saved Videos</span>
                {savedVideosCount > 0 && <span className="badge-count">{savedVideosCount}</span>}
              </button>
            </nav>
          </section>

          {/* Feed Content */}
          <div className="watch-feed" data-watch-feed>
            {isLoading ? (
              <div className="post-feed" aria-busy="true">
                {[1, 2, 3].map((i) => (
                  <div
                    key={i}
                    className="card feed-post watch-video-card"
                    style={{ minHeight: '260px', background: 'var(--color-surface)' }}
                  >
                    <div style={{ display: 'flex', gap: '12px', alignItems: 'center', padding: '16px' }}>
                      <div style={{ width: '42px', height: '42px', borderRadius: '50%', background: 'var(--color-border)' }} />
                      <div style={{ flex: 1 }}>
                        <div style={{ width: '140px', height: '16px', background: 'var(--color-border)', borderRadius: '4px', marginBottom: '6px' }} />
                        <div style={{ width: '80px', height: '12px', background: 'var(--color-border-soft)', borderRadius: '4px' }} />
                      </div>
                    </div>
                    <div style={{ width: '100%', height: '220px', background: 'var(--color-border-soft)' }} />
                  </div>
                ))}
              </div>
            ) : error ? (
              <section className="card post-empty-state watch-error-card" role="alert">
                <div className="post-empty-state__icon" aria-hidden="true">
                  <AlertCircle size={32} />
                </div>
                <h2>Unable to load videos</h2>
                {isTechnicalError(safeErrorText) ? (
                  <>
                    <p className="watch-error-message">
                      Unable to load videos right now. Please try again.
                    </p>
                    <div className="watch-error-trace-container">
                      <div className="watch-error-trace" title="Error diagnostic details">
                        {safeErrorText}
                      </div>
                    </div>
                  </>
                ) : (
                  <p className="watch-error-message">{safeErrorText}</p>
                )}
                <div className="watch-error-actions">
                  <button
                    className="member-button member-button--primary"
                    type="button"
                    onClick={handleRefresh}
                    disabled={isRefreshing}
                  >
                    <RotateCw size={14} className={isRefreshing ? 'spin-icon' : ''} style={{ marginRight: '6px' }} />
                    Retry
                  </button>
                </div>
              </section>
            ) : posts.length > 0 ? (
              <>
                {posts.map((post) => (
                  <WatchVideoCard
                    key={post.id}
                    post={post}
                    currentUser={user}
                    onPostHidden={handlePostHidden}
                    onPostUpdated={handlePostUpdated}
                  />
                ))}

                {hasMore && (
                  <div className="watch-load-more" style={{ textAlign: 'center', margin: '20px 0' }}>
                    <button
                      type="button"
                      className="member-button member-button--secondary"
                      onClick={handleLoadMore}
                      disabled={isLoadingMore}
                      style={{ minWidth: '160px' }}
                    >
                      {isLoadingMore ? 'Loading more videos...' : 'Load More Videos'}
                    </button>
                  </div>
                )}
              </>
            ) : (
              <div className="watch-empty-state">
                <span className="watch-empty-state__icon">
                  <VideoOff size={28} />
                </span>
                <h3>No video posts found</h3>
                <p>
                  {activeFilter === 'my_videos'
                    ? "No Business Page video campaigns found for your account."
                    : activeFilter === 'saved'
                    ? "You haven't saved any video posts yet."
                    : activeFilter === 'trending'
                    ? 'No trending video posts available at the moment.'
                    : 'No video posts available at the moment. Check back soon for new videos.'}
                </p>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* Right Sidebar Widgets */}
      <WatchRightSidebar
        filter={activeFilter}
        onFilterChange={handleFilterTab}
        suggestedCreators={suggestedCreators}
        trendingVideos={trendingVideos}
        recentVideos={recentVideos}
        myVideosCount={myVideosCount}
        savedVideosCount={savedVideosCount}
      />
    </>
  );
}

export default WatchPage;
