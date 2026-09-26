import { useState, useEffect, useCallback, useRef, useMemo } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  Calendar,
  MapPin,
  Video,
  Tag,
  Globe,
  Lock,
  CheckCircle2,
  Star,
  Pencil,
  Share2,
  Trash2,
  Info,
  ExternalLink,
  Map,
  MessageSquare,
  Image as ImageIcon,
  Send,
  ShieldCheck,
  Users,
  User,
  ArrowLeft,
  UserPlus,
  X,
  Check,
  UsersRound,
  Wallet,
  Play,
  Pause as PauseIcon,
  Gift,
  Megaphone,
  Plus,
} from 'lucide-react';
import eventApi from '../../api/eventApi';
import friendApi from '../../api/friendApi';
import useAuth from '../../hooks/useAuth';
import PostCard from '../../components/posts/PostCard';
import { getAvatarUrl, getInitials } from '../../utils/assetHelper';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import AddFundsToEventCampaignModal from '../../components/events/AddFundsToEventCampaignModal';
import PaidEventQualificationModal from '../../components/events/PaidEventQualificationModal';

import { AddFundModal } from '../../components/business/ads/AddFundModal';
import { ModalPortal } from '../../components/common/ModalPortal';

function formatEventScheduleDate(dateString, timeString, endDateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  let formatted = date.toLocaleDateString('en-US', options);

  if (timeString) {
    try {
      const [hours, minutes] = timeString.split(':');
      const h = parseInt(hours, 10);
      const ampm = h >= 12 ? 'PM' : 'AM';
      const formattedHours = h % 12 || 12;
      formatted += ` at ${formattedHours}:${minutes} ${ampm}`;
    } catch {
      // Ignore
    }
  }

  if (endDateString && endDateString !== dateString) {
    const endDate = new Date(endDateString);
    formatted += ` - ${endDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`;
  }

  return formatted;
}

function getCampaignStatusConfig(campaign) {
  if (!campaign) return null;
  const isExhausted = campaign.status === 'stopped' || campaign.status === 'budget_exhausted' || Boolean(campaign.is_exhausted);
  const isLowBudget = Boolean(campaign.is_low_budget);

  if (isExhausted) {
    return {
      label: 'EXHAUSTED',
      bg: '#fee2e2',
      color: '#b91c1c',
      border: '#fca5a5',
      dot: '#ef4444',
    };
  }

  if (isLowBudget) {
    return {
      label: 'LOW BUDGET',
      bg: '#fef3c7',
      color: '#b45309',
      border: '#fde68a',
      dot: '#f59e0b',
    };
  }

  if (campaign.status === 'active') {
    return {
      label: 'ACTIVE',
      bg: '#dcfce7',
      color: '#15803d',
      border: '#86efac',
      dot: '#22c55e',
    };
  }

  if (campaign.status === 'paused') {
    return {
      label: 'PAUSED',
      bg: '#fef3c7',
      color: '#b45309',
      border: '#fde68a',
      dot: '#f59e0b',
    };
  }

  if (campaign.status === 'draft') {
    return {
      label: 'DRAFT',
      bg: '#f1f5f9',
      color: '#475569',
      border: '#cbd5e1',
      dot: '#94a3b8',
    };
  }

  if (campaign.status === 'pending_review') {
    return {
      label: 'PENDING REVIEW',
      bg: '#fef3c7',
      color: '#b45309',
      border: '#fde68a',
      dot: '#f59e0b',
    };
  }

  if (campaign.status === 'approved') {
    return {
      label: 'APPROVED',
      bg: '#dcfce7',
      color: '#15803d',
      border: '#86efac',
      dot: '#22c55e',
    };
  }

  if (campaign.status === 'completed') {
    return {
      label: 'COMPLETED',
      bg: '#f1f5f9',
      color: '#475569',
      border: '#cbd5e1',
      dot: '#94a3b8',
    };
  }

  const rawLabel = campaign.display_status || campaign.status || 'UNKNOWN';
  return {
    label: String(rawLabel).toUpperCase(),
    bg: '#f1f5f9',
    color: '#475569',
    border: '#cbd5e1',
    dot: '#94a3b8',
  };
}

export function EventDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();

  const [data, setData] = useState(null);
  const [postsPage, setPostsPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [copiedShare, setCopiedShare] = useState(false);

  // Screen-aware layout breakpoint (desktop >= 1024px, mobile/tablet < 1024px)
  const [isDesktop, setIsDesktop] = useState(() => {
    if (typeof window !== 'undefined') {
      return window.innerWidth >= 1024;
    }
    return true;
  });

  useEffect(() => {
    const handleResize = () => {
      setIsDesktop(window.innerWidth >= 1024);
    };
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  // Discussion Post Composer State
  const [postBody, setPostBody] = useState('');
  const [postMedia, setPostMedia] = useState(null);
  const [isPosting, setIsPosting] = useState(false);
  const [postError, setPostError] = useState(null);
  const fileInputRef = useRef(null);



  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [verifyMessage, setVerifyMessage] = useState('Please verify your phone number first before proceeding.');
  const [attendeeTab, setAttendeeTab] = useState('going'); // for general viewers preview

  // Invite Modal State
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [friends, setFriends] = useState([]);
  const [isLoadingFriends, setIsLoadingFriends] = useState(false);
  const [invitedMap, setInvitedMap] = useState({});

  // Campaign & Funding Modal State
  const [campaignData, setCampaignData] = useState(null);
  const [availableAdFunds, setAvailableAdFunds] = useState(0);
  const [platformFeePercent, setPlatformFeePercent] = useState(2.5);
  const [showAddFundsModal, setShowAddFundsModal] = useState(false);
  const [showDepositModal, setShowDepositModal] = useState(false);
  const [campaignActionLoading, setCampaignActionLoading] = useState(false);
  const [showPaidEventModal, setShowPaidEventModal] = useState(false);

  const hasPaidCampaign = Boolean(data?.campaign && (data?.is_paid || Number(data.campaign.budget) > 0));
  const isCampaignEligible = Boolean(data?.campaign?.is_eligible);
  const isAlreadyRewarded = Boolean(
    data?.campaign?.already_rewarded ||
    data?.already_rewarded ||
    data?.event?.already_rewarded
  );
  const isOrganizer = Boolean(
    data?.is_organizer ||
    data?.is_host ||
    data?.campaign?.is_owner ||
    (currentUser?.id && (
      data?.organizer_id === currentUser.id ||
      data?.organizer?.id === currentUser.id ||
      data?.campaign?.member_id === currentUser.id ||
      data?.event?.organizer_id === currentUser.id ||
      data?.event?.member_id === currentUser.id
    ))
  );
  const canEarnPaidEventReward = Boolean(hasPaidCampaign && isCampaignEligible && !isOrganizer && !isAlreadyRewarded);
  const isCampaignExhausted = Boolean(hasPaidCampaign && !isCampaignEligible && !isAlreadyRewarded);
  const isPaidEvent = hasPaidCampaign;
  const earnUpToFormatted = data?.campaign?.earn_up_to_formatted || (data?.campaign?.earn_up_to_usd ? `$${Number(data.campaign.earn_up_to_usd).toFixed(4)}` : '$0.0250');

  const goingMembers = useMemo(() => data?.going_members || [], [data?.going_members]);
  const interestedMembers = useMemo(() => data?.interested_members || [], [data?.interested_members]);

  const displayedMembers = useMemo(() => {
    return attendeeTab === 'going' ? goingMembers : interestedMembers;
  }, [attendeeTab, goingMembers, interestedMembers]);

  const fetchEventDetail = useCallback(
    (page = 1) => {
      if (!id) return Promise.resolve(null);
      return eventApi.getEvent(id, page);
    },
    [id]
  );

  useEffect(() => {
    let isMounted = true;

    fetchEventDetail(postsPage)
      .then((res) => {
        if (isMounted && res) {
          setData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load event details.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchEventDetail, postsPage]);

  const handleRefresh = () => {
    fetchEventDetail(postsPage)
      .then((res) => {
        if (res) setData(res);
      })
      .catch(() => {});
  };

  useEffect(() => {
    if (data?.is_organizer && id) {
      eventApi
        .getEventCampaign(id)
        .then((res) => {
          if (res?.success) {
            setCampaignData(res.campaign);
            if (res.available_ad_funds !== undefined) {
              setAvailableAdFunds(res.available_ad_funds);
            }
            if (res.platform_fee_percent !== undefined) {
              setPlatformFeePercent(res.platform_fee_percent);
            }
          }
        })
        .catch(() => {});
    }
  }, [data?.is_organizer, id]);

  const handleOpenAddFunds = () => {
    const isVerified = currentUser?.is_verified || currentUser?.mobile_verified_at;
    if (!isVerified) {
      setShowVerifyModal(true);
      return;
    }
    setShowAddFundsModal(true);
  };

  const handleCampaignFunded = (updatedCampaign, newAdBalance) => {
    setCampaignData(updatedCampaign);
    if (newAdBalance !== undefined) {
      setAvailableAdFunds(newAdBalance);
    }
  };

  const handleActivateCampaign = async () => {
    if (campaignActionLoading) return;
    const isVerified = currentUser?.is_verified || currentUser?.mobile_verified_at;
    if (!isVerified) {
      setShowVerifyModal(true);
      return;
    }

    if (!campaignData || Number(campaignData.remaining_amount || 0) <= 0) {
      alert('Please add funds to your event campaign before activating it.');
      setShowAddFundsModal(true);
      return;
    }

    setCampaignActionLoading(true);
    try {
      const res = await eventApi.activateEventCampaign(id);
      if (res && res.success) {
        setCampaignData(res.campaign);
      }
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to activate campaign.';
      alert(msg);
    } finally {
      setCampaignActionLoading(false);
    }
  };

  const handlePauseCampaign = async () => {
    if (campaignActionLoading) return;
    setCampaignActionLoading(true);
    try {
      const res = await eventApi.pauseEventCampaign(id);
      if (res && res.success) {
        setCampaignData(res.campaign);
      }
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to pause campaign.';
      alert(msg);
    } finally {
      setCampaignActionLoading(false);
    }
  };

  const handleResumeCampaign = async () => {
    if (campaignActionLoading) return;
    setCampaignActionLoading(true);
    try {
      const res = await eventApi.resumeEventCampaign(id);
      if (res && res.success) {
        setCampaignData(res.campaign);
      }
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to resume campaign.';
      alert(msg);
    } finally {
      setCampaignActionLoading(false);
    }
  };

  const handleReactivateCampaign = async () => {
    if (campaignActionLoading) return;
    const isVerified = currentUser?.is_verified || currentUser?.mobile_verified_at;
    if (!isVerified) {
      setShowVerifyModal(true);
      return;
    }

    const minReward = Number(campaignData?.minimum_event_reward || 0.0250);
    if (!campaignData || Number(campaignData.remaining_amount || 0) < minReward) {
      alert(`Please add funds to your event campaign before reactivating it. Minimum required: $${minReward.toFixed(4)} USD.`);
      setShowAddFundsModal(true);
      return;
    }

    setCampaignActionLoading(true);
    try {
      const res = await eventApi.reactivateEventCampaign(id);
      if (res && res.success) {
        setCampaignData(res.campaign);
      }
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to reactivate campaign.';
      alert(msg);
    } finally {
      setCampaignActionLoading(false);
    }
  };

  const handleRespond = async (responseType) => {
    const isVerified = currentUser?.is_verified || currentUser?.mobile_verified_at;
    if (!isVerified) {
      setVerifyMessage('Please verify your phone number first before proceeding.');
      setShowVerifyModal(true);
      return;
    }

    // Intercept 'interested' for eligible sponsored events to trigger reward flow (non-organizer only)
    if (responseType === 'interested' && canEarnPaidEventReward) {
      // Open the authoritative paid event qualification modal
      setShowPaidEventModal(true);
      return;
    }

    if (responseType === 'interested' && isAlreadyRewarded) {
      // Already rewarded: if user is not currently marked interested, sync response
      if (data?.user_response !== 'interested') {
        try {
          const res = await eventApi.respondToEvent(id, 'interested');
          if (res && res.success) {
            setData((prev) => (prev ? {
              ...prev,
              user_response: res.response,
              going_count: res.going_count ?? prev.going_count,
              interested_count: res.interested_count ?? prev.interested_count,
            } : prev));
          }
        } catch (_err) {
          void _err;
        }
      }
      return;
    }

    // Normal RSVP flow for 'going' or non-sponsored events
    try {
      const res = await eventApi.respondToEvent(id, responseType);
      if (res && res.success) {
        setData((prev) => {
          if (!prev) return prev;
          return {
            ...prev,
            user_response: res.response,
            going_count: res.going_count ?? prev.going_count,
            interested_count: res.interested_count ?? prev.interested_count,
          };
        });
      }
    } catch (err) {
      if (err.response?.data?.needs_verification || err.response?.data?.verified_required || err.response?.data?.error_code === 'PHONE_VERIFICATION_REQUIRED') {
        setVerifyMessage(err.response?.data?.message || 'Please verify your phone number first before proceeding.');
        setShowVerifyModal(true);
      }
    }
  };

  const handleShare = () => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(window.location.href);
      setCopiedShare(true);
      setTimeout(() => setCopiedShare(false), 3000);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Are you sure you want to cancel and delete this event?')) return;
    try {
      await eventApi.deleteEvent(id);
      navigate('/member/events');
    } catch {
      alert('Failed to delete event.');
    }
  };

  const handlePostSubmit = async (e) => {
    e.preventDefault();
    if (isPosting || (!postBody.trim() && !postMedia)) return;

    if (postMedia && postMedia.type && postMedia.type.startsWith('video/')) {
      setPostError('Videos can only be posted from a Business Page.');
      return;
    }

    setIsPosting(true);
    setPostError(null);

    const formData = new FormData();
    if (postBody.trim()) formData.append('body', postBody.trim());
    if (postMedia) formData.append('media', postMedia);

    try {
      const res = await eventApi.storeEventPost(id, formData);
      setPostBody('');
      setPostMedia(null);
      if (fileInputRef.current) fileInputRef.current.value = '';

      if (res && res.post) {
        setData((prev) => {
          if (!prev) return prev;
          const currentPosts = prev.posts?.data || [];
          return {
            ...prev,
            posts: {
              ...prev.posts,
              data: [res.post, ...currentPosts],
            },
          };
        });
      } else {
        handleRefresh();
      }
    } catch (err) {
      setPostError(err.response?.data?.message || 'Failed to publish post.');
    } finally {
      setIsPosting(false);
    }
  };

  const openInviteModal = async () => {
    setShowInviteModal(true);
    setIsLoadingFriends(true);
    try {
      const res = await friendApi.getFriends();
      const list = Array.isArray(res) ? res : res?.friends?.data || res?.friends || res?.data || [];
      setFriends(list);
    } catch {
      setFriends([]);
    } finally {
      setIsLoadingFriends(false);
    }
  };

  const handleSendInvite = async (friendId) => {
    try {
      await eventApi.inviteMember(id, friendId);
      setInvitedMap((prev) => ({ ...prev, [friendId]: true }));
    } catch {
      alert('Failed to send invitation.');
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading event details...
      </div>
    );
  }

  if (error || !data?.event) {
    return (
      <div className="card" style={{ maxWidth: '800px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Event Not Found</h2>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>{error || 'This event could not be found.'}</p>
        <Link to="/member/events" className="member-button member-button--primary">
          Back to Events Hub
        </Link>
      </div>
    );
  }

  const event = data.event;
  const organizer = event.organizer;
  const goingCount = data.going_count || 0;
  const interestedCount = data.interested_count || 0;
  const postsList = data.posts?.data || [];
  const postsPagination = data.posts || {};

  const cover = event.cover_photo
    ? event.cover_photo.startsWith('http')
      ? event.cover_photo
      : `/${event.cover_photo}`
    : '/member_assets/images/default-event.jpg';

  const startDateObj = event.start_date ? new Date(event.start_date) : new Date();
  const monthStr = startDateObj.toLocaleString('en-US', { month: 'short' }).toUpperCase();
  const dayStr = startDateObj.getDate().toString().padStart(2, '0');

  return (
    <div className="event-show-wrapper" style={{ width: '100%', margin: '0 auto' }}>
      {/* Back button */}
      <div style={{ marginBottom: '16px' }}>
        <Link
          to="/member/events"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '6px',
            color: 'var(--color-text-secondary)',
            fontSize: '0.875rem',
            textDecoration: 'none',
          }}
        >
          <ArrowLeft size={16} />
          <span>Back to Events Hub</span>
        </Link>
      </div>

      {/* Hero Banner */}
      <div className="event-hero-card card" style={{ borderRadius: '16px', overflow: 'hidden', marginBottom: '24px' }}>
        <div className="event-hero-cover" style={{ position: 'relative', height: '280px', background: '#1e293b' }}>
          <img
            src={cover}
            alt={event.title}
            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
          />
          <div
            className="event-hero-overlay"
            style={{
              position: 'absolute',
              inset: 0,
              background: 'linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%)',
            }}
          />

          {/* Floating Type Badge */}
          <div
            className={`event-hero-badge badge-${event.event_type}`}
            style={{
              position: 'absolute',
              top: '16px',
              left: '16px',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              background: event.event_type === 'online' ? '#10b981' : '#4f7df3',
              color: '#ffffff',
              padding: '4px 10px',
              borderRadius: '20px',
              fontSize: '12px',
              fontWeight: 700,
            }}
          >
            {event.event_type === 'online' ? <Video size={13} /> : <MapPin size={13} />}
            <span>{event.event_type === 'online' ? 'Online Event' : 'In-Person Event'}</span>
          </div>

        </div>

        <div className="event-hero-body">
          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
            <div className="event-hero-info" style={{ flex: 1, minWidth: 0, display: 'flex', gap: '16px', alignItems: 'flex-start' }}>
              {/* Event Date Badge Box */}
              <div
                className="event-hero-date-badge"
                style={{
                  position: 'static',
                  top: 'auto',
                  right: 'auto',
                  bottom: 'auto',
                  left: 'auto',
                  width: '58px',
                  minWidth: '58px',
                  height: '62px',
                  background: '#ffffff',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '6px 8px',
                  textAlign: 'center',
                  boxShadow: '0 2px 8px rgba(15, 23, 42, 0.06)',
                  flexShrink: 0,
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  justifyContent: 'center',
                  zIndex: 'auto',
                }}
              >
                <span style={{ display: 'block', fontSize: '11px', fontWeight: 800, color: '#ef4444', letterSpacing: '0.04em', textTransform: 'uppercase' }}>{monthStr}</span>
                <span style={{ display: 'block', fontSize: '22px', fontWeight: 900, color: '#0f172a', lineHeight: 1.1 }}>{dayStr}</span>
              </div>

              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ display: 'flex', gap: '8px', marginBottom: '8px', flexWrap: 'wrap' }}>
                  <span className="community-badge" style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '11.5px' }}>
                    <Tag size={11} /> {event.category}
                  </span>
                  <span className="community-badge" style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '11.5px' }}>
                    {event.privacy === 'public' ? <Globe size={11} /> : <Lock size={11} />}
                    <span>{event.privacy === 'public' ? 'Public' : event.privacy === 'friends_only' ? 'Friends Only' : 'Private'} Event</span>
                  </span>
                </div>

                <h1 className="event-hero-title" style={{ fontSize: '24px', fontWeight: 900, margin: '0 0 10px 0', color: 'var(--color-text-main)', overflowWrap: 'break-word', wordBreak: 'break-word' }}>
                  {event.title}
                </h1>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', fontSize: '13.5px', color: 'var(--color-text-secondary)', marginBottom: '14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0, wordBreak: 'break-word' }}>
                    <Calendar size={14} color="#4f7df3" style={{ flexShrink: 0 }} />
                    <span>{formatEventScheduleDate(event.start_date, event.start_time, event.end_date)}</span>
                  </div>
                  {event.event_type === 'online' ? (
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0, wordBreak: 'break-word' }}>
                      <Video size={14} color="#10b981" style={{ flexShrink: 0 }} />
                      <span>Online Video Meeting</span>
                    </div>
                  ) : event.location_address ? (
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0, wordBreak: 'break-word' }}>
                      <MapPin size={14} color="#ef4444" style={{ flexShrink: 0 }} />
                      <span>{event.location_address}{event.location_city ? `, ${event.location_city}` : ''}</span>
                    </div>
                  ) : null}
                </div>

                {/* Host Strip */}
                {organizer && (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '12px' }}>
                    <MemberAvatar member={organizer} size={36} />
                    <div>
                      <span style={{ fontSize: '11.5px', color: 'var(--color-text-secondary)' }}>Hosted by </span>
                      <Link to={`/member/people/${organizer.id}`} style={{ fontWeight: 700, fontSize: '13.5px', color: 'var(--color-text-main)', textDecoration: 'none', display: 'inline-flex', alignItems: 'center' }}>
                        <span>{organizer.name}</span>
                        <VerifiedBadge member={organizer} size={14} />
                      </Link>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Sponsored Event Reward Banner (For Attendees who can earn) */}
            {canEarnPaidEventReward && (
              <div
                className="event-sponsored-banner"
                style={{
                  backgroundColor: 'rgba(79, 125, 243, 0.08)',
                  border: '1px solid rgba(79, 125, 243, 0.25)',
                  borderRadius: '10px',
                  padding: '12px 16px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '12px',
                  marginBottom: '14px',
                  width: '100%',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px', minWidth: 0, flex: 1 }}>
                  <div style={{ backgroundColor: '#4f7df3', color: '#fff', padding: '6px', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <Gift size={18} aria-hidden="true" />
                  </div>
                  <div style={{ minWidth: 0, overflowWrap: 'break-word', wordBreak: 'break-word' }}>
                    <div style={{ fontSize: '13.5px', fontWeight: 700, color: '#1e293b' }}>
                      Sponsored Event • Earn up to {earnUpToFormatted} USD
                    </div>
                    <div style={{ fontSize: '12px', color: '#64748b', marginTop: '2px' }}>
                      Earn cash rewards in your Reward Wallet by marking yourself as Interested
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => handleRespond('interested')}
                  style={{
                    backgroundColor: '#059669',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '8px',
                    padding: '8px 16px',
                    fontSize: '13px',
                    fontWeight: 700,
                    cursor: 'pointer',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    whiteSpace: 'nowrap',
                    boxShadow: '0 2px 4px rgba(5, 150, 105, 0.25)',
                  }}
                >
                  <Gift size={14} aria-hidden="true" />
                  <span>Unlock</span>
                </button>
              </div>
            )}

            {/* Sponsored Event Banner for Organizer */}
            {isPaidEvent && isOrganizer && (
              <div
                className="event-sponsored-banner"
                style={{
                  backgroundColor: 'rgba(79, 125, 243, 0.08)',
                  border: '1px solid rgba(79, 125, 243, 0.25)',
                  borderRadius: '10px',
                  padding: '12px 16px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '12px',
                  marginBottom: '14px',
                  width: '100%',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px', minWidth: 0, flex: 1 }}>
                  <div style={{ backgroundColor: '#4f7df3', color: '#fff', padding: '6px', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <Megaphone size={18} aria-hidden="true" />
                  </div>
                  <div style={{ minWidth: 0, overflowWrap: 'break-word', wordBreak: 'break-word' }}>
                    <div style={{ fontSize: '13.5px', fontWeight: 700, color: '#1e293b' }}>
                      Promoted Event Campaign • {data?.campaign?.display_status || 'Active'}
                    </div>
                    <div style={{ fontSize: '12px', color: '#64748b', marginTop: '2px' }}>
                      Remaining Budget: ${Number(data?.campaign?.remaining_amount || 0).toFixed(4)} USD • Platform promotion active
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={handleOpenAddFunds}
                  className="member-button member-button--secondary"
                  style={{ whiteSpace: 'nowrap', padding: '6px 14px', fontSize: '12px' }}
                >
                  <Plus size={13} aria-hidden="true" />
                  <span>Add Funds</span>
                </button>
              </div>
            )}

            {isPaidEvent && isAlreadyRewarded && (
              <div
                style={{
                  backgroundColor: '#ecfdf5',
                  border: '1px solid #a7f3d0',
                  borderRadius: '10px',
                  padding: '10px 16px',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '10px',
                  marginBottom: '14px',
                  width: '100%',
                }}
              >
                <Check size={18} color="#059669" aria-hidden="true" />
                <span style={{ fontSize: '13px', fontWeight: 600, color: '#047857' }}>
                  Sponsored Event • You have already qualified and received your reward for this event.
                </span>
              </div>
            )}

            {/* Action Buttons Bar */}
            <div className="event-hero-actions" style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
              {!isOrganizer && (
                <>
                  {hasPaidCampaign ? (
                    <>
                      {isAlreadyRewarded ? (
                        <button
                          type="button"
                          className="member-button"
                          disabled
                          style={{
                            backgroundColor: '#f0fdf4',
                            borderColor: '#86efac',
                            color: '#16a34a',
                            fontWeight: 700,
                            cursor: 'default',
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                          }}
                        >
                          <Check size={15} color="#16a34a" aria-hidden="true" />
                          <span>Rewarded</span>
                        </button>
                      ) : isCampaignExhausted ? (
                        <button
                          type="button"
                          className="member-button member-button--secondary"
                          disabled
                          style={{ cursor: 'not-allowed', color: '#94a3b8', display: 'inline-flex', alignItems: 'center', gap: '6px' }}
                        >
                          <Gift size={15} aria-hidden="true" />
                          <span>Campaign Exhausted</span>
                        </button>
                      ) : (
                        <button
                          type="button"
                          className="member-button member-button--primary"
                          onClick={() => handleRespond('interested')}
                          style={{
                            backgroundColor: '#059669',
                            borderColor: '#047857',
                            color: '#ffffff',
                            fontWeight: 700,
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                            boxShadow: '0 2px 4px rgba(5, 150, 105, 0.25)',
                          }}
                        >
                          <Gift size={15} aria-hidden="true" />
                          <span>Unlock</span>
                        </button>
                      )}
                    </>
                  ) : (
                    <>
                      <button
                        type="button"
                        className={`member-button ${data?.user_response === 'going' ? 'member-button--primary' : 'member-button--secondary'}`}
                        onClick={() => handleRespond('going')}
                        style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}
                      >
                        <CheckCircle2 size={15} aria-hidden="true" />
                        <span>Going</span>
                      </button>
                      <button
                        type="button"
                        className={`member-button ${data?.user_response === 'interested' ? 'member-button--primary' : 'member-button--secondary'}`}
                        onClick={() => handleRespond('interested')}
                        style={{ display: 'inline-flex', alignItems: 'center', gap: '6px' }}
                      >
                        <Star size={15} aria-hidden="true" />
                        <span>Interested</span>
                      </button>
                    </>
                  )}
                </>
              )}

              {isOrganizer && (
                <button
                  type="button"
                  className="member-button member-button--primary"
                  onClick={() => {
                    const el = document.getElementById('host-attendees-section');
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                  }}
                  title="View interested and attending people"
                >
                  <UsersRound size={15} aria-hidden="true" />
                  <span>Contact Attendees ({goingMembers.length + interestedMembers.length})</span>
                </button>
              )}

              {isOrganizer && (
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  onClick={handleOpenAddFunds}
                  title="Add Funds to Event Campaign"
                  style={{
                    backgroundColor: '#f0fdf4',
                    borderColor: '#86efac',
                    color: '#16a34a',
                    fontWeight: 700,
                  }}
                >
                  <Wallet size={15} aria-hidden="true" />
                  <span>
                    Add Funds
                    {campaignData?.remaining_amount !== undefined && campaignData.remaining_amount > 0
                      ? ` ($${Number(campaignData.remaining_amount).toFixed(4)})`
                      : ''}
                  </span>
                </button>
              )}

              {isOrganizer && campaignData && (
                <>
                  {/* Campaign Status Badge */}
                  {(() => {
                    const statusConfig = getCampaignStatusConfig(campaignData);
                    if (!statusConfig) return null;
                    return (
                      <span
                        style={{
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '6px',
                          padding: '6px 14px',
                          borderRadius: '9999px',
                          fontSize: '11.5px',
                          fontWeight: 700,
                          letterSpacing: '0.04em',
                          textTransform: 'uppercase',
                          lineHeight: 1.2,
                          backgroundColor: statusConfig.bg,
                          color: statusConfig.color,
                          border: `1px solid ${statusConfig.border}`,
                          boxSizing: 'border-box',
                          whiteSpace: 'nowrap',
                          flexShrink: 0,
                          userSelect: 'none',
                        }}
                      >
                        <span
                          style={{
                            width: '7px',
                            height: '7px',
                            borderRadius: '50%',
                            backgroundColor: statusConfig.dot,
                            display: 'inline-block',
                            flexShrink: 0,
                          }}
                          aria-hidden="true"
                        />
                        <span>{statusConfig.label}</span>
                      </span>
                    );
                  })()}

                  {/* Lifecycle Controls */}
                  {['draft', 'approved', 'pending_review'].includes(campaignData.status) && (
                    <button
                      type="button"
                      className="member-button member-button--primary"
                      onClick={handleActivateCampaign}
                      disabled={campaignActionLoading}
                      title="Activate Campaign"
                      style={{
                        backgroundColor: '#16a34a',
                        borderColor: '#15803d',
                        color: '#ffffff',
                        fontWeight: 700,
                      }}
                    >
                      <Play size={15} aria-hidden="true" />
                      <span>{campaignActionLoading ? 'Activating...' : 'Activate Campaign'}</span>
                    </button>
                  )}

                  {/* Reactivate Button for Stopped/Exhausted Campaign */}
                  {(campaignData.status === 'stopped' || campaignData.is_exhausted) && (
                    <button
                      type="button"
                      className="member-button member-button--primary"
                      onClick={handleReactivateCampaign}
                      disabled={campaignActionLoading}
                      title="Reactivate Campaign"
                      style={{
                        backgroundColor: '#16a34a',
                        borderColor: '#15803d',
                        color: '#ffffff',
                        fontWeight: 700,
                      }}
                    >
                      <Play size={15} aria-hidden="true" />
                      <span>{campaignActionLoading ? 'Reactivating...' : 'Reactivate Campaign'}</span>
                    </button>
                  )}

                  {campaignData.status === 'active' && (
                    <button
                      type="button"
                      className="member-button member-button--secondary"
                      onClick={handlePauseCampaign}
                      disabled={campaignActionLoading}
                      title="Pause Campaign"
                      style={{
                        backgroundColor: '#fffbeb',
                        borderColor: '#fcd34d',
                        color: '#b45309',
                        fontWeight: 600,
                      }}
                    >
                      <PauseIcon size={15} aria-hidden="true" />
                      <span>{campaignActionLoading ? 'Pausing...' : 'Pause Campaign'}</span>
                    </button>
                  )}

                  {campaignData.status === 'paused' && (
                    <button
                      type="button"
                      className="member-button member-button--primary"
                      onClick={handleResumeCampaign}
                      disabled={campaignActionLoading}
                      title="Resume Campaign"
                      style={{
                        backgroundColor: '#16a34a',
                        borderColor: '#15803d',
                        color: '#ffffff',
                        fontWeight: 700,
                      }}
                    >
                      <Play size={15} aria-hidden="true" />
                      <span>{campaignActionLoading ? 'Resuming...' : 'Resume Campaign'}</span>
                    </button>
                  )}
                </>
              )}

              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={openInviteModal}
                title="Invite Friends"
              >
                <UserPlus size={15} aria-hidden="true" />
                <span>Invite</span>
              </button>

              {isOrganizer && (
                <>
                  <Link to={`/member/events/${event.id}/edit`} className="member-button member-button--secondary">
                    <Pencil size={15} aria-hidden="true" />
                    <span>Edit</span>
                  </Link>
                  <button
                    type="button"
                    className="member-button member-button--secondary"
                    onClick={handleDelete}
                    title="Cancel Event"
                    style={{ color: '#ef4444' }}
                  >
                    <Trash2 size={15} aria-hidden="true" />
                  </button>
                </>
              )}

              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={handleShare}
                title="Share Event"
              >
                <Share2 size={15} aria-hidden="true" />
                <span>{copiedShare ? 'Link Copied!' : 'Share'}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Main Layout: Desktop 2-Column or Mobile Single-Column Stack */}
      {(() => {
        const aboutCardNode = (
          <div className="card event-about-card">
            <h2 style={{ fontSize: '16px', fontWeight: 800, margin: '0 0 12px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <Info size={18} color="#4f7df3" aria-hidden="true" />
              <span>About this Event</span>
            </h2>
            <div style={{ fontSize: '14px', lineHeight: 1.6, color: 'var(--color-text-main)', whiteSpace: 'pre-line', overflowWrap: 'break-word', wordBreak: 'break-word' }}>
              {event.description || event.short_description || 'No detailed description provided for this event.'}
            </div>

            {/* Online link or Offline venue */}
            {event.event_type === 'online' && event.meeting_link && (
              <div className="event-meeting-box">
                <div className="event-meeting-box__info">
                  <strong style={{ fontSize: '13.5px', color: '#065f46', display: 'block' }}>Online Meeting Link</strong>
                  <span style={{ fontSize: '12px', color: '#047857' }}>Join the video conference when the event starts.</span>
                </div>
                <a
                  href={event.meeting_link}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="member-button member-button--primary event-meeting-box__btn"
                  style={{ fontSize: '12px', padding: '6px 14px' }}
                >
                  <ExternalLink size={13} aria-hidden="true" />
                  <span>Join Meeting</span>
                </a>
              </div>
            )}

            {event.event_type === 'offline' && event.location_address && (
              <div className="event-venue-box">
                <div className="event-venue-box__info">
                  <strong style={{ fontSize: '13.5px', color: 'var(--color-text-main)', display: 'block' }}>Venue Address</strong>
                  <span style={{ fontSize: '12px', color: 'var(--color-text-secondary)' }}>
                    {event.location_address}{event.location_city ? `, ${event.location_city}` : ''}
                  </span>
                </div>
                {event.google_maps_link && (
                  <a
                    href={event.google_maps_link}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="member-button member-button--secondary event-venue-box__btn"
                    style={{ fontSize: '12px', padding: '6px 14px' }}
                  >
                    <Map size={13} aria-hidden="true" />
                    <span>Open in Maps</span>
                  </a>
                )}
              </div>
            )}
          </div>
        );

        const outreachCardNode = isOrganizer ? (
          <div className="card event-outreach-card">
            <div className="event-outreach-card__inner">
              <div className="event-outreach-card__info-wrap">
                <div className="event-outreach-card__icon-box">
                  <UsersRound size={24} />
                </div>
                <div className="event-outreach-card__text-wrap">
                  <div className="event-outreach-card__title-row">
                    <h3 style={{ fontSize: '17px', fontWeight: 800, margin: 0, color: '#ffffff' }}>
                      Host Outreach &amp; Contact Center
                    </h3>
                    <span className="event-outreach-card__host-pill">
                      Host Only
                    </span>
                  </div>
                  <p className="event-outreach-card__desc">
                    <strong>{goingCount + interestedCount} members</strong> responded ({goingCount} going, {interestedCount} interested). View full contact info, mobile phone numbers, WhatsApp chat links, and export data.
                  </p>
                </div>
              </div>

              <Link
                to={`/member/events/${event.id}/outreach`}
                className="member-button event-outreach-btn"
              >
                <UsersRound size={16} />
                <span>Open Contact Center</span>
              </Link>
            </div>
          </div>
        ) : null;

        const attendeesCardNode = (!isOrganizer && (goingMembers.length > 0 || interestedMembers.length > 0)) ? (
          <div className="card event-attendees-card">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
              <h2 style={{ fontSize: '16px', fontWeight: 800, margin: 0, display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Users size={18} color="#4f46e5" aria-hidden="true" />
                <span>Attendees &amp; Responses ({goingCount + interestedCount})</span>
              </h2>
            </div>

            <div className="event-attendees-tabs-row" style={{ marginBottom: '12px' }}>
              <div className="event-attendees-tabs">
                <button
                  type="button"
                  className={`event-attendees-tab-btn ${attendeeTab === 'going' ? 'active' : ''}`}
                  onClick={() => setAttendeeTab('going')}
                >
                  <CheckCircle2 size={13} color="#10b981" />
                  <span>Going ({goingMembers.length})</span>
                </button>

                <button
                  type="button"
                  className={`event-attendees-tab-btn ${attendeeTab === 'interested' ? 'active' : ''}`}
                  onClick={() => setAttendeeTab('interested')}
                >
                  <Star size={13} color="#f59e0b" />
                  <span>Interested ({interestedMembers.length})</span>
                </button>
              </div>
            </div>

            <div className="event-attendees-grid">
              {displayedMembers.map((m) => {
                const av = getAvatarUrl(m.profile_photo);
                const init = getInitials(m.name);
                return (
                  <Link
                    key={m.id}
                    to={`/member/people/${m.id}`}
                    className="event-attendees-grid-item"
                  >
                    {av ? (
                      <img src={av} alt={m.name} style={{ width: '32px', height: '32px', borderRadius: '50%', objectFit: 'cover' }} />
                    ) : (
                      <span style={{ width: '32px', height: '32px', borderRadius: '50%', background: '#e0e7ff', color: '#4f46e5', fontWeight: 700, fontSize: '11px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        {init}
                      </span>
                    )}
                    <div style={{ minWidth: 0, overflow: 'hidden' }}>
                      <span style={{ fontSize: '13px', fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: '4px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                        <span>{m.name}</span>
                        <VerifiedBadge member={m} size={12} />
                      </span>
                      {m.city && (
                        <span style={{ fontSize: '11px', color: '#64748b', display: 'block' }}>
                          {m.city}
                        </span>
                      )}
                    </div>
                  </Link>
                );
              })}
            </div>
          </div>
        ) : null;

        const discussionCardNode = (
          <div className="card event-discussion-card">
            <h2 style={{ fontSize: '16px', fontWeight: 800, margin: '0 0 16px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <MessageSquare size={18} color="#4f7df3" aria-hidden="true" />
              <span>Event Discussion</span>
            </h2>

            {/* Discussion Composer */}
            <form onSubmit={handlePostSubmit} style={{ marginBottom: '24px' }}>
              {postError && (
                <div style={{ padding: '8px 12px', background: '#fee2e2', color: '#b91c1c', borderRadius: '8px', marginBottom: '10px', fontSize: '12.5px' }}>
                  {postError}
                </div>
              )}
              <textarea
                rows={3}
                className="event-composer-textarea"
                placeholder="Post an update, ask a question, or share something with attendees..."
                value={postBody}
                onChange={(e) => setPostBody(e.target.value)}
                style={{
                  width: '100%',
                  padding: '12px',
                  borderRadius: '10px',
                  border: '1px solid #cbd5e1',
                  fontSize: '13.5px',
                  resize: 'vertical',
                }}
              />
              <div className="event-discussion-composer-footer">
                <label className="event-media-attach-label">
                  <ImageIcon size={16} style={{ flexShrink: 0 }} />
                  <span className="event-media-attach-text">
                    {postMedia ? postMedia.name : 'Attach Photo'}
                  </span>
                  <input
                    type="file"
                    ref={fileInputRef}
                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                    hidden
                    onChange={(e) => {
                      const file = e.target.files?.[0] || null;
                      if (file && file.type && file.type.startsWith('video/')) {
                        setPostError('Videos can only be posted from a Business Page.');
                        e.target.value = '';
                        setPostMedia(null);
                        return;
                      }
                      setPostError(null);
                      setPostMedia(file);
                    }}
                  />
                </label>

                <button
                  type="submit"
                  className="member-button member-button--primary event-discussion-post-btn"
                  disabled={isPosting || (!postBody.trim() && !postMedia)}
                >
                  <Send size={14} aria-hidden="true" />
                  <span>{isPosting ? 'Posting...' : 'Post Update'}</span>
                </button>
              </div>
            </form>

            {/* Discussion Posts List */}
            {postsList.length === 0 ? (
              <div className="notification-empty" style={{ padding: '24px', textAlign: 'center' }}>
                <MessageSquare size={32} color="#94a3b8" style={{ margin: '0 auto 8px' }} />
                <h3 style={{ fontSize: '15px', margin: '0 0 4px 0' }}>No Discussion Posts Yet</h3>
                <p style={{ fontSize: '13px', color: 'var(--color-text-secondary)', margin: 0 }}>
                  Be the first attendee to post an update or question about this event!
                </p>
              </div>
            ) : (
              <div className="event-posts-list" style={{ display: 'flex', flexDirection: 'column', gap: '16px', width: '100%', minWidth: 0 }}>
                {postsList.map((post) => (
                  <PostCard
                    key={post.id}
                    post={post}
                    currentUser={currentUser}
                    onPostDeleted={handleRefresh}
                  />
                ))}
              </div>
            )}

            {/* Posts Pagination */}
            {postsPagination.last_page > 1 && (
              <div className="event-posts-pagination">
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  disabled={postsPage <= 1}
                  onClick={() => setPostsPage((p) => Math.max(1, p - 1))}
                >
                  Previous
                </button>
                <span style={{ display: 'flex', alignItems: 'center', fontSize: '13px', color: '#64748b' }}>
                  Page {postsPagination.current_page} of {postsPagination.last_page}
                </span>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  disabled={postsPage >= postsPagination.last_page}
                  onClick={() => setPostsPage((p) => p + 1)}
                >
                  Next
                </button>
              </div>
            )}
          </div>
        );

        const organizerCardNode = organizer ? (
          <div className="card event-organizer-card">
            <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '12px' }}>
              <MemberAvatar member={organizer} size={64} />
            </div>

            <h3 style={{ fontSize: '16px', fontWeight: 800, margin: '0 0 4px 0' }}>
              <Link to={`/member/people/${organizer.id}`} style={{ color: 'inherit', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>
                <span>{organizer.name}</span>
                <VerifiedBadge member={organizer} size={15} />
              </Link>
            </h3>
            <span className="community-badge" style={{ fontSize: '11px', display: 'inline-flex', alignItems: 'center', gap: '4px', marginBottom: '10px' }}>
              <ShieldCheck size={12} /> Host
            </span>

            {organizer.bio && (
              <p style={{ fontSize: '12.5px', color: 'var(--color-text-secondary)', margin: '0 0 14px 0', lineHeight: 1.4 }}>
                {organizer.bio.slice(0, 100)}
              </p>
            )}

            <div className="event-organizer-stats-row">
              <div>
                <strong style={{ fontSize: '16px', color: 'var(--color-text-main)', display: 'block' }}>{goingCount}</strong>
                <span style={{ fontSize: '11px', color: 'var(--color-text-secondary)' }}>Going</span>
              </div>
              <div>
                <strong style={{ fontSize: '16px', color: 'var(--color-text-main)', display: 'block' }}>{interestedCount}</strong>
                <span style={{ fontSize: '11px', color: 'var(--color-text-secondary)' }}>Interested</span>
              </div>
            </div>

            <Link
              to={`/member/people/${organizer.id}`}
              className="member-button member-button--secondary event-organizer-view-btn"
            >
              <User size={13} aria-hidden="true" />
              <span>View Host Profile</span>
            </Link>
          </div>
        ) : null;

        const statusCardNode = (
          <div className="card event-status-card">
            <h3 style={{ fontSize: '15px', fontWeight: 800, margin: '0 0 14px 0' }}>Event Status</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <div style={{ width: '32px', height: '32px', borderRadius: '8px', background: 'rgba(79, 125, 243, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#4f7df3' }}>
                  <Users size={16} />
                </div>
                <div>
                  <span style={{ fontSize: '11px', color: 'var(--color-text-secondary)', display: 'block' }}>Going</span>
                  <strong style={{ fontSize: '13.5px', color: 'var(--color-text-main)' }}>{goingCount} Members</strong>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <div style={{ width: '32px', height: '32px', borderRadius: '8px', background: 'rgba(245, 158, 11, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#f59e0b' }}>
                  <Star size={16} />
                </div>
                <div>
                  <span style={{ fontSize: '11px', color: 'var(--color-text-secondary)', display: 'block' }}>Interested</span>
                  <strong style={{ fontSize: '13.5px', color: 'var(--color-text-main)' }}>{interestedCount} Members</strong>
                </div>
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <div style={{ width: '32px', height: '32px', borderRadius: '8px', background: 'rgba(16, 185, 129, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#10b981' }}>
                  {event.privacy === 'public' ? <Globe size={16} /> : <Lock size={16} />}
                </div>
                <div>
                  <span style={{ fontSize: '11px', color: 'var(--color-text-secondary)', display: 'block' }}>Privacy</span>
                  <strong style={{ fontSize: '13.5px', color: 'var(--color-text-main)' }}>{event.privacy === 'public' ? 'Public' : 'Private'} Event</strong>
                </div>
              </div>
            </div>
          </div>
        );

        if (isDesktop) {
          return (
            <div className="event-details-layout">
              {/* Left Column */}
              <div className="event-main-column">
                {aboutCardNode}
                {outreachCardNode}
                {attendeesCardNode}
                {discussionCardNode}
              </div>

              {/* Right Sidebar Column */}
              <div className="event-sidebar-column">
                {organizerCardNode}
                {statusCardNode}
              </div>
            </div>
          );
        }

        return (
          <div className="event-details-layout event-details-layout--stacked">
            {aboutCardNode}
            {outreachCardNode}
            {organizerCardNode}
            {statusCardNode}
            {attendeesCardNode}
            {discussionCardNode}
          </div>
        );
      })()}

      {/* Invite Friends Modal */}
      {showInviteModal && (
        <ModalPortal isOpen={showInviteModal} onClose={() => setShowInviteModal(false)}>
          <div
            className="card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invite-friends-modal-title"
            style={{ maxWidth: '480px', width: '100%', padding: '24px', borderRadius: '16px', background: '#fff' }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
              <h3 id="invite-friends-modal-title" style={{ fontSize: '17px', fontWeight: 800, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <UserPlus size={18} color="#4f7df3" />
                <span>Invite Friends to Event</span>
              </h3>
              <button
                type="button"
                onClick={() => setShowInviteModal(false)}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer', color: '#64748b' }}
              >
                <X size={18} />
              </button>
            </div>

            {isLoadingFriends ? (
              <div style={{ padding: '30px', textAlign: 'center', color: '#64748b' }}>
                Loading friends list...
              </div>
            ) : friends.length === 0 ? (
              <div style={{ padding: '24px', textAlign: 'center', color: '#64748b' }}>
                No friends found to invite. Connect with other members first!
              </div>
            ) : (
              <div style={{ maxHeight: '300px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {friends.map((friend) => {
                  const isInvited = Boolean(invitedMap[friend.id]);
                  return (
                    <div
                      key={friend.id}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '8px 12px',
                        borderRadius: '8px',
                        background: '#f8fafc',
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <MemberAvatar member={friend} size={32} />
                        <strong style={{ fontSize: '13px', color: '#0f172a' }}>{friend.name}</strong>
                      </div>

                      <button
                        type="button"
                        className={`member-button ${isInvited ? 'member-button--secondary' : 'member-button--primary'}`}
                        disabled={isInvited}
                        onClick={() => handleSendInvite(friend.id)}
                        style={{ padding: '4px 12px', fontSize: '11.5px' }}
                      >
                        {isInvited ? 'Invited' : 'Invite'}
                      </button>
                    </div>
                  );
                })}
              </div>
            )}

            <div style={{ marginTop: '16px', display: 'flex', justifyContent: 'flex-end' }}>
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={() => setShowInviteModal(false)}
              >
                Close
              </button>
            </div>
          </div>
        </ModalPortal>
      )}

      {/* Account Verification Modal for Unverified Members */}
      <AccountVerificationModal
        isOpen={showVerifyModal}
        initialError={verifyMessage}
        onClose={() => setShowVerifyModal(false)}
        onVerified={() => {
          setShowVerifyModal(false);
          if (canEarnPaidEventReward) {
            setShowPaidEventModal(true);
          } else {
            handleRefresh();
          }
        }}
      />

      {/* Paid Event Qualification Modal for Sponsored Events */}
      {showPaidEventModal && (
        <PaidEventQualificationModal
          isOpen={showPaidEventModal}
          onClose={() => setShowPaidEventModal(false)}
          event={data?.event || event}
          campaign={data?.campaign || campaignData}
          onSuccess={() => {
            setData((prev) => {
              if (!prev) return prev;
              const wasInterested = prev.user_response === 'interested';
              const wasGoing = prev.user_response === 'going';
              const curInterest = prev.interested_count || 0;
              return {
                ...prev,
                already_rewarded: true,
                user_response: 'interested',
                interested_count: wasInterested ? curInterest : curInterest + 1,
                going_count: wasGoing ? Math.max(0, (prev.going_count || 1) - 1) : prev.going_count,
                campaign: {
                  ...prev.campaign,
                  already_rewarded: true,
                },
              };
            });
            setShowPaidEventModal(false);
          }}
        />
      )}

      {/* Event Campaign Add Funds Modal */}
      {showAddFundsModal && isOrganizer && (
        <AddFundsToEventCampaignModal
          event={event}
          campaign={campaignData}
          availableAdFunds={availableAdFunds}
          platformFeePercent={platformFeePercent}
          onClose={() => setShowAddFundsModal(false)}
          onCampaignUpdated={handleCampaignFunded}
          onOpenDepositModal={() => setShowDepositModal(true)}
        />
      )}

      {/* Universal USDT (BEP-20) Deposit Modal */}
      {showDepositModal && (
        <AddFundModal
          page={null}
          isOpen={showDepositModal}
          onClose={() => setShowDepositModal(false)}
          onDepositSubmitted={() => {
            setShowDepositModal(false);
            if (id) {
              eventApi
                .getEventCampaign(id)
                .then((res) => {
                  if (res?.success) {
                    setCampaignData(res.campaign);
                    if (res.available_ad_funds !== undefined) {
                      setAvailableAdFunds(res.available_ad_funds);
                    }
                  }
                })
                .catch(() => {});
            }
          }}
        />
      )}

    </div>
  );
}

export default EventDetailPage;
