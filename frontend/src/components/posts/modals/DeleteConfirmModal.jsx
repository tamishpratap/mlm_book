import { useEffect } from 'react';
import { X, Trash2 } from 'lucide-react';
import { ModalPortal } from '../../common/ModalPortal';

export function DeleteConfirmModal({
  isOpen,
  onClose,
  onConfirm,
  title = 'Delete Item',
  message = 'Are you sure you want to delete this? This action cannot be undone.',
  isDeleting = false,
  confirmLabel = 'Delete',
  loadingLabel = 'Deleting...',
  icon = null,
  errorMessage = null,
  confirmButtonStyle = null,
  depth = 1,
}) {
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose} depth={depth}>
      <div
        className="story-modal__panel card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-confirm-modal-title"
        style={{
          maxWidth: '420px',
          width: '100%',
          padding: '24px',
          borderRadius: '16px',
          backgroundColor: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
        }}
      >
        <header style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <span style={{
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: '36px',
              height: '36px',
              borderRadius: '10px',
              background: 'rgba(255, 65, 104, 0.1)',
              color: 'var(--color-danger, #ff4168)',
            }}>
              {icon || <Trash2 size={18} />}
            </span>
            <h2 style={{ fontSize: '1.15rem', fontWeight: 700, margin: 0, color: 'var(--color-text)' }}>
              {title}
            </h2>
          </div>
          <button
            type="button"
            style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--color-text-muted)' }}
            onClick={onClose}
          >
            <X size={18} />
          </button>
        </header>

        <p style={{ color: 'var(--color-text-secondary)', fontSize: '0.9rem', lineHeight: 1.5, margin: '0 0 20px 0' }}>
          {message}
        </p>

        {errorMessage && (
          <p style={{ color: 'var(--color-danger, #ff4168)', fontSize: '0.85rem', lineHeight: 1.4, margin: '0 0 16px 0' }}>
            {errorMessage}
          </p>
        )}

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={onClose}
            disabled={isDeleting}
          >
            Cancel
          </button>
          <button
            type="button"
            className="member-button"
            style={confirmButtonStyle || { background: 'var(--color-danger, #ff4168)', color: '#ffffff' }}
            onClick={onConfirm}
            disabled={isDeleting}
          >
            {isDeleting ? loadingLabel : confirmLabel}
          </button>
        </div>
      </div>
    </ModalPortal>
  );
}

export default DeleteConfirmModal;
