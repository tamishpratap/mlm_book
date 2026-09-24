import { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { DollarSign, Crop, X } from 'lucide-react';
import eventApi from '../../api/eventApi';
import useAuth from '../../hooks/useAuth';
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

export function EditEventPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user: currentUser } = useAuth();

  const [categories, setCategories] = useState([]);

  const [formData, setFormData] = useState({
    title: '',
    short_description: '',
    description: '',
    category: '',
    event_type: 'offline',
    privacy: 'public',
    start_date: '',
    start_time: '',
    location_address: '',
    meeting_link: '',
  });

  const [coverPhoto, setCoverPhoto] = useState(null);
  const [coverPreview, setCoverPreview] = useState(null);
  const [pendingCoverFile, setPendingCoverFile] = useState(null);
  const [coverAdjustmentState, setCoverAdjustmentState] = useState(null);
  const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false);
  const fileInputRef = useRef(null);

  const [isLoading, setIsLoading] = useState(true);
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
  const [campaign, setCampaign] = useState(null);
  const [isPaid, setIsPaid] = useState(false);

  const effectiveToday = minDate || getTodayDateString();
  const isToday = formData.start_date === effectiveToday;

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentMinTime(getNextMinuteTimeString());
    }, 15000);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    let isMounted = true;
    eventApi
      .getEditEventData(id)
      .then((res) => {
        if (isMounted && res) {
          if (Array.isArray(res.categories)) {
            setCategories(res.categories);
          }
          if (res.today) {
            setMinDate(res.today);
          }
          if (res.event) {
            const ev = res.event;
            setFormData({
              title: ev.title || '',
              short_description: ev.short_description || '',
              description: ev.description || '',
              category: ev.category || '',
              event_type: ev.event_type || 'offline',
              privacy: ev.privacy || 'public',
              start_date: ev.start_date ? ev.start_date.slice(0, 10) : '',
              start_time: ev.start_time ? ev.start_time.slice(0, 5) : '',
              location_address: ev.location_address || '',
              meeting_link: ev.meeting_link || '',
            });
            if (ev.cover_photo) {
              setCoverPreview(
                ev.cover_photo.startsWith('http') ? ev.cover_photo : `/${ev.cover_photo}`
              );
            }
          }
          if (res.campaign) {
            setCampaign(res.campaign);
          }
          if (res.is_paid !== undefined) {
            setIsPaid(Boolean(res.is_paid));
          } else if (res.campaign) {
            setIsPaid(true);
          }
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load event for editing.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [id]);

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

  const handleRemoveBlockedFiles = () => {
    handleRemoveCover();
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

    setIsSubmitting(true);

    const payload = new FormData();
    Object.keys(formData).forEach((key) => {
      if (formData[key] !== null && formData[key] !== undefined && formData[key] !== '') {
        payload.append(key, formData[key]);
      }
    });

    if (coverPhoto) {
      payload.append('cover_photo', coverPhoto);
    }

    try {
      await eventApi.updateEvent(id, payload);
      navigate(`/member/events/${id}`);
    } catch (err) {
      setError(
        err.response?.data?.errors?.start_time?.[0] ||
        err.response?.data?.errors?.start_date?.[0] ||
        err.response?.data?.message ||
        'Failed to update event.'
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading event details...
      </div>
    );
  }

  return (
    <div className="product-create-page card" style={{ maxWidth: '800px', margin: '20px auto', padding: '24px' }}>
      <header className="member-card__header" style={{ marginBottom: '20px' }}>
        <div>
          <h1 style={{ fontSize: '22px', fontWeight: 800, margin: '0 0 6px 0' }}>Edit Event</h1>
          <p style={{ color: 'var(--color-text-secondary)', margin: 0 }}>Update event details for {formData.title}</p>
        </div>
      </header>

      {/* Paid Event Campaign Information Banner (Read-only, preserves financial isolation) */}
      {isPaid && (
        <div
          style={{
            background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
            border: '1px solid #86efac',
            borderRadius: '12px',
            padding: '16px 20px',
            marginBottom: '20px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '12px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '8px',
                background: '#16a34a',
                color: '#ffffff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
              }}
            >
              <DollarSign size={20} />
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <strong style={{ fontSize: '14px', color: '#166534' }}>
                  Paid Event Campaign Active
                </strong>
                <span
                  style={{
                    background: '#bbf7d0',
                    color: '#14532d',
                    fontSize: '11px',
                    fontWeight: 700,
                    padding: '2px 8px',
                    borderRadius: '20px',
                  }}
                >
                  {campaign?.campaign_id || 'Canonical Campaign'}
                </span>
              </div>
              <p style={{ margin: '2px 0 0 0', fontSize: '12px', color: '#15803d' }}>
                Budget: <strong>${parseFloat(campaign?.budget || 0).toFixed(2)} USD</strong> • Remaining:{' '}
                <strong>${parseFloat(campaign?.remaining_amount || 0).toFixed(2)} USD</strong>. Campaign funding is safely managed via Campaign Add Funds and cannot be edited here.
              </p>
            </div>
          </div>
          <Link
            to={`/member/events/${id}`}
            className="member-button"
            style={{
              background: '#16a34a',
              color: '#ffffff',
              border: 'none',
              padding: '6px 14px',
              fontSize: '12px',
              fontWeight: 700,
              borderRadius: '8px',
              textDecoration: 'none',
            }}
          >
            Manage Campaign
          </Link>
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
              <option value="offline">In-Person</option>
              <option value="online">Online</option>
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
              <option value="public">Public</option>
              <option value="friends_only">Friends Only</option>
              <option value="private">Private</option>
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
            <label htmlFor="location_address" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
              Address / Location
            </label>
            <input
              type="text"
              id="location_address"
              name="location_address"
              value={formData.location_address}
              onChange={handleChange}
              style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1' }}
            />
          </div>
        </div>

        <div className="form-group" style={{ marginBottom: '16px' }}>
          <label htmlFor="meeting_link" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
            Online Meeting Link
          </label>
          <input
            type="url"
            id="meeting_link"
            name="meeting_link"
            value={formData.meeting_link}
            onChange={handleChange}
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
            style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #cbd5e1', resize: 'vertical' }}
          />
        </div>

        <div className="form-group" style={{ marginBottom: '24px' }}>
          <label htmlFor="cover_photo" style={{ fontWeight: 600, display: 'block', marginBottom: '6px' }}>
            Change Cover Photo
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
                {pendingCoverFile && (
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
                )}

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

        <div className="form-actions" style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
          <Link to={`/member/events/${id}`} className="member-button member-button--secondary">
            Cancel
          </Link>
          <button
            type="submit"
            className="member-button member-button--primary"
            disabled={isSubmitting}
          >
            {isSubmitting ? 'Saving...' : 'Save Changes'}
          </button>
        </div>
      </form>

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
            location_address: formData.location_address,
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

export default EditEventPage;
