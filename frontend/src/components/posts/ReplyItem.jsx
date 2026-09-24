import { useState, useRef, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { MoreHorizontal, Pencil, Trash2, ThumbsUp } from 'lucide-react';
import postApi from '../../api/postApi';
import { getAvatarUrl } from '../../utils/assetHelper';
import { renderContentWithLinks } from '../../utils/linkHelper';

const REACTION_CONFIG = {
  like: { emoji: '👍', label: 'Like', color: '#2563eb' },
  love: { emoji: '❤️', label: 'Love', color: '#dc2626' },
  haha: { emoji: '😂', label: 'Haha', color: '#d97706' },
  wow: { emoji: '😮', label: 'Wow', color: '#7c3aed' },
  sad: { emoji: '😢', label: 'Sad', color: '#2563eb' },
  angry: { emoji: '😡', label: 'Angry', color: '#ea580c' },
};

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);
  if (diffSec < 60) return 'just now';
  if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m`;
  if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h`;
  if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d`;
  return date.toLocaleDateString();
}

function getInitials(name) {
  if (!name) return 'M';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'M';
}

export function ReplyItem({
  reply,
  post,
  currentUser,
  onDeleteReply,
  onUpdateReply,
}) {
  const author = reply.member || reply.author || reply.user;
  const currentMemberId = currentUser?.id;
  const isAuthor = reply.member_id === currentMemberId;
  const isPostOwner = post?.member_id === currentMemberId;
  const canEdit = isAuthor;
  const canDelete = isAuthor || isPostOwner;

  const [isEditing, setIsEditing] = useState(false);
  const [editText, setEditText] = useState(reply.comment);
  const [isSaving, setIsSaving] = useState(false);
  const [optionsOpen, setOptionsOpen] = useState(false);
  const [pickerOpen, setPickerOpen] = useState(false);

  // Reaction state
  const [userReaction, setUserReaction] = useState(reply.user_reaction || null);
  const [reactionsCount, setReactionsCount] = useState(
    reply.reactions_count || (reply.reactions ? reply.reactions.length : 0)
  );

  const menuRef = useRef(null);
  const replyLikeWrapRef = useRef(null);
  const pickerOpenTimerRef = useRef(null);
  const pickerCloseTimerRef = useRef(null);
  const touchLongPressTimerRef = useRef(null);
  const isTouchInteractionRef = useRef(false);
  const justInteractedRef = useRef(false);
  const isReactingRef = useRef(false);

  const clearPickerTimers = () => {
    if (pickerOpenTimerRef.current) {
      clearTimeout(pickerOpenTimerRef.current);
      pickerOpenTimerRef.current = null;
    }
    if (pickerCloseTimerRef.current) {
      clearTimeout(pickerCloseTimerRef.current);
      pickerCloseTimerRef.current = null;
    }
    if (touchLongPressTimerRef.current) {
      clearTimeout(touchLongPressTimerRef.current);
      touchLongPressTimerRef.current = null;
    }
  };

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setOptionsOpen(false);
      }
      if (replyLikeWrapRef.current && !replyLikeWrapRef.current.contains(e.target)) {
        clearPickerTimers();
        setPickerOpen(false);
        isTouchInteractionRef.current = false;
      }
    };

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        setOptionsOpen(false);
        clearPickerTimers();
        setPickerOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('touchstart', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('touchstart', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
      clearPickerTimers();
    };
  }, []);

  const handleSaveEdit = async (e) => {
    e.preventDefault();
    if (!editText.trim()) return;
    setIsSaving(true);
    try {
      await postApi.updateComment(reply.id, editText.trim());
      setIsEditing(false);
      if (onUpdateReply) onUpdateReply(reply.id, editText.trim());
    } catch {
      // Error handling
    } finally {
      setIsSaving(false);
    }
  };

  const handleMouseEnterLike = () => {
    if (isTouchInteractionRef.current) return;
    if (justInteractedRef.current) return;

    if (pickerCloseTimerRef.current) {
      clearTimeout(pickerCloseTimerRef.current);
      pickerCloseTimerRef.current = null;
    }

    if (pickerOpen) return;

    if (pickerOpenTimerRef.current) clearTimeout(pickerOpenTimerRef.current);
    pickerOpenTimerRef.current = setTimeout(() => {
      setPickerOpen(true);
    }, 180);
  };

  const handleMouseLeaveLike = () => {
    if (isTouchInteractionRef.current) return;

    if (pickerOpenTimerRef.current) {
      clearTimeout(pickerOpenTimerRef.current);
      pickerOpenTimerRef.current = null;
    }
    justInteractedRef.current = false;

    if (pickerCloseTimerRef.current) clearTimeout(pickerCloseTimerRef.current);
    pickerCloseTimerRef.current = setTimeout(() => {
      setPickerOpen(false);
    }, 320);
  };

  const handleTouchStartLike = () => {
    isTouchInteractionRef.current = true;
    if (touchLongPressTimerRef.current) clearTimeout(touchLongPressTimerRef.current);
    touchLongPressTimerRef.current = setTimeout(() => {
      setPickerOpen(true);
    }, 350);
  };

  const handleTouchEndLike = () => {
    if (touchLongPressTimerRef.current) {
      clearTimeout(touchLongPressTimerRef.current);
      touchLongPressTimerRef.current = null;
    }
  };

  const handleReact = async (reactionType = 'like') => {
    clearPickerTimers();
    justInteractedRef.current = true;
    setPickerOpen(false);

    if (isReactingRef.current) return;
    isReactingRef.current = true;

    // Snapshot previous state for rollback
    const prevReaction = userReaction;
    const prevCount = reactionsCount;

    // Calculate optimistic state
    const isTogglingOff = prevReaction === reactionType;
    const nextReaction = isTogglingOff ? null : reactionType;
    const nextCount = isTogglingOff
      ? Math.max(0, prevCount - 1)
      : prevReaction
      ? prevCount
      : prevCount + 1;

    // Apply immediate optimistic state
    setUserReaction(nextReaction);
    setReactionsCount(nextCount);

    try {
      const response = await postApi.reactToComment(reply.id, reactionType);
      if (response && response.success) {
        setUserReaction(response.user_reaction);
        setReactionsCount(response.reactions_count);
      } else {
        setUserReaction(prevReaction);
        setReactionsCount(prevCount);
      }
    } catch {
      setUserReaction(prevReaction);
      setReactionsCount(prevCount);
    } finally {
      isReactingRef.current = false;
    }
  };

  const [imgError, setImgError] = useState(false);

  const rawPhoto = author?.profile_photo_url
    || author?.profile_photo
    || (author?.avatar_url && !author.avatar_url.includes('profile.png') ? author.avatar_url : null);

  const isDefaultPlaceholder = rawPhoto && (
    rawPhoto.includes('default.png') ||
    rawPhoto.includes('member_assets/images/dashboard/image/profile.png')
  );

  const photoUrl = rawPhoto && !isDefaultPlaceholder ? getAvatarUrl(rawPhoto) : null;

  useEffect(() => {
    setImgError(false);
  }, [photoUrl]);

  const activeReactionData = userReaction ? REACTION_CONFIG[userReaction] : null;

  return (
    <div className="post-reply-item" id={`post-comment-${reply.id}`}>
      <div className="post-reply-item__avatar-wrap">
        {photoUrl && !imgError ? (
          <img
            className="post-reply-item__avatar"
            src={photoUrl}
            alt={author?.name || 'User'}
            onError={() => setImgError(true)}
          />
        ) : (
          <span className="post-reply-item__avatar post-reply-item__avatar--initials">
            {getInitials(author?.name)}
          </span>
        )}
      </div>

      <div className="post-reply-item__content" ref={menuRef}>
        <div className="post-reply-item__bubble">
          <div className="post-reply-item__meta">
            <Link
              to={`/member/people/${author?.id}`}
              style={{ color: 'inherit', textDecoration: 'none' }}
            >
              <strong className="post-reply-item__name">{author?.name || 'Member'}</strong>
            </Link>
            <span className="post-reply-item__id">
              {author?.user_id ? (author.user_id.startsWith('@') ? author.user_id : `@${author.user_id}`) : `ID: #${author?.id}`}
            </span>

            {(canEdit || canDelete) && (
              <div className="post-comment-options-wrapper">
                <button
                  className="post-comment-item__options-btn"
                  type="button"
                  aria-label="Reply options"
                  onClick={() => setOptionsOpen((prev) => !prev)}
                >
                  <MoreHorizontal size={14} aria-hidden="true" />
                </button>
                {optionsOpen && (
                  <div className="post-comment-options-menu" style={{ display: 'block' }}>
                    {canEdit && (
                      <button
                        className="post-comment-options-menu__item"
                        type="button"
                        onClick={() => {
                          setIsEditing(true);
                          setOptionsOpen(false);
                        }}
                      >
                        <Pencil size={12} aria-hidden="true" />
                        Edit
                      </button>
                    )}
                    {canDelete && (
                      <button
                        className="post-comment-options-menu__item post-comment-options-menu__item--danger"
                        type="button"
                        onClick={() => {
                          setOptionsOpen(false);
                          if (onDeleteReply) onDeleteReply(reply.id);
                        }}
                      >
                        <Trash2 size={12} aria-hidden="true" />
                        Delete
                      </button>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {isEditing ? (
            <form className="post-comment-edit-form" onSubmit={handleSaveEdit}>
              <textarea
                className="post-comment-edit-form__input"
                value={editText}
                onChange={(e) => setEditText(e.target.value)}
                maxLength={1000}
                required
                rows={2}
              />
              <div className="post-comment-edit-form__actions">
                <button
                  type="button"
                  className="post-comment-edit-form__btn-cancel"
                  onClick={() => setIsEditing(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="post-comment-edit-form__btn-save"
                  disabled={isSaving}
                >
                  {isSaving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </form>
          ) : (
            <p className="post-reply-item__text">{renderContentWithLinks(reply.comment)}</p>
          )}
        </div>

        {/* Reply Footer: Like & Time */}
        <div className="post-comment-item__footer">
          <div
            ref={replyLikeWrapRef}
            className="post-comment-like-wrap"
            onMouseEnter={handleMouseEnterLike}
            onMouseLeave={handleMouseLeaveLike}
            style={{ position: 'relative', display: 'inline-flex' }}
          >
            {pickerOpen && (
              <div
                className="post-reaction-picker post-reaction-picker--comment is-visible"
                style={{ display: 'flex', bottom: '100%', position: 'absolute' }}
                onMouseEnter={handleMouseEnterLike}
                onMouseLeave={handleMouseLeaveLike}
                onClick={(e) => e.stopPropagation()}
              >
                {Object.entries(REACTION_CONFIG).map(([type, config]) => (
                  <button
                    key={type}
                    className="post-reaction-picker__item"
                    type="button"
                    title={config.label}
                    onClick={(e) => {
                      e.stopPropagation();
                      handleReact(type);
                    }}
                  >
                    {config.emoji}
                  </button>
                ))}
              </div>
            )}

            <button
              className={`post-comment-item__action-btn ${userReaction ? 'is-active' : ''}`}
              type="button"
              style={activeReactionData ? { color: activeReactionData.color, fontWeight: 600 } : {}}
              onTouchStart={handleTouchStartLike}
              onTouchEnd={handleTouchEndLike}
              onTouchCancel={handleTouchEndLike}
              onClick={() => handleReact(userReaction || 'like')}
            >
              {activeReactionData ? (
                <>
                  <span>{activeReactionData.emoji}</span>
                  <span>{activeReactionData.label}</span>
                </>
              ) : (
                <>
                  <ThumbsUp size={12} />
                  <span>Like</span>
                </>
              )}
            </button>
          </div>

          {reactionsCount > 0 && (
            <span className="post-comment-item__reaction-count">
              <span className="post-comment-item__badge-icon">👍</span>
              <span>{reactionsCount}</span>
            </span>
          )}

          <time className="post-comment-item__time">
            {formatRelativeTime(reply.created_at)}
          </time>
        </div>
      </div>
    </div>
  );
}

export default ReplyItem;
