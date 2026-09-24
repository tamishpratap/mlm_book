import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import postApi from '../../../api/postApi';
import { ModalPortal } from '../../common/ModalPortal';

const REPORT_REASONS = [
  'Spam',
  'Fake News',
  'Harassment',
  'Violence',
  'Adult Content',
  'Hate Speech',
  'Other',
];

export function ReportModal({ isOpen = true, onClose, postId, post = null }) {
  const [reason, setReason] = useState(REPORT_REASONS[0]);
  const [description, setDescription] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  const isNumericId = (val) => val !== null && val !== undefined && val !== '' && !isNaN(val) && /^\d+$/.test(String(val).trim());

  const resolveTargetPostId = () => {
    if (isNumericId(postId)) return Number(postId);
    if (post) {
      if (isNumericId(post.id)) return Number(post.id);
      if (isNumericId(post.post_id)) return Number(post.post_id);
      if (isNumericId(post.source_post_id)) return Number(post.source_post_id);
      if (isNumericId(post.ad_campaign?.post_id)) return Number(post.ad_campaign.post_id);
      if (isNumericId(post.campaign?.post_id)) return Number(post.campaign.post_id);
      if (isNumericId(post.event?.post_id)) return Number(post.event.post_id);
      if (isNumericId(post.original_post?.id)) return Number(post.original_post.id);
    }
    return postId;
  };

  const resolvedId = resolveTargetPostId();
  const isUnbackedSynthetic = !isNumericId(resolvedId) && typeof resolvedId === 'string' && /^(event|event_campaign|campaign|business_campaign)_/i.test(resolvedId);

  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isUnbackedSynthetic) {
      setError('This feed item is not associated with an active post and cannot be reported as a post.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      await postApi.reportPost(resolvedId, reason, description);
      setSuccess(true);
      setTimeout(() => {
        setSuccess(false);
        onClose();
        setDescription('');
      }, 1500);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to submit report. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel post-report-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="post-report-modal-title"
        style={{
          maxWidth: '460px',
          width: '100%',
          backgroundColor: '#ffffff',
          borderRadius: '16px',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
        }}
      >
        <header className="story-modal__header">
          <div>
            <span>Report Content</span>
            <h2 id="post-report-modal-title">Report Post</h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close Modal"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        {success ? (
          <div style={{ padding: '30px 20px', textAlign: 'center' }}>
            <p style={{ color: 'var(--color-success)', fontWeight: 600, fontSize: '1rem', margin: 0 }}>
              Thank you. We have received your report and will review it.
            </p>
          </div>
        ) : (
          <form className="post-report-modal__form" onSubmit={handleSubmit}>
            <div className="post-report-modal__body">
              {isUnbackedSynthetic ? (
                <div style={{
                  padding: '12px 14px',
                  backgroundColor: '#fef2f2',
                  border: '1px solid #fecaca',
                  borderRadius: '8px',
                  color: '#991b1b',
                  fontSize: '0.875rem',
                  marginBottom: '14px',
                  lineHeight: 1.4,
                }}>
                  This event or promotional item is not associated with an active post and cannot be reported through post moderation.
                </div>
              ) : (
                <p className="post-report-modal__subtitle">
                  Please select a reason why you are reporting this post:
                </p>
              )}

              {error && (
                <div style={{ color: 'var(--color-danger)', fontSize: '0.85rem', marginBottom: '10px' }}>
                  {error}
                </div>
              )}

              <div className="post-report-modal__reasons">
                {REPORT_REASONS.map((r) => (
                  <label className="post-report-modal__reason-label" key={r}>
                    <input
                      type="radio"
                      name="reason"
                      value={r}
                      checked={reason === r}
                      onChange={(e) => setReason(e.target.value)}
                    />
                    <span>{r}</span>
                  </label>
                ))}
              </div>

              <div className="post-report-modal__input-wrap">
                <label htmlFor="report-description" className="post-report-modal__textarea-label">
                  Additional Details (Optional)
                </label>
                <textarea
                  id="report-description"
                  className="post-report-modal__textarea"
                  name="description"
                  placeholder="Provide more context..."
                  aria-label="Additional details"
                  maxLength={500}
                  rows={3}
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                />
              </div>
            </div>

            <footer className="post-report-modal__footer">
              <button
                className="post-report-modal__btn-cancel"
                type="button"
                onClick={onClose}
                disabled={isSubmitting}
              >
                Cancel
              </button>
              <button
                className="post-report-modal__btn-submit"
                type="submit"
                disabled={isSubmitting || isUnbackedSynthetic}
              >
                {isSubmitting ? 'Sending...' : 'Send Report'}
              </button>
            </footer>
          </form>
        )}
      </div>
    </ModalPortal>
  );
}

export default ReportModal;
