import { useState, useEffect } from 'react';
import {
  X,
  Gift,
  CheckCircle2,
  AlertCircle,
  Info,
  Layers,
  MapPin,
  Globe,
  Loader2,
  Sparkles,
} from 'lucide-react';
import eventApi from '../../api/eventApi';
import { ModalPortal } from '../common/ModalPortal';

export default function PaidEventQualificationModal({
  isOpen,
  onClose,
  event,
  campaign,
  onSuccess,
}) {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [previewData, setPreviewData] = useState(null);
  const [error, setError] = useState(null);
  const [successResult, setSuccessResult] = useState(null);
  const [consentAccepted, setConsentAccepted] = useState(false);

  const eventId = event?.id;

  useEffect(() => {
    if (!isOpen || !eventId) {
      setPreviewData(null);
      setError(null);
      setSuccessResult(null);
      setLoading(true);
      setConsentAccepted(false);
      return;
    }

    let isMounted = true;
    setLoading(true);
    setError(null);
    setSuccessResult(null);
    setConsentAccepted(false);

    eventApi
      .getEventRewardPreview(eventId, campaign?.id)
      .then((data) => {
        if (isMounted) {
          if (data && data.success) {
            setPreviewData(data);
          } else {
            setError(data?.message || 'Unable to calculate your current event reward.');
          }
        }
      })
      .catch((err) => {
        if (isMounted) {
          const msg =
            err.response?.data?.message ||
            'Unable to load your event reward preview. Please ensure your account is verified.';
          setError(msg);
        }
      })
      .finally(() => {
        if (isMounted) {
          setLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [isOpen, eventId, campaign?.id]);

  const handleClose = () => {
    if (submitting) return;
    setConsentAccepted(false);
    onClose();
  };

  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.key === 'Escape' && isOpen && !submitting) {
        setConsentAccepted(false);
        onClose();
      }
    };
    if (isOpen) {
      document.addEventListener('keydown', handleKeyDown);
    }
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, onClose, submitting]);

  const handleInterestedSubmit = async () => {
    if (submitting || !eventId || !consentAccepted) return;

    setSubmitting(true);
    setError(null);

    try {
      const response = await eventApi.qualifyEventInterest(eventId, { consent_accepted: true });

      if (response && response.success) {
        setSuccessResult(response);
        if (onSuccess) {
          onSuccess(response);
        }

        // After a brief celebratory display, close modal so the surface updates to Rewarded in-place
        setTimeout(() => {
          onClose();
        }, 1600);
      } else {
        setError(response?.message || 'Unable to qualify for event reward. Please try again.');
      }
    } catch (err) {
      const msg =
        err.response?.data?.message ||
        'An error occurred while processing your reward qualification. Please try again.';
      setError(msg);
    } finally {
      setSubmitting(false);
    }
  };

  if (!isOpen) return null;

  const verifiedCount = previewData?.direct_verified_referral_count ?? 0;
  const currentRewardExact = previewData?.reward_amount_exact || (previewData?.reward_amount_usd ? Number(previewData.reward_amount_usd).toFixed(4) : '0.0000');
  const matchedRange = previewData?.matched_range;
  const allRules = previewData?.all_active_rules || [];
  const eventTitle = event?.title || previewData?.event_title || 'Paid Event';
  const organizerName = event?.organizer?.name || event?.member?.name || 'Event Host';

  return (
    <ModalPortal isOpen={isOpen} onClose={() => { if (!submitting) onClose(); }}>
      <div
        className="card modal-dialog-custom"
        role="dialog"
        aria-modal="true"
        aria-labelledby="paid-event-qualification-title"
        onClick={(e) => e.stopPropagation()}
        style={{
          width: '100%',
          maxWidth: '520px',
          maxHeight: 'min(90vh, 740px)',
          overflowY: 'auto',
          backgroundColor: '#ffffff',
          borderRadius: '16px',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          border: '1px solid #e2e8f0',
          display: 'flex',
          flexDirection: 'column',
          position: 'relative',
        }}
      >
        {/* Modal Header */}
        <div
          style={{
            padding: '18px 22px',
            borderBottom: '1px solid #f1f5f9',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: '12px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <div
              style={{
                width: '38px',
                height: '38px',
                borderRadius: '10px',
                backgroundColor: '#ecfdf5',
                color: '#059669',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Gift size={22} />
            </div>
            <div>
              <h3 style={{ margin: 0, fontSize: '16.5px', fontWeight: 700, color: '#0f172a' }}>
                Event Reward Qualification
              </h3>
              <p style={{ margin: '2px 0 0 0', fontSize: '12px', color: '#64748b' }}>
                Hosted by {organizerName}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            disabled={submitting}
            aria-label="Close"
            style={{
              background: 'none',
              border: 'none',
              color: '#94a3b8',
              cursor: submitting ? 'not-allowed' : 'pointer',
              padding: '6px',
              borderRadius: '8px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.15s ease',
            }}
          >
            <X size={18} />
          </button>
        </div>

        {/* Modal Body */}
        <div style={{ padding: '22px', display: 'flex', flexDirection: 'column', gap: '18px' }}>
          {/* Event Context Strip */}
          <div
            style={{
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              padding: '12px 16px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '12px',
            }}
          >
            <div style={{ display: 'flex', flexDirection: 'column', gap: '2px' }}>
              <span style={{ fontSize: '14px', fontWeight: 700, color: '#1e293b' }}>
                {eventTitle}
              </span>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '11.5px', color: '#64748b' }}>
                {event?.is_online ? (
                  <span style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
                    <Globe size={12} color="#059669" /> Online Event
                  </span>
                ) : event?.location ? (
                  <span style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
                    <MapPin size={12} color="#059669" /> {event.location}
                  </span>
                ) : null}
                {event?.category && <span>• {event.category}</span>}
              </div>
            </div>
            <span
              style={{
                fontSize: '11px',
                fontWeight: 700,
                backgroundColor: '#ecfdf5',
                color: '#065f46',
                padding: '4px 8px',
                borderRadius: '6px',
                border: '1px solid #a7f3d0',
                textTransform: 'uppercase',
                letterSpacing: '0.04em',
                flexShrink: 0,
              }}
            >
              Sponsored
            </span>
          </div>

          {loading ? (
            <div
              style={{
                padding: '40px 20px',
                textAlign: 'center',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: '12px',
              }}
            >
              <div
                style={{
                  width: '34px',
                  height: '34px',
                  border: '3px solid #e2e8f0',
                  borderTopColor: '#059669',
                  borderRadius: '50%',
                  animation: 'spin 0.8s linear infinite',
                }}
              />
              <span style={{ fontSize: '13.5px', color: '#64748b', fontWeight: 500 }}>
                Loading your personalized reward eligibility...
              </span>
            </div>
          ) : successResult ? (
            /* Celebratory Success Banner */
            <div
              style={{
                background: 'linear-gradient(135deg, #059669 0%, #047857 100%)',
                borderRadius: '14px',
                padding: '24px 20px',
                color: '#ffffff',
                textAlign: 'center',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: '10px',
                boxShadow: '0 8px 20px rgba(5, 150, 105, 0.3)',
              }}
            >
              <div
                style={{
                  width: '48px',
                  height: '48px',
                  borderRadius: '50%',
                  backgroundColor: '#ffffff',
                  color: '#059669',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Sparkles size={28} />
              </div>
              <h4 style={{ margin: 0, fontSize: '18px', fontWeight: 800 }}>
                {successResult.already_rewarded ? 'Already Qualified!' : 'Reward Credited!'}
              </h4>
              <p style={{ margin: 0, fontSize: '13px', opacity: 0.95, maxWidth: '380px' }}>
                {successResult.message || `You earned $${successResult.reward_amount_exact || (successResult.reward_amount_usd ? Number(successResult.reward_amount_usd).toFixed(4) : '0.0000')} USD credited to your Wallet.`}
              </p>
              <span style={{ fontSize: '11.5px', opacity: 0.8, marginTop: '4px' }}>
                Reward credited to your Wallet.
              </span>
            </div>
          ) : error ? (
            <div
              style={{
                padding: '16px',
                backgroundColor: '#fef2f2',
                border: '1px solid #fecaca',
                borderRadius: '12px',
                display: 'flex',
                alignItems: 'flex-start',
                gap: '10px',
              }}
            >
              <AlertCircle size={18} color="#dc2626" style={{ flexShrink: 0, marginTop: '2px' }} />
              <div>
                <strong style={{ fontSize: '13px', color: '#991b1b', display: 'block' }}>
                  Qualification Notice
                </strong>
                <p style={{ margin: '4px 0 0 0', fontSize: '12.5px', color: '#b91c1c' }}>
                  {error}
                </p>
              </div>
            </div>
          ) : (
            <>
              {Boolean(previewData?.already_rewarded) && (
                <div
                  style={{
                    padding: '12px 16px',
                    backgroundColor: '#ecfdf5',
                    border: '1px solid #a7f3d0',
                    borderRadius: '12px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '10px',
                  }}
                >
                  <CheckCircle2 size={18} color="#059669" style={{ flexShrink: 0 }} />
                  <span style={{ fontSize: '13px', color: '#047857', fontWeight: 600 }}>
                    You have already qualified and received your reward for this event.
                  </span>
                </div>
              )}

              {/* Member Tier Summary Card */}
              <div
                style={{
                  background: 'linear-gradient(135deg, #059669 0%, #047857 100%)',
                  borderRadius: '14px',
                  padding: '18px 20px',
                  color: '#ffffff',
                  boxShadow: '0 4px 12px rgba(5, 150, 105, 0.25)',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '14px',
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <span style={{ fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.05em', opacity: 0.9, fontWeight: 600 }}>
                      Your Direct Verified Referrals
                    </span>
                    <div style={{ fontSize: '28px', fontWeight: 800, marginTop: '2px', letterSpacing: '-0.02em' }}>
                      {verifiedCount}
                    </div>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    <span style={{ fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.05em', opacity: 0.9, fontWeight: 600 }}>
                      Your Qualifying Reward
                    </span>
                    <div style={{ fontSize: '28px', fontWeight: 800, marginTop: '2px', color: '#ecfdf5', letterSpacing: '-0.02em' }}>
                      ${currentRewardExact} <span style={{ fontSize: '14px', fontWeight: 600 }}>USD</span>
                    </div>
                  </div>
                </div>

                <div
                  style={{
                    backgroundColor: 'rgba(255, 255, 255, 0.14)',
                    borderRadius: '8px',
                    padding: '8px 12px',
                    fontSize: '11.5px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px',
                  }}
                >
                  <Info size={14} style={{ flexShrink: 0 }} />
                  <span>
                    Your reward depends on the number of direct members who have completed mobile/WhatsApp verification.
                  </span>
                </div>
              </div>

              {/* Reward Levels List */}
              <div>
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    marginBottom: '10px',
                  }}
                >
                  <span style={{ fontSize: '13px', fontWeight: 700, color: '#334155', display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <Layers size={15} color="#059669" />
                    <span>Event Reward Levels</span>
                  </span>
                  {matchedRange && (
                    <span
                      style={{
                        fontSize: '11px',
                        fontWeight: 700,
                        backgroundColor: '#d1fae5',
                        color: '#065f46',
                        padding: '2px 8px',
                        borderRadius: '999px',
                        border: '1px solid #a7f3d0',
                      }}
                    >
                      Your Level: {matchedRange.label}
                    </span>
                  )}
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                  {allRules.map((rule) => {
                    const isSelected = rule.is_current;
                    return (
                      <div
                        key={rule.id}
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          padding: '10px 14px',
                          borderRadius: '10px',
                          border: isSelected ? '1.5px solid #059669' : '1px solid #e2e8f0',
                          backgroundColor: isSelected ? '#f0fdf4' : '#f8fafc',
                          transition: 'all 0.15s ease',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <span
                            style={{
                              fontSize: '13px',
                              fontWeight: isSelected ? 700 : 600,
                              color: isSelected ? '#065f46' : '#1e293b',
                            }}
                          >
                            {rule.label} verified referrals
                          </span>
                          {isSelected && (
                            <span
                              style={{
                                fontSize: '10.5px',
                                fontWeight: 700,
                                backgroundColor: '#059669',
                                color: '#ffffff',
                                padding: '1px 6px',
                                borderRadius: '4px',
                              }}
                            >
                              Current
                            </span>
                          )}
                        </div>
                        <span
                          style={{
                            fontSize: '13.5px',
                            fontWeight: isSelected ? 800 : 700,
                            color: isSelected ? '#047857' : '#475569',
                          }}
                        >
                          {rule.is_unconfigured ? 'Config Required' : rule.reward_amount_formatted}
                        </span>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Reward Conditions Card */}
              <div
                style={{
                  backgroundColor: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '14px 16px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '8px',
                }}
              >
                <span style={{ fontSize: '12px', fontWeight: 700, color: '#475569', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  Qualification Conditions
                </span>
                <ul style={{ margin: 0, padding: 0, listStyle: 'none', display: 'flex', flexDirection: 'column', gap: '6px' }}>
                  {[
                    'WhatsApp / mobile verified member required for reward qualification.',
                    'Reward is resolved dynamically based on current direct verified referrals.',
                    'Clicking "Interested" acts as the qualifying trigger for this event.',
                    'One reward per member per event campaign.',
                    'Reward amount is credited directly to your Wallet upon confirmation.',
                  ].map((condition, idx) => (
                    <li
                      key={idx}
                      style={{
                        display: 'flex',
                        alignItems: 'flex-start',
                        gap: '8px',
                        fontSize: '12px',
                        color: '#475569',
                        lineHeight: 1.4,
                      }}
                    >
                      <CheckCircle2 size={14} color="#059669" style={{ flexShrink: 0, marginTop: '2px' }} />
                      <span>{condition}</span>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Profile-Sharing Consent Checkbox */}
              <div
                style={{
                  padding: '4px 2px',
                }}
                onClick={(e) => e.stopPropagation()}
              >
                <label
                  htmlFor="event-reward-consent-checkbox"
                  onClick={(e) => e.stopPropagation()}
                  style={{
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '9px',
                    cursor: loading || submitting || Boolean(previewData?.already_rewarded) ? 'not-allowed' : 'pointer',
                    fontSize: '12.5px',
                    fontWeight: 500,
                    color: '#334155',
                    lineHeight: 1.45,
                    userSelect: 'none',
                  }}
                >
                  <input
                    id="event-reward-consent-checkbox"
                    type="checkbox"
                    checked={consentAccepted}
                    onChange={(e) => {
                      e.stopPropagation();
                      setConsentAccepted(e.target.checked);
                    }}
                    onClick={(e) => e.stopPropagation()}
                    disabled={loading || submitting || Boolean(previewData?.already_rewarded)}
                    style={{
                      marginTop: '2px',
                      width: '16px',
                      height: '16px',
                      accentColor: '#059669',
                      cursor: loading || submitting || Boolean(previewData?.already_rewarded) ? 'not-allowed' : 'pointer',
                      flexShrink: 0,
                    }}
                  />
                  <span>
                    I agree that my profile content may be shared with the owner if I show interest.
                  </span>
                </label>
              </div>
            </>
          )}
        </div>

        {/* Modal Footer */}
        <div
          style={{
            padding: '16px 22px',
            borderTop: '1px solid #f1f5f9',
            backgroundColor: '#fafafa',
            borderBottomLeftRadius: '16px',
            borderBottomRightRadius: '16px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'flex-end',
            gap: '10px',
          }}
        >
          <button
            type="button"
            onClick={handleClose}
            disabled={submitting}
            className="member-button"
            style={{
              backgroundColor: '#f1f5f9',
              color: '#475569',
              fontSize: '13px',
              fontWeight: 600,
              padding: '8px 18px',
              borderRadius: '8px',
              border: 'none',
              cursor: submitting ? 'not-allowed' : 'pointer',
            }}
          >
            Cancel
          </button>
          {!successResult && (
            <button
              type="button"
              onClick={handleInterestedSubmit}
              disabled={loading || submitting || Boolean(error) || Boolean(previewData?.already_rewarded) || !consentAccepted}
              className="member-button"
              style={{
                backgroundColor: loading || submitting || error || previewData?.already_rewarded || !consentAccepted ? '#94a3b8' : '#059669',
                color: '#ffffff',
                fontSize: '13px',
                fontWeight: 700,
                padding: '8px 22px',
                borderRadius: '8px',
                border: 'none',
                cursor: loading || submitting || error || previewData?.already_rewarded || !consentAccepted ? 'not-allowed' : 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '6px',
                boxShadow: loading || submitting || error || previewData?.already_rewarded || !consentAccepted ? 'none' : '0 2px 4px rgba(5, 150, 105, 0.25)',
              }}
            >
              {submitting ? (
                <>
                  <Loader2 size={15} style={{ animation: 'spin 0.8s linear infinite' }} />
                  <span>Claiming Reward...</span>
                </>
              ) : previewData?.already_rewarded ? (
                <>
                  <CheckCircle2 size={15} />
                  <span>Already Rewarded</span>
                </>
              ) : (
                <span>Interested</span>
              )}
            </button>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}
