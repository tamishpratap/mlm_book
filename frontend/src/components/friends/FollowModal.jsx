import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { X, Users } from 'lucide-react';
import friendApi from '../../api/friendApi';
import MemberAvatar from '../common/MemberAvatar';
import { ModalPortal } from '../common/ModalPortal';

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

export function FollowModal({
  isOpen,
  onClose,
  type = 'followers', // 'followers' | 'following'
  memberId,
  memberName = 'Member',
}) {
  const [members, setMembers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const page = 1;

  useEffect(() => {
    let isMounted = true;
    if (isOpen && memberId) {
      const fetchFn = type === 'followers' ? friendApi.getFollowers : friendApi.getFollowing;
      fetchFn(memberId, page)
        .then((data) => {
          if (isMounted) {
            const list = data.followers?.data || data.following?.data || data.members?.data || [];
            setMembers(list);
          }
        })
        .catch(() => {
          if (isMounted) setMembers([]);
        })
        .finally(() => {
          if (isMounted) setIsLoading(false);
        });
    }

    return () => {
      isMounted = false;
    };
  }, [isOpen, memberId, type, page]);

  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const title = type === 'followers' ? `${memberName}'s Followers` : `${memberName}'s Following`;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="follow-modal-title"
        style={{
          maxWidth: '480px',
          width: '100%',
          maxHeight: 'min(80vh, 640px)',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '16px',
          overflow: 'hidden',
          backgroundColor: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
        }}
      >
        <header className="story-modal__header" style={{ padding: '16px 20px', borderBottom: '1px solid var(--color-border-soft)' }}>
          <div>
            <h2 id="follow-modal-title" style={{ fontSize: '1.15rem', fontWeight: 700, margin: 0 }}>
              {title}
            </h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        <div className="follow-modal__body" style={{ padding: '16px 20px', overflowY: 'auto', flex: 1 }}>
          {isLoading ? (
            <div style={{ padding: '24px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
              Loading...
            </div>
          ) : members.length > 0 ? (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {members.map((m) => {
                return (
                  <div
                    key={m.id}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      gap: '12px',
                      padding: '8px',
                      borderRadius: '10px',
                      background: 'var(--color-surface, #fff)',
                    }}
                  >
                    <Link
                      to={`/member/people/${m.id}`}
                      onClick={onClose}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        textDecoration: 'none',
                        color: 'inherit',
                      }}
                    >
                      <MemberAvatar
                        member={m}
                        size={42}
                      />
                      <div>
                        <strong style={{ display: 'block', fontSize: '0.92rem', color: 'var(--color-text)' }}>
                          {m.name}
                        </strong>
                        <span style={{ fontSize: '0.8rem', color: 'var(--color-text-secondary)' }}>
                          {m.user_id ? `@${m.user_id}` : '@member'}
                        </span>
                      </div>
                    </Link>

                    <Link
                      to={`/member/people/${m.id}`}
                      onClick={onClose}
                      className="member-button member-button--secondary"
                      style={{ padding: '6px 12px', fontSize: '0.8rem' }}
                    >
                      View
                    </Link>
                  </div>
                );
              })}
            </div>
          ) : (
            <div style={{ textAlign: 'center', padding: '32px 16px', color: 'var(--color-text-secondary)' }}>
              <Users size={32} style={{ opacity: 0.5, marginBottom: '8px' }} />
              <p style={{ margin: 0 }}>No {type} found.</p>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default FollowModal;
