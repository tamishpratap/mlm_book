import { useState, useEffect, useRef } from 'react';
import { RotateCw, ArrowUp, Newspaper } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import postApi from '../../api/postApi';
import PostComposer from '../../components/posts/PostComposer';
import PostCard from '../../components/posts/PostCard';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';
import StoriesTray from '../../components/stories/StoriesTray';

export function SocialsFeedPage() {
  const { user } = useAuth();
  const [posts, setPosts] = useState([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [newPostsCount, setNewPostsCount] = useState(0);

  const composerRef = useRef(null);

  useEffect(() => {
    let isMounted = true;

    postApi
      .getFeed(1)
      .then((data) => {
        if (isMounted && data && Array.isArray(data.posts)) {
          setPosts(data.posts);
          setHasMore(Boolean(data.has_more));
          setPage(1);
          setError(null);
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        if (err.response?.status === 401) {
          setError('Session expired. Please log in again.');
        } else {
          setError(err.response?.data?.message || 'Unable to load feed posts.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
          setIsRefreshing(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  // Polling for newer posts every 30 seconds
  const latestPostId = posts[0]?.id || 0;
  useEffect(() => {
    if (latestPostId <= 0) return;

    const timer = setInterval(() => {
      postApi
        .checkNewPosts(latestPostId)
        .then((res) => {
          if (res && res.has_new && res.count > 0) {
            setNewPostsCount(res.count);
          }
        })
        .catch(() => {});
    }, 30000);

    return () => clearInterval(timer);
  }, [latestPostId]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    setNewPostsCount(0);
    setError(null);
    postApi
      .getFeed(1)
      .then((data) => {
        if (data && Array.isArray(data.posts)) {
          setPosts(data.posts);
          setHasMore(Boolean(data.has_more));
          setPage(1);
        }
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Unable to refresh feed.');
      })
      .finally(() => {
        setIsRefreshing(false);
      });
  };

  const handleLoadMore = () => {
    if (isLoadingMore || !hasMore) return;
    setIsLoadingMore(true);
    const nextPage = page + 1;
    postApi
      .getFeed(nextPage)
      .then((data) => {
        if (data && Array.isArray(data.posts)) {
          setPosts((prev) => {
            const existingIds = new Set(prev.map((p) => p.id));
            const freshPosts = data.posts.filter((p) => !existingIds.has(p.id));
            return [...prev, ...freshPosts];
          });
          setHasMore(Boolean(data.has_more));
          setPage(nextPage);
        }
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Unable to load more posts.');
      })
      .finally(() => {
        setIsLoadingMore(false);
      });
  };

  const handlePostCreated = (newPost) => {
    setPosts((prev) => [newPost, ...prev]);
  };

  const handlePostDeleted = (postId) => {
    setPosts((prev) => prev.filter((p) => p.id !== postId));
  };

  const handlePostHidden = (postId) => {
    setPosts((prev) => prev.filter((p) => p.id !== postId));
  };

  const handlePostUpdated = (updatedPost) => {
    setPosts((prev) => prev.map((p) => (p.id === updatedPost.id ? updatedPost : p)));
  };

  return (
    <>
      <main className="member-main feed" id="feed">
        {/* Stories Tray */}
        <StoriesTray currentUser={user} />

        {/* Post Composer */}
        <div ref={composerRef}>
          <PostComposer currentUser={user} onPostCreated={handlePostCreated} allowVideo={false} />
        </div>

        {/* Feed Toolbar */}
        <div className="feed-toolbar">
          <div className="feed-toolbar__title">
            <span>Smart Feed</span>
          </div>
          <button
            className="feed-refresh-btn"
            type="button"
            aria-label="Refresh Feed"
            onClick={handleRefresh}
            disabled={isRefreshing}
          >
            <RotateCw size={14} className={isRefreshing ? 'fa-spin' : ''} aria-hidden="true" />
            <span>{isRefreshing ? 'Refreshing...' : 'Refresh Feed'}</span>
          </button>
        </div>

        {/* Floating Pill when new posts detected */}
        {newPostsCount > 0 && (
          <button
            className="new-posts-pill"
            type="button"
            onClick={handleRefresh}
            style={{ display: 'inline-flex' }}
          >
            <ArrowUp size={14} aria-hidden="true" />
            <span>{newPostsCount} New {newPostsCount === 1 ? 'Post' : 'Posts'} Available</span>
          </button>
        )}

        {/* Loading State */}
        {isLoading ? (
          <div className="post-feed" aria-busy="true">
            {[1, 2, 3].map((i) => (
              <div
                key={i}
                className="card feed-post"
                style={{ minHeight: '180px', background: 'var(--color-surface)' }}
              >
                <div style={{ display: 'flex', gap: '12px', alignItems: 'center', marginBottom: '16px' }}>
                  <div style={{ width: '42px', height: '42px', borderRadius: '50%', background: 'var(--color-border)' }} />
                  <div style={{ flex: 1 }}>
                    <div style={{ width: '140px', height: '16px', background: 'var(--color-border)', borderRadius: '4px', marginBottom: '6px' }} />
                    <div style={{ width: '80px', height: '12px', background: 'var(--color-border-soft)', borderRadius: '4px' }} />
                  </div>
                </div>
                <div style={{ width: '90%', height: '14px', background: 'var(--color-border-soft)', borderRadius: '4px', marginBottom: '8px' }} />
                <div style={{ width: '65%', height: '14px', background: 'var(--color-border-soft)', borderRadius: '4px' }} />
              </div>
            ))}
          </div>
        ) : error ? (
          <section className="card post-empty-state" role="alert">
            <h2>Unable to load feed</h2>
            <p>{error}</p>
            <button
              className="member-button member-button--primary"
              type="button"
              onClick={handleRefresh}
              style={{ marginTop: '12px' }}
            >
              Retry
            </button>
          </section>
        ) : posts.length > 0 ? (
          <div className="post-feed">
            {posts.map((post) => (
              <PostCard
                key={post.id}
                post={post}
                currentUser={user}
                onPostDeleted={handlePostDeleted}
                onPostHidden={handlePostHidden}
                onPostUpdated={handlePostUpdated}
              />
            ))}

            {hasMore && (
              <div style={{ textAlign: 'center', margin: '20px 0' }}>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={handleLoadMore}
                  disabled={isLoadingMore}
                  style={{ minWidth: '160px' }}
                >
                  {isLoadingMore ? 'Loading more posts...' : 'Load More Posts'}
                </button>
              </div>
            )}
          </div>
        ) : (
          <section className="card post-empty-state">
            <div className="post-empty-state__icon">
              <Newspaper size={36} aria-hidden="true" />
            </div>
            <h2>No Posts Available</h2>
            <p>Your feed is quiet right now. Share something or connect with friends!</p>
          </section>
        )}
      </main>

      {/* Right Sidebar Widgets */}
      <FeedRightSidebar currentUser={user} />
    </>
  );
}

export default SocialsFeedPage;
