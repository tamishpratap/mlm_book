import { useState, useEffect, useRef } from 'react';
import { ChevronRight } from 'lucide-react';
import storyApi from '../../api/storyApi';
import StoryCreateCard from './StoryCreateCard';
import StoryCard from './StoryCard';
import CreateStoryModal from './CreateStoryModal';
import StoryViewer from './StoryViewer';
import StoryViewersModal from './modals/StoryViewersModal';

export function StoriesTray({ currentUser }) {
  const [stories, setStories] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  // Modals state
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [activeStoryId, setActiveStoryId] = useState(null);
  const [viewersModalStoryId, setViewersModalStoryId] = useState(null);

  const trayRef = useRef(null);

  const fetchStories = async () => {
    try {
      const data = await storyApi.getStories();
      if (data && Array.isArray(data.stories)) {
        setStories(data.stories);
      }
    } catch {
      // Fallback
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchStories();
  }, []);

  const handleScrollNext = () => {
    if (trayRef.current) {
      trayRef.current.scrollBy({ left: 240, behavior: 'smooth' });
    }
  };

  const handleStoryCreated = () => {
    fetchStories();
  };

  const handleStoryDeleted = (deletedId) => {
    setStories((prev) => prev.filter((s) => s.id !== deletedId));
    fetchStories();
  };

  if (isLoading) {
    return (
      <section className="card stories" aria-label="Stories" style={{ minHeight: '180px' }}>
        <div style={{ display: 'flex', gap: '8px', padding: '8px' }}>
          {[1, 2, 3, 4].map((i) => (
            <div
              key={i}
              style={{
                width: '110px',
                height: '160px',
                borderRadius: '12px',
                background: 'var(--color-border-soft, #f1f5f9)',
              }}
            />
          ))}
        </div>
      </section>
    );
  }

  return (
    <>
      <section className="card stories" aria-label="Stories" ref={trayRef}>
        {/* Create Story Button */}
        <StoryCreateCard
          currentUser={currentUser}
          onOpenCreate={() => setIsCreateOpen(true)}
        />

        {/* Active Published Stories */}
        {stories.map((story) => (
          <StoryCard
            key={story.id}
            story={story}
            currentMemberId={currentUser?.id}
            onOpenViewer={(id) => setActiveStoryId(id)}
            onOpenViewersList={(id) => setViewersModalStoryId(id)}
          />
        ))}

        {stories.length > 4 && (
          <button
            className="stories__next"
            type="button"
            aria-label="Next stories"
            onClick={handleScrollNext}
          >
            <ChevronRight size={18} aria-hidden="true" />
          </button>
        )}
      </section>

      {/* 1. Create Story Modal */}
      {isCreateOpen && (
        <CreateStoryModal
          isOpen={isCreateOpen}
          onClose={() => setIsCreateOpen(false)}
          onStoryCreated={handleStoryCreated}
        />
      )}

      {/* 2. Full Story Viewer */}
      {activeStoryId && (
        <StoryViewer
          storyId={activeStoryId}
          currentMemberId={currentUser?.id}
          onClose={() => setActiveStoryId(null)}
          onStoryDeleted={handleStoryDeleted}
          onNavigateToStory={(id) => setActiveStoryId(id)}
        />
      )}

      {/* 3. Direct Story Viewers Modal */}
      {viewersModalStoryId && (
        <StoryViewersModal
          isOpen={Boolean(viewersModalStoryId)}
          onClose={() => setViewersModalStoryId(null)}
          storyId={viewersModalStoryId}
        />
      )}
    </>
  );
}

export default StoriesTray;
