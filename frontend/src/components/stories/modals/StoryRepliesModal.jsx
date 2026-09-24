import { useState, useEffect } from 'react';
import { X, MessageSquareOff, CheckCheck, Check, Trash2 } from 'lucide-react';
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

export function StoryRepliesModal({ isOpen, onClose, storyId, currentMemberId }) {
  const [replies, setReplies] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    if (isOpen && storyId) {
      storyApi
        .getReplies(storyId)
        .then((data) => {
          if (isMounted) {
            if (data && Array.isArray(data.replies)) {
              setReplies(data.replies);
            } else {
              setReplies([]);
            }
          }
        })
        .catch(() => {
          if (isMounted) {
            setReplies([]);
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

  const handleDeleteReply = async (replyId) => {
    try {
      await storyApi.deleteReply(replyId);
      setReplies((prev) => prev.filter((r) => r.id !== replyId));
    } catch {
      // Revert if error
    }
  };

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose} depth={1}>
      <div
        className="story-modal__panel story-replies-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="story-replies-title"
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
            <span>Story Conversation</span>
            <h2 id="story-replies-title">Replies ({replies.length})</h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Story Replies"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        <div className="story-replies-modal__body">
          {isLoading ? (
            <div style={{ padding: '24px', textAlign: 'center', color: 'var(--color-text-secondary)' }}>
              Loading replies...
            </div>
          ) : replies.length > 0 ? (
            replies.map((reply) => {
              const sender = reply.sender;
              if (!sender) return null;
              const isSelf = sender.id === currentMemberId;
              return (
                <div
                  className={`story-reply-item ${isSelf ? 'story-reply-item--self' : ''}`}
                  key={reply.id}
                >
                  <div className="story-reply-item__avatar-wrap">
                    <MemberAvatar member={sender} size={36} className="story-reply-item__avatar" />
                  </div>

                  <div className="story-reply-item__content">
                    <div className="story-reply-item__header">
                      <strong className="story-reply-item__name">{sender.name || 'Member'}</strong>
                      {sender.user_id !== undefined && sender.user_id !== null && (
                        <span className="story-reply-item__id">
                          {String(sender.user_id).startsWith('@') ? String(sender.user_id) : `@${String(sender.user_id)}`}
                        </span>
                      )}
                      {!sender.user_id && sender.id && (
                        <span className="story-reply-item__id">ID: #{sender.id}</span>
                      )}
                      <time className="story-reply-item__time">
                        {formatRelativeTime(reply.created_at)}
                      </time>
                    </div>

                    <div className="story-reply-item__bubble">
                      <p>{reply.message}</p>
                    </div>

                    <div className="story-reply-item__footer">
                      <span className={`story-reply-item__status ${reply.is_seen ? 'is-seen' : ''}`}>
                        {reply.is_seen ? (
                          <>
                            <CheckCheck size={14} aria-hidden="true" /> Seen
                          </>
                        ) : (
                          <>
                            <Check size={14} aria-hidden="true" /> Sent
                          </>
                        )}
                      </span>

                      <button
                        className="story-reply-item__delete"
                        type="button"
                        title="Delete reply"
                        aria-label="Delete reply"
                        onClick={() => handleDeleteReply(reply.id)}
                      >
                        <Trash2 size={13} aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })
          ) : (
            <div className="story-replies-empty">
              <div className="story-replies-empty__icon">
                <MessageSquareOff size={32} aria-hidden="true" />
              </div>
              <h3>No replies yet</h3>
              <p>When connections reply to your Story, their messages will appear here.</p>
            </div>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default StoryRepliesModal;
