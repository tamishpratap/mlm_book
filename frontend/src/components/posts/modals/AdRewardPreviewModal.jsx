import { useState, useEffect } from 'react';
import {
  X,
  Gift,
  CheckCircle2,
  AlertCircle,
  Info,
  Layers,
  Building2,
  Globe,
  MapPin,
  Loader2,
} from 'lucide-react';
import postApi from '../../../api/postApi';
import { ModalPortal } from '../../common/ModalPortal';

export default function AdRewardPreviewModal({
  isOpen,
  onClose,
  campaignId,
  businessPage,
  event,
  onContinue,
}) {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [previewData, setPreviewData] = useState(null);
  const [error, setError] = useState(null);
  const [consentAccepted, setConsentAccepted] = useState(false);

  useEffect(() => {
    if (!isOpen || !campaignId) {
      setPreviewData(null);
      setError(null);
      setLoading(true);
      setConsentAccepted(false);
      setSubmitting(false);
      return;
    }

    let isMounted = true;
    setLoading(true);
    setError(null);
    setConsentAccepted(false);
    setSubmitting(false);

    postApi
      .getCampaignRewardPreview(campaignId)
      .then((data) => {
        if (isMounted) {
          if (data && data.success) {
            setPreviewData(data);
          } else {
            setError(data?.message || 'Unable to calculate your current reward. Please try again.');
          }
        }
      })
      .catch((err) => {
        if (isMounted) {
          const msg =
            err.response?.data?.message ||
            'Unable to calculate your current reward. Please try again.';
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
  }, [isOpen, campaignId]);

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

  if (!isOpen) return null;

  const isEvent = Boolean(event || previewData?.campaign?.campaign_type === 'event' || previewData?.event_title);
  const memberReward = previewData?.member_reward || {};
  const verifiedCount = memberReward.direct_verified_referral_count ?? previewData?.direct_verified_referral_count ?? 0;
  const currentRewardUsd = memberReward.applicable_reward_usd ?? previewData?.reward_amount_usd ?? 0.0;
  const currentRewardExact = memberReward.applicable_reward_exact || previewData?.reward_amount_exact || (previewData?.reward_amount_usd ? Number(previewData.reward_amount_usd).toFixed(4) : Number(currentRewardUsd).toFixed(4));
  const matchedRange = memberReward.matched_range ?? previewData?.matched_range;
  const allRules = previewData?.all_active_rules || [];

  const pageName = businessPage?.page_name || previewData?.campaign?.business_page_name || 'Promoted Business';
  const organizerName = event?.organizer?.name || event?.member?.name || previewData?.organizer_name || 'Event Host';
  const contextTitle = isEvent
    ? (event?.title || previewData?.event_title || 'Paid Event')
    : (previewData?.campaign?.campaign_name || businessPage?.page_name || 'Promoted Business Campaign');
  const category = isEvent
    ? (event?.category || previewData?.category || null)
    : (businessPage?.category || businessPage?.industry || previewData?.campaign?.category || null);

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="card modal-dialog-custom"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ad-reward-preview-title"
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
                {isEvent ? 'Event Reward Qualification' : 'Campaign Reward Information'}
              </h3>
              <p style={{ margin: '2px 0 0 0', fontSize: '12px', color: '#64748b' }}>
                {isEvent ? `Hosted by ${organizerName}` : `Promoted by ${pageName}`}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            style={{
              background: 'none',
              border: 'none',
              color: '#94a3b8',
              cursor: 'pointer',
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
          {/* Campaign Context Strip */}
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
                {contextTitle}
              </span>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '11.5px', color: '#64748b' }}>
                {isEvent ? (
                  event?.is_online ? (
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
                      <Globe size={12} color="#059669" /> Online Event
                    </span>
                  ) : event?.location ? (
                    <span style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
                      <MapPin size={12} color="#059669" /> {event.location}
                    </span>
                  ) : null
                ) : (
                  <span style={{ display: 'inline-flex', alignItems: 'center', gap: '3px' }}>
                    <Building2 size={12} color="#059669" /> {pageName}
                  </span>
                )}
                {category ? (
                  <span>• {category}</span>
                ) : !isEvent ? (
                  <span>• Business Page Campaign</span>
                ) : null}
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
                  Reward Configuration Notice
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
                    You have already qualified and received your reward for this campaign.
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
                    <span>{isEvent ? 'Event Reward Levels' : 'Campaign Reward Levels'}</span>
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

              {/* Qualification Conditions Card */}
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
                  {(isEvent
                    ? [
                        'WhatsApp / mobile verified member required for reward qualification.',
                        'Reward is resolved dynamically based on current direct verified referrals.',
                        'Clicking "Interested" acts as the qualifying trigger for this event.',
                        'One reward per member per event campaign.',
                        'Reward amount is credited directly to your Wallet upon confirmation.',
                      ]
                    : [
                        'WhatsApp / mobile verified member required for reward qualification.',
                        'Reward is resolved dynamically based on current direct verified referrals.',
                        'Clicking continue opens the campaign target and does not pay immediately.',
                        'Reward is issued only after valid campaign qualification.',
                        'One reward per member per campaign.',
                      ]
                  ).map((condition, idx) => (
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
              >
                <label
                  htmlFor="business-ad-consent-checkbox"
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
                    id="business-ad-consent-checkbox"
                    type="checkbox"
                    checked={consentAccepted}
                    onChange={(e) => setConsentAccepted(e.target.checked)}
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
          <button
            type="button"
            onClick={async () => {
              if (!consentAccepted || loading || submitting || error || previewData?.already_rewarded) return;
              if (onContinue) {
                setSubmitting(true);
                try {
                  await onContinue({ consent_accepted: true });
                } catch (err) {
                  const msg =
                    err?.response?.data?.message ||
                    err?.message ||
                    'Unable to record interest. Please try again.';
                  setError(msg);
                } finally {
                  setSubmitting(false);
                }
              }
            }}
            disabled={loading || submitting || Boolean(error) || !consentAccepted || Boolean(previewData?.already_rewarded)}
            className="member-button"
            style={{
              backgroundColor: loading || submitting || error || !consentAccepted || previewData?.already_rewarded ? '#94a3b8' : '#059669',
              color: '#ffffff',
              fontSize: '13px',
              fontWeight: 700,
              padding: '8px 22px',
              borderRadius: '8px',
              border: 'none',
              cursor: loading || submitting || error || !consentAccepted || previewData?.already_rewarded ? 'not-allowed' : 'pointer',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              boxShadow: loading || submitting || error || !consentAccepted || previewData?.already_rewarded ? 'none' : '0 2px 4px rgba(5, 150, 105, 0.25)',
            }}
          >
            {submitting ? (
              <>
                <Loader2 size={15} style={{ animation: 'spin 0.8s linear infinite' }} />
                <span>Processing...</span>
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
        </div>
      </div>
    </ModalPortal>
  );
}
