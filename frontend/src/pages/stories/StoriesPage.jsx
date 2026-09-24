import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Sparkles } from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import StoriesTray from '../../components/stories/StoriesTray';
import StoryViewer from '../../components/stories/StoryViewer';
import FeedRightSidebar from '../../components/posts/FeedRightSidebar';

export function StoriesPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [selectedStoryId, setSelectedStoryId] = useState(null);
  const activeStoryId = id || selectedStoryId;

  const handleCloseViewer = () => {
    setSelectedStoryId(null);
    if (id) {
      navigate('/member/socials');
    }
  };

  return (
    <>
      <main className="member-main feed" id="stories-page">
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
            <span>Back to Feed</span>
          </Link>
        </div>

        <header
          className="card section-header-card"
          style={{ padding: '20px 24px', marginBottom: '16px', borderRadius: '16px' }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <span
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: '40px',
                height: '40px',
                borderRadius: '12px',
                background: 'linear-gradient(135deg, rgba(36, 116, 244, 0.1), rgba(112, 65, 238, 0.1))',
                color: 'var(--color-primary, #3b82f6)',
              }}
            >
              <Sparkles size={20} />
            </span>
            <div>
              <h1 style={{ fontSize: '1.35rem', fontWeight: 700, margin: 0, color: 'var(--color-text)' }}>
                Member Stories
              </h1>
              <p style={{ margin: '2px 0 0 0', fontSize: '0.85rem', color: 'var(--color-text-secondary)' }}>
                Explore 24-hour photos and videos shared by your network.
              </p>
            </div>
          </div>
        </header>

        {/* Stories Tray */}
        <StoriesTray currentUser={user} />

        {/* Story Viewer if direct ID opened */}
        {activeStoryId && (
          <StoryViewer
            storyId={activeStoryId}
            currentMemberId={user?.id}
            onClose={handleCloseViewer}
            onNavigateToStory={(nextId) => {
              setSelectedStoryId(nextId);
              navigate(`/member/stories/${nextId}`, { replace: true });
            }}
          />
        )}
      </main>

      <FeedRightSidebar />
    </>
  );
}

export default StoriesPage;
