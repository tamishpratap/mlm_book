import { useState, useEffect } from 'react';
import {
  UserPlus,
  UserCheck,
  BadgeCheck,
  AlertCircle,
  CheckCircle2,
  Loader2,
  ShieldAlert,
} from 'lucide-react';
import accountApi from '../../api/accountApi';
import useAuth from '../../hooks/useAuth';
import { getInitials } from '../../utils/assetHelper';
import MemberAvatar from '../common/MemberAvatar';

export function IntroducerSettingsCard({
  member,
  introducer: propIntroducer,
  onIntroducerAdded,
  style,
}) {
  const { setUser } = useAuth();
  const [introducerIdInput, setIntroducerIdInput] = useState('');
  const [currentIntroducer, setCurrentIntroducer] = useState(propIntroducer || null);
  const [preview, setPreview] = useState(null);
  const [isChecking, setIsChecking] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState(null);
  const [successMsg, setSuccessMsg] = useState(null);

  const hasIntroducer = Boolean(currentIntroducer || member?.introducer_id);

  useEffect(() => {
    if (propIntroducer) {
      setCurrentIntroducer(propIntroducer);
    }
  }, [propIntroducer]);

  // Debounced auto-preview when user types an Introducer ID
  useEffect(() => {
    if (hasIntroducer) return;

    const trimmed = introducerIdInput.trim().replace(/^@/, '');
    if (!trimmed) {
      setPreview(null);
      setError(null);
      return;
    }

    // Check self referral locally
    if (
      member?.user_id &&
      trimmed.toLowerCase() === member.user_id.toLowerCase()
    ) {
      setPreview({
        valid: false,
        is_self: true,
        message: 'You cannot use your own ID as your introducer.',
      });
      setError('You cannot use your own ID as your introducer.');
      return;
    }

    const timer = setTimeout(async () => {
      setIsChecking(true);
      setError(null);
      try {
        const res = await accountApi.checkIntroducer(trimmed);
        setPreview(res);
        if (!res.valid) {
          setError(res.message || 'Invalid Introducer ID.');
        } else {
          setError(null);
        }
      } catch (err) {
        const msg = err.response?.data?.message || 'The selected Introducer ID does not exist.';
        setError(msg);
        setPreview(null);
      } finally {
        setIsChecking(false);
      }
    }, 450);

    return () => clearTimeout(timer);
  }, [introducerIdInput, hasIntroducer, member?.user_id]);

  const handleSubmit = async (e) => {
    e?.preventDefault();
    const cleanId = introducerIdInput.trim().replace(/^@/, '');
    if (!cleanId) {
      setError('Please enter an Introducer User ID.');
      return;
    }

    if (hasIntroducer) {
      setError('Your introducer is already assigned.');
      return;
    }

    setIsSubmitting(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await accountApi.claimIntroducer({ introducer_id: cleanId });
      if (res && res.success) {
        setSuccessMsg(res.message || 'Introducer added successfully.');
        setCurrentIntroducer(res.introducer || { user_id: cleanId, name: cleanId });
        setIntroducerIdInput('');
        setPreview(null);
        if (setUser && res.member) {
          setUser((prev) => ({
            ...prev,
            ...res.member,
            introducer_id: res.member.introducer_id || cleanId,
          }));
        }
        onIntroducerAdded?.(res);
      } else {
        setError(res?.message || 'Failed to add introducer.');
      }
    } catch (err) {
      const msg =
        err.response?.data?.errors?.introducer_id?.[0] ||
        err.response?.data?.message ||
        'Failed to add introducer. Please verify the ID and try again.';
      setError(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <section
      className="member-card verification-card introducer-settings-card"
      style={{
        background: '#ffffff',
        borderRadius: '16px',
        border: '1px solid #e5e7eb',
        padding: '24px',
        display: 'flex',
        flexDirection: 'column',
        boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.05)',
        width: '100%',
        maxWidth: '100%',
        minWidth: 0,
        boxSizing: 'border-box',
        overflow: 'hidden',
        ...style,
      }}
    >
      {/* Header */}
      <header
        className="introducer-card__header"
        style={{
          display: 'flex',
          alignItems: 'flex-start',
          justifyContent: 'space-between',
          marginBottom: '16px',
          paddingBottom: '14px',
          borderBottom: '1px solid #f0f0f0',
          flexWrap: 'wrap',
          gap: '10px',
          width: '100%',
          minWidth: 0,
          boxSizing: 'border-box',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', minWidth: 0, flex: '1 1 auto' }}>
          <div
            style={{
              width: '40px',
              height: '40px',
              borderRadius: '10px',
              background: hasIntroducer ? '#ecfdf5' : '#eff6ff',
              color: hasIntroducer ? '#059669' : '#2563eb',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0,
            }}
          >
            {hasIntroducer ? <UserCheck size={20} /> : <UserPlus size={20} />}
          </div>
          <div style={{ minWidth: 0, flex: 1 }}>
            <h2 style={{ fontSize: '16px', fontWeight: 700, color: '#111827', margin: '0 0 2px 0', overflowWrap: 'break-word', wordBreak: 'break-word' }}>
              Referral / Introducer
            </h2>
            <p style={{ margin: 0, color: '#6b7280', fontSize: '12px', overflowWrap: 'break-word', wordBreak: 'break-word' }}>
              {hasIntroducer
                ? 'Your verified introducer relationship is active.'
                : 'Link the verified member who introduced you to MLM Book.'}
            </p>
          </div>
        </div>

        <span
          className="introducer-card__status-badge"
          style={{
            fontSize: '11px',
            fontWeight: 700,
            padding: '4px 10px',
            borderRadius: '999px',
            background: hasIntroducer ? '#dcfce7' : '#f1f5f9',
            color: hasIntroducer ? '#15803d' : '#64748b',
            display: 'inline-flex',
            alignItems: 'center',
            gap: '4px',
            flexShrink: 0,
            alignSelf: 'flex-start',
          }}
        >
          {hasIntroducer ? (
            <>
              <BadgeCheck size={13} />
              <span>Assigned</span>
            </>
          ) : (
            <span>Not Assigned</span>
          )}
        </span>
      </header>

      {/* Global Alerts */}
      {error && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '10px 14px',
            background: '#fee2e2',
            color: '#b91c1c',
            borderRadius: '8px',
            fontSize: '13px',
            marginBottom: '14px',
          }}
        >
          <AlertCircle size={16} style={{ flexShrink: 0 }} />
          <span>{error}</span>
        </div>
      )}

      {successMsg && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '10px 14px',
            background: '#dcfce7',
            color: '#15803d',
            borderRadius: '8px',
            fontSize: '13px',
            marginBottom: '14px',
          }}
        >
          <CheckCircle2 size={16} style={{ flexShrink: 0 }} />
          <span>{successMsg}</span>
        </div>
      )}

      {/* STATE A: INTRODUCER IS ASSIGNED (IMMUTABLE) */}
      {hasIntroducer ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '14px', width: '100%', minWidth: 0 }}>
          <div
            className="introducer-profile-box"
            style={{
              background: '#f8fafc',
              padding: '14px 16px',
              borderRadius: '12px',
              border: '1px solid #e2e8f0',
              display: 'flex',
              alignItems: 'center',
              gap: '14px',
              width: '100%',
              minWidth: 0,
              boxSizing: 'border-box',
            }}
          >
            <MemberAvatar member={currentIntroducer} size={44} />

            <div style={{ flex: 1, minWidth: 0, overflow: 'hidden' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap', minWidth: 0 }}>
                <strong style={{ fontSize: '14.5px', color: '#0f172a', overflowWrap: 'anywhere', wordBreak: 'break-word' }}>
                  {currentIntroducer?.name || 'Verified Introducer'}
                </strong>
                <span
                  style={{
                    fontSize: '11px',
                    color: '#059669',
                    background: '#ecfdf5',
                    padding: '2px 6px',
                    borderRadius: '6px',
                    fontWeight: 700,
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '3px',
                    flexShrink: 0,
                  }}
                >
                  <BadgeCheck size={12} />
                  <span>Verified Referrer</span>
                </span>
              </div>
              <span style={{ display: 'block', fontSize: '12.5px', color: '#64748b', marginTop: '2px', overflowWrap: 'anywhere', wordBreak: 'break-all' }}>
                @{currentIntroducer?.user_id || member?.introducer_id}
              </span>
            </div>
          </div>

          <div
            style={{
              padding: '10px 14px',
              borderRadius: '8px',
              background: '#f8fafc',
              border: '1px solid #f1f5f9',
              fontSize: '12px',
              color: '#64748b',
              display: 'flex',
              alignItems: 'flex-start',
              gap: '8px',
              width: '100%',
              minWidth: 0,
              boxSizing: 'border-box',
            }}
          >
            <ShieldAlert size={14} color="#64748b" style={{ flexShrink: 0, marginTop: '2px' }} />
            <span style={{ minWidth: 0, flex: 1, overflowWrap: 'anywhere', wordBreak: 'break-word', lineHeight: 1.45 }}>
              Your introducer relationship is permanently established and cannot be changed or removed.
            </span>
          </div>
        </div>
      ) : (
        /* STATE B: NO INTRODUCER ASSIGNED — ADD INTRODUCER FORM */
        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
          <div
            style={{
              background: '#f8fafc',
              padding: '12px 14px',
              borderRadius: '10px',
              fontSize: '12.5px',
              color: '#475569',
              lineHeight: 1.45,
            }}
          >
            You don’t have an introducer assigned yet. If you registered without a referral code or skipped the introducer step during Google Signup, enter a valid, mobile-verified Introducer ID below.
          </div>

          <div>
            <label
              htmlFor="introducer-id-input"
              style={{
                display: 'block',
                fontSize: '13px',
                fontWeight: 600,
                color: '#374151',
                marginBottom: '6px',
              }}
            >
              Introducer User ID
            </label>
            <div style={{ position: 'relative' }}>
              <input
                id="introducer-id-input"
                type="text"
                value={introducerIdInput}
                onChange={(e) => setIntroducerIdInput(e.target.value)}
                placeholder="e.g. teart3"
                maxLength={30}
                autoComplete="off"
                disabled={isSubmitting}
                className="form-control"
                style={{
                  width: '100%',
                  padding: '10px 14px 10px 28px',
                  borderRadius: '10px',
                  border: preview?.valid ? '1px solid #10b981' : error ? '1px solid #ef4444' : '1px solid #d1d5db',
                  fontSize: '14px',
                  boxSizing: 'border-box',
                  background: isSubmitting ? '#f1f5f9' : '#ffffff',
                }}
              />
              <span
                style={{
                  position: 'absolute',
                  left: '12px',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  color: '#9ca3af',
                  fontSize: '14px',
                  fontWeight: 600,
                }}
              >
                @
              </span>
              {isChecking && (
                <div
                  style={{
                    position: 'absolute',
                    right: '12px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    color: '#3b82f6',
                  }}
                >
                  <Loader2 size={16} className="animate-spin" />
                </div>
              )}
            </div>
            <p style={{ margin: '4px 0 0 0', fontSize: '11.5px', color: '#9ca3af' }}>
              Only mobile-verified members are eligible to be assigned as introducers.
            </p>
          </div>

          {/* Valid Introducer Live Preview Box */}
          {preview?.valid && (
            <div
              style={{
                background: '#f0fdf4',
                border: '1px solid #bbf7d0',
                borderRadius: '10px',
                padding: '10px 14px',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
              }}
            >
              <div
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '50%',
                  background: '#10b981',
                  color: '#ffffff',
                  display: 'grid',
                  placeItems: 'center',
                  fontSize: '12px',
                  fontWeight: 700,
                  flexShrink: 0,
                }}
              >
                {getInitials(preview.name || preview.user_id)}
              </div>
              <div style={{ flex: 1, minWidth: 0 }}>
                <strong style={{ fontSize: '13px', color: '#065f46', display: 'block' }}>
                  {preview.name}
                </strong>
                <span style={{ fontSize: '11.5px', color: '#047857' }}>
                  @{preview.user_id} • Eligible Verified Referrer
                </span>
              </div>
              <BadgeCheck size={18} color="#10b981" />
            </div>
          )}

          <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '4px' }}>
            <button
              type="submit"
              className="member-button member-button--primary"
              disabled={isSubmitting || isChecking || !introducerIdInput.trim() || (preview && !preview.valid)}
              style={{
                fontSize: '13px',
                padding: '9px 18px',
                borderRadius: '10px',
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
              }}
            >
              {isSubmitting ? (
                <>
                  <Loader2 size={14} className="animate-spin" />
                  <span>Verifying & Adding...</span>
                </>
              ) : (
                <>
                  <UserPlus size={14} />
                  <span>Verify & Add Introducer</span>
                </>
              )}
            </button>
          </div>
        </form>
      )}
    </section>
  );
}

export default IntroducerSettingsCard;
