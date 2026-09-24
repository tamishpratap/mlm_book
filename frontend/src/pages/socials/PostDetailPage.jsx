import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import postApi from '../../api/postApi';
import PostCard from '../../components/posts/PostCard';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function PostDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const [post, setPost] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;

    postApi
      .getPost(id)
      .then((data) => {
        if (isMounted) {
          if (data && data.post) {
            setPost(data.post);
          } else {
            setPost(data);
          }
          setError(null);
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        if (err.response?.status === 404) {
          setError('Post not found or has been deleted.');
        } else if (err.response?.status === 403) {
          setError('You do not have permission to view this post.');
        } else {
          setError(err.response?.data?.message || 'Unable to load post details.');
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
  }, [id]);

  return (
    <>
      <main className="member-main feed" id="feed">
        <div style={{ marginBottom: '16px' }}>
          <Link
            to="/member/socials"
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              color: 'var(--color-text-secondary)',
              fontSize: '0.875rem',
              fontWeight: 500,
              textDecoration: 'none',
            }}
          >
            <ArrowLeft size={16} />
            <span>Back to Socials Feed</span>
          </Link>
        </div>

        {isLoading ? (
          <div
            className="card feed-post"
            style={{ minHeight: '220px', background: 'var(--color-surface)' }}
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
        ) : error ? (
          <section className="card post-empty-state" role="alert">
            <h2>Post Unavailable</h2>
            <p>{error}</p>
            <Link
              className="member-button member-button--primary"
              to="/member/socials"
              style={{ marginTop: '12px', textDecoration: 'none' }}
            >
              Back to Feed
            </Link>
          </section>
        ) : post ? (
          <div className="post-feed">
            <PostCard
              post={post}
              currentUser={user}
              onPostDeleted={() => setPost(null)}
              onPostUpdated={(updated) => setPost(updated)}
            />
          </div>
        ) : null}
      </main>

      <FeedRightSidebar currentUser={user} />
    </>
  );
}

export default PostDetailPage;
