import { useState, useEffect, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ShieldCheck, AlertCircle, Phone, ArrowLeft, Wallet, DollarSign, Sparkles, Crop, X } from 'lucide-react';
import eventApi from '../../api/eventApi';
import useAuth from '../../hooks/useAuth';
import AccountVerificationModal from '../../components/verification/AccountVerificationModal';
import { AddFundModal } from '../../components/business/ads/AddFundModal';
import { ImageAdjustmentModal } from '../../components/posts/modals/ImageAdjustmentModal';
import { useImageModeration } from '../../hooks/useImageModeration';
import { MODERATION_CONTEXTS } from '../../config/imageModerationPolicy';
import { ImageModerationScanModal } from '../../components/moderation/ImageModerationScanModal';

function getTodayDateString() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function getCurrentTimeString() {
  const d = new Date();
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
}

function getNextMinuteTimeString() {
  const d = new Date();
  d.setMinutes(d.getMinutes() + 1);
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
}

function getDefaultStartDate() {
  const d = new Date();
  d.setDate(d.getDate() + 7);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const BUDGET_PRESETS = [5, 10, 25, 50, 100];

export function CreateEventPage() {
  const navigate = useNavigate();
  const { user: currentUser, refreshUser } = useAuth();

  const [categories, setCategories] = useState([]);

  const [formData, setFormData] = useState(() => ({
    title: '',
    category: '',
    event_type: 'offline',
    privacy: 'public',
    start_date: getDefaultStartDate(),
    start_time: '18:00',
    location_city: '',
    location_address: '',
    meeting_link: '',
    short_description: '',
    description: '',
  }));

  const [coverPhoto, setCoverPhoto] = useState(null);
  const [coverPreview, setCoverPreview] = useState(null);
  const [pendingCoverFile, setPendingCoverFile] = useState(null);
  const [coverAdjustmentState, setCoverAdjustmentState] = useState(null);
  const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false);
  const fileInputRef = useRef(null);

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);

  const {
    scanImages,
    progress: modProgress,
    decision: modDecision,
    error: modError,
    cancel: cancelModeration,
    reset: resetModeration,
  } = useImageModeration({ context: MODERATION_CONTEXTS.POST_IMAGE });

  const [isScanModalOpen, setIsScanModalOpen] = useState(false);
  const [scanStatus, setScanStatus] = useState('IDLE');
  const [blockedItems, setBlockedItems] = useState([]);
  const [minDate, setMinDate] = useState(() => getTodayDateString());
  const [currentMinTime, setCurrentMinTime] = useState(() => getNextMinuteTimeString());
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [showDepositModal, setShowDepositModal] = useState(false);

  // Mandatory Paid Campaign Budget State (All events are paid campaigns)
  const [campaignBudget, setCampaignBudget] = useState('10');
  const [adBalance, setAdBalance] = useState(parseFloat(currentUser?.p2p_wallet ?? currentUser?.fund_wallet ?? currentUser?.ad_balance ?? 0));

  const isVerified = Boolean(currentUser?.is_verified || currentUser?.mobile_verified_at);

  const effectiveToday = minDate || getTodayDateString();
  const isToday = formData.start_date === effectiveToday;

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentMinTime(getNextMinuteTimeString());
    }, 15000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    if (currentUser?.p2p_wallet !== undefined || currentUser?.ad_balance !== undefined) {
      setAdBalance(parseFloat(currentUser.p2p_wallet ?? currentUser.fund_wallet ?? currentUser.ad_balance ?? 0));
    }
  }, [currentUser]);

  useEffect(() => {
    eventApi
      .getCreateEventData()
      .then((data) => {
        if (data && Array.isArray(data.categories)) {
          setCategories(data.categories);
          setFormData((prev) => ({
            ...prev,
            category: prev.category || (data.categories.length > 0 ? data.categories[0] : ''),
          }));
        }
        if (data && data.today) {
          setMinDate(data.today);
        }
      })
      .catch(() => {});
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      if (!file.type.startsWith('image/')) {
        setError('Please select a valid image file (JPG, PNG, WEBP).');
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        setError('Event cover photo must not exceed 5 MB.');
        return;
      }
      setError(null);
      setPendingCoverFile(file);
      setIsAdjustModalOpen(true);
    }
  };

  const handleApplyAdjustment = ({ file: adjustedFile, previewUrl, adjustmentState: state }) => {
    if (coverPreview && coverPreview.startsWith('blob:')) {
      URL.revokeObjectURL(coverPreview);
    }
    setCoverPhoto(adjustedFile);
    setCoverPreview(previewUrl);
    setCoverAdjustmentState(state);
    setIsAdjustModalOpen(false);
  };

  const handleCancelAdjustment = () => {
    setIsAdjustModalOpen(false);
    // If no coverPhoto was previously set, clear the file input and pending file
    if (!coverPhoto) {
      setPendingCoverFile(null);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleRemoveCover = () => {
    if (coverPreview && coverPreview.startsWith('blob:')) {
      URL.revokeObjectURL(coverPreview);
    }
    setCoverPhoto(null);
    setCoverPreview(null);
    setPendingCoverFile(null);
    setCoverAdjustmentState(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const numBudget = parseFloat(campaignBudget) || 0;
  const platformFeePercent = 2.5;
  const feeAmount = parseFloat((numBudget * (platformFeePercent / 100)).toFixed(2));
  const totalWalletDebit = parseFloat((numBudget + feeAmount).toFixed(2));
  const hasInsufficientFunds = totalWalletDebit > adBalance;
  const shortfall = hasInsufficientFunds ? (totalWalletDebit - adBalance).toFixed(2) : '0.00';

  const handleRemoveBlockedFiles = () => {
    setCoverPhoto(null);
    setCoverPreview(null);
    setPendingCoverFile(null);
    setCoverAdjustmentState(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    setBlockedItems([]);
    setIsScanModalOpen(false);
    setScanStatus('IDLE');
    resetModeration();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isSubmitting) return;
    setError(null);

    // Event Start Date must be Today or Future Date
    const effectiveToday = minDate || getTodayDateString();
    if (!formData.start_date || formData.start_date < effectiveToday) {
      setError('The event start date must be today or a future date.');
      return;
    }

    // If Start Date is Today, Start Time must be strictly later than current time
    if (formData.start_date === effectiveToday) {
      const currentNow = new Date();
      const currentHHMM = `${String(currentNow.getHours()).padStart(2, '0')}:${String(currentNow.getMinutes()).padStart(2, '0')}`;
      if (!formData.start_time || formData.start_time <= currentHHMM) {
        setError('Start time must be later than the current time when the event starts today.');
        return;
      }
    }

    // Mandatory Paid Event Budget Validation
    if (isNaN(numBudget) || numBudget < 1.0) {
      setError('Please enter a valid campaign budget (minimum $1.00 USD).');
      return;
    }

    if (hasInsufficientFunds) {
      setError(
        `Insufficient advertising funds. Campaign Budget: $${numBudget.toFixed(2)} USD, Platform Fee (${platformFeePercent}%): $${feeAmount.toFixed(2)} USD, Total Required: $${totalWalletDebit.toFixed(2)} USD, Available: $${adBalance.toFixed(2)} USD. Shortfall: $${shortfall} USD. Please deposit funds first.`
      );
      return;
    }

    setIsSubmitting(true);

    // Pre-flight Client-Side Image Moderation
    if (coverPhoto) {
      setIsScanModalOpen(true);
      setScanStatus('SCANNING');
      setBlockedItems([]);

      try {
        const batchSummary = await scanImages([coverPhoto]);

        if (!batchSummary.allAllowed) {
          setScanStatus('BLOCKED');
          setBlockedItems(batchSummary.blockedItems);
          const blockedNames = batchSummary.blockedItems.map((b) => b.fileName).join(', ');
          setError(`Restricted content detected in event cover: ${blockedNames}. Please replace the flagged photo.`);
          setIsSubmitting(false);
          return;
        }

        if (batchSummary.error) {
          setScanStatus('ERROR');
          setError('Failed to verify cover image against safety standards. Please try again.');
          setIsSubmitting(false);
          return;
        }

        setIsScanModalOpen(false);
        setScanStatus('IDLE');
      } catch {
        setScanStatus('ERROR');
        setError('Verification encountered an unexpected error. Please try again.');
        setIsSubmitting(false);
        return;
      }
    }

    const payload = new FormData();
    Object.keys(formData).forEach((key) => {
      if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
        payload.append(key, formData[key]);
      }
    });

    if (coverPhoto) {
      payload.append('cover_photo', coverPhoto);
    }

    // Every event is a Paid Campaign Event
    payload.append('is_paid', '1');
    payload.append('create_campaign', '1');
    payload.append('budget', numBudget.toFixed(2));
    payload.append('currency', 'USD');
    const idempotencyKey = (typeof crypto !== 'undefined' && crypto.randomUUID)
      ? crypto.randomUUID()
      : `ev-create-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
    payload.append('idempotency_key', idempotencyKey);

    try {
      const res = await eventApi.createEvent(payload);
      if (res && res.event) {
        navigate(`/member/events/${res.event.id}`);
      } else {
        navigate('/member/events');
      }
    } catch (err) {
      setError(
        err.response?.data?.errors?.start_time?.[0] ||
        err.response?.data?.errors?.start_date?.[0] ||
        err.response?.data?.message ||
        'Failed to create event. Please check required fields.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="product-create-page card" style={{ maxWidth: '800px', margin: '20px auto', padding: '24px' }}>
      <header className="member-card__header" style={{ marginBottom: '20px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h1 style={{ fontSize: '22px', fontWeight: 800, margin: '0 0 6px 0' }}>Create New Event</h1>
          <p style={{ color: 'var(--color-text-secondary)', margin: 0 }}>Organize an online or in-person community event.</p>
        </div>
        <Link to="/member/events" className="member-button member-button--secondary" style={{ padding: '7px 12px', fontSize: '13px' }}>
          <ArrowLeft size={15} />
          <span>Back</span>
        </Link>
      </header>

      {/* Mobile Verification Required Banner if unverified */}
      {!isVerified && (
        <div
          style={{
            background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
            border: '1px solid #fcd34d',
            borderRadius: '14px',
            padding: '18px 20px',
            marginBottom: '24px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '14px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#d97706', color: '#ffffff', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
              <ShieldCheck size={22} />
            </div>
            <div>
              <strong style={{ fontSize: '15px', color: '#92400e', display: 'block', marginBottom: '2px' }}>
                Mobile WhatsApp Verification Required
              </strong>
              <p style={{ margin: 0, fontSize: '13px', color: '#78350f' }}>
                Only verified members can create and host events on MLM Book. Verify your phone number via WhatsApp to continue.
              </p>
            </div>
          </div>

          <button
            type="button"
            className="member-button"
            style={{
              background: '#059669',
              color: '#ffffff',
              border: 'none',
              fontWeight: 700,
              fontSize: '13px',
              padding: '9px 16px',
              borderRadius: '10px',
            }}
            onClick={() => setShowVerifyModal(true)}
          >
            <Phone size={14} />
            <span>Verify Mobile on WhatsApp</span>
          </button>
        </div>
      )}

      {error && (
        <div style={{ padding: '12px 16px', background: '#fee2e2', color: '#b91c1c', borderRadius: '8px', marginBottom: '20px' }}>
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="member-form">
        <div className="form-group" style={{ marginBottom: '16px' }}>
          <label htmlFor="title" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
            Event Title *
          </label>
          <input
            type="text"
            id="title"
            name="title"
            value={formData.title}
            onChange={handleChange}
            required
            placeholder="e.g. MLM Leaders Annual Summit 2026"
            style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
          />
        </div>

        <div className="form-row" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '16px' }}>
          <div className="form-group">
            <label htmlFor="category" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Category *
            </label>
            <select
              id="category"
              name="category"
              value={formData.category}
              onChange={handleChange}
              required
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            >
              {categories.length === 0 ? (
                <option value="" disabled>
                  Loading categories...
                </option>
              ) : (
                <>
                  <option value="" disabled>
                    Select a category
                  </option>
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </>
              )}
            </select>
          </div>

          <div className="form-group">
            <label htmlFor="event_type" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Location Type *
            </label>
            <select
              id="event_type"
              name="event_type"
              value={formData.event_type}
              onChange={handleChange}
              required
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            >
              <option value="offline">In-Person (Physical Location)</option>
              <option value="online">Online (Zoom, Google Meet, Link)</option>
            </select>
          </div>
        </div>

        <div className="form-row" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '16px' }}>
          <div className="form-group">
            <label htmlFor="privacy" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Privacy *
            </label>
            <select
              id="privacy"
              name="privacy"
              value={formData.privacy}
              onChange={handleChange}
              required
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            >
              <option value="public">Public (Anyone on MLM Book)</option>
              <option value="friends_only">Friends Only</option>
              <option value="private">Private (Invite Only)</option>
            </select>
          </div>

          <div className="form-group">
            <label htmlFor="start_date" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Start Date *
            </label>
            <input
              type="date"
              id="start_date"
              name="start_date"
              min={minDate || getTodayDateString()}
              value={formData.start_date}
              onChange={handleChange}
              autoComplete="off"
              required
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            />
          </div>
        </div>

        <div className="form-row" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '16px' }}>
          <div className="form-group">
            <label htmlFor="start_time" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Start Time
            </label>
            <input
              type="time"
              id="start_time"
              name="start_time"
              min={isToday ? currentMinTime : undefined}
              value={formData.start_time || ''}
              onChange={handleChange}
              onFocus={() => setCurrentMinTime(getNextMinuteTimeString())}
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            />
          </div>

          <div className="form-group">
            <label htmlFor="location_city" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              City / Location
            </label>
            <input
              type="text"
              id="location_city"
              name="location_city"
              value={formData.location_city}
              onChange={handleChange}
              placeholder="e.g. New York, NY"
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            />
          </div>
        </div>

        {formData.event_type === 'offline' && (
          <div className="form-group" style={{ marginBottom: '16px' }}>
            <label htmlFor="location_address" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Full Venue Address
            </label>
            <input
              type="text"
              id="location_address"
              name="location_address"
              value={formData.location_address}
              onChange={handleChange}
              placeholder="e.g. Grand Ballroom, Hilton Hotel, 5th Ave"
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            />
          </div>
        )}

        {formData.event_type === 'online' && (
          <div className="form-group" style={{ marginBottom: '16px' }}>
            <label htmlFor="meeting_link" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Online Meeting Link (Zoom, Meet, Teams)
            </label>
            <input
              type="url"
              id="meeting_link"
              name="meeting_link"
              value={formData.meeting_link}
              onChange={handleChange}
              placeholder="https://zoom.us/j/123456789"
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            />
          </div>
        )}

        <div className="form-group" style={{ marginBottom: '16px' }}>
          <label htmlFor="short_description" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
            Short Summary
          </label>
          <input
            type="text"
            id="short_description"
            name="short_description"
            value={formData.short_description}
            onChange={handleChange}
            placeholder="A one-line summary of what attendees will learn or experience"
            style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
          />
        </div>

        <div className="form-group" style={{ marginBottom: '16px' }}>
          <label htmlFor="description" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
            Full Description
          </label>
          <textarea
            id="description"
            name="description"
            rows="5"
            value={formData.description}
            onChange={handleChange}
            placeholder="Detailed event agenda, schedule, speakers, requirements..."
            style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1', resize: 'vertical' }}
          />
        </div>

        <div className="form-group" style={{ marginBottom: '24px' }}>
          <label htmlFor="cover_photo" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
            Event Cover Photo
          </label>
          <input
            ref={fileInputRef}
            type="file"
            id="cover_photo"
            name="cover_photo"
            accept=".jpg,.jpeg,.png,.webp"
            onChange={handleFileChange}
          />
          {coverPreview && (
            <div
              style={{
                position: 'relative',
                marginTop: '12px',
                borderRadius: '12px',
                overflow: 'hidden',
                background: '#0f172a',
                border: '1px solid #cbd5e1',
              }}
            >
              <img
                src={coverPreview}
                alt="Cover Preview"
                style={{ width: '100%', maxHeight: '240px', objectFit: 'contain', display: 'block' }}
              />
              <div
                style={{
                  position: 'absolute',
                  top: '10px',
                  right: '10px',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  zIndex: 5,
                }}
              >
                <button
                  type="button"
                  onClick={() => setIsAdjustModalOpen(true)}
                  title="Adjust / Frame Image"
                  style={{
                    background: 'rgba(15, 23, 42, 0.85)',
                    backdropFilter: 'blur(4px)',
                    color: '#ffffff',
                    border: '1px solid rgba(255, 255, 255, 0.25)',
                    borderRadius: '20px',
                    padding: '6px 14px',
                    fontSize: '0.78rem',
                    fontWeight: 700,
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    cursor: 'pointer',
                    boxShadow: '0 2px 8px rgba(0, 0, 0, 0.25)',
                  }}
                >
                  <Crop size={14} />
                  <span>Adjust Framing</span>
                </button>

                <button
                  type="button"
                  aria-label="Remove cover photo"
                  onClick={handleRemoveCover}
                  title="Remove cover photo"
                  style={{
                    width: '32px',
                    height: '32px',
                    borderRadius: '50%',
                    background: 'rgba(15, 23, 42, 0.85)',
                    color: '#ffffff',
                    border: '1px solid rgba(255, 255, 255, 0.25)',
                    display: 'grid',
                    placeItems: 'center',
                    cursor: 'pointer',
                  }}
                >
                  <X size={16} aria-hidden="true" />
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Mandatory Paid Event Campaign Budget Configuration */}
        <div
          style={{
            background: 'linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, rgba(99, 102, 241, 0.08) 100%)',
            border: '1.5px solid #6366f1',
            borderRadius: '12px',
            padding: '18px 20px',
            marginBottom: '24px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '16px' }}>
            <div
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '10px',
                background: '#4f46e5',
                color: '#ffffff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
            >
              <Wallet size={18} />
            </div>
            <div>
              <strong style={{ fontSize: '15px', color: 'var(--color-text-primary, #1e293b)', display: 'block' }}>
                Event Campaign Budget *
              </strong>
              <p style={{ margin: 0, fontSize: '12px', color: 'var(--color-text-secondary, #64748b)' }}>
                All events on MLM Book are paid campaigns. Allocate an advertising budget to promote your event and reward interested members (minimum $1.00 USD).
              </p>
            </div>
          </div>

          <div style={{ paddingTop: '12px', borderTop: '1px solid rgba(99, 102, 241, 0.2)' }}>
            <div style={{ marginBottom: '14px' }}>
              <label style={{ fontWeight: 600, fontSize: '13px', display: 'block', marginBottom: '6px' }}>
                Campaign Budget ($ USD) *
              </label>
              <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
                <span style={{ position: 'absolute', left: '12px', color: '#64748b', fontWeight: 600 }}>$</span>
                <input
                  type="number"
                  min="1.00"
                  step="1.00"
                  required
                  value={campaignBudget}
                  onChange={(e) => setCampaignBudget(e.target.value)}
                  placeholder="10.00"
                  style={{
                    width: '100%',
                    padding: '10px 14px 10px 28px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '15px',
                    fontWeight: 700,
                  }}
                />
              </div>
            </div>

            {/* Presets */}
            <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '16px' }}>
              {BUDGET_PRESETS.map((preset) => (
                <button
                  key={preset}
                  type="button"
                  onClick={() => setCampaignBudget(String(preset))}
                  style={{
                    padding: '6px 14px',
                    borderRadius: '8px',
                    border: numBudget === preset ? '2px solid #4f46e5' : '1px solid #cbd5e1',
                    background: numBudget === preset ? '#e0e7ff' : '#ffffff',
                    color: numBudget === preset ? '#4338ca' : '#334155',
                    fontWeight: 600,
                    fontSize: '12px',
                    cursor: 'pointer',
                  }}
                >
                  ${preset}
                </button>
              ))}
            </div>

            {/* Financial Calculation Breakdown */}
            <div
              style={{
                background: '#ffffff',
                borderRadius: '8px',
                border: '1px solid #e2e8f0',
                padding: '12px 14px',
                fontSize: '13px',
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))',
                gap: '12px',
                marginBottom: '14px',
              }}
            >
              <div>
                <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Campaign Budget</span>
                <strong>${numBudget.toFixed(2)} USD</strong>
              </div>
              <div>
                <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Platform Fee ({platformFeePercent}%)</span>
                <strong>${feeAmount.toFixed(2)} USD</strong>
              </div>
              <div>
                <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Total Required</span>
                <strong style={{ color: '#4f46e5' }}>${totalWalletDebit.toFixed(2)} USD</strong>
              </div>
              <div>
                <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>Available Ad Balance</span>
                <strong style={{ color: adBalance < totalWalletDebit ? '#ef4444' : '#10b981' }}>
                  ${adBalance.toFixed(2)} USD
                </strong>
              </div>
            </div>

            {/* Insufficient Funds Warning & Deposit Action */}
            {hasInsufficientFunds && (
              <div
                style={{
                  background: '#fef2f2',
                  border: '1px solid #fecaca',
                  borderRadius: '8px',
                  padding: '10px 14px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: '10px',
                  color: '#b91c1c',
                  fontSize: '12px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <AlertCircle size={16} />
                  <span>
                    Insufficient ad balance (shortfall: <strong>${shortfall} USD</strong>).
                  </span>
                </div>
                <button
                  type="button"
                  onClick={() => setShowDepositModal(true)}
                  style={{
                    background: '#dc2626',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '6px',
                    padding: '5px 12px',
                    fontWeight: 600,
                    fontSize: '12px',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                  }}
                >
                  <Wallet size={13} />
                  <span>Deposit USDT (BEP-20)</span>
                </button>
              </div>
            )}
          </div>
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '24px' }}>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={() => navigate('/member/events')}
          >
            Cancel
          </button>
          <button
            type="submit"
            className="member-button member-button--primary"
            disabled={isSubmitting || !isVerified}
          >
            {isSubmitting ? 'Publishing...' : 'Publish Event'}
          </button>
        </div>
      </form>

      {/* Account Verification Modal */}
      <AccountVerificationModal
        isOpen={showVerifyModal}
        onClose={() => setShowVerifyModal(false)}
        onVerified={() => {
          setShowVerifyModal(false);
          window.location.reload();
        }}
      />

      {/* Universal USDT (BEP-20) Deposit Modal */}
      {showDepositModal && (
        <AddFundModal
          page={null}
          isOpen={showDepositModal}
          onClose={() => setShowDepositModal(false)}
          onDepositSubmitted={() => {
            setShowDepositModal(false);
            if (refreshUser) refreshUser();
          }}
          onSuccess={() => {
            setShowDepositModal(false);
            if (refreshUser) refreshUser();
          }}
        />
      )}

      {/* Event Cover Photo Adjust & Frame Modal */}
      {isAdjustModalOpen && pendingCoverFile && (
        <ImageAdjustmentModal
          isOpen={isAdjustModalOpen}
          file={pendingCoverFile}
          initialAdjustment={coverAdjustmentState}
          onApply={handleApplyAdjustment}
          onCancel={handleCancelAdjustment}
          currentUser={currentUser}
          previewMode="event"
          defaultAspectRatioId="16:9"
          eventData={{
            title: formData.title,
            category: formData.category,
            event_type: formData.event_type,
            start_date: formData.start_date,
            start_time: formData.start_time,
            location_address: formData.location_address,
            location_city: formData.location_city,
          }}
        />
      )}

      {/* Pre-Flight Client-Side Image Moderation Modal */}
      <ImageModerationScanModal
        isOpen={isScanModalOpen}
        status={scanStatus}
        progress={modProgress}
        decision={modDecision}
        error={modError}
        blockedItems={blockedItems}
        onCancel={() => {
          cancelModeration();
          setIsScanModalOpen(false);
          setScanStatus('IDLE');
          setIsSubmitting(false);
        }}
        onRetry={() => {
          handleSubmit(new Event('submit'));
        }}
        onAcknowledge={handleRemoveBlockedFiles}
      />
    </div>
  );
}

export default CreateEventPage;
