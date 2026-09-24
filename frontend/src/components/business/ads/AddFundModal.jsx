import { X } from 'lucide-react';
import { AddFundView } from './AddFundView';
import { ModalPortal } from '../../common/ModalPortal';

export function AddFundModal({
  page = null,
  isOpen = true,
  onClose,
  onDepositSubmitted,
  onSuccess,
}) {
  if (isOpen === false) return null;

  const handleSubmitted = (deposit) => {
    if (onDepositSubmitted) onDepositSubmitted(deposit);
    if (onSuccess) onSuccess(deposit);
  };

  return (
    <ModalPortal isOpen={Boolean(isOpen)} onClose={onClose}>
      <div
        className="card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="add-fund-modal-title"
        style={{
          backgroundColor: '#ffffff',
          borderRadius: '20px',
          maxWidth: '850px',
          width: '100%',
          maxHeight: 'min(90vh, 760px)',
          overflowY: 'auto',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          padding: '24px',
          position: 'relative',
        }}
      >
        <button
          type="button"
          onClick={onClose}
          style={{
            position: 'absolute',
            top: '20px',
            right: '20px',
            background: '#f1f5f9',
            border: 'none',
            borderRadius: '50%',
            width: '32px',
            height: '32px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            color: '#64748b',
          }}
          aria-label="Close"
        >
          <X size={18} />
        </button>

        <AddFundView
          page={page}
          showHistory={false}
          onDepositSubmitted={handleSubmitted}
        />
      </div>
    </ModalPortal>
  );
}

export default AddFundModal;
