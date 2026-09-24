import { useState, useRef, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Ellipsis,
  Bookmark,
  EyeOff,
  Flag,
  Video,
  ThumbsUp,
  MessageCircle,
  Share2,
  ArrowRight,
  SendHorizontal,
  BadgeCheck,
} from 'lucide-react';
import WatchPlayer from './WatchPlayer';
import CommentItem from '../posts/CommentItem';
import ReactorsModal from '../posts/modals/ReactorsModal';
import ShareModal from '../posts/modals/ShareModal';
import ReportModal from '../posts/modals/ReportModal';
import AccountVerificationModal from '../verification/AccountVerificationModal';
import postApi from '../../api/postApi';
import { useAuth } from '../../hooks/useAuth';
import { getAvatarUrl, getMediaUrl } from '../../utils/assetHelper';
import { renderContentWithLinks } from '../../utils/linkHelper';
import { isMemberMobileVerified } from '../../utils/whatsappVerification';

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

export function WatchVideoCard({ post, currentUser: propCurrentUser, onPostHidden, onPostUpdated }) {
  const { user: authUser } = useAuth();
  const currentUser = propCurrentUser || authUser;
  const isVerifiedMember = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const currentMemberId = currentUser?.id;
  const isAuthor = post.member_id === currentMemberId;
  const bizPage = post.business_page;
  const isBizAdmin = bizPage ? Boolean(bizPage.is_admin || bizPage.admin_id === currentMemberId) : false;
  const isOwner = isAuthor || isBizAdmin;
  const author = post.member;
  const authorName = bizPage ? bizPage.page_name : author?.name || 'Member';
  const authorPhoto = bizPage
    ? (bizPage.logo ? getAvatarUrl(bizPage.logo) : null)
    : (author?.profile_photo ? getAvatarUrl(author.profile_photo) : null);
  const isSharedPost = Boolean(post.original_post_id);
  const originalPost = post.original_post;
  const videoPost = isSharedPost && originalPost && originalPost.media_type === 'video' ? originalPost : post;
  const videoPath = videoPost?.media_path ? getMediaUrl(videoPost.media_path) : null;

  // Local interaction states
  const [isSaved, setIsSaved] = useState(Boolean(post.is_saved));
  const [savesCount, setSavesCount] = useState(post.saved_posts_count || (post.is_saved ? 1 : 0));
  const [userReaction, setUserReaction] = useState(post.user_reaction || (post.has_liked ? 'like' : null));
  const [reactionsCount, setReactionsCount] = useState(
    post.reactions_count !== undefined ? post.reactions_count : (post.likes_count || 0)
  );

  // Dropdown and picker menus
  const [optionsOpen, setOptionsOpen] = useState(false);
  const [pickerOpen, setPickerOpen] = useState(false);
  const optionsRef = useRef(null);
  const likeWrapperRef = useRef(null);
  const commentInputRef = useRef(null);
  const pickerOpenTimerRef = useRef(null);
  const pickerCloseTimerRef = useRef(null);
  const touchLongPressTimerRef = useRef(null);
  const isTouchInteractionRef = useRef(false);
  const justInteractedRef = useRef(false);
  const isReactingRef = useRef(false);

  // Modals state
  const [showReactorsModal, setShowReactorsModal] = useState(false);
  const [reactorsList, setReactorsList] = useState([]);
  const [showShareModal, setShowShareModal] = useState(false);
  const [showReportModal, setShowReportModal] = useState(false);
  const [authorAvatarError, setAuthorAvatarError] = useState(false);
  const [userAvatarError, setUserAvatarError] = useState(false);

  // Comments state
  const [comments, setComments] = useState(post.comments || []);
  const [commentsCount, setCommentsCount] = useState(
    post.comments_count !== undefined ? post.comments_count : (post.comments ? post.comments.length : 0)
  );
  const [newCommentText, setNewCommentText] = useState('');
  const [isSubmittingComment, setIsSubmittingComment] = useState(false);
  const [hasMoreComments, setHasMoreComments] = useState(
    (post.comments_count || 0) > (post.comments ? post.comments.length : 0)
  );
  const [isLoadingMoreComments, setIsLoadingMoreComments] = useState(false);

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
      if (optionsRef.current && !optionsRef.current.contains(e.target)) {
        setOptionsOpen(false);
      }
      if (likeWrapperRef.current && !likeWrapperRef.current.contains(e.target)) {
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

  const handleToggleSave = async () => {
    setOptionsOpen(false);
    try {
      const response = await postApi.toggleSave(post.id);
      setIsSaved(response.is_saved);
      setSavesCount(response.saves_count);
    } catch {
      // Revert
    }
  };

  const handleHidePost = async () => {
    setOptionsOpen(false);
    try {
      await postApi.hidePost(post.id);
      if (onPostHidden) onPostHidden(post.id);
    } catch {
      // Revert
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

    if (!isVerifiedMember) {
      setShowVerifyModal(true);
      return;
    }

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
      const response = await postApi.reactToPost(post.id, reactionType);
      if (response && response.success) {
        const confirmedReaction = response.user_reaction ?? (response.has_liked ? 'like' : null);
        const confirmedCount = response.reactions_count !== undefined
          ? response.reactions_count
          : (response.likes_count !== undefined ? response.likes_count : nextCount);
        setUserReaction(confirmedReaction);
        setReactionsCount(confirmedCount);
        if (onPostUpdated) {
          onPostUpdated({
            ...post,
            user_reaction: confirmedReaction,
            has_liked: Boolean(confirmedReaction),
            reactions_count: confirmedCount,
            likes_count: response.likes_count !== undefined ? response.likes_count : confirmedCount,
          });
        }
      } else {
        // Rollback
        setUserReaction(prevReaction);
        setReactionsCount(prevCount);
      }
    } catch {
      // Rollback on network/auth error
      setUserReaction(prevReaction);
      setReactionsCount(prevCount);
    } finally {
      isReactingRef.current = false;
    }
  };

  const handleOpenReactors = async () => {
    if (!isOwner) return;
    setShowReactorsModal(true);
    try {
      const data = await postApi.getReactors(post.id);
      if (data && data.reactors) {
        setReactorsList(data.reactors);
      }
    } catch {
      // Fallback
    }
  };

  const handleLoadMoreComments = async () => {
    if (isLoadingMoreComments) return;
    setIsLoadingMoreComments(true);
    try {
      const data = await postApi.getComments(post.id, comments.length);
      if (data && Array.isArray(data.comments)) {
        setComments((prev) => [...data.comments, ...prev]);
        setHasMoreComments(data.has_more);
      }
    } catch {
      // Error
    } finally {
      setIsLoadingMoreComments(false);
    }
  };

  const handleCreateComment = async (e) => {
    e.preventDefault();
    if (!newCommentText.trim() || isSubmittingComment) return;
    setIsSubmittingComment(true);
    try {
      const data = await postApi.createComment(post.id, newCommentText.trim());
      if (data && data.comment) {
        setComments((prev) => [...prev, data.comment]);
        setCommentsCount((prev) => prev + 1);
        setNewCommentText('');
      }
    } catch {
      // Error
    } finally {
      setIsSubmittingComment(false);
    }
  };

  const handleCommentUpdated = (updatedComment) => {
    setComments((prev) => prev.map((c) => (c.id === updatedComment.id ? updatedComment : c)));
  };

  const handleCommentDeleted = (commentId) => {
    setComments((prev) => prev.filter((c) => c.id !== commentId));
    setCommentsCount((prev) => Math.max(0, prev - 1));
  };

  const userPhoto = currentUser?.profile_photo ? getAvatarUrl(currentUser.profile_photo) : null;

  return (
    <article className="card feed-post watch-video-card" data-post-id={post.id}>
      {/* Header */}
      <header className="post-header">
        {bizPage ? (
          <Link to={`/member/business-pages/${bizPage.slug || bizPage.id}`}>
            {authorPhoto && !authorAvatarError ? (
              <img
                className="avatar"
                src={authorPhoto}
                alt={bizPage.page_name}
                loading="lazy"
                onError={() => setAuthorAvatarError(true)}
              />
            ) : (
              <span className="avatar post-avatar-initials" role="img" aria-label={bizPage.page_name}>
                {getInitials(bizPage.page_name)}
              </span>
            )}
          </Link>
        ) : (
          <Link to={`/member/people/${author?.id}`}>
            {authorPhoto && !authorAvatarError ? (
              <img
                className="avatar"
                src={authorPhoto}
                alt={author?.name}
                loading="lazy"
                onError={() => setAuthorAvatarError(true)}
              />
            ) : (
              <span className="avatar post-avatar-initials" role="img" aria-label={authorName}>
                {getInitials(authorName)}
              </span>
            )}
          </Link>
        )}

        <div className="post-header__meta">
          <div>
            {bizPage ? (
              <Link to={`/member/business-pages/${bizPage.slug || bizPage.id}`} style={{ color: 'inherit', textDecoration: 'none' }}>
                <strong>{bizPage.page_name}</strong>
              </Link>
            ) : (
              <Link to={`/member/people/${author?.id}`} style={{ color: 'inherit', textDecoration: 'none' }}>
                <strong>{authorName}</strong>
              </Link>
            )}

            {bizPage?.is_verified && (
              <BadgeCheck size={15} color="#20c875" style={{ verticalAlign: 'middle', marginLeft: '3px' }} title="Verified Business" />
            )}

            {!bizPage && author?.user_id && (
              <small style={{ color: 'var(--color-text-muted)', fontSize: '0.775rem', marginLeft: '4px' }}>
                @{author.user_id}
              </small>
            )}
          </div>

          <Link to={`/member/posts/${post.id}`}>
            <time dateTime={post.created_at}>{formatRelativeTime(post.created_at)}</time>
            <span aria-hidden="true"> · </span>
            <Video size={13} aria-hidden="true" title="Watch Video" />
          </Link>
        </div>

        {/* Options menu */}
        <div className="post-header__options" ref={optionsRef}>
          <button
            className="mini-button"
            type="button"
            aria-label="Post options"
            onClick={() => setOptionsOpen((prev) => !prev)}
          >
            <Ellipsis size={18} aria-hidden="true" />
          </button>

          {optionsOpen && (
            <div className="post-options-menu" style={{ display: 'block' }}>
              <button className="post-options-menu__item" type="button" onClick={handleToggleSave}>
                <Bookmark size={14} aria-hidden="true" />
                <span>{isSaved ? 'Unsave Video' : 'Save Video'}</span>
              </button>

              <button className="post-options-menu__item" type="button" onClick={handleHidePost}>
                <EyeOff size={14} aria-hidden="true" />
                <span>Hide Video</span>
              </button>

              <button
                className="post-options-menu__item post-options-menu__item--danger"
                type="button"
                onClick={() => {
                  setOptionsOpen(false);
                  setShowReportModal(true);
                }}
              >
                <Flag size={14} aria-hidden="true" />
                <span>Report Video</span>
              </button>
            </div>
          )}
        </div>
      </header>

      {/* Caption Body */}
      {post.body && (
        <p className="post-copy watch-video-card__copy">
          {renderContentWithLinks(post.body)}
        </p>
      )}

      {/* Custom Responsive Video Player */}
      {videoPath && (
        <WatchPlayer src={videoPath} authorName={authorName} />
      )}

      {/* Private Owner Reaction Banner */}
      {isOwner && reactionsCount > 0 && (
        <div className="post-owner-reaction-banner">
          <span>
            <ThumbsUp size={13} style={{ verticalAlign: '-1px', marginRight: '6px' }} />
            <strong>{reactionsCount} {reactionsCount === 1 ? 'member' : 'members'}</strong> reacted to your video
          </span>
          <button
            type="button"
            className="post-owner-reaction-banner__btn"
            onClick={handleOpenReactors}
          >
            View Reactions
          </button>
        </div>
      )}

      {/* Footer Actions */}
      <footer className="post-footer">
        <div className="post-actions">
          {/* Like / Reaction Button & Picker */}
          <div
            ref={likeWrapperRef}
            className="post-like-wrapper"
            onMouseEnter={handleMouseEnterLike}
            onMouseLeave={handleMouseLeaveLike}
            style={{ position: 'relative' }}
          >
            {pickerOpen && (
              <div
                className="post-reaction-picker is-visible"
                role="toolbar"
                aria-label="Choose a reaction"
                onMouseEnter={handleMouseEnterLike}
                onMouseLeave={handleMouseLeaveLike}
                onClick={(e) => e.stopPropagation()}
              >
                {Object.entries(REACTION_CONFIG).map(([rType, rData]) => (
                  <button
                    key={rType}
                    className="post-reaction-picker__item"
                    type="button"
                    title={rData.label}
                    aria-label={`React ${rData.label}`}
                    onClick={(e) => {
                      e.stopPropagation();
                      handleReact(rType);
                    }}
                  >
                    {rData.emoji}
                  </button>
                ))}
              </div>
            )}

            <button
              className={`post-action-btn post-action-btn--like ${userReaction ? 'is-active' : ''}`}
              type="button"
              aria-label={userReaction ? `Reacted ${REACTION_CONFIG[userReaction]?.label || 'Like'}` : 'Like video'}
              onTouchStart={handleTouchStartLike}
              onTouchEnd={handleTouchEndLike}
              onTouchCancel={handleTouchEndLike}
              onClick={() => handleReact(userReaction || 'like')}
              style={
                userReaction
                  ? {
                      color: REACTION_CONFIG[userReaction]?.color || '#2563eb',
                      borderColor: `${REACTION_CONFIG[userReaction]?.color || '#2563eb'}44`,
                      background: `${REACTION_CONFIG[userReaction]?.color || '#2563eb'}12`,
                    }
                  : undefined
              }
            >
              <span className="post-action-btn__icon">
                {userReaction ? REACTION_CONFIG[userReaction]?.emoji : <ThumbsUp size={16} aria-hidden="true" />}
              </span>
              <span>{userReaction ? REACTION_CONFIG[userReaction]?.label : 'Like'}</span>
            </button>
          </div>

          {/* Reactions Count (Clickable modal trigger for Post Owner only; static badge for others) */}
          {isOwner ? (
            <button
              className="post-action-btn post-action-btn--likers"
              type="button"
              aria-label="View who reacted to your video"
              title="View who reacted to your video"
              onClick={handleOpenReactors}
            >
              <span className="post-actions__reaction-badges">
                <span className="post-actions__badge">👍</span>
              </span>
              <span>{reactionsCount}</span>
            </button>
          ) : (
            <span
              className="post-action-badge post-action-btn--likers"
              aria-label={`${reactionsCount} reactions`}
              title={`${reactionsCount} reactions`}
            >
              <span className="post-actions__reaction-badges">
                <span className="post-actions__badge">👍</span>
              </span>
              <span>{reactionsCount}</span>
            </span>
          )}

          {/* Comment button */}
          <button
            className="post-action-btn post-action-btn--comment-count"
            type="button"
            aria-label="View Comments"
            onClick={() => commentInputRef.current?.focus()}
          >
            <MessageCircle size={16} aria-hidden="true" />
            <span>Comments ({commentsCount})</span>
          </button>

          {/* Save Video button */}
          <button
            className={`post-action-btn post-action-btn--save ${isSaved ? 'is-active' : ''}`}
            type="button"
            aria-label="Save Video"
            onClick={handleToggleSave}
          >
            <Bookmark size={16} aria-hidden="true" />
            <span>
              <span>{isSaved ? 'Saved' : 'Save'}</span> ({savesCount})
            </span>
          </button>

          {/* Share Video button */}
          <button
            className="post-action-btn post-action-btn--share"
            type="button"
            aria-label="Share Video"
            onClick={() => setShowShareModal(true)}
          >
            <Share2 size={16} aria-hidden="true" />
            <span>Share</span>
          </button>
        </div>

        <Link className="post-detail-link" to={`/member/posts/${post.id}`}>
          View video <ArrowRight size={14} aria-hidden="true" />
        </Link>
      </footer>

      {/* Post Comments Section */}
      <div className="post-comments">
        {hasMoreComments && (
          <button
            className="post-comments__more"
            type="button"
            onClick={handleLoadMoreComments}
            disabled={isLoadingMoreComments}
          >
            {isLoadingMoreComments ? 'Loading previous comments...' : 'View previous comments'}
          </button>
        )}

        <div className="post-comments__list">
          {comments.length > 0 ? (
            comments.map((comment) => (
              <CommentItem
                key={comment.id}
                post={post}
                comment={comment}
                currentUser={currentUser}
                onCommentUpdated={handleCommentUpdated}
                onCommentDeleted={handleCommentDeleted}
              />
            ))
          ) : (
            <div className="post-comments__empty">
              <p>Be the first to comment on this video.</p>
            </div>
          )}
        </div>

        {/* Comment Form */}
        <form className="post-comment-form" onSubmit={handleCreateComment}>
          <div className="post-comment-form__avatar-wrap">
            {userPhoto && !userAvatarError ? (
              <img
                className="post-comment-form__avatar"
                src={userPhoto}
                alt={currentUser?.name}
                onError={() => setUserAvatarError(true)}
              />
            ) : (
              <span className="post-comment-form__avatar post-comment-form__avatar--initials">
                {getInitials(currentUser?.name)}
              </span>
            )}
          </div>

          <div className="post-comment-form__input-wrap">
            <input
              ref={commentInputRef}
              className="post-comment-form__input"
              type="text"
              name="comment"
              value={newCommentText}
              onChange={(e) => setNewCommentText(e.target.value)}
              placeholder="Write a comment..."
              aria-label="Write a comment"
              maxLength={1000}
              required
              autoComplete="off"
            />
            <button
              className="post-comment-form__submit"
              type="submit"
              aria-label="Send Comment"
              disabled={isSubmittingComment || !newCommentText.trim()}
            >
              <SendHorizontal size={16} aria-hidden="true" />
            </button>
          </div>
        </form>
      </div>

      {/* Modals */}
      {showReactorsModal && (
        <ReactorsModal
          reactors={reactorsList}
          onClose={() => setShowReactorsModal(false)}
        />
      )}

      {showShareModal && (
        <ShareModal
          isOpen={showShareModal}
          post={post}
          onClose={() => setShowShareModal(false)}
          onShareSuccess={(response) => {
            if (onPostUpdated) {
              onPostUpdated({
                ...post,
                shares_count: response?.shares_count ?? ((post.shares_count || 0) + 1),
              });
            }
          }}
        />
      )}

      {showReportModal && (
        <ReportModal
          isOpen={showReportModal}
          postId={
            (/^\d+$/.test(String(post?.id || '')) ? post.id : null) ||
            post?.post_id ||
            post?.source_post_id ||
            post?.ad_campaign?.post_id ||
            post?.campaign?.post_id ||
            post?.id
          }
          post={post}
          onClose={() => setShowReportModal(false)}
        />
      )}

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage="Please verify your mobile number through WhatsApp before reacting to or liking posts."
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => setShowVerifyModal(false)}
        />
      )}
    </article>
  );
}

export default WatchVideoCard;
