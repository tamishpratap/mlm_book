import { UserPlus, X, Users, ArrowRight } from 'lucide-react';
import { ModalPortal } from '../../../components/common/ModalPortal';

export function MissedIntroducerReminderModal({
  isOpen,
  onClose,
  onAddIntroducer,
}) {
  if (!isOpen) return null;

  return (
    <ModalPortal isOpen={isOpen} onClose={onClose}>
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="missed-introducer-title"
        style={{
          maxWidth: '460px',
          width: '100%',
          maxHeight: '90vh',
          overflowY: 'auto',
          borderRadius: '20px',
          background: '#ffffff',
          boxShadow: '0 25px 50px -12px rgba(15, 23, 42, 0.35)',
          border: '1px solid #e2e8f0',
          position: 'relative',
          padding: 0,
        }}
        onClick={(e) => e.stopPropagation()}
      >
        {/* Close (X) Button */}
        <button
          type="button"
          onClick={onClose}
          aria-label="Close reminder"
          style={{
            position: 'absolute',
            top: '16px',
            right: '16px',
            width: '34px',
            height: '34px',
            borderRadius: '50%',
            border: 'none',
            background: '#f1f5f9',
            color: '#64748b',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            zIndex: 10,
            transition: 'background 0.15s ease, color 0.15s ease',
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.background = '#e2e8f0';
            e.currentTarget.style.color = '#0f172a';
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.background = '#f1f5f9';
            e.currentTarget.style.color = '#64748b';
          }}
        >
          <X size={17} />
        </button>

        {/* Modal Top Header Strip */}
        <div
          style={{
            padding: '28px 24px 20px',
            textAlign: 'center',
            borderBottom: '1px solid #f1f5f9',
            background: 'linear-gradient(180deg, #f0f7ff 0%, #ffffff 100%)',
          }}
        >
          <div
            style={{
              width: '60px',
              height: '60px',
              borderRadius: '18px',
              background: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 14px',
              boxShadow: '0 8px 20px -4px rgba(37, 99, 235, 0.4)',
            }}
          >
            <UserPlus size={28} />
          </div>

          <h2
            id="missed-introducer-title"
            style={{
              fontSize: '19px',
              fontWeight: 800,
              color: '#0f172a',
              margin: '0 0 6px 0',
              lineHeight: 1.3,
            }}
          >
            Do You Have an Introducer?
          </h2>
          <span
            style={{
              fontSize: '11px',
              fontWeight: 700,
              color: '#2563eb',
              background: '#eff6ff',
              padding: '3px 10px',
              borderRadius: '999px',
              border: '1px solid #dbeafe',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '4px',
            }}
          >
            <Users size={12} />
            <span>Referral Network</span>
          </span>
        </div>

        {/* Modal Body */}
        <div style={{ padding: '20px 24px' }}>
          <p
            style={{
              fontSize: '13.5px',
              color: '#475569',
              lineHeight: 1.55,
              margin: '0 0 16px 0',
              textAlign: 'center',
            }}
          >
            If someone referred you to MLM Book and you didn’t enter their Introducer ID during registration, you can add it now from Account Settings.
          </p>

          <div
            style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              padding: '12px 14px',
              marginBottom: '22px',
              fontSize: '12px',
              color: '#64748b',
              lineHeight: 1.45,
            }}
          >
            <strong style={{ color: '#334155', display: 'block', marginBottom: '2px' }}>
              Why link your introducer?
            </strong>
            Linking your introducer connects your account to your referrer’s network and qualifies referral attribution once your mobile number is verified.
          </div>

          {/* Action Buttons */}
          <div
            style={{
              display: 'flex',
              flexDirection: 'row',
              gap: '10px',
              justifyContent: 'flex-end',
            }}
          >
            <button
              type="button"
              onClick={onClose}
              className="member-button member-button--secondary"
              style={{
                flex: 1,
                justifyContent: 'center',
                padding: '11px 16px',
                borderRadius: '10px',
                fontSize: '13.5px',
                fontWeight: 600,
                color: '#64748b',
              }}
            >
              Maybe Later
            </button>

            <button
              type="button"
              onClick={onAddIntroducer}
              className="member-button member-button--primary"
              style={{
                flex: 1.4,
                justifyContent: 'center',
                padding: '11px 16px',
                borderRadius: '10px',
                fontSize: '13.5px',
                fontWeight: 700,
                background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                color: '#ffffff',
                border: 'none',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                boxShadow: '0 4px 12px rgba(37, 99, 235, 0.25)',
              }}
            >
              <span>Add Introducer</span>
              <ArrowRight size={15} />
            </button>
          </div>
        </div>
      </div>
    </ModalPortal>
  );
}

export default MissedIntroducerReminderModal;
