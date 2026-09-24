import { useEffect } from 'react';
import { X, Smile } from 'lucide-react';
import { Link } from 'react-router-dom';
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
  if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d ago`;
  return date.toLocaleDateString();
}

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

export function CommentReactorsModal({ isOpen, onClose, reactors = [] }) {
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
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel post-reactors-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="comment-reactors-title"
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
            <span>Comment Reactions</span>
            <h2 id="comment-reactors-title">All Reactions ({reactors.length})</h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Comment Reactions"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        <div className="post-reactors-modal__body">
          {reactors.length > 0 ? (
            reactors.map((item, idx) => {
              const member = item.member;
              if (!member) return null;
              const emoji = REACTION_EMOJIS[item.reaction] || '👍';

              return (
                <div className="post-liker-item" key={item.id || idx}>
                  <div className="post-liker-item__avatar-wrap">
                    <MemberAvatar member={member} size={40} className="post-liker-item__avatar" />
                    <span className="post-liker-item__badge">{emoji}</span>
                  </div>

                  <div className="post-liker-item__info">
                    <Link
                      to={`/member/people/${member.id}`}
                      style={{ color: 'inherit', textDecoration: 'none' }}
                    >
                      <strong className="post-liker-item__name">{member.name}</strong>
                    </Link>
                    <span className="post-liker-item__id">
                      {member.user_id ? (member.user_id.startsWith('@') ? member.user_id : `@${member.user_id}`) : `ID: #${member.id}`}
                    </span>
                  </div>

                  <time className="post-liker-item__time">
                    {formatRelativeTime(item.created_at)}
                  </time>
                </div>
              );
            })
          ) : (
            <div className="post-likers-empty">
              <div className="post-likers-empty__icon">
                <Smile size={32} aria-hidden="true" />
              </div>
              <h3>No reactions yet</h3>
              <p>Be the first friend to react to this comment!</p>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default CommentReactorsModal;
