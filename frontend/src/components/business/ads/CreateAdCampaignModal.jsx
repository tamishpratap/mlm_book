import { useState, useEffect } from 'react';
import {
  X,
  Megaphone,
  Wallet,
  CheckCircle2,
  AlertCircle,
  Sparkles,
  ChevronRight,
  ChevronLeft,
  FileText,
  ShieldCheck,
} from 'lucide-react';
import businessApi from '../../../api/businessApi';
import { getMediaUrl } from '../../../utils/assetHelper';
import { ModalPortal } from '../../common/ModalPortal';

const BUDGET_PRESETS = [10, 25, 50, 100, 250, 500];

function formatDateTimeForInput(date) {
  const pad = (n) => String(n).padStart(2, '0');
  const yyyy = date.getFullYear();
  const mm = pad(date.getMonth() + 1);
  const dd = pad(date.getDate());
  const hh = pad(date.getHours());
  const mi = pad(date.getMinutes());
  return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
}

export function CreateAdCampaignModal({
  page,
  initialPost = null,
  availablePosts = [],
  hasPageContent = null,
  availableAdFunds = 0.00,
  platformFeePercent = 2.5,
  onAddFundClick = null,
  onOpenExistingCampaign = null,
  onClose,
  onCampaignCreated,
}) {
  const [selectedPost, setSelectedPost] = useState(initialPost);
  const [campaignName, setCampaignName] = useState('');
  const [budget, setBudget] = useState('50');
  const [startAt, setStartAt] = useState('');
  const [step, setStep] = useState(1); // 1: Setup, 2: Review
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [existingCampaignInfo, setExistingCampaignInfo] = useState(null);
  const [, setIsCheckingExisting] = useState(false);
  const [errors, setErrors] = useState({});
  const [generalError, setGeneralError] = useState('');

  const hasContent = hasPageContent !== null
    ? hasPageContent
    : (Boolean(selectedPost) || (Array.isArray(availablePosts) && availablePosts.length > 0) || Boolean(initialPost));

  const numBudget = parseFloat(budget) || 0;
  const numFeePercent = (platformFeePercent !== undefined && platformFeePercent !== null && !isNaN(Number(platformFeePercent)))
    ? Number(platformFeePercent)
    : 0;
  const feeAmount = parseFloat((numBudget * (numFeePercent / 100)).toFixed(2));
  const totalWalletDebit = parseFloat((numBudget + feeAmount).toFixed(2));

  const postMedia = selectedPost?.media_path || selectedPost?.media_url || selectedPost?.media;
  const isVideoPost = Boolean(
    postMedia && (
      selectedPost?.media_type === 'video' ||
      selectedPost?.type === 'video' ||
      (typeof selectedPost?.mime_type === 'string' && selectedPost.mime_type.startsWith('video/')) ||
      (typeof postMedia === 'string' && postMedia.match(/\.(mp4|webm|mov|ogg|m4v)(\?.*)?$/i))
    )
  );
  const resolvedMediaUrl = postMedia ? getMediaUrl(postMedia) : null;

  useEffect(() => {
    const now = new Date();
    setStartAt(formatDateTimeForInput(now));

    const defaultName = initialPost?.body
      ? `${page?.page_name || 'Business'} Ad - ${initialPost.body.slice(0, 20)}...`
      : `${page?.page_name || 'Business'} Campaign - ${now.toLocaleDateString()}`;
    setCampaignName(defaultName);

    if (initialPost) {
      setSelectedPost(initialPost);
    } else if (availablePosts.length > 0) {
      setSelectedPost(availablePosts[0]);
    }
  }, [initialPost, availablePosts, page]);

  // Check if selected post already has an active or existing campaign
  useEffect(() => {
    if (!page?.slug || !selectedPost?.id) {
      setExistingCampaignInfo(null);
      return;
    }

    let isMounted = true;
    setIsCheckingExisting(true);

    businessApi
      .checkPostAdCampaign(page.slug, selectedPost.id)
      .then((res) => {
        if (isMounted) {
          if (res?.has_campaign && res?.campaign) {
            setExistingCampaignInfo(res.campaign);
          } else {
            setExistingCampaignInfo(null);
          }
        }
      })
      .catch((err) => {
        console.error('Failed to check post campaign status:', err);
      })
      .finally(() => {
        if (isMounted) {
          setIsCheckingExisting(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [page, selectedPost]);

  const validateStep1 = () => {
    const errs = {};
    if (!hasContent) {
      errs.post = 'Please add at least one post or photo to your Business Page before running an ad campaign.';
      setGeneralError('Please add at least one post or photo to your Business Page before running an ad campaign.');
    }

    if (existingCampaignInfo) {
      errs.post = `This post already has an existing campaign ('${existingCampaignInfo.campaign_name}'). Add funds to the existing campaign instead.`;
    }

    if (!campaignName.trim()) {
      errs.campaignName = 'Campaign name is required.';
    } else if (campaignName.length > 255) {
      errs.campaignName = 'Campaign name cannot exceed 255 characters.';
    }

    if (!budget || isNaN(numBudget) || numBudget <= 0) {
      errs.budget = 'Please enter a valid budget greater than $0.';
    } else if (numBudget > 999999999) {
      errs.budget = 'Budget amount is too large.';
    } else if (availableAdFunds !== undefined && totalWalletDebit > Number(availableAdFunds)) {
      const shortfall = (totalWalletDebit - Number(availableAdFunds)).toFixed(2);
      errs.budget = feeAmount > 0
        ? `Insufficient advertising funds. Campaign Budget: $${numBudget.toFixed(2)} USD, Platform Fee (${numFeePercent}%): $${feeAmount.toFixed(2)} USD, Total Debit: $${totalWalletDebit.toFixed(2)} USD, Available: $${Number(availableAdFunds).toFixed(2)} USD. Shortfall: $${shortfall} USD. Please add funds.`
        : `Insufficient advertising funds. Campaign Budget: $${numBudget.toFixed(2)} USD, Available: $${Number(availableAdFunds).toFixed(2)} USD. Shortfall: $${shortfall} USD. Please add funds.`;
    }

    if (hasContent && !selectedPost && availablePosts.length > 0) {
      errs.post = 'Please select a post to promote.';
    }

    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleNextStep = () => {
    setGeneralError('');
    if (!hasContent) {
      setGeneralError('Please add at least one post or photo to your Business Page before running an ad campaign.');
      setErrors((prev) => ({
        ...prev,
        post: 'Please add at least one post or photo to your Business Page before running an ad campaign.',
      }));
      return;
    }
    if (validateStep1()) {
      setStep(2);
    }
  };

  const handleSubmit = async (submitForReview = true) => {
    if (!hasContent) {
      setGeneralError('Please add at least one post or photo to your Business Page before running an ad campaign.');
      setStep(1);
      return;
    }

    if (!validateStep1()) {
      setStep(1);
      return;
    }

    setIsSubmitting(true);
    setGeneralError('');

    try {
      const payload = {
        campaign_name: campaignName.trim(),
        post_id: selectedPost ? selectedPost.id : null,
        budget: parseFloat(budget),
        currency: 'USD',
        start_at: startAt ? new Date(startAt).toISOString().slice(0, 19).replace('T', ' ') : null,
      };

      // 1. Create Draft
      const res = await businessApi.createAdCampaign(page.slug, payload);
      let campaign = res.campaign;

      // 2. If submitting for review, transition to pending_review
      if (submitForReview && campaign) {
        const submitRes = await businessApi.submitAdCampaign(page.slug, campaign.campaign_id || campaign.id);
        campaign = submitRes.campaign;
      }

      if (onCampaignCreated) {
        onCampaignCreated(campaign);
      }
      onClose();
    } catch (err) {
      console.error('Failed to create ad campaign:', err);
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      }
      setGeneralError(
        err.response?.data?.errors?.business_page?.[0] ||
        err.response?.data?.message ||
        'Please add at least one post or photo to your Business Page before running an ad campaign.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <ModalPortal isOpen={true} onClose={() => { if (!isSubmitting) onClose(); }}>
      <div
        className="card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-ad-campaign-title"
        style={{
          width: '100%',
          maxWidth: '680px',
          maxHeight: 'min(90vh, 760px)',
          display: 'flex',
          flexDirection: 'column',
          borderRadius: '20px',
          overflow: 'hidden',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          background: 'var(--color-card-bg, #ffffff)',
        }}
      >
        {/* Modal Header */}
        <div
          style={{
            padding: '20px 24px',
            borderBottom: '1px solid var(--color-border, #e2e8f0)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            background: 'linear-gradient(to right, #f8fafc, #ffffff)',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div
              style={{
                width: '40px',
                height: '40px',
                borderRadius: '12px',
                backgroundColor: 'rgba(79, 125, 243, 0.12)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: '#4f7df3',
              }}
            >
              <Megaphone size={20} />
            </div>
            <div>
              <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0, color: 'var(--color-text-main, #1e293b)' }}>
                {step === 1 ? 'Create Ad Campaign' : 'Review & Submit Campaign'}
              </h2>
              <p style={{ margin: '2px 0 0', fontSize: '13px', color: 'var(--color-text-secondary, #64748b)' }}>
                Promote your post on <strong>{page?.page_name}</strong>
              </p>
            </div>
          </div>

          <button
            type="button"
            className="mini-button"
            onClick={onClose}
            disabled={isSubmitting}
            style={{ borderRadius: '50%', padding: '8px' }}
            aria-label="Close"
          >
            <X size={18} />
          </button>
        </div>

        {/* Modal Body */}
        <div style={{ padding: '24px', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '20px' }}>
          {generalError && (
            <div
              style={{
                padding: '12px 16px',
                borderRadius: '12px',
                backgroundColor: '#fef2f2',
                border: '1px solid #fecaca',
                color: '#b91c1c',
                fontSize: '13.5px',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
              }}
            >
              <AlertCircle size={18} style={{ flexShrink: 0 }} />
              <span>{generalError}</span>
            </div>
          )}

          {step === 1 ? (
            <>
              {!hasContent && (
                <div
                  style={{
                    padding: '14px 18px',
                    borderRadius: '12px',
                    backgroundColor: '#fffbeb',
                    border: '1px solid #fde68a',
                    color: '#92400e',
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '12px',
                    marginBottom: '4px',
                  }}
                >
                  <AlertCircle size={20} style={{ color: '#d97706', flexShrink: 0, marginTop: '2px' }} />
                  <div>
                    <div style={{ fontWeight: 700, fontSize: '13.5px', marginBottom: '2px', color: '#92400e' }}>
                      Content Required
                    </div>
                    <div style={{ fontSize: '13px', color: '#b45309', lineHeight: 1.4 }}>
                      Please add at least one post or photo to your Business Page before running an ad campaign.
                    </div>
                  </div>
                </div>
              )}

              {/* Promoted Post Preview / Selector */}
              <div>
                <label style={{ display: 'block', fontSize: '13.5px', fontWeight: 700, marginBottom: '8px', color: 'var(--color-text-main, #334155)' }}>
                  Promoted Business Post
                </label>

                {availablePosts.length > 1 && !initialPost && (
                  <select
                    value={selectedPost?.id || ''}
                    onChange={(e) => {
                      const found = availablePosts.find((p) => String(p.id) === e.target.value);
                      if (found) setSelectedPost(found);
                    }}
                    className="biz-filter-select"
                    style={{ width: '100%', marginBottom: '12px', padding: '10px 14px' }}
                  >
                    {availablePosts.map((p) => (
                      <option key={p.id} value={p.id}>
                        {p.body ? (p.body.length > 50 ? `${p.body.slice(0, 50)}...` : p.body) : `Post #${p.id} (${p.media_type || 'Media'})`}
                      </option>
                    ))}
                  </select>
                )}

                {selectedPost ? (
                  <div
                    style={{
                      border: '1px solid var(--color-border, #e2e8f0)',
                      borderRadius: '16px',
                      padding: '16px',
                      backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '10px' }}>
                      <div
                        style={{
                          width: '36px',
                          height: '36px',
                          borderRadius: '50%',
                          backgroundColor: '#4f7df3',
                          color: '#ffffff',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontWeight: 700,
                          fontSize: '13px',
                          overflow: 'hidden',
                        }}
                      >
                        {page?.logo_url ? (
                          <img src={page.logo_url} alt="" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                        ) : (
                          (page?.page_name?.[0] || 'B').toUpperCase()
                        )}
                      </div>
                      <div>
                        <div style={{ fontSize: '14px', fontWeight: 700, color: 'var(--color-text-main, #1e293b)' }}>
                          {page?.page_name}
                        </div>
                        <div style={{ fontSize: '12px', color: 'var(--color-text-secondary, #64748b)' }}>
                          @{page?.page_username || 'page'} • Business Post #{selectedPost.id}
                        </div>
                      </div>
                    </div>

                    {selectedPost.body && (
                      <p style={{ fontSize: '13.5px', color: 'var(--color-text-main, #334155)', margin: '0 0 10px 0', lineHeight: 1.5 }}>
                        {selectedPost.body}
                      </p>
                    )}

                    {postMedia && (
                      <div style={{ borderRadius: '12px', overflow: 'hidden', maxHeight: '200px', backgroundColor: '#000' }}>
                        {isVideoPost ? (
                          <video
                            src={resolvedMediaUrl}
                            style={{ width: '100%', maxHeight: '200px', objectFit: 'contain', display: 'block' }}
                            controls
                            playsInline
                            preload="metadata"
                          />
                        ) : (
                          <img
                            src={resolvedMediaUrl}
                            alt="Post Media"
                            style={{ width: '100%', maxHeight: '200px', objectFit: 'cover', display: 'block' }}
                          />
                        )}
                      </div>
                    )}

                    {existingCampaignInfo && (
                      <div
                        style={{
                          marginTop: '12px',
                          padding: '14px',
                          borderRadius: '12px',
                          backgroundColor: '#fef3c7',
                          border: '1px solid #fde68a',
                          color: '#92400e',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: '8px',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', fontWeight: 700, fontSize: '13.5px' }}>
                          <AlertCircle size={17} style={{ color: '#d97706', flexShrink: 0 }} />
                          <span>This Post Already Has an Ad Campaign</span>
                        </div>
                        <p style={{ margin: 0, fontSize: '12.5px', lineHeight: 1.4 }}>
                          Campaign <strong>{existingCampaignInfo.campaign_name}</strong> is already linked to this post. Under the <em>One Post = One Campaign</em> rule, you cannot create a second campaign for the same post. Add funds to the existing campaign instead.
                        </p>
                        {onOpenExistingCampaign && (
                          <button
                            type="button"
                            onClick={() => {
                              onClose();
                              onOpenExistingCampaign(existingCampaignInfo);
                            }}
                            style={{
                              alignSelf: 'flex-start',
                              marginTop: '4px',
                              padding: '6px 14px',
                              borderRadius: '8px',
                              backgroundColor: '#d97706',
                              color: '#ffffff',
                              fontWeight: 700,
                              fontSize: '12.5px',
                              border: 'none',
                              cursor: 'pointer',
                              display: 'flex',
                              alignItems: 'center',
                              gap: '6px',
                            }}
                          >
                            <Wallet size={14} />
                            <span>Manage Campaign & Add Funds</span>
                          </button>
                        )}
                      </div>
                    )}
                  </div>
                ) : !hasContent ? (
                  <div
                    style={{
                      padding: '24px 20px',
                      textAlign: 'center',
                      color: '#92400e',
                      border: '1px dashed #fde68a',
                      borderRadius: '14px',
                      backgroundColor: '#fffbeb',
                    }}
                  >
                    <AlertCircle size={32} style={{ margin: '0 auto 10px', color: '#d97706' }} />
                    <p style={{ margin: '0 0 6px 0', fontSize: '14.5px', fontWeight: 700, color: '#92400e' }}>
                      Content Required
                    </p>
                    <p style={{ margin: 0, fontSize: '13px', color: '#b45309', lineHeight: 1.4 }}>
                      Please add at least one post or photo to your Business Page before running an ad campaign.
                    </p>
                  </div>
                ) : (
                  <div style={{ padding: '20px', textAlign: 'center', color: '#94a3b8', border: '1px dashed #cbd5e1', borderRadius: '12px' }}>
                    <FileText size={28} style={{ margin: '0 auto 8px' }} />
                    <p style={{ margin: 0, fontSize: '13px' }}>No timeline post selected.</p>
                  </div>
                )}
                {errors.post && <span style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px', display: 'block' }}>{errors.post}</span>}
              </div>

              {/* Campaign Name */}
              <div>
                <label style={{ display: 'block', fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', color: 'var(--color-text-main, #334155)' }}>
                  Campaign Name *
                </label>
                <input
                  type="text"
                  value={campaignName}
                  onChange={(e) => setCampaignName(e.target.value)}
                  placeholder="e.g. Summer Discount Promo"
                  className="biz-search-input"
                  style={{ width: '100%', padding: '10px 14px', borderRadius: '10px' }}
                />
                {errors.campaignName && (
                  <span style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px', display: 'block' }}>{errors.campaignName}</span>
                )}
              </div>

              {/* Budget Settings */}
              <div>
                {/* Available Funds Status Banner */}
                <div
                  style={{
                    padding: '12px 14px',
                    borderRadius: '10px',
                    backgroundColor: '#f0fdf4',
                    border: '1px solid #bbf7d0',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    marginBottom: '12px',
                    flexWrap: 'wrap',
                    gap: '8px',
                  }}
                >
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <Wallet size={16} color="#16a34a" />
                    <div>
                      <span style={{ fontSize: '12px', color: '#166534', fontWeight: 600 }}>Available Ad Funds: </span>
                      <strong style={{ fontSize: '13.5px', color: '#15803d' }}>
                        ${Number(availableAdFunds).toLocaleString('en-US', { minimumFractionDigits: 2 })} USD
                      </strong>
                    </div>
                  </div>
                  {onAddFundClick && (
                    <button
                      type="button"
                      onClick={onAddFundClick}
                      style={{
                        background: '#16a34a',
                        color: '#ffffff',
                        border: 'none',
                        padding: '4px 10px',
                        borderRadius: '6px',
                        fontSize: '12px',
                        fontWeight: 700,
                        cursor: 'pointer',
                        display: 'inline-flex',
                        alignItems: 'center',
                        gap: '4px',
                      }}
                    >
                      <span>+ Add Fund</span>
                    </button>
                  )}
                </div>

                <label style={{ display: 'block', fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', color: 'var(--color-text-main, #334155)' }}>
                  Campaign Budget (USD) *
                </label>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '10px' }}>
                  <div style={{ position: 'relative', flex: 1 }}>
                    <span style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#64748b', fontWeight: 700 }}>
                      $
                    </span>
                    <input
                      type="number"
                      step="1"
                      min="1"
                      value={budget}
                      onChange={(e) => setBudget(e.target.value)}
                      placeholder="50"
                      className="biz-search-input"
                      style={{ width: '100%', paddingLeft: '28px', borderRadius: '10px' }}
                    />
                  </div>
                </div>

                {/* Quick Presets */}
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                  {BUDGET_PRESETS.map((p) => (
                    <button
                      key={p}
                      type="button"
                      onClick={() => setBudget(String(p))}
                      style={{
                        padding: '6px 12px',
                        borderRadius: '8px',
                        border: String(p) === String(budget) ? '1px solid #4f7df3' : '1px solid var(--color-border, #e2e8f0)',
                        backgroundColor: String(p) === String(budget) ? 'rgba(79, 125, 243, 0.1)' : '#ffffff',
                        color: String(p) === String(budget) ? '#4f7df3' : '#475569',
                        fontWeight: String(p) === String(budget) ? 700 : 500,
                        fontSize: '12.5px',
                        cursor: 'pointer',
                      }}
                    >
                      ${p}
                    </button>
                  ))}
                </div>

                {/* Dynamic Balance Preview with Platform Fee */}
                <div style={{ padding: '12px 14px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: '10px', marginTop: '10px', fontSize: '12.5px' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px', color: '#475569' }}>
                    <span>Campaign Running Budget:</span>
                    <span style={{ fontWeight: 700, color: '#1e293b' }}>${numBudget.toFixed(2)} USD</span>
                  </div>
                  {feeAmount > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '6px', color: '#475569' }}>
                      <span>Platform Fee ({numFeePercent}%):</span>
                      <span style={{ fontWeight: 700, color: '#2563eb' }}>+${feeAmount.toFixed(2)} USD</span>
                    </div>
                  )}
                  <div style={{ display: 'flex', justifyContent: 'space-between', paddingTop: '6px', borderTop: '1px dashed #cbd5e1', fontWeight: 700, color: '#1e293b' }}>
                    <span>Total Wallet Debit:</span>
                    <span style={{ color: '#4f7df3', fontSize: '13.5px' }}>${totalWalletDebit.toFixed(2)} USD</span>
                  </div>

                  <div style={{ marginTop: '8px', fontSize: '12px', fontWeight: 600 }}>
                    {totalWalletDebit > Number(availableAdFunds) ? (
                      <span style={{ color: '#dc2626' }}>
                        ⚠️ Insufficient advertising funds. Shortfall: ${(totalWalletDebit - Number(availableAdFunds)).toFixed(2)} USD
                      </span>
                    ) : (
                      <span style={{ color: '#166534' }}>
                        ✓ Estimated remaining funds after debit: ${(Number(availableAdFunds) - totalWalletDebit).toFixed(2)} USD
                      </span>
                    )}
                  </div>
                </div>

                {errors.budget && (
                  <span style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px', display: 'block' }}>{errors.budget}</span>
                )}
              </div>

              {/* Start Date Settings */}
              <div>
                <label style={{ display: 'block', fontSize: '13.5px', fontWeight: 700, marginBottom: '6px', color: 'var(--color-text-main, #334155)' }}>
                  Start Date & Time
                </label>
                <input
                  type="datetime-local"
                  value={startAt}
                  onChange={(e) => setStartAt(e.target.value)}
                  className="biz-search-input"
                  style={{ width: '100%', padding: '9px 12px', borderRadius: '10px' }}
                />
              </div>

              {/* Platform Audience Reach Disclaimer */}
              <div
                style={{
                  padding: '14px 16px',
                  borderRadius: '12px',
                  backgroundColor: '#eff6ff',
                  border: '1px solid #bfdbfe',
                  display: 'flex',
                  alignItems: 'flex-start',
                  gap: '12px',
                }}
              >
                <Sparkles size={20} color="#3b82f6" style={{ flexShrink: 0, marginTop: '2px' }} />
                <div style={{ fontSize: '13px', color: '#1e40af', lineHeight: 1.5 }}>
                  <strong>Platform-Wide Advertising:</strong> Approved campaigns are scheduled for broad platform delivery
                  to reach members across MLM_Book feeds upon administrator review. Delivery continues until available budget is exhausted.
                </div>
              </div>
            </>
          ) : (
            /* Step 2: Review Screen */
            <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
              <div
                style={{
                  padding: '16px',
                  borderRadius: '14px',
                  backgroundColor: 'var(--color-subtle-bg, #f8fafc)',
                  border: '1px solid var(--color-border, #e2e8f0)',
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
                  gap: '16px',
                }}
              >
                <div>
                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Campaign Name</div>
                  <div style={{ fontSize: '14px', fontWeight: 700, color: '#1e293b', marginTop: '2px' }}>{campaignName}</div>
                </div>

                <div>
                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Campaign Running Budget</div>
                  <div style={{ fontSize: '16px', fontWeight: 800, color: '#15803d', marginTop: '2px' }}>
                    ${numBudget.toLocaleString('en-US', { minimumFractionDigits: 2 })} USD
                  </div>
                </div>

                {feeAmount > 0 && (
                  <div>
                    <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Platform Fee ({numFeePercent}%)</div>
                    <div style={{ fontSize: '14px', fontWeight: 700, color: '#2563eb', marginTop: '2px' }}>
                      +${feeAmount.toFixed(2)} USD
                    </div>
                  </div>
                )}

                <div>
                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Total Wallet Debit</div>
                  <div style={{ fontSize: '16px', fontWeight: 800, color: '#4f7df3', marginTop: '2px' }}>
                    ${totalWalletDebit.toFixed(2)} USD
                  </div>
                </div>

                <div>
                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Available Funds → Remaining</div>
                  <div style={{ fontSize: '13px', fontWeight: 600, color: '#334155', marginTop: '2px' }}>
                    ${Number(availableAdFunds).toFixed(2)} → ${(Math.max(0, Number(availableAdFunds) - totalWalletDebit)).toFixed(2)} USD
                  </div>
                </div>

                <div>
                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 600 }}>Schedule</div>
                  <div style={{ fontSize: '13px', color: '#334155', marginTop: '2px' }}>
                    Start: {startAt ? new Date(startAt).toLocaleDateString() : 'Immediate'}
                  </div>
                </div>
              </div>

              {/* Promoted Post Snippet */}
              {selectedPost && (
                <div
                  style={{
                    border: '1px solid var(--color-border, #e2e8f0)',
                    borderRadius: '14px',
                    padding: '16px',
                    backgroundColor: '#ffffff',
                  }}
                >
                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 700, marginBottom: '8px', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                    Promoted Post Summary
                  </div>
                  {selectedPost.body && (
                    <p style={{ fontSize: '13.5px', color: '#334155', margin: '0 0 10px 0', lineHeight: 1.5 }}>
                      {selectedPost.body}
                    </p>
                  )}
                  {postMedia && (
                    <div
                      style={{
                        borderRadius: '10px',
                        overflow: 'hidden',
                        maxHeight: isVideoPost ? '180px' : '140px',
                        backgroundColor: isVideoPost ? '#000000' : undefined,
                      }}
                    >
                      {isVideoPost ? (
                        <video
                          src={resolvedMediaUrl}
                          controls
                          playsInline
                          preload="metadata"
                          style={{
                            width: '100%',
                            maxHeight: '180px',
                            objectFit: 'contain',
                            display: 'block',
                          }}
                        />
                      ) : (
                        <img
                          src={resolvedMediaUrl}
                          alt="Post Preview"
                          style={{
                            width: '100%',
                            maxHeight: '140px',
                            objectFit: 'cover',
                            display: 'block',
                          }}
                        />
                      )}
                    </div>
                  )}
                </div>
              )}

              {/* Review Process Notice */}
              <div
                style={{
                  padding: '14px 16px',
                  borderRadius: '12px',
                  backgroundColor: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '12px',
                }}
              >
                <ShieldCheck size={22} color="#0ea5e9" style={{ flexShrink: 0 }} />
                <div style={{ fontSize: '13px', color: '#334155' }}>
                  <strong>Admin Moderation Flow:</strong> When you submit, your campaign status transitions to{' '}
                  <span style={{ color: '#d97706', fontWeight: 700 }}>Pending Review</span>. An administrator will review your content
                  prior to ad activation.
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Modal Footer */}
        <div
          style={{
            padding: '16px 24px',
            borderTop: '1px solid var(--color-border, #e2e8f0)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            backgroundColor: '#f8fafc',
          }}
        >
          {step === 1 ? (
            <>
              <button
                type="button"
                className="btn btn-secondary"
                onClick={onClose}
                disabled={isSubmitting}
                style={{ padding: '8px 18px', borderRadius: '10px', fontSize: '13.5px' }}
              >
                Cancel
              </button>

              <button
                type="button"
                className="btn btn-primary"
                onClick={handleNextStep}
                disabled={!hasContent || isSubmitting}
                title={!hasContent ? 'Please add at least one post or photo to your Business Page before running an ad campaign.' : undefined}
                style={{
                  padding: '8px 20px',
                  borderRadius: '10px',
                  fontSize: '13.5px',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                  backgroundColor: !hasContent ? '#94a3b8' : '#4f7df3',
                  color: '#ffffff',
                  fontWeight: 700,
                  border: 'none',
                  cursor: !hasContent ? 'not-allowed' : 'pointer',
                  opacity: !hasContent ? 0.65 : 1,
                }}
              >
                <span>Continue to Review</span>
                <ChevronRight size={16} />
              </button>
            </>
          ) : (
            <>
              <button
                type="button"
                className="btn btn-secondary"
                onClick={() => setStep(1)}
                disabled={isSubmitting}
                style={{
                  padding: '8px 18px',
                  borderRadius: '10px',
                  fontSize: '13.5px',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '6px',
                }}
              >
                <ChevronLeft size={16} />
                <span>Back</span>
              </button>

              <div style={{ display: 'flex', gap: '10px' }}>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => handleSubmit(false)}
                  disabled={isSubmitting}
                  style={{
                    padding: '8px 16px',
                    borderRadius: '10px',
                    fontSize: '13.5px',
                    fontWeight: 600,
                    cursor: isSubmitting ? 'not-allowed' : 'pointer',
                  }}
                >
                  {isSubmitting ? 'Saving...' : 'Save as Draft'}
                </button>

                <button
                  type="button"
                  className="btn btn-primary"
                  onClick={() => handleSubmit(true)}
                  disabled={isSubmitting}
                  style={{
                    padding: '8px 22px',
                    borderRadius: '10px',
                    fontSize: '13.5px',
                    fontWeight: 700,
                    backgroundColor: '#4f7df3',
                    color: '#ffffff',
                    border: 'none',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px',
                    cursor: isSubmitting ? 'not-allowed' : 'pointer',
                    boxShadow: '0 4px 12px rgba(79, 125, 243, 0.25)',
                  }}
                >
                  <CheckCircle2 size={16} />
                  <span>{isSubmitting ? 'Submitting...' : 'Submit for Review'}</span>
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </ModalPortal>
  );
}

export default CreateAdCampaignModal;
