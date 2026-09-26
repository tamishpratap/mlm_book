import { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  Ellipsis,
  Pin,
  Bookmark,
  Flag,
  Trash2,
  ThumbsUp,
  MessageCircle,
  Share2,
  Repeat,
  SendHorizontal,
  BadgeCheck,
  Lock,
  ExternalLink,
  UsersRound,
  Building2,
  Megaphone,
  Sparkles,
  Check,
  Calendar,
  ArrowRight,
  MapPin,
  Tag,
  Gift,
  Star,
} from 'lucide-react';
import CommentItem from './CommentItem';
import ReactorsModal from './modals/ReactorsModal';
import SharersModal from './modals/SharersModal';
import ShareModal from './modals/ShareModal';
import ReportModal from './modals/ReportModal';
import DeleteConfirmModal from './modals/DeleteConfirmModal';
import AdRewardPreviewModal from './modals/AdRewardPreviewModal';
import PaidEventQualificationModal from '../events/PaidEventQualificationModal';
import AccountVerificationModal from '../verification/AccountVerificationModal';
import postApi from '../../api/postApi';
import communityApi from '../../api/communityApi';
import { useAuth } from '../../hooks/useAuth';
import { getAvatarUrl, getMediaUrl } from '../../utils/assetHelper';
import VerifiedBadge from '../common/VerifiedBadge';
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

function formatRewardAmount(val) {
  if (val === undefined || val === null || val === '') return null;
  if (typeof val === 'string' && val.startsWith('$')) return val;
  const num = Number(val);
  if (Number.isNaN(num)) return null;
  return `$${num.toFixed(4)}`;
}

/**
 * PostFeedVideo
 * Standard HTML5 video player for feed posts with automatic scroll-away pause
 * and background audio prevention.
 */
function PostFeedVideo({
  src,
  className = 'post-video',
  controls = true,
  playsInline = true,
  preload = 'metadata',
  ...props
}) {
  const videoRef = useRef(null);

  useEffect(() => {
    const videoEl = videoRef.current;
    if (!videoEl || !('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          // Bypassed if currently in fullscreen mode
          const isFullscreen =
            document.fullscreenElement === videoEl ||
            document.webkitFullscreenElement === videoEl;
          if (isFullscreen) return;

          // When video leaves visible viewport (< 20% visible), pause playback & stop audio
          if (!entry.isIntersecting || entry.intersectionRatio < 0.2) {
            if (!videoEl.paused) {
              videoEl.pause();
            }
          }
        });
      },
      { threshold: [0, 0.2] }
    );

    observer.observe(videoEl);

    // Pause when browser tab becomes hidden
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'hidden') {
        const currentVideo = videoRef.current;
        if (currentVideo && !currentVideo.paused) {
          currentVideo.pause();
        }
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      observer.disconnect();
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      // Clean up playback on unmount / route navigation
      if (videoEl && !videoEl.paused) {
        videoEl.pause();
      }
    };
  }, []);

  return (
    <video
      ref={videoRef}
      src={src}
      className={className}
      controls={controls}
      playsInline={playsInline}
      preload={preload}
      {...props}
    />
  );
}

export function PostCard({
  post,
  currentUser: propCurrentUser,
  onPostDeleted,
  onPostHidden,
  onPostUpdated,
  onRunAd,
  communitySlug,
  isCommunityAdmin = false,
}) {
  void onPostHidden;
  const { user: authUser } = useAuth();
  const currentUser = propCurrentUser || authUser;
  const currentMemberId = currentUser?.id;
  const isAuthor = post.member_id === currentMemberId;
  const bizPage = post.business_page || post.businessPage || post.ad_campaign?.business_page || post.ad_campaign?.businessPage;
  const isBizAdmin = bizPage ? Boolean(bizPage.is_admin || bizPage.admin_id === currentMemberId) : false;
  const isOwner = isAuthor || isBizAdmin;
  const author = post.member;
  const authorName = post.event?.organizer?.name || (bizPage ? bizPage.page_name : author?.name || 'Member');
  const isSharedPost = Boolean(post.original_post_id);
  const originalPost = post.original_post || post.originalPost;
  const shareTargetPost = isSharedPost && originalPost ? originalPost : post;

  // Local interaction states
  const [isPinned, setIsPinned] = useState(Boolean(post.is_pinned));
  const [isAnnouncement, setIsAnnouncement] = useState(Boolean(post.is_announcement));
  const [isSaved, setIsSaved] = useState(Boolean(post.is_saved));
  const [savesCount, setSavesCount] = useState(post.saved_posts_count || (post.is_saved ? 1 : 0));
  const [userReaction, setUserReaction] = useState(post.user_reaction || (post.has_liked ? 'like' : null));
  const [reactionsCount, setReactionsCount] = useState(
    post.reactions_count !== undefined ? post.reactions_count : (post.likes_count || 0)
  );
  const [sharesCount, setSharesCount] = useState(
    shareTargetPost?.shares_count !== undefined
      ? Number(shareTargetPost.shares_count)
      : (post.shares_count !== undefined ? Number(post.shares_count) : 0)
  );

  useEffect(() => {
    const nextCount = shareTargetPost?.shares_count !== undefined
      ? Number(shareTargetPost.shares_count)
      : (post.shares_count !== undefined ? Number(post.shares_count) : 0);
    setSharesCount(nextCount);
  }, [shareTargetPost?.shares_count, post.shares_count]);

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

  // Avatar error states
  const [authorAvatarError, setAuthorAvatarError] = useState(false);
  const [origAvatarError, setOrigAvatarError] = useState(false);
  const [userAvatarError, setUserAvatarError] = useState(false);

  // Sponsored Ad / Event Detection & Deduplicated Impression Tracking
  const navigate = useNavigate();
  const isVerifiedMember = isMemberMobileVerified(currentUser);
  const contentType = post.content_type ||
    (post.type === 'PAID_EVENT' || post.type === 'sponsored_event' || (post.is_sponsored && (post.event || post.event_id)) ? 'PAID_EVENT' :
     post.content_type === 'PAID_AD' || post.type === 'PAID_AD' || post.type === 'sponsored_ad' || post.is_sponsored || post.ad_campaign ? 'PAID_AD' :
     'ORGANIC_POST');
  const isEventSponsored = Boolean(
    contentType === 'PAID_EVENT' || post.type === 'sponsored_event' || (post.is_sponsored && (post.event || post.event_id))
  );
  const isAdSponsored = Boolean(
    !isEventSponsored && (contentType === 'PAID_AD' || post.type === 'sponsored_ad' || post.is_sponsored || post.ad_campaign)
  );
  const isSponsored = isAdSponsored;
  const eventData = post.event || post.event_data || post.ad_campaign?.event;
  const eventCampaign = post.ad_campaign || post.campaign || eventData?.campaign || eventData?.campaign_details;
  const earnUpToAmount = eventCampaign?.earn_up_to_formatted ||
    post?.earn_up_to_formatted ||
    eventData?.earn_up_to_formatted ||
    formatRewardAmount(eventCampaign?.earn_up_to_usd) ||
    formatRewardAmount(eventCampaign?.reward_amount_usd) ||
    formatRewardAmount(post?.earn_up_to_usd) ||
    formatRewardAmount(eventData?.earn_up_to_usd) ||
    formatRewardAmount(eventData?.reward_amount_usd) ||
    '';
  const adCampaignData = post.ad_campaign || post.campaign;
  const adEarnUpToAmount = adCampaignData?.earn_up_to_formatted ||
    post?.earn_up_to_formatted ||
    (adCampaignData?.earn_up_to_usd ? `$${Number(adCampaignData.earn_up_to_usd).toFixed(4)}` :
    (adCampaignData?.reward_amount_usd ? `$${Number(adCampaignData.reward_amount_usd).toFixed(4)}` : '$0.0250'));

  const isCampaignOwner = Boolean(
    isOwner ||
    post.is_owner ||
    post.ad_campaign?.is_owner ||
    post.campaign?.is_owner ||
    eventCampaign?.is_owner ||
    adCampaignData?.is_owner ||
    (currentMemberId && (
      (post.member_id && post.member_id === currentMemberId) ||
      (post.ad_campaign?.member_id && post.ad_campaign.member_id === currentMemberId) ||
      (post.campaign?.member_id && post.campaign.member_id === currentMemberId) ||
      (bizPage && (bizPage.member_id === currentMemberId || bizPage.is_owner)) ||
      (eventData && (eventData.organizer_id === currentMemberId || eventData.is_organizer || eventData.organizer?.id === currentMemberId))
    ))
  );

  const isRewardEligibleEvent = Boolean(
    eventCampaign &&
    !isCampaignOwner &&
    (post.is_sponsored || eventCampaign.is_eligible || (eventCampaign.remaining_amount !== undefined && Number(eventCampaign.remaining_amount) > 0) || (eventCampaign.budget !== undefined && Number(eventCampaign.budget) > 0))
  );

  const hasText = Boolean(post.body && post.body.trim().length > 0 && !isEventSponsored);
  const mediaUrl = post.media_path || post.media_url || post.media;
  const hasMedia = Boolean(!isSharedPost && !isEventSponsored && mediaUrl);
  const isVideo = Boolean(hasMedia && (
    post.media_type === 'video' ||
    (typeof mediaUrl === 'string' && mediaUrl.match(/\.(mp4|webm|mov)(\?.*)?$/i))
  ));

  const impressionTrackedRef = useRef(false);

  useEffect(() => {
    if (isAdSponsored && isVerifiedMember && (post.ad_campaign_id || post.ad_campaign?.campaign_id) && !impressionTrackedRef.current) {
      impressionTrackedRef.current = true;
      const cId = post.ad_campaign_id || post.ad_campaign?.campaign_id;
      const impressionKey = `imp_${cId}_${post.id}_${currentMemberId || 'guest'}`;
      postApi.recordAdImpression(cId, impressionKey, 'social_feed').catch(() => {});
    }
  }, [isAdSponsored, isVerifiedMember, post.ad_campaign_id, post.ad_campaign, post.id, currentMemberId]);

  const handleAdClick = (e) => {
    if (
      e.target.closest('.global-modal-overlay') ||
      e.target.closest('[role="dialog"]') ||
      e.target.closest('.modal-dialog-custom') ||
      e.target.closest('.post-options-menu') ||
      e.target.closest('.post-card-actions') ||
      e.target.closest('.comment-section') ||
      e.target.closest('.post-header__options') ||
      e.target.closest('.sponsored-cta-banner') ||
      e.target.closest('.paid-event-card__earn-row') ||
      e.target.closest('.paid-event-card__footer') ||
      e.target.closest('a') ||
      e.target.closest('button')
    ) {
      return;
    }

    if (isEventSponsored && eventData) {
      navigate(`/member/events/${eventData.id}`);
      return;
    }

    if (isSponsored && isVerifiedMember && (post.ad_campaign_id || post.ad_campaign?.campaign_id)) {
      const cId = post.ad_campaign_id || post.ad_campaign?.campaign_id;
      const clickKey = `clk_${cId}_${post.id}_${currentMemberId || 'guest'}_${Date.now()}`;
      postApi.recordAdClick(cId, clickKey, 'social_feed').catch(() => {});
    }
  };

  const [showRewardPreviewModal, setShowRewardPreviewModal] = useState(false);
  const [showEventQualificationModal, setShowEventQualificationModal] = useState(false);
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [verifyMessage, setVerifyMessage] = useState('Please verify your phone number first before proceeding.');
  const isAlreadyRewardedEvent = Boolean(
    eventCampaign?.already_rewarded ||
    eventData?.already_rewarded ||
    eventData?.campaign?.already_rewarded ||
    post.already_rewarded
  );
  const [isEventRewarded, setIsEventRewarded] = useState(isAlreadyRewardedEvent);

  useEffect(() => {
    setIsEventRewarded(Boolean(
      eventCampaign?.already_rewarded ||
      eventData?.already_rewarded ||
      eventData?.campaign?.already_rewarded ||
      post.already_rewarded
    ));
  }, [
    eventCampaign?.already_rewarded,
    eventData?.already_rewarded,
    eventData?.campaign?.already_rewarded,
    post.already_rewarded,
  ]);

  const handleEarnClick = (e) => {
    if (e) {
      e.preventDefault?.();
      e.stopPropagation?.();
    }
    const eventId = eventData?.id || post.event_id;

    if (isCampaignOwner) {
      if (eventId) {
        navigate(`/member/events/${eventId}`);
      }
      return;
    }

    if (isEventRewarded || isAlreadyRewardedEvent) return;

    if (!isVerifiedMember) {
      setVerifyMessage('Please verify your phone number first before proceeding.');
      setShowVerifyModal(true);
      return;
    }

    setShowEventQualificationModal(true);
  };

  const handleInterestedClick = (e) => {
    e.stopPropagation();
    if (!isSponsored && !isRewardEligibleEvent) return;

    if (isCampaignOwner) {
      if (isEventSponsored) {
        if (eventData?.id || post.event_id) {
          navigate(`/member/events/${eventData?.id || post.event_id}`);
        }
      } else if (bizPage?.slug || bizPage?.id) {
        navigate(`/member/business-pages/${bizPage.slug || bizPage.id}?tab=ads`);
      }
      return;
    }

    if (isEventSponsored) {
      handleEarnClick(e);
      return;
    }

    if (!isVerifiedMember) {
      setVerifyMessage('Please verify your phone number first before proceeding.');
      setShowVerifyModal(true);
      return;
    }

    if (post.ad_campaign?.already_rewarded) return;
    setShowRewardPreviewModal(true);
  };

  const handleContinueToRewardTarget = async (consentData = { consent_accepted: true }) => {
    const cId = post.ad_campaign_id || post.ad_campaign?.campaign_id;
    if (!cId) return;

    if (!consentData?.consent_accepted) {
      return;
    }

    await postApi.recordAdInterest(cId, { consent_accepted: true });
    setShowRewardPreviewModal(false);

    const targetSlug = bizPage?.slug || bizPage?.id;
    if (targetSlug) {
      navigate(`/member/business-pages/${targetSlug}?campaign=${cId}&action=landing_reward`);
    }
  };
  const [showSharersModal, setShowSharersModal] = useState(false);
  const [sharersList, setSharersList] = useState([]);
  const [isLoadingSharers, setIsLoadingSharers] = useState(false);
  const [showShareModal, setShowShareModal] = useState(false);
  const [showReportModal, setShowReportModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  // Comments state - standardized to only 4 newest comments initially
  const [comments, setComments] = useState(() => {
    if (Array.isArray(post.comments)) {
      return post.comments.slice(-4);
    }
    return [];
  });
  const [commentsCount, setCommentsCount] = useState(
    post.comments_count !== undefined ? post.comments_count : (post.comments ? post.comments.length : 0)
  );
  const [newCommentText, setNewCommentText] = useState('');
  const [isSubmittingComment, setIsSubmittingComment] = useState(false);
  const [hasMoreComments, setHasMoreComments] = useState(() => {
    const total = post.comments_count !== undefined ? post.comments_count : (post.comments ? post.comments.length : 0);
    const initialLen = Array.isArray(post.comments) ? Math.min(post.comments.length, 4) : 0;
    return total > initialLen;
  });
  const [isLoadingMoreComments, setIsLoadingMoreComments] = useState(false);

  useEffect(() => {
    if (Array.isArray(post.comments)) {
      setComments(post.comments.slice(-4));
    }
    const count = typeof post.comments_count !== 'undefined'
      ? post.comments_count
      : (post.comments ? post.comments.length : 0);
    setCommentsCount(count);
    const initialLen = Array.isArray(post.comments) ? Math.min(post.comments.length, 4) : 0;
    setHasMoreComments(count > initialLen);
  }, [post.comments, post.comments_count]);

  // Fetch initial 4 newest comments if not preloaded in feed payload but comments exist
  useEffect(() => {
    let isCancelled = false;
    if (post.id && (!post.comments || post.comments.length === 0) && (post.comments_count || 0) > 0) {
      setIsLoadingMoreComments(true);
      postApi.getComments(post.id, 0, 4)
        .then((data) => {
          if (!isCancelled && data && Array.isArray(data.comments)) {
            setComments(data.comments);
            setHasMoreComments(data.has_more);
            if (typeof data.comments_count !== 'undefined') {
              setCommentsCount(data.comments_count);
            }
          }
        })
        .catch(() => {})
        .finally(() => {
          if (!isCancelled) setIsLoadingMoreComments(false);
        });
    }
    return () => {
      isCancelled = true;
    };
  }, [post.id]);

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

  // Post Options Handlers
  const handleTogglePin = async () => {
    setOptionsOpen(false);
    if (!communitySlug) return;
    try {
      const response = await communityApi.togglePinPost(communitySlug, post.id);
      setIsPinned(response.is_pinned);
      if (onPostUpdated) onPostUpdated({ ...post, is_pinned: response.is_pinned });
    } catch {
      // Revert
    }
  };

  const handleToggleAnnouncement = async () => {
    setOptionsOpen(false);
    if (!communitySlug) return;
    try {
      const response = await communityApi.toggleAnnouncementPost(communitySlug, post.id);
      setIsAnnouncement(response.is_announcement);
      if (onPostUpdated) onPostUpdated({ ...post, is_announcement: response.is_announcement });
    } catch {
      // Revert
    }
  };

  const handleToggleSave = async () => {
    setOptionsOpen(false);
    try {
      const response = await postApi.toggleSave(post.id);
      setIsSaved(response.is_saved);
      setSavesCount(response.saves_count);
      if (onPostUpdated) {
        onPostUpdated({
          ...post,
          is_saved: response.is_saved,
          saved_posts_count: response.saves_count,
        });
      }
    } catch {
      // Revert
    }
  };

  /*
  // Hide Post action removed from UI per requirement
  const handleHidePost = async () => {
    setOptionsOpen(false);
    try {
      await postApi.hidePost(post.id);
      if (onPostHidden) onPostHidden(post.id);
    } catch {
      // Revert
    }
  };
  */

  const handleDeletePost = async () => {
    setIsDeleting(true);
    try {
      if (communitySlug) {
        await communityApi.deleteCommunityPost(communitySlug, post.id);
      } else {
        await postApi.deletePost(post.id);
      }
      setShowDeleteModal(false);
      if (onPostDeleted) onPostDeleted(post.id);
    } catch {
      // Error
    } finally {
      setIsDeleting(false);
    }
  };

  // Reactions Handlers with Optimistic UI updates
  const handleMouseEnterLike = () => {
    if (isTouchInteractionRef.current) return;
    if (justInteractedRef.current) return;

    // Pointer entered wrapper, trigger, or picker: cancel any pending close
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

    // Graceful close delay so pointer can transition between trigger and picker without flickering
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
      setVerifyMessage('Please verify your mobile number through WhatsApp before reacting to or liking posts.');
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
      ? prevCount // Reaction switch keeps total count unchanged
      : prevCount + 1; // New reaction increments count

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
        // Rollback on server rejection
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

  const handleOpenSharers = async () => {
    setShowSharersModal(true);
    setIsLoadingSharers(true);
    try {
      const data = await postApi.getSharers(shareTargetPost.id);
      if (data && data.sharers) {
        setSharersList(data.sharers);
      }
      if (data && typeof data.shares_count !== 'undefined') {
        setSharesCount(Number(data.shares_count));
      }
    } catch {
      // Fallback
    } finally {
      setIsLoadingSharers(false);
    }
  };

  // Comments Handlers
  const handleLoadMoreComments = async () => {
    if (isLoadingMoreComments) return;
    setIsLoadingMoreComments(true);
    try {
      const data = await postApi.getComments(post.id, comments.length, 4);
      if (data && Array.isArray(data.comments)) {
        setComments((prev) => {
          const existingIds = new Set(prev.map((c) => c.id));
          const newComments = data.comments.filter((c) => !existingIds.has(c.id));
          return [...newComments, ...prev];
        });
        setHasMoreComments(data.has_more);
        if (typeof data.comments_count !== 'undefined') {
          setCommentsCount(data.comments_count);
        }
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
        setComments((prev) => {
          if (prev.some((c) => c.id === data.comment.id)) return prev;
          return [...prev, data.comment];
        });
      } else {
        const newCommentObj = {
          id: data.comment_id || Date.now(),
          post_id: post.id,
          member_id: currentMemberId,
          member: currentUser,
          comment: newCommentText.trim(),
          created_at: new Date().toISOString(),
          replies: [],
          reactions_count: 0,
        };
        setComments((prev) => [...prev, newCommentObj]);
      }
      const nextCommentsCount = (commentsCount || 0) + 1;
      setCommentsCount(nextCommentsCount);
      setNewCommentText('');
      if (onPostUpdated) {
        onPostUpdated({
          ...post,
          comments_count: nextCommentsCount,
        });
      }
    } catch {
      // Error
    } finally {
      setIsSubmittingComment(false);
    }
  };

  const handleDeleteComment = async (commentId) => {
    try {
      await postApi.deleteComment(commentId);
      setComments((prev) => prev.filter((c) => c.id !== commentId));
      const nextCommentsCount = Math.max(0, (commentsCount || 1) - 1);
      setCommentsCount(nextCommentsCount);
      if (onPostUpdated) {
        onPostUpdated({
          ...post,
          comments_count: nextCommentsCount,
        });
      }
    } catch {
      // Error
    }
  };

  // Author & media details
  const photoUrl = isEventSponsored && eventData?.organizer?.profile_photo
    ? getAvatarUrl(eventData.organizer.profile_photo)
    : bizPage
    ? (bizPage.logo ? getAvatarUrl(bizPage.logo) : (bizPage.logo_url ? getAvatarUrl(bizPage.logo_url) : null))
    : author?.profile_photo
    ? getAvatarUrl(author.profile_photo)
    : null;
  const userPhotoUrl = currentUser?.profile_photo ? getAvatarUrl(currentUser.profile_photo) : null;
  const activeReactionData = userReaction ? REACTION_CONFIG[userReaction] : null;

  // Shared original author details
  const origBiz = originalPost?.business_page;
  const origAuthor = originalPost?.member;
  const origName = origBiz ? origBiz.page_name : origAuthor?.name || 'Member';
  const origPhotoUrl = origBiz
    ? (origBiz.logo ? getAvatarUrl(origBiz.logo) : (origBiz.logo_url ? getAvatarUrl(origBiz.logo_url) : null))
    : origAuthor?.profile_photo
    ? getAvatarUrl(origAuthor.profile_photo)
    : null;
  // Event module temporarily disabled: hide event posts from feed
  if (isEventSponsored || post.type === 'event' || post.event_id || post.event) {
    return null;
  }

  return (
    <article
      className={`card feed-post ${isSharedPost ? 'feed-post--shared' : ''} ${post.is_suggested ? 'feed-post--suggested' : ''} ${isSponsored ? 'feed-post--sponsored' : ''}`}
      data-post-id={post.id}
      onClick={handleAdClick}
    >
      {/* Header */}
      <header className="post-header">
        {photoUrl && !authorAvatarError ? (
          <img
            className="avatar"
            src={photoUrl}
            alt={authorName}
            loading="lazy"
            onError={() => setAuthorAvatarError(true)}
          />
        ) : (
          <span
            className="avatar post-avatar-initials"
            role="img"
            aria-label={`${authorName} initials`}
          >
            {getInitials(authorName)}
          </span>
        )}

        <div className="post-header__meta">
          <div>
            {bizPage ? (
              <Link
                to={`/member/business-pages/${bizPage.slug || bizPage.id}`}
                style={{ color: 'inherit', textDecoration: 'none' }}
              >
                <strong>{bizPage.page_name}</strong>
              </Link>
            ) : (
              <Link
                to={`/member/people/${author?.id}`}
                style={{ color: 'inherit', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}
              >
                <strong>{author?.name || 'Member'}</strong>
                {!bizPage && <VerifiedBadge member={author} size={15} />}
              </Link>
            )}

            {bizPage?.is_verified && (
              <BadgeCheck
                size={15}
                color="#20c875"
                style={{ verticalAlign: 'middle', marginLeft: '3px' }}
                aria-label="Verified Business"
              />
            )}

            {isPinned && (
              <span className="post-header__pinned-tag">
                · <Pin size={12} aria-hidden="true" style={{ verticalAlign: '-1px' }} /> Pinned Post
              </span>
            )}

            {isAnnouncement && (
              <span
                className="community-badge"
                style={{
                  background: 'rgba(234, 88, 12, 0.12)',
                  color: '#ea580c',
                  border: '1px solid rgba(234, 88, 12, 0.25)',
                  fontSize: '11px',
                  padding: '2px 8px',
                  marginLeft: '6px',
                  fontWeight: 700,
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                }}
              >
                <Megaphone size={11} aria-hidden="true" />
                <span>Announcement</span>
              </span>
            )}

            {isSharedPost && (
              <span className="post-header__shared-tag">
                <Repeat size={12} aria-hidden="true" style={{ verticalAlign: '-1px' }} /> shared a post
              </span>
            )}

            {post.is_suggested && !isSponsored && (
              <span className="post-header__suggested-tag">· Suggested for you</span>
            )}

            {isSponsored && (
              <span
                className="sponsored-tag"
                style={{
                  backgroundColor: 'rgba(79, 125, 243, 0.12)',
                  color: '#4f7df3',
                  border: '1px solid rgba(79, 125, 243, 0.25)',
                  fontSize: '11px',
                  padding: '2px 8px',
                  borderRadius: '12px',
                  marginLeft: '6px',
                  fontWeight: 700,
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                }}
              >
                <Sparkles size={11} aria-hidden="true" />
                <span>{isEventSponsored ? 'Sponsored Event' : 'Sponsored'}</span>
              </span>
            )}

            {!isSponsored && isEventSponsored && (
              <span
                className="event-tag"
                style={{
                  backgroundColor: 'rgba(16, 185, 129, 0.12)',
                  color: '#059669',
                  border: '1px solid rgba(16, 185, 129, 0.25)',
                  fontSize: '11px',
                  padding: '2px 8px',
                  borderRadius: '12px',
                  marginLeft: '6px',
                  fontWeight: 700,
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                }}
              >
                <Calendar size={11} aria-hidden="true" />
                <span>Event</span>
              </span>
            )}
          </div>

          {isSponsored ? (
            <div style={{ fontSize: '12px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '2px' }}>
              {isEventSponsored ? (
                <>
                  <Calendar size={12} color="#4f7df3" aria-hidden="true" />
                  <span>Promoted Event • Hosted by {eventData?.organizer?.name || 'Organizer'}</span>
                </>
              ) : (
                <>
                  <Megaphone size={12} color="#4f7df3" aria-hidden="true" />
                  <span>Promoted Business Post</span>
                </>
              )}
            </div>
          ) : isEventSponsored ? (
            <div style={{ fontSize: '12px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '2px' }}>
              <Calendar size={12} color="#059669" aria-hidden="true" />
              <span>Event • Hosted by {eventData?.organizer?.name || 'Organizer'}</span>
            </div>
          ) : (
            <Link to={`/member/posts/${post.id}`}>
              <time dateTime={post.created_at}>{formatRelativeTime(post.created_at)}</time>
              <span aria-hidden="true"> · </span>
              {bizPage ? (
                <Building2 size={13} aria-hidden="true" title="Business Page Timeline" />
              ) : (
                <UsersRound size={13} aria-hidden="true" />
              )}
            </Link>
          )}
        </div>

        {/* Post Options Menu */}
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
              {onRunAd && (
                <button
                  className="post-options-menu__item"
                  type="button"
                  onClick={() => {
                    setOptionsOpen(false);
                    onRunAd(post);
                  }}
                  style={{ color: '#4f7df3', fontWeight: 600 }}
                >
                  <Megaphone size={14} color="#4f7df3" aria-hidden="true" />
                  <span>Run Ad / Promote</span>
                </button>
              )}

              {communitySlug && isCommunityAdmin && (
                <button
                  className="post-options-menu__item"
                  type="button"
                  onClick={handleTogglePin}
                >
                  <Pin size={14} aria-hidden="true" />
                  <span>
                    {isPinned ? 'Unpin from Community' : 'Pin to Community Top'}
                  </span>
                </button>
              )}

              {communitySlug && isCommunityAdmin && (
                <button
                  className="post-options-menu__item"
                  type="button"
                  onClick={handleToggleAnnouncement}
                >
                  <Megaphone size={14} aria-hidden="true" />
                  <span>
                    {isAnnouncement
                      ? 'Remove Announcement'
                      : 'Mark as Community Announcement'}
                  </span>
                </button>
              )}

              {(isAuthor || (communitySlug && isCommunityAdmin)) && (
                <button
                  className="post-options-menu__item post-options-menu__item--danger"
                  type="button"
                  onClick={() => {
                    setOptionsOpen(false);
                    setShowDeleteModal(true);
                  }}
                >
                  <Trash2 size={14} aria-hidden="true" />
                  <span>Delete Post</span>
                </button>
              )}

              <button
                className="post-options-menu__item"
                type="button"
                onClick={handleToggleSave}
              >
                <Bookmark size={14} aria-hidden="true" />
                <span>{isSaved ? 'Unsave Post' : 'Save Post'}</span>
              </button>

              {/* Hide Post action removed from UI per requirement */}

              <button
                className="post-options-menu__item post-options-menu__item--danger"
                type="button"
                onClick={() => {
                  setOptionsOpen(false);
                  setShowReportModal(true);
                }}
              >
                <Flag size={14} aria-hidden="true" />
                <span>Report Post</span>
              </button>
            </div>
          )}
        </div>
      </header>

      {/* CARD 1: Dedicated Text Content Card */}
      {hasText && (
        <div className="post-text-card">
          <div
            tabIndex={0}
            aria-label="Post text content"
            className="post-text-card__content"
          >
            {renderContentWithLinks(post.body)}
          </div>
        </div>
      )}

      {/* Paid Event Social Card */}
      {isEventSponsored && eventData && (
        <div className="paid-event-card">
          {/* ONE Single Event Image (Media Section directly below Header) */}
          {(eventData.cover_photo_url || eventData.cover_photo || post.media_path || post.media_url) && (
            <div className="paid-event-card__media">
              <Link
                to={`/member/events/${eventData.id}`}
                className="paid-event-card__media-link"
              >
                <img
                  src={eventData.cover_photo_url || getMediaUrl(eventData.cover_photo || post.media_path || post.media_url, 'events')}
                  alt={eventData.title}
                  className="paid-event-card__image"
                  loading="lazy"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                  }}
                />
              </Link>
            </div>
          )}

          {/* Event Content Section (cleanly grouped below image) */}
          <div className="paid-event-card__content">
            {/* Event Title */}
            <h3 className="paid-event-card__title">
              <Link
                to={`/member/events/${eventData.id}`}
                className="paid-event-card__title-link"
              >
                {eventData.title}
              </Link>
            </h3>

            {/* Date / Event Type / Category Meta */}
            <div className="paid-event-card__meta">
              {eventData.start_date && (
                <span className="paid-event-card__meta-item">
                  <Calendar size={13} color="#64748b" aria-hidden="true" />
                  <span>{eventData.start_date}{eventData.start_time ? ` at ${eventData.start_time}` : ''}</span>
                </span>
              )}
              <span className="paid-event-card__meta-item">
                {eventData.event_type === 'online' ? (
                  <span className="paid-event-card__badge-online">Online Event</span>
                ) : (
                  <>
                    <MapPin size={13} color="#64748b" aria-hidden="true" />
                    <span>{eventData.location_city || eventData.location_venue || 'In-Person'}</span>
                  </>
                )}
              </span>
              {eventData.category && (
                <span className="paid-event-card__meta-item">
                  <Tag size={12} color="#64748b" aria-hidden="true" />
                  <span>{eventData.category}</span>
                </span>
              )}
            </div>

            {/* Event Description / Excerpt */}
            {(eventData.description || post.body) && (
              <p className="paid-event-card__description">
                {renderContentWithLinks(eventData.description || post.body)}
              </p>
            )}

            {/* Earn Up To Reward Presentation & CTA */}
            <div className="paid-event-card__earn-row">
              <div className="paid-event-card__earn-info">
                <div className="paid-event-card__earn-badge-row">
                  {isCampaignOwner ? null : (isEventRewarded || isAlreadyRewardedEvent) ? (
                    <span
                      className="paid-event-card__earn-badge"
                      style={{
                        backgroundColor: '#d1fae5',
                        color: '#047857',
                        border: '1px solid #a7f3d0',
                      }}
                    >
                      <Check size={13} color="#059669" aria-hidden="true" />
                      Rewarded
                    </span>
                  ) : isRewardEligibleEvent ? (
                    <span className="paid-event-card__earn-badge">
                      <Gift size={13} aria-hidden="true" />
                      Earn up to {earnUpToAmount}
                    </span>
                  ) : null}
                </div>
                <div className="paid-event-card__earn-subtext">
                  {isCampaignOwner
                    ? (isRewardEligibleEvent ? 'Promoted Event • Your Active Campaign' : 'Your Event')
                    : (isEventRewarded || isAlreadyRewardedEvent)
                      ? 'Promoted Event • Reward already earned'
                      : isRewardEligibleEvent
                        ? 'Promoted Event • Earn rewards based on direct verified referrals'
                        : 'Community Event'}
                </div>
              </div>

              {isCampaignOwner ? (
                <Link
                  to={`/member/events/${eventData?.id || post.event_id}`}
                  className="paid-event-card__cta-btn"
                  style={{ textDecoration: 'none' }}
                >
                  <span>Manage</span>
                  <ExternalLink size={13} aria-hidden="true" />
                </Link>
              ) : (isEventRewarded || isAlreadyRewardedEvent) ? (
                <div className="paid-event-card__rewarded-actions" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span
                    style={{
                      backgroundColor: '#ecfdf5',
                      color: '#047857',
                      fontSize: '12px',
                      fontWeight: 700,
                      padding: '6px 12px',
                      borderRadius: '6px',
                      border: '1px solid #a7f3d0',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '4px',
                    }}
                  >
                    <Check size={13} color="#059669" />
                    <span>Rewarded</span>
                  </span>
                  <Link
                    to={`/member/events/${eventData?.id || post.event_id}`}
                    style={{
                      backgroundColor: '#f1f5f9',
                      color: '#475569',
                      fontSize: '12px',
                      fontWeight: 600,
                      padding: '6px 12px',
                      borderRadius: '6px',
                      textDecoration: 'none',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '4px',
                    }}
                  >
                    <span>View Event</span>
                    <ExternalLink size={12} />
                  </Link>
                </div>
              ) : isRewardEligibleEvent ? (
                <div className="paid-event-card__actions-group" style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                  <button
                    type="button"
                    className="paid-event-card__cta-btn paid-event-card__cta-btn--interested"
                    onClick={handleEarnClick}
                    style={{
                      backgroundColor: '#ffffff',
                      color: '#059669',
                      border: '1.5px solid #059669',
                      fontSize: '13px',
                      fontWeight: 700,
                      padding: '7px 16px',
                      borderRadius: '8px',
                      cursor: 'pointer',
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: '6px',
                      transition: 'all 0.15s ease',
                      boxShadow: '0 1px 2px rgba(0, 0, 0, 0.05)',
                    }}
                    title={earnUpToAmount ? `Interested / Earn up to ${earnUpToAmount}` : 'Interested'}
                    aria-label={earnUpToAmount ? `Show interest and earn up to ${earnUpToAmount} in ${eventData?.title || 'this event'}` : `Show interest in ${eventData?.title || 'this event'}`}
                  >
                    <Star size={13} aria-hidden="true" />
                    <span>{earnUpToAmount ? `Interested / Earn up to ${earnUpToAmount}` : 'Interested'}</span>
                  </button>
                </div>
              ) : (
                <Link
                  to={`/member/events/${eventData?.id || post.event_id}`}
                  className="paid-event-card__cta-btn"
                  style={{ textDecoration: 'none' }}
                >
                  <span>View Event</span>
                  <ExternalLink size={13} aria-hidden="true" />
                </Link>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Shared Post Embedded Box */}
      {isSharedPost && originalPost && (
        <div className="shared-post-box">
          <div className="shared-post-box__header">
            <Link
              to={origBiz ? `/member/business-pages/${origBiz.slug || origBiz.id}` : `/member/people/${origAuthor?.id}`}
              className="shared-post-box__avatar-link"
            >
              {origPhotoUrl && !origAvatarError ? (
                <img
                  className="shared-post-box__avatar"
                  src={origPhotoUrl}
                  alt={origName}
                  loading="lazy"
                  onError={() => setOrigAvatarError(true)}
                />
              ) : (
                <span
                  className="shared-post-box__avatar shared-post-box__avatar--initials"
                  role="img"
                  aria-label={`${origName} initials`}
                >
                  {getInitials(origName)}
                </span>
              )}
            </Link>

            <div className="shared-post-box__meta">
              <div className="shared-post-box__author-row">
                <Link
                  to={origBiz ? `/member/business-pages/${origBiz.slug || origBiz.id}` : `/member/people/${origAuthor?.id}`}
                  className="shared-post-box__author-name"
                >
                  <strong>{origName}</strong>
                </Link>
                {origBiz?.is_verified && (
                  <BadgeCheck size={14} color="#20c875" style={{ marginLeft: '4px' }} />
                )}
              </div>
              <Link
                to={`/member/posts/${originalPost.id}`}
                className="shared-post-box__time-link"
                title="Open original post"
              >
                <time dateTime={originalPost.created_at}>
                  {formatRelativeTime(originalPost.created_at)}
                </time>
                <span aria-hidden="true"> · </span>
                <span>Original post</span>
              </Link>
            </div>

            <Link
              to={`/member/posts/${originalPost.id}`}
              className="shared-post-box__view-original"
              title="View original post"
            >
              <ExternalLink size={14} aria-hidden="true" />
              <span>Original post</span>
            </Link>
          </div>

          {originalPost.body && <p className="shared-post-box__text">{renderContentWithLinks(originalPost.body)}</p>}

          {(originalPost.media_path || originalPost.media_url || originalPost.media) && (
            <div style={{ marginTop: '10px' }}>
              {(originalPost.media_type === 'video' || (typeof (originalPost.media_path || originalPost.media_url || '') === 'string' && (originalPost.media_path || originalPost.media_url || '').match(/\.(mp4|webm|mov)(\?.*)?$/i))) ? (
                <PostFeedVideo
                  className="post-video"
                  controls
                  playsInline
                  preload="metadata"
                  src={getMediaUrl(originalPost.media_path || originalPost.media_url || originalPost.media)}
                />
              ) : (
                <img
                  className="post-image"
                  src={getMediaUrl(originalPost.media_path || originalPost.media_url || originalPost.media)}
                  alt={`Shared by ${origName}`}
                  loading="lazy"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                  }}
                />
              )}
            </div>
          )}
        </div>
      )}

      {/* Shared Post Unavailable */}
      {isSharedPost && !originalPost && (
        <div className="shared-post-box shared-post-box--unavailable">
          <div className="shared-post-box__unavailable-content">
            <Lock size={20} aria-hidden="true" />
            <div>
              <strong>This content isn't available right now</strong>
              <p>When this happens, it's usually because the original author deleted the post or it's private.</p>
            </div>
          </div>
        </div>
      )}

      {/* CARD 2: Dedicated Image / Media Card */}
      {hasMedia && (
        <div className={`post-media-card ${!hasText ? 'post-media-card--standalone' : ''}`}>
          {isVideo ? (
            <PostFeedVideo
              className="post-video"
              controls
              playsInline
              preload="metadata"
              src={getMediaUrl(mediaUrl)}
            />
          ) : (
            <img
              className="post-image"
              src={getMediaUrl(mediaUrl)}
              alt={`Shared by ${authorName}`}
              loading="lazy"
              onError={(e) => {
                e.currentTarget.style.display = 'none';
              }}
            />
          )}
        </div>
      )}

      {/* Private Owner Reaction Banner */}
      {isOwner && reactionsCount > 0 && (
        <div className="post-owner-reaction-banner">
          <span>
            <ThumbsUp size={13} style={{ verticalAlign: '-1px', marginRight: '6px' }} />
            <strong>{reactionsCount} {reactionsCount === 1 ? 'member' : 'members'}</strong> reacted to your post
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

      {/* Sponsored Post Interested / Landing Reward CTA Banner */}
      {isAdSponsored && (
        <div
          className="sponsored-cta-banner"
          style={{
            margin: '10px 0 4px 0',
            padding: '12px 16px',
            backgroundColor: isCampaignOwner ? 'rgba(241, 245, 249, 0.7)' : (adCampaignData?.already_rewarded ? 'rgba(16, 185, 129, 0.05)' : 'rgba(5, 150, 105, 0.06)'),
            borderRadius: '10px',
            border: isCampaignOwner ? '1px solid #cbd5e1' : (adCampaignData?.already_rewarded ? '1px solid rgba(16, 185, 129, 0.2)' : '1px solid rgba(5, 150, 105, 0.25)'),
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: '12px',
          }}
        >
          <div>
            <div style={{ fontSize: '13.5px', fontWeight: 700, color: '#1e293b', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span>{bizPage?.page_name || adCampaignData?.campaign_name || authorName || 'Promoted Business'}</span>
              {isCampaignOwner ? null : adCampaignData?.already_rewarded ? (
                <span style={{ fontSize: '10.5px', fontWeight: 700, color: '#047857', backgroundColor: '#d1fae5', padding: '1px 6px', borderRadius: '4px' }}>
                  Rewarded
                </span>
              ) : (
                <span style={{ fontSize: '10.5px', fontWeight: 700, color: '#047857', backgroundColor: '#d1fae5', padding: '1px 6px', borderRadius: '4px' }}>
                  Earn up to {adEarnUpToAmount}
                </span>
              )}
            </div>
            <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '2px' }}>
              {isCampaignOwner
                ? 'Promoted Business • Your Active Campaign'
                : adCampaignData?.already_rewarded
                  ? 'Promoted Business • Reward already earned'
                  : 'Promoted Business • Earn rewards based on direct verified referrals'}
            </div>
          </div>

          {isCampaignOwner ? (
            <Link
              to={bizPage?.slug ? `/member/business-pages/${bizPage.slug}?tab=ads` : (bizPage?.id ? `/member/business-pages/${bizPage.id}?tab=ads` : '/member/business-pages')}
              className="member-button"
              style={{
                backgroundColor: '#f1f5f9',
                color: '#334155',
                fontSize: '12.5px',
                fontWeight: 700,
                padding: '7px 16px',
                borderRadius: '8px',
                border: '1px solid #cbd5e1',
                textDecoration: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
              }}
            >
              <span>Manage</span>
              <ExternalLink size={13} aria-hidden="true" />
            </Link>
          ) : adCampaignData?.already_rewarded ? (
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <span
                style={{
                  backgroundColor: '#ecfdf5',
                  color: '#047857',
                  fontSize: '12px',
                  fontWeight: 700,
                  padding: '6px 12px',
                  borderRadius: '6px',
                  border: '1px solid #a7f3d0',
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                }}
              >
                <Check size={13} color="#059669" />
                <span>Rewarded</span>
              </span>
              {bizPage && (
                <Link
                  to={`/member/business-pages/${bizPage.slug || bizPage.id}`}
                  style={{
                    backgroundColor: '#f1f5f9',
                    color: '#475569',
                    fontSize: '12px',
                    fontWeight: 600,
                    padding: '6px 12px',
                    borderRadius: '6px',
                    textDecoration: 'none',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '4px',
                  }}
                >
                  <span>Visit Page</span>
                  <ExternalLink size={12} />
                </Link>
              )}
            </div>
          ) : (
            <button
              type="button"
              className="member-button"
              onClick={handleInterestedClick}
              style={{
                backgroundColor: '#059669',
                color: '#ffffff',
                fontSize: '12.5px',
                fontWeight: 700,
                padding: '7px 16px',
                borderRadius: '8px',
                border: 'none',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                boxShadow: '0 2px 4px rgba(5, 150, 105, 0.25)',
              }}
            >
              <span>Interested / Earn</span>
              <ExternalLink size={13} aria-hidden="true" />
            </button>
          )}
        </div>
      )}

      {/* Footer / Action Bar */}
      <footer className="post-footer">
        {isEventSponsored && eventData ? (
          <div className="paid-event-card__footer">
            <span className="paid-event-card__footer-count">
              {eventData.guests_count || 0} {(eventData.guests_count === 1) ? 'person' : 'people'} responded
            </span>
            <Link
              to={`/member/events/${eventData.id}`}
              className="paid-event-card__footer-link"
            >
              <span>View Event Details</span>
              <ArrowRight size={13} aria-hidden="true" />
            </Link>
          </div>
        ) : (
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
                {Object.entries(REACTION_CONFIG).map(([type, config]) => (
                  <button
                    key={type}
                    className="post-reaction-picker__item"
                    type="button"
                    title={config.label}
                    aria-label={config.label}
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
              className={`post-action-btn post-action-btn--like ${userReaction ? 'is-active' : ''}`}
              type="button"
              aria-label={userReaction ? `Reacted ${activeReactionData?.label || 'Like'}` : 'Like post'}
              style={
                activeReactionData
                  ? {
                      color: activeReactionData.color,
                      borderColor: `${activeReactionData.color}44`,
                      background: `${activeReactionData.color}12`,
                    }
                  : {}
              }
              onTouchStart={handleTouchStartLike}
              onTouchEnd={handleTouchEndLike}
              onTouchCancel={handleTouchEndLike}
              onClick={() => handleReact(userReaction || 'like')}
            >
              <span className="post-action-btn__icon">
                {activeReactionData ? (
                  activeReactionData.emoji
                ) : (
                  <ThumbsUp size={16} aria-hidden="true" />
                )}
              </span>
              <span>{activeReactionData ? activeReactionData.label : 'Like'}</span>
            </button>
          </div>

          {/* Reactions Count (Clickable modal trigger for Post Owner only; static badge for others) */}
          {isOwner ? (
            <button
              className="post-action-btn post-action-btn--likers"
              type="button"
              aria-label="View who reacted to your post"
              title="View who reacted to your post"
              onClick={handleOpenReactors}
            >
              <span className="post-actions__reaction-badges">
                <span className="post-actions__badge">👍</span>
              </span>
              <span>{reactionsCount}</span>
            </button>
          ) : (
            <div
              className="post-action-badge post-action-badge--likers"
              aria-label="Reactions count"
              title="Reactions count"
            >
              <span className="post-actions__reaction-badges">
                <span className="post-actions__badge">👍</span>
              </span>
              <span>{reactionsCount}</span>
            </div>
          )}

          {/* Comments Count Button */}
          <button
            className="post-action-btn post-action-btn--comment-count"
            type="button"
            aria-label="View Comments"
            onClick={() => {
              if (comments.length === 0 && commentsCount > 0 && !isLoadingMoreComments) {
                handleLoadMoreComments();
              }
              commentInputRef.current?.focus();
            }}
          >
            <MessageCircle size={16} aria-hidden="true" />
            <span>Comments ({commentsCount})</span>
          </button>

          {/* Save Button */}
          <button
            className={`post-action-btn post-action-btn--save ${isSaved ? 'is-active' : ''}`}
            type="button"
            aria-label="Save Post"
            onClick={handleToggleSave}
          >
            <Bookmark size={16} aria-hidden="true" />
            <span>{isSaved ? 'Saved' : 'Save'} ({savesCount})</span>
          </button>

          {/* Share Button */}
          <button
            className="post-action-btn post-action-btn--share"
            type="button"
            aria-label="Share Post"
            onClick={() => setShowShareModal(true)}
          >
            <Share2 size={16} aria-hidden="true" />
            <span>Share</span>
          </button>

          {/* Shares Count */}
          <button
            className="post-action-btn post-action-btn--share-count"
            type="button"
            aria-label="View Shares"
            onClick={handleOpenSharers}
          >
            <Repeat size={16} aria-hidden="true" />
            <span>{sharesCount} Shares</span>
          </button>
        </div>
        )}
      </footer>

      {/* Comments Section */}
      {!isEventSponsored && (
        <div className="post-comments">
        {hasMoreComments && (
          <button
            className="post-comments__more"
            type="button"
            onClick={handleLoadMoreComments}
            disabled={isLoadingMoreComments}
          >
            {isLoadingMoreComments ? 'Loading comments...' : 'View previous comments'}
          </button>
        )}

        <div className="post-comments__list">
          {comments.length > 0 ? (
            comments.map((comment) => (
              <CommentItem
                key={comment.id}
                comment={comment}
                post={post}
                currentUser={currentUser}
                onDeleteComment={handleDeleteComment}
              />
            ))
          ) : (
            <div className="post-comments__empty">
              <p>Be the first to comment.</p>
            </div>
          )}
        </div>

        {/* Add Comment Form */}
        <form className="post-comment-form" onSubmit={handleCreateComment}>
          <div className="post-comment-form__avatar-wrap">
            {userPhotoUrl && !userAvatarError ? (
              <img
                className="post-comment-form__avatar"
                src={userPhotoUrl}
                alt={currentUser?.name || 'User'}
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
              placeholder="Write a comment..."
              aria-label="Write a comment"
              maxLength={1000}
              required
              autoComplete="off"
              value={newCommentText}
              onChange={(e) => setNewCommentText(e.target.value)}
            />
            <button
              className="post-comment-form__submit"
              type="submit"
              disabled={!newCommentText.trim() || isSubmittingComment}
              aria-label="Send Comment"
            >
              <SendHorizontal size={16} aria-hidden="true" />
            </button>
          </div>
        </form>
      </div>
      )}

      {/* Modals */}
      {showReactorsModal && (
        <ReactorsModal
          isOpen={showReactorsModal}
          onClose={() => setShowReactorsModal(false)}
          reactors={reactorsList}
        />
      )}

      {showSharersModal && (
        <SharersModal
          isOpen={showSharersModal}
          onClose={() => setShowSharersModal(false)}
          sharers={sharersList}
          isLoading={isLoadingSharers}
          totalCount={sharesCount}
        />
      )}

      {showShareModal && (
        <ShareModal
          isOpen={showShareModal}
          onClose={() => setShowShareModal(false)}
          post={post}
          onShareSuccess={(response) => {
            const nextCount = response?.shares_count !== undefined
              ? Number(response.shares_count)
              : (sharesCount + 1);
            setSharesCount(nextCount);
            if (onPostUpdated) {
              const updatedOriginalPost = post.original_post
                ? { ...post.original_post, shares_count: nextCount }
                : (post.originalPost ? { ...post.originalPost, shares_count: nextCount } : null);
              onPostUpdated({
                ...post,
                shares_count: nextCount,
                ...(updatedOriginalPost ? { original_post: updatedOriginalPost, originalPost: updatedOriginalPost } : {}),
              });
            }
          }}
        />
      )}

      {showReportModal && (
        <ReportModal
          isOpen={showReportModal}
          onClose={() => setShowReportModal(false)}
          postId={
            (/^\d+$/.test(String(post?.id || '')) ? post.id : null) ||
            post?.post_id ||
            post?.source_post_id ||
            post?.ad_campaign?.post_id ||
            post?.campaign?.post_id ||
            post?.event?.post_id ||
            post?.id
          }
          post={post}
        />
      )}

      {showDeleteModal && (
        <DeleteConfirmModal
          isOpen={showDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onConfirm={handleDeletePost}
          title="Delete Post"
          message="Are you sure you want to permanently delete this post? This action cannot be undone."
          isDeleting={isDeleting}
        />
      )}

      {showRewardPreviewModal && (
        <AdRewardPreviewModal
          isOpen={showRewardPreviewModal}
          onClose={() => setShowRewardPreviewModal(false)}
          campaignId={post.ad_campaign_id || post.ad_campaign?.campaign_id || post.ad_campaign_raw_id}
          businessPage={bizPage}
          onContinue={handleContinueToRewardTarget}
        />
      )}

      {showEventQualificationModal && (
        <PaidEventQualificationModal
          isOpen={showEventQualificationModal}
          onClose={() => setShowEventQualificationModal(false)}
          event={eventData}
          campaign={eventCampaign}
          onSuccess={() => {
            setIsEventRewarded(true);
            if (onPostUpdated) {
              onPostUpdated({
                ...post,
                already_rewarded: true,
                ad_campaign: {
                  ...eventCampaign,
                  already_rewarded: true,
                },
                event: {
                  ...eventData,
                  already_rewarded: true,
                  user_response: 'interested',
                },
              });
            }
            const eventId = eventData?.id || post.event_id;
            if (eventId) {
              setTimeout(() => {
                navigate(`/member/events/${eventId}`);
              }, 1500);
            }
          }}
        />
      )}

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          initialError={verifyMessage}
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
            if (isEventSponsored) {
              setShowEventQualificationModal(true);
            } else if (isSponsored) {
              setShowRewardPreviewModal(true);
            }
          }}
        />
      )}
    </article>
  );
}

export default PostCard;
