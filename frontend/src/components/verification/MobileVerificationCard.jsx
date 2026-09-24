import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  CheckCircle2,
  MessageSquare,
  AlertCircle,
  ArrowRight,
  Clock,
  ExternalLink,
  ShieldCheck,
  Lock,
} from 'lucide-react';
import verificationApi from '../../api/verificationApi';
import useAuth from '../../hooks/useAuth';
import VerifiedBadge from '../common/VerifiedBadge';
import { resolveWhatsAppVerificationUrl, isMemberMobileVerified } from '../../utils/whatsappVerification';

export function MobileVerificationCard({
  member: propMember,
  onVerified,
  showProfileLink = true,
  cardClassName = 'card verification-card',
  style,
}) {
  const { user, setUser, refreshUser } = useAuth();
  const currentMember = propMember || user;

  // Steps: 'verified_hub' | 'pending_hub' | 'ready' | 'send_hi'
  const [step, setStep] = useState('ready');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [successMsg, setSuccessMsg] = useState(null);
  const [whatsappUrl, setWhatsappUrl] = useState('');
  const [whatsappDestination, setWhatsappDestination] = useState('');
  const [maskedPhone, setMaskedPhone] = useState('');

  const formatMaskedPhone = (phone) => {
    if (!phone) return '';
    const clean = phone.replace(/[^\d+]/g, '');
    if (clean.length <= 6) return clean;
    const start = clean.slice(0, clean.startsWith('+') ? 5 : 4);
    const end = clean.slice(-2);
    return `${start}****${end}`;
  };

  const isVerified = isMemberMobileVerified(currentMember);
  const isPending = Boolean(
    !isVerified && (
      currentMember?.is_verification_pending ||
      currentMember?.mobile_verification_requested_at ||
      currentMember?.verification_status === 'pending'
    )
  );

  useEffect(() => {
    let isMounted = true;

    if (isVerified) {
      setStep('verified_hub');
    } else if (isPending) {
      setStep('pending_hub');
    } else {
      setStep('ready');
    }

    if (currentMember?.phone) {
      setMaskedPhone(formatMaskedPhone(currentMember.phone));
    }

    verificationApi.getStatus()
      .then((res) => {
        if (!isMounted || !res) return;
        if (res.masked_phone) {
          setMaskedPhone(res.masked_phone);
        } else if (res.phone) {
          setMaskedPhone(formatMaskedPhone(res.phone));
        }
        if (res.whatsapp_url) {
          setWhatsappUrl(resolveWhatsAppVerificationUrl(res.whatsapp_url, res.whatsapp_destination || whatsappDestination, res.member || currentMember));
        }
        if (res.whatsapp_destination) {
          setWhatsappDestination(res.whatsapp_destination);
        }
        if (res.is_verified) {
          setStep('verified_hub');
          setError(null);
          const verifiedMemberData = res.member || {
            ...user,
            is_verified: true,
            mobile_verified_at: res.mobile_verified_at || new Date().toISOString(),
            verification_status: 'verified',
          };
          if (setUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            setUser((prev) => ({
              ...prev,
              ...verifiedMemberData,
              is_verified: true,
              mobile_verified_at: res.mobile_verified_at || verifiedMemberData.mobile_verified_at || prev?.mobile_verified_at,
              verification_status: 'verified',
            }));
          }
          if (refreshUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            refreshUser();
          }
        } else if (res.is_pending || res.mobile_verification_requested_at) {
          setStep('pending_hub');
        }
      })
      .catch((err) => {
        console.warn('Could not load verification status for card:', err);
      });

    return () => {
      isMounted = false;
    };
  }, [isVerified, isPending, currentMember, user, setUser, refreshUser, whatsappDestination]);

  const handleProceedToWhatsApp = async () => {
    setIsLoading(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await verificationApi.initiateVerification();
      if (res && res.success) {
        if (res.whatsapp_url) setWhatsappUrl(resolveWhatsAppVerificationUrl(res.whatsapp_url, res.whatsapp_destination || whatsappDestination, res.member || currentMember));
        if (res.masked_phone) setMaskedPhone(res.masked_phone);
        if (res.is_verified) {
          setStep('verified_hub');
          setError(null);
          const verifiedMemberData = res.member || {
            ...user,
            is_verified: true,
            mobile_verified_at: res.mobile_verified_at || new Date().toISOString(),
            verification_status: 'verified',
          };
          if (setUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            setUser((prev) => ({
              ...prev,
              ...verifiedMemberData,
              is_verified: true,
              mobile_verified_at: res.mobile_verified_at || verifiedMemberData.mobile_verified_at || prev?.mobile_verified_at,
              verification_status: 'verified',
            }));
          }
          if (refreshUser && (!user?.is_verified || !user?.mobile_verified_at)) {
            refreshUser();
          }
          onVerified?.(verifiedMemberData);
          return;
        }
        if (res.is_pending) {
          setStep('pending_hub');
          return;
        }
        setStep('send_hi');
      } else {
        setError(res?.message || 'Unable to initiate WhatsApp verification. Please try again.');
      }
    } catch (err) {
      const msg = err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Unable to proceed to WhatsApp verification.';
      setError(msg);
    } finally {
      setIsLoading(false);
    }
  };

  const handleOpenWhatsApp = () => {
    const targetUrl = resolveWhatsAppVerificationUrl(whatsappUrl, whatsappDestination, currentMember);
    window.open(targetUrl, '_blank', 'noopener,noreferrer');
  };

  const handleSubmitSentHi = async () => {
    setIsLoading(true);
    setError(null);
    setSuccessMsg(null);

    try {
      const res = await verificationApi.submitVerificationRequest();
      if (res && res.success) {
        if (setUser && res.member) {
          setUser((prev) => ({
            ...prev,
            ...res.member,
            is_verified: false,
            is_verification_pending: true,
            verification_status: 'pending',
            mobile_verification_requested_at: res.mobile_verification_requested_at || new Date().toISOString(),
          }));
        }
        setStep('pending_hub');
        setSuccessMsg('Your verification request has been submitted and is pending admin approval.');
      } else {
        setError(res?.message || 'Failed to submit verification request. Please try again.');
      }
    } catch (err) {
      let msg = err.response?.data?.errors?.request?.[0]
        || err.response?.data?.errors?.phone?.[0]
        || err.response?.data?.message
        || 'Failed to submit verification request.';

      if (typeof msg !== 'string' || msg.includes('SQLSTATE') || msg.includes('QueryException')) {
        msg = 'Failed to submit verification request. Please try again later.';
      }

      setError(msg);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div style={style}>
      {error && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '14px 16px',
            borderRadius: '14px',
            background: '#fef2f2',
            border: '1px solid #fecaca',
            color: '#b91c1c',
            fontSize: '13.5px',
            marginBottom: '20px',
          }}
        >
          <AlertCircle size={20} style={{ flexShrink: 0 }} />
          <div style={{ flex: 1 }}>{error}</div>
        </div>
      )}

      {successMsg && (
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            padding: '14px 16px',
            borderRadius: '14px',
            background: '#f0fdf4',
            border: '1px solid #bbf7d0',
            color: '#15803d',
            fontSize: '13.5px',
            marginBottom: '20px',
          }}
        >
          <CheckCircle2 size={20} style={{ flexShrink: 0 }} />
          <div style={{ flex: 1 }}>{successMsg}</div>
        </div>
      )}

      {/* STATE 1: ALREADY VERIFIED HUB */}
      {step === 'verified_hub' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            textAlign: 'center',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div
            style={{
              width: '80px',
              height: '80px',
              borderRadius: '24px',
              background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
              boxShadow: '0 10px 25px -5px rgba(16, 185, 129, 0.4)',
            }}
          >
            <CheckCircle2 size={44} color="#ffffff" />
          </div>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              background: '#f0fdf4',
              border: '1px solid #bbf7d0',
              padding: '6px 14px',
              borderRadius: '999px',
              color: '#047857',
              fontWeight: 700,
              fontSize: '13px',
              marginBottom: '12px',
            }}
          >
            <VerifiedBadge member={currentMember} size={15} />
            <span>Verified WhatsApp Member</span>
          </span>

          <h2 style={{ fontSize: '20px', fontWeight: 800, color: '#0f172a', margin: '0 0 8px 0' }}>
            Your Account is Fully Verified
          </h2>

          <p style={{ fontSize: '13.5px', color: '#64748b', margin: '0 0 20px 0', lineHeight: 1.5 }}>
            Your registered mobile number <strong>{maskedPhone || currentMember?.phone}</strong> is
            verified. Your official green tick badge is displayed across all your posts, profile, and
            community interactions.
          </p>

          <div
            style={{
              display: 'grid',
              gridTemplateColumns: '1fr 1fr',
              gap: '12px',
              padding: '14px',
              borderRadius: '14px',
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              textAlign: 'left',
              marginBottom: '20px',
            }}
          >
            <div>
              <span style={{ fontSize: '11px', color: '#64748b', display: 'block' }}>
                Verified Number
              </span>
              <strong style={{ fontSize: '13.5px', color: '#0f172a' }}>
                {maskedPhone || currentMember?.phone || 'Verified'}
              </strong>
            </div>
            <div>
              <span style={{ fontSize: '11px', color: '#64748b', display: 'block' }}>
                Verification Channel
              </span>
              <strong
                style={{
                  fontSize: '13.5px',
                  color: '#10b981',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '4px',
                }}
              >
                <MessageSquare size={13} /> WhatsApp
              </strong>
            </div>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'center', flexWrap: 'wrap' }}>
            {showProfileLink && (
              <Link
                to="/member/profile"
                className="member-button member-button--primary"
                style={{ flex: '1 1 140px', justifyContent: 'center', textAlign: 'center' }}
              >
                <span>View My Profile</span>
              </Link>
            )}
          </div>
        </div>
      )}

      {/* STATE 2: VERIFICATION PENDING HUB */}
      {step === 'pending_hub' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            textAlign: 'center',
            background: '#ffffff',
            border: '1px solid #fde68a',
          }}
        >
          <div
            style={{
              width: '72px',
              height: '72px',
              borderRadius: '22px',
              background: 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              margin: '0 auto 16px',
              boxShadow: '0 8px 20px -4px rgba(217, 119, 6, 0.35)',
            }}
          >
            <Clock size={38} color="#ffffff" />
          </div>

          <span
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              background: '#fef3c7',
              border: '1px solid #fde68a',
              padding: '6px 14px',
              borderRadius: '999px',
              color: '#b45309',
              fontWeight: 700,
              fontSize: '13px',
              marginBottom: '12px',
            }}
          >
            <Clock size={14} color="#d97706" />
            <span>Verification Pending Admin Review</span>
          </span>

          <h2 style={{ fontSize: '19px', fontWeight: 800, color: '#0f172a', margin: '0 0 8px 0' }}>
            Verification Request Submitted
          </h2>

          <p style={{ fontSize: '13.5px', color: '#64748b', margin: '0 0 20px 0', lineHeight: 1.5 }}>
            We received your request from <strong>{maskedPhone || currentMember?.phone}</strong>. Our administrators
            manually verify each incoming WhatsApp "Hi" message to maintain network authenticity.
          </p>

          <div
            style={{
              background: '#fffbeb',
              border: '1px solid #fde68a',
              borderRadius: '14px',
              padding: '14px 16px',
              textAlign: 'left',
              fontSize: '12.5px',
              color: '#92400e',
              lineHeight: 1.45,
              marginBottom: '20px',
            }}
          >
            <strong>What happens next?</strong>
            <p style={{ margin: '4px 0 0 0' }}>
              Once an administrator reviews your WhatsApp message, your account will be activated with the Green Verified Tick badge.
            </p>
          </div>

          <div style={{ display: 'flex', gap: '10px', justifyContent: 'center' }}>
            <button
              type="button"
              onClick={handleOpenWhatsApp}
              className="member-button member-button--secondary"
              style={{ fontSize: '13px', gap: '6px' }}
            >
              <MessageSquare size={14} />
              <span>Open WhatsApp Again</span>
              <ExternalLink size={13} />
            </button>
          </div>
        </div>
      )}

      {/* STATE 3: READY TO VERIFY (CONFIRM REGISTERED PHONE) */}
      {step === 'ready' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '20px' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '6px' }}>
              <ShieldCheck size={20} color="#059669" />
              <h2 style={{ fontSize: '18px', fontWeight: 800, margin: 0, color: '#0f172a' }}>
                Verify Mobile via WhatsApp
              </h2>
            </div>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
              Verify your registered WhatsApp mobile number to unlock verified member benefits.
            </p>
          </div>

          {/* Registered Phone Read-Only Box */}
          <div
            style={{
              background: '#f8fafc',
              border: '1.5px solid #e2e8f0',
              borderRadius: '14px',
              padding: '16px',
              marginBottom: '20px',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '8px' }}>
              <span style={{ fontSize: '11.5px', fontWeight: 700, color: '#64748b', textTransform: 'uppercase' }}>
                Registered WhatsApp Number
              </span>
              <span
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '4px',
                  fontSize: '11px',
                  fontWeight: 700,
                  color: '#059669',
                  background: '#ecfdf5',
                  padding: '2px 8px',
                  borderRadius: '999px',
                }}
              >
                <Lock size={11} />
                <span>From Registration</span>
              </span>
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
              <div
                style={{
                  width: '38px',
                  height: '38px',
                  borderRadius: '10px',
                  background: '#e0f2fe',
                  color: '#0284c7',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <MessageSquare size={18} />
              </div>
              <div>
                <strong style={{ fontSize: '16px', color: '#0f172a' }}>
                  {maskedPhone || currentMember?.phone || 'No phone registered'}
                </strong>
                <div style={{ fontSize: '11.5px', color: '#64748b' }}>
                  No OTP required. Verify directly by sending "Hi" on WhatsApp.
                </div>
              </div>
            </div>
          </div>

          <button
            type="button"
            className="member-button member-button--primary"
            onClick={handleProceedToWhatsApp}
            disabled={isLoading || (!maskedPhone && !currentMember?.phone)}
            style={{
              width: '100%',
              padding: '13px',
              borderRadius: '12px',
              background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
              color: '#ffffff',
              fontSize: '14.5px',
              fontWeight: 700,
              justifyContent: 'center',
              gap: '8px',
              border: 'none',
              cursor: isLoading ? 'not-allowed' : 'pointer',
            }}
          >
            <span>{isLoading ? 'Preparing WhatsApp Link...' : 'Continue to WhatsApp Verification'}</span>
            {!isLoading && <ArrowRight size={16} />}
          </button>
        </div>
      )}

      {/* STATE 4: SEND HI INSTRUCTIONS */}
      {step === 'send_hi' && (
        <div
          className={cardClassName}
          style={{
            padding: '28px',
            borderRadius: '20px',
            background: '#ffffff',
            border: '1px solid #e2e8f0',
          }}
        >
          <div style={{ marginBottom: '18px' }}>
            <h2 style={{ fontSize: '18px', fontWeight: 800, margin: '0 0 6px 0', color: '#0f172a' }}>
              Send "Hi" to Verify
            </h2>
            <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
              Follow the instructions below to complete your verification request.
            </p>
          </div>

          <div
            style={{
              background: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '14px',
              padding: '16px',
              marginBottom: '20px',
            }}
          >
            <ol style={{ margin: 0, paddingLeft: '18px', fontSize: '13px', color: '#334155', lineHeight: 1.6 }}>
              <li>Tap <strong>"Open WhatsApp"</strong> below to open the chat with <strong>"Hi"</strong> pre-filled.</li>
              <li>Send <strong>"Hi"</strong> from your registered number (<strong>{maskedPhone || currentMember?.phone}</strong>).</li>
              <li>Return here and tap <strong>"I have sent Hi"</strong>.</li>
            </ol>
          </div>

          <button
            type="button"
            onClick={handleOpenWhatsApp}
            style={{
              width: '100%',
              padding: '13px',
              borderRadius: '12px',
              background: '#25D366',
              color: '#ffffff',
              fontSize: '14px',
              fontWeight: 700,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '8px',
              border: 'none',
              cursor: 'pointer',
              marginBottom: '12px',
            }}
          >
            <MessageSquare size={17} />
            <span>Open WhatsApp (Pre-filled "Hi")</span>
            <ExternalLink size={14} />
          </button>

          <button
            type="button"
            onClick={handleSubmitSentHi}
            disabled={isLoading}
            className="member-button member-button--primary"
            style={{
              width: '100%',
              padding: '13px',
              borderRadius: '12px',
              background: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
              color: '#ffffff',
              fontSize: '14.5px',
              fontWeight: 700,
              justifyContent: 'center',
              gap: '8px',
              border: 'none',
              cursor: isLoading ? 'not-allowed' : 'pointer',
            }}
          >
            <CheckCircle2 size={16} />
            <span>{isLoading ? 'Submitting Request...' : 'I have sent Hi'}</span>
          </button>

          <div style={{ textAlign: 'center', marginTop: '14px' }}>
            <button
              type="button"
              onClick={() => setStep('ready')}
              style={{
                background: 'none',
                border: 'none',
                color: '#64748b',
                fontSize: '12px',
                cursor: 'pointer',
                padding: 0,
              }}
            >
              ← Back to phone details
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default MobileVerificationCard;
