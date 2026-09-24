import { useState, useRef, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { MoreHorizontal, Pencil, Trash2, ThumbsUp, SendHorizontal } from 'lucide-react';
import ReplyItem from './ReplyItem';
import CommentReactorsModal from './modals/CommentReactorsModal';
import postApi from '../../api/postApi';
import VerifiedBadge from '../common/VerifiedBadge';
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

export function CommentItem({
  comment,
  post,
  currentUser,
  onDeleteComment,
}) {
  const author = comment.member || comment.author || comment.user;
  const currentMemberId = currentUser?.id;
  const isAuthor = comment.member_id === currentMemberId;
  const isPostOwner = post?.member_id === currentMemberId;
  const canEdit = isAuthor;
  const canDelete = isAuthor || isPostOwner;

  const [isEditing, setIsEditing] = useState(false);
  const [commentBody, setCommentBody] = useState(comment.comment);
  const [editText, setEditText] = useState(comment.comment);
  const [isSaving, setIsSaving] = useState(false);
  const [optionsOpen, setOptionsOpen] = useState(false);
  const [pickerOpen, setPickerOpen] = useState(false);

  // Reaction state
  const [userReaction, setUserReaction] = useState(comment.user_reaction || null);
  const [reactionsCount, setReactionsCount] = useState(
    comment.reactions_count || (comment.reactions ? comment.reactions.length : 0)
  );
  const [showReactorsModal, setShowReactorsModal] = useState(false);
  const [reactorsList, setReactorsList] = useState([]);

  // Replies state
  const [showReplyForm, setShowReplyForm] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [isSubmittingReply, setIsSubmittingReply] = useState(false);
  const [replies, setReplies] = useState(comment.replies || []);
  const [repliesCount, setRepliesCount] = useState(
    comment.replies_count || (comment.replies ? comment.replies.length : 0)
  );
  const [hasMoreReplies, setHasMoreReplies] = useState(
    (comment.replies_count || 0) > (comment.replies ? comment.replies.length : 0)
  );
  const [isLoadingReplies, setIsLoadingReplies] = useState(false);

  useEffect(() => {
    if (comment.replies) {
      setReplies(comment.replies);
    }
    const count = typeof comment.replies_count !== 'undefined'
      ? comment.replies_count
      : (comment.replies ? comment.replies.length : 0);
    setRepliesCount(count);
    setHasMoreReplies(count > (comment.replies ? comment.replies.length : 0));
  }, [comment.replies, comment.replies_count]);

  const menuRef = useRef(null);
  const commentLikeWrapRef = useRef(null);
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
      if (commentLikeWrapRef.current && !commentLikeWrapRef.current.contains(e.target)) {
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
      await postApi.updateComment(comment.id, editText.trim());
      setCommentBody(editText.trim());
      setIsEditing(false);
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
      const response = await postApi.reactToComment(comment.id, reactionType);
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

  const handleOpenReactors = async () => {
    setShowReactorsModal(true);
    try {
      const data = await postApi.getCommentReactors(comment.id);
      if (data && data.reactors) {
        setReactorsList(data.reactors);
      }
    } catch {
      // Fallback
    }
  };

  const handleLoadMoreReplies = async () => {
    if (isLoadingReplies) return;
    setIsLoadingReplies(true);
    try {
      const data = await postApi.getReplies(comment.id, replies.length);
      if (data && Array.isArray(data.replies)) {
        setReplies((prev) => {
          const existingIds = new Set(prev.map((r) => r.id));
          const newReplies = data.replies.filter((r) => !existingIds.has(r.id));
          return [...newReplies, ...prev];
        });
        setHasMoreReplies(data.has_more);
        if (typeof data.replies_count !== 'undefined') {
          setRepliesCount(data.replies_count);
        }
      }
    } catch {
      // Fallback
    } finally {
      setIsLoadingReplies(false);
    }
  };

  const handleCreateReply = async (e) => {
    e.preventDefault();
    if (!replyText.trim() || isSubmittingReply) return;
    setIsSubmittingReply(true);
    try {
      const data = await postApi.createReply(comment.id, replyText.trim());
      if (data && data.reply) {
        setReplies((prev) => {
          if (prev.some((r) => r.id === data.reply.id)) return prev;
          return [...prev, data.reply];
        });
      } else {
        // Optimistic reply object
        const newReplyObj = {
          id: data?.reply_id || Date.now(),
          comment: replyText.trim(),
          member_id: currentMemberId,
          member: currentUser,
          parent_id: comment.id,
          created_at: new Date().toISOString(),
          reactions_count: 0,
        };
        setReplies((prev) => [...prev, newReplyObj]);
      }
      setRepliesCount((prev) => (data && typeof data.replies_count !== 'undefined' ? data.replies_count : prev + 1));
      setReplyText('');
      setShowReplyForm(false);
    } catch {
      // Error handling
    } finally {
      setIsSubmittingReply(false);
    }
  };

  const handleDeleteReply = async (replyId) => {
    try {
      await postApi.deleteComment(replyId);
      setReplies((prev) => prev.filter((r) => r.id !== replyId));
      setRepliesCount((prev) => Math.max(0, prev - 1));
    } catch {
      // Error handling
    }
  };

  const handleUpdateReply = (replyId, newText) => {
    setReplies((prev) =>
      prev.map((r) => (r.id === replyId ? { ...r, comment: newText } : r))
    );
  };

  const [imgError, setImgError] = useState(false);
  const [userImgError, setUserImgError] = useState(false);

  const rawAuthorPhoto = author?.profile_photo_url
    || author?.profile_photo
    || (author?.avatar_url && !author.avatar_url.includes('profile.png') ? author.avatar_url : null);
  const isAuthorPlaceholder = rawAuthorPhoto && (
    rawAuthorPhoto.includes('default.png') ||
    rawAuthorPhoto.includes('member_assets/images/dashboard/image/profile.png')
  );
  const photoUrl = rawAuthorPhoto && !isAuthorPlaceholder ? getAvatarUrl(rawAuthorPhoto) : null;

  const rawUserPhoto = currentUser?.profile_photo_url
    || currentUser?.profile_photo
    || (currentUser?.avatar_url && !currentUser.avatar_url.includes('profile.png') ? currentUser.avatar_url : null);
  const isUserPlaceholder = rawUserPhoto && (
    rawUserPhoto.includes('default.png') ||
    rawUserPhoto.includes('member_assets/images/dashboard/image/profile.png')
  );
  const userPhotoUrl = rawUserPhoto && !isUserPlaceholder ? getAvatarUrl(rawUserPhoto) : null;

  useEffect(() => {
    setImgError(false);
  }, [photoUrl]);

  useEffect(() => {
    setUserImgError(false);
  }, [userPhotoUrl]);

  const activeReactionData = userReaction ? REACTION_CONFIG[userReaction] : null;

  return (
    <div className="post-comment-item" id={`post-comment-${comment.id}`}>
      <div className="post-comment-item__avatar-wrap">
        {photoUrl && !imgError ? (
          <img
            className="post-comment-item__avatar"
            src={photoUrl}
            alt={author?.name || 'User'}
            onError={() => setImgError(true)}
          />
        ) : (
          <span className="post-comment-item__avatar post-comment-item__avatar--initials">
            {getInitials(author?.name)}
          </span>
        )}
      </div>

      <div className="post-comment-item__content" ref={menuRef}>
        <div className="post-comment-item__bubble">
          <div className="post-comment-item__meta">
            <Link
              to={`/member/people/${author?.id}`}
              style={{ color: 'inherit', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}
            >
              <strong className="post-comment-item__name">{author?.name || 'Member'}</strong>
              <VerifiedBadge member={author} size={13} />
            </Link>
            <span className="post-comment-item__id">
              {author?.user_id ? (author.user_id.startsWith('@') ? author.user_id : `@${author.user_id}`) : `ID: #${author?.id}`}
            </span>

            {(canEdit || canDelete) && (
              <div className="post-comment-options-wrapper">
                <button
                  className="post-comment-item__options-btn"
                  type="button"
                  aria-label="Comment options"
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
                          if (onDeleteComment) onDeleteComment(comment.id);
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
            <p className="post-comment-item__text">{renderContentWithLinks(commentBody)}</p>
          )}
        </div>

        {/* Comment Footer: Like / React / Reply / Time */}
        <div className="post-comment-item__footer">
          <div
            ref={commentLikeWrapRef}
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

          <button
            className="post-comment-item__action-btn"
            type="button"
            onClick={() => setShowReplyForm((prev) => !prev)}
          >
            Reply
          </button>

          {reactionsCount > 0 && (
            <button
              type="button"
              className="post-comment-item__reaction-count"
              onClick={handleOpenReactors}
              style={{ background: 'none', border: 'none', cursor: 'pointer', padding: 0 }}
            >
              <span className="post-comment-item__badge-icon">👍</span>
              <span>{reactionsCount}</span>
            </button>
          )}

          <time className="post-comment-item__time">
            {formatRelativeTime(comment.created_at)}
          </time>
        </div>

        {/* Nested Replies Section */}
        <div className="post-comment-replies">
          {hasMoreReplies && (
            <button
              type="button"
              className="post-comment-replies__more"
              onClick={handleLoadMoreReplies}
              disabled={isLoadingReplies}
            >
              {isLoadingReplies ? 'Loading replies...' : `View previous replies (${repliesCount - replies.length})`}
            </button>
          )}

          {replies.map((reply) => (
            <ReplyItem
              key={reply.id}
              reply={reply}
              post={post}
              currentUser={currentUser}
              onDeleteReply={handleDeleteReply}
              onUpdateReply={handleUpdateReply}
            />
          ))}

          {showReplyForm && (
            <form className="post-reply-form" onSubmit={handleCreateReply} style={{ marginTop: '8px' }}>
              <div className="post-reply-form__avatar-wrap">
                {userPhotoUrl && !userImgError ? (
                  <img
                    className="post-reply-form__avatar"
                    src={userPhotoUrl}
                    alt={currentUser?.name || 'User'}
                    onError={() => setUserImgError(true)}
                  />
                ) : (
                  <span className="post-reply-form__avatar post-reply-form__avatar--initials">
                    {getInitials(currentUser?.name)}
                  </span>
                )}
              </div>
              <div className="post-reply-form__input-wrap">
                <input
                  type="text"
                  className="post-reply-form__input"
                  placeholder={`Reply to ${author?.name || 'comment'}...`}
                  value={replyText}
                  onChange={(e) => setReplyText(e.target.value)}
                  maxLength={1000}
                  autoFocus
                />
                <button
                  type="submit"
                  className="post-reply-form__submit"
                  disabled={!replyText.trim() || isSubmittingReply}
                  aria-label="Send reply"
                >
                  <SendHorizontal size={14} />
                </button>
              </div>
            </form>
          )}
        </div>
      </div>

      {showReactorsModal && (
        <CommentReactorsModal
          isOpen={showReactorsModal}
          onClose={() => setShowReactorsModal(false)}
          reactors={reactorsList}
        />
      )}
    </div>
  );
}

export default CommentItem;
