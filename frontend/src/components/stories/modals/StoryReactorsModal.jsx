import { useState, useEffect } from 'react';
import { X, HeartOff } from 'lucide-react';
import storyApi from '../../../api/storyApi';
import { getAvatarUrl, getInitials } from '../../../utils/assetHelper';
import { ModalPortal } from '../../common/ModalPortal';
import MemberAvatar from '../../common/MemberAvatar';

const REACTION_EMOJIS = {
  like: '👍',
  love: '❤️',
  haha: '😂',
  wow: '😮',
  sad: '😢',
  angry: '😡',
};

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

export function StoryReactorsModal({ isOpen, onClose, storyId }) {
  const [reactions, setReactions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    if (isOpen && storyId) {
      setIsLoading(true);
      storyApi
        .getReactors(storyId)
        .then((data) => {
          if (isMounted) {
            const list = Array.isArray(data)
              ? data
              : data?.reactors && Array.isArray(data.reactors)
              ? data.reactors
              : [];
            setReactions(list);
          }
        })
        .catch(() => {
          if (isMounted) {
            setReactions([]);
          }
        })
        .finally(() => {
          if (isMounted) {
            setIsLoading(false);
          }
        });
    } else if (!isOpen) {
      setReactions([]);
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

  const safeReactions = Array.isArray(reactions) ? reactions : [];

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose} depth={1}>
      <div
        className="story-modal__panel story-reactors-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="story-reactors-title"
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
            <h2 id="story-reactors-title">Reactions ({safeReactions.length})</h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Story Reactions"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        <div className="story-reactors-modal__body">
          {isLoading ? (
            <div style={{ padding: '24px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
              Loading reactions...
            </div>
          ) : safeReactions.length > 0 ? (
            safeReactions.map((reactionItem, idx) => {
              if (!reactionItem) return null;
              const reactor = reactionItem.member || reactionItem.user || reactionItem;
              if (!reactor || typeof reactor !== 'object') return null;

              const rawReaction = typeof reactionItem.reaction === 'string'
                ? reactionItem.reaction.toLowerCase().trim()
                : 'like';
              const emoji = REACTION_EMOJIS[rawReaction] || '👍';

              const rawUserId = reactor.user_id !== undefined && reactor.user_id !== null
                ? String(reactor.user_id).trim()
                : null;
              const displayUserId = rawUserId
                ? (rawUserId.startsWith('@') ? rawUserId : `@${rawUserId}`)
                : (reactor.id ? `ID: #${reactor.id}` : '');

              const reactorName = reactor.name || reactor.display_name || 'Member';

              return (
                <div className="story-reactor-item" key={reactionItem.id || `reactor-${reactor.id || idx}`}>
                  <div className="story-reactor-item__avatar-wrap">
                    <MemberAvatar member={reactor} size={36} className="story-reactor-item__avatar" />
                    <span className="story-reactor-item__emoji-badge">{emoji}</span>
                  </div>
                  <div className="story-reactor-item__info">
                    <strong className="story-reactor-item__name">{reactorName}</strong>
                    {displayUserId && (
                      <span className="story-reactor-item__id">{displayUserId}</span>
                    )}
                  </div>
                  <time className="story-reactor-item__time">
                    {formatRelativeTime(reactionItem.updated_at || reactionItem.created_at)}
                  </time>
                </div>
              );
            })
          ) : (
            <div className="story-reactors-empty">
              <div className="story-reactors-empty__icon">
                <HeartOff size={32} aria-hidden="true" />
              </div>
              <h3>No reactions yet</h3>
              <p>When connections react to your Story, you will see them listed here.</p>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default StoryReactorsModal;
