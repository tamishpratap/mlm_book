import { useState, useEffect } from 'react';
import { Bookmark, Sparkles } from 'lucide-react';
import { Link } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';
import postApi from '../../api/postApi';
import PostCard from '../../components/posts/PostCard';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function SavedPostsPage() {
  const { user } = useAuth();
  const [posts, setPosts] = useState([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;

    postApi
      .getSavedPosts(1)
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
          setError(err.response?.data?.message || 'Unable to load saved posts.');
        }
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  const handleLoadMore = () => {
    if (isLoadingMore || !hasMore) return;
    setIsLoadingMore(true);
    const nextPage = page + 1;
    postApi
      .getSavedPosts(nextPage)
      .then((data) => {
        if (data && Array.isArray(data.posts)) {
          setPosts((prev) => [...prev, ...data.posts]);
          setHasMore(Boolean(data.has_more));
          setPage(nextPage);
        }
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Unable to load more saved posts.');
      })
      .finally(() => {
        setIsLoadingMore(false);
      });
  };

  const handleRetry = () => {
    setIsLoading(true);
    setError(null);
    postApi
      .getSavedPosts(1)
      .then((data) => {
        if (data && Array.isArray(data.posts)) {
          setPosts(data.posts);
          setHasMore(Boolean(data.has_more));
          setPage(1);
        }
      })
      .catch((err) => {
        setError(err.response?.data?.message || 'Unable to load saved posts.');
      })
      .finally(() => {
        setIsLoading(false);
      });
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
      <main className="member-main saved-posts-page" id="saved-posts-content">
        <header className="card section-header-card" style={{ padding: '20px 24px', marginBottom: '16px', borderRadius: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <span style={{
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: '40px',
              height: '40px',
              borderRadius: '12px',
              background: 'var(--color-primary-soft, #eff6ff)',
              color: 'var(--color-primary, #3b82f6)',
            }}>
              <Bookmark size={20} />
            </span>
            <div>
              <h1 style={{ fontSize: '1.35rem', fontWeight: 700, margin: 0, color: 'var(--color-text)' }}>
                Saved Posts
              </h1>
              <p style={{ margin: '2px 0 0 0', fontSize: '0.85rem', color: 'var(--color-text-secondary)' }}>
                All posts and updates you have bookmarked for later review.
              </p>
            </div>
          </div>
        </header>

        {isLoading ? (
          <div className="post-feed" aria-busy="true">
            {[1, 2].map((i) => (
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
            <h2>Unable to load saved posts</h2>
            <p>{error}</p>
            <button
              className="member-button member-button--primary"
              type="button"
              onClick={handleRetry}
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
                >
                  {isLoadingMore ? 'Loading more saved posts...' : 'Load More'}
                </button>
              </div>
            )}
          </div>
        ) : (
          <section className="card post-empty-state">
            <div className="post-empty-state__icon">
              <Bookmark size={36} aria-hidden="true" />
            </div>
            <h2>No Saved Posts Yet</h2>
            <p>You haven't bookmarked any posts yet. Save posts from your feed to see them here.</p>
            <Link
              className="member-button member-button--primary"
              to="/member/socials"
              style={{ marginTop: '10px', textDecoration: 'none' }}
            >
              <Sparkles size={16} aria-hidden="true" />
              <span>Explore Socials Feed</span>
            </Link>
          </section>
        )}
      </main>

      <FeedRightSidebar currentUser={user} />
    </>
  );
}

export default SavedPostsPage;
