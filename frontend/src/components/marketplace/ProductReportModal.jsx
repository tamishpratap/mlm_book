import { useState, useEffect } from 'react';
import { X, ShieldAlert } from 'lucide-react';
import marketplaceApi from '../../api/marketplaceApi';
import { ModalPortal } from '../common/ModalPortal';

export function ProductReportModal({
  isOpen,
  onClose,
  productId,
  productTitle = 'this product',
}) {
  const [reason, setReason] = useState('inappropriate');
  const [notes, setNotes] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [error, setError] = useState(null);

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
    setIsSubmitting(true);
    setError(null);
    try {
      await marketplaceApi.reportProduct(productId, { reason, notes });
      setIsSuccess(true);
      setTimeout(() => {
        setIsSuccess(false);
        onClose();
      }, 1500);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to submit report.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        className="story-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="report-modal-title"
        style={{
          maxWidth: '440px',
          width: '100%',
          padding: '24px',
          backgroundColor: '#ffffff',
          borderRadius: '16px',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
        }}
      >
        <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <ShieldAlert size={20} color="var(--color-danger, #ef4444)" />
            <h2 id="report-modal-title" style={{ fontSize: '1.15rem', fontWeight: 700, margin: 0 }}>
              Report Listing
            </h2>
          </div>
          <button
            className="story-modal__close"
            type="button"
            aria-label="Close"
            onClick={onClose}
          >
            <X size={18} aria-hidden="true" />
          </button>
        </header>

        {isSuccess ? (
          <div style={{ textAlign: 'center', padding: '24px 0', color: '#16a34a' }}>
            <strong style={{ display: 'block', fontSize: '1.1rem', marginBottom: '6px' }}>Report Submitted</strong>
            <p style={{ margin: 0, fontSize: '0.875rem', color: 'var(--color-text-secondary)' }}>
              Thank you for helping keep the MLM Book marketplace safe.
            </p>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="member-form">
            <p style={{ fontSize: '0.85rem', color: 'var(--color-text-secondary)', marginBottom: '14px' }}>
              Why are you reporting <strong>{productTitle}</strong>?
            </p>

            <div className="form-group" style={{ marginBottom: '14px' }}>
              <label htmlFor="report-reason" style={{ fontSize: '0.85rem', fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                Reason *
              </label>
              <select
                id="report-reason"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--color-border-soft)' }}
                required
              >
                <option value="inappropriate">Inappropriate Content</option>
                <option value="fraud">Scam or Fraud</option>
                <option value="counterfeit">Counterfeit / Fake Item</option>
                <option value="prohibited">Prohibited or Illegal Item</option>
                <option value="misleading">Misleading or Inaccurate</option>
                <option value="other">Other Reason</option>
              </select>
            </div>

            <div className="form-group" style={{ marginBottom: '16px' }}>
              <label htmlFor="report-notes" style={{ fontSize: '0.85rem', fontWeight: 600, display: 'block', marginBottom: '6px' }}>
                Additional Details (optional)
              </label>
              <textarea
                id="report-notes"
                rows={3}
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Provide any additional context for moderators..."
                style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--color-border-soft)' }}
              />
            </div>

            {error && (
              <div style={{ color: '#ef4444', fontSize: '0.8rem', marginBottom: '12px' }}>
                {error}
              </div>
            )}

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
              <button
                type="button"
                className="member-button member-button--secondary"
                onClick={onClose}
                disabled={isSubmitting}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="member-button member-button--danger"
                disabled={isSubmitting}
              >
                {isSubmitting ? 'Submitting...' : 'Submit Report'}
              </button>
            </div>
          </form>
        )}
      </div>
    </ModalPortal>
  );
}

export default ProductReportModal;
