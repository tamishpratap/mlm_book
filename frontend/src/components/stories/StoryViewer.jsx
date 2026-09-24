import { useState, useEffect, useRef, useCallback } from 'react';
import { createPortal } from 'react-dom';
import {
  X,
  Volume2,
  VolumeX,
  Ellipsis,
  MoreVertical,
  Trash2,
  Link as LinkIcon,
  Eye,
  MessageSquare,
  ThumbsUp,
  Send,
  ImageOff,
  ChevronDown,
  ChevronUp,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';
import storyApi from '../../api/storyApi';
import StoryViewersModal from './modals/StoryViewersModal';
import StoryReactorsModal from './modals/StoryReactorsModal';
import StoryRepliesModal from './modals/StoryRepliesModal';
import DeleteConfirmModal from '../posts/modals/DeleteConfirmModal';
import { getMediaUrl } from '../../utils/assetHelper';
import VerifiedBadge from '../common/VerifiedBadge';
import MemberAvatar from '../common/MemberAvatar';

const REACTION_CONFIG = {
  like: { emoji: '👍', label: 'Like', color: '#3b82f6' },
  love: { emoji: '❤️', label: 'Love', color: '#ef4444' },
  haha: { emoji: '😂', label: 'Haha', color: '#f59e0b' },
  wow: { emoji: '😮', label: 'Wow', color: '#f59e0b' },
  sad: { emoji: '😢', label: 'Sad', color: '#3b82f6' },
  angry: { emoji: '😡', label: 'Angry', color: '#f97316' },
};

const CAPTION_THRESHOLD = 110;

function getCaptionPreview(text, limit = CAPTION_THRESHOLD) {
  if (!text) return '';
  const firstLine = text.split('\n')[0];
  if (firstLine.length <= limit && !text.includes('\n')) {
    return text;
  }
  const source = firstLine.length <= limit ? firstLine : firstLine.slice(0, limit);
  const lastSpace = source.lastIndexOf(' ');
  const cleanCut = lastSpace > limit * 0.6 ? source.slice(0, lastSpace) : source;
  return cleanCut.trim();
}

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

export function StoryViewer({
  storyId,
  currentMemberId,
  onClose,
  onStoryDeleted,
  onNavigateToStory,
}) {
  const [storyData, setStoryData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Sound & progress state
  const [isMuted, setIsMuted] = useState(true);
  const [isPaused, setIsPaused] = useState(false);
  const [isCaptionExpanded, setIsCaptionExpanded] = useState(false);
  const [progressPercent, setProgressPercent] = useState(0);

  // Dropdowns & modals state
  const [optionsOpen, setOptionsOpen] = useState(false);
  const [headerMenuOpen, setHeaderMenuOpen] = useState(false);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [showViewersModal, setShowViewersModal] = useState(false);
  const [showReactorsModal, setShowReactorsModal] = useState(false);
  const [showRepliesModal, setShowRepliesModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  // Viewer interaction state
  const [replyText, setReplyText] = useState('');
  const [isSendingReply, setIsSendingReply] = useState(false);
  const [isReplying, setIsReplying] = useState(false);
  const isReplyingRef = useRef(false);
  const replyInputRef = useRef(null);
  const replyFormRef = useRef(null);

  const setReplyingState = useCallback((replying) => {
    isReplyingRef.current = replying;
    setIsReplying(replying);
  }, []);
  const [replyFeedback, setReplyFeedback] = useState(null);
  const [userReaction, setUserReaction] = useState(null);
  const [reactionsCount, setReactionsCount] = useState(0);
  const [topReactions, setTopReactions] = useState([]);
  const [viewsCount, setViewsCount] = useState(0);
  const [repliesCount, setRepliesCount] = useState(0);
  const [unreadRepliesCount, setUnreadRepliesCount] = useState(0);

  const videoRef = useRef(null);
  const bgVideoRef = useRef(null);
  const progressTimerRef = useRef(null);
  const startTimeRef = useRef(null);
  const elapsedBeforePauseRef = useRef(0);
  const hasAdvancedRef = useRef(false);
  const [isDocumentHidden, setIsDocumentHidden] = useState(
    typeof document !== 'undefined' ? document.hidden : false
  );

  const optionsMenuRef = useRef(null);
  const likeWrapperRef = useRef(null);
  const isReactingRef = useRef(false);
  const pickerOpenTimerRef = useRef(null);
  const pickerCloseTimerRef = useRef(null);
  const touchLongPressTimerRef = useRef(null);
  const isTouchInteractionRef = useRef(false);
  const isLongPressRef = useRef(false);

  const clearPickerTimers = useCallback(() => {
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
  }, []);

  useEffect(() => {
    let isMounted = true;
    if (storyId) {
      hasAdvancedRef.current = false;
      setIsLoading(true);
      setError(null);
      setProgressPercent(0);
      elapsedBeforePauseRef.current = 0;
      setPickerOpen(false);
      setIsCaptionExpanded(false);
      setShowViewersModal(false);
      setShowReactorsModal(false);
      setShowRepliesModal(false);
      setShowDeleteModal(false);
      setOptionsOpen(false);
      setHeaderMenuOpen(false);
      setReplyText('');
      setReplyingState(false);
      clearPickerTimers();
      storyApi
        .getStory(storyId)
        .then((data) => {
          if (isMounted) {
            if (data && data.story) {
              setStoryData(data);
              setUserReaction(data.user_reaction || null);
              setReactionsCount(Number(data.reactions_count) || 0);
              setTopReactions(Array.isArray(data.top_reactions) ? data.top_reactions : []);
              setViewsCount(Number(data.views_count) || 0);
              setRepliesCount(Number(data.replies_count) || 0);
              setUnreadRepliesCount(Number(data.unread_replies_count) || 0);
              setError(null);
            } else {
              setError('Story not found or has expired.');
            }
          }
        })
        .catch((err) => {
          if (!isMounted) return;
          if (err.response?.status === 404) {
            setError('This story has expired or was removed.');
          } else if (err.response?.status === 403) {
            setError('You do not have permission to view this story.');
          } else {
            setError('Unable to load story.');
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
  }, [storyId, clearPickerTimers, setReplyingState]);

  // Tab visibility listener to pause when browser tab loses focus/visibility
  useEffect(() => {
    const handleVisibilityChange = () => {
      setIsDocumentHidden(document.hidden);
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, []);

  const handleNext = useCallback(() => {
    if (isSendingReply) return;
    if (hasAdvancedRef.current) return;
    hasAdvancedRef.current = true;
    if (storyData?.next_story_id) {
      if (onNavigateToStory) {
        onNavigateToStory(storyData.next_story_id);
      }
    } else {
      onClose();
    }
  }, [storyData, onNavigateToStory, onClose, isSendingReply]);

  const handlePrev = useCallback(() => {
    if (isSendingReply) return;
    hasAdvancedRef.current = false;
    if (storyData?.previous_story_id) {
      if (onNavigateToStory) {
        onNavigateToStory(storyData.previous_story_id);
      }
    }
  }, [storyData, onNavigateToStory, isSendingReply]);

  // Combined paused state
  const isAnyModalOpen =
    showViewersModal ||
    showReactorsModal ||
    showRepliesModal ||
    showDeleteModal ||
    optionsOpen ||
    headerMenuOpen ||
    pickerOpen;

  const isStoryPaused =
    isLoading ||
    Boolean(error) ||
    isPaused ||
    isAnyModalOpen ||
    isCaptionExpanded ||
    isDocumentHidden ||
    isReplying ||
    isSendingReply ||
    !storyData;

  const isVideo = storyData?.story?.media_type === 'video';

  // Video play/pause synchronization
  useEffect(() => {
    if (!isVideo) return;
    const video = videoRef.current;
    const bgVideo = bgVideoRef.current;
    if (!video) return;

    if (isStoryPaused) {
      video.pause();
      bgVideo?.pause();
    } else {
      video.play().catch(() => {});
      bgVideo?.play().catch(() => {});
    }
  }, [isVideo, isStoryPaused]);

  // Video progress synchronization with actual media playback
  useEffect(() => {
    if (!isVideo || !storyData) return;
    const video = videoRef.current;
    if (!video) return;

    let animFrame = null;

    const syncProgress = () => {
      if (video.duration && isFinite(video.duration) && video.duration > 0) {
        const pct = Math.min(100, (video.currentTime / video.duration) * 100);
        setProgressPercent(pct);
        if (pct >= 100 || video.ended) {
          if (isReplyingRef.current) {
            return;
          }
          cancelAnimationFrame(animFrame);
          setProgressPercent(100);
          handleNext();
          return;
        }
      }
      if (!video.paused && !video.ended && !isStoryPaused) {
        animFrame = requestAnimationFrame(syncProgress);
      }
    };

    const handleTimeUpdate = () => {
      if (video.duration && isFinite(video.duration) && video.duration > 0) {
        const pct = Math.min(100, (video.currentTime / video.duration) * 100);
        setProgressPercent(pct);
      }
    };

    const handlePlay = () => {
      cancelAnimationFrame(animFrame);
      if (!isStoryPaused) {
        animFrame = requestAnimationFrame(syncProgress);
      }
    };

    const handlePause = () => {
      cancelAnimationFrame(animFrame);
    };

    const handleEnded = () => {
      cancelAnimationFrame(animFrame);
      if (isReplyingRef.current) {
        return;
      }
      setProgressPercent(100);
      handleNext();
    };

    video.addEventListener('timeupdate', handleTimeUpdate);
    video.addEventListener('loadedmetadata', handleTimeUpdate);
    video.addEventListener('play', handlePlay);
    video.addEventListener('pause', handlePause);
    video.addEventListener('ended', handleEnded);

    if (!video.paused && !video.ended && !isStoryPaused) {
      animFrame = requestAnimationFrame(syncProgress);
    }

    return () => {
      cancelAnimationFrame(animFrame);
      video.removeEventListener('timeupdate', handleTimeUpdate);
      video.removeEventListener('loadedmetadata', handleTimeUpdate);
      video.removeEventListener('play', handlePlay);
      video.removeEventListener('pause', handlePause);
      video.removeEventListener('ended', handleEnded);
    };
  }, [isVideo, storyData, isStoryPaused, handleNext]);

  // Image / Text story progress timer (5000ms duration contract)
  useEffect(() => {
    if (isVideo || isStoryPaused) {
      if (progressTimerRef.current) {
        cancelAnimationFrame(progressTimerRef.current);
        progressTimerRef.current = null;
      }
      return;
    }

    const DURATION = 5000;
    startTimeRef.current = performance.now() - elapsedBeforePauseRef.current;

    const tick = (now) => {
      if (isReplyingRef.current) return;
      const elapsed = now - startTimeRef.current;
      elapsedBeforePauseRef.current = elapsed;
      const pct = Math.min(100, (elapsed / DURATION) * 100);
      setProgressPercent(pct);

      if (pct >= 100) {
        if (isReplyingRef.current) {
          return;
        }
        setProgressPercent(100);
        handleNext();
      } else {
        progressTimerRef.current = requestAnimationFrame(tick);
      }
    };

    progressTimerRef.current = requestAnimationFrame(tick);

    return () => {
      if (progressTimerRef.current) {
        cancelAnimationFrame(progressTimerRef.current);
        progressTimerRef.current = null;
      }
    };
  }, [isVideo, isStoryPaused, handleNext]);

  // Keyboard navigation & Escape handling
  useEffect(() => {
    const handleKeyDown = (e) => {
      const isInput =
        e.target &&
        (e.target.tagName === 'INPUT' ||
          e.target.tagName === 'TEXTAREA' ||
          e.target.isContentEditable);

      if (e.key === 'Escape') {
        if (isReplyingRef.current) {
          replyInputRef.current?.blur();
          setReplyingState(false);
          return;
        }
        if (isCaptionExpanded) {
          setIsCaptionExpanded(false);
          return;
        }
        if (pickerOpen) {
          clearPickerTimers();
          setPickerOpen(false);
          return;
        }
        if (optionsOpen) {
          setOptionsOpen(false);
          return;
        }
        if (headerMenuOpen) {
          setHeaderMenuOpen(false);
          return;
        }
        onClose();
      } else if (!isInput && !isReplyingRef.current && (e.key === 'ArrowRight' || e.key === ' ')) {
        e.preventDefault();
        handleNext();
      } else if (!isInput && !isReplyingRef.current && e.key === 'ArrowLeft') {
        e.preventDefault();
        handlePrev();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isCaptionExpanded, pickerOpen, optionsOpen, headerMenuOpen, handleNext, handlePrev, onClose, clearPickerTimers, setReplyingState]);

  // Outside click listener for options menu, reaction picker, and empty reply interaction
  useEffect(() => {
    const handleOutsideClick = (e) => {
      if ((headerMenuOpen || optionsOpen) && optionsMenuRef.current && !optionsMenuRef.current.contains(e.target)) {
        setHeaderMenuOpen(false);
        setOptionsOpen(false);
      }
      if (pickerOpen && likeWrapperRef.current && !likeWrapperRef.current.contains(e.target)) {
        clearPickerTimers();
        setPickerOpen(false);
        isTouchInteractionRef.current = false;
      }
      if (
        isReplyingRef.current &&
        replyFormRef.current &&
        !replyFormRef.current.contains(e.target) &&
        !replyText.trim()
      ) {
        replyInputRef.current?.blur();
        setReplyingState(false);
      }
    };
    document.addEventListener('mousedown', handleOutsideClick);
    document.addEventListener('touchstart', handleOutsideClick);
    return () => {
      document.removeEventListener('mousedown', handleOutsideClick);
      document.removeEventListener('touchstart', handleOutsideClick);
    };
  }, [headerMenuOpen, optionsOpen, pickerOpen, clearPickerTimers, replyText, setReplyingState]);

  // Story options
  const handleDeleteConfirm = async () => {
    if (!storyData?.story) return;
    setIsDeleting(true);
    try {
      await storyApi.deleteStory(storyData.story.id);
      setShowDeleteModal(false);
      if (onStoryDeleted) onStoryDeleted(storyData.story.id);
      onClose();
    } catch {
      // Error
    } finally {
      setIsDeleting(false);
    }
  };

  const handleCopyLink = () => {
    const storyUrl = `${window.location.origin}/member/stories/${storyData?.story?.id}`;
    if (navigator?.clipboard?.writeText) {
      navigator.clipboard.writeText(storyUrl).then(() => {
        setOptionsOpen(false);
        setHeaderMenuOpen(false);
        setReplyFeedback('Story link copied to clipboard!');
        setTimeout(() => setReplyFeedback(null), 2500);
      }).catch(() => {
        setOptionsOpen(false);
        setHeaderMenuOpen(false);
      });
    } else {
      setOptionsOpen(false);
      setHeaderMenuOpen(false);
    }
  };

  const handleToggleMute = useCallback((e) => {
    e?.stopPropagation();
    setIsMuted((prev) => {
      const next = !prev;
      if (videoRef.current) {
        videoRef.current.muted = next;
      }
      return next;
    });
    setHeaderMenuOpen(false);
  }, []);

  const handleMoreAction = (e) => {
    e?.stopPropagation();
    setOptionsOpen((prev) => !prev);
  };

  // Reaction Mouse & Touch Handlers
  const handleMouseEnterLike = () => {
    if (isTouchInteractionRef.current) return;
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
    if (pickerCloseTimerRef.current) clearTimeout(pickerCloseTimerRef.current);
    pickerCloseTimerRef.current = setTimeout(() => {
      setPickerOpen(false);
    }, 320);
  };

  const handleTouchStartLike = () => {
    isTouchInteractionRef.current = true;
    isLongPressRef.current = false;
    if (touchLongPressTimerRef.current) clearTimeout(touchLongPressTimerRef.current);
    touchLongPressTimerRef.current = setTimeout(() => {
      isLongPressRef.current = true;
      setPickerOpen((prev) => !prev);
    }, 300);
  };

  const handleTouchEndLike = (e) => {
    if (touchLongPressTimerRef.current) {
      clearTimeout(touchLongPressTimerRef.current);
      touchLongPressTimerRef.current = null;
    }
    if (isLongPressRef.current) {
      e.preventDefault();
      isLongPressRef.current = false;
    }
  };

  // Reactions Handler with concurrency lock and rollback
  const handleReact = async (reactionType) => {
    clearPickerTimers();
    setPickerOpen(false);
    if (!storyData?.story || isReactingRef.current) return;
    isReactingRef.current = true;

    const prevReaction = userReaction;
    const prevReactionsCount = reactionsCount;
    const prevTopReactions = topReactions;

    try {
      const response = await storyApi.reactToStory(storyData.story.id, reactionType);
      setUserReaction(response.user_reaction ?? null);
      setReactionsCount(Number(response.reactions_count) || 0);
      setTopReactions(Array.isArray(response.top_reactions) ? response.top_reactions : []);
    } catch (err) {
      setUserReaction(prevReaction);
      setReactionsCount(prevReactionsCount);
      setTopReactions(prevTopReactions);
      const msg = err.response?.data?.message || 'Failed to update reaction.';
      setReplyFeedback(msg);
      setTimeout(() => setReplyFeedback(null), 3000);
    } finally {
      isReactingRef.current = false;
    }
  };

  const handleLikeButtonClick = (e) => {
    if (e) e.stopPropagation();
    if (isLongPressRef.current) return;
    setPickerOpen((prev) => !prev);
  };

  // Replies
  const handleSendReply = async (e) => {
    e.preventDefault();
    if (!replyText.trim() || isSendingReply || !storyData?.story) return;
    setIsSendingReply(true);
    try {
      await storyApi.replyToStory(storyData.story.id, replyText.trim());
      setReplyText('');
      setReplyFeedback('Reply sent!');
      setReplyingState(false);
      replyInputRef.current?.blur();
      setTimeout(() => setReplyFeedback(null), 2500);
    } catch (err) {
      setReplyFeedback(err.response?.data?.message || 'Failed to send reply.');
      setTimeout(() => setReplyFeedback(null), 3000);
    } finally {
      setIsSendingReply(false);
    }
  };

  if (!storyId) return null;

  const story = storyData?.story;
  const author = story?.member;
  const isOwner = story?.member_id === currentMemberId;
  const isImage = story?.media_type === 'image';
  const mediaFolder = isVideo ? 'stories/videos' : 'stories/images';
  const mediaUrl = story?.media_path
    ? getMediaUrl(story.media_path, mediaFolder)
    : (story?.media_url ? getMediaUrl(story.media_url, mediaFolder) : null);
  const isTextOnly = story?.media_type === 'text' || (!mediaUrl && Boolean(story?.caption));
  const isLongCaption = Boolean(
    story?.caption &&
    (story.caption.length > CAPTION_THRESHOLD || story.caption.includes('\n'))
  );
  const normalizedReaction = typeof userReaction === 'string' ? userReaction.toLowerCase().trim() : null;
  const activeReactionConfig = normalizedReaction && REACTION_CONFIG[normalizedReaction] ? REACTION_CONFIG[normalizedReaction] : null;

  const authorStoryCount = storyData?.author_story_count || 1;
  const authorStoryIndex = storyData?.author_story_index || 0;

  if (typeof document === 'undefined') return null;
  const modalRoot = document.getElementById('modal-root') || document.body;

  return createPortal(
    <div
      className="story-viewer is-open"
      role="dialog"
      aria-modal="true"
      aria-labelledby="story-viewer-title"
    >
      <button
        className="story-viewer__backdrop"
        type="button"
        aria-label="Close Story"
        onClick={onClose}
      />

      {/* Desktop Navigation - Previous */}
      <button
        className="story-viewer__desktop-nav story-viewer__desktop-nav--prev"
        type="button"
        aria-label="Previous Story"
        title="Previous Story"
        disabled={!storyData?.previous_story_id}
        onMouseDown={(e) => e.stopPropagation()}
        onClick={(e) => {
          e.stopPropagation();
          handlePrev();
        }}
      >
        <ChevronLeft size={28} aria-hidden="true" />
      </button>

      <section
        className="story-viewer__panel"
        tabIndex={-1}
        onMouseDown={() => {
          if (!isReplyingRef.current) setIsPaused(true);
        }}
        onMouseUp={() => {
          if (!isReplyingRef.current) setIsPaused(false);
        }}
        onMouseLeave={() => {
          if (!isReplyingRef.current) setIsPaused(false);
        }}
        onTouchStart={() => {
          if (!isReplyingRef.current) setIsPaused(true);
        }}
        onTouchEnd={() => {
          if (!isReplyingRef.current) setIsPaused(false);
        }}
      >
        {/* Top Progress Bar Segments */}
        <div
          className="story-viewer__progress"
          aria-hidden="true"
          data-story-count={authorStoryCount}
          data-story-index={authorStoryIndex}
        >
          {Array.from({ length: authorStoryCount }).map((_, i) => {
            let width = '0%';
            if (i < authorStoryIndex) width = '100%';
            else if (i === authorStoryIndex) width = `${progressPercent}%`;

            return (
              <div className="story-viewer__progress-segment" key={i}>
                <div className="story-viewer__progress-fill" style={{ width }} />
              </div>
            );
          })}
        </div>

        {/* Blurred Background */}
        <div className="story-viewer__background" aria-hidden="true">
          {mediaUrl && (
            isImage ? (
              <img src={mediaUrl} alt="" />
            ) : (
              <video ref={bgVideoRef} src={mediaUrl} muted playsInline loop />
            )
          )}
          {isTextOnly && <div className="story-viewer__background--text" />}
        </div>
        <div className="story-viewer__shade" aria-hidden="true" />

        {/* Header */}
        <header className="story-viewer__header">
          <div className="story-viewer__author">
            <MemberAvatar member={author} size={38} />
            <div className="story-viewer__author-info">
              <div className="story-viewer__author-line">
                <h1 id="story-viewer-title" className="story-viewer__author-title">
                  <span className="story-viewer__author-name">{author?.name || 'Member'}</span>
                  <VerifiedBadge member={author} size={14} />
                </h1>
                {isOwner ? (
                  <span className={`story-viewer__owner ${story?.is_expired || story?.status === 'expired' ? 'story-viewer__owner--expired' : ''}`}>
                    {story?.is_expired || story?.status === 'expired' ? 'Expired Story' : 'Your Story'}
                  </span>
                ) : (
                  <span className="story-viewer__badge">Connection</span>
                )}
              </div>
              <time>{story ? formatRelativeTime(story.created_at) : ''}</time>
            </div>
          </div>

          <div className="story-viewer__top-actions">
            <div className="story-viewer__options-wrap" ref={optionsMenuRef}>
              <button
                className="story-viewer__options-btn story-viewer__more-btn"
                type="button"
                aria-label="Story options"
                aria-haspopup="true"
                aria-expanded={headerMenuOpen}
                onClick={(e) => {
                  e.stopPropagation();
                  setHeaderMenuOpen((prev) => !prev);
                }}
              >
                <MoreVertical size={20} aria-hidden="true" />
              </button>

              {headerMenuOpen && (
                <div
                  className="story-viewer__options-menu"
                  role="menu"
                  aria-label="Story actions"
                  style={{ display: 'block' }}
                  onClick={(e) => e.stopPropagation()}
                >
                  {/* Action 1: Mute / Unmute */}
                  <button
                    className="story-viewer__option-item story-viewer__option-sound"
                    type="button"
                    role="menuitem"
                    aria-label={isMuted ? 'Unmute video' : 'Mute video'}
                    data-action="action-1"
                    onClick={handleToggleMute}
                  >
                    {isMuted ? <Volume2 size={16} aria-hidden="true" /> : <VolumeX size={16} aria-hidden="true" />}
                    <span>{isMuted ? 'Unmute' : 'Mute'}</span>
                  </button>

                  {/* Action 2: Story Options / More */}
                  <button
                    className="story-viewer__option-item story-viewer__option-more"
                    type="button"
                    role="menuitem"
                    aria-label="Story options"
                    data-action="action-2"
                    onClick={handleMoreAction}
                  >
                    <Ellipsis size={16} aria-hidden="true" />
                    <span>Story Options</span>
                  </button>

                  {/* Submenu revealed when optionsOpen is true */}
                  {optionsOpen && (
                    <div className="story-viewer__options-subgroup" onClick={(e) => e.stopPropagation()}>
                      {isOwner && (
                        <button
                          className="story-viewer__option-item story-viewer__option-item--danger"
                          type="button"
                          role="menuitem"
                          onClick={() => {
                            setHeaderMenuOpen(false);
                            setOptionsOpen(false);
                            setShowDeleteModal(true);
                          }}
                        >
                          <Trash2 size={14} aria-hidden="true" />
                          <span>Delete Story</span>
                        </button>
                      )}
                      <button
                        className="story-viewer__option-item"
                        type="button"
                        role="menuitem"
                        onClick={() => {
                          handleCopyLink();
                          setHeaderMenuOpen(false);
                          setOptionsOpen(false);
                        }}
                      >
                        <LinkIcon size={14} aria-hidden="true" />
                        <span>Copy Story Link</span>
                      </button>
                    </div>
                  )}

                  {/* Action 3: Close Story */}
                  <button
                    className="story-viewer__option-item story-viewer__option-close"
                    type="button"
                    role="menuitem"
                    aria-label="Close Story"
                    data-action="action-3"
                    onClick={() => {
                      setHeaderMenuOpen(false);
                      setOptionsOpen(false);
                      onClose();
                    }}
                  >
                    <X size={16} aria-hidden="true" />
                    <span>Close Story</span>
                  </button>
                </div>
              )}
            </div>
          </div>
        </header>

        {/* Media Container */}
        <div className="story-viewer__media">
          {/* Previous & Next click zones */}
          <button
            className="story-viewer__nav story-viewer__nav--prev"
            type="button"
            aria-label="Previous Story"
            onClick={handlePrev}
            disabled={!storyData?.previous_story_id}
          />
          <button
            className="story-viewer__nav story-viewer__nav--next"
            type="button"
            aria-label="Next Story"
            onClick={handleNext}
          />

          {isLoading ? (
            <div className="story-viewer__loading" role="status" aria-label="Loading Story">
              <div className="story-viewer__skeleton" aria-hidden="true" />
            </div>
          ) : error ? (
            <div className="story-viewer__unavailable" role="alert">
              <ImageOff size={32} aria-hidden="true" />
              <span>{error}</span>
            </div>
          ) : mediaUrl && isImage ? (
            <img
              className="story-viewer__media-content"
              src={mediaUrl}
              alt={`${author?.name || 'Author'}'s Story`}
            />
          ) : mediaUrl && isVideo ? (
            <video
              ref={videoRef}
              className="story-viewer__media-content"
              src={mediaUrl}
              autoPlay
              muted={isMuted}
              playsInline
              preload="auto"
              aria-label={`${author?.name || 'Author'}'s Story video`}
            />
          ) : isTextOnly ? (
            <div className="story-viewer__text-canvas">
              <div className="story-viewer__text-canvas-inner">
                <p className="story-viewer__text-content">{story.caption}</p>
              </div>
            </div>
          ) : (
            <div className="story-viewer__unavailable" role="alert">
              <ImageOff size={32} aria-hidden="true" />
              <span>This Story could not be loaded.</span>
            </div>
          )}
        </div>

        {/* Bottom Container: Caption + Footer Actions */}
        <div className="story-viewer__bottom-container">
          {/* Bounded Caption Card */}
          {story?.caption && !isTextOnly && (
            <div
              className={`story-viewer__caption-card ${
                isLongCaption
                  ? isCaptionExpanded
                    ? 'is-expanded'
                    : 'is-collapsed'
                  : 'is-short'
              }`}
              onMouseEnter={() => {
                if (!isCaptionExpanded) setIsPaused(true);
              }}
              onMouseLeave={() => {
                if (!isCaptionExpanded) setIsPaused(false);
              }}
              onMouseDown={(e) => e.stopPropagation()}
              onTouchStart={(e) => e.stopPropagation()}
              onClick={(e) => {
                e.stopPropagation();
                if (isLongCaption && !isCaptionExpanded) {
                  setIsCaptionExpanded(true);
                }
              }}
              role="region"
              aria-label="Story caption"
            >
              {isLongCaption && isCaptionExpanded && (
                <div className="story-viewer__caption-header">
                  <span className="story-viewer__caption-title">Story Caption</span>
                  <button
                    type="button"
                    className="story-viewer__caption-collapse-btn"
                    onClick={(e) => {
                      e.stopPropagation();
                      setIsCaptionExpanded(false);
                    }}
                    aria-label="Collapse caption"
                  >
                    <ChevronDown size={14} aria-hidden="true" />
                    <span>Show less</span>
                  </button>
                </div>
              )}

              <div className="story-viewer__caption-body">
                {isLongCaption && !isCaptionExpanded ? (
                  <p className="story-viewer__caption story-viewer__caption--preview">
                    <span>{getCaptionPreview(story.caption)}... </span>
                    <button
                      type="button"
                      className="story-viewer__caption-more-btn"
                      onClick={(e) => {
                        e.stopPropagation();
                        setIsCaptionExpanded(true);
                      }}
                      aria-label="See full caption"
                    >
                      See more
                    </button>
                  </p>
                ) : (
                  <p className="story-viewer__caption story-viewer__caption--full">
                    {story.caption}
                  </p>
                )}
              </div>

              {isLongCaption && isCaptionExpanded && (
                <div className="story-viewer__caption-footer">
                  <button
                    type="button"
                    className="story-viewer__caption-collapse-btn story-viewer__caption-collapse-btn--bottom"
                    onClick={(e) => {
                      e.stopPropagation();
                      setIsCaptionExpanded(false);
                    }}
                    aria-label="Collapse caption"
                  >
                    <ChevronUp size={14} aria-hidden="true" />
                    <span>Show less</span>
                  </button>
                </div>
              )}
            </div>
          )}

          {/* Footer Actions */}
          <div className="story-viewer__footer">
            <div className="story-viewer__footer-actions">
              {isOwner ? (
                <>
                  {/* 1. Seen By Button */}
                  <button
                    className="story-viewer__seen-by"
                    type="button"
                    aria-label={`Seen by ${viewsCount}`}
                    onMouseDown={(e) => e.stopPropagation()}
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowViewersModal(true);
                    }}
                  >
                    <Eye size={15} aria-hidden="true" />
                    <span>Seen by {viewsCount}</span>
                  </button>

                  {/* 2. Reaction Summary Button */}
                  <button
                    className="story-viewer__react-summary story-viewer__react-summary--owner"
                    type="button"
                    aria-label="View reactions"
                    onMouseDown={(e) => e.stopPropagation()}
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowReactorsModal(true);
                    }}
                  >
                    <span className="story-viewer__react-emojis">
                      {topReactions.length > 0 ? (
                        topReactions.map((emoji, idx) => <i key={idx}>{emoji}</i>)
                      ) : (
                        <i>👍</i>
                      )}
                    </span>
                    <span>{reactionsCount}</span>
                  </button>

                  {/* 3. Replies Button */}
                  <button
                    className="story-viewer__replies-btn"
                    type="button"
                    aria-label="View story replies"
                    onMouseDown={(e) => e.stopPropagation()}
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowRepliesModal(true);
                    }}
                  >
                    <MessageSquare size={15} aria-hidden="true" />
                    <span>Replies</span>
                    <span className="story-viewer__replies-count">{repliesCount}</span>
                    {unreadRepliesCount > 0 && (
                      <span className="story-viewer__unread-badge">{unreadRepliesCount}</span>
                    )}
                  </button>
                </>
              ) : (
                <>
                  {/* 1. Reply Form */}
                  <form
                    ref={replyFormRef}
                    className="story-viewer__reply-form"
                    onSubmit={handleSendReply}
                    onMouseDown={(e) => e.stopPropagation()}
                    onMouseUp={(e) => e.stopPropagation()}
                    onTouchStart={(e) => e.stopPropagation()}
                    onTouchEnd={(e) => e.stopPropagation()}
                    onClick={(e) => e.stopPropagation()}
                  >
                    <input
                      ref={replyInputRef}
                      className="story-viewer__reply-input"
                      type="text"
                      name="message"
                      placeholder={`Reply to ${author?.name || 'Author'}...`}
                      maxLength={500}
                      required
                      autoComplete="off"
                      value={replyText}
                      onFocus={() => setReplyingState(true)}
                      onChange={(e) => {
                        setReplyText(e.target.value);
                        if (!isReplyingRef.current) setReplyingState(true);
                      }}
                      onMouseDown={(e) => {
                        e.stopPropagation();
                        setReplyingState(true);
                      }}
                      onMouseUp={(e) => e.stopPropagation()}
                      onTouchStart={(e) => {
                        e.stopPropagation();
                        setReplyingState(true);
                      }}
                      onTouchEnd={(e) => e.stopPropagation()}
                      onClick={(e) => {
                        e.stopPropagation();
                        setReplyingState(true);
                      }}
                      onKeyDown={(e) => {
                        e.stopPropagation();
                        if (e.key === 'Escape') {
                          replyInputRef.current?.blur();
                          setReplyingState(false);
                        }
                      }}
                    />
                    <button
                      className="story-viewer__reply-submit"
                      type="submit"
                      aria-label="Send reply"
                      disabled={!replyText.trim() || isSendingReply}
                      onMouseDown={(e) => e.stopPropagation()}
                      onMouseUp={(e) => e.stopPropagation()}
                      onTouchStart={(e) => e.stopPropagation()}
                      onTouchEnd={(e) => e.stopPropagation()}
                      onClick={(e) => e.stopPropagation()}
                    >
                      <Send size={15} aria-hidden="true" />
                    </button>
                  </form>

                  {/* 2. Like / Reaction Button & Floating Picker */}
                  <div
                    ref={likeWrapperRef}
                    className="story-viewer__like-wrapper"
                    onMouseEnter={handleMouseEnterLike}
                    onMouseLeave={handleMouseLeaveLike}
                    onMouseDown={(e) => e.stopPropagation()}
                  >
                    {pickerOpen && (
                      <div
                        className="story-reaction-picker"
                        role="toolbar"
                        aria-label="Choose a reaction"
                        onMouseEnter={handleMouseEnterLike}
                        onMouseLeave={handleMouseLeaveLike}
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={(e) => e.stopPropagation()}
                      >
                        {Object.entries(REACTION_CONFIG).map(([key, item]) => (
                          <button
                            key={key}
                            className={`story-reaction-picker__btn ${normalizedReaction === key ? 'is-active' : ''}`}
                            type="button"
                            title={item.label}
                            aria-label={`React ${item.label}`}
                            onClick={(e) => {
                              e.stopPropagation();
                              handleReact(key);
                            }}
                          >
                            <span>{item.emoji}</span>
                          </button>
                        ))}
                      </div>
                    )}

                    <button
                      className={`story-viewer__like-btn ${activeReactionConfig ? 'is-active' : ''}`}
                      type="button"
                      aria-label={activeReactionConfig ? `Reacted ${activeReactionConfig.label}` : 'Like Story'}
                      style={
                        activeReactionConfig
                          ? {
                              borderColor: `${activeReactionConfig.color}66`,
                              boxShadow: `0 0 10px ${activeReactionConfig.color}33`,
                            }
                          : {}
                      }
                      onTouchStart={handleTouchStartLike}
                      onTouchEnd={handleTouchEndLike}
                      onClick={handleLikeButtonClick}
                    >
                      <span className="story-viewer__like-icon">
                        {activeReactionConfig ? (
                          activeReactionConfig.emoji
                        ) : (
                          <ThumbsUp size={16} aria-hidden="true" />
                        )}
                      </span>
                      <span className="story-viewer__like-label">
                        {activeReactionConfig ? activeReactionConfig.label : 'Like'}
                      </span>
                    </button>
                  </div>
                </>
              )}
            </div>

            {replyFeedback && (
              <div
                className="story-viewer__toast"
                role="status"
                aria-live="polite"
                style={{
                  background: 'rgba(0,0,0,0.85)',
                  color: '#fff',
                  padding: '6px 14px',
                  borderRadius: '8px',
                  marginTop: '8px',
                  textAlign: 'center',
                  fontSize: '0.85rem',
                  backdropFilter: 'blur(8px)',
                }}
              >
                {replyFeedback}
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Desktop Navigation - Next */}
      <button
        className="story-viewer__desktop-nav story-viewer__desktop-nav--next"
        type="button"
        aria-label="Next Story"
        title="Next Story"
        onMouseDown={(e) => e.stopPropagation()}
        onClick={(e) => {
          e.stopPropagation();
          handleNext();
        }}
      >
        <ChevronRight size={28} aria-hidden="true" />
      </button>

      {/* Modals */}
      {showViewersModal && (
        <StoryViewersModal
          isOpen={showViewersModal}
          onClose={() => setShowViewersModal(false)}
          storyId={story?.id}
        />
      )}

      {showReactorsModal && (
        <StoryReactorsModal
          isOpen={showReactorsModal}
          onClose={() => setShowReactorsModal(false)}
          storyId={story?.id}
        />
      )}

      {showRepliesModal && (
        <StoryRepliesModal
          isOpen={showRepliesModal}
          onClose={() => setShowRepliesModal(false)}
          storyId={story?.id}
          currentMemberId={currentMemberId}
        />
      )}

      {showDeleteModal && (
        <DeleteConfirmModal
          isOpen={showDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onConfirm={handleDeleteConfirm}
          title="Delete Story?"
          message="Are you sure you want to permanently delete this story? This action cannot be undone."
          isDeleting={isDeleting}
          depth={1}
        />
      )}
    </div>,
    modalRoot
  );
}

export default StoryViewer;
