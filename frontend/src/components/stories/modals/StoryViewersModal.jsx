import { useState, useEffect } from 'react';
import { X, EyeOff } from 'lucide-react';
import storyApi from '../../../api/storyApi';
import { getAvatarUrl, getInitials } from '../../../utils/assetHelper';
import { ModalPortal } from '../../common/ModalPortal';
import MemberAvatar from '../../common/MemberAvatar';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);
  if (diffSec < 60) return 'just now';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
  return date.toLocaleDateString();
}

export function StoryViewersModal({ isOpen, onClose, storyId }) {
  const [views, setViews] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    if (isOpen && storyId) {
      storyApi
        .getViewers(storyId)
        .then((data) => {
          if (isMounted) {
            if (data && Array.isArray(data.viewers)) {
              setViews(data.viewers);
            } else {
              setViews([]);
            }
          }
        })
        .catch(() => {
          if (isMounted) {
            setViews([]);
          }
        })
        .finally(() => {
          if (isMounted) {
            setIsLoading(false);
          }
        });
    }

    return () => {
      isMounted = false;
    };
  }, [isOpen, storyId]);

  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose} depth={1}>
      <div
        className="story-modal__panel story-viewers-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="story-viewers-title"
        style={{
          maxWidth: '460px',
          width: '100%',
          backgroundColor: '#ffffff',
          borderRadius: '16px',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
        }}
      >
        <header className="story-modal__header">
          <div>
            <span>Story Analytics</span>
            <h2 id="story-viewers-title">Seen by {views.length}</h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Story Views"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        <div className="story-viewers-modal__body">
          {isLoading ? (
            <div style={{ padding: '24px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
              Loading viewers...
            </div>
          ) : views.length > 0 ? (
            views.map((view, idx) => {
              if (!view) return null;
              const viewer = view.viewer;
              if (!viewer || typeof viewer !== 'object') return null;
              const rawUserId = viewer.user_id !== undefined && viewer.user_id !== null
                ? String(viewer.user_id).trim()
                : null;
              const displayUserId = rawUserId
                ? (rawUserId.startsWith('@') ? rawUserId : `@${rawUserId}`)
                : (viewer.id ? `ID: #${viewer.id}` : '');
              return (
                <div className="story-viewer-item" key={view.id || `viewer-${viewer.id || idx}`}>
                  <div className="story-viewer-item__avatar-wrap">
                    <MemberAvatar member={viewer} size={36} className="story-viewer-item__avatar" />
                  </div>
                  <div className="story-viewer-item__info">
                    <strong className="story-viewer-item__name">{viewer.name || 'Member'}</strong>
                    {displayUserId && (
                      <span className="story-viewer-item__id">{displayUserId}</span>
                    )}
                  </div>
                  <time className="story-viewer-item__time">
                    Viewed {formatRelativeTime(view.created_at)}
                  </time>
                </div>
              );
            })
          ) : (
            <div className="story-viewers-empty">
              <div className="story-viewers-empty__icon">
                <EyeOff size={32} aria-hidden="true" />
              </div>
              <h3>No one has viewed your Story yet.</h3>
              <p>When connections view your Story, you will see them listed here.</p>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default StoryViewersModal;
