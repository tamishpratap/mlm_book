import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  Video,
  MapPin,
  Clock,
  Users,
  Check,
  Tag,
  ArrowRight,
  Calendar,
  User,
  Gift,
  ExternalLink,
  Star,
} from 'lucide-react';
import useAuth from '../../hooks/useAuth';
import { getMediaUrl } from '../../utils/assetHelper';
import VerifiedBadge from '../common/VerifiedBadge';
import AccountVerificationModal from '../verification/AccountVerificationModal';
import PaidEventQualificationModal from './PaidEventQualificationModal';

function formatEventDate(dateString) {
  if (!dateString) return { month: 'JAN', day: '01' };
  const date = new Date(dateString);
  const month = date.toLocaleString('en-US', { month: 'short' }).toUpperCase();
  const day = date.getDate().toString().padStart(2, '0');
  return { month, day };
}

function formatEventTime(timeString) {
  if (!timeString) return 'All Day';
  try {
    const [hours, minutes] = timeString.split(':');
    const h = parseInt(hours, 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const formattedHours = h % 12 || 12;
    return `${formattedHours}:${minutes} ${ampm}`;
  } catch {
    return timeString;
  }
}

function formatRewardAmount(val) {
  if (val === undefined || val === null || val === '') return null;
  if (typeof val === 'string' && val.startsWith('$')) return val;
  const num = Number(val);
  if (Number.isNaN(num)) return null;
  return `$${num.toFixed(4)}`;
}

export function EventCard({ event, initialUserResponse = null, onResponseChange = null }) {
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();
  const [, setUserResponse] = useState(
    event.user_response || initialUserResponse || null
  );
  const [guestsCount, setGuestsCount] = useState(event.guests_count || 0);
  const [isSubmitting] = useState(false);
  const [imgError, setImgError] = useState(false);
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [verifyMessage, setVerifyMessage] = useState('Please verify your phone number first before proceeding.');
  const [showRewardModal, setShowRewardModal] = useState(false);
  const [isRewardedLocal, setIsRewardedLocal] = useState(false);

  const { month, day } = formatEventDate(event.start_date);
  const cover = !imgError && (event.cover_photo_url || getMediaUrl(event.cover_photo, 'events'));

  const isOwner = Boolean(
    currentUser && (
      (event.organizer_id && currentUser.id === event.organizer_id) ||
      (event.organizer?.id && currentUser.id === event.organizer.id) ||
      (event.member_id && currentUser.id === event.member_id) ||
      (event.campaign?.member_id && currentUser.id === event.campaign.member_id) ||
      (event.campaign_details?.member_id && currentUser.id === event.campaign_details.member_id) ||
      event.campaign?.is_owner ||
      event.campaign_details?.is_owner ||
      event.is_owner ||
      event.is_organizer ||
      event.is_host
    )
  );

  const hasCampaign = Boolean(
    event.is_paid ||
    event.campaign ||
    event.campaign_details ||
    (event.campaign?.budget && Number(event.campaign.budget) > 0) ||
    (event.campaign_details?.budget && Number(event.campaign_details.budget) > 0)
  );

  const isAlreadyRewarded = isRewardedLocal || Boolean(
    event.already_rewarded ||
    event.campaign?.already_rewarded ||
    event.campaign_details?.already_rewarded
  );

  const isCampaignEligible = Boolean(
    event.is_campaign_eligible ??
    (event.campaign?.is_eligible ??
    (event.campaign_details?.is_eligible ?? true))
  );

  const earnUpToAmount =
    event.earn_up_to_formatted ||
    event.campaign?.earn_up_to_formatted ||
    event.campaign_details?.earn_up_to_formatted ||
    formatRewardAmount(event.earn_up_to_usd) ||
    formatRewardAmount(event.campaign?.earn_up_to_usd) ||
    '$0.0250';

  const handleEarnClick = (e) => {
    e.preventDefault();
    e.stopPropagation();

    const isVerified = currentUser?.is_verified || currentUser?.mobile_verified_at;
    if (!isVerified) {
      setVerifyMessage('Please verify your phone number first before proceeding.');
      setShowVerifyModal(true);
      return;
    }

    setShowRewardModal(true);
  };

  const isOnline = event.event_type === 'online';
  const locationLabel = isOnline
    ? 'Online Event'
    : (event.location_city || event.location_venue || 'In-Person');

  return (
    <article className="event-card" data-event-card={event.id}>
      <Link to={`/member/events/${event.id}`} className="event-card__cover-link">
        <div className="event-card__cover">
          {cover ? (
            <img
              src={cover}
              alt={event.title}
              loading="lazy"
              onError={() => setImgError(true)}
            />
          ) : (
            <div className="event-card__cover-placeholder">
              <Calendar size={36} aria-hidden="true" />
            </div>
          )}
          <div className="event-card__cover-overlay" />

          {/* Type Badge */}
          <span className={`event-card__badge ${isOnline ? 'badge-online' : 'badge-offline'}`}>
            {isOnline ? (
              <Video size={12} aria-hidden="true" />
            ) : (
              <MapPin size={12} aria-hidden="true" />
            )}
            <span>{isOnline ? 'Online' : 'In-Person'}</span>
          </span>

          {/* Category Tag */}
          {event.category && (
            <span className="event-card__category">
              <Tag size={11} aria-hidden="true" />
              <span>{event.category}</span>
            </span>
          )}
        </div>
      </Link>

      <div className="event-card__body">
        <div className="event-card__main-row">
          {/* Calendar Date Block */}
          <div className="event-card__date" title={`Date: ${month} ${day}`}>
            <span className="event-card__month">{month}</span>
            <span className="event-card__day">{day}</span>
          </div>

          {/* Text Info */}
          <div className="event-card__info">
            <h3 className="event-card__title" title={event.title}>
              <Link to={`/member/events/${event.id}`}>{event.title}</Link>
            </h3>

            <div className="event-card__meta">
              <span className="event-card__meta-item">
                <Clock size={13} aria-hidden="true" />
                <span>{formatEventTime(event.start_time)}</span>
              </span>

              <span className="event-card__meta-item">
                {isOnline ? <Video size={13} aria-hidden="true" /> : <MapPin size={13} aria-hidden="true" />}
                <span className="event-card__location" title={locationLabel}>{locationLabel}</span>
              </span>

              <span className="event-card__meta-item">
                <Users size={13} aria-hidden="true" />
                <span>{guestsCount} {guestsCount === 1 ? 'person' : 'people'} responded</span>
              </span>

              {event.organizer && (
                <span className="event-card__meta-item event-card__meta-item--organizer" style={{ color: '#475569', display: 'inline-flex', alignItems: 'center', flexWrap: 'wrap' }}>
                  <User size={13} aria-hidden="true" />
                  <span>Hosted by <strong>{event.organizer.name}</strong></span>
                  <VerifiedBadge member={event.organizer} size={13} />
                </span>
              )}
            </div>
          </div>
        </div>

        {/* Action Controls */}
        <div className="event-card__footer">
          {isOwner ? (
            <Link
              to={`/member/events/${event.id}`}
              className="event-card__btn event-card__btn--manage"
              style={{
                backgroundColor: '#f1f5f9',
                color: '#334155',
                borderColor: '#cbd5e1',
                textDecoration: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                fontWeight: 600,
                fontSize: '13px',
                padding: '7px 14px',
                borderRadius: '8px',
                border: '1px solid #cbd5e1',
              }}
              title="Manage this event"
              aria-label={`Manage ${event.title}`}
            >
              <span>Manage</span>
              <ExternalLink size={13} aria-hidden="true" />
            </Link>
          ) : (
            <div className="event-card__rsvp-actions">
              {hasCampaign && (
                <>
                  {isAlreadyRewarded ? (
                    <span
                      className="event-card__btn event-card__btn--rewarded"
                      title="You have already qualified and received your reward for this event."
                    >
                      <Check size={14} aria-hidden="true" />
                      <span>Rewarded</span>
                    </span>
                  ) : !isCampaignEligible ? (
                    <button
                      type="button"
                      className="event-card__btn event-card__btn--exhausted"
                      disabled
                      title="Campaign budget exhausted or inactive"
                    >
                      <Gift size={14} aria-hidden="true" />
                      <span>Campaign Exhausted</span>
                    </button>
                  ) : (
                    <button
                      type="button"
                      className="event-card__btn event-card__btn--interested"
                      onClick={handleEarnClick}
                      disabled={isSubmitting}
                      title={earnUpToAmount ? `Interested / Earn up to ${earnUpToAmount}` : 'Interested'}
                      aria-label={earnUpToAmount ? `Show interest and earn up to ${earnUpToAmount} in ${event.title}` : `Show interest in ${event.title}`}
                    >
                      <Star size={14} aria-hidden="true" />
                      <span>{earnUpToAmount ? `Interested / Earn up to ${earnUpToAmount}` : 'Interested'}</span>
                    </button>
                  )}
                </>
              )}
            </div>
          )}

          <Link
            to={`/member/events/${event.id}`}
            className="event-card__view-link"
            aria-label={`View details for ${event.title}`}
          >
            <span>View</span>
            <ArrowRight size={13} aria-hidden="true" />
          </Link>
        </div>
      </div>

      {/* Paid Event Qualification Modal */}
      <PaidEventQualificationModal
        isOpen={showRewardModal}
        onClose={() => setShowRewardModal(false)}
        event={event}
        campaign={event.campaign || event.campaign_details}
        onSuccess={() => {
          setIsRewardedLocal(true);
          setUserResponse('interested');
          setGuestsCount((prev) => prev + 1);
          if (onResponseChange) {
            onResponseChange(event.id, 'interested');
          }
          setTimeout(() => {
            navigate(`/member/events/${event.id}`);
          }, 1500);
        }}
      />

      {/* Account Verification Modal */}
      <AccountVerificationModal
        isOpen={showVerifyModal}
        initialError={verifyMessage}
        onClose={() => setShowVerifyModal(false)}
      />
    </article>
  );
}

export default EventCard;
