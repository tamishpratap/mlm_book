import { useEffect } from 'react';
import { X, Repeat } from 'lucide-react';
import { Link } from 'react-router-dom';
import { ModalPortal } from '../../common/ModalPortal';
import MemberAvatar from '../../common/MemberAvatar';

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);
  if (diffSec < 60) return 'just now';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
  if (diffSec < 3600 * 24) return `${Math.floor(diffSec / 3600)}h ago`;
  if (diffSec < 3600 * 24 * 7) return `${Math.floor(diffSec / (3600 * 24))}d ago`;
  return date.toLocaleDateString();
}

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

export function SharersModal({
  isOpen,
  onClose,
  sharers = [],
  isLoading = false,
  totalCount,
}) {
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const displayCount = typeof totalCount === 'number' ? totalCount : sharers.length;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel post-sharers-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="post-sharers-title"
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
            <span>Post Shares</span>
            <h2 id="post-sharers-title">People who shared this ({displayCount})</h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Post Shares"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        <div className="post-sharers-modal__body">
          {isLoading ? (
            <div
              className="post-likers-loading"
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '48px 20px',
                minHeight: '180px',
              }}
            >
              <div
                className="spinner"
                style={{
                  width: '32px',
                  height: '32px',
                  border: '3px solid rgba(37, 99, 235, 0.2)',
                  borderTopColor: '#2563eb',
                  borderRadius: '50%',
                  animation: 'spin 0.8s linear infinite',
                }}
              />
              <p style={{ marginTop: '14px', color: '#64748b', fontSize: '0.875rem' }}>
                Loading people who shared...
              </p>
            </div>
          ) : sharers.length > 0 ? (
            sharers.map((item, idx) => {
              const member = item.member || (item.name ? item : null);
              if (!member) return null;

              return (
                <div className="post-liker-item" key={item.id || idx}>
                  <div className="post-liker-item__avatar-wrap">
                    <MemberAvatar member={member} size={40} className="post-liker-item__avatar" />
                    <span className="post-liker-item__badge">
                      <Repeat size={12} />
                    </span>
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
                    {item.share_message && (
                      <p style={{ margin: '4px 0 0 0', fontSize: '0.825rem', color: 'var(--color-text-secondary)' }}>
                        "{item.share_message}"
                      </p>
                    )}
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
                <Repeat size={32} aria-hidden="true" />
              </div>
              <h3>No shares yet</h3>
              <p>Be the first friend to share this post!</p>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default SharersModal;
