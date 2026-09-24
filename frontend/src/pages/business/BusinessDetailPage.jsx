import { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, Link, useSearchParams } from 'react-router-dom';
import {
  BadgeCheck,
  Tag,
  Lock,
  FileText,
  Globe,
  Star,
  Users,
  MapPin,
  Crown,
  Shield,
  Edit3,
  Check,
  Clock,
  UserPlus,
  Share2,
  MoreHorizontal,
  Home,
  Info,
  Image as ImageIcon,
  Video,
  Send,
  ExternalLink,
  Mail,
  Phone,
  Calendar,
  User,
  Copy,
  UserCheck,
  UserMinus,
  X,
  ThumbsUp,
  ThumbsDown,
  Flag,
  Building2,
  MessageSquare,
  Bell,
  BarChart3,
  Megaphone,
  Wallet,
  Camera,
  Eye,
  Gift,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import postApi from '../../api/postApi';
import useAuth from '../../hooks/useAuth';
import { getAvatarUrl, getCoverUrl, getInitials, getMediaUrl } from '../../utils/assetHelper';
import PostComposer from '../../components/posts/PostComposer';
import PostCard from '../../components/posts/PostCard';
import BusinessReviewCard from '../../components/business/BusinessReviewCard';
import ShareModal from '../../components/posts/modals/ShareModal';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import BusinessAdCampaignsList from '../../components/business/ads/BusinessAdCampaignsList';
import CreateAdCampaignModal from '../../components/business/ads/CreateAdCampaignModal';
import AddFundView from '../../components/business/ads/AddFundView';
import ProfileImageAdjustModal from '../../components/profile/ProfileImageAdjustModal';
import { ModalPortal } from '../../components/common/ModalPortal';
import ProfileMediaViewerModal from '../../components/profile/ProfileMediaViewerModal';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import { isMemberMobileVerified } from '../../utils/whatsappVerification';

function formatDate(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

export function BusinessDetailPage() {
  const { slug } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const { user: currentUser } = useAuth();
  const isVerified = isMemberMobileVerified(currentUser);
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [verifyPromptMessage, setVerifyPromptMessage] = useState('Please verify your mobile number through WhatsApp to perform this action.');

  const activeTab = searchParams.get('tab') || 'home';

  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [copiedLink, setCopiedLink] = useState(false);
  const [showDropdown, setShowDropdown] = useState(false);
  const [showShareModal, setShowShareModal] = useState(false);
  const [showRunAdModal, setShowRunAdModal] = useState(false);
  const [targetPostForAd, setTargetPostForAd] = useState(null);
  const dropdownRef = useRef(null);

  // Sponsored Campaign Landing Reward State (No follow required)
  const campaignParam = searchParams.get('campaign');
  const [campaignRewardStatus, setCampaignRewardStatus] = useState(null);
  const [isProcessingLandingReward, setIsProcessingLandingReward] = useState(false);
  const [rewardSuccessMessage, setRewardSuccessMessage] = useState(null);
  const landingVisitTrackedRef = useRef(false);

  useEffect(() => {
    if (campaignParam && currentUser && !landingVisitTrackedRef.current) {
      if (data && (data.is_owner || data.business_page?.member_id === currentUser.id)) {
        return;
      }
      landingVisitTrackedRef.current = true;
      setIsProcessingLandingReward(true);

      const eventId = `visit_${campaignParam}_${currentUser.id || 'usr'}_${Date.now()}`;
      postApi.qualifyLandingVisit(campaignParam, {
        qualifying_event_id: eventId,
        landing_page_url: window.location.href,
      })
        .then((res) => {
          if (res && res.success && res.rewarded) {
            const amtExact = res.reward_amount_exact || (res.reward_amount_usd ? Number(res.reward_amount_usd).toFixed(4) : '0.0500');
            setCampaignRewardStatus({
              already_rewarded: true,
              eligible_to_earn: false,
              reward_amount_usd: res.reward_amount_usd,
            });
            setRewardSuccessMessage(`✓ Qualifying campaign visit verified! $${amtExact} USD credited to your Reward Wallet.`);
          } else if (res && (res.already_rewarded || res.duplicate)) {
            setCampaignRewardStatus({
              already_rewarded: true,
              eligible_to_earn: false,
            });
          }
        })
        .catch((err) => {
          if (err.response?.data?.already_rewarded || err.response?.data?.duplicate) {
            setCampaignRewardStatus({
              already_rewarded: true,
              eligible_to_earn: false,
            });
          }
        })
        .finally(() => {
          setIsProcessingLandingReward(false);
        });
    }
  }, [campaignParam, currentUser, data]);

  // Photo Upload & Adjustment Modals (same system as Member Profile)
  const [photoModalType, setPhotoModalType] = useState(null); // 'avatar' | 'cover' | null
  const [selectedFile, setSelectedFile] = useState(null);
  const [isUploadingPhoto, setIsUploadingPhoto] = useState(false);
  const [photoError, setPhotoError] = useState(null);
  const [viewMedia, setViewMedia] = useState(null); // { type: 'avatar' | 'cover', url: string } | null
  const avatarFileInputRef = useRef(null);
  const coverFileInputRef = useRef(null);

  const handleOpenViewMedia = (type, url) => {
    if (url) {
      setViewMedia({ type, url });
    }
  };

  const handleCloseViewMedia = () => {
    setViewMedia(null);
  };

  const handleClosePhotoModal = useCallback(() => {
    setPhotoModalType(null);
    setSelectedFile(null);
    setPhotoError(null);
    if (avatarFileInputRef.current) {
      avatarFileInputRef.current.value = '';
    }
    if (coverFileInputRef.current) {
      coverFileInputRef.current.value = '';
    }
  }, []);

  const handleFileChange = (e, type) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (type === 'avatar' && file.size > 5 * 1024 * 1024) {
      alert('Profile photo size must not exceed 5MB.');
      e.target.value = '';
      return;
    }
    if (type === 'cover' && file.size > 10 * 1024 * 1024) {
      alert('Cover photo size must not exceed 10MB.');
      e.target.value = '';
      return;
    }

    setPhotoModalType(type);
    setSelectedFile(file);
    setPhotoError(null);
  };



  // Followers tab filter state
  const [followerSearch, setFollowerSearch] = useState('');
  const [followerSort, setFollowerSort] = useState('newest');

  // Reviews tab filter state
  const [reviewSearch, setReviewSearch] = useState('');
  const [reviewSort, setReviewSort] = useState('newest');

  const fetchPageDetail = useCallback(() => {
    if (!slug) return Promise.resolve(null);
    const params = {
      tab: activeTab,
      q: activeTab === 'reviews' ? (reviewSearch || undefined) : (followerSearch || undefined),
      sort: activeTab === 'reviews' ? (reviewSort || undefined) : (followerSort || undefined),
    };
    return businessApi.getBusinessPage(slug, params);
  }, [slug, activeTab, followerSearch, followerSort, reviewSearch, reviewSort]);

  useEffect(() => {
    let isMounted = true;

    fetchPageDetail()
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load business page.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchPageDetail]);

  // Handle outside click & Escape for Three-Dot Dropdown
  useEffect(() => {
    if (!showDropdown) return;
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setShowDropdown(false);
      }
    };
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        setShowDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [showDropdown]);

  const handleTabChange = (tabName) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('tab', tabName);
    setSearchParams(newParams);
  };

  const handleToggleFollow = async () => {
    if (!isVerified) {
      setVerifyPromptMessage('Please verify your mobile number through WhatsApp before following a business page.');
      setShowVerifyModal(true);
      return;
    }

    try {
      const res = await businessApi.toggleFollow(slug);
      if (res && res.success) {
        setData((prev) => {
          if (!prev) return prev;
          return {
            ...prev,
            follow_status: res.status,
            is_following: res.is_following,
            followers_count: res.followers_count ?? prev.followers_count,
          };
        });
      }
    } catch {
      // Ignore
    }
  };

  const handleCopyLink = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
      setCopiedLink(true);
      setTimeout(() => setCopiedLink(false), 3000);
    }
  };

  const handlePostCreated = (newPost) => {
    if (newPost) {
      setData((prev) => {
        if (!prev) return prev;
        const currentTimeline = prev.timeline_posts?.data || (Array.isArray(prev.timeline_posts) ? prev.timeline_posts : []);
        const updatedTimeline = [newPost, ...currentTimeline.filter((p) => p.id !== newPost.id)];
        return {
          ...prev,
          timeline_posts: prev.timeline_posts?.data
            ? { ...prev.timeline_posts, data: updatedTimeline, total: (prev.timeline_posts.total || currentTimeline.length) + 1 }
            : updatedTimeline,
        };
      });
    }
    fetchPageDetail().then((res) => {
      if (res) setData(res);
    });
  };

  // Follower Management States & Handlers
  const [isInviteFollowModalOpen, setIsInviteFollowModalOpen] = useState(false);
  const [inviteFollowId, setInviteFollowId] = useState('');
  const [isInvitingFollow, setIsInvitingFollow] = useState(false);
  const [inviteFollowError, setInviteFollowError] = useState(null);

  const handleOpenInviteFollow = () => {
    setInviteFollowId('');
    setInviteFollowError(null);
    setIsInviteFollowModalOpen(true);
  };

  const handleCloseInviteFollow = () => {
    setIsInviteFollowModalOpen(false);
  };

  const handleOpenRunAd = (post) => {
    setTargetPostForAd(post);
    setShowRunAdModal(true);
  };

  const handleSendInviteFollow = async (e) => {
    e.preventDefault();
    if (!inviteFollowId || isInvitingFollow) return;

    setIsInvitingFollow(true);
    setInviteFollowError(null);

    try {
      await businessApi.inviteToFollow(slug, parseInt(inviteFollowId, 10));
      setIsInviteFollowModalOpen(false);
      alert('Follow invitation sent successfully!');
    } catch (err) {
      setInviteFollowError(err.response?.data?.message || 'Failed to send follow invitation.');
    } finally {
      setIsInvitingFollow(false);
    }
  };

  const handleApproveFollowRequest = async (followerId) => {
    try {
      await businessApi.handleFollowRequest(slug, followerId, 'accept');
      fetchPageDetail().then((res) => res && setData(res));
    } catch {
      alert('Failed to approve follow request.');
    }
  };

  const handleDeclineFollowRequest = async (followerId) => {
    try {
      await businessApi.handleFollowRequest(slug, followerId, 'reject');
      fetchPageDetail().then((res) => res && setData(res));
    } catch {
      alert('Failed to decline follow request.');
    }
  };

  const handleRemoveFollower = async (followerId, name) => {
    if (!window.confirm(`Are you sure you want to remove ${name} from followers?`)) return;
    try {
      await businessApi.removeFollower(slug, followerId);
      fetchPageDetail().then((res) => res && setData(res));
    } catch {
      alert('Failed to remove follower.');
    }
  };

  // Reviews Modal States & Handlers
  const [isWriteReviewModalOpen, setIsWriteReviewModalOpen] = useState(false);
  const [reviewEditMode, setReviewEditMode] = useState(false);
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewRecommendation, setReviewRecommendation] = useState('recommend');
  const [reviewTitle, setReviewTitle] = useState('');
  const [reviewBody, setReviewBody] = useState('');
  const [reviewPhotos, setReviewPhotos] = useState(null);
  const [isSubmittingReview, setIsSubmittingReview] = useState(false);
  const [reviewError, setReviewError] = useState(null);

  // Reply Modal States
  const [isReplyModalOpen, setIsReplyModalOpen] = useState(false);
  const [replyingReview, setReplyingReview] = useState(null);
  const [replyText, setReplyText] = useState('');
  const [isSubmittingReply, setIsSubmittingReply] = useState(false);
  const [replyError, setReplyError] = useState(null);

  // Report Modal States
  const [isReportModalOpen, setIsReportModalOpen] = useState(false);
  const [reportingReviewId, setReportingReviewId] = useState(null);
  const [reportReason, setReportReason] = useState('spam');
  const [reportDetails, setReportDetails] = useState('');
  const [isSubmittingReport, setIsSubmittingReport] = useState(false);
  const [reportError, setReportError] = useState(null);

  // Customer Message Modal States & Handler
  const [isCustomerMessageModalOpen, setIsCustomerMessageModalOpen] = useState(false);
  const [customerMessageText, setCustomerMessageText] = useState('');
  const [isSendingCustomerMessage, setIsSendingCustomerMessage] = useState(false);
  const [customerMessageError, setCustomerMessageError] = useState(null);

  const handleSendCustomerMessage = async (e) => {
    e.preventDefault();
    if (!isVerified) {
      setVerifyPromptMessage('Please verify your mobile number through WhatsApp before contacting this business page.');
      setShowVerifyModal(true);
      return;
    }
    if (!customerMessageText.trim() || isSendingCustomerMessage) return;

    setIsSendingCustomerMessage(true);
    setCustomerMessageError(null);

    try {
      const res = await businessApi.startConversation(slug, { message: customerMessageText.trim() });
      if (res && res.success) {
        setIsCustomerMessageModalOpen(false);
        setCustomerMessageText('');
        alert('Message sent! The business team has been notified.');
      }
    } catch (err) {
      setCustomerMessageError(err.response?.data?.message || 'Failed to send message.');
    } finally {
      setIsSendingCustomerMessage(false);
    }
  };

  const handleOpenWriteReview = (isEdit = false) => {
    if (!isVerified) {
      setVerifyPromptMessage('Please verify your mobile number through WhatsApp before reviewing a business page.');
      setShowVerifyModal(true);
      return;
    }
    const userRev = data?.user_review;
    if (isEdit && userRev) {
      setReviewEditMode(true);
      setReviewRating(userRev.rating || 5);
      setReviewRecommendation(userRev.recommendation || 'recommend');
      setReviewTitle(userRev.title || '');
      setReviewBody(userRev.body || '');
    } else {
      setReviewEditMode(false);
      setReviewRating(5);
      setReviewRecommendation('recommend');
      setReviewTitle('');
      setReviewBody('');
    }
    setReviewPhotos(null);
    setReviewError(null);
    setIsWriteReviewModalOpen(true);
  };

  const handleCloseWriteReview = () => {
    setIsWriteReviewModalOpen(false);
  };

  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!isVerified) {
      setVerifyPromptMessage('Please verify your mobile number through WhatsApp before reviewing a business page.');
      setShowVerifyModal(true);
      return;
    }
    if (!reviewBody.trim() || isSubmittingReview) return;

    setIsSubmittingReview(true);
    setReviewError(null);

    try {
      const userRev = data?.user_review;
      if (reviewEditMode && userRev) {
        await businessApi.updateReview(slug, userRev.id, {
          rating: reviewRating,
          recommendation: reviewRecommendation,
          title: reviewTitle.trim() || undefined,
          body: reviewBody.trim(),
        });
      } else {
        const formData = new FormData();
        formData.append('rating', reviewRating);
        formData.append('recommendation', reviewRecommendation);
        if (reviewTitle.trim()) formData.append('title', reviewTitle.trim());
        formData.append('body', reviewBody.trim());
        if (reviewPhotos && reviewPhotos.length > 0) {
          for (let i = 0; i < reviewPhotos.length; i++) {
            formData.append('photos[]', reviewPhotos[i]);
          }
        }
        await businessApi.storeReview(slug, formData);
      }
      setIsWriteReviewModalOpen(false);
      fetchPageDetail().then((res) => res && setData(res));
    } catch (err) {
      setReviewError(err.response?.data?.message || 'Failed to submit review.');
    } finally {
      setIsSubmittingReview(false);
    }
  };

  const handleDeleteReview = async (reviewId) => {
    if (!window.confirm('Are you sure you want to delete this review?')) return;
    try {
      await businessApi.deleteReview(slug, reviewId);
      fetchPageDetail().then((res) => res && setData(res));
    } catch {
      alert('Failed to delete review.');
    }
  };

  const handleVoteReview = async (reviewId, type) => {
    if (!isVerified) {
      setVerifyPromptMessage('Please verify your mobile number through WhatsApp before rating or voting on reviews.');
      setShowVerifyModal(true);
      return;
    }
    try {
      const res = await businessApi.voteReview(slug, reviewId, type);
      if (res && res.success) {
        fetchPageDetail().then((pageRes) => pageRes && setData(pageRes));
      }
    } catch {
      alert('Failed to record vote.');
    }
  };

  const handleOpenReply = (rev) => {
    const existing = rev.official_reply?.reply || rev.officialReply?.reply || '';
    setReplyingReview(rev);
    setReplyText(existing);
    setReplyError(null);
    setIsReplyModalOpen(true);
  };

  const handleCloseReply = () => {
    setIsReplyModalOpen(false);
    setReplyingReview(null);
  };

  const handleSubmitReply = async (e) => {
    e.preventDefault();
    if (!replyingReview || !replyText.trim() || isSubmittingReply) return;

    setIsSubmittingReply(true);
    setReplyError(null);

    try {
      await businessApi.storeReviewReply(slug, replyingReview.id, replyText.trim());
      setIsReplyModalOpen(false);
      fetchPageDetail().then((res) => res && setData(res));
    } catch (err) {
      setReplyError(err.response?.data?.message || 'Failed to submit official reply.');
    } finally {
      setIsSubmittingReply(false);
    }
  };

  const handleOpenReport = (reviewId) => {
    setReportingReviewId(reviewId);
    setReportReason('spam');
    setReportDetails('');
    setReportError(null);
    setIsReportModalOpen(true);
  };

  const handleCloseReport = () => {
    setIsReportModalOpen(false);
    setReportingReviewId(null);
  };

  const handleSubmitReport = async (e) => {
    e.preventDefault();
    if (!reportingReviewId || isSubmittingReport) return;

    setIsSubmittingReport(true);
    setReportError(null);

    try {
      await businessApi.reportReview(slug, reportingReviewId, {
        reason: reportReason,
        details: reportDetails.trim() || undefined,
      });
      setIsReportModalOpen(false);
      alert('Thank you. Your report has been submitted to the moderation team.');
    } catch (err) {
      setReportError(err.response?.data?.message || 'Failed to submit report.');
    } finally {
      setIsSubmittingReport(false);
    }
  };

  const handleToggleHideReview = async (reviewId) => {
    try {
      const res = await businessApi.toggleHideReview(slug, reviewId);
      if (res && res.success) {
        alert(res.message);
        fetchPageDetail().then((pageRes) => pageRes && setData(pageRes));
      }
    } catch {
      alert('Failed to update review visibility.');
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading business page...
      </div>
    );
  }

  if (error || !data?.business_page) {
    return (
      <div className="card" style={{ maxWidth: '800px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Business Page Not Found</h2>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>
          {error || 'This business page does not exist or is private.'}
        </p>
        <Link to="/member/business-pages" className="member-button member-button--primary">
          Back to Business Pages
        </Link>
      </div>
    );
  }

  const page = data.business_page;
  const isOwner = Boolean(data.is_owner);
  const canManage = Boolean(data.can_manage);
  const followStatus = data.follow_status || 'none';
  const followersCount = data.followers_count ?? 0;
  const pinnedPost = data.pinned_post;
  const timelinePosts = data.timeline_posts?.data || (Array.isArray(data.timeline_posts) ? data.timeline_posts : []);
  const photos = data.photos?.data || (Array.isArray(data.photos) ? data.photos : []);
  const videos = data.videos?.data || (Array.isArray(data.videos) ? data.videos : []);
  const followersList = data.followers?.data || (Array.isArray(data.followers) ? data.followers : []);
  const pendingFollowRequests = data.pending_follow_requests || [];
  const friends = data.friends || [];
  const reviewsList = data.reviews?.data || (Array.isArray(data.reviews) ? data.reviews : []);
  const averageRating = Number(data.average_rating ?? 0);
  const reviewsCount = Number(data.reviews_count ?? (data.reviews?.total ?? (Array.isArray(data.reviews) ? data.reviews.length : 0)));
  const ratingDistribution = data.rating_distribution || { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
  const recommendationBadge = data.recommendation_badge || 'No Reviews Yet';
  const userReview = data.user_review || null;
  const isFollowing = Boolean(data.is_following);

  const profilePhotoUrl = page.logo ? getAvatarUrl(page.logo) : null;
  const coverPhotoUrl = page.cover_photo ? getCoverUrl(page.cover_photo) : null;
  const initials = getInitials(page.page_name);

  const handleSaveAdjustedPhoto = async (adjustedFile) => {
    if (!adjustedFile || !photoModalType) return;

    setIsUploadingPhoto(true);
    setPhotoError(null);

    const formData = new FormData();
    if (photoModalType === 'avatar') {
      formData.append('profile_photo', adjustedFile);
    } else {
      formData.append('cover_photo', adjustedFile);
    }

    try {
      if (photoModalType === 'avatar') {
        const res = await businessApi.updateProfilePhoto(slug, formData);
        if (res && res.success) {
          setData((prev) => ({
            ...prev,
            business_page: res.business_page || {
              ...prev?.business_page,
              logo: res.logo || res.photo_url,
            },
          }));
          handleClosePhotoModal();
          fetchPageDetail().then((r) => r && setData(r));
        }
      } else {
        const res = await businessApi.updateCoverPhoto(slug, formData);
        if (res && res.success) {
          setData((prev) => ({
            ...prev,
            business_page: res.business_page || {
              ...prev?.business_page,
              cover_photo: res.cover_photo || res.photo_url,
            },
          }));
          handleClosePhotoModal();
          fetchPageDetail().then((r) => r && setData(r));
        }
      }
    } catch (err) {
      setPhotoError(err.response?.data?.message || err.message || 'Failed to upload image.');
    } finally {
      setIsUploadingPhoto(false);
    }
  };

  const handleRemovePhoto = async (type) => {
    if (!window.confirm(`Are you sure you want to remove the business page ${type === 'avatar' ? 'profile' : 'cover'} photo?`)) {
      return;
    }

    setIsUploadingPhoto(true);
    try {
      if (type === 'avatar') {
        const res = await businessApi.removeProfilePhoto(slug);
        setData((prev) => ({
          ...prev,
          business_page: res?.business_page || { ...prev?.business_page, logo: null },
        }));
      } else {
        const res = await businessApi.removeCoverPhoto(slug);
        setData((prev) => ({
          ...prev,
          business_page: res?.business_page || { ...prev?.business_page, cover_photo: null },
        }));
      }
      handleClosePhotoModal();
      fetchPageDetail().then((r) => r && setData(r));
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to remove photo.');
    } finally {
      setIsUploadingPhoto(false);
    }
  };

  const locationText = [page.city, page.state, page.country].filter(Boolean).join(', ');
  const social = page.social_links || {};

  return (
    <div className="biz-page">
      {/* Hero Header */}
      <div className="biz-hero">
        <div className="biz-hero__cover">
          {coverPhotoUrl ? (
            <img
              className="biz-hero__cover-img"
              src={coverPhotoUrl}
              alt={`${page.page_name} cover banner`}
              onClick={() => handleOpenViewMedia('cover', coverPhotoUrl)}
              title="View Cover Photo"
              style={{ cursor: 'pointer' }}
            />
          ) : (
            <div
              className="biz-hero__cover-placeholder"
              onClick={() => canManage && coverFileInputRef.current?.click()}
              title={canManage ? "Add Cover Photo" : undefined}
              style={canManage ? { cursor: 'pointer' } : undefined}
            />
          )}
          <div className="biz-hero__cover-overlay" aria-hidden="true" />

          {canManage && (
            <div className="biz-hero__cover-actions">
              {coverPhotoUrl && (
                <button
                  type="button"
                  className="media-upload-button"
                  onClick={() => handleOpenViewMedia('cover', coverPhotoUrl)}
                  aria-label="View Cover Photo"
                  title="View Cover Photo"
                >
                  <Eye size={18} aria-hidden="true" />
                  <span>View Cover</span>
                </button>
              )}

              <button
                type="button"
                className="media-upload-button"
                onClick={() => coverFileInputRef.current?.click()}
                aria-label="Change Cover Photo"
                title="Change Cover Photo"
              >
                <Camera size={18} aria-hidden="true" />
                <span>Change Cover</span>
              </button>
            </div>
          )}
        </div>

        <div className="biz-hero__body">
          <div className="biz-hero__avatar-container">
            <div
              className={`biz-hero__avatar ${canManage || profilePhotoUrl ? 'biz-hero__avatar--clickable' : ''}`}
              onClick={() => {
                if (profilePhotoUrl) {
                  handleOpenViewMedia('avatar', profilePhotoUrl);
                } else if (canManage) {
                  avatarFileInputRef.current?.click();
                }
              }}
              title={profilePhotoUrl ? "View Profile Photo" : (canManage ? "Change Profile Photo" : page.page_name)}
            >
              {profilePhotoUrl ? (
                <img src={profilePhotoUrl} alt={`${page.page_name} logo`} />
              ) : (
                <span className="biz-hero__avatar-initials">{initials}</span>
              )}

              {canManage && (
                <div
                  className="biz-hero__avatar-camera"
                  aria-label="Change profile photo"
                  title="Change profile photo"
                  onClick={(e) => {
                    e.stopPropagation();
                    avatarFileInputRef.current?.click();
                  }}
                >
                  <Camera size={18} aria-hidden="true" />
                </div>
              )}
            </div>
          </div>

          <div className="biz-hero__content">
            <div className="biz-hero__title-row">
              <div>
                <h1>
                  {page.page_name}
                  {page.is_verified && (
                    <BadgeCheck size={22} color="#20c875" title="Verified Business" aria-hidden="true" />
                  )}
                </h1>
                <span className="biz-hero__username">@{page.page_username}</span>
              </div>

              <div className="biz-hero__actions">
                {canManage ? (
                  <>
                    {isOwner ? (
                      <span className="biz-badge biz-badge--category" style={{ padding: '8px 14px', fontSize: '13px' }}>
                        <Crown size={14} aria-hidden="true" />
                        <span>Page Owner</span>
                      </span>
                    ) : (
                      <span className="biz-badge biz-badge--category" style={{ padding: '8px 14px', fontSize: '13px', background: 'rgba(79, 125, 243, 0.15)', color: '#4f7df3' }}>
                        <Shield size={14} aria-hidden="true" />
                        <span>Page Team</span>
                      </span>
                    )}

                    <Link to={`/member/business-pages/${page.slug}/analytics`} className="member-button member-button--secondary">
                      <BarChart3 size={15} color="#4f7df3" aria-hidden="true" />
                      <span>Analytics</span>
                    </Link>

                    <Link to={`/member/business-pages/${page.slug}/notifications`} className="member-button member-button--secondary">
                      <Bell size={15} color="#4f7df3" aria-hidden="true" />
                      <span>Notifications</span>
                    </Link>

                    {isOwner && (
                      <Link to={`/member/business-pages/${page.slug}/edit`} className="member-button member-button--primary">
                        <Edit3 size={15} aria-hidden="true" />
                        <span>Edit Page</span>
                      </Link>
                    )}
                  </>
                ) : (
                  <button
                    type="button"
                    className={`member-button ${followStatus === 'accepted' || followStatus === 'pending' ? 'member-button--secondary' : 'member-button--primary'}`}
                    onClick={handleToggleFollow}
                  >
                    {followStatus === 'accepted' ? (
                      <>
                        <Check size={15} color="#20c875" aria-hidden="true" />
                        <span>Following</span>
                      </>
                    ) : followStatus === 'pending' ? (
                      <>
                        <Clock size={15} color="#f7b940" aria-hidden="true" />
                        <span>Requested</span>
                      </>
                    ) : (
                      <>
                        <UserPlus size={15} aria-hidden="true" />
                        <span>Follow</span>
                      </>
                    )}
                  </button>
                )}

                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={() => setShowShareModal(true)}
                  aria-label="Share Business Page"
                >
                  <Share2 size={15} aria-hidden="true" />
                  <span>Share</span>
                </button>

                {/* More dropdown */}
                <div className="biz-dropdown" ref={dropdownRef} style={{ position: 'relative', zIndex: 50 }}>
                  <button
                    type="button"
                    className="member-button member-button--secondary"
                    onClick={(e) => {
                      e.stopPropagation();
                      setShowDropdown((prev) => !prev);
                    }}
                    aria-label="More options"
                  >
                    <MoreHorizontal size={16} aria-hidden="true" />
                  </button>
                  {showDropdown && (
                    <div
                      className="biz-dropdown-menu show"
                      style={{
                        position: 'absolute',
                        right: 0,
                        top: 'calc(100% + 6px)',
                        background: '#ffffff',
                        border: '1px solid #e7ecf4',
                        borderRadius: '14px',
                        padding: '6px 0',
                        boxShadow: '0 8px 24px rgba(34, 49, 78, 0.08)',
                        zIndex: 100,
                        minWidth: '200px',
                        display: 'block',
                      }}
                    >
                      <button
                        type="button"
                        className="biz-dropdown-item"
                        onClick={() => {
                          setShowDropdown(false);
                          setShowShareModal(true);
                        }}
                      >
                        <Share2 size={15} color="#4f7df3" />
                        <span>Share Business Page</span>
                      </button>

                      <button
                        type="button"
                        className="biz-dropdown-item"
                        onClick={() => {
                          handleCopyLink();
                          setShowDropdown(false);
                        }}
                      >
                        <Copy size={14} />
                        <span>{copiedLink ? 'Link Copied!' : 'Copy Page Link'}</span>
                      </button>

                      {page.owner && (
                        <Link
                          to={`/member/people/${page.owner.id}`}
                          className="biz-dropdown-item"
                          onClick={() => setShowDropdown(false)}
                        >
                          <User size={14} />
                          <span>View Owner Profile</span>
                        </Link>
                      )}

                      <div style={{ height: '1px', background: '#e7ecf4', margin: '4px 0' }} />

                      <div style={{ padding: '6px 16px', fontSize: '11px', color: '#98a2b3' }}>
                        Page ID: <code>{page.page_id || `biz_${page.id}`}</code>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="biz-card__badges">
              <span className="biz-badge biz-badge--category">
                <Tag size={12} aria-hidden="true" />
                <span>{page.category}</span>
              </span>
              <span className="biz-badge biz-badge--visibility">
                {page.visibility === 'private' ? (
                  <>
                    <Lock size={12} aria-hidden="true" />
                    <span>Private</span>
                  </>
                ) : page.visibility === 'draft' ? (
                  <>
                    <FileText size={12} aria-hidden="true" />
                    <span>Draft</span>
                  </>
                ) : (
                  <>
                    <Globe size={12} aria-hidden="true" />
                    <span>Public</span>
                  </>
                )}
              </span>
              {page.is_verified && (
                <span className="biz-badge biz-badge--verified">
                  <BadgeCheck size={12} aria-hidden="true" />
                  <span>Official Verified Page</span>
                </span>
              )}
            </div>

            <div className="biz-hero__meta">
              <span>
                <Users size={14} aria-hidden="true" />
                <strong>{followersCount}</strong> Followers
              </span>
              {locationText && (
                <span>
                  <MapPin size={14} aria-hidden="true" />
                  <span>{locationText}</span>
                </span>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Sponsored Campaign Landing Reward Context Banner */}
      {campaignParam && !isOwner && (
        <div
          style={{
            margin: '16px auto',
            maxWidth: '1200px',
            padding: '14px 20px',
            backgroundColor: '#ecfdf5',
            border: '1px solid #a7f3d0',
            borderRadius: '12px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: '16px',
            flexWrap: 'wrap',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flex: 1, minWidth: 'min(260px, 100%)' }}>
            <div
              style={{
                width: '38px',
                height: '38px',
                borderRadius: '10px',
                backgroundColor: '#d1fae5',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
            >
              <Gift size={20} color="#059669" />
            </div>
            <div>
              <strong style={{ fontSize: '14px', color: '#065f46', display: 'block' }}>
                {rewardSuccessMessage || (campaignRewardStatus?.already_rewarded
                  ? 'Campaign Reward Credited'
                  : isProcessingLandingReward
                  ? 'Verifying Qualifying Campaign Visit...'
                  : 'Campaign Visit Recorded')}
              </strong>
              <div style={{ fontSize: '12.5px', color: '#047857', marginTop: '2px' }}>
                {campaignRewardStatus?.already_rewarded || rewardSuccessMessage
                  ? 'You have received the one-time $0.05 USD reward for this campaign in your verified Member Reward Wallet.'
                  : isProcessingLandingReward
                  ? 'Please wait while your qualifying campaign landing visit is verified...'
                  : 'Qualifying campaign visit has been tracked.'}
              </div>
            </div>
          </div>
          {(campaignRewardStatus?.already_rewarded || rewardSuccessMessage) && (
            <span
              style={{
                backgroundColor: '#059669',
                color: '#ffffff',
                padding: '7px 14px',
                borderRadius: '8px',
                fontSize: '12.5px',
                fontWeight: 700,
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
              }}
            >
              <Check size={14} color="#ffffff" />
              <span>Rewarded $0.05</span>
            </span>
          )}
        </div>
      )}

      {/* Profile Navigation Tabs */}
      <nav className="biz-nav-tabs" aria-label="Business Profile Tabs">
        <button
          type="button"
          className={`biz-nav-tab ${activeTab === 'home' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('home')}
        >
          <Home size={15} aria-hidden="true" />
          <span>Home</span>
        </button>
        <button
          type="button"
          className={`biz-nav-tab ${activeTab === 'about' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('about')}
        >
          <Info size={15} aria-hidden="true" />
          <span>About</span>
        </button>
        <button
          type="button"
          className={`biz-nav-tab ${activeTab === 'photos' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('photos')}
        >
          <ImageIcon size={15} aria-hidden="true" />
          <span>Photos</span>
        </button>
        <button
          type="button"
          className={`biz-nav-tab ${activeTab === 'videos' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('videos')}
        >
          <Video size={15} aria-hidden="true" />
          <span>Videos</span>
        </button>
        <button
          type="button"
          className={`biz-nav-tab ${activeTab === 'followers' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('followers')}
        >
          <Users size={15} aria-hidden="true" />
          <span>Followers ({followersCount})</span>
        </button>
        {/* Reviews tab disabled from active Member navigation per Phase 7 requirements (backend review history preserved) */}
        {/* <button
          type="button"
          className={`biz-nav-tab ${activeTab === 'reviews' ? 'is-active' : ''}`}
          onClick={() => handleTabChange('reviews')}
        >
          <Star size={15} aria-hidden="true" />
          <span>Reviews ({page.reviews_count || 0})</span>
        </button> */}
        {(isOwner || Boolean(data.is_admin)) && (
          <button
            type="button"
            className={`biz-nav-tab ${activeTab === 'add-fund' || activeTab === 'funds' ? 'is-active' : ''}`}
            onClick={() => handleTabChange('add-fund')}
          >
            <Wallet size={15} aria-hidden="true" />
            <span>Add Fund</span>
          </button>
        )}
        {(isOwner || Boolean(data.is_admin)) && (
          <button
            type="button"
            className={`biz-nav-tab ${activeTab === 'ads' ? 'is-active' : ''}`}
            onClick={() => handleTabChange('ads')}
          >
            <Megaphone size={15} aria-hidden="true" />
            <span>Ads</span>
          </button>
        )}
      </nav>

      {/* Main Content Layout */}
      <div className="biz-layout" style={{ marginTop: '24px' }}>
        {activeTab === 'home' && (
          <div className="biz-detail-grid">
            {/* Timeline Feed Column */}
            <div className="biz-detail-main">
              {/* Post Composer for Owner / Authorized Team */}
              {canManage && (
                <div style={{ marginBottom: '4px' }}>
                  <PostComposer
                    currentUser={currentUser}
                    businessPage={page}
                    onPostCreated={handlePostCreated}
                    placeholder={`Publish an update for ${page.page_name}...`}
                    allowVideo={true}
                  />
                </div>
              )}

              {/* Pinned Post */}
              {pinnedPost && (
                <div style={{ position: 'relative' }}>
                  <div style={{ position: 'absolute', top: '-10px', right: '16px', zIndex: 10, background: '#4f7df3', color: '#fff', fontSize: '11px', fontWeight: 700, padding: '2px 8px', borderRadius: '10px' }}>
                    Pinned Post
                  </div>
                  <PostCard
                    post={pinnedPost}
                    currentUser={currentUser}
                    onRunAd={isOwner || Boolean(data.is_admin) ? (p) => handleOpenRunAd(p) : undefined}
                    onPostDeleted={() => fetchPageDetail().then((res) => res && setData(res))}
                  />
                </div>
              )}

              {/* Timeline Posts */}
              {timelinePosts.length === 0 && !pinnedPost ? (
                <div className="card" style={{ padding: '40px 20px', textAlign: 'center', borderRadius: '16px' }}>
                  <FileText size={36} color="#94a3b8" style={{ margin: '0 auto 12px' }} />
                  <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 6px 0' }}>No Posts Yet</h3>
                  <p style={{ color: 'var(--color-text-secondary)', margin: 0, fontSize: '13.5px' }}>
                    Follow this page to see updates, announcements, and media in your feed.
                  </p>
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                  {timelinePosts.map((post) => (
                    <PostCard
                      key={post.id}
                      post={post}
                      currentUser={currentUser}
                      onRunAd={isOwner || Boolean(data.is_admin) ? (p) => handleOpenRunAd(p) : undefined}
                      onPostDeleted={() => fetchPageDetail().then((res) => res && setData(res))}
                    />
                  ))}
                </div>
              )}
            </div>

            {/* Sidebar Column */}
            <div className="biz-detail-sidebar">
              {/* About Summary Card */}
              <div className="card" style={{ padding: '20px', borderRadius: '16px' }}>
                <h3 style={{ fontSize: '16px', fontWeight: 800, margin: '0 0 12px 0' }}>About</h3>
                {page.description && (
                  <p style={{ fontSize: '13.5px', color: 'var(--color-text-main)', lineHeight: 1.5, margin: '0 0 16px 0', overflowWrap: 'anywhere', wordBreak: 'break-word' }}>
                    {page.description}
                  </p>
                )}

                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', fontSize: '13px', color: 'var(--color-text-secondary)' }}>
                  {locationText && (
                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', minWidth: 0 }}>
                      <MapPin size={15} color="#ef4444" style={{ flexShrink: 0, marginTop: '2px' }} />
                      <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.address ? `${page.address}, ${locationText}` : locationText}</span>
                    </div>
                  )}
                  {page.website && (
                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', minWidth: 0 }}>
                      <ExternalLink size={15} color="#4f7df3" style={{ flexShrink: 0, marginTop: '2px' }} />
                      <a href={page.website} target="_blank" rel="noopener noreferrer" style={{ color: '#4f7df3', textDecoration: 'none', minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>
                        {page.website.replace(/^https?:\/\//, '')}
                      </a>
                    </div>
                  )}
                  {page.email && (
                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', minWidth: 0 }}>
                      <Mail size={15} color="#10b981" style={{ flexShrink: 0, marginTop: '2px' }} />
                      <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.email}</span>
                    </div>
                  )}
                  {page.phone && (
                    <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', minWidth: 0 }}>
                      <Phone size={15} color="#8b5cf6" style={{ flexShrink: 0, marginTop: '2px' }} />
                      <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.phone}</span>
                    </div>
                  )}
                  <div style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', minWidth: 0 }}>
                    <Calendar size={15} color="#64748b" style={{ flexShrink: 0, marginTop: '2px' }} />
                    <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>Created {formatDate(page.created_at)}</span>
                  </div>
                </div>
              </div>

              {/* Owner Info Card */}
              {page.owner && (
                <div className="card" style={{ padding: '20px', borderRadius: '16px' }}>
                  <h3 style={{ fontSize: '15px', fontWeight: 800, margin: '0 0 12px 0' }}>Page Owner</h3>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px', minWidth: 0 }}>
                    <Link to={`/member/people/${page.owner.id}`} style={{ display: 'inline-flex', flexShrink: 0, textDecoration: 'none' }}>
                      <MemberAvatar
                        member={page.owner}
                        size={44}
                      />
                    </Link>
                    <div style={{ minWidth: 0, overflow: 'hidden' }}>
                      <Link to={`/member/people/${page.owner.id}`} style={{ fontWeight: 700, fontSize: '14px', color: 'inherit', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '4px', maxWidth: '100%' }}>
                        <span style={{ overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.owner.name}</span>
                        <VerifiedBadge member={page.owner} size={14} />
                      </Link>
                      {page.owner.user_id && (
                        <small style={{ color: 'var(--color-text-secondary)', display: 'block', overflowWrap: 'anywhere', wordBreak: 'break-all' }}>@{page.owner.user_id}</small>
                      )}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}

        {activeTab === 'about' && (
          <div className="card" style={{ padding: '28px', borderRadius: '18px' }}>
            <h2 style={{ fontSize: '20px', fontWeight: 800, margin: '0 0 16px 0' }}>About {page.page_name}</h2>
            <div style={{ fontSize: '14.5px', lineHeight: 1.6, color: 'var(--color-text-main)', whiteSpace: 'pre-line', marginBottom: '24px' }}>
              {page.description}
            </div>

            <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '0 0 14px 0', borderTop: '1px solid #e7ecf4', paddingTop: '18px' }}>
              Contact & Social Information
            </h3>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(240px, 100%), 1fr))', gap: '16px', fontSize: '13.5px' }}>
              {page.email && (
                <div style={{ minWidth: 0 }}>
                  <strong style={{ display: 'block', color: 'var(--color-text-secondary)', fontSize: '12px' }}>Email</strong>
                  <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.email}</span>
                </div>
              )}
              {page.phone && (
                <div style={{ minWidth: 0 }}>
                  <strong style={{ display: 'block', color: 'var(--color-text-secondary)', fontSize: '12px' }}>Phone</strong>
                  <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.phone}</span>
                </div>
              )}
              {page.website && (
                <div style={{ minWidth: 0 }}>
                  <strong style={{ display: 'block', color: 'var(--color-text-secondary)', fontSize: '12px' }}>Website</strong>
                  <a href={page.website} target="_blank" rel="noopener noreferrer" style={{ color: '#4f7df3', minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>
                    {page.website}
                  </a>
                </div>
              )}
              {locationText && (
                <div style={{ minWidth: 0 }}>
                  <strong style={{ display: 'block', color: 'var(--color-text-secondary)', fontSize: '12px' }}>Address</strong>
                  <span style={{ minWidth: 0, overflowWrap: 'anywhere', wordBreak: 'break-word' }}>{page.address ? `${page.address}, ${locationText}` : locationText}</span>
                </div>
              )}
            </div>

            {Object.keys(social).some((k) => social[k]) && (
              <>
                <h3 style={{ fontSize: '16px', fontWeight: 700, margin: '20px 0 14px 0', borderTop: '1px solid #e7ecf4', paddingTop: '18px' }}>
                  Social Links
                </h3>
                <div style={{ display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
                  {social.facebook && (
                    <a href={social.facebook} target="_blank" rel="noopener noreferrer" className="member-button member-button--secondary">
                      Facebook
                    </a>
                  )}
                  {social.twitter && (
                    <a href={social.twitter} target="_blank" rel="noopener noreferrer" className="member-button member-button--secondary">
                      Twitter
                    </a>
                  )}
                  {social.instagram && (
                    <a href={social.instagram} target="_blank" rel="noopener noreferrer" className="member-button member-button--secondary">
                      Instagram
                    </a>
                  )}
                  {social.linkedin && (
                    <a href={social.linkedin} target="_blank" rel="noopener noreferrer" className="member-button member-button--secondary">
                      LinkedIn
                    </a>
                  )}
                  {social.youtube && (
                    <a href={social.youtube} target="_blank" rel="noopener noreferrer" className="member-button member-button--secondary">
                      YouTube
                    </a>
                  )}
                </div>
              </>
            )}
          </div>
        )}

        {activeTab === 'photos' && (
          <div className="card" style={{ padding: '24px', borderRadius: '18px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 16px 0' }}>Photos Gallery</h2>
            {photos.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px', color: 'var(--color-text-secondary)' }}>
                No photos uploaded to this business page timeline yet.
              </div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: '16px' }}>
                {photos.map((p) => (
                  <img
                    key={p.id}
                    src={getMediaUrl(p.media_path)}
                    alt="Business gallery"
                    style={{ width: '100%', height: '160px', objectFit: 'cover', borderRadius: '12px' }}
                    loading="lazy"
                  />
                ))}
              </div>
            )}
          </div>
        )}

        {activeTab === 'videos' && (
          <div className="card" style={{ padding: '24px', borderRadius: '18px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 16px 0' }}>Videos Gallery</h2>
            {videos.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px', color: 'var(--color-text-secondary)' }}>
                No videos uploaded to this business page timeline yet.
              </div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: '16px' }}>
                {videos.map((v) => (
                  <video
                    key={v.id}
                    src={getMediaUrl(v.media_path)}
                    controls
                    style={{ width: '100%', height: '180px', objectFit: 'cover', borderRadius: '12px', background: '#000' }}
                  />
                ))}
              </div>
            )}
          </div>
        )}

        {activeTab === 'followers' && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
            {/* Follow Requests Section for Admins/Owners on Private Pages */}
            {canManage && pendingFollowRequests.length > 0 && (
              <div className="card" style={{ padding: '24px', borderRadius: '18px', border: '1px solid #fef08a', background: '#fefce8' }}>
                <h3 style={{ fontSize: '16px', fontWeight: 800, margin: '0 0 14px 0', color: '#854d0e', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Clock size={18} color="#ca8a04" />
                  <span>Pending Follow Requests ({pendingFollowRequests.length})</span>
                </h3>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(280px, 100%), 1fr))', gap: '12px' }}>
                  {pendingFollowRequests.map((req) => {
                    const m = req.member || {};
                    return (
                      <div
                        key={req.id}
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          gap: '12px',
                          padding: '12px',
                          background: '#fff',
                          borderRadius: '12px',
                          border: '1px solid #fef08a',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                          <Link to={`/member/people/${m.id}`} style={{ display: 'inline-flex', flexShrink: 0, textDecoration: 'none' }}>
                            <MemberAvatar
                              member={m}
                              size={36}
                            />
                          </Link>
                          <div>
                            <strong style={{ fontSize: '13px', display: 'block', color: '#1d2738' }}>{m.name}</strong>
                            <small style={{ color: '#64748b' }}>@{m.user_id || 'user'}</small>
                          </div>
                        </div>

                        <div style={{ display: 'flex', gap: '6px' }}>
                          <button
                            type="button"
                            className="member-button member-button--primary"
                            style={{ padding: '4px 10px', fontSize: '12px' }}
                            onClick={() => handleApproveFollowRequest(req.id)}
                          >
                            <UserCheck size={12} />
                            <span>Accept</span>
                          </button>
                          <button
                            type="button"
                            className="member-button member-button--secondary"
                            style={{ padding: '4px 8px', fontSize: '12px' }}
                            onClick={() => handleDeclineFollowRequest(req.id)}
                          >
                            <X size={12} />
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Followers List Card */}
            <div className="card" style={{ padding: '24px', borderRadius: '18px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px', flexWrap: 'wrap', gap: '12px' }}>
                <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0 }}>Followers ({followersCount})</h2>
                <div style={{ display: 'flex', gap: '10px', alignItems: 'center', flexWrap: 'wrap' }}>
                  {canManage && (
                    <button
                      type="button"
                      className="member-button member-button--secondary"
                      onClick={handleOpenInviteFollow}
                      style={{ padding: '6px 12px', fontSize: '13px' }}
                    >
                      <UserPlus size={14} />
                      <span>Invite Friends</span>
                    </button>
                  )}
                  <input
                    type="text"
                    placeholder="Search followers..."
                    value={followerSearch}
                    onChange={(e) => setFollowerSearch(e.target.value)}
                    className="biz-search-input"
                    style={{ width: '160px', padding: '6px 12px', fontSize: '13px' }}
                  />
                  <select
                    value={followerSort}
                    onChange={(e) => setFollowerSort(e.target.value)}
                    className="biz-filter-select"
                    style={{ padding: '6px 12px', fontSize: '13px' }}
                  >
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="alphabetical">A-Z</option>
                  </select>
                </div>
              </div>

              {followersList.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '40px', color: 'var(--color-text-secondary)' }}>
                  No followers found for this page.
                </div>
              ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(min(260px, 100%), 1fr))', gap: '16px' }}>
                  {followersList.map((f) => {
                    const m = f.member;
                    if (!m) return null;
                    return (
                      <div
                        key={f.id}
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          gap: '12px',
                          padding: '12px',
                          background: '#f8fafc',
                          borderRadius: '12px',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                          <Link to={`/member/people/${m.id}`} style={{ display: 'inline-flex', flexShrink: 0, textDecoration: 'none' }}>
                            <MemberAvatar
                              member={m}
                              size={40}
                            />
                          </Link>
                          <div>
                            <Link to={`/member/people/${m.id}`} style={{ fontWeight: 700, fontSize: '13.5px', color: 'inherit', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}>
                              <span>{m.name}</span>
                              <VerifiedBadge member={m} size={13} />
                            </Link>
                            {m.user_id && <small style={{ color: '#64748b', display: 'block' }}>@{m.user_id}</small>}
                          </div>
                        </div>

                        {canManage && (
                          <button
                            type="button"
                            className="member-button member-button--danger"
                            style={{ padding: '4px 8px', fontSize: '12px' }}
                            title="Remove follower"
                            onClick={() => handleRemoveFollower(f.id, m.name)}
                          >
                            <UserMinus size={13} />
                          </button>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        )}

        {activeTab === 'reviews' && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
            {/* Overall Ratings Summary Card */}
            <div className="card" style={{ padding: '24px', borderRadius: '18px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '16px', flexWrap: 'wrap' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
                  <div style={{ fontSize: '42px', fontWeight: 800, color: '#1d2738', lineHeight: 1 }}>
                    {averageRating.toFixed(1)}
                  </div>
                  <div>
                    <div style={{ display: 'flex', gap: '2px', color: '#f7b940' }}>
                      {[1, 2, 3, 4, 5].map((star) => (
                        <Star
                          key={star}
                          size={16}
                          color="#f7b940"
                          fill={star <= Math.round(averageRating) ? '#f7b940' : 'none'}
                        />
                      ))}
                    </div>
                    <span style={{ fontSize: '13px', color: '#687386', marginTop: '2px', display: 'block' }}>
                      Based on {reviewsCount} {reviewsCount === 1 ? 'review' : 'reviews'} • <strong style={{ color: '#4f7df3' }}>{recommendationBadge}</strong>
                    </span>
                  </div>
                </div>

                <div>
                  {userReview ? (
                    <button
                      type="button"
                      className="member-button member-button--secondary"
                      onClick={() => handleOpenWriteReview(true)}
                    >
                      <Edit3 size={15} />
                      <span>Edit Your Review</span>
                    </button>
                  ) : isFollowing && !isOwner ? (
                    <button
                      type="button"
                      className="member-button member-button--primary"
                      onClick={() => handleOpenWriteReview(false)}
                    >
                      <Star size={15} />
                      <span>Write a Review</span>
                    </button>
                  ) : isOwner ? (
                    <span style={{ fontSize: '12.5px', color: '#98a2b3', fontStyle: 'italic' }}>
                      Page owners cannot review their own page.
                    </span>
                  ) : (
                    <button
                      type="button"
                      className="member-button member-button--disabled"
                      disabled
                      title="Follow this business page to write a review"
                    >
                      <Lock size={15} />
                      <span>Follow to Write Review</span>
                    </button>
                  )}
                </div>
              </div>

              {/* Rating Distribution Bars */}
              <div
                style={{
                  marginTop: '20px',
                  paddingTop: '16px',
                  borderTop: '1px solid #e7ecf4',
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(min(160px, 100%), 1fr))',
                  gap: '10px',
                }}
              >
                {[5, 4, 3, 2, 1].map((star) => {
                  const cnt = ratingDistribution[star] ?? 0;
                  const totalRev = Math.max(1, reviewsCount);
                  const pct = Math.round((cnt / totalRev) * 100);
                  return (
                    <div key={star} style={{ display: 'flex', alignItems: 'center', gap: '8px', fontSize: '12px', color: '#687386' }}>
                      <span style={{ width: '45px' }}>{star} Stars</span>
                      <div style={{ flex: 1, height: '8px', borderRadius: '4px', background: '#edf3ff', overflow: 'hidden' }}>
                        <div style={{ height: '100%', width: `${pct}%`, background: '#f7b940', borderRadius: '4px' }} />
                      </div>
                      <span style={{ width: '30px', textAlign: 'right', fontWeight: 600 }}>{cnt}</span>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Search & Filter Bar */}
            <div className="card" style={{ padding: '20px 24px', borderRadius: '18px' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '16px', flexWrap: 'wrap' }}>
                <div style={{ flex: 1, minWidth: 'min(200px, 100%)' }}>
                  <input
                    type="text"
                    value={reviewSearch}
                    onChange={(e) => setReviewSearch(e.target.value)}
                    className="biz-search-input"
                    placeholder="Search reviews by keyword or author..."
                    style={{ width: '100%' }}
                  />
                </div>
                <select
                  value={reviewSort}
                  onChange={(e) => setReviewSort(e.target.value)}
                  className="biz-filter-select"
                >
                  <option value="newest">Newest Reviews</option>
                  <option value="highest">Highest Rating</option>
                  <option value="lowest">Lowest Rating</option>
                  <option value="recommended">Recommended First</option>
                  <option value="photos">With Photos Only</option>
                </select>
              </div>

              {/* Reviews List */}
              <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', marginTop: '20px' }}>
                {reviewsList.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '40px', color: 'var(--color-text-secondary)' }}>
                    No customer reviews found matching your search.
                  </div>
                ) : (
                  reviewsList.map((rv) => (
                    <BusinessReviewCard
                      key={rv.id}
                      review={rv}
                      currentMemberId={currentUser?.id}
                      pageName={page.page_name}
                      isAdmin={Boolean(data.is_admin || isOwner)}
                      onVote={handleVoteReview}
                      onEdit={() => handleOpenWriteReview(true)}
                      onDelete={handleDeleteReview}
                      onReply={handleOpenReply}
                      onReport={handleOpenReport}
                      onToggleHide={handleToggleHideReview}
                    />
                  ))
                )}
              </div>
            </div>
          </div>
        )}

        {(activeTab === 'add-fund' || activeTab === 'funds') && (isOwner || Boolean(data.is_admin)) && (
          <AddFundView page={page} />
        )}

        {activeTab === 'ads' && (isOwner || Boolean(data.is_admin)) && (
          <BusinessAdCampaignsList
            page={page}
            availablePosts={timelinePosts}
            isOwner={isOwner}
            isTeamAdmin={Boolean(data.is_admin || isOwner)}
          />
        )}
      </div>

      {/* Write / Edit Review Modal */}
      {isWriteReviewModalOpen && (
        <ModalPortal isOpen={isWriteReviewModalOpen} onClose={handleCloseWriteReview}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="write-review-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '520px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Star size={18} color="#f7b940" />
                <span>{reviewEditMode ? 'Edit Your Business Review' : 'Write a Business Review'}</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={handleCloseWriteReview}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSubmitReview} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {reviewError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {reviewError}
                </div>
              )}

              {/* Star Rating Picker */}
              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '8px', display: 'block' }}>
                  Your Overall Rating
                </label>
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                  {[1, 2, 3, 4, 5].map((val) => (
                    <button
                      key={val}
                      type="button"
                      onClick={() => setReviewRating(val)}
                      style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: 0 }}
                    >
                      <Star
                        size={28}
                        color="#f7b940"
                        fill={val <= reviewRating ? '#f7b940' : 'none'}
                      />
                    </button>
                  ))}
                  <span style={{ fontSize: '14px', fontWeight: 700, color: '#1d2738', marginLeft: '8px' }}>
                    {reviewRating} of 5 Stars
                  </span>
                </div>
              </div>

              {/* Recommendation Radio Selector */}
              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '8px', display: 'block' }}>
                  Do you recommend this business?
                </label>
                <div style={{ display: 'flex', gap: '16px' }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '6px', cursor: 'pointer', fontSize: '13.5px' }}>
                    <input
                      type="radio"
                      name="recommendation"
                      value="recommend"
                      checked={reviewRecommendation === 'recommend'}
                      onChange={() => setReviewRecommendation('recommend')}
                    />
                    <ThumbsUp size={14} color="#20c875" />
                    <span>Recommend</span>
                  </label>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '6px', cursor: 'pointer', fontSize: '13.5px' }}>
                    <input
                      type="radio"
                      name="recommendation"
                      value="not_recommend"
                      checked={reviewRecommendation === 'not_recommend'}
                      onChange={() => setReviewRecommendation('not_recommend')}
                    />
                    <ThumbsDown size={14} color="#ef4444" />
                    <span>Do Not Recommend</span>
                  </label>
                </div>
              </div>

              {/* Title Input */}
              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Headline / Title (Optional)
                </label>
                <input
                  type="text"
                  value={reviewTitle}
                  onChange={(e) => setReviewTitle(e.target.value)}
                  placeholder="Summarize your experience..."
                  className="biz-search-input"
                  maxLength={255}
                />
              </div>

              {/* Body Textarea */}
              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Detailed Review
                </label>
                <textarea
                  value={reviewBody}
                  onChange={(e) => setReviewBody(e.target.value)}
                  rows={4}
                  className="biz-search-input"
                  style={{ height: 'auto', padding: '12px' }}
                  placeholder="Tell us about product quality, customer service, or your overall feedback..."
                  required
                  maxLength={5000}
                />
              </div>

              {/* Photos Attachment (Only on creation) */}
              {!reviewEditMode && (
                <div className="form-group">
                  <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                    Attach Photos (Optional)
                  </label>
                  <input
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    onChange={(e) => setReviewPhotos(e.target.files)}
                    className="biz-search-input"
                    style={{ padding: '8px' }}
                  />
                </div>
              )}

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '10px' }}>
                <button type="button" className="member-button member-button--secondary" onClick={handleCloseWriteReview}>
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isSubmittingReview || !reviewBody.trim()}
                >
                  <Send size={14} />
                  <span>{isSubmittingReview ? 'Submitting...' : reviewEditMode ? 'Update Review' : 'Publish Review'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}

      {/* Official Reply Modal */}
      {isReplyModalOpen && replyingReview && (
        <ModalPortal isOpen={isReplyModalOpen && Boolean(replyingReview)} onClose={handleCloseReply}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="reply-review-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '480px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 id="reply-review-modal-title" style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Building2 size={18} color="#4f7df3" />
                <span>Official Reply from {page.page_name}</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={handleCloseReply}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSubmitReply} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {replyError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {replyError}
                </div>
              )}

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Official Response
                </label>
                <textarea
                  value={replyText}
                  onChange={(e) => setReplyText(e.target.value)}
                  rows={4}
                  className="biz-search-input"
                  style={{ height: 'auto', padding: '12px' }}
                  placeholder="Thank the customer or clarify details officially as the business owner..."
                  required
                  maxLength={3000}
                />
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '10px' }}>
                <button type="button" className="member-button member-button--secondary" onClick={handleCloseReply}>
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isSubmittingReply || !replyText.trim()}
                >
                  <span>{isSubmittingReply ? 'Publishing...' : 'Publish Reply'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}

      {/* Report Review Modal */}
      {isReportModalOpen && (
        <ModalPortal isOpen={isReportModalOpen} onClose={handleCloseReport}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="report-review-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '460px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 id="report-review-modal-title" style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Flag size={18} color="#ef4444" />
                <span>Report Review</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={handleCloseReport}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSubmitReport} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {reportError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {reportError}
                </div>
              )}

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Reason for Report
                </label>
                <select
                  value={reportReason}
                  onChange={(e) => setReportReason(e.target.value)}
                  className="biz-filter-select"
                  style={{ width: '100%' }}
                  required
                >
                  <option value="spam">Spam / Advertisement</option>
                  <option value="fake_review">Fake / Misleading Review</option>
                  <option value="harassment">Harassment or Hate Speech</option>
                  <option value="offensive">Offensive / Inappropriate Content</option>
                  <option value="other">Other Violation</option>
                </select>
              </div>

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Additional Details (Optional)
                </label>
                <textarea
                  value={reportDetails}
                  onChange={(e) => setReportDetails(e.target.value)}
                  rows={3}
                  className="biz-search-input"
                  style={{ height: 'auto', padding: '10px' }}
                  placeholder="Provide details to assist the moderation team..."
                  maxLength={1000}
                />
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '10px' }}>
                <button type="button" className="member-button member-button--secondary" onClick={handleCloseReport}>
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--danger"
                  disabled={isSubmittingReport}
                >
                  <span>{isSubmittingReport ? 'Submitting...' : 'Submit Report'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}

      {/* Invite Friends to Follow Modal */}
      {isInviteFollowModalOpen && (
        <ModalPortal isOpen={isInviteFollowModalOpen} onClose={handleCloseInviteFollow}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invite-follow-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '440px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 id="invite-follow-modal-title" style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <UserPlus size={18} color="#4f7df3" />
                <span>Invite Friends to Follow</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={handleCloseInviteFollow}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSendInviteFollow} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {inviteFollowError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {inviteFollowError}
                </div>
              )}

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Select Connection / Friend
                </label>
                {friends.length > 0 ? (
                  <select
                    value={inviteFollowId}
                    onChange={(e) => setInviteFollowId(e.target.value)}
                    className="biz-filter-select"
                    style={{ width: '100%' }}
                    required
                  >
                    <option value="">Choose a friend to invite...</option>
                    {friends.map((fr) => (
                      <option key={fr.id} value={fr.id}>
                        {fr.name} (@{fr.user_id || 'user'})
                      </option>
                    ))}
                  </select>
                ) : (
                  <input
                    type="number"
                    value={inviteFollowId}
                    onChange={(e) => setInviteFollowId(e.target.value)}
                    placeholder="Enter Member ID..."
                    className="biz-search-input"
                    required
                  />
                )}
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '12px' }}>
                <button type="button" className="member-button member-button--secondary" onClick={handleCloseInviteFollow}>
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isInvitingFollow || !inviteFollowId}
                >
                  <Send size={14} />
                  <span>{isInvitingFollow ? 'Sending...' : 'Send Invitation'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}
      {/* Send Message to Business Page Modal (For Customers) */}
      {isCustomerMessageModalOpen && (
        <ModalPortal isOpen={isCustomerMessageModalOpen} onClose={() => setIsCustomerMessageModalOpen(false)}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="customer-message-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '480px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 id="customer-message-modal-title" style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <MessageSquare size={18} color="#4f7df3" />
                <span>Message {page.page_name}</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={() => setIsCustomerMessageModalOpen(false)}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <form onSubmit={handleSendCustomerMessage} style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
              {customerMessageError && (
                <div style={{ padding: '10px 14px', background: '#fee2e2', color: '#b91c1c', borderRadius: '10px', fontSize: '13px' }}>
                  {customerMessageError}
                </div>
              )}

              <div className="form-group">
                <label style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '6px', display: 'block' }}>
                  Your Message
                </label>
                <textarea
                  value={customerMessageText}
                  onChange={(e) => setCustomerMessageText(e.target.value)}
                  rows={4}
                  className="biz-search-input"
                  style={{ height: 'auto', padding: '12px' }}
                  placeholder="Ask a question about products, services, pricing, or support..."
                  required
                  maxLength={5000}
                />
              </div>

              <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '10px' }}>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={() => setIsCustomerMessageModalOpen(false)}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="member-button member-button--primary"
                  disabled={isSendingCustomerMessage || !customerMessageText.trim()}
                >
                  <Send size={14} />
                  <span>{isSendingCustomerMessage ? 'Sending...' : 'Send Message'}</span>
                </button>
              </div>
            </form>
          </div>
        </ModalPortal>
      )}

      {/* Business Page Share Modal */}
      {showShareModal && (
        <ShareModal
          isOpen={showShareModal}
          onClose={() => setShowShareModal(false)}
          businessPage={page}
          entityType="business_page"
        />
      )}

      {/* Create Ad Campaign Modal */}
      {showRunAdModal && (
        <CreateAdCampaignModal
          page={page}
          initialPost={targetPostForAd}
          availablePosts={timelinePosts}
          hasPageContent={Boolean(targetPostForAd) || (timelinePosts && timelinePosts.length > 0) || (Number(data?.audience_counters?.posts || 0) > 0)}
          availableAdFunds={Number(currentUser?.ad_balance ?? 0.00)}
          onClose={() => {
            setShowRunAdModal(false);
            setTargetPostForAd(null);
          }}
          onCampaignCreated={() => {
            handleTabChange('ads');
          }}
        />
      )}

      {/* Hidden File Inputs for Owner/Admin */}
      {canManage && (
        <>
          <input
            ref={avatarFileInputRef}
            type="file"
            accept="image/jpeg,image/png,image/webp,image/jpg"
            onChange={(e) => handleFileChange(e, 'avatar')}
            style={{ display: 'none' }}
          />
          <input
            ref={coverFileInputRef}
            type="file"
            accept="image/jpeg,image/png,image/webp,image/jpg"
            onChange={(e) => handleFileChange(e, 'cover')}
            style={{ display: 'none' }}
          />
        </>
      )}

      {/* Adjust Profile Photo / Cover Photo Crop & Preview Modal */}
      <ProfileImageAdjustModal
        isOpen={Boolean(photoModalType && selectedFile)}
        type={photoModalType || 'avatar'}
        file={selectedFile}
        onSave={handleSaveAdjustedPhoto}
        onCancel={handleClosePhotoModal}
        onRemove={() => handleRemovePhoto(photoModalType)}
        hasExistingPhoto={photoModalType === 'avatar' ? Boolean(page.logo) : Boolean(page.cover_photo)}
        isUploading={isUploadingPhoto}
        errorMessage={photoError}
      />

      {/* View Profile Photo / Cover Photo Lightbox Modal */}
      <ProfileMediaViewerModal
        isOpen={Boolean(viewMedia)}
        type={viewMedia?.type || 'avatar'}
        imageUrl={viewMedia?.url}
        memberName={page.page_name || 'Business Page'}
        onClose={handleCloseViewMedia}
        onChangePhoto={canManage ? () => {
          if (viewMedia?.type === 'cover') {
            coverFileInputRef.current?.click();
          } else {
            avatarFileInputRef.current?.click();
          }
        } : null}
      />

      {showVerifyModal && (
        <AccountVerificationModal
          isOpen={showVerifyModal}
          promptMessage={verifyPromptMessage}
          onClose={() => setShowVerifyModal(false)}
          onVerified={() => {
            setShowVerifyModal(false);
          }}
        />
      )}
    </div>
  );
}

export default BusinessDetailPage;
